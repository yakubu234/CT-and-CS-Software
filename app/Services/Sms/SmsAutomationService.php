<?php

namespace App\Services\Sms;

use App\Models\Branch;
use App\Models\LoanDetail;
use App\Models\SmsAutomationRule;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SmsAutomationService
{
    public function __construct(
        protected SmsTemplateRenderer $renderer,
        protected SmsPhoneNormalizer $phoneNormalizer,
        protected SmsDispatchService $dispatchService,
        protected SmsSettingsService $settings,
    ) {
    }

    public function handleTransaction(Transaction $transaction): void
    {
        if ($transaction->is_branch || ! $transaction->user_id) {
            return;
        }

        $event = strtolower((string) $transaction->dr_cr) === 'cr'
            ? SmsAutomationRule::EVENT_TRANSACTION_CREDIT
            : SmsAutomationRule::EVENT_TRANSACTION_DEBIT;

        $transaction->loadMissing(['user.detail', 'account.product']);

        $rules = SmsAutomationRule::query()
            ->with('template')
            ->where('status', true)
            ->where('event', $event)
            ->where(function ($query) use ($transaction): void {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $transaction->branch_id);
            })
            ->get();

        foreach ($rules as $rule) {
            $user = $transaction->user;

            if (! $user) {
                continue;
            }

            $this->createAndDispatch(
                $rule,
                $user,
                $transaction->branch_id ? Branch::query()->find($transaction->branch_id) : null,
                $this->phoneNormalizer->normalize($user->detail?->mobile),
                $this->renderer->render($rule->template?->body ?? '', [
                    'member_name' => $user->name,
                    'member_no' => $user->detail?->member_no ?: $user->member_no,
                    'branch_name' => $user->branch?->name,
                    'amount' => number_format((float) $transaction->amount, 2),
                    'transaction_date' => optional($transaction->trans_date)->format('d M Y'),
                    'transaction_type' => strtoupper((string) $transaction->dr_cr),
                    'account_number' => $transaction->account?->account_number,
                    'account_type' => $transaction->account?->product?->type,
                    'current_balance' => number_format((float) data_get($transaction->transaction_details, 'balance_after', 0), 2),
                    'society_name' => 'Oreoluwapo CT&CS',
                ]),
                $transaction,
                'automation:' . $rule->id . ':transaction:' . $transaction->id
            );
        }
    }

    public function handleLoanApproved(LoanDetail $detail): void
    {
        $detail->loadMissing(['borrower.detail', 'borrower.branch', 'loan']);

        $rules = SmsAutomationRule::query()
            ->with('template')
            ->where('status', true)
            ->where('event', SmsAutomationRule::EVENT_LOAN_APPROVED)
            ->where(function ($query) use ($detail): void {
                $query->whereNull('branch_id')
                    ->orWhere('branch_id', $detail->branch_id);
            })
            ->get();

        foreach ($rules as $rule) {
            $borrower = $detail->borrower;

            if (! $borrower) {
                continue;
            }

            $this->createAndDispatch(
                $rule,
                $borrower,
                $detail->branch_id ? Branch::query()->find($detail->branch_id) : null,
                $this->phoneNormalizer->normalize($borrower->detail?->mobile),
                $this->renderer->render($rule->template?->body ?? '', [
                    'member_name' => $borrower->name,
                    'member_no' => $borrower->detail?->member_no ?: $borrower->member_no,
                    'branch_name' => $borrower->branch?->name,
                    'loan_id' => $detail->loan?->loan_id,
                    'loan_amount' => number_format((float) $detail->applied_amount, 2),
                    'release_date' => optional($detail->release_date)->format('d M Y'),
                    'due_date' => optional($detail->due_date)->format('d M Y'),
                    'society_name' => 'Oreoluwapo CT&CS',
                ]),
                $detail,
                'automation:' . $rule->id . ':loan:' . $detail->id
            );
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

            if ($rule->event === SmsAutomationRule::EVENT_BIRTHDAY) {
                $processed += $this->processBirthdayRule($rule, $now);
                continue;
            }

            if ($rule->event === SmsAutomationRule::EVENT_MONTHLY_STATEMENT) {
                $processed += $this->processMonthlyStatementRule($rule, $now);
            }
        }

        return $processed;
    }

    protected function processBirthdayRule(SmsAutomationRule $rule, Carbon $now): int
    {
        $count = 0;

        $query = User::query()
            ->with(['detail', 'branch', 'savingsAccounts.product'])
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->whereHas('detail', function ($builder) use ($now): void {
                $builder
                    ->whereMonth('date_of_birth', $now->month)
                    ->whereDay('date_of_birth', $now->day);
            });

        if ($rule->branch_id) {
            $query->where('branch_id', $rule->branch_id);
        }

        foreach ($query->get() as $user) {
            $referenceKey = 'automation:' . $rule->id . ':birthday:' . $user->id . ':' . $now->format('Y-m-d');

            if (SmsMessage::query()->where('reference_key', $referenceKey)->exists()) {
                continue;
            }

            $this->createAndDispatch(
                $rule,
                $user,
                $rule->branch,
                $this->phoneNormalizer->normalize($user->detail?->mobile),
                $this->renderer->render($rule->template?->body ?? '', [
                    'member_name' => $user->name,
                    'member_no' => $user->detail?->member_no ?: $user->member_no,
                    'branch_name' => $user->branch?->name,
                    'birth_day' => optional($user->detail?->date_of_birth)->format('d M'),
                    'society_name' => 'Oreoluwapo CT&CS',
                ]),
                $user,
                $referenceKey
            );

            $count++;
        }

        return $count;
    }

    protected function processMonthlyStatementRule(SmsAutomationRule $rule, Carbon $now): int
    {
        $dayOfMonth = (int) ($rule->day_of_month ?: 1);

        if ($now->day !== $dayOfMonth) {
            return 0;
        }

        $count = 0;

        $query = User::query()
            ->with(['detail', 'branch', 'savingsAccounts.product'])
            ->where('branch_account', false)
            ->whereNull('deleted_at');

        if ($rule->branch_id) {
            $query->where('branch_id', $rule->branch_id);
        }

        foreach ($query->get() as $user) {
            $referenceKey = 'automation:' . $rule->id . ':monthly:' . $user->id . ':' . $now->format('Y-m');

            if (SmsMessage::query()->where('reference_key', $referenceKey)->exists()) {
                continue;
            }

            $statementSummary = $this->statementSummary(
                $user->savingsAccounts
                    ->where('is_branch_acount', false)
                    ->where('status', 1)
                    ->values()
            );

            $this->createAndDispatch(
                $rule,
                $user,
                $rule->branch,
                $this->phoneNormalizer->normalize($user->detail?->mobile),
                $this->renderer->render($rule->template?->body ?? '', [
                    'member_name' => $user->name,
                    'member_no' => $user->detail?->member_no ?: $user->member_no,
                    'branch_name' => $user->branch?->name,
                    'month_label' => $now->format('F Y'),
                    'statement_summary' => $statementSummary['full'],
                    'statement_compact' => $statementSummary['compact'],
                    'statement_total_balance' => $statementSummary['total'],
                    'society_name' => 'Oreoluwapo CT&CS',
                ]),
                $user,
                $referenceKey
            );

            $count++;
        }

        return $count;
    }

    protected function createAndDispatch(
        SmsAutomationRule $rule,
        User $user,
        ?Branch $branch,
        ?string $phone,
        string $messageBody,
        mixed $related,
        string $referenceKey
    ): void {
        $message = SmsMessage::create([
            'automation_rule_id' => $rule->id,
            'user_id' => $user->id,
            'branch_id' => $branch?->id,
            'phone' => $phone,
            'recipient_name' => $user->name,
            'message' => $messageBody,
            'provider' => $this->settings->activeProvider(),
            'sender_id' => $this->settings->senderId(),
            'status' => SmsMessage::STATUS_PENDING,
            'related_type' => $related ? $related::class : null,
            'related_id' => $related?->id,
            'reference_key' => $referenceKey,
            'scheduled_for' => now(),
        ]);

        $this->dispatchService->dispatch($message);
    }

    protected function isTimeDue(SmsAutomationRule $rule, Carbon $now): bool
    {
        if (! $rule->schedule_time) {
            return true;
        }

        return substr($rule->schedule_time, 0, 5) === $now->format('H:i');
    }

    protected function statementSummary(Collection $accounts): array
    {
        $items = $accounts->map(function ($account): array {
            $type = strtoupper((string) ($account->product?->type ?: 'ACCOUNT'));
            $label = match ($type) {
                'SAVINGS' => 'Sav',
                'SHARES' => 'Shr',
                'AUTHENTICATION' => 'Auth',
                'DEPOSIT' => 'Dep',
                default => Str::title(Str::lower($type)),
            };

            return [
                'full' => ($account->product?->type ?: 'Account') . ': ' . number_format((float) $account->balance, 2),
                'compact' => $label . ' ' . number_format((float) $account->balance, 2),
                'balance' => (float) $account->balance,
            ];
        });

        return [
            'full' => $items->pluck('full')->implode(', '),
            'compact' => $items->pluck('compact')->implode(' | '),
            'total' => number_format((float) $items->sum('balance'), 2),
        ];
    }
}
