<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trans_date' => ['required', 'date'],
            'savings_account_id' => ['required', 'integer', 'exists:savings_accounts,id'],
            'dr_cr' => ['required', 'in:cr,dr'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Transaction|null $transaction */
                $transaction = $this->route('transaction');

                if (! $transaction) {
                    return;
                }

                $transaction->loadMissing('user');

                if (! $transaction->user) {
                    $validator->errors()->add('transaction', 'This transaction is missing its original member record.');
                    return;
                }

                $accountId = (int) $this->input('savings_account_id');

                $belongsToOriginalMember = $transaction->user
                    ->savingsAccounts()
                    ->where('is_branch_acount', false)
                    ->where('status', 1)
                    ->whereKey($accountId)
                    ->exists();

                if (! $belongsToOriginalMember) {
                    $validator->errors()->add(
                        'savings_account_id',
                        'You can only edit this transaction using an account that belongs to the original member. If this was posted to the wrong member, delete it and recreate it for the rightful owner.'
                    );
                    return;
                }

            },
        ];
    }
}
