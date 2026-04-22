<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\ActiveBranchService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class UpdateIncomeExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
            'trans_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $categoryId = (int) $this->input('transaction_category_id');

                $isValidExpenseCategory = TransactionCategory::query()
                    ->whereKey($categoryId)
                    ->where('type_to_transaction', 'expenses')
                    ->where('status', 1)
                    ->exists();

                if (! $isValidExpenseCategory) {
                    $validator->errors()->add(
                        'transaction_category_id',
                        'Please choose an active income or expense category.'
                    );
                    return;
                }

                /** @var Transaction|null $transaction */
                $transaction = $this->route('incomeExpense');

                if (! $transaction) {
                    return;
                }

                $branch = app(ActiveBranchService::class)->ensureActiveBranch($this->user());

                if (! $branch) {
                    return;
                }

                $category = TransactionCategory::query()->find($categoryId);
                $amount = round((float) $this->input('amount', 0), 2);

                if (! $category || $amount <= 0) {
                    return;
                }

                $balanceBefore = round(
                    (float) Transaction::query()
                        ->where('branch_id', $branch->id)
                        ->where('is_branch', true)
                        ->whereNull('deleted_at')
                        ->whereKeyNot($transaction->id)
                        ->sum(DB::raw("case when lower(dr_cr) = 'cr' then amount else -amount end")),
                    2
                );

                $drCr = strtolower((string) $category->related_to);
                $balanceAfter = $drCr === 'cr'
                    ? round($balanceBefore + $amount, 2)
                    : round($balanceBefore - $amount, 2);

                if ($balanceAfter < 0) {
                    $validator->errors()->add(
                        'amount',
                        "This {$category->name} entry would make the society balance go below zero."
                    );
                }
            },
        ];
    }
}
