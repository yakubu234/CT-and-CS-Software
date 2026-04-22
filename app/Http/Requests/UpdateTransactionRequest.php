<?php

namespace App\Http\Requests;

use App\Models\SavingsAccount;
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

                $originalAccount = $transaction->account()->first();
                $newAccount = SavingsAccount::query()->find($accountId);

                if (! $originalAccount || ! $newAccount) {
                    return;
                }

                $originalRevertedBalance = strtolower((string) $transaction->dr_cr) === 'cr'
                    ? round((float) $originalAccount->balance - (float) $transaction->amount, 2)
                    : round((float) $originalAccount->balance + (float) $transaction->amount, 2);

                if ($originalRevertedBalance < 0) {
                    $validator->errors()->add(
                        'amount',
                        "Updating this transaction would make {$originalAccount->account_number} go below zero."
                    );
                }

                $newBalanceBefore = $newAccount->is($originalAccount)
                    ? $originalRevertedBalance
                    : round((float) $newAccount->balance, 2);

                $amount = round((float) $this->input('amount', 0), 2);
                $drCr = strtolower((string) $this->input('dr_cr', ''));

                if ($amount <= 0 || ! in_array($drCr, ['cr', 'dr'], true)) {
                    return;
                }

                $newBalanceAfter = $drCr === 'cr'
                    ? round($newBalanceBefore + $amount, 2)
                    : round($newBalanceBefore - $amount, 2);

                if ($newBalanceAfter < 0) {
                    $validator->errors()->add(
                        'amount',
                        "This update would make {$newAccount->account_number} go below zero."
                    );
                }
            },
        ];
    }
}
