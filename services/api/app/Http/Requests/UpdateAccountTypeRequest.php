<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:account_types,code,'.$this->route('accountType')->id,
            'name' => 'sometimes|string|max:100',
            'display_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
