<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BranchFinanceSummaryService
{
    public function buildMonthlySummary(Branch $branch, ?Carbon $referenceDate = null): array
    {
        $referenceDate ??= now();
        $monthStart = $referenceDate->copy()->startOfMonth();
        $monthEnd = $referenceDate->copy()->endOfMonth();

        $broughtForwardQuery = $this->baseQuery($branch)
            ->where('trans_date', '<', $monthStart);

        $currentMonthQuery = $this->baseQuery($branch)
            ->whereBetween('trans_date', [$monthStart, $monthEnd]);

        $bfCredit = $this->sumByDrCr(clone $broughtForwardQuery, 'cr');
        $bfDebit = $this->sumByDrCr(clone $broughtForwardQuery, 'dr');
        $monthInflow = $this->sumByDrCr(clone $currentMonthQuery, 'cr');
        $monthOutflow = $this->sumByDrCr(clone $currentMonthQuery, 'dr');

        $bfNet = round($bfCredit - $bfDebit, 2);
        $monthNet = round($monthInflow - $monthOutflow, 2);
        $closingBalance = round($bfNet + $monthNet, 2);
        $transactionCount = (clone $currentMonthQuery)->count();

        return [
            'month_label' => $monthStart->format('F Y'),
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
            'brought_forward' => [
                'credit' => $bfCredit,
                'debit' => $bfDebit,
                'net' => $bfNet,
            ],
            'current_month' => [
                'inflow' => $monthInflow,
                'outflow' => $monthOutflow,
                'net' => $monthNet,
                'transaction_count' => $transactionCount,
            ],
            'closing_balance' => $closingBalance,
            'breakdown' => $this->buildBreakdown($branch, $monthStart, $monthEnd),
        ];
    }

    protected function baseQuery(Branch $branch): Builder
    {
        return Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at');
    }

    protected function sumByDrCr(Builder $query, string $drCr): float
    {
        return round(
            (float) $query->whereRaw('LOWER(dr_cr) = ?', [strtolower($drCr)])->sum('amount'),
            2
        );
    }

    protected function buildBreakdown(Branch $branch, Carbon $monthStart, Carbon $monthEnd): array
    {
        return [
            'items' => [
                $this->singleFlowItem(
                    'Loan disbursement',
                    $this->typedMonthlyQuery($branch, $monthStart, $monthEnd, ['Loan Disbursement'])
                ),
                $this->singleFlowItem(
                    'Loan repayment',
                    $this->typedMonthlyQuery($branch, $monthStart, $monthEnd, ['Loan Repayment'])
                ),
                $this->singleFlowItem(
                    'Interest',
                    $this->typedMonthlyQuery($branch, $monthStart, $monthEnd, ['Loan Interest Repayment'])
                ),
                $this->singleFlowItem(
                    'Income',
                    $this->incomeExpenseMonthlyQuery($branch, $monthStart, $monthEnd, 'cr')
                ),
                $this->singleFlowItem(
                    'Expenses',
                    $this->incomeExpenseMonthlyQuery($branch, $monthStart, $monthEnd, 'dr')
                ),
                $this->memberTransactionsItem(
                    $this->memberTransactionMonthlyQuery($branch, $monthStart, $monthEnd)
                ),
            ],
            'account_balances' => $this->memberAccountBalances($branch),
        ];
    }

    protected function singleFlowItem(string $label, Builder $query): array
    {
        $row = $query
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as record_count')
            ->first();

        return [
            'label' => $label,
            'amount' => round((float) ($row->total ?? 0), 2),
            'count' => (int) ($row->record_count ?? 0),
        ];
    }

    protected function memberTransactionsItem(Builder $query): array
    {
        $row = $query
            ->selectRaw("
                COALESCE(SUM(CASE WHEN LOWER(dr_cr) = 'cr' THEN amount ELSE 0 END), 0) as inflow,
                COALESCE(SUM(CASE WHEN LOWER(dr_cr) = 'dr' THEN amount ELSE 0 END), 0) as outflow,
                COUNT(*) as record_count
            ")
            ->first();

        $inflow = round((float) ($row->inflow ?? 0), 2);
        $outflow = round((float) ($row->outflow ?? 0), 2);

        return [
            'label' => 'Member transactions summary',
            'amount' => round($inflow - $outflow, 2),
            'inflow' => $inflow,
            'outflow' => $outflow,
            'count' => (int) ($row->record_count ?? 0),
        ];
    }

    protected function typedMonthlyQuery(Branch $branch, Carbon $monthStart, Carbon $monthEnd, array $types): Builder
    {
        return $this->baseQuery($branch)
            ->whereBetween('trans_date', [$monthStart, $monthEnd])
            ->whereIn('type', $types);
    }

    protected function incomeExpenseMonthlyQuery(Branch $branch, Carbon $monthStart, Carbon $monthEnd, string $drCr): Builder
    {
        return $this->baseQuery($branch)
            ->whereBetween('trans_date', [$monthStart, $monthEnd])
            ->where('tracking_id', 'expenses')
            ->whereRaw('LOWER(dr_cr) = ?', [strtolower($drCr)]);
    }

    protected function memberTransactionMonthlyQuery(Branch $branch, Carbon $monthStart, Carbon $monthEnd): Builder
    {
        return $this->baseQuery($branch)
            ->whereBetween('trans_date', [$monthStart, $monthEnd])
            ->where('tracking_id', 'regular');
    }

    protected function memberAccountBalances(Branch $branch): Collection
    {
        $rows = SavingsAccount::query()
            ->join('users', 'users.id', '=', 'savings_accounts.user_id')
            ->join('savings_products', 'savings_products.id', '=', 'savings_accounts.savings_product_id')
            ->where('users.branch_id', (string) $branch->id)
            ->where('users.branch_account', false)
            ->whereNull('users.deleted_at')
            ->where('savings_accounts.is_branch_acount', false)
            ->where('savings_accounts.status', 1)
            ->groupBy('savings_products.type')
            ->selectRaw('savings_products.type, COALESCE(SUM(savings_accounts.balance), 0) as total_balance, COUNT(*) as account_count')
            ->get()
            ->keyBy('type');

        $orderedTypes = [
            'SAVINGS' => 'Savings',
            'DEPOSIT' => 'Deposit',
            'SHARES' => 'Shares',
            'AUTHENTICATION' => 'Authentication',
        ];

        return collect($orderedTypes)->map(function (string $label, string $type) use ($rows) {
            return [
                'label' => $label,
                'amount' => round((float) ($rows[$type]->total_balance ?? 0), 2),
                'count' => (int) ($rows[$type]->account_count ?? 0),
            ];
        })->values();
    }
}
