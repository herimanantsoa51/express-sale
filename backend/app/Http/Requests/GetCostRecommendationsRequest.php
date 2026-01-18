<?php
// ============================================================================
// 1. REQUESTS (Validators)
// ============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour obtenir les RECOMMANDATIONS de répartition des coûts
 * GET /api/stock-receipts/{id}/cost-recommendations
 */
class GetCostRecommendationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => 'nullable|in:weight,value,quantity',
        ];
    }

    public function messages(): array
    {
        return [
            'method.in' => 'La méthode doit être: weight, value ou quantity',
        ];
    }
}