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

        try {
            Mail::mailer($this->settings->mailer())
                ->to($message->email, $message->recipient_name)
                ->send(new EmailModuleMessage($message));
        } catch (Throwable $exception) {
            $message->update([
                'mailer' => $this->settings->mailer(),
                'status' => EmailMessage::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            return $message->fresh();
        }

        $message->update([
            'mailer' => $this->settings->mailer(),
            'status' => EmailMessage::STATUS_SENT,
            'error_message' => null,
            'processed_at' => now(),
            'sent_at' => Carbon::now(),
        ]);

        return $message->fresh();
    }
}
