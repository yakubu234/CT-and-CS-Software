<?php

namespace App\Http\Requests;

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

                foreach ($entries as $index => $entry) {
                    $accountId = (int) ($entry['savings_account_id'] ?? 0);
                    if ($accountId < 1) {
                        continue;
                    }

                    $accountExistsForMember = \App\Models\SavingsAccount::query()
                        ->whereKey($accountId)
                        ->where('user_id', $memberId)
                        ->where('is_branch_acount', false)
                        ->where('status', 1)
                        ->exists();

                    if (! $accountExistsForMember) {
                        $validator->errors()->add(
                            "entries.{$index}.savings_account_id",
                            'The selected account is invalid for the chosen member.'
                        );
                    }
                }
            },
        ];
    }
}
