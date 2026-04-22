<?php

namespace App\Services\Sms;

class SmsSendResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $externalId = null,
        public readonly ?string $errorMessage = null,
        public readonly array $payload = [],
    ) {
    }

    public static function success(?string $externalId = null, array $payload = []): self
    {
        return new self(true, $externalId, null, $payload);
    }

    public static function failure(string $message, array $payload = []): self
    {
        return new self(false, null, $message, $payload);
    }
}
