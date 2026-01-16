<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRateRequest extends FormRequest
{
    public function authorize()
    {
        // Seulement les admins peuvent créer des taux
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules()
    {
        return [
            'euro_rate' => 'required|numeric|min:0.0001|max:999999.9999',
            'yuan_rate' => 'required|numeric|min:0.0001|max:999999.9999',
            'dollar_rate' => 'required|numeric|min:0.0001|max:999999.9999',
            'dirham_rate' => 'required|numeric|min:0.0001|max:999999.9999',
            'baht_rate' => 'required|numeric|min:0.0001|max:999999.9999',
            'effective_date' => 'required|date|after_or_equal:today',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'euro_rate.required' => 'Le taux Euro est obligatoire',
            'euro_rate.min' => 'Le taux Euro doit être supérieur à 0',
            'yuan_rate.required' => 'Le taux Yuan est obligatoire',
            'dollar_rate.required' => 'Le taux Dollar est obligatoire',
            'dirham_rate.required' => 'Le taux Dirham est obligatoire',
            'baht_rate.required' => 'Le taux Baht est obligatoire',
            'effective_date.required' => 'La date d\'effet est obligatoire',
            'effective_date.after_or_equal' => 'La date d\'effet ne peut pas être dans le passé',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'created_by' => $this->user()->id
        ]);
    }
}