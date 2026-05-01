<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\LoanDetail;
use App\Models\User;
use App\Support\TableListing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class InterestReportService
{
    public function build(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $membersQuery = $this->baseMemberQuery($branch, $request, $startDate, $endDate)
            ->orderByDesc('interest_current')
            ->orderBy('member_no')
            ->orderBy('users.id');

        $summary = $this->aggregateSummary((clone $membersQuery)->reorder());
        $members = TableListing::paginate($membersQuery, $request);

        return [
            'members' => $members,
            'summary' => [
                'interest_brought_forward' => round((float) ($summary->interest_brought_forward ?? 0), 2),
                'interest_current' => round((float) ($summary->interest_current ?? 0), 2),
                'interest_total' => round((float) ($summary->interest_total ?? 0), 2),
                'outstanding_interest' => round((float) ($summary->outstanding_interest ?? 0), 2),
                'member_count' => (int) ($summary->member_count ?? 0),
            ],
            'filters' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate->toDateString(),
                'member_id' => $request->input('member_id'),
                'search' => $request->string('search')->toString(),
            ],
            'weekly_trend' => $this->weeklyTrend($branch, $request, $startDate, $endDate),
        ];
    }

    public function buildExportData(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $membersQuery = $this->baseMemberQuery($branch, $request, $startDate, $endDate)
            ->orderByDesc('interest_current')
            ->orderBy('member_no')
            ->orderBy('users.id');

        $summary = $this->aggregateSummary((clone $membersQuery)->reorder());
        $members = $membersQuery->get()->map(fn ($member) => $this->formatMemberSummaryRow($member))->values();

        return [
            'branch' => $branch,
            'summary' => [
                'interest_brought_forward' => round((float) ($summary->interest_brought_forward ?? 0), 2),
                'interest_current' => round((float) ($summary->interest_current ?? 0), 2),
                'interest_total' => round((float) ($summary->interest_total ?? 0), 2),
                'outstanding_interest' => round((float) ($summary->outstanding_interest ?? 0), 2),
                'member_count' => (int) ($summary->member_count ?? 0),
            ],
            'filters' => [
                'start_date' => $startDate?->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'member_id' => $request->input('member_id'),
                'search' => $request->string('search')->toString(),
            ],
            'weekly_trend' => $this->weeklyTrend($branch, $request, $startDate, $endDate),
            'member_sheets' => $this->buildMemberSheetData($branch, $request, $startDate, $endDate, $members),
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

    protected function baseMemberQuery(Branch $branch, Request $request, ?Carbon $startDate, Carbon $endDate): Builder
    {
        $query = User::query()
            ->leftJoin('user_details as details', 'details.user_id', '=', 'users.id')
            ->where('users.branch_id', $branch->id)
            ->where('users.branch_account', false)
            ->whereNull('users.deleted_at')
            ->where(function (Builder $query): void {
                $query->where('users.user_type', 'customer')
                    ->orWhere('users.society_exco', true)
                    ->orWhere('users.former_exco', true);
            })
            ->where(function (Builder $query) use ($branch, $endDate): void {
                $query->whereExists($this->interestExistsSubQuery($branch, $endDate))
                    ->orWhereExists($this->outstandingInterestExistsSubQuery($branch, $endDate));
            })
            ->select([
                'users.id',
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
                    ) as member_name
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
                    ->orWhere('users.member_no', 'like', '%' . $search . '%')
                    ->orWhere('details.member_no', 'like', '%' . $search . '%');
            });
        }

        $query->selectSub($this->interestBroughtForwardSubQuery($branch, $startDate), 'interest_brought_forward');
        $query->selectSub($this->interestCurrentSubQuery($branch, $startDate, $endDate), 'interest_current');
        $query->selectSub($this->interestTotalSubQuery($branch, $endDate), 'interest_total');
        $query->selectSub($this->outstandingInterestSubQuery($branch, $endDate), 'outstanding_interest');
        $query->selectSub($this->lastInterestDateSubQuery($branch, $endDate), 'last_interest_date');
        $query->selectSub($this->loanCountSubQuery($branch, $endDate), 'loan_count');

        return $query;
    }

    protected function interestExistsSubQuery(Branch $branch, Carbon $endDate): \Closure
    {
        return function ($query) use ($branch, $endDate): void {
            $query->selectRaw('1')
                ->from('loan_payments')
                ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
                ->join('loan_details', 'loan_details.loan_id', '=', 'loans.id')
                ->whereColumn('loans.borrower_id', 'users.id')
                ->where('loans.branch_id', $branch->id)
                ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
                ->whereDate('loan_details.release_date', '<=', $endDate->toDateString())
                ->whereNull('loan_payments.deleted_at')
                ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
                ->whereRaw('COALESCE(loan_payments.interest_paid, 0) > 0');
        };
    }

    protected function outstandingInterestExistsSubQuery(Branch $branch, Carbon $endDate): \Closure
    {
        return function ($query) use ($branch, $endDate): void {
            $query->selectRaw('1')
                ->from('loans')
                ->whereColumn('loans.borrower_id', 'users.id')
                ->where('loans.branch_id', $branch->id)
                ->whereRaw(
                    "(SELECT COALESCE(lp.outstanding_interest, 0)
                        FROM loan_payments as lp
                        WHERE lp.loan_id = loans.id
                          AND lp.deleted_at IS NULL
                          AND DATE(lp.paid_at) <= ?
                        ORDER BY lp.paid_at DESC, lp.id DESC
                        LIMIT 1) > 0",
                    [$endDate->toDateString()]
                );
        };
    }

    protected function interestBroughtForwardSubQuery(Branch $branch, ?Carbon $startDate): \Illuminate\Database\Query\Builder
    {
        if (! $startDate) {
            return DB::query()->selectRaw('0');
        }

        return DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->whereColumn('loans.borrower_id', 'users.id')
            ->where('loans.branch_id', $branch->id)
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<', $startDate->toDateString())
            ->selectRaw('COALESCE(SUM(COALESCE(loan_payments.interest_paid, 0)), 0)');
    }

    protected function interestCurrentSubQuery(Branch $branch, ?Carbon $startDate, Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->whereColumn('loans.borrower_id', 'users.id')
            ->where('loans.branch_id', $branch->id)
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->selectRaw('COALESCE(SUM(COALESCE(loan_payments.interest_paid, 0)), 0)');

        if ($startDate) {
            $query->whereDate('loan_payments.paid_at', '>=', $startDate->toDateString());
        }

        return $query;
    }

    protected function interestTotalSubQuery(Branch $branch, Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->whereColumn('loans.borrower_id', 'users.id')
            ->where('loans.branch_id', $branch->id)
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->selectRaw('COALESCE(SUM(COALESCE(loan_payments.interest_paid, 0)), 0)');
    }

    protected function outstandingInterestSubQuery(Branch $branch, Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loans')
            ->whereColumn('loans.borrower_id', 'users.id')
            ->where('loans.branch_id', $branch->id)
            ->selectRaw(
                "COALESCE(SUM(COALESCE((
                    SELECT COALESCE(lp.outstanding_interest, 0)
                    FROM loan_payments as lp
                    WHERE lp.loan_id = loans.id
                      AND lp.deleted_at IS NULL
                      AND DATE(lp.paid_at) <= ?
                    ORDER BY lp.paid_at DESC, lp.id DESC
                    LIMIT 1
                ), 0)), 0)",
                [$endDate->toDateString()]
            );
    }

    protected function lastInterestDateSubQuery(Branch $branch, Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->whereColumn('loans.borrower_id', 'users.id')
            ->where('loans.branch_id', $branch->id)
            ->whereNull('loan_payments.deleted_at')
            ->whereRaw('COALESCE(loan_payments.interest_paid, 0) > 0')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->selectRaw('MAX(loan_payments.paid_at)');
    }

    protected function loanCountSubQuery(Branch $branch, Carbon $endDate): \Illuminate\Database\Query\Builder
    {
        return DB::table('loans')
            ->whereColumn('loans.borrower_id', 'users.id')
            ->where('loans.branch_id', $branch->id)
            ->whereExists(function ($query) use ($endDate): void {
                $query->selectRaw('1')
                    ->from('loan_details')
                    ->whereColumn('loan_details.loan_id', 'loans.id')
                    ->where('loan_details.decision_status', LoanDetail::STATUS_APPROVED)
                    ->whereDate('loan_details.release_date', '<=', $endDate->toDateString());
            })
            ->selectRaw('COUNT(*)');
    }

    protected function aggregateSummary(Builder $query): stdClass
    {
        $summary = DB::query()
            ->fromSub($query->toBase(), 'interest_report_rows')
            ->selectRaw('
                COUNT(*) as member_count,
                COALESCE(SUM(interest_brought_forward), 0) as interest_brought_forward,
                COALESCE(SUM(interest_current), 0) as interest_current,
                COALESCE(SUM(interest_total), 0) as interest_total,
                COALESCE(SUM(outstanding_interest), 0) as outstanding_interest
            ')
            ->first();

        return $summary ?? (object) [
            'member_count' => 0,
            'interest_brought_forward' => 0,
            'interest_current' => 0,
            'interest_total' => 0,
            'outstanding_interest' => 0,
        ];
    }

    protected function weeklyTrend(Branch $branch, Request $request, ?Carbon $startDate, Carbon $endDate): Collection
    {
        $query = DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->leftJoin('users as borrowers', 'borrowers.id', '=', 'loans.borrower_id')
            ->leftJoin('user_details as details', 'details.user_id', '=', 'borrowers.id')
            ->where('loans.branch_id', $branch->id)
            ->whereNull('loan_payments.deleted_at')
            ->whereRaw('COALESCE(loan_payments.interest_paid, 0) > 0')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString());

        if ($startDate) {
            $query->whereDate('loan_payments.paid_at', '>=', $startDate->toDateString());
        }

        if ($request->filled('member_id')) {
            $query->where('loans.borrower_id', (int) $request->input('member_id'));
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('borrowers.name', 'like', '%' . $search . '%')
                    ->orWhere('borrowers.last_name', 'like', '%' . $search . '%')
                    ->orWhere('borrowers.member_no', 'like', '%' . $search . '%')
                    ->orWhere('details.member_no', 'like', '%' . $search . '%')
                    ->orWhere('loans.loan_id', 'like', '%' . $search . '%');
            });
        }

        return $query
            ->selectRaw("
                DATE(DATE_SUB(loan_payments.paid_at, INTERVAL WEEKDAY(loan_payments.paid_at) DAY)) as week_start,
                DATE(DATE_ADD(DATE_SUB(loan_payments.paid_at, INTERVAL WEEKDAY(loan_payments.paid_at) DAY), INTERVAL 6 DAY)) as week_end,
                COALESCE(SUM(COALESCE(loan_payments.interest_paid, 0)), 0) as total_interest
            ")
            ->groupBy('week_start', 'week_end')
            ->orderBy('week_start')
            ->get()
            ->map(function ($row) {
                $weekStart = Carbon::parse($row->week_start);
                $weekEnd = Carbon::parse($row->week_end);

                return [
                    'week_label' => $weekStart->format('d M') . ' - ' . $weekEnd->format('d M Y'),
                    'week_start' => $weekStart->toDateString(),
                    'week_end' => $weekEnd->toDateString(),
                    'total_interest' => round((float) ($row->total_interest ?? 0), 2),
                ];
            })
            ->values();
    }

    protected function buildMemberSheetData(
        Branch $branch,
        Request $request,
        ?Carbon $startDate,
        Carbon $endDate,
        Collection $members
    ): Collection {
        $transactions = $this->memberTransactions($branch, $request, $startDate, $endDate, $members->pluck('id')->all())
            ->groupBy('member_id');

        return $members->map(function (array $member) use ($transactions) {
            return [
                'member_id' => (int) $member['id'],
                'member_no' => $member['member_no'],
                'member_name' => $member['member_name'],
                'interest_brought_forward' => $member['interest_brought_forward'],
                'interest_current' => $member['interest_current'],
                'interest_total' => $member['interest_total'],
                'outstanding_interest' => $member['outstanding_interest'],
                'last_interest_date' => $member['last_interest_date'],
                'loan_count' => $member['loan_count'],
                'rows' => $transactions->get($member['id'], collect())->values(),
            ];
        })->values();
    }

    protected function memberTransactions(
        Branch $branch,
        Request $request,
        ?Carbon $startDate,
        Carbon $endDate,
        array $memberIds
    ): Collection {
        if ($memberIds === []) {
            return collect();
        }

        $query = DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->leftJoin('users as borrowers', 'borrowers.id', '=', 'loans.borrower_id')
            ->leftJoin('user_details as details', 'details.user_id', '=', 'borrowers.id')
            ->where('loans.branch_id', $branch->id)
            ->whereIn('loans.borrower_id', $memberIds)
            ->whereNull('loan_payments.deleted_at')
            ->whereDate('loan_payments.paid_at', '<=', $endDate->toDateString())
            ->where(function ($query): void {
                $query->whereRaw('COALESCE(loan_payments.interest_paid, 0) > 0')
                    ->orWhereRaw('COALESCE(loan_payments.outstanding_interest, 0) > 0');
            });

        if ($startDate) {
            $query->whereDate('loan_payments.paid_at', '>=', $startDate->toDateString());
        }

        if ($request->filled('member_id')) {
            $query->where('loans.borrower_id', (int) $request->input('member_id'));
        }

        return $query
            ->select([
                'loans.borrower_id as member_id',
                'loans.loan_id',
                'loan_payments.paid_at',
                'loan_payments.interest_rate',
                'loan_payments.interest_paid',
                'loan_payments.outstanding_interest',
                'loan_payments.repayment_amount',
                'loan_payments.total_amount',
                'loan_payments.remarks',
                'loan_payments.carry_forward',
            ])
            ->orderBy('loans.borrower_id')
            ->orderBy('loan_payments.paid_at')
            ->orderBy('loan_payments.id')
            ->get()
            ->map(function ($row) {
                return [
                    'member_id' => (int) $row->member_id,
                    'loan_id' => $row->loan_id,
                    'paid_at' => $row->paid_at ? Carbon::parse($row->paid_at)->toDateString() : null,
                    'interest_rate' => round((float) ($row->interest_rate ?? 0), 4),
                    'interest_paid' => round((float) ($row->interest_paid ?? 0), 2),
                    'outstanding_interest' => round((float) ($row->outstanding_interest ?? 0), 2),
                    'repayment_amount' => round((float) ($row->repayment_amount ?? 0), 2),
                    'total_amount' => round((float) ($row->total_amount ?? 0), 2),
                    'remarks' => (string) ($row->remarks ?? ''),
                    'carry_forward' => round((float) ($row->carry_forward ?? 0), 2),
                ];
            });
    }

    protected function formatMemberSummaryRow(object $member): array
    {
        return [
            'id' => (int) $member->id,
            'member_no' => $member->member_no ?: ('MEMBER-' . $member->id),
            'member_name' => $member->member_name ?: 'Unnamed Member',
            'interest_brought_forward' => round((float) ($member->interest_brought_forward ?? 0), 2),
            'interest_current' => round((float) ($member->interest_current ?? 0), 2),
            'interest_total' => round((float) ($member->interest_total ?? 0), 2),
            'outstanding_interest' => round((float) ($member->outstanding_interest ?? 0), 2),
            'last_interest_date' => $member->last_interest_date,
            'loan_count' => (int) ($member->loan_count ?? 0),
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
