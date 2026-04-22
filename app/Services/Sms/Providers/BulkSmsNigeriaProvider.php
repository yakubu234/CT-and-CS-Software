<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsMessage;
use App\Services\Sms\SmsBalanceResult;
use App\Services\Sms\SmsProvider;
use App\Services\Sms\SmsSendResult;
use App\Services\Sms\SmsSettingsService;
use Illuminate\Support\Facades\Http;

class BulkSmsNigeriaProvider implements SmsProvider
{
    public function __construct(
        protected SmsSettingsService $settings,
    ) {
    }

    public function send(SmsMessage $message): SmsSendResult
    {
        $config = $this->settings->providerConfig('bulksmsnigeria');
        $token = (string) ($config['api_token'] ?? '');
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://www.bulksmsnigeria.com'), '/');
        $endpoint = (string) ($config['endpoint'] ?? '/api/v2/sms');

        if ($token === '') {
            return SmsSendResult::failure('BulkSMSNigeria API token is missing.');
        }

        $response = Http::acceptJson()
            ->withToken($token)
            ->post($baseUrl . $endpoint, array_filter([
                'from' => $message->sender_id,
                'to' => $message->phone,
                'body' => $message->message,
                'gateway' => $config['gateway'] ?? null,
                'callback_url' => $this->settings->callbackUrl(),
            ], static fn ($value): bool => $value !== null && $value !== ''));

        if (! $response->successful()) {
            return SmsSendResult::failure(
                $response->json('message') ?: ('BulkSMSNigeria request failed with status ' . $response->status()),
                ['response' => $response->json() ?: $response->body()]
            );
        }

        return SmsSendResult::success(
            $response->json('data.message_id') ?: $response->json('message_id'),
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
        $config = $this->settings->providerConfig('bulksmsnigeria');
        $token = (string) ($config['api_token'] ?? '');
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://www.bulksmsnigeria.com'), '/');
        $endpoint = (string) ($config['balance_endpoint'] ?? '/api/v2/balance');

        if ($token === '') {
            return SmsBalanceResult::failure('BulkSMSNigeria API token is missing.');
        }

        $response = Http::acceptJson()
            ->withToken($token)
            ->get($baseUrl . $endpoint);

        if (! $response->successful()) {
            return SmsBalanceResult::failure(
                $response->json('message') ?: ('BulkSMSNigeria balance request failed with status ' . $response->status()),
                ['response' => $response->json() ?: $response->body()]
            );
        }

        return SmsBalanceResult::success(
            (float) ($response->json('data.balance') ?? $response->json('balance') ?? 0),
            (string) ($response->json('data.currency') ?? $response->json('currency') ?? 'NGN'),
            ['response' => $response->json()]
        );
    }
}
