<?php

namespace App\Services\Sms;

use App\Services\Sms\Providers\BulkSmsNigeriaProvider;
use App\Services\Sms\Providers\GenericHttpSmsProvider;
use App\Services\Sms\Providers\TermiiProvider;
use RuntimeException;

class SmsProviderManager
{
    public function __construct(
        protected SmsSettingsService $settings,
        protected BulkSmsNigeriaProvider $bulkSmsNigeriaProvider,
        protected TermiiProvider $termiiProvider,
        protected GenericHttpSmsProvider $genericHttpSmsProvider,
    ) {
    }

    public function provider(?string $name = null): SmsProvider
    {
        $name ??= $this->settings->activeProvider();

        return match ($name) {
            'bulksmsnigeria' => $this->bulkSmsNigeriaProvider,
            'termii' => $this->termiiProvider,
            'generic' => $this->genericHttpSmsProvider,
            default => throw new RuntimeException('No SMS provider is configured.'),
        };
    }
}
