<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\LoanPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LoanPaymentService
{
    public function __construct(
        protected LoanService $loanService,
    ) {
    }

    public function activeLoansForBranch(Branch $branch): Collection
    {
        return Loan::query()
            ->with(['borrower.detail', 'details' => function ($query): void {
                $query->where('decision_status', LoanDetail::STATUS_APPROVED)
                    ->orderByDesc('id');
            }, 'payments' => function ($query): void {
                $query->whereNull('deleted_at')->latest('paid_at')->latest('id');
            }])
            ->where('branch_id', $branch->id)
            ->whereRaw('CAST(COALESCE(balanace, 0) AS DECIMAL(15,2)) > 0')
            ->orderBy('loan_id')
            ->get();
    }

    public function repaymentListQuery(Branch $branch)
    {
        return LoanPayment::query()
            ->with(['loan.borrower.detail', 'detail', 'user'])
            ->whereHas('loan', fn ($query) => $query->where('branch_id', $branch->id))
            ->whereNull('deleted_at')
            ->latest('paid_at')
            ->latest('id');
    }

    public function repaymentContext(Loan $loan, ?string $paidAt = null, ?LoanPayment $excludePayment = null): array
    {
        $loan->loadMissing([
            'borrower.detail',
            'details' => function ($query): void {
                $query->where('decision_status', LoanDetail::STATUS_APPROVED)->orderByDesc('id');
            },
            'payments' => function ($query): void {
                $query->whereNull('deleted_at')->latest('paid_at')->latest('id');
            },
        ]);

        $detail = $loan->details->first();

        if (! $detail) {
            throw new RuntimeException('This loan does not have an approved loan record available for repayment.');
        }

        $paidDate = $paidAt ? Carbon::parse($paidAt)->startOfDay() : now()->startOfDay();
        $currentBalance = round((float) ($loan->balanace ?? 0), 2);
        $pendingCarryForwards = $loan->payments
            ->filter(function (LoanPayment $payment) use ($excludePayment): bool {
                if ($excludePayment && (int) $payment->id === (int) $excludePayment->id) {
                    return false;
                }

                return (float) ($payment->outstanding_interest ?? 0) > 0
                    && (int) ($payment->carry_forward ?? 0) === 1;
            })
            ->values();

        $carriedForwardInterest = round(
            (float) $pendingCarryForwards->sum(fn (LoanPayment $payment): float => (float) ($payment->outstanding_interest ?? 0)),
            2
        );

        return [
            'loan' => $loan,
            'detail' => $detail,
            'paid_at' => $paidDate,
            'current_balance' => $currentBalance,
            'pending_carry_forward_amount' => $carriedForwardInterest,
            'pending_carry_forwards' => $pendingCarryForwards,
            'due_cycle' => $this->dueCycleSummary($detail, $paidDate),
        ];
    }

    public function suggestedInterest(Loan $loan, float $interestRate, ?string $paidAt = null, ?LoanPayment $excludePayment = null): array
    {
        $context = $this->repaymentContext($loan, $paidAt, $excludePayment);
        $currentInterest = round($context['current_balance'] * ($interestRate / 100), 2);
        $totalSuggested = round($context['pending_carry_forward_amount'] + $currentInterest, 2);

        return [
            'interest_rate' => round($interestRate, 2),
            'current_balance' => $context['current_balance'],
            'current_interest_due' => $currentInterest,
            'carried_forward_interest' => $context['pending_carry_forward_amount'],
            'total_interest_due' => $totalSuggested,
            'due_cycle' => $context['due_cycle'],
            'pending_carry_forwards' => $context['pending_carry_forwards'],
        ];
    }

    public function create(Branch $branch, User $actor, Loan $loan, array $payload): LoanPayment
    {
        return DB::transaction(function () use ($branch, $actor, $loan, $payload): LoanPayment {
            $prepared = $this->prepareRepaymentPayload($branch, $loan, $payload);
            $branchBalanceBefore = $this->branchLedgerBalance($branch);
            $balanceAfter = round($branchBalanceBefore + $prepared['principal_applied'] + $prepared['interest_applied'], 2);

            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'paid_at' => $prepared['paid_at'],
                'late_penalties' => 0,
                'interest' => $prepared['interest_expected_total'],
                'repayment_amount' => $prepared['principal_applied'],
                'total_amount' => $prepared['principal_applied'] + $prepared['interest_applied'],
                'remarks' => $payload['remarks'] ?? null,
                'user_id' => $actor->id,
                'transaction_id' => null,
                'repayment_id' => null,
                'balance' => $prepared['loan_balance_after'],
                'interest_rate' => $prepared['interest_rate'] > 0 ? $prepared['interest_rate'] : null,
                'is_interest_paid' => $prepared['interest_applied'],
                'transation_type' => 'cr',
                'interest_paid' => $prepared['interest_applied'],
                'applied_amount' => $prepared['principal_applied'],
                'is_approved' => true,
                'is_calculated' => '1',
                'total_outstanding' => $prepared['loan_balance_before'],
                'loan_details_id' => $prepared['detail']->id,
                'interest_transaction_id' => null,
                'outstanding_interest' => $prepared['interest_remaining'],
                'release_date' => optional($prepared['detail']->release_date)->format('Y-m-d'),
                'former_carry_forward_id' => $prepared['pending_carry_forward_ids'],
                'carry_forward' => $prepared['carry_forward_flag'],
                'carry_forward_by' => $prepared['carry_forward_flag'] === 1 ? $actor->id : null,
            ]);

            $batchId = (string) Str::uuid();

            if ($prepared['principal_applied'] > 0) {
                $principalTransaction = $this->createBranchCreditTransaction(
                    $branch,
                    $actor,
                    $loan,
                    $prepared['detail'],
                    $payment,
                    $batchId,
                    $prepared['paid_at']->format('Y-m-d'),
                    $prepared['principal_applied'],
                    'Loan Repayment',
                    $payload['remarks'] ?? 'Loan repayment',
                    $branchBalanceBefore,
                    round($branchBalanceBefore + $prepared['principal_applied'], 2)
                );

                $payment->transaction_id = $principalTransaction->id;
                $branchBalanceBefore += $prepared['principal_applied'];
            }

            if ($prepared['interest_applied'] > 0) {
                $interestTransaction = $this->createBranchCreditTransaction(
                    $branch,
                    $actor,
                    $loan,
                    $prepared['detail'],
                    $payment,
                    $batchId,
                    $prepared['paid_at']->format('Y-m-d'),
                    $prepared['interest_applied'],
                    'Loan Interest Repayment',
                    $payload['remarks'] ?? 'Loan interest repayment',
                    $branchBalanceBefore,
                    round($branchBalanceBefore + $prepared['interest_applied'], 2)
                );

                $payment->interest_transaction_id = $interestTransaction->id;
            }

            $payment->save();

            $this->markPriorCarryForwardsAsCalculated($prepared['pending_carry_forwards']);
            $this->loanService->syncLoanAggregate($loan->fresh());

            return $payment->fresh([
                'loan.borrower.detail',
                'detail',
                'user',
                'principalTransaction',
                'interestTransaction',
            ]);
        });
    }

    public function update(Branch $branch, User $actor, LoanPayment $payment, array $payload): LoanPayment
    {
        return DB::transaction(function () use ($branch, $actor, $payment, $payload): LoanPayment {
            $payment->loadMissing(['loan.borrower.detail', 'detail', 'principalTransaction', 'interestTransaction']);
            $loan = $payment->loan;

            if (! $loan) {
                throw new RuntimeException('This repayment is missing its linked loan.');
            }

            $this->restorePriorCarryForwards($payment->former_carry_forward_id);
            $loan->refresh();

            $prepared = $this->prepareRepaymentPayload($branch, $loan, $payload, $payment);
            $branchBalanceBefore = $this->branchLedgerBalance($branch, array_filter([
                $payment->transaction_id,
                $payment->interest_transaction_id,
            ]));
            $branchBalanceAfter = round($branchBalanceBefore + $prepared['principal_applied'] + $prepared['interest_applied'], 2);

            if ($branchBalanceAfter < 0) {
                throw new RuntimeException('Updating this repayment would make the society balance invalid.');
            }

            $payment->update([
                'paid_at' => $prepared['paid_at'],
                'interest' => $prepared['interest_expected_total'],
                'repayment_amount' => $prepared['principal_applied'],
                'total_amount' => $prepared['principal_applied'] + $prepared['interest_applied'],
                'remarks' => $payload['remarks'] ?? null,
                'user_id' => $actor->id,
                'balance' => $prepared['loan_balance_after'],
                'interest_rate' => $prepared['interest_rate'] > 0 ? $prepared['interest_rate'] : null,
                'is_interest_paid' => $prepared['interest_applied'],
                'interest_paid' => $prepared['interest_applied'],
                'applied_amount' => $prepared['principal_applied'],
                'total_outstanding' => $prepared['loan_balance_before'],
                'loan_details_id' => $prepared['detail']->id,
                'outstanding_interest' => $prepared['interest_remaining'],
                'release_date' => optional($prepared['detail']->release_date)->format('Y-m-d'),
                'former_carry_forward_id' => $prepared['pending_carry_forward_ids'],
                'carry_forward' => $prepared['carry_forward_flag'],
                'carry_forward_by' => $prepared['carry_forward_flag'] === 1 ? $actor->id : null,
            ]);

            $batchId = $payment->principalTransaction?->batch_id
                ?: $payment->interestTransaction?->batch_id
                ?: (string) Str::uuid();

            $principalTransaction = $this->upsertRepaymentTransaction(
                $branch,
                $actor,
                $loan,
                $prepared['detail'],
                $payment,
                $payment->principalTransaction,
                $batchId,
                $prepared['paid_at']->format('Y-m-d'),
                $prepared['principal_applied'],
                'Loan Repayment',
                $payload['remarks'] ?? 'Loan repayment',
                $branchBalanceBefore,
                round($branchBalanceBefore + $prepared['principal_applied'], 2)
            );

            $branchBalanceBefore += $prepared['principal_applied'];

            $interestTransaction = $this->upsertRepaymentTransaction(
                $branch,
                $actor,
                $loan,
                $prepared['detail'],
                $payment,
                $payment->interestTransaction,
                $batchId,
                $prepared['paid_at']->format('Y-m-d'),
                $prepared['interest_applied'],
                'Loan Interest Repayment',
                $payload['remarks'] ?? 'Loan interest repayment',
                $branchBalanceBefore,
                round($branchBalanceBefore + $prepared['interest_applied'], 2)
            );

            $payment->transaction_id = $principalTransaction?->id;
            $payment->interest_transaction_id = $interestTransaction?->id;
            $payment->save();

            $this->markPriorCarryForwardsAsCalculated($prepared['pending_carry_forwards']);
            $this->loanService->syncLoanAggregate($loan->fresh());

            return $payment->fresh([
                'loan.borrower.detail',
                'detail',
                'user',
                'principalTransaction',
                'interestTransaction',
            ]);
        });
    }

    public function delete(User $actor, LoanPayment $payment): void
    {
        DB::transaction(function () use ($actor, $payment): void {
            $payment->loadMissing(['loan', 'principalTransaction', 'interestTransaction']);

            $loan = $payment->loan;
            if (! $loan) {
                throw new RuntimeException('This repayment is missing its linked loan.');
            }

            $branch = Branch::query()->find($loan->branch_id);
            if (! $branch) {
                throw new RuntimeException('The linked branch for this repayment could not be found.');
            }

            $balanceWithoutRepaymentCredits = $this->branchLedgerBalance($branch, array_filter([
                $payment->transaction_id,
                $payment->interest_transaction_id,
            ]));

            if ($balanceWithoutRepaymentCredits < 0) {
                throw new RuntimeException('Deleting this repayment would make the society balance invalid.');
            }

            foreach ([$payment->principalTransaction, $payment->interestTransaction] as $transaction) {
                if (! $transaction) {
                    continue;
                }

                $transaction->update([
                    'updated_user_id' => $actor->id,
                    'loan_repayment_id' => null,
                ]);
                $transaction->delete();
            }

            $this->restorePriorCarryForwards($payment->former_carry_forward_id);

            $payment->update(['user_id' => $actor->id]);
            $payment->delete();

            $this->loanService->syncLoanAggregate($loan->fresh());
        });
    }

    public function dueCycleSummary(LoanDetail $detail, Carbon $paidDate): array
    {
        $releaseDate = optional($detail->release_date)?->copy()?->startOfDay();

        if (! $releaseDate) {
            return [
                'is_due' => false,
                'label' => 'Release date is missing.',
                'next_due_date' => null,
            ];
        }

        if ($paidDate->lt($releaseDate)) {
            return [
                'is_due' => false,
                'label' => 'This repayment date is earlier than the loan release date.',
                'next_due_date' => $releaseDate->format('Y-m-d'),
            ];
        }

        $interval = $detail->interest_week_interval;
        $isDue = false;
        $nextDueDate = null;

        if (in_array($interval, ['weekly', 'every-2-weeks', 'every-3-weeks'], true)) {
            $days = match ($interval) {
                'weekly' => 7,
                'every-2-weeks' => 14,
                'every-3-weeks' => 21,
            };

            $diff = $releaseDate->diffInDays($paidDate);
            $isDue = $diff > 0 && $diff % $days === 0;
            $cyclesCompleted = intdiv($diff, $days);
            $nextDueDate = $releaseDate->copy()->addDays(($cyclesCompleted + 1) * $days)->format('Y-m-d');
        } elseif ($interval === 'monthly') {
            $cursor = $releaseDate->copy();
            $matched = false;

            while ($cursor->lte($paidDate)) {
                if ($cursor->equalTo($paidDate)) {
                    $matched = true;
                    break;
                }

                $cursor->addMonthNoOverflow();
            }

            $isDue = $matched;
            $nextDueDate = $matched
                ? $paidDate->copy()->addMonthNoOverflow()->format('Y-m-d')
                : $cursor->format('Y-m-d');
        }

        return [
            'is_due' => $isDue,
            'label' => $isDue
                ? 'This repayment date falls on a scheduled interest interval.'
                : 'This repayment date is outside the scheduled interest interval. Interest can still be paid later.',
            'next_due_date' => $nextDueDate,
        ];
    }

    protected function prepareRepaymentPayload(Branch $branch, Loan $loan, array $payload, ?LoanPayment $excludePayment = null): array
    {
        $context = $this->repaymentContext($loan, $payload['paid_at'], $excludePayment);
        $interestRate = round((float) ($payload['interest_rate'] ?? 0), 2);
        $interestMeta = $this->suggestedInterest($loan, $interestRate, $payload['paid_at'], $excludePayment);
        $enteredPrincipal = round((float) ($payload['repayment_amount'] ?? 0), 2);
        $enteredInterest = round((float) ($payload['interest_paid'] ?? 0), 2);
        $principalApplied = $enteredPrincipal;
        $interestExpectedTotal = $interestMeta['total_interest_due'];
        $interestApplied = min($enteredInterest, $interestExpectedTotal);

        if ($enteredInterest > $interestExpectedTotal) {
            $principalApplied += round($enteredInterest - $interestExpectedTotal, 2);
        }

        if ($principalApplied <= 0 && $interestApplied <= 0) {
            throw new RuntimeException('Enter a repayment amount, an interest payment, or both.');
        }

        if ($principalApplied > $context['current_balance']) {
            throw new RuntimeException('The repayment amount cannot be greater than the current amount owed on this loan.');
        }

        $interestRemaining = round(max($interestExpectedTotal - $interestApplied, 0), 2);
        $carryForwardFlag = $interestRemaining > 0
            ? ((bool) ($payload['carry_forward_remaining'] ?? false) ? 1 : 0)
            : 0;

        return [
            'detail' => $context['detail'],
            'paid_at' => $context['paid_at'],
            'loan_balance_before' => $context['current_balance'],
            'loan_balance_after' => round(max($context['current_balance'] - $principalApplied, 0), 2),
            'interest_rate' => $interestRate,
            'current_interest_due' => $interestMeta['current_interest_due'],
            'interest_expected_total' => $interestExpectedTotal,
            'interest_applied' => $interestApplied,
            'interest_remaining' => $interestRemaining,
            'principal_applied' => $principalApplied,
            'carry_forward_flag' => $carryForwardFlag,
            'pending_carry_forwards' => $interestMeta['pending_carry_forwards'],
            'pending_carry_forward_ids' => $interestMeta['pending_carry_forwards']->pluck('id')->implode(','),
            'due_cycle' => $interestMeta['due_cycle'],
        ];
    }

    protected function createBranchCreditTransaction(
        Branch $branch,
        User $actor,
        Loan $loan,
        LoanDetail $detail,
        LoanPayment $payment,
        string $batchId,
        string $paidAt,
        float $amount,
        string $type,
        string $description,
        float $balanceBefore,
        float $balanceAfter
    ): Transaction {
        return Transaction::create([
            'user_id' => $branch->branch_user_id,
            'trans_date' => $paidAt,
            'savings_account_id' => null,
            'charge' => 0,
            'amount' => $amount,
            'gateway_amount' => 0,
            'dr_cr' => 'cr',
            'type' => $type,
            'attachment' => null,
            'method' => 'Manual',
            'status' => 2,
            'note' => null,
            'description' => $description,
            'loan_id' => $loan->id,
            'ref_id' => null,
            'parent_id' => null,
            'gateway_id' => null,
            'created_user_id' => $actor->id,
            'updated_user_id' => null,
            'branch_id' => $branch->id,
            'transaction_details' => [
                'scope' => 'loan-repayment',
                'loan_id' => $loan->loan_id,
                'loan_detail_id' => $detail->id,
                'loan_repayment_id' => $payment->id,
                'borrower_id' => $loan->borrower_id,
                'borrower_name' => $loan->borrower?->name,
                'member_no' => $loan->borrower?->detail?->member_no ?: $loan->borrower?->member_no,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ],
            'tracking_id' => 'loan_repayment',
            'detail_id' => (string) $detail->id,
            'is_branch' => 1,
            'loan_details_id' => $detail->id,
            'loan_repayment_id' => $payment->id,
            'batch_id' => $batchId,
        ]);
    }

    protected function upsertRepaymentTransaction(
        Branch $branch,
        User $actor,
        Loan $loan,
        LoanDetail $detail,
        LoanPayment $payment,
        ?Transaction $transaction,
        string $batchId,
        string $paidAt,
        float $amount,
        string $type,
        string $description,
        float $balanceBefore,
        float $balanceAfter
    ): ?Transaction {
        if ($amount <= 0) {
            if ($transaction) {
                $transaction->update([
                    'updated_user_id' => $actor->id,
                    'loan_repayment_id' => null,
                ]);
                $transaction->delete();
            }

            return null;
        }

        $attributes = [
            'user_id' => $branch->branch_user_id,
            'trans_date' => $paidAt,
            'amount' => $amount,
            'dr_cr' => 'cr',
            'type' => $type,
            'description' => $description,
            'updated_user_id' => $actor->id,
            'branch_id' => $branch->id,
            'transaction_details' => [
                'scope' => 'loan-repayment',
                'loan_id' => $loan->loan_id,
                'loan_detail_id' => $detail->id,
                'loan_repayment_id' => $payment->id,
                'borrower_id' => $loan->borrower_id,
                'borrower_name' => $loan->borrower?->name,
                'member_no' => $loan->borrower?->detail?->member_no ?: $loan->borrower?->member_no,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ],
            'tracking_id' => 'loan_repayment',
            'detail_id' => (string) $detail->id,
            'is_branch' => 1,
            'loan_details_id' => $detail->id,
            'loan_repayment_id' => $payment->id,
            'batch_id' => $batchId,
        ];

        if ($transaction) {
            $transaction->update($attributes);

            return $transaction->fresh();
        }

        return Transaction::create(array_merge($attributes, [
            'savings_account_id' => null,
            'charge' => 0,
            'gateway_amount' => 0,
            'attachment' => null,
            'method' => 'Manual',
            'status' => 2,
            'note' => null,
            'loan_id' => $loan->id,
            'ref_id' => null,
            'parent_id' => null,
            'gateway_id' => null,
            'created_user_id' => $actor->id,
        ]));
    }

    protected function markPriorCarryForwardsAsCalculated(Collection $payments): void
    {
        if ($payments->isEmpty()) {
            return;
        }

        $payments->each(function (LoanPayment $payment): void {
            $payment->update(['carry_forward' => 2]);
        });
    }

    protected function restorePriorCarryForwards(?string $ids): void
    {
        if (! $ids) {
            return;
        }

        $paymentIds = collect(explode(',', $ids))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($paymentIds->isEmpty()) {
            return;
        }

        LoanPayment::query()
            ->whereIn('id', $paymentIds->all())
            ->whereNull('deleted_at')
            ->whereRaw('CAST(COALESCE(outstanding_interest, 0) AS DECIMAL(15,2)) > 0')
            ->update(['carry_forward' => 1]);
    }

    protected function branchLedgerBalance(Branch $branch, array $excludeTransactionIds = []): float
    {
        $query = Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at');

        if ($excludeTransactionIds !== []) {
            $query->whereNotIn('id', $excludeTransactionIds);
        }

        return round(
            (float) $query->sum(DB::raw("case when lower(dr_cr) = 'cr' then amount else -amount end")),
            2
        );
    }
}
