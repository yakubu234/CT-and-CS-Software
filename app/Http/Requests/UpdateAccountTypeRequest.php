<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'interest_method' => ['nullable', 'string', Rule::in([
                'daily_outstanding_balance',
                'average_daily_balance',
                'minimum_balance',
                'flat_rate',
            ])],
            'interest_period' => ['nullable', 'integer', Rule::in([1, 2, 3, 6, 12])],
            'min_bal_interest_rate' => ['nullable', 'numeric', 'min:0'],
            'allow_withdraw' => ['required', 'boolean'],
            'minimum_account_balance' => ['nullable', 'numeric', 'min:0'],
            'minimum_deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'maintenance_fee' => ['nullable', 'numeric', 'min:0'],
            'maintenance_fee_posting_period' => ['nullable', 'integer', Rule::in([1, 2, 3, 6, 12])],
            'status' => ['required', 'integer', Rule::in([1, 2])],
        ];
    }
}
