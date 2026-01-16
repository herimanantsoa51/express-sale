<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;



/**
 * Request pour créer un transfert entre comptes
 */
class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'transaction_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'from_account_id.required' => 'Le compte source est requis',
            'to_account_id.required' => 'Le compte destination est requis',
            'to_account_id.different' => 'Le compte destination doit être différent du compte source',
            'amount.required' => 'Le montant est requis',
            'amount.min' => 'Le montant doit être supérieur à zéro',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->from_account_id && $this->amount) {
                $account = \App\Models\Account::find($this->from_account_id);
                if ($account && $account->current_balance < $this->amount) {
                    $validator->errors()->add('amount', 'Solde insuffisant sur le compte source');
                }
            }
        });
    }
}

