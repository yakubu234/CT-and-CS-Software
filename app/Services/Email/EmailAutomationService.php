<?php

namespace App\Services\Email;

use App\Models\EmailMessage;
use App\Models\EmailTemplate;
use App\Models\LoanDetail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class EmailAutomationService
{
    public function __construct(
        protected EmailTemplateRenderer $renderer,
        protected EmailCampaignService $campaigns,
        protected EmailDispatchService $dispatch,
    ) {
    }

    public function memberRegistered(User $member): void
    {
        if (! EmailTemplate::query()->where('category', 'member_registration')->where('status', true)->exists()) {
            return;
        }

        $token = Password::broker()->createToken($member);
        $this->send('member_registration', $member, 'registration:' . $member->id, $member, [
            'reference_code' => $member->display_member_no ?: (string) $member->id,
            'reset_url' => route('member-password.reset', ['token' => $token, 'email' => $member->email]),
        ]);
    }

    public function accountVerificationRequested(User $member, bool $resend = false): bool
    {
        $url = URL::temporarySignedRoute('member-email.verify', now()->addDays(2), [
            'member' => $member->id,
            'hash' => sha1(strtolower($member->email)),
        ]);

        return $this->send('account_verification', $member, 'account-verification:' . $member->id . ($resend ? ':' . now()->timestamp : ''), $member, [
            'verification_url' => $url,
            'reference_code' => $member->display_member_no ?: (string) $member->id,
        ]);
    }

    public function loanApproved(LoanDetail $detail): void
    {
        $detail->loadMissing(['borrower.detail', 'borrower.branch', 'loan']);
        if ($detail->borrower) {
            $this->send('loan_updates', $detail->borrower, 'loan-approval:' . $detail->id, $detail, [
                'reference_code' => $detail->loan?->loan_id ?: (string) $detail->id,
                'loan_amount' => number_format((float) $detail->applied_amount, 2),
            ]);
        }
    }

    public function passwordResetRequested(User $member, string $resetUrl): bool
    {
        return $this->send('password_resets', $member, 'password-reset:' . $member->id . ':' . now()->timestamp, $member, [
            'reset_url' => $resetUrl,
            'reference_code' => $member->display_member_no ?: (string) $member->id,
        ]);
    }

    public function processRepaymentReminders(): int
    {
        $count = 0;
        $reminderDate = now()->addDays(3)->toDateString();

        LoanDetail::query()
            ->with(['borrower.detail', 'borrower.branch', 'loan'])
            ->where('decision_status', LoanDetail::STATUS_APPROVED)
            ->whereDate('due_date', $reminderDate)
            ->orderBy('id')
            ->chunkById(100, function ($details) use (&$count, $reminderDate): void {
                foreach ($details as $detail) {
                    $member = $detail->borrower;
                    if (! $member || $member->status != 1 || ! $member->email || (float) $detail->applied_amount <= (float) $detail->payments()->whereNull('deleted_at')->sum('repayment_amount')) {
                        continue;
                    }

                    if ($this->send('repayment_reminders', $member, 'repayment-reminder:' . $detail->id . ':' . $reminderDate, $detail, [
                        'reference_code' => $detail->loan?->loan_id ?: (string) $detail->id,
                        'due_date' => $detail->due_date?->format('d M Y'),
                    ])) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    protected function send(string $category, User $member, string $reference, object $related, array $extra = []): bool
    {
        if (! filter_var($member->email, FILTER_VALIDATE_EMAIL) || EmailMessage::query()->where('reference_key', $reference)->exists()) {
            return false;
        }

        $template = EmailTemplate::query()->where('category', $category)->where('status', true)->latest('id')->first();
        if (! $template) {
            return false;
        }

        $member->loadMissing(['detail', 'branch']);
        $context = array_replace($this->campaigns->contextForUser($member, $member->branch), $extra);

        try {
            $body = $this->renderer->render($template->body, $context);
            if ($category === 'account_verification' && ! str_contains($template->body, '{{verification_url}}')) {
                $body .= '<p><a href="' . htmlspecialchars($context['verification_url'], ENT_QUOTES, 'UTF-8') . '">Verify your email address</a></p>';
            }
            if ($category === 'password_resets' && ! str_contains($template->body, '{{reset_url}}')) {
                $body .= '<p><a href="' . htmlspecialchars($context['reset_url'], ENT_QUOTES, 'UTF-8') . '">Reset your password</a></p>';
            }
            if ($category === 'member_registration' && ! str_contains($template->body, '{{reset_url}}')) {
                $body .= '<p><a href="' . htmlspecialchars($context['reset_url'], ENT_QUOTES, 'UTF-8') . '">Set your portal password</a></p>';
            }

            $message = EmailMessage::create([
                'user_id' => $member->id,
                'branch_id' => $member->branch_id,
                'email' => $member->email,
                'recipient_name' => $member->name,
                'subject' => $this->renderer->render($template->subject, $context),
                'body' => $body,
                'status' => EmailMessage::STATUS_PENDING,
                'related_type' => $related::class,
                'related_id' => $related->id,
                'reference_key' => $reference,
                'meta' => ['template_id' => $template->id, 'created_via' => 'system-trigger', 'transactional_security' => in_array($category, ['member_registration', 'password_resets', 'account_verification'], true)],
            ]);
            $this->dispatch->dispatch($message);
            return true;
        } catch (\Throwable $exception) {
            Log::error('Automatic email failed', ['reference' => $reference, 'error' => $exception->getMessage()]);
            return false;
        }
    }
}
