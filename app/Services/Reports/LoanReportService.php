<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\User;
use App\Support\MemberNumber;
use App\Support\TableListing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class LoanReportService
{
    public function build(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $loansQuery = $this->baseQuery($branch, $request, $startDate, $endDate)
            ->orderByDesc('first_release_date')
            ->orderBy('loans.loan_id');

        $summary = $this->aggregateSummary((clone $loansQuery)->reorder());
        $loans = TableListing::paginate($loansQuery, $request);

        return [
            'loans' => $loans,
            'summary' => $this->summaryCards($summary),
            'summary_meta' => [
                'loan_count' => (int) ($summary->loan_count ?? 0),
                'active_count' => (int) ($summary->active_count ?? 0),
            ],
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

        $loansQuery = $this->baseQuery($branch, $request, $startDate, $endDate)
            ->orderByDesc('first_release_date')
            ->orderBy('loans.loan_id');

        $summary = $this->aggregateSummary((clone $loansQuery)->reorder());
        $loans = $loansQuery->get()->map(fn ($loan) => $this->formatLoanRow($loan, $branch))->values();

        return [
            'branch' => $branch,
            'loans' => $loans,
            'summary' => [
                'total_disbursed' => round((float) ($summary->total_disbursed ?? 0), 2),
                'principal_paid_period' => round((float) ($summary->principal_paid_period ?? 0), 2),
                'interest_paid_period' => round((float) ($summary->interest_paid_period ?? 0), 2),
                'additional_loans' => round((float) ($summary->additional_loans ?? 0), 2),
                'outstanding_amount' => round((float) ($summary->outstanding_amount ?? 0), 2),
                'loan_count' => (int) ($summary->loan_count ?? 0),
                'active_count' => (int) ($summary->active_count ?? 0),
            ],
            'filters' => [
                'start_date' => $startDate?->format('Y-m-d'),
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

    protected function baseQuery(Branch $branch, Request $request, ?Carbon $startDate, Carbon $endDate): Builder
    {
        $query = Loan::query()
            ->leftJoin('users as borrowers', 'borrowers.id', '=', 'loans.borrower_id')
            ->leftJoin('user_details as borrower_details', 'borrower_details.user_id', '=', 'borrowers.id')
            ->where('loans.branch_id', $branch->id)
            ->whereExists(function ($subQuery) use ($endDate): void {
                $subQuery->selectRaw('1')
                    ->from('loan_details')
                    ->whereColumn('loan_details.loan_id', 'loans.id')
                    ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
                    ->whereDate('loan_details.release_date', '<=', $endDate->toDateString());
            })
            ->select([
                'loans.id',
                'loans.loan_id',
                'loans.repayment_status',
                'loans.status',
                DB::raw('COALESCE(borrower_details.member_no, borrowers.member_no) as member_no'),
                DB::raw("
                    TRIM(
                        CONCAT(
                            COALESCE(borrowers.name, ''),
                            CASE
                                WHEN borrowers.last_name IS NOT NULL AND borrowers.last_name <> ''
                                    THEN CONCAT(' ', borrowers.last_name)
                                ELSE ''
                            END
                        )
                    ) as borrower_name
                "),
            ]);

        if ($request->filled('member_id')) {
            $query->where('loans.borrower_id', (int) $request->input('member_id'));
        }

        if ($startDate) {
            $query->where(function (Builder $builder) use ($startDate, $endDate): void {
                $builder->whereRaw('CAST(COALESCE(loans.balanace, 0) AS DECIMAL(15,2)) > 0')
                    ->orWhereExists(function ($subQuery) use ($startDate, $endDate): void {
                        $subQuery->selectRaw('1')
                            ->from('loan_details')
                            ->whereColumn('loan_details.loan_id', 'loans.id')
                            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
                            ->whereDate('loan_details.release_date', '>=', $startDate->toDateString())
                            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString());
                    })
                    ->orWhereExists(function ($subQuery) use ($startDate, $endDate): void {
                        $subQuery->selectRaw('1')
                            ->from('loan_payments')
                            ->whereColumn('loan_payments.loan_id', 'loans.id')
                            ->whereNull('loan_payments.deleted_at')
                            ->whereDate('loan_payments.paid_at', '>=', $startDate->toDateString())
                            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString());
                    });
            });
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('loans.loan_id', 'like', '%' . $search . '%')
                    ->orWhere('borrowers.name', 'like', '%' . $search . '%')
                    ->orWhere('borrowers.last_name', 'like', '%' . $search . '%')
                    ->orWhere('borrowers.member_no', 'like', '%' . $search . '%')
                    ->orWhere('borrower_details.member_no', 'like', '%' . $search . '%');
            });
        }

        $query->selectSub($this->firstApprovedReleaseDateSubQuery($endDate), 'first_release_date');
        $query->selectSub($this->lastApprovedDueDateSubQuery($endDate), 'due_date');
        $query->selectSub($this->firstApprovedAmountSubQuery($endDate), 'original_amount');
        $query->selectSub($this->totalApprovedAmountSubQuery($endDate), 'total_disbursed_amount');
        $query->selectSub($this->principalPaidPeriodSubQuery($startDate, $endDate), 'principal_paid_period');
        $query->selectSub($this->interestPaidPeriodSubQuery($startDate, $endDate), 'interest_paid_period');
        $query->selectSub($this->principalPaidTotalSubQuery($endDate), 'principal_paid_total');
        $query->selectSub($this->lastPaymentDateSubQuery($endDate), 'last_payment_date');

        return $query;
    }

    protected function firstApprovedReleaseDateSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_details')
            ->whereColumn('loan_details.loan_id', 'loans.id')
            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
            ->orderBy('loan_details.release_date')
            ->orderBy('loan_details.id')
            ->limit(1)
            ->select('loan_details.release_date');
    }

    protected function lastApprovedDueDateSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_details')
            ->whereColumn('loan_details.loan_id', 'loans.id')
            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
            ->orderByDesc('loan_details.release_date')
            ->orderByDesc('loan_details.id')
            ->limit(1)
            ->select('loan_details.due_date');
    }

    protected function firstApprovedAmountSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_details')
            ->whereColumn('loan_details.loan_id', 'loans.id')
            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
            ->orderBy('loan_details.release_date')
            ->orderBy('loan_details.id')
            ->limit(1)
            ->selectRaw('COALESCE(loan_details.applied_amount, 0)');
    }

    protected function totalApprovedAmountSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_details')
            ->whereColumn('loan_details.loan_id', 'loans.id')
            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
            ->selectRaw('COALESCE(SUM(loan_details.applied_amount), 0)');
    }

    protected function principalPaidPeriodSubQuery(?Carbon $startDate, Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('loan_payments')
            ->whereColumn('loan_payments.loan_id', 'loans.id')
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->selectRaw('COALESCE(SUM(loan_payments.repayment_amount), 0)');

        if ($startDate) {
            $query->whereDate('loan_payments.paid_at', '>=', $startDate->toDateString());
        }

        return $query;
    }

    protected function interestPaidPeriodSubQuery(?Carbon $startDate, Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('loan_payments')
            ->whereColumn('loan_payments.loan_id', 'loans.id')
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->selectRaw('COALESCE(SUM(COALESCE(loan_payments.interest_paid, 0)), 0)');

        if ($startDate) {
            $query->whereDate('loan_payments.paid_at', '>=', $startDate->toDateString());
        }

        return $query;
    }

    protected function principalPaidTotalSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_payments')
            ->whereColumn('loan_payments.loan_id', 'loans.id')
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->selectRaw('COALESCE(SUM(loan_payments.repayment_amount), 0)');
    }

    protected function lastPaymentDateSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_payments')
            ->whereColumn('loan_payments.loan_id', 'loans.id')
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->selectRaw('MAX(loan_payments.paid_at)');
    }

    protected function aggregateSummary(Builder $query): stdClass
    {
        $summary = DB::query()
            ->fromSub($query->toBase(), 'loan_report_rows')
            ->selectRaw('
                COUNT(*) as loan_count,
                COALESCE(SUM(CASE WHEN COALESCE(total_disbursed_amount, 0) - COALESCE(principal_paid_total, 0) > 0 THEN 1 ELSE 0 END), 0) as active_count,
                COALESCE(SUM(total_disbursed_amount), 0) as total_disbursed,
                COALESCE(SUM(principal_paid_period), 0) as principal_paid_period,
                COALESCE(SUM(interest_paid_period), 0) as interest_paid_period,
                COALESCE(SUM(GREATEST(COALESCE(total_disbursed_amount, 0) - COALESCE(original_amount, 0), 0)), 0) as additional_loans,
                COALESCE(SUM(GREATEST(COALESCE(total_disbursed_amount, 0) - COALESCE(principal_paid_total, 0), 0)), 0) as outstanding_amount
            ')
            ->first();

        return $summary ?? (object) [
            'loan_count' => 0,
            'active_count' => 0,
            'total_disbursed' => 0,
            'principal_paid_period' => 0,
            'interest_paid_period' => 0,
            'additional_loans' => 0,
            'outstanding_amount' => 0,
        ];
    }

    protected function summaryCards(stdClass $summary): array
    {
        return [
            [
                'label' => 'Total Disbursed',
                'icon' => 'fas fa-wallet',
                'value' => (float) ($summary->total_disbursed ?? 0),
                'tone' => 'text-primary',
            ],
            [
                'label' => 'Paid In Period',
                'icon' => 'fas fa-money-bill-wave',
                'value' => (float) ($summary->principal_paid_period ?? 0),
                'tone' => 'text-success',
            ],
            [
                'label' => 'Interest In Period',
                'icon' => 'fas fa-percent',
                'value' => (float) ($summary->interest_paid_period ?? 0),
                'tone' => 'text-info',
            ],
            [
                'label' => 'Additional Loans',
                'icon' => 'fas fa-layer-group',
                'value' => (float) ($summary->additional_loans ?? 0),
                'tone' => 'text-warning',
            ],
            [
                'label' => 'Outstanding',
                'icon' => 'fas fa-hand-holding-usd',
                'value' => (float) ($summary->outstanding_amount ?? 0),
                'tone' => 'text-danger',
            ],
        ];
    }

    protected function formatLoanRow(object $loan, Branch $branch): array
    {
        $originalAmount = round((float) ($loan->original_amount ?? 0), 2);
        $totalDisbursed = round((float) ($loan->total_disbursed_amount ?? 0), 2);
        $principalPaidPeriod = round((float) ($loan->principal_paid_period ?? 0), 2);
        $interestPaidPeriod = round((float) ($loan->interest_paid_period ?? 0), 2);
        $principalPaidTotal = round((float) ($loan->principal_paid_total ?? 0), 2);

        return [
            'loan_id' => $loan->loan_id,
            'borrower_name' => $loan->borrower_name ?: 'Unnamed Member',
            'member_no' => MemberNumber::normalize($loan->member_no, $branch) ?: 'N/A',
            'first_release_date' => $loan->first_release_date,
            'due_date' => $loan->due_date,
            'original_amount' => $originalAmount,
            'additional_amount' => round(max($totalDisbursed - $originalAmount, 0), 2),
            'principal_paid_period' => $principalPaidPeriod,
            'interest_paid_period' => $interestPaidPeriod,
            'outstanding_amount' => round(max($totalDisbursed - $principalPaidTotal, 0), 2),
            'last_payment_date' => $loan->last_payment_date,
            'status' => round(max($totalDisbursed - $principalPaidTotal, 0), 2) > 0 ? 'Active' : 'Closed',
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
