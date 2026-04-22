<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $transactionCategory = $this->route('transactionCategory');

        return [
            'name' => [
                'required',
                'string',
                'max:30',
                Rule::unique('transaction_categories', 'name')->ignore($transactionCategory?->id),
            ],
            'related_to' => ['required', Rule::in(['cr', 'dr'])],
            'status' => ['required', 'boolean'],
            'note' => ['nullable', 'string'],
        ];
    }
}
