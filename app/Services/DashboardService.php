<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\LoanDetail;
use App\Models\SavingsAccount;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardService
{
    public function build(Branch $branch): array
    {
        $today = now();
        $startOfMonth = $today->copy()->startOfMonth();
        $startOfDay = $today->copy()->startOfDay();

        $memberBase = User::query()
            ->where('branch_id', (string) $branch->id)
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->where(function ($query): void {
                $query->where('user_type', 'customer')
                    ->orWhere('society_exco', true)
                    ->orWhere('former_exco', true);
            });

        $memberCount = (clone $memberBase)->count();
        $newMembersThisMonth = (clone $memberBase)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $branchPurseBalance = (float) SavingsAccount::query()
            ->where('user_id', $branch->branch_user_id)
            ->where('is_branch_acount', true)
            ->where('status', 1)
            ->sum('balance');

        $accountTotals = $this->memberAccountTotals($branch);
        $memberWalletTotal = array_sum($accountTotals);
        $loanStats = $this->loanStats($branch);
        $cashToday = $this->cashMovement($branch, $startOfDay, $today);
        $cashThisMonth = $this->cashMovement($branch, $startOfMonth, $today);

        return [
            'cards' => [
                [
                    'label' => 'Society Purse',
                    'value' => $branchPurseBalance,
                    'format' => 'currency',
                    'icon' => 'fas fa-university',
                    'tone' => 'primary',
                    'hint' => 'Current branch account balance',
                    'route' => route('accounts.index'),
                ],
                [
                    'label' => 'Members',
                    'value' => $memberCount,
                    'format' => 'number',
                    'icon' => 'fas fa-users',
                    'tone' => 'success',
                    'hint' => "{$newMembersThisMonth} added this month",
                    'route' => route('members.index'),
                ],
                [
                    'label' => 'Loan Exposure',
                    'value' => $loanStats['outstanding_amount'],
                    'format' => 'currency',
                    'icon' => 'fas fa-hand-holding-usd',
                    'tone' => 'danger',
                    'hint' => $loanStats['active_count'] . ' active borrower record(s)',
                    'route' => route('loans.active'),
                ],
                [
                    'label' => 'Pending Loan Requests',
                    'value' => $loanStats['pending_count'],
                    'format' => 'number',
                    'icon' => 'fas fa-hourglass-half',
                    'tone' => 'warning',
                    'hint' => 'Awaiting approval or decline',
                    'route' => route('loans.pending'),
                ],
            ],
            'cash_today' => $cashToday,
            'cash_this_month' => $cashThisMonth,
            'account_totals' => $accountTotals,
            'loan_stats' => $loanStats,
            'cash_flow_chart' => $this->cashFlowChart($branch),
            'account_chart' => $this->accountChart($accountTotals),
            'source_chart' => $this->sourceChart($branch),
            'recent_activity' => $this->recentActivity($branch),
            'quick_actions' => [
                ['label' => 'Add Member', 'icon' => 'fas fa-user-plus', 'route' => route('members.create')],
                ['label' => 'Record Transaction', 'icon' => 'fas fa-exchange-alt', 'route' => route('transactions.create')],
                ['label' => 'Income / Expense', 'icon' => 'fas fa-paper-plane', 'route' => route('income-expenses.create')],
                ['label' => 'Loan Request', 'icon' => 'fas fa-file-invoice-dollar', 'route' => route('loans.create')],
                ['label' => 'Loan Repayment', 'icon' => 'fas fa-money-check-alt', 'route' => route('loan-payments.create')],
                ['label' => 'Member Balance Report', 'icon' => 'fas fa-chart-bar', 'route' => route('reports.member-balance')],
            ],
        ];
    }

    protected function memberAccountTotals(Branch $branch): array
    {
        $rows = SavingsAccount::query()
            ->join('users', 'users.id', '=', 'savings_accounts.user_id')
            ->join('savings_products', 'savings_products.id', '=', 'savings_accounts.savings_product_id')
            ->where('users.branch_id', (string) $branch->id)
            ->where('users.branch_account', false)
            ->whereNull('users.deleted_at')
            ->where('savings_accounts.is_branch_acount', false)
            ->where('savings_accounts.status', 1)
            ->whereIn('savings_products.type', ['SAVINGS', 'SHARES', 'AUTHENTICATION', 'DEPOSIT'])
            ->groupBy('savings_products.type')
            ->selectRaw('savings_products.type, COALESCE(SUM(savings_accounts.balance), 0) as total')
            ->pluck('total', 'type');

        return [
            'Savings' => (float) ($rows['SAVINGS'] ?? 0),
            'Shares' => (float) ($rows['SHARES'] ?? 0),
            'Auth' => (float) ($rows['AUTHENTICATION'] ?? 0),
            'Deposit' => (float) ($rows['DEPOSIT'] ?? 0),
        ];
    }

    protected function loanStats(Branch $branch): array
    {
        $approved = LoanDetail::query()
            ->where('branch_id', $branch->id)
            ->where('decision_status', LoanDetail::STATUS_APPROVED);

        $outstandingAmount = (float) (clone $approved)
            ->selectRaw('COALESCE(SUM(GREATEST(applied_amount - amount_repayed, 0)), 0) as total')
            ->value('total');

        $activeCount = (clone $approved)
            ->whereRaw('GREATEST(applied_amount - amount_repayed, 0) > 0')
            ->distinct('borrower_id')
            ->count('borrower_id');

        $pendingCount = LoanDetail::query()
            ->where('branch_id', $branch->id)
            ->where('decision_status', LoanDetail::STATUS_PENDING)
            ->count();

        $overdueAmount = (float) LoanDetail::query()
            ->where('branch_id', $branch->id)
            ->where('decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('due_date', '<', now()->toDateString())
            ->selectRaw('COALESCE(SUM(GREATEST(applied_amount - amount_repayed, 0)), 0) as total')
            ->value('total');

        $overdueCount = LoanDetail::query()
            ->where('branch_id', $branch->id)
            ->where('decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereRaw('GREATEST(applied_amount - amount_repayed, 0) > 0')
            ->count();

        $repaymentsThisMonth = (float) DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->where('loans.branch_id', $branch->id)
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '>=', now()->startOfMonth()->toDateString())
            ->selectRaw('COALESCE(SUM(COALESCE(repayment_amount, 0) + COALESCE(interest_paid, 0)), 0) as total')
            ->value('total');

        return [
            'outstanding_amount' => round($outstandingAmount, 2),
            'active_count' => $activeCount,
            'pending_count' => $pendingCount,
            'overdue_amount' => round($overdueAmount, 2),
            'overdue_count' => $overdueCount,
            'repayments_this_month' => round($repaymentsThisMonth, 2),
        ];
    }

    protected function cashMovement(Branch $branch, Carbon $start, Carbon $end): array
    {
        $row = Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at')
            ->whereBetween('trans_date', [$start, $end])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN LOWER(dr_cr) = 'cr' THEN amount ELSE 0 END), 0) as credits,
                COALESCE(SUM(CASE WHEN LOWER(dr_cr) = 'dr' THEN amount ELSE 0 END), 0) as debits
            ")
            ->first();

        $credits = (float) ($row->credits ?? 0);
        $debits = (float) ($row->debits ?? 0);

        return [
            'credits' => round($credits, 2),
            'debits' => round($debits, 2),
            'net' => round($credits - $debits, 2),
        ];
    }

    protected function cashFlowChart(Branch $branch): array
    {
        $labels = [];
        $credits = [];
        $debits = [];

        for ($index = 5; $index >= 0; $index--) {
            $month = now()->subMonths($index);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $movement = $this->cashMovement($branch, $start, $end);

            $labels[] = $month->format('M');
            $credits[] = $movement['credits'];
            $debits[] = $movement['debits'];
        }

        return compact('labels', 'credits', 'debits');
    }

    protected function accountChart(array $accountTotals): array
    {
        return [
            'labels' => array_keys($accountTotals),
            'values' => array_values($accountTotals),
        ];
    }

    protected function sourceChart(Branch $branch): array
    {
        $rows = Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at')
            ->where('trans_date', '>=', now()->subDays(30)->startOfDay())
            ->groupBy('tracking_id')
            ->selectRaw('tracking_id, COUNT(*) as total')
            ->pluck('total', 'tracking_id');

        $labels = [];
        $values = [];

        foreach ($rows as $source => $total) {
            $labels[] = Str::headline((string) ($source ?: 'unknown'));
            $values[] = (int) $total;
        }

        return [
            'labels' => $labels ?: ['No Activity'],
            'values' => $values ?: [0],
        ];
    }

    protected function recentActivity(Branch $branch)
    {
        return Transaction::query()
            ->with(['account.product', 'creator'])
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at')
            ->latest('trans_date')
            ->latest('id')
            ->limit(8)
            ->get();
    }
}
