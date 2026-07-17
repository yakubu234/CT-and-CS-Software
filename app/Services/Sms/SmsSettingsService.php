<?php

namespace App\Services\Sms;

use App\Models\Setting;

class SmsSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()->where('name', $key)->first();

        if (! $setting) {
            return $default;
        }

        $decoded = json_decode($setting->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
    }

    public function put(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['name' => $key],
            ['value' => is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)]
        );
    }

    public function activeProvider(): ?string
    {
        return $this->string('sms.active_provider');
    }

    public function senderId(): ?string
    {
        return $this->string('sms.sender_id');
    }

    public function callbackUrl(): ?string
    {
        return $this->string('sms.callback_url');
    }

    public function providerConfig(?string $provider = null): array
    {
        $provider ??= $this->activeProvider();

        if (! $provider) {
            return [];
        }

        $config = $this->get("sms.providers.{$provider}", []);

        return array_replace($this->providerDefaults($provider), is_array($config) ? $config : []);
    }

    public function providerDefaults(string $provider): array
    {
        return match ($provider) {
            'termii' => [
                'base_url' => 'https://api.ng.termii.com',
                'endpoint' => '/api/sms/send',
                'balance_endpoint' => '/api/get-balance',
                'api_key' => '',
                'channel' => 'generic',
                'type' => 'plain',
            ],
            'bulksmsnigeria' => [
                'base_url' => 'https://www.bulksmsnigeria.com',
                'endpoint' => '/api/v2/sms',
                'balance_endpoint' => '/api/v2/balance',
                'api_token' => '',
                'gateway' => '',
            ],
            'generic' => [
                'base_url' => '',
                'endpoint' => '',
                'balance_endpoint' => '',
                'api_key' => '',
                'auth_mode' => 'bearer',
                'auth_header_name' => 'X-API-KEY',
                'message_field' => 'message',
                'phone_field' => 'to',
                'sender_field' => 'from',
                'callback_field' => 'callback_url',
            ],
            default => [],
        };
    }

    public function string(string $key, ?string $default = null): ?string
    {
        $value = $this->get($key, $default);

        return $value === null ? null : (string) $value;
    }
}
