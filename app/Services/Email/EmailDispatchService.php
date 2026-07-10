<?php

namespace App\Services\Email;

use App\Mail\EmailModuleMessage;
use App\Models\EmailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailDispatchService
{
    public function __construct(
        protected EmailSettingsService $settings,
        protected EmailSmtpAccountService $smtpAccounts,
        protected EmailPreferenceService $preferences,
    ) {
    }

    public function dispatch(EmailMessage $message): EmailMessage
    {
        $message->refresh();

        if (! $message->email) {
            $message->update([
                'status' => EmailMessage::STATUS_SKIPPED,
                'error_message' => 'Recipient email address is missing.',
                'processed_at' => now(),
            ]);

            return $message->fresh();
        }

        [$canSend, $pauseReason] = $this->preferences->canSend($message);

        if (! $canSend) {
            $message->update([
                'status' => EmailMessage::STATUS_SKIPPED,
                'error_message' => $pauseReason,
                'processed_at' => now(),
            ]);

            return $message->fresh();
        }

        $smtpAccount = $this->smtpAccounts->eligibleAccount();

        if (! $smtpAccount) {
            $message->update([
                'status' => EmailMessage::STATUS_PENDING,
                'error_message' => 'No SMTP account is currently available within its hourly limit.',
                'scheduled_for' => $this->smtpAccounts->nextAvailableAt(),
                'processed_at' => now(),
            ]);

            return $message->fresh();
        }

        $mailer = $this->smtpAccounts->configureRuntimeMailer($smtpAccount);

        try {
            Mail::mailer($mailer)
                ->to($message->email, $message->recipient_name)
                ->send(new EmailModuleMessage($message));
        } catch (Throwable $exception) {
            $message->update([
                'mailer' => $mailer,
                'smtp_account_id' => $smtpAccount->id,
                'status' => EmailMessage::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            return $message->fresh();
        }

        $this->smtpAccounts->recordSent($smtpAccount);

        $message->update([
            'mailer' => $mailer,
            'smtp_account_id' => $smtpAccount->id,
            'status' => EmailMessage::STATUS_SENT,
            'error_message' => null,
            'processed_at' => now(),
            'sent_at' => Carbon::now(),
        ]);

        return $message->fresh();
    }
}
