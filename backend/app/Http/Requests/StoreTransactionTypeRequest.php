<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:transaction_types,code',
            'name' => 'required|string|max:100',
            'display_name' => 'required|string|max:255',
            'category' => 'required|in:income,expense,transfer',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => 'La catégorie doit être: income, expense, ou transfer',
        ];
    }
}

class UpdateTransactionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|max:50|unique:transaction_types,code,' . $this->route('transactionType')->id,
            'name' => 'sometimes|string|max:100',
            'display_name' => 'sometimes|string|max:255',
            'category' => 'sometimes|in:income,expense,transfer',
            'description' => 'nullable|string',
        ];
    }
}

/**
 * Request pour créer une catégorie de dépense
 */
class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];
    }
}

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255|unique:expense_categories,name,' . $this->route('expenseCategory')->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];
    }
}