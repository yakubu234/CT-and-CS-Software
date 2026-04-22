<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\ActiveBranchService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class StoreIncomeExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trans_date' => ['required', 'date'],
            'entries' => ['required', 'array', 'min:1', 'max:20'],
            'entries.*.transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
            'entries.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $entries = $this->input('entries', []);

                foreach ($entries as $index => $entry) {
                    $categoryId = (int) ($entry['transaction_category_id'] ?? 0);

                    $category = TransactionCategory::query()
                        ->whereKey($categoryId)
                        ->where('type_to_transaction', 'expenses')
                        ->where('status', 1)
                        ->first();

                    if (! $category) {
                        $validator->errors()->add(
                            "entries.{$index}.transaction_category_id",
                            'Please choose an active income or expense category.'
                        );
                        continue;
                    }

                    $branch = app(ActiveBranchService::class)->ensureActiveBranch($this->user());

                    if (! $branch) {
                        continue;
                    }

                    static $runningBalance = null;

                    if ($runningBalance === null) {
                        $runningBalance = round(
                            (float) Transaction::query()
                                ->where('branch_id', $branch->id)
                                ->where('is_branch', true)
                                ->whereNull('deleted_at')
                                ->sum(DB::raw("case when lower(dr_cr) = 'cr' then amount else -amount end")),
                            2
                        );
                    }

                    $amount = round((float) ($entry['amount'] ?? 0), 2);

                    if ($amount <= 0) {
                        continue;
                    }

                    $drCr = strtolower((string) $category->related_to);
                    $balanceAfter = $drCr === 'cr'
                        ? round($runningBalance + $amount, 2)
                        : round($runningBalance - $amount, 2);

                    if ($balanceAfter < 0) {
                        $validator->errors()->add(
                            "entries.{$index}.amount",
                            "This {$category->name} entry would make the society balance go below zero."
                        );
                    }

                    $runningBalance = $balanceAfter;
                }
            },
        ];
    }
}
