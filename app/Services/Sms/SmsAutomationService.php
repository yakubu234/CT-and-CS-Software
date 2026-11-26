<?php

namespace App\Services\Sms;

use App\Models\Branch;
use App\Models\LoanDetail;
use App\Models\LoanPayment;
use App\Models\MemberBranchMembership;
use App\Models\SmsAutomationRule;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SmsAutomationService
{
    public function __construct(
        protected SmsTemplateRenderer $renderer,
        protected SmsPhoneNormalizer $phoneNormalizer,
        protected SmsDispatchService $dispatchService,
        protected SmsSettingsService $settings,
        protected SmsContextBuilder $contexts,
    ) {
    }

    /**
     * Credit/debit alerts for one saved transaction. The whole posting it belongs to is
     * alerted together, so calling this for each entry of a posting sends one alert.
     */
    public function handleTransaction(Transaction $transaction): void
    {
        if ($transaction->is_branch || ! $transaction->user_id) {
            return;
        }

        $transactions = $transaction->batch_id
            ? Transaction::query()->with(['user.detail', 'account.product'])
                ->where('batch_id', $transaction->batch_id)
                ->where('is_branch', 0)
                ->whereNull('deleted_at')
                ->get()
            : collect([$transaction->loadMissing(['user.detail', 'account.product'])]);

        $this->handleTransactionBatch($transactions);
    }

    /**
     * One alert per member, per posting, per direction: everything a member was credited
     * in one save becomes a single Credit Alert (likewise for debits). Only the members
     * in the posting are messaged, and each posting can only ever alert once.
     */
    public function handleTransactionBatch(Collection $transactions): void
    {
        $groups = $transactions
            ->filter(fn (Transaction $transaction) => ! $transaction->is_branch && $transaction->user_id)
            ->groupBy(fn (Transaction $transaction) => implode('|', [
                $transaction->user_id,
                $transaction->batch_id ?: 'tx' . $transaction->id,
                strtolower((string) $transaction->dr_cr) === 'dr' ? 'dr' : 'cr',
            ]));

        foreach ($groups as $group) {
            /** @var Transaction $first */
            $first = $group->first();
            $direction = strtolower((string) $first->dr_cr) === 'dr' ? 'dr' : 'cr';
            $event = $direction === 'dr' ? SmsAutomationRule::EVENT_TRANSACTION_DEBIT : SmsAutomationRule::EVENT_TRANSACTION_CREDIT;
            $user = $first->user;

            if (! $user) {
                continue;
            }

            $rules = $this->activeRules($event, $first->branch_id);
            if ($rules->isEmpty()) {
                continue;
            }

            $branch = $first->branch_id ? Branch::query()->find($first->branch_id) : null;
            $membership = $first->member_branch_membership_id
                ? MemberBranchMembership::query()->with('branch')->find($first->member_branch_membership_id)
                : $this->contexts->membershipFor($user, $first->branch_id);

            $context = $this->contexts->transactionBatch(
                $user,
                $branch,
                $membership,
                $direction,
                $group->map(fn (Transaction $transaction) => [
                    'type' => $transaction->account?->product?->type ?: data_get($transaction->transaction_details, 'account_type'),
                    'amount' => $transaction->amount,
                    'account_number' => $transaction->account?->account_number ?: data_get($transaction->transaction_details, 'account_number'),
                ])->values()->all(),
                $first->trans_date
            );

            $postingKey = $first->batch_id ?: 'tx' . $first->id;
            foreach ($rules as $rule) {
                $this->createAndDispatch(
                    $rule,
                    $user,
                    $branch,
                    $context,
                    $first,
                    'automation:' . $rule->id . ':posting:' . $postingKey . ':' . $user->id . ':' . $direction
                );
            }
        }
    }

    public function handleLoanRepayment(LoanPayment $payment): void
    {
        $payment->loadMissing(['loan.borrower.detail', 'loan.branch', 'loan.membership.branch']);
        $borrower = $payment->loan?->borrower;

        if (! $borrower) {
            return;
        }

        $rules = $this->activeRules(SmsAutomationRule::EVENT_LOAN_REPAYMENT, $payment->loan->branch_id);
        if ($rules->isEmpty()) {
            return;
        }

        $context = $this->contexts->loanRepayment($payment);

        foreach ($rules as $rule) {
            $this->createAndDispatch(
                $rule,
                $borrower,
                $payment->loan->branch,
                $context,
                $payment,
                'automation:' . $rule->id . ':loan_repayment:' . $payment->id
            );
        }
    }

    public function handleLoanApproved(LoanDetail $detail): void
    {
        $detail->loadMissing(['borrower.detail', 'borrower.branch', 'loan']);
        $borrower = $detail->borrower;

        if (! $borrower) {
            return;
        }

        $branch = $detail->branch_id ? Branch::query()->find($detail->branch_id) : null;
        $context = [
            ...$this->contexts->common($borrower, $branch, $this->contexts->membershipFor($borrower, $detail->branch_id)),
            'loan_id' => $detail->loan?->loan_id,
            'loan_amount' => $this->contexts->money($detail->applied_amount),
            'release_date' => $this->contexts->date($detail->release_date),
            'due_date' => $this->contexts->date($detail->due_date),
        ];

        foreach ($this->activeRules(SmsAutomationRule::EVENT_LOAN_APPROVED, $detail->branch_id) as $rule) {
            $this->createAndDispatch($rule, $borrower, $branch, $context, $detail, 'automation:' . $rule->id . ':loan:' . $detail->id);
        }
    }

    public function processDateBasedAutomations(?Carbon $now = null): int
    {
        $now ??= now();
        $processed = 0;

        $rules = SmsAutomationRule::query()
            ->with(['template', 'branch'])
            ->where('status', true)
            ->whereIn('event', [
                SmsAutomationRule::EVENT_BIRTHDAY,
                SmsAutomationRule::EVENT_MONTHLY_STATEMENT,
            ])
            ->get();

        foreach ($rules as $rule) {
            if (! $this->isTimeDue($rule, $now)) {
                continue;
            }

            // Once a rule has fully run today, skip it for the rest of the day instead of
            // reloading every member each minute. The key includes the rule's settings, so
            // editing the time or day lets it run again.
            $doneKey = 'sms-automation-done:' . $rule->id . ':' . md5($rule->event . '|' . $rule->schedule_time . '|' . $rule->day_of_month . '|' . $rule->branch_id) . ':' . $now->format('Y-m-d');

            if (Cache::has($doneKey)) {
                continue;
            }

            if ($rule->event === SmsAutomationRule::EVENT_BIRTHDAY) {
                $processed += $this->processBirthdayRule($rule, $now);
            } elseif ($rule->event === SmsAutomationRule::EVENT_MONTHLY_STATEMENT) {
                $processed += $this->processMonthlyStatementRule($rule, $now);
            }

            Cache::put($doneKey, true, $now->copy()->endOfDay()->addHour());
        }

        return $processed;
    }

    /**
     * Send (or release) a message that a Manual rule left waiting for staff.
     */
    public function releaseAwaiting(SmsMessage $message): SmsMessage
    {
        if ($message->status !== SmsMessage::STATUS_AWAITING) {
            return $message;
        }

        $message->update(['status' => SmsMessage::STATUS_PENDING]);

        return $this->dispatchService->dispatch($message);
    }

    protected function processBirthdayRule(SmsAutomationRule $rule, Carbon $now): int
    {
        $count = 0;

        // Celebrants come straight from Member Management: active customer members whose
        // date of birth falls on today's day and month (birth year ignored). 29 Feb
        // birthdays are celebrated on 28 Feb in non-leap years.
        $birthDays = [[$now->month, $now->day]];
        if ($now->month === 2 && $now->day === 28 && ! $now->isLeapYear()) {
            $birthDays[] = [2, 29];
        }

        $membershipScope = fn ($query) => $query
            ->where('status', true)
            ->when($rule->branch_id, fn ($inner) => $inner->where('branch_id', $rule->branch_id));

        $query = $this->eligibleMembers($membershipScope)
            ->whereHas('detail', function ($builder) use ($birthDays): void {
                $builder->where(function ($dates) use ($birthDays): void {
                    foreach ($birthDays as [$month, $day]) {
                        $dates->orWhere(fn ($date) => $date
                            ->whereMonth('date_of_birth', $month)
                            ->whereDay('date_of_birth', $day));
                    }
                });
            });

        $birthdayRuleIds = SmsAutomationRule::query()
            ->where('event', SmsAutomationRule::EVENT_BIRTHDAY)
            ->pluck('id');

        foreach ($query->get() as $user) {
            // One greeting per member per day, even if they are in several societies
            // that each have their own birthday rule.
            $alreadyGreeted = SmsMessage::query()
                ->where('user_id', $user->id)
                ->whereIn('automation_rule_id', $birthdayRuleIds)
                ->where('reference_key', 'like', 'automation:%:birthday:' . $user->id . ':' . $now->format('Y-m-d'))
                ->exists();

            if ($alreadyGreeted) {
                continue;
            }

            $membership = $user->branchMemberships->sortByDesc('is_primary')->first();
            $branch = $rule->branch ?: $membership?->branch ?: $user->branch;

            $sent = $this->createAndDispatch(
                $rule,
                $user,
                $branch,
                [
                    ...$this->contexts->common($user, $branch, $membership),
                    'birth_day' => optional($user->detail?->date_of_birth)->format('d M'),
                ],
                $user,
                'automation:' . $rule->id . ':birthday:' . $user->id . ':' . $now->format('Y-m-d')
            );

            $count += $sent ? 1 : 0;
        }

        return $count;
    }

    /**
     * Monthly statement / balance alert: one message per active society membership, with
     * that society's four account balances.
     */
    protected function processMonthlyStatementRule(SmsAutomationRule $rule, Carbon $now): int
    {
        if ($now->day !== $rule->effectiveDayOfMonth($now)) {
            return 0;
        }

        $count = 0;
        $membershipScope = fn ($query) => $query
            ->where('status', true)
            ->when($rule->branch_id, fn ($inner) => $inner->where('branch_id', $rule->branch_id));

        // The statement covers the last complete month before the send date, so every
        // figure is a true month-end figure (e.g. sent on 1 Nov -> October statement).
        $statementMonth = $now->copy()->startOfMonth()->subMonthNoOverflow();

        foreach ($this->eligibleMembers($membershipScope)->get() as $user) {
            foreach ($user->branchMemberships as $membership) {
                $sent = $this->createAndDispatch(
                    $rule,
                    $user,
                    $membership->branch,
                    $this->contexts->monthlyStatement($user, $membership->branch, $membership, $statementMonth),
                    $user,
                    'automation:' . $rule->id . ':monthly:' . $user->id . ':' . $membership->id . ':' . $now->format('Y-m')
                );

                $count += $sent ? 1 : 0;
            }
        }

        return $count;
    }

    /** Active customer members, as listed in Member Management, with matching memberships loaded. */
    protected function eligibleMembers(\Closure $membershipScope)
    {
        return User::query()
            ->with(['detail', 'branch', 'branchMemberships' => fn ($query) => $membershipScope($query)->with('branch')])
            ->where('branch_account', false)
            ->where('user_type', 'customer')
            ->whereNull('deleted_at')
            ->whereHas('branchMemberships', $membershipScope);
    }

    protected function activeRules(string $event, mixed $branchId): Collection
    {
        return SmsAutomationRule::query()
            ->with('template')
            ->where('status', true)
            ->where('event', $event)
            ->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id')
                    ->when($branchId, fn ($inner) => $inner->orWhere('branch_id', $branchId));
            })
            ->get();
    }

    /**
     * Render and record one message. Automated rules send straight away; Manual rules
     * leave it "awaiting" in SMS Logs until staff send it. Returns false if this exact
     * message (same reference key) was already created, so nothing is ever sent twice.
     */
    protected function createAndDispatch(
        SmsAutomationRule $rule,
        User $user,
        ?Branch $branch,
        array $context,
        mixed $related,
        string $referenceKey
    ): bool {
        if (SmsMessage::query()->where('reference_key', $referenceKey)->exists()) {
            return false;
        }

        $message = SmsMessage::create([
            'automation_rule_id' => $rule->id,
            'user_id' => $user->id,
            'branch_id' => $branch?->id,
            'phone' => $this->phoneNormalizer->normalize($user->detail?->mobile),
            'recipient_name' => $user->name,
            'message' => $this->renderer->render($rule->template?->body ?? '', $context),
            'provider' => $this->settings->activeProvider(),
            'sender_id' => $this->settings->senderId(),
            'status' => $rule->isManual() ? SmsMessage::STATUS_AWAITING : SmsMessage::STATUS_PENDING,
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->id,
            'reference_key' => $referenceKey,
            'scheduled_for' => now(),
            'meta' => ['send_mode' => $rule->sendMode()],
        ]);

        if (! $rule->isManual()) {
            $this->dispatchService->dispatch($message);
        }

        return true;
    }

    protected function isTimeDue(SmsAutomationRule $rule, Carbon $now): bool
    {
        if (! $rule->schedule_time) {
            return true;
        }

        // Due from the chosen time onward for the rest of the day, so a run that lands a
        // minute late (or a missed run) still sends. Each member is only sent once per
        // day/month because the per-member reference keys are checked before sending.
        return $now->format('H:i') >= substr($rule->schedule_time, 0, 5);
    }
}
