<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsMessage;
use App\Services\Sms\SmsBalanceResult;
use App\Services\Sms\SmsProvider;
use App\Services\Sms\SmsSendResult;
use App\Services\Sms\SmsSettingsService;
use Illuminate\Support\Facades\Http;

class TermiiProvider implements SmsProvider
{
    public function __construct(
        protected SmsSettingsService $settings,
    ) {
    }

    public function send(SmsMessage $message): SmsSendResult
    {
        $config = $this->settings->providerConfig('termii');
        $apiKey = (string) ($config['api_key'] ?? '');
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $endpoint = (string) ($config['endpoint'] ?? '/api/sms/send');

        if ($apiKey === '' || $baseUrl === '') {
            return SmsSendResult::failure('Termii base URL or API key is missing.');
        }

        $payload = array_filter([
            'api_key' => $apiKey,
            'to' => $message->phone,
            'from' => $message->sender_id,
            'sms' => $message->message,
            'type' => $config['type'] ?? 'plain',
            'channel' => $config['channel'] ?? 'generic',
        ], static fn ($value): bool => $value !== null && $value !== '');

        $response = Http::acceptJson()->post($baseUrl . $endpoint, $payload);

        if (! $response->successful()) {
            return SmsSendResult::failure(
                $response->json('message') ?: ('Termii request failed with status ' . $response->status()),
                ['response' => $response->json() ?: $response->body()]
            );
        }

        return SmsSendResult::success(
            $response->json('message_id') ?: $response->json('data.message_id'),
            ['response' => $response->json()]
        );
    }

    public function sendTest(string $phone, string $message, ?string $senderId = null): SmsSendResult
    {
        return $this->send(new SmsMessage([
            'phone' => $phone,
            'message' => $message,
            'sender_id' => $senderId ?: $this->settings->senderId(),
        ]));
    }

    public function balance(): SmsBalanceResult
    {
        $config = $this->settings->providerConfig('termii');
        $apiKey = (string) ($config['api_key'] ?? '');
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $endpoint = (string) ($config['balance_endpoint'] ?? '');

        if ($apiKey === '' || $baseUrl === '') {
            return SmsBalanceResult::failure('Termii base URL or API key is missing.');
        }

        if ($endpoint === '') {
            return SmsBalanceResult::failure('Set a Termii balance endpoint before checking balance.');
        }

        $response = Http::acceptJson()->get($baseUrl . $endpoint, [
            'api_key' => $apiKey,
        ]);

        if (! $response->successful()) {
            return SmsBalanceResult::failure(
                $response->json('message') ?: ('Termii balance request failed with status ' . $response->status()),
                ['response' => $response->json() ?: $response->body()]
            );
        }

        return SmsBalanceResult::success(
            (float) ($response->json('balance') ?? $response->json('data.balance') ?? 0),
            (string) ($response->json('currency') ?? $response->json('data.currency') ?? 'NGN'),
            ['response' => $response->json()]
        );
    }
}
