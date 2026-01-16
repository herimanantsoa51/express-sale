<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class UpdateCurrencyRateRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules()
    {
        $currencyRate = $this->route('currency_rate');
        
        return [
            'euro_rate' => 'sometimes|required|numeric|min:0.0001|max:999999.9999',
            'yuan_rate' => 'sometimes|required|numeric|min:0.0001|max:999999.9999',
            'dollar_rate' => 'sometimes|required|numeric|min:0.0001|max:999999.9999',
            'dirham_rate' => 'sometimes|required|numeric|min:0.0001|max:999999.9999',
            'baht_rate' => 'sometimes|required|numeric|min:0.0001|max:999999.9999',
            'effective_date' => 'sometimes|required|date',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'euro_rate.min' => 'Le taux Euro doit être supérieur à 0',
            'yuan_rate.min' => 'Le taux Yuan doit être supérieur à 0',
            'dollar_rate.min' => 'Le taux Dollar doit être supérieur à 0',
            'dirham_rate.min' => 'Le taux Dirham doit être supérieur à 0',
            'baht_rate.min' => 'Le taux Baht doit être supérieur à 0',
        ];
    }
}