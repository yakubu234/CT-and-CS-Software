<?php

namespace App\Http\Requests;

use App\Models\SavingsAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer', 'exists:users,id'],
            'trans_date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1', 'max:4'],
            'entries.*.savings_account_id' => ['required', 'integer', 'exists:savings_accounts,id'],
            'entries.*.dr_cr' => ['required', 'in:cr,dr'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $entries = collect($this->input('entries', []))
            ->filter(function ($entry) {
                return filled($entry['savings_account_id'] ?? null)
                    || filled($entry['amount'] ?? null)
                    || filled($entry['description'] ?? null);
            })
            ->values()
            ->all();

        $this->merge([
            'entries' => $entries,
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $memberId = (int) $this->input('member_id');
                $entries = $this->input('entries', []);

                if ($memberId < 1 || ! is_array($entries) || $entries === []) {
                    return;
                }

                $runningBalances = [];

                foreach ($entries as $index => $entry) {
                    $accountId = (int) ($entry['savings_account_id'] ?? 0);
                    $drCr = strtolower((string) ($entry['dr_cr'] ?? ''));
                    $amount = round((float) ($entry['amount'] ?? 0), 2);

                    if ($accountId < 1 || ! in_array($drCr, ['cr', 'dr'], true) || $amount <= 0) {
                        continue;
                    }

                    $account = SavingsAccount::query()
                        ->whereKey($accountId)
                        ->where('user_id', $memberId)
                        ->where('is_branch_acount', false)
                        ->where('status', 1)
                        ->first();

                    if (! $account) {
                        $validator->errors()->add(
                            "entries.{$index}.savings_account_id",
                            'The selected account is invalid for the chosen member.'
                        );
                        continue;
                    }

                    $balanceBefore = $runningBalances[$account->id] ?? round((float) $account->balance, 2);
                    $balanceAfter = $drCr === 'cr'
                        ? round($balanceBefore + $amount, 2)
                        : round($balanceBefore - $amount, 2);

                    if ($balanceAfter < 0) {
                        $validator->errors()->add(
                            "entries.{$index}.amount",
                            "This entry would make {$account->account_number} go below zero."
                        );
                    }

                    $runningBalances[$account->id] = $balanceAfter;
                }
            },
        ];
    }
}
