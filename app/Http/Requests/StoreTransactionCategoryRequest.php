<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:30', 'unique:transaction_categories,name'],
            'related_to' => ['required', Rule::in(['cr', 'dr'])],
            'status' => ['required', 'boolean'],
            'note' => ['nullable', 'string'],
        ];
    }
}
