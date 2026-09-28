<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MemberStatementService
{
    public function query(User $member): Builder
    {
        $membershipId = $member->relationLoaded('activeMembership') ? $member->activeMembership?->id : null;
        $branchId = $member->relationLoaded('activeMembership') ? $member->activeMembership?->branch_id : $member->branch_id;
        $loanIds = Loan::query()
            ->where('borrower_id', $member->id)
            ->when($membershipId, fn (Builder $query) => $query->where('member_branch_membership_id', $membershipId))
            ->where('branch_id', $branchId)
            ->select('id');

        return Transaction::query()
            ->with('account.product')
            ->where('branch_id', $branchId)
            ->where(function (Builder $query) use ($member, $loanIds, $membershipId): void {
                $query->where(function (Builder $memberQuery) use ($member, $membershipId): void {
                    $memberQuery->where('user_id', $member->id)->where('is_branch', false)
                        ->when($membershipId, function (Builder $scope) use ($membershipId): void {
                            $scope->where(function (Builder $membershipQuery) use ($membershipId): void {
                                $membershipQuery->where('member_branch_membership_id', $membershipId)
                                    ->orWhereHas('account', fn (Builder $account) => $account->where('member_branch_membership_id', $membershipId));
                            });
                        });
                })->orWhere(function (Builder $loanQuery) use ($loanIds): void {
                    $loanQuery->where('is_branch', true)
                        ->whereIn('loan_id', $loanIds)
                        ->whereIn('tracking_id', ['loan', 'loan_repayment']);
                });
            })
            ->orderBy('trans_date')
            ->orderBy('id');
    }

    public function period(array $filters): array
    {
        $period = $filters['period'] ?? 'full';
        $start = $end = null;

        if ($period === 'annual') {
            $year = (int) ($filters['year'] ?? now()->year);
            $start = Carbon::create($year, 1, 1)->toDateString();
            $end = Carbon::create($year, 12, 31)->toDateString();
        } elseif ($period === 'monthly') {
            $month = Carbon::createFromFormat('!Y-m', $filters['month'] ?? now()->format('Y-m'));
            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();
        } elseif ($period === 'custom') {
            $start = $filters['start_date'];
            $end = $filters['end_date'];
        }

        return [$start, $end];
    }

    public function rows(User $member, array $filters): array
    {
        [$start, $end] = $this->period($filters);
        $query = $this->query($member);
        $opening = 0;

        if ($start) {
            $opening = (float) $this->query($member)
                ->whereDate('trans_date', '<', $start)
                ->get()
                ->sum(fn (Transaction $transaction): float => $this->signedAmount($transaction));
            $query->whereDate('trans_date', '>=', $start);
        }
        if ($end) {
            $query->whereDate('trans_date', '<=', $end);
        }

        $balance = $opening;
        $rows = $query->get()->map(function (Transaction $transaction) use (&$balance): array {
            $balance += $this->signedAmount($transaction);

            return ['transaction' => $transaction, 'balance' => round($balance, 2)];
        });

        return ['rows' => $rows, 'opening' => round($opening, 2), 'closing' => round($balance, 2), 'start' => $start, 'end' => $end];
    }

    public function memberDirection(Transaction $transaction): string
    {
        if ($this->isInterestPayment($transaction)) {
            return 'PAID';
        }

        $credit = strtolower((string) $transaction->dr_cr) === 'cr';
        if ($transaction->is_branch && in_array($transaction->tracking_id, ['loan', 'loan_repayment'], true)) {
            $credit = ! $credit;
        }

        return $credit ? 'CR' : 'DR';
    }

    private function signedAmount(Transaction $transaction): float
    {
        if ($this->isInterestPayment($transaction)) {
            return 0.0;
        }

        return $this->memberDirection($transaction) === 'CR'
            ? (float) $transaction->amount
            : -(float) $transaction->amount;
    }

    private function isInterestPayment(Transaction $transaction): bool
    {
        return $transaction->is_branch
            && $transaction->tracking_id === 'loan_repayment'
            && str_contains(mb_strtolower((string) $transaction->type), 'interest');
    }
}
