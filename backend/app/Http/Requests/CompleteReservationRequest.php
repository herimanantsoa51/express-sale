<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_id' => 'required|exists:accounts,id',
            'notes' => 'nullable|string|max:500',
        ];
    }
    public function messages(): array
    {
        return [
            'account_id.required' => 'Le compte de destination est requis',
            'account_id.exists' => 'Le compte sélectionné n\'existe pas',
        ];
    }
}
