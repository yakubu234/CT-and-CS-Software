<?php

namespace App\Services\Sms;

use App\Models\SmsMessage;
use Illuminate\Support\Carbon;
use RuntimeException;

class SmsDispatchService
{
    public function __construct(
        protected SmsProviderManager $providerManager,
        protected SmsSettingsService $settings,
    ) {
    }

    public function dispatch(SmsMessage $message): SmsMessage
    {
        $message->refresh();

        if (! $message->phone) {
            $message->update([
                'status' => SmsMessage::STATUS_SKIPPED,
                'error_message' => 'Recipient phone number is missing.',
                'processed_at' => now(),
            ]);

            return $message->fresh();
        }

        if (! $this->settings->activeProvider()) {
            $message->update([
                'status' => SmsMessage::STATUS_FAILED,
                'error_message' => 'No SMS provider is configured.',
                'processed_at' => now(),
            ]);

            return $message->fresh();
        }

        try {
            $result = $this->providerManager->provider()->send($message);
        } catch (RuntimeException $exception) {
            $message->update([
                'status' => SmsMessage::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            return $message->fresh();
        }

        $message->update([
            'provider' => $this->settings->activeProvider(),
            'status' => $result->successful ? SmsMessage::STATUS_SENT : SmsMessage::STATUS_FAILED,
            'external_id' => $result->externalId,
            'error_message' => $result->errorMessage,
            'processed_at' => now(),
            'sent_at' => $result->successful ? Carbon::now() : null,
            'meta' => array_merge($message->meta ?? [], $result->payload),
        ]);

        return $message->fresh();
    }
}
