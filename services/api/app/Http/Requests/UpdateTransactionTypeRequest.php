<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:transaction_types,code,'.$this->route('transactionType')->id,
            'name' => 'sometimes|string|max:100',
            'display_name' => 'sometimes|string|max:255',
            'category' => 'sometimes|in:income,expense,transfer',
            'description' => 'nullable|string',
        ];
    }
}
