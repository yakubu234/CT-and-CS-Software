<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLoanPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'loan_id' => ['required', 'integer', 'exists:loans,id'],
            'paid_at' => ['required', 'date'],
            'repayment_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'interest_paid' => ['nullable', 'numeric', 'min:0'],
            'carry_forward_remaining' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $repaymentAmount = round((float) $this->input('repayment_amount', 0), 2);
                $interestPaid = round((float) $this->input('interest_paid', 0), 2);

                if ($repaymentAmount <= 0 && $interestPaid <= 0) {
                    $validator->errors()->add('repayment_amount', 'Enter a repayment amount, an interest payment, or both.');
                }
            },
        ];
    }
}
