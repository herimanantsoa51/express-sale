<?php

namespace App\Http\Controllers;

use App\Enums\ActivityAction;
use App\Enums\SaleType;
use App\Helpers\ActivityLogger;
use App\Http\Requests\StoreImmediateSaleRequest;
use App\Http\Resources\ImmediateSaleDetailResource;
use App\Http\Resources\ImmediateSaleListResource;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\ImmediateSaleService;
use App\Services\PosPrintService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImmediateSaleController extends Controller
{
    protected ImmediateSaleService $immediateSaleService;

    protected PosPrintService $posPrintService;

    public function __construct(ImmediateSaleService $immediateSaleService, PosPrintService $posPrintService)
    {
        $this->immediateSaleService = $immediateSaleService;
        $this->posPrintService = $posPrintService;
    }

    /**
     * GET /api/sales/immediate
     * Liste des ventes immédiates avec filtres, recherche et pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);

        $query = Sale::query()
            ->where('sale_type', SaleType::IMMEDIATE)
            ->with([
                'customer:id,name,customer_number,loyalty_points',
                'user:id,name',
                'accountTransaction:id,sale_id,reference_number',
            ]);

        // filtre date
        if ($request->filled(['from_date', 'to_date'])) {
            $from = Carbon::parse($request->from_date)->utc();
            $to = Carbon::parse($request->to_date)->utc();

            Log::info("Filtering immediate sales from {$from} to {$to}");

            $query->whereBetween('sale_date', [$from, $to]);
        }

        // filtre vendeur
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // recherche
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'ILIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('customer_number', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('accountTransaction', function ($t) use ($search) {
                        $t->where('reference_number', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $query->orderBy('created_at', 'desc');

        return ImmediateSaleListResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * GET /api/sales/immediate/{id}
     * Détail d'une vente immédiate
     */
    public function show(int $id)
    {
        $sale = Sale::where('sale_type', SaleType::IMMEDIATE)
            ->with([
                'customer:id,name,customer_number,loyalty_points',
                'user:id,name',
                'items.variant.product:id,name',
                'items.variant.attributeValues.attributeValue.attributeType',
                'accountTransaction.account',
                'accountTransaction.transactionType',
            ])
            ->findOrFail($id);

        return new ImmediateSaleDetailResource($sale);
    }

    /**
     * POST /api/sales/immediate
     * Créer une vente immédiate
     */
    public function store(StoreImmediateSaleRequest $request): JsonResponse
    {
        try {
            $sale = $this->immediateSaleService->createImmediateSale($request->validated());

            // Impression automatique si activée
            $printResult = $this->posPrintService->autoPrintIfEnabled(
                'immediate_sale',
                fn () => $this->posPrintService->printSale($sale)
            );
            ActivityLogger::success(
                ActivityAction::SALE_CREATED,
                " a créé une vente rapide d'une valeur de {$sale->total_amount}",
                [
                    'model_type' => Sale::class,
                    'model_id' => $sale->id,
                    'metadata' => $sale->toArray(),
                ],
                "ventes/immediate/{$sale->id}"
            );

            return response()->json([
                'message' => 'Vente créée avec succès',
                'data' => new SaleResource($sale),
                'print_info' => $printResult,
            ], 201);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::SALE_CREATED,
                ' la creation de la vente rapide a échoué',
                $e,
                [
                    'metadata' => $request->validated(),
                ]
            );
            Log::error('Erreur création vente immédiate', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création de la vente',
                'error' => $e->getMessage(),
                'debug' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 422);
        }
    }

    /**
     * POST /api/sales/immediate/cancel/{sale}
     * Annuler une vente immédiate
     */
    public function cancel(Sale $sale): JsonResponse
    {
        try {
            $sale = $this->immediateSaleService->cancelImmediateSale($sale);
            ActivityLogger::success(
                ActivityAction::SALE_CANCELLED,
                " a annulée la vente rapide {$sale->sale_number}",
                [
                    'model_type' => Sale::class,
                    'model_id' => $sale->id,
                    'metadata' => $sale->toArray(),
                ],
                "ventes/immediate/{$sale->id}"
            );

            return response()->json([
                'message' => 'Vente immédiate annulée avec succès',
            ]);
        } catch (\Exception $e) {
            ActivityLogger::error(
                ActivityAction::SALE_CANCELLED,
                " l'annulation d'une vente rapide a échouée {$sale->sale_number}",
                $e,
                [
                    'metadata' => $sale->toArray(),
                ]
            );

            return response()->json([
                'message' => 'Erreur lors de l\'annulation de la réservation immédiate',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
