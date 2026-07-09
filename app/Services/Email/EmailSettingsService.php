<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Config;

class EmailSettingsService
{
    public function mailer(): ?string
    {
        return Config::get('mail.default');
    }

    public function fromAddress(): ?string
    {
        return Config::get('mail.from.address');
    }

    public function fromName(): ?string
    {
        return Config::get('mail.from.name');
    }

    public function configSummary(): array
    {
        $mailer = $this->mailer();

        return [
            'mailer' => $mailer,
            'from_address' => $this->fromAddress(),
            'from_name' => $this->fromName(),
            'host' => $mailer ? Config::get("mail.mailers.{$mailer}.host") : null,
            'port' => $mailer ? Config::get("mail.mailers.{$mailer}.port") : null,
            'encryption' => $mailer ? Config::get("mail.mailers.{$mailer}.encryption") : null,
        ];
    }
}
