<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\LoanDetail;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\User;
use App\Support\MemberNumber;
use App\Support\TableListing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SocietyLedgerReportService
{
    protected const ACCOUNT_TYPES = ['SHARES', 'SAVINGS', 'DEPOSIT', 'AUTHENTICATION'];

    public function build(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $query = $this->transactionsQuery($branch, $request, $startDate, $endDate)
            ->latest('trans_date')
            ->latest('id');

        $records = TableListing::paginate($query, $request, 20);
        $summary = $this->summary($branch, $request, $startDate, $endDate);

        return [
            'records' => $records,
            'member_ledgers' => $this->previewLedgers($branch, $request, $startDate, $endDate),
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate->toDateString(),
                'member_id' => $request->string('member_id')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ];
    }

    public function buildExportData(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $memberId = (int) $request->integer('member_id');

        $members = User::query()
            ->with(['detail', 'branch'])
            ->where('branch_id', $branch->id)
            ->where('user_type', 'customer')
            ->where('branch_account', false)
            ->when($memberId > 0, fn (Builder $query) => $query->where('id', $memberId))
            ->orderBy('name')
            ->get();

        $memberSheets = $members->map(fn (User $member): array => $this->memberLedger($branch, $member, $startDate, $endDate))
            ->filter(fn (array $sheet): bool => $sheet['rows']->isNotEmpty())
            ->values();

        return [
            'sheets' => $memberSheets,
            'summary' => $this->summary($branch, $request, $startDate, $endDate),
            'filters' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate->toDateString(),
                'member_id' => $request->string('member_id')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ];
    }

    public function memberOptions(Branch $branch): Collection
    {
        return User::query()
            ->with('detail')
            ->where('branch_id', $branch->id)
            ->where('user_type', 'customer')
            ->where('branch_account', false)
            ->orderBy('name')
            ->get()
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'member_no' => MemberNumber::normalize($member->detail?->member_no ?: $member->member_no, $branch),
            ]);
    }

    protected function transactionsQuery(Branch $branch, Request $request, ?Carbon $startDate, Carbon $endDate): Builder
    {
        return Transaction::query()
            ->with(['user.detail', 'account.product'])
            ->where('branch_id', $branch->id)
            ->where('is_branch', false)
            ->whereNull('deleted_at')
            ->where('trans_date', '<=', $endDate)
            ->when($startDate, fn (Builder $query) => $query->where('trans_date', '>=', $startDate))
            ->when($request->filled('member_id'), fn (Builder $query) => $query->where('user_id', (int) $request->input('member_id')))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%' . trim((string) $request->input('search')) . '%';

                $query->where(function (Builder $builder) use ($search): void {
                    $builder->where('description', 'like', $search)
                        ->orWhere('type', 'like', $search)
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery->where('name', 'like', $search)
                                ->orWhere('last_name', 'like', $search)
                                ->orWhere('member_no', 'like', $search);
                        })
                        ->orWhereHas('account', function (Builder $accountQuery) use ($search): void {
                            $accountQuery->where('account_number', 'like', $search);
                        });
                });
            });
    }

    protected function summary(Branch $branch, Request $request, ?Carbon $startDate, Carbon $endDate): array
    {
        $query = $this->transactionsQuery($branch, $request, $startDate, $endDate);

        $credit = (float) (clone $query)->whereRaw("LOWER(dr_cr) = 'cr'")->sum('amount');
        $debit = (float) (clone $query)->whereRaw("LOWER(dr_cr) = 'dr'")->sum('amount');

        return [
            'credit' => round($credit, 2),
            'debit' => round($debit, 2),
            'net' => round($credit - $debit, 2),
            'count' => (clone $query)->count(),
        ];
    }

    protected function memberLedger(Branch $branch, User $member, ?Carbon $startDate, Carbon $endDate): array
    {
        $balances = $this->openingBalances($member, $startDate);
        $loanBalance = $this->openingLoanBalance($member, $startDate);
        $events = collect();

        $accountTransactions = Transaction::query()
            ->with('account.product')
            ->where('branch_id', $branch->id)
            ->where('user_id', $member->id)
            ->where('is_branch', false)
            ->whereNull('deleted_at')
            ->where('trans_date', '<=', $endDate)
            ->when($startDate, fn (Builder $query) => $query->where('trans_date', '>=', $startDate))
            ->orderBy('trans_date')
            ->orderBy('id')
            ->get();

        foreach ($accountTransactions as $transaction) {
            $events->push([
                'date' => $transaction->trans_date?->copy() ?: $transaction->created_at,
                'sort' => 20,
                'type' => 'account',
                'record' => $transaction,
            ]);
        }

        LoanDetail::query()
            ->where('branch_id', $branch->id)
            ->where('borrower_id', $member->id)
            ->where('decision_status', LoanDetail::STATUS_APPROVED)
            ->where('release_date', '<=', $endDate)
            ->when($startDate, fn (Builder $query) => $query->where('release_date', '>=', $startDate))
            ->get()
            ->each(function (LoanDetail $detail) use ($events): void {
                $events->push([
                    'date' => $detail->release_date?->copy() ?: $detail->created_at,
                    'sort' => 10,
                    'type' => 'loan_disbursement',
                    'record' => $detail,
                ]);
            });

        LoanPayment::query()
            ->whereHas('loan', fn (Builder $query) => $query->where('borrower_id', $member->id)->where('branch_id', $branch->id))
            ->whereNull('deleted_at')
            ->where('paid_at', '<=', $endDate)
            ->when($startDate, fn (Builder $query) => $query->where('paid_at', '>=', $startDate))
            ->get()
            ->each(function (LoanPayment $payment) use ($events): void {
                $events->push([
                    'date' => $payment->paid_at?->copy() ?: $payment->created_at,
                    'sort' => 30,
                    'type' => 'loan_payment',
                    'record' => $payment,
                ]);
            });

        $rows = collect();

        if ($startDate) {
            $rows->push($this->blankLedgerRow($startDate->toDateString(), 'Brought Forward', '', $balances, $loanBalance));
        }

        foreach ($events->sortBy([['date', 'asc'], ['sort', 'asc']])->values() as $event) {
            if ($event['type'] === 'account') {
                /** @var Transaction $transaction */
                $transaction = $event['record'];
                $accountType = strtoupper((string) ($transaction->account?->product?->type ?? ''));
                $amount = round((float) $transaction->amount, 2);

                if (in_array($accountType, self::ACCOUNT_TYPES, true)) {
                    $balances[$accountType] = round($balances[$accountType] + (strtolower((string) $transaction->dr_cr) === 'cr' ? $amount : -$amount), 2);
                }

                $rows->push($this->accountLedgerRow($transaction, $balances, $loanBalance));
            }

            if ($event['type'] === 'loan_disbursement') {
                /** @var LoanDetail $detail */
                $detail = $event['record'];
                $amount = round((float) $detail->applied_amount, 2);
                $loanBalance = round($loanBalance + $amount, 2);
                $rows->push($this->loanLedgerRow($detail->release_date?->format('Y-m-d') ?: optional($detail->created_at)->format('Y-m-d'), 'Loan Granted', $amount, 0, $loanBalance, 0, $balances));
            }

            if ($event['type'] === 'loan_payment') {
                /** @var LoanPayment $payment */
                $payment = $event['record'];
                $principal = round((float) ($payment->repayment_amount ?? 0), 2);
                $interest = round((float) ($payment->interest_paid ?? $payment->is_interest_paid ?? 0), 2);
                $loanBalance = round(max($loanBalance - $principal, 0), 2);
                $rows->push($this->loanLedgerRow($payment->paid_at?->format('Y-m-d') ?: optional($payment->created_at)->format('Y-m-d'), 'Loan Repayment', 0, $principal, $loanBalance, $interest, $balances));
            }
        }

        return [
            'member' => [
                'name' => $member->name,
                'member_no' => MemberNumber::normalize($member->detail?->member_no ?: $member->member_no, $branch),
            ],
            'rows' => $rows,
            'summary' => $this->ledgerSummary($rows),
        ];
    }

    protected function previewLedgers(Branch $branch, Request $request, ?Carbon $startDate, Carbon $endDate): Collection
    {
        $memberId = (int) $request->integer('member_id');
        $memberQuery = User::query()
            ->with(['detail', 'branch'])
            ->where('branch_id', $branch->id)
            ->where('user_type', 'customer')
            ->where('branch_account', false);

        if ($memberId > 0) {
            $memberQuery->where('id', $memberId);
        } else {
            $activityMemberIds = $this->transactionsQuery($branch, $request, $startDate, $endDate)
                ->select('user_id')
                ->distinct()
                ->limit(8)
                ->pluck('user_id')
                ->filter()
                ->all();

            if ($activityMemberIds !== []) {
                $memberQuery->whereIn('id', $activityMemberIds);
            }
        }

        return $memberQuery
            ->orderBy('name')
            ->limit($memberId > 0 ? 1 : 5)
            ->get()
            ->map(function (User $member) use ($branch, $startDate, $endDate): array {
                $ledger = $this->memberLedger($branch, $member, $startDate, $endDate);

                return [
                    'member' => $ledger['member'],
                    'summary' => $ledger['summary'],
                    'rows' => $ledger['rows']->take(8),
                    'total_rows' => $ledger['rows']->count(),
                ];
            })
            ->filter(fn (array $ledger): bool => $ledger['total_rows'] > 0)
            ->values();
    }

    protected function ledgerSummary(Collection $rows): array
    {
        return [
            'shares_balance' => (float) ($rows->last()['SHARES_balance'] ?? 0),
            'savings_balance' => (float) ($rows->last()['SAVINGS_balance'] ?? 0),
            'deposit_balance' => (float) ($rows->last()['DEPOSIT_balance'] ?? 0),
            'authentication_balance' => (float) ($rows->last()['AUTHENTICATION_balance'] ?? 0),
            'loan_balance' => (float) ($rows->last()['loan_balance'] ?? 0),
            'loan_interest' => round((float) $rows->sum('loan_interest'), 2),
        ];
    }

    protected function openingBalances(User $member, ?Carbon $startDate): array
    {
        $balances = array_fill_keys(self::ACCOUNT_TYPES, 0.0);

        if (! $startDate) {
            return $balances;
        }

        $transactions = Transaction::query()
            ->with('account.product')
            ->where('user_id', $member->id)
            ->where('is_branch', false)
            ->whereNull('deleted_at')
            ->where('trans_date', '<', $startDate)
            ->get();

        foreach ($transactions as $transaction) {
            $accountType = strtoupper((string) ($transaction->account?->product?->type ?? ''));

            if (! array_key_exists($accountType, $balances)) {
                continue;
            }

            $amount = round((float) $transaction->amount, 2);
            $balances[$accountType] = round($balances[$accountType] + (strtolower((string) $transaction->dr_cr) === 'cr' ? $amount : -$amount), 2);
        }

        return $balances;
    }

    protected function openingLoanBalance(User $member, ?Carbon $startDate): float
    {
        if (! $startDate) {
            return 0.0;
        }

        $approvedBefore = LoanDetail::query()
            ->where('borrower_id', $member->id)
            ->where('decision_status', LoanDetail::STATUS_APPROVED)
            ->where('release_date', '<', $startDate)
            ->sum('applied_amount');

        $paidBefore = LoanPayment::query()
            ->whereHas('loan', fn (Builder $query) => $query->where('borrower_id', $member->id))
            ->whereNull('deleted_at')
            ->where('paid_at', '<', $startDate)
            ->sum('repayment_amount');

        return round(max((float) $approvedBefore - (float) $paidBefore, 0), 2);
    }

    protected function blankLedgerRow(string $date, string $particular, string $reference, array $balances, float $loanBalance): array
    {
        return [
            'date' => $date,
            'particular' => $particular,
            'reference' => $reference,
            'SHARES_debit' => 0,
            'SHARES_credit' => 0,
            'SHARES_balance' => $balances['SHARES'],
            'SAVINGS_debit' => 0,
            'SAVINGS_credit' => 0,
            'SAVINGS_balance' => $balances['SAVINGS'],
            'loan_debit' => 0,
            'loan_credit' => 0,
            'loan_balance' => $loanBalance,
            'loan_interest' => 0,
            'DEPOSIT_debit' => 0,
            'DEPOSIT_credit' => 0,
            'DEPOSIT_balance' => $balances['DEPOSIT'],
            'AUTHENTICATION_debit' => 0,
            'AUTHENTICATION_credit' => 0,
            'AUTHENTICATION_balance' => $balances['AUTHENTICATION'],
        ];
    }

    protected function accountLedgerRow(Transaction $transaction, array $balances, float $loanBalance): array
    {
        $row = $this->blankLedgerRow(
            $transaction->trans_date?->format('Y-m-d') ?: optional($transaction->created_at)->format('Y-m-d'),
            $transaction->description ?: $transaction->type ?: 'Transaction',
            $transaction->account?->account_number ?: '',
            $balances,
            $loanBalance
        );

        $accountType = strtoupper((string) ($transaction->account?->product?->type ?? ''));
        $amount = round((float) $transaction->amount, 2);

        if (in_array($accountType, self::ACCOUNT_TYPES, true)) {
            $row[$accountType . '_debit'] = strtolower((string) $transaction->dr_cr) === 'dr' ? $amount : 0;
            $row[$accountType . '_credit'] = strtolower((string) $transaction->dr_cr) === 'cr' ? $amount : 0;
            $row[$accountType . '_balance'] = $balances[$accountType];
        }

        return $row;
    }

    protected function loanLedgerRow(string $date, string $particular, float $debit, float $credit, float $balance, float $interest, array $accountBalances): array
    {
        $row = $this->blankLedgerRow($date, $particular, '', $accountBalances, $balance);
        $row['loan_debit'] = $debit;
        $row['loan_credit'] = $credit;
        $row['loan_balance'] = $balance;
        $row['loan_interest'] = $interest;

        return $row;
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
