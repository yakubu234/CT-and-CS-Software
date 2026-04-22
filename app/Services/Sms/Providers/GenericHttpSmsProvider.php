<?php

namespace App\Services\Sms\Providers;

use App\Models\SmsMessage;
use App\Services\Sms\SmsBalanceResult;
use App\Services\Sms\SmsProvider;
use App\Services\Sms\SmsSendResult;
use App\Services\Sms\SmsSettingsService;
use Illuminate\Support\Facades\Http;

class GenericHttpSmsProvider implements SmsProvider
{
    public function __construct(
        protected SmsSettingsService $settings,
    ) {
    }

    public function send(SmsMessage $message): SmsSendResult
    {
        $config = $this->settings->providerConfig('generic');
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $endpoint = (string) ($config['endpoint'] ?? '');
        $apiKey = (string) ($config['api_key'] ?? '');

        if ($baseUrl === '' || $endpoint === '') {
            return SmsSendResult::failure('Generic SMS provider base URL or endpoint is missing.');
        }

        $messageField = (string) ($config['message_field'] ?? 'message');
        $phoneField = (string) ($config['phone_field'] ?? 'to');
        $senderField = (string) ($config['sender_field'] ?? 'from');
        $callbackField = (string) ($config['callback_field'] ?? 'callback_url');
        $authMode = (string) ($config['auth_mode'] ?? 'bearer');
        $authHeader = (string) ($config['auth_header_name'] ?? 'X-API-KEY');

        $payload = [
            $messageField => $message->message,
            $phoneField => $message->phone,
            $senderField => $message->sender_id,
        ];

        if ($this->settings->callbackUrl()) {
            $payload[$callbackField] = $this->settings->callbackUrl();
        }

        $request = Http::acceptJson();

        if ($apiKey !== '') {
            if ($authMode === 'bearer') {
                $request = $request->withToken($apiKey);
            } elseif ($authMode === 'header') {
                $request = $request->withHeaders([$authHeader => $apiKey]);
            } elseif ($authMode === 'body') {
                $payload['api_key'] = $apiKey;
            }
        }

        $response = $request->post($baseUrl . $endpoint, $payload);

        if (! $response->successful()) {
            return SmsSendResult::failure(
                $response->json('message') ?: ('Generic SMS request failed with status ' . $response->status()),
                ['response' => $response->json() ?: $response->body()]
            );
        }

        return SmsSendResult::success(
            $response->json('message_id') ?: $response->json('data.message_id') ?: $response->json('id'),
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
        $config = $this->settings->providerConfig('generic');
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $endpoint = (string) ($config['balance_endpoint'] ?? '');
        $apiKey = (string) ($config['api_key'] ?? '');
        $authMode = (string) ($config['auth_mode'] ?? 'bearer');
        $authHeader = (string) ($config['auth_header_name'] ?? 'X-API-KEY');

        if ($baseUrl === '' || $endpoint === '') {
            return SmsBalanceResult::failure('Set a generic balance endpoint before checking balance.');
        }

        $request = Http::acceptJson();
        $query = [];

        if ($apiKey !== '') {
            if ($authMode === 'bearer') {
                $request = $request->withToken($apiKey);
            } elseif ($authMode === 'header') {
                $request = $request->withHeaders([$authHeader => $apiKey]);
            } elseif ($authMode === 'body') {
                $query['api_key'] = $apiKey;
            }
        }

        $response = $request->get($baseUrl . $endpoint, $query);

        if (! $response->successful()) {
            return SmsBalanceResult::failure(
                $response->json('message') ?: ('Generic balance request failed with status ' . $response->status()),
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
