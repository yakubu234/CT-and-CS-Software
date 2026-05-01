<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\User;
use App\Support\TableListing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class LoanDueReportService
{
    public function build(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $loansQuery = $this->baseQuery($branch, $request, $startDate, $endDate)
            ->orderBy('latest_due_date')
            ->orderBy('loans.loan_id');

        $summary = $this->aggregateSummary((clone $loansQuery)->reorder());
        $loans = TableListing::paginate($loansQuery, $request);

        return [
            'loans' => $loans,
            'summary' => $this->summaryCards($summary),
            'summary_meta' => [
                'loan_count' => (int) ($summary->loan_count ?? 0),
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
            ->orderBy('latest_due_date')
            ->orderBy('loans.loan_id');

        $summary = $this->aggregateSummary((clone $loansQuery)->reorder());
        $loans = $loansQuery->get()->map(fn ($loan) => $this->formatLoanRow($loan))->values();

        return [
            'branch' => $branch,
            'loans' => $loans,
            'summary' => [
                'total_amount' => round((float) ($summary->total_amount ?? 0), 2),
                'total_paid' => round((float) ($summary->total_paid ?? 0), 2),
                'outstanding' => round((float) ($summary->outstanding ?? 0), 2),
                'loan_count' => (int) ($summary->loan_count ?? 0),
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
            ->whereRaw('CAST(COALESCE(loans.balanace, 0) AS DECIMAL(15,2)) > 0')
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
                DB::raw('COALESCE(borrower_details.member_no, borrowers.member_no) as member_no'),
                DB::raw('COALESCE(borrower_details.mobile, \'\') as member_phone'),
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
                $builder->whereExists(function ($subQuery) use ($startDate, $endDate): void {
                    $subQuery->selectRaw('1')
                        ->from('loan_details')
                        ->whereColumn('loan_details.loan_id', 'loans.id')
                        ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
                        ->whereDate('loan_details.release_date', '>=', $startDate->toDateString())
                        ->whereDate('loan_details.release_date', '<=', $endDate->toDateString());
                })->orWhereExists(function ($subQuery) use ($startDate, $endDate): void {
                    $subQuery->selectRaw('1')
                        ->from('loan_payments')
                        ->whereColumn('loan_payments.loan_id', 'loans.id')
                        ->whereNull('loan_payments.deleted_at')
                        ->whereDate('loan_payments.paid_at', '>=', $startDate->toDateString())
                        ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString());
                })->orWhereDate('loans.due_date', '>=', $startDate->toDateString());
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

        $query->selectSub($this->latestApprovedAtSubQuery($endDate), 'approval_date');
        $query->selectSub($this->latestApprovedBySubQuery($endDate), 'approved_by_name');
        $query->selectSub($this->latestInterestRateSubQuery($endDate), 'interest_rate');
        $query->selectSub($this->latestDueDateSubQuery($endDate), 'latest_due_date');
        $query->selectSub($this->totalApprovedAmountSubQuery($endDate), 'total_amount');
        $query->selectSub($this->totalPaidSubQuery($endDate), 'total_paid');
        $query->selectSub($this->outstandingAmountSubQuery($endDate), 'outstanding_amount');

        return $query;
    }

    protected function latestApprovedAtSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_details')
            ->whereColumn('loan_details.loan_id', 'loans.id')
            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
            ->orderByDesc('loan_details.release_date')
            ->orderByDesc('loan_details.id')
            ->limit(1)
            ->selectRaw('COALESCE(DATE(loan_details.approved_at), loan_details.release_date)');
    }

    protected function latestApprovedBySubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_details')
            ->leftJoin('users as approvers', 'approvers.id', '=', 'loan_details.approved_by')
            ->whereColumn('loan_details.loan_id', 'loans.id')
            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
            ->orderByDesc('loan_details.release_date')
            ->orderByDesc('loan_details.id')
            ->limit(1)
            ->selectRaw("
                TRIM(
                    CONCAT(
                        COALESCE(approvers.name, ''),
                        CASE
                            WHEN approvers.last_name IS NOT NULL AND approvers.last_name <> ''
                                THEN CONCAT(' ', approvers.last_name)
                            ELSE ''
                        END
                    )
                )
            ");
    }

    protected function latestInterestRateSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_details')
            ->whereColumn('loan_details.loan_id', 'loans.id')
            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
            ->orderByDesc('loan_details.release_date')
            ->orderByDesc('loan_details.id')
            ->limit(1)
            ->selectRaw('COALESCE(loan_details.interest_rate, 0)');
    }

    protected function latestDueDateSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
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

    protected function totalApprovedAmountSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_details')
            ->whereColumn('loan_details.loan_id', 'loans.id')
            ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
            ->selectRaw('COALESCE(SUM(loan_details.applied_amount), 0)');
    }

    protected function totalPaidSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_payments')
            ->whereColumn('loan_payments.loan_id', 'loans.id')
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->selectRaw('COALESCE(SUM(loan_payments.repayment_amount), 0)');
    }

    protected function outstandingAmountSubQuery(Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        $approved = $this->totalApprovedAmountSubQuery($endDate);
        $paid = $this->totalPaidSubQuery($endDate);

        return DB::query()
            ->selectRaw('GREATEST((' . $approved->toSql() . ') - (' . $paid->toSql() . '), 0)')
            ->mergeBindings($approved)
            ->mergeBindings($paid);
    }

    protected function aggregateSummary(Builder $query): stdClass
    {
        $summary = DB::query()
            ->fromSub($query->toBase(), 'loan_due_rows')
            ->selectRaw('
                COUNT(*) as loan_count,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COALESCE(SUM(total_paid), 0) as total_paid,
                COALESCE(SUM(outstanding_amount), 0) as outstanding
            ')
            ->first();

        return $summary ?? (object) [
            'loan_count' => 0,
            'total_amount' => 0,
            'total_paid' => 0,
            'outstanding' => 0,
        ];
    }

    protected function summaryCards(stdClass $summary): array
    {
        return [
            [
                'label' => 'Total Amount',
                'icon' => 'fas fa-wallet',
                'value' => (float) ($summary->total_amount ?? 0),
                'tone' => 'text-success',
            ],
            [
                'label' => 'Total Paid',
                'icon' => 'fas fa-money-check-alt',
                'value' => (float) ($summary->total_paid ?? 0),
                'tone' => 'text-info',
            ],
            [
                'label' => 'Outstanding',
                'icon' => 'fas fa-hand-holding-usd',
                'value' => (float) ($summary->outstanding ?? 0),
                'tone' => 'text-danger',
            ],
        ];
    }

    protected function formatLoanRow(object $loan): array
    {
        return [
            'loan_id' => $loan->loan_id,
            'branch_name' => $loan->branch?->name,
            'borrower_name' => $loan->borrower_name ?: 'Unnamed Member',
            'member_no' => $loan->member_no ?: 'N/A',
            'member_phone' => $loan->member_phone ?: '',
            'approval_date' => $loan->approval_date,
            'approved_by_name' => $loan->approved_by_name ?: 'N/A',
            'interest_rate' => round((float) ($loan->interest_rate ?? 0), 4),
            'total_amount' => round((float) ($loan->total_amount ?? 0), 2),
            'total_paid' => round((float) ($loan->total_paid ?? 0), 2),
            'due_date' => $loan->latest_due_date,
            'outstanding_amount' => round((float) ($loan->outstanding_amount ?? 0), 2),
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
