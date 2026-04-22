<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class IncomeExpenseService
{
    public function createBatch(Branch $branch, User $actor, string $transactionDate, array $entries): Collection
    {
        return DB::transaction(function () use ($branch, $actor, $transactionDate, $entries): Collection {
            $branchUserId = (int) $branch->branch_user_id;

            if ($branchUserId < 1) {
                throw new RuntimeException('The active branch is not linked to a society account user yet.');
            }

            $batchId = (string) Str::uuid();
            $records = new Collection();
            $branchBalance = $this->branchLedgerBalance($branch);

            foreach ($entries as $entry) {
                $category = $this->resolveActiveExpenseCategory((int) $entry['transaction_category_id']);
                $amount = $this->normalizeAmount($entry['amount']);
                $description = $entry['description'] ?: $category->name;
                $drCr = strtolower($category->related_to);
                $balanceBefore = $branchBalance;
                $balanceAfter = $this->calculateBalanceAfter($balanceBefore, $drCr, $amount);

                if ($balanceAfter < 0) {
                    throw new RuntimeException("This {$category->name} entry would make the society balance go below zero.");
                }

                $records->push(Transaction::create([
                    'user_id' => $branchUserId,
                    'trans_date' => $transactionDate,
                    'savings_account_id' => null,
                    'charge' => 0,
                    'amount' => $amount,
                    'gateway_amount' => 0,
                    'dr_cr' => $drCr,
                    'type' => $category->name,
                    'attachment' => null,
                    'method' => 'Manual',
                    'status' => 2,
                    'note' => null,
                    'description' => $description,
                    'loan_id' => null,
                    'ref_id' => null,
                    'parent_id' => null,
                    'gateway_id' => null,
                    'created_user_id' => $actor->id,
                    'updated_user_id' => null,
                    'branch_id' => $branch->id,
                    'transaction_details' => [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'related_to' => $drCr,
                        'scope' => 'income-expense',
                        'branch_name' => $branch->name,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                    ],
                    'tracking_id' => 'expenses',
                    'detail_id' => (string) $category->id,
                    'is_branch' => 1,
                    'loan_details_id' => null,
                    'loan_repayment_id' => null,
                    'batch_id' => $batchId,
                ])->fresh(['creator', 'updater']));

                $branchBalance = $balanceAfter;
            }

            return $records;
        });
    }

    public function create(Branch $branch, User $actor, TransactionCategory $category, array $payload): Transaction
    {
        return DB::transaction(function () use ($branch, $actor, $category, $payload): Transaction {
            $branchUserId = (int) $branch->branch_user_id;

            if ($branchUserId < 1) {
                throw new RuntimeException('The active branch is not linked to a society account user yet.');
            }

            $amount = $this->normalizeAmount($payload['amount']);
            $transDate = $payload['trans_date'];
            $description = $payload['description'] ?: $category->name;
            $drCr = strtolower($category->related_to);
            $balanceBefore = $this->branchLedgerBalance($branch);
            $balanceAfter = $this->calculateBalanceAfter($balanceBefore, $drCr, $amount);

            if ($balanceAfter < 0) {
                throw new RuntimeException("This {$category->name} entry would make the society balance go below zero.");
            }

            return Transaction::create([
                'user_id' => $branchUserId,
                'trans_date' => $transDate,
                'savings_account_id' => null,
                'charge' => 0,
                'amount' => $amount,
                'gateway_amount' => 0,
                'dr_cr' => $drCr,
                'type' => $category->name,
                'attachment' => null,
                'method' => 'Manual',
                'status' => 2,
                'note' => null,
                'description' => $description,
                'loan_id' => null,
                'ref_id' => null,
                'parent_id' => null,
                'gateway_id' => null,
                'created_user_id' => $actor->id,
                'updated_user_id' => null,
                'branch_id' => $branch->id,
                'transaction_details' => [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'related_to' => $drCr,
                    'scope' => 'income-expense',
                    'branch_name' => $branch->name,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                ],
                'tracking_id' => 'expenses',
                'detail_id' => (string) $category->id,
                'is_branch' => 1,
                'loan_details_id' => null,
                'loan_repayment_id' => null,
                'batch_id' => (string) Str::uuid(),
            ])->fresh(['creator', 'updater']);
        });
    }

    public function update(Branch $branch, User $actor, Transaction $transaction, TransactionCategory $category, array $payload): Transaction
    {
        return DB::transaction(function () use ($branch, $actor, $transaction, $category, $payload): Transaction {
            $branchUserId = (int) $branch->branch_user_id;

            if ($branchUserId < 1) {
                throw new RuntimeException('The active branch is not linked to a society account user yet.');
            }

            $amount = $this->normalizeAmount($payload['amount']);
            $drCr = strtolower($category->related_to);
            $balanceBefore = $this->branchLedgerBalance($branch, $transaction->id);
            $balanceAfter = $this->calculateBalanceAfter($balanceBefore, $drCr, $amount);

            if ($balanceAfter < 0) {
                throw new RuntimeException("Updating this {$category->name} entry would make the society balance go below zero.");
            }

            $transaction->update([
                'user_id' => $branchUserId,
                'trans_date' => $payload['trans_date'],
                'amount' => $amount,
                'dr_cr' => $drCr,
                'type' => $category->name,
                'description' => $payload['description'] ?: $category->name,
                'updated_user_id' => $actor->id,
                'branch_id' => $branch->id,
                'transaction_details' => [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'related_to' => $drCr,
                    'scope' => 'income-expense',
                    'branch_name' => $branch->name,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                ],
                'tracking_id' => 'expenses',
                'detail_id' => (string) $category->id,
                'is_branch' => 1,
            ]);

            return $transaction->fresh(['creator', 'updater']);
        });
    }

    public function delete(User $actor, Transaction $transaction): void
    {
        DB::transaction(function () use ($actor, $transaction): void {
            $branch = Branch::query()->find($transaction->branch_id);

            if (! $branch) {
                throw new RuntimeException('The linked branch for this income or expense entry could not be found.');
            }

            $balanceWithoutTransaction = $this->branchLedgerBalance($branch, $transaction->id);

            if ($balanceWithoutTransaction < 0) {
                throw new RuntimeException('Deleting this entry would make the society balance invalid.');
            }

            $transaction->forceFill([
                'updated_user_id' => $actor->id,
            ])->save();

            $transaction->delete();
        });
    }

    protected function normalizeAmount(mixed $amount): float
    {
        return round((float) $amount, 2);
    }

    protected function calculateBalanceAfter(float $balanceBefore, string $drCr, float $amount): float
    {
        return $drCr === 'cr'
            ? round($balanceBefore + $amount, 2)
            : round($balanceBefore - $amount, 2);
    }

    protected function branchLedgerBalance(Branch $branch, ?int $excludeTransactionId = null): float
    {
        $query = Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at');

        if ($excludeTransactionId !== null) {
            $query->whereKeyNot($excludeTransactionId);
        }

        return round(
            (float) $query->sum(DB::raw("case when lower(dr_cr) = 'cr' then amount else -amount end")),
            2
        );
    }

    protected function resolveActiveExpenseCategory(int $categoryId): TransactionCategory
    {
        return TransactionCategory::query()
            ->whereKey($categoryId)
            ->where('type_to_transaction', 'expenses')
            ->where('status', 1)
            ->firstOrFail();
    }
}
