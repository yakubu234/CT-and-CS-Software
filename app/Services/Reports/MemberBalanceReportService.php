<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\User;
use App\Support\TableListing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class MemberBalanceReportService
{
    public function build(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $membersQuery = $this->baseQuery($branch, $request, $startDate, $endDate)
            ->orderBy('display_name');

        $members = TableListing::paginate($membersQuery, $request);

        $summary = DB::query()
            ->fromSub((clone $membersQuery)->reorder()->toBase(), 'member_balances')
            ->selectRaw('
                COALESCE(SUM(loan_opening), 0) as loan_opening,
                COALESCE(SUM(loan_current), 0) as loan_current,
                COALESCE(SUM(savings_opening), 0) as savings_opening,
                COALESCE(SUM(savings_current), 0) as savings_current,
                COALESCE(SUM(shares_opening), 0) as shares_opening,
                COALESCE(SUM(shares_current), 0) as shares_current,
                COALESCE(SUM(auth_opening), 0) as auth_opening,
                COALESCE(SUM(auth_current), 0) as auth_current,
                COALESCE(SUM(deposit_opening), 0) as deposit_opening,
                COALESCE(SUM(deposit_current), 0) as deposit_current
            ')
            ->first();

        $summary ??= (object) [
            'loan_opening' => 0,
            'loan_current' => 0,
            'savings_opening' => 0,
            'savings_current' => 0,
            'shares_opening' => 0,
            'shares_current' => 0,
            'auth_opening' => 0,
            'auth_current' => 0,
            'deposit_opening' => 0,
            'deposit_current' => 0,
        ];

        return [
            'members' => $members,
            'summary' => $this->summaryCards($summary),
            'filters' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate->toDateString(),
                'member_id' => $request->input('member_id'),
                'search' => $request->string('search')->toString(),
            ],
        ];
    }

    public function buildExportData(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $members = $this->baseQuery($branch, $request, $startDate, $endDate)
            ->orderBy('display_name')
            ->get()
            ->map(function ($member) {
                $loanOpening = (float) $member->loan_opening;
                $loanCurrent = (float) $member->loan_current;
                $sharesOpening = (float) $member->shares_opening;
                $sharesCurrent = (float) $member->shares_current;
                $authOpening = (float) $member->auth_opening;
                $authCurrent = (float) $member->auth_current;
                $depositOpening = (float) $member->deposit_opening;
                $depositCurrent = (float) $member->deposit_current;
                $savingsOpening = (float) $member->savings_opening;
                $savingsCurrent = (float) $member->savings_current;

                return [
                    'member_name' => $member->display_name ?: 'Unnamed Member',
                    'member_no' => $member->member_no ?: 'N/A',
                    'loan_opening' => $loanOpening,
                    'loan_current' => $loanCurrent,
                    'shares_opening' => $sharesOpening,
                    'shares_current' => $sharesCurrent,
                    'auth_opening' => $authOpening,
                    'auth_current' => $authCurrent,
                    'deposit_opening' => $depositOpening,
                    'deposit_current' => $depositCurrent,
                    'savings_opening' => $savingsOpening,
                    'savings_current' => $savingsCurrent,
                ];
            })
            ->values();

        return [
            'branch' => $branch,
            'members' => $members,
            'summary' => $this->summaryTotalsFromMembers($members),
            'filters' => [
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d'),
                'member_id' => $request->input('member_id'),
                'search' => $request->string('search')->toString(),
            ],
        ];
    }

    public function memberOptions(Branch $branch)
    {
        return User::query()
            ->leftJoin('user_details as details', 'details.user_id', '=', 'users.id')
            ->where('users.branch_id', $branch->id)
            ->where('users.branch_account', false)
            ->whereNull('users.deleted_at')
            ->where(function (Builder $query): void {
                $query->where('users.user_type', 'customer')
                    ->orWhere('users.society_exco', true)
                    ->orWhere('users.former_exco', true);
            })
            ->orderBy('users.name')
            ->orderBy('users.last_name')
            ->get([
                'users.id',
                DB::raw("
                    TRIM(
                        CONCAT(
                            COALESCE(users.name, ''),
                            CASE
                                WHEN users.last_name IS NOT NULL AND users.last_name <> ''
                                    THEN CONCAT(' ', users.last_name)
                                ELSE ''
                            END
                        )
                    ) as display_name
                "),
                DB::raw('COALESCE(details.member_no, users.member_no) as member_code'),
            ]);
    }

    protected function baseQuery(
        Branch $branch,
        Request $request,
        ?Carbon $startDate,
        Carbon $endDate,
    ): Builder {
        $query = User::query()
            ->leftJoin('user_details as details', 'details.user_id', '=', 'users.id')
            ->where('users.branch_id', $branch->id)
            ->where('users.branch_account', false)
            ->whereNull('users.deleted_at')
            ->where(function (Builder $builder): void {
                $builder->where('users.user_type', 'customer')
                    ->orWhere('users.society_exco', true)
                    ->orWhere('users.former_exco', true);
            })
            ->select([
                'users.id',
                'users.name',
                'users.last_name',
                'users.email',
                'users.profile_picture',
                DB::raw('COALESCE(details.member_no, users.member_no) as member_no'),
                DB::raw("
                    TRIM(
                        CONCAT(
                            COALESCE(users.name, ''),
                            CASE
                                WHEN users.last_name IS NOT NULL AND users.last_name <> ''
                                    THEN CONCAT(' ', users.last_name)
                                ELSE ''
                            END
                        )
                    ) as display_name
                "),
            ]);

        if ($request->filled('member_id')) {
            $query->where('users.id', (int) $request->input('member_id'));
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.last_name', 'like', '%' . $search . '%')
                    ->orWhere('users.email', 'like', '%' . $search . '%')
                    ->orWhere('users.member_no', 'like', '%' . $search . '%')
                    ->orWhere('details.member_no', 'like', '%' . $search . '%');
            });
        }

        foreach ([
            'SAVINGS' => 'savings',
            'SHARES' => 'shares',
            'AUTHENTICATION' => 'auth',
            'DEPOSIT' => 'deposit',
        ] as $type => $alias) {
            $query->selectSub(
                $this->accountBalanceSubQuery($type, $startDate, true),
                $alias . '_opening'
            );

            $query->selectSub(
                $this->accountBalanceSubQuery($type, $endDate, false),
                $alias . '_current'
            );
        }

        $query->selectSub($this->loanOutstandingSubQuery($branch, $startDate, true), 'loan_opening');
        $query->selectSub($this->loanOutstandingSubQuery($branch, $endDate, false), 'loan_current');

        return $query;
    }

    protected function accountBalanceSubQuery(string $type, Carbon|string|null $date, bool $exclusive): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('transactions')
            ->join('savings_accounts', 'savings_accounts.id', '=', 'transactions.savings_account_id')
            ->join('savings_products', 'savings_products.id', '=', 'savings_accounts.savings_product_id')
            ->whereColumn('savings_accounts.user_id', 'users.id')
            ->where('savings_accounts.is_branch_acount', false)
            ->where('savings_products.type', $type)
            ->where('transactions.is_branch', false)
            ->where('transactions.tracking_id', 'regular')
            ->whereNull('transactions.deleted_at')
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN LOWER(transactions.dr_cr) = 'cr' THEN transactions.amount
                            ELSE -transactions.amount
                        END
                    ),
                    0
                )
            ");

        if ($date instanceof Carbon) {
            $operator = $exclusive ? '<' : '<=';
            $query->where('transactions.trans_date', $operator, $date);
        }

        if ($date === null && $exclusive) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    protected function loanOutstandingSubQuery(Branch $branch, ?Carbon $date, bool $exclusive): \Illuminate\Database\Query\Builder
    {
        if (! $date) {
            return DB::query()->selectRaw('0');
        }

        $approvedOperator = $exclusive ? '<' : '<=';
        $repaymentOperator = $exclusive ? '<' : '<=';
        $cutoff = $date->toDateString();

        $approvedSubQuery = DB::table('loan_details')
            ->join('loans', 'loans.id', '=', 'loan_details.loan_id')
            ->whereColumn('loans.borrower_id', 'users.id')
            ->where('loans.branch_id', $branch->id)
            ->where('loan_details.decision_status', 'approved')
            ->whereDate('loan_details.release_date', $approvedOperator, $cutoff)
            ->selectRaw('COALESCE(SUM(loan_details.applied_amount), 0)');

        $repaymentSubQuery = DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->whereColumn('loans.borrower_id', 'users.id')
            ->where('loans.branch_id', $branch->id)
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', $repaymentOperator, $cutoff)
            ->selectRaw('COALESCE(SUM(loan_payments.repayment_amount), 0)');

        return DB::query()
            ->selectRaw(
                'GREATEST((' . $approvedSubQuery->toSql() . ') - (' . $repaymentSubQuery->toSql() . '), 0)'
            )
            ->mergeBindings($approvedSubQuery)
            ->mergeBindings($repaymentSubQuery);
    }

    protected function summaryCards(stdClass $summary): array
    {
        $loanOpening = (float) $summary->loan_opening;
        $loanCurrent = (float) $summary->loan_current;
        $savingsOpening = (float) $summary->savings_opening;
        $savingsCurrent = (float) $summary->savings_current;
        $sharesOpening = (float) $summary->shares_opening;
        $sharesCurrent = (float) $summary->shares_current;
        $authOpening = (float) $summary->auth_opening;
        $authCurrent = (float) $summary->auth_current;
        $depositOpening = (float) $summary->deposit_opening;
        $depositCurrent = (float) $summary->deposit_current;

        return [
            [
                'label' => 'Loan',
                'icon' => 'fas fa-hand-holding-usd',
                'opening' => $loanOpening,
                'current' => $loanCurrent,
                'tone' => 'text-danger',
            ],
            [
                'label' => 'Savings',
                'icon' => 'fas fa-piggy-bank',
                'opening' => $savingsOpening,
                'current' => $savingsCurrent,
                'tone' => 'text-success',
            ],
            [
                'label' => 'Shares',
                'icon' => 'fas fa-chart-pie',
                'opening' => $sharesOpening,
                'current' => $sharesCurrent,
                'tone' => 'text-success',
            ],
            [
                'label' => 'Auth',
                'icon' => 'fas fa-lock',
                'opening' => $authOpening,
                'current' => $authCurrent,
                'tone' => 'text-success',
            ],
            [
                'label' => 'Deposit',
                'icon' => 'fas fa-wallet',
                'opening' => $depositOpening,
                'current' => $depositCurrent,
                'tone' => 'text-success',
            ],
            [
                'label' => 'Total Net',
                'icon' => 'fas fa-coins',
                'opening' => ($savingsOpening + $sharesOpening + $authOpening + $depositOpening) - $loanOpening,
                'current' => ($savingsCurrent + $sharesCurrent + $authCurrent + $depositCurrent) - $loanCurrent,
                'tone' => 'text-primary',
            ],
        ];
    }

    protected function summaryTotalsFromMembers(Collection $members): array
    {
        return [
            'loan_opening' => round((float) $members->sum('loan_opening'), 2),
            'loan_current' => round((float) $members->sum('loan_current'), 2),
            'shares_opening' => round((float) $members->sum('shares_opening'), 2),
            'shares_current' => round((float) $members->sum('shares_current'), 2),
            'auth_opening' => round((float) $members->sum('auth_opening'), 2),
            'auth_current' => round((float) $members->sum('auth_current'), 2),
            'deposit_opening' => round((float) $members->sum('deposit_opening'), 2),
            'deposit_current' => round((float) $members->sum('deposit_current'), 2),
            'savings_opening' => round((float) $members->sum('savings_opening'), 2),
            'savings_current' => round((float) $members->sum('savings_current'), 2),
        ];
    }

    protected function resolveDateRange(Request $request): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfDay()
            : null;

        $endDate = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        return [$startDate, $endDate];
    }
}
