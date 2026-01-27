<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRate;
use App\Http\Requests\StoreCurrencyRateRequest;
use App\Http\Requests\UpdateCurrencyRateRequest;
use App\Http\Resources\CurrencyRateResource;
use App\Http\Resources\CurrencyRateCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Helpers\ActivityLogger;
use App\Enums\ActivityAction;


class CurrencyRateController extends Controller
{
    /**
     * Liste des taux de change avec pagination et filtres
     * GET /api/currency-rates
     */
    public function index(Request $request): CurrencyRateCollection
    {
        $query = CurrencyRate::with('creator');

        // Filtre par date
        if ($request->has('from_date')) {
            $query->where('effective_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('effective_date', '<=', $request->to_date);
        }

        // Filtre par mois/année
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('effective_date', $request->month)
                  ->whereYear('effective_date', $request->year);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'effective_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 20);
        $rates = $query->paginate($perPage);

        return new CurrencyRateCollection($rates);
    }

    /**
     * Créer un nouveau taux de change
     * POST /api/currency-rates
     */
    public function store(StoreCurrencyRateRequest $request): JsonResponse
{   


    try {
        $validated = $request->validated();

        // Vérifier unicité par date
        $existingRate = CurrencyRate::where('effective_date', $validated['effective_date'])->first();
    
        if ($existingRate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Un taux de change existe déjà pour cette date',
                'existing_rate' => new CurrencyRateResource($existingRate)
            ], 422);
        }
    
        $currencyRate = CurrencyRate::create($validated);
        ActivityLogger::success(
            ActivityAction::CURRENCY_RATE_CREATED,
            "Taux de change créé pour la date : {$currencyRate->effective_date}",
            [
                'model_type' => CurrencyRate::class,
                'model_id' => $currencyRate->id,
                'metadata' => $currencyRate->toArray(),
            ],
            "/comptes/conversion"
        );
        return response()->json([
            'status' => 'success',
            'message' => 'Taux de change créé avec succès',
            'data' => new CurrencyRateResource($currencyRate)
        ], 201);
    } catch (\Exception $th) {
        //throw $th;

        ActivityLogger::error(
            ActivityAction::CURRENCY_RATE_CREATED,
            "Erreur lors de la création du taux de change",
            $th,
        );

        return(response()->json([
            'status' => 'error',
            'message' => 'Erreur lors de la création du taux de change',
            'error' => $th->getMessage()
        ], 500));

    }
   
}


    /**
     * Afficher un taux spécifique
     * GET /api/currency-rates/{id}
     */
    public function show(CurrencyRate $currencyRate): JsonResponse
    {
        $currencyRate->load('creator');

        return response()->json([
            'status' => 'success',
            'data' => new CurrencyRateResource($currencyRate)
        ]);
    }

    /**
     * Mettre à jour un taux
     * PUT/PATCH /api/currency-rates/{id}
     */
    public function update(UpdateCurrencyRateRequest $request, CurrencyRate $currencyRate): JsonResponse
    {   

        try {
            $validated = $request->validated();
        
            // Vérifier si la modification de date crée un doublon
            if (isset($validated['effective_date']) && 
                $validated['effective_date'] != $currencyRate->effective_date) {
                
                $existingRate = CurrencyRate::where('effective_date', $validated['effective_date'])
                                            ->where('id', '!=', $currencyRate->id)
                                            ->first();
    
                if ($existingRate) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Un autre taux existe déjà pour cette date'
                    ], 422);
                }
            }
    
            $currencyRate->update($validated);
            
            ActivityLogger::success(
                ActivityAction::CURRENCY_RATE_UPDATED,
                "Taux de change mis à jour pour la date : {$currencyRate->effective_date}",
                [
                    'model_type' => CurrencyRate::class,
                    'model_id' => $currencyRate->id,
                    'metadata' => $currencyRate->toArray(),
                ],               
                 "/comptes/conversion"
            );
            return response()->json([
                'status' => 'success',
                'message' => 'Taux de change mis à jour avec succès',
                'data' => new CurrencyRateResource($currencyRate)
            ]);
        } catch (\Exception $th) {
            //throw $th;
            ActivityLogger::error(
                ActivityAction::CURRENCY_RATE_UPDATED,
                "Erreur lors de la mise à jour du taux de change",
                $th,
            );
            return(response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour du taux de change',
                'error' => $th->getMessage()
            ], 500));
        }
       
    }

    /**
     * Supprimer un taux
     * DELETE /api/currency-rates/{id}
     */
    public function destroy(CurrencyRate $currencyRate): JsonResponse
    {
        try {
                // Empêcher la suppression du taux actuel
            if ($currencyRate->isCurrent()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Impossible de supprimer le taux de change actuel'
                ], 422);
            }

            $currencyRate->delete();
            ActivityLogger::success(
                ActivityAction::CURRENCY_RATE_DELETED,
                "Taux de change supprimé pour la date : {$currencyRate->effective_date}",
                [
                    'model_type' => CurrencyRate::class,
                    'model_id' => $currencyRate->id,
                    'metadata' => $currencyRate->toArray(),
                ],
                "/comptes/conversion"
            );
            return response()->json([
                'status' => 'success',
                'message' => 'Taux de change supprimé avec succès'
            ]);
        } catch (\Exception $th) {
            //throw $th;
            ActivityLogger::error(
                ActivityAction::CURRENCY_RATE_DELETED,
                "Erreur lors de la suppression du taux de change",
                $th,
            );
            return(response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la suppression du taux de change',
                'error' => $th->getMessage()
            ], 500));
        }
    }

    /**
     * Récupérer le taux actuel
     * GET /api/currency-rates/current
     */
    public function current(): JsonResponse
    {
        $currentRate = CurrencyRate::getSystemRate();

        if (!$currentRate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun taux de change défini'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new CurrencyRateResource($currentRate)
        ]);
    }

    /**
     * Récupérer le taux à une date spécifique
     * GET /api/currency-rates/at-date?date=2025-01-15
     */
    public function atDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $rate = CurrencyRate::getRateAtDate($request->date);

        if (!$rate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun taux trouvé pour cette date'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new CurrencyRateResource($rate)
        ]);
    }

    /**
     * Convertir un montant en Ariary
     * POST /api/currency-rates/convert
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:euro,yuan,dollar,dirham,baht',
        ]);

        // Récupérer le taux actif du système
        $rateModel = CurrencyRate::getSystemRate();

        if (!$rateModel) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucun taux actif défini dans le système'
            ], 404);
        }

        $field = strtolower($request->currency) . '_rate';

        if (!isset($rateModel->$field)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Devise non disponible'
            ], 422);
        }

        $rate = (float) $rateModel->$field;

        // Conversion : 1 devise = X Ariary
        $ariaryAmount = $request->amount * $rate;

        return response()->json([
            'status' => 'success',
            'data' => [
                'amount' => (float) $request->amount,
                'currency' => $request->currency,
                'rate_in_ariary' => $rate,
                'ariary_amount' => round($ariaryAmount, 2),
                'formatted' => number_format($ariaryAmount, 2, ',', ' ') . ' Ar',
                'formula' => "{$request->amount} × {$rate}",
                'used_rate_id' => $rateModel->id,
                'effective_date' => $rateModel->effective_date->format('Y-m-d'),
            ]
        ]);
    }


    /**
     * Vérifier si les taux sont à jour
     * GET /api/currency-rates/check-freshness
     */
    public function checkFreshness(): JsonResponse
    {
        $currentRate = CurrencyRate::getSystemRate();


        if (!$currentRate) {
            return response()->json([
                'status' => 'warning',
                'message' => 'Aucun taux de change défini',
                'needs_update' => true,
                'days_old' => null
            ]);
        }

        $daysOld = $currentRate->effective_date->diffInDays(Carbon::today());
        $needsUpdate = $daysOld > 7;

        return response()->json([
            'status' => $needsUpdate ? 'warning' : 'success',
            'message' => $needsUpdate 
                ? "Les taux datent de {$daysOld} jours, mise à jour recommandée" 
                : "Les taux sont à jour",
            'needs_update' => $needsUpdate,
            'days_old' => $daysOld,
            'effective_date' => $currentRate->effective_date->format('Y-m-d'),
            'current_rate' => new CurrencyRateResource($currentRate)
        ]);
    }
}