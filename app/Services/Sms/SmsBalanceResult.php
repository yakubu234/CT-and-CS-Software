<?php

namespace App\Services\Sms;

class SmsBalanceResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?float $balance = null,
        public readonly ?string $currency = null,
        public readonly ?string $message = null,
        public readonly array $payload = [],
    ) {
    }

    public static function success(?float $balance, ?string $currency = 'NGN', array $payload = []): self
    {
        return new self(true, $balance, $currency, null, $payload);
    }

    public static function failure(string $message, array $payload = []): self
    {
        return new self(false, null, null, $message, $payload);
    }
}
