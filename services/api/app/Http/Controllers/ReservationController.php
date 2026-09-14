<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Helpers\ActivityLogger;
use App\Http\Requests\CompleteReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationListResource;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Sale;
use App\Services\PosPrintService;
use App\Services\ReservationService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    protected ReservationService $reservationService;

    protected SaleService $saleService;

    protected PosPrintService $posPrintService;

    public function __construct(ReservationService $reservationService, SaleService $saleService, PosPrintService $posPrintService)
    {
        $this->reservationService = $reservationService;
        $this->saleService = $saleService;
        $this->posPrintService = $posPrintService;
    }

    /**
     * GET /api/reservations
     * Liste des réservations avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::with([
            'customer:id,name,customer_number',
            'user:id,name',
        ]);

        // Filtre par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par vendeur (user_id du sale)
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Recherche
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtre par date/heure de réservation (FROM)
        if ($request->filled('reservation_date_from')) {
            $query->where('reservation_date', '>=', $request->reservation_date_from);
        }

        // Filtre par date/heure de réservation (TO)
        if ($request->filled('reservation_date_to')) {
            $query->where('reservation_date', '<=', $request->reservation_date_to);
        }

        // Filtre par date/heure d'expiration (FROM)
        if ($request->filled('expiry_date_from')) {
            $query->where('expiry_date', '>=', $request->expiry_date_from);
        }

        // Filtre par date/heure d'expiration (TO)
        if ($request->filled('expiry_date_to')) {
            $query->where('expiry_date', '<=', $request->expiry_date_to);
        }

        // Filtrer les réservations actives
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Filtrer les réservations expirées
        if ($request->boolean('expired_only')) {
            $query->expired();
        }

        // Filtrer les réservations expirant bientôt
        if ($request->has('expiring_soon_days')) {
            $query->expiringSoon((int) $request->expiring_soon_days);
        }

        // Tri avec colonnes autorisées
        $allowedSortColumns = [
            'reservation_date',
            'expiry_date',
            'total_amount',
            'remaining_amount',
            'status',
            'sale_id',
            'created_at',
            'updated_at',
        ];

        $sortBy = in_array($request->get('sort_by'), $allowedSortColumns)
            ? $request->get('sort_by')
            : 'reservation_date';

        $sortOrder = in_array(strtolower($request->get('sort_order')), ['asc', 'desc'])
            ? $request->get('sort_order')
            : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->get('per_page', 15), 100);
        $reservations = $query->paginate($perPage);

        return response()->json([
            'data' => ReservationListResource::collection($reservations),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'total' => $reservations->total(),
            ],
        ]);
    }

    /**
     * GET /api/reservations/{id}
     */
    public function show(int $id): JsonResponse
    {
        $reservation = Reservation::with([
            'customer:id,name,phone,customer_number',
            'user:id,name',
            'items.variant.product:id,name,image_url',
            'items.variant.attributeValues.attributeValue.attributeType:id,name,display_name',
            'transactions' => function ($q) {
                $q->select(
                    'id',
                    'account_id',
                    'reservation_id',
                    'transaction_type_id',
                    'amount',
                    'notes',
                    'balance_before',
                    'balance_after',
                    'transaction_date',
                    'created_by'
                )->with([
                    'account:id,name',
                    'transactionType:id,name',
                    'creator:id,name',
                ]);
            },
        ])->findOrFail($id);

        return response()->json([
            'data' => new ReservationResource($reservation),
        ]);
    }

    /**
     * POST /api/sales/reservation
     * Créer une réservation
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        try {
            $reservation = $this->reservationService->createReservation($request->validated());

            $printResult = $this->posPrintService->autoPrintIfEnabled(
                'reservation',
                fn () => $this->posPrintService->printReservation($reservation)
            );
            ActivityLogger::success(
                ActivityAction::RESERVATION_CREATED,
                " a créé une réservation d'une valeur de {$reservation->total_amount}",
                [
                    'model_type' => Reservation::class,
                    'model_id' => $reservation->id,
                    'metadata' => $reservation->toArray(),
                ],
                "ventes/reservations/{$reservation->id}"
            );

            return response()->json([
                'message' => 'Réservation créée avec succès',
                'data' => new ReservationResource($reservation),
                'print_info' => $printResult,
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::RESERVATION_CREATED,
                " la création d'une réservation a échoué ",
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );
            Log::error('Erreur création réservation', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création de la réservation',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/reservations/{id}/complete
     * Compléter une réservation (paiement final)
     */
    public function complete(CompleteReservationRequest $request, int $id): JsonResponse
    {
        try {
            $reservation = $this->reservationService->completeReservation($id, $request->validated());

            $printResult = $this->posPrintService->autoPrintIfEnabled(
                'reservation_complete',
                fn () => $this->posPrintService->printReservationReceipt($reservation)
            );

            ActivityLogger::success(
                ActivityAction::RESERVATION_COMPLETED,
                " a reçu le paiement de finalisation d'une reservation",
                [
                    'model_type' => Reservation::class,
                    'model_id' => $reservation->id,
                    'metadata' => $reservation->toArray(),
                ],
                "ventes/reservations/{$reservation->id}"
            );

            return response()->json([
                'message' => 'Réservation complétée avec succès',
                'data' => new ReservationResource($reservation),
                'print_info' => $printResult,
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::RESERVATION_COMPLETED,
                " la finalisation d'une reservation a échoué",
                $e,
                [
                    'metadata' => $request->validated(),
                ],
            );
            Log::error('Erreur complétion réservation', [
                'reservation_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la complétion de la réservation',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/reservations/{id}/cancel
     * Annuler une réservation
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $reservation = $this->reservationService->cancelReservation($id, $request->reason);
            ActivityLogger::success(
                ActivityAction::RESERVATION_CANCELLED,
                " a annulée la reservation {$reservation->sale->sale_number}",
                [
                    'model_type' => Reservation::class,
                    'model_id' => $reservation->id,
                    'metadata' => $reservation->toArray(),
                ],
                "ventes/reservations/{$reservation->id}"
            );

            return response()->json([
                'message' => 'Réservation annulée avec succès',
                'data' => new ReservationResource($reservation),
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::RESERVATION_CANCELLED,
                " l'annulation d'une reservation a échoué",
                $e,
                [
                    'metadata' => $request,
                ],
            );

            return response()->json([
                'message' => 'Erreur lors de l\'annulation de la réservation',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/reservations/{id}/add-deposit
     * Ajouter un acompte supplémentaire à une réservation
     */
    public function addDeposit(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:accounts,id',
            'payment_method' => 'required|string',
        ]);

        try {
            $reservation = Reservation::findOrFail($id);

            if (! $reservation->isActive()) {
                throw new \Exception("Cette réservation n'est plus active");
            }

            $amount = min($request->amount, $reservation->remaining_amount);

            $this->saleService->createReservationTransaction(
                $reservation,
                $request->account_id,
                $amount,
                "Acompte supplémentaire réservation #{$reservation->reservation_number}",
            );

            $reservation->deposit_amount += $amount;
            $reservation->remaining_amount -= $amount;

            if ($reservation->remaining_amount <= 0) {
                $reservation->status = 'completed';
                $reservation->completed_at = now();
            } else {
                $reservation->status = 'partial_paid';
            }

            $reservation->save();

            return response()->json([
                'message' => 'Acompte ajouté avec succès',
                'data' => new ReservationResource($reservation->load(['transactions', 'customer'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'ajout de l\'acompte',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/reservations/expiring-soon
     * Réservations expirant bientôt
     */
    public function expiringSoon(Request $request): JsonResponse
    {
        $days = $request->get('days', 3);

        $reservations = Reservation::with(['customer', 'sale'])
            ->expiringSoon($days)
            ->orderBy('expiry_date')
            ->get();

        return response()->json([
            'data' => ReservationResource::collection($reservations),
            'summary' => [
                'count' => $reservations->count(),
                'total_remaining' => $reservations->sum('remaining_amount'),
                'days' => $days,
            ],
        ]);
    }

    /**
     * GET /api/reservations/expired
     * Réservations expirées
     */
    public function expired(): JsonResponse
    {
        $reservations = Reservation::with(['customer', 'sale'])
            ->expired()
            ->orderBy('expiry_date', 'desc')
            ->get();

        return response()->json([
            'data' => ReservationResource::collection($reservations),
            'summary' => [
                'count' => $reservations->count(),
                'total_deposits' => $reservations->sum('deposit_amount'),
            ],
        ]);
    }
}
