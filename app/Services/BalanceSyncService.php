<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use RuntimeException;

class BalanceSyncService
{
    public function syncSavingsAccount(SavingsAccount $account): float
    {
        $account->load([
            'product',
            'user.detail',
        ]);

        $transactions = Transaction::query()
            ->where('savings_account_id', $account->id)
            ->where('tracking_id', 'regular')
            ->where('is_branch', false)
            ->whereNull('deleted_at')
            ->orderBy('trans_date')
            ->orderBy('id')
            ->get();

        $runningBalance = $this->syncAccountTransactionCollection(
            $transactions,
            (float) ($account->opening_balance ?? 0),
            $account->account_number
        );

        $account->forceFill([
            'balance' => $runningBalance,
        ])->save();

        return $runningBalance;
    }

    public function syncAccountTransactionCollection(Collection $transactions, float $openingBalance = 0, string $accountLabel = 'This account'): float
    {
        $runningBalance = round($openingBalance, 2);

        foreach ($transactions as $transaction) {
            $balanceBefore = $runningBalance;
            $balanceAfter = $this->applyDirection($balanceBefore, strtolower((string) $transaction->dr_cr), (float) $transaction->amount);
            $transactionDate = $transaction->trans_date ? $transaction->trans_date->format('Y-m-d') : 'the selected date';
            $transactionType = $transaction->type ?: 'transaction';
            $transactionAmount = number_format((float) $transaction->amount, 2);

            if ($balanceAfter < 0) {
                $availableBalance = number_format($balanceBefore, 2);
                $shortfall = number_format(abs($balanceAfter), 2);
                $direction = strtolower((string) $transaction->dr_cr) === 'dr' ? 'debit' : 'credit';

                throw new RuntimeException(
                    "Insufficient balance for {$transactionType} account {$accountLabel} on {$transactionDate}. "
                    . "When transactions are applied in date order, the available balance is ₦{$availableBalance}, "
                    . "but the attempted {$direction} is ₦{$transactionAmount}, leaving a shortfall of ₦{$shortfall}. "
                    . 'Reduce the debit amount or credit the account before posting this transaction.'
                );
            }

            $this->updateTransactionSnapshot($transaction, $balanceBefore, $balanceAfter);
            $runningBalance = $balanceAfter;
        }

        return $runningBalance;
    }

    public function syncBranchLedger(Branch $branch, bool $enforceNonNegative = true): float
    {
        $transactions = Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at')
            ->orderBy('trans_date')
            ->orderBy('id')
            ->get();

        return $this->syncBranchTransactionCollection($transactions, $branch->name ?: 'This branch', $enforceNonNegative);
    }

    public function syncBranchTransactionCollection(Collection $transactions, string $branchLabel = 'This branch', bool $enforceNonNegative = true): float
    {
        $runningBalance = 0.0;

        foreach ($transactions as $transaction) {
            $balanceBefore = $runningBalance;
            $balanceAfter = $this->applyDirection($balanceBefore, strtolower((string) $transaction->dr_cr), (float) $transaction->amount);
            $transactionDate = $transaction->trans_date ? $transaction->trans_date->format('Y-m-d') : 'the selected date';
            $transactionType = $transaction->type ?: 'transaction';
            $transactionAmount = number_format((float) $transaction->amount, 2);

            if ($enforceNonNegative && $balanceAfter < 0) {
                $availableBalance = number_format($balanceBefore, 2);
                $shortfall = number_format(abs($balanceAfter), 2);
                $isExpense = $transaction->tracking_id === 'expenses'
                    && strtolower((string) $transaction->dr_cr) === 'dr';
                $attemptedEntry = $isExpense
                    ? "expense \"{$transactionType}\""
                    : "debit for \"{$transactionType}\"";
                $correctiveAction = $isExpense
                    ? 'Reduce the expense amount or record sufficient income before posting this expense.'
                    : 'Reduce the debit amount or credit the branch before posting this transaction.';

                throw new RuntimeException(
                    "Insufficient branch balance for {$branchLabel} on {$transactionDate}. "
                    . "When branch transactions are applied in date order, the available balance is ₦{$availableBalance}, "
                    . "but the attempted {$attemptedEntry} is ₦{$transactionAmount}, leaving a shortfall of ₦{$shortfall}. "
                    . $correctiveAction
                );
            }

            $this->updateTransactionSnapshot($transaction, $balanceBefore, $balanceAfter);
            $runningBalance = $balanceAfter;
        }

        return $runningBalance;
    }

    protected function applyDirection(float $balanceBefore, string $drCr, float $amount): float
    {
        return $drCr === 'cr'
            ? round($balanceBefore + $amount, 2)
            : round($balanceBefore - $amount, 2);
    }

    protected function updateTransactionSnapshot(Transaction $transaction, float $balanceBefore, float $balanceAfter): void
    {
        $details = is_array($transaction->transaction_details) ? $transaction->transaction_details : [];

        $details['balance_before'] = round($balanceBefore, 2);
        $details['balance_after'] = round($balanceAfter, 2);

        $transaction->update([
            'transaction_details' => $details,
        ]);
    }
}
