<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SocietyReportService
{
    public function build(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $productRows = $this->productRows($branch, $startDate, $endDate);
        $expenses = $this->branchFlow($branch, $startDate, $endDate, fn (Builder $query) => $this->expenseScope($query));
        $loans = $this->branchFlow($branch, $startDate, $endDate, fn (Builder $query) => $this->loanScope($query));
        $loanDisbursements = $this->branchFlow($branch, $startDate, $endDate, fn (Builder $query) => $this->loanDisbursementScope($query));
        $principalRepayments = $this->branchFlow($branch, $startDate, $endDate, fn (Builder $query) => $this->loanPrincipalRepaymentScope($query));
        $interestRepayments = $this->branchFlow($branch, $startDate, $endDate, fn (Builder $query) => $this->loanInterestRepaymentScope($query));
        $broughtForward = $this->broughtForward($branch, $startDate);

        $productSubtotal = round((float) $productRows->sum('balance'), 2);
        $memberAccountDebit = round((float) $productRows->sum('total_debit'), 2);
        $memberAccountCredit = round((float) $productRows->sum('total_credit'), 2);
        $currentTotal = round($broughtForward + $productSubtotal, 2);
        $loanExpenseDebit = round($expenses['total_debit'] + $loans['total_debit'], 2);
        $loanExpenseCredit = round($expenses['total_credit'] + $loans['total_credit'], 2);
        $finalTotal = round(($currentTotal + $loanExpenseCredit) - $loanExpenseDebit, 2);
        $reconciliation = [
            'opening_balance' => $broughtForward,
            'member_account_credit' => $memberAccountCredit,
            'member_account_debit' => $memberAccountDebit,
            'member_account_net' => $productSubtotal,
            'income' => $expenses['total_credit'],
            'expenses' => $expenses['total_debit'],
            'loan_disbursements' => $loanDisbursements['total_debit'],
            'principal_repayments' => $principalRepayments['total_credit'],
            'interest_repayments' => $interestRepayments['total_credit'],
            'closing_balance' => $finalTotal,
        ];

        return [
            'rows' => $productRows,
            'expenses' => $expenses,
            'loans' => $loans,
            'summary' => [
                'brought_forward' => $broughtForward,
                'product_subtotal' => $productSubtotal,
                'current_total' => $currentTotal,
                'loan_expense_debit' => $loanExpenseDebit,
                'loan_expense_credit' => $loanExpenseCredit,
                'final_total' => $finalTotal,
            ],
            'reconciliation' => $reconciliation,
            'warnings' => $this->warnings($branch, $reconciliation),
            'filters' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }

    public function buildExportData(Branch $branch, Request $request): array
    {
        return $this->build($branch, $request);
    }

    protected function productRows(Branch $branch, ?Carbon $startDate, Carbon $endDate): Collection
    {
        $query = Transaction::query()
            ->join('savings_accounts', 'savings_accounts.id', '=', 'transactions.savings_account_id')
            ->join('savings_products', 'savings_products.id', '=', 'savings_accounts.savings_product_id')
            ->where('transactions.branch_id', $branch->id)
            ->where('transactions.is_branch', false)
            ->whereNull('transactions.deleted_at')
            ->whereNull('savings_accounts.disabled_at')
            ->where('transactions.trans_date', '<=', $endDate);

        if ($startDate) {
            $query->where('transactions.trans_date', '>=', $startDate);
        }

        return $query
            ->groupBy('savings_products.id', 'savings_products.name', 'savings_products.type')
            ->orderByRaw("FIELD(savings_products.type, 'SAVINGS', 'SHARES', 'AUTHENTICATION', 'DEPOSIT')")
            ->select([
                'savings_products.id',
                'savings_products.name',
                'savings_products.type',
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(transactions.dr_cr) = 'dr' THEN transactions.amount ELSE 0 END), 0) as total_debit"),
                DB::raw("COALESCE(SUM(CASE WHEN LOWER(transactions.dr_cr) = 'cr' THEN transactions.amount ELSE 0 END), 0) as total_credit"),
            ])
            ->get()
            ->map(function ($row): array {
                $debit = round((float) $row->total_debit, 2);
                $credit = round((float) $row->total_credit, 2);

                return [
                    'name' => $row->name ?: $row->type,
                    'type' => $row->type,
                    'total_debit' => $debit,
                    'total_credit' => $credit,
                    'balance' => round($credit - $debit, 2),
                ];
            })
            ->values();
    }

    protected function broughtForward(Branch $branch, ?Carbon $startDate): float
    {
        if (! $startDate) {
            return 0.0;
        }

        $productNet = $this->productRows($branch, null, $startDate->copy()->subSecond())->sum('balance');
        $expenseNet = $this->branchFlow($branch, null, $startDate->copy()->subSecond(), fn (Builder $query) => $this->expenseScope($query));
        $loanNet = $this->branchFlow($branch, null, $startDate->copy()->subSecond(), fn (Builder $query) => $this->loanScope($query));

        return round(
            (float) $productNet
            + ((float) $expenseNet['total_credit'] - (float) $expenseNet['total_debit'])
            + ((float) $loanNet['total_credit'] - (float) $loanNet['total_debit']),
            2
        );
    }

    protected function branchFlow(Branch $branch, ?Carbon $startDate, Carbon $endDate, callable $scope): array
    {
        $query = Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at')
            ->where('trans_date', '<=', $endDate);

        if ($startDate) {
            $query->where('trans_date', '>=', $startDate);
        }

        $scope($query);

        $row = $query->selectRaw("
            COALESCE(SUM(CASE WHEN LOWER(dr_cr) = 'dr' THEN amount ELSE 0 END), 0) as total_debit,
            COALESCE(SUM(CASE WHEN LOWER(dr_cr) = 'cr' THEN amount ELSE 0 END), 0) as total_credit
        ")->first();

        return [
            'total_debit' => round((float) ($row->total_debit ?? 0), 2),
            'total_credit' => round((float) ($row->total_credit ?? 0), 2),
        ];
    }

    protected function expenseScope(Builder $query): void
    {
        $query->where('tracking_id', 'expenses');
    }

    protected function loanScope(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder->whereIn('tracking_id', ['loan', 'loan_repayment'])
                ->orWhereIn('type', ['Loan Disbursement', 'Loan Repayment', 'Loan Interest Repayment']);
        });
    }

    protected function loanDisbursementScope(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder->where('tracking_id', 'loan')
                ->orWhere('type', 'Loan Disbursement');
        });
    }

    protected function loanPrincipalRepaymentScope(Builder $query): void
    {
        $query->where(function (Builder $builder): void {
            $builder->where('type', 'Loan Repayment')
                ->orWhere(function (Builder $nested): void {
                    $nested->where('tracking_id', 'loan_repayment')
                        ->where('type', '!=', 'Loan Interest Repayment');
                });
        });
    }

    protected function loanInterestRepaymentScope(Builder $query): void
    {
        $query->where('type', 'Loan Interest Repayment');
    }

    protected function warnings(Branch $branch, array $reconciliation): array
    {
        $warnings = [];

        if (! $branch->branch_user_id) {
            $warnings[] = 'This branch is not linked to a society account user yet.';
        }

        if ((float) $reconciliation['closing_balance'] < 0) {
            $warnings[] = 'The closing society balance is negative for the selected period.';
        }

        if ((float) $reconciliation['loan_disbursements'] > ((float) $reconciliation['opening_balance'] + (float) $reconciliation['member_account_credit'] + (float) $reconciliation['income'])) {
            $warnings[] = 'Loan disbursements are higher than opening balance plus current inflows.';
        }

        return $warnings;
    }

    protected function resolveDateRange(Request $request): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        return [$startDate, $endDate];
    }
}
