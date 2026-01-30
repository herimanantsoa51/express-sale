<?php

namespace App\Http\Controllers;

use App\Models\CashCount;
use App\Models\CashCountDenomination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PosPrintService;
use App\Helpers\ActivityLogger;
use  App\Enums\ActivityAction;

class CashCountController extends Controller
{
    /**
     *  Liste paginée des comptages de caisse
     * Filtres:
     * - from_date
     * - to_date
     */
    protected PosPrintService $posPrintService;


    public function __construct()
    {
        $this->posPrintService = new PosPrintService();
    }   
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 10);

        $cashCounts = CashCount::query()
            ->select([
                'id',
                'count_date',
                'total_amount',
                'created_by',
                'notes',
                'created_at',
            ])
            ->with([
                'creator:id,name'
            ])
            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('count_date', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('count_date', '<=', $request->to_date);
            })
            ->orderBy('count_date', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => $cashCounts->items(),
            'meta' => [
                'current_page' => $cashCounts->currentPage(),
                'last_page' => $cashCounts->lastPage(),
                'per_page' => $cashCounts->perPage(),
                'total' => $cashCounts->total(),
            ],
        ]);
    }

    /**
     * 📌 Détail d’un comptage (sans validation)
     */
    public function show(int $id): JsonResponse
    {
        $cashCount = CashCount::query()
            ->select([
                'id',
                'count_date',
                'created_by',
                'total_amount',
                'notes',
                'created_at',
            ])
            ->with([
                'creator:id,name',
                'denominations:id,cash_count_id,denomination,quantity,subtotal'
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id'=>$cashCount->id,
                'count_date'=>$cashCount->count_date,
                'created_by'=>$cashCount->creator,
                'total_amount'=>$cashCount->total_amount,
                'notes'=>$cashCount->notes,
                'denominations'=> $cashCount->denominations->map(function ($item) {
                    return [
                        'denomination' => $item->denomination,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->subtotal,
                    ];
                }),
            ]
        ]);
    }

    /**
     * ✏️ Mise à jour d’un comptage
     * (recalcul du total obligatoire)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'count_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'denominations' => ['required', 'array'],
            'denominations.*.denomination' => ['required', 'integer', 'min:1'],
            'denominations.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $id) {

            $cashCount = CashCount::with('denominations')->findOrFail($id);

            // Mise à jour info principale
            $cashCount->update([
                'count_date' => $request->count_date,
                'notes' => $request->notes,
            ]);

            // Reset des coupures
            $cashCount->denominations()->delete();

            $total = 0;

            foreach ($request->denominations as $item) {
                $subtotal = $item['denomination'] * $item['quantity'];
                $total += $subtotal;

                $cashCount->denominations()->create([
                    'denomination' => $item['denomination'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ]);
            }
            ActivityLogger::success(
                ActivityAction::CASH_COUNT_UPDATED,
                "a modifié une comptage de billet d'un total de {$total} Ar.",
                [
                    "model_type"=>CashCount::class,
                    "model_id"=>$cashCount->id,
                    "metadata"=>[
                        "old"=>[
                            'count_date'=>$cashCount->getOriginal('count_date'),
                            'notes'=>$cashCount->getOriginal('notes'),
                            'denominations'=> $cashCount->denominations->map(function ($item) {
                                return [
                                    'denomination' => $item->denomination,
                                    'quantity' => $item->quantity,
                                    'subtotal' => $item->subtotal,
                                ];
                            })->toArray()
                        ],           
                        "new"=>$request->toArray()
                    ]
                    ],
                    "comptages/{$cashCount->id}"
            );
            $cashCount->update([
                'total_amount' => $total,
            ]);
        });


        return response()->json([
            'message' => 'Comptage de caisse mis à jour avec succès'
        ]);
    }


     /**
     * POST /api/cash-counts
     * Créer un comptage de caisse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'count_date' => ['required', 'date', 'unique:cash_counts,count_date'],
            'notes' => ['nullable', 'string'],
            'denominations' => ['required', 'array', 'min:1'],
            'denominations.*.denomination' => ['required', 'integer', 'min:1'],
            'denominations.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        return DB::transaction(function () use ($data) {
            // Création du comptage principal
            $cashCount = CashCount::create([
                'count_date' => $data['count_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
                'total_amount' => 0, // recalculé après
            ]);

            $total = 0;

            foreach ($data['denominations'] as $row) {
                $subtotal = $row['denomination'] * $row['quantity'];

                CashCountDenomination::create([
                    'cash_count_id' => $cashCount->id,
                    'denomination' => $row['denomination'],
                    'quantity' => $row['quantity'],
                    'subtotal' => $subtotal,
                ]);

                $total += $subtotal;
            }

            // Mise à jour du total
            $cashCount->update([
                'total_amount' => $total,
            ]);

            // Charger les relations pour l'impression
            $cashCount->load(['creator', 'denominations']);

            // ✅ Impression automatique si activée
            $printResult = $this->posPrintService->autoPrintIfEnabled(
                'cash_count',
                fn() => $this->posPrintService->printCashCount($cashCount)
            );
            ActivityLogger::success(
                ActivityAction::CASH_COUNT_CREATED,
                "a créé une comptage de billet d'un total de {$total} Ar.",
                [
                        "model_type"=>CashCount::class,
                        "model_id"=>$cashCount->id,
                        "metadata"=>$cashCount->toArray()
                ],
                "comptages/{$cashCount->id}"
            );
            return response()->json([
                'message' => 'Comptage enregistré avec succès',
                'data' => $cashCount,
                'print_info' => $printResult,
            ], 201);
        });
    }

}
