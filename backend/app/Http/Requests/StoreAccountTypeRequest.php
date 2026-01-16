<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request pour créer un type de compte
 */
class StoreAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:account_types,code',
            'name' => 'required|string|max:100',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}