<?php

namespace App\Services\Email;

use App\Models\EmailSmtpAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class EmailSmtpAccountService
{
    public function eligibleAccount(): ?EmailSmtpAccount
    {
        $this->resetExpiredWindows();

        return EmailSmtpAccount::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNull('paused_until')
                    ->orWhere('paused_until', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('window_started_at')
                    ->orWhereColumn('sent_in_window', '<', 'hourly_limit');
            })
            ->inRandomOrder()
            ->first();
    }

    public function nextAvailableAt(): Carbon
    {
        $nextWindow = EmailSmtpAccount::query()
            ->where('is_active', true)
            ->whereNotNull('window_started_at')
            ->orderBy('window_started_at')
            ->value('window_started_at');

        if ($nextWindow) {
            return Carbon::parse($nextWindow)->addHour();
        }

        $pausedUntil = EmailSmtpAccount::query()
            ->where('is_active', true)
            ->whereNotNull('paused_until')
            ->orderBy('paused_until')
            ->value('paused_until');

        return $pausedUntil ? Carbon::parse($pausedUntil) : now()->addMinutes(15);
    }

    public function configureRuntimeMailer(EmailSmtpAccount $account): string
    {
        $mailerName = 'smtp_dynamic_' . $account->id;

        Config::set("mail.mailers.{$mailerName}", [
            'transport' => 'smtp',
            'host' => $account->host,
            'port' => $account->port,
            'encryption' => $account->encryption ?: null,
            'username' => $account->username,
            'password' => $account->decrypted_password,
            'timeout' => null,
            'local_domain' => config('mail.mailers.smtp.local_domain'),
        ]);

        Config::set('mail.from.address', $account->from_address);
        Config::set('mail.from.name', $account->from_name ?: config('app.name'));

        return $mailerName;
    }

    public function recordSent(EmailSmtpAccount $account): void
    {
        $account->refresh();

        if (! $account->window_started_at || $account->window_started_at->lte(now()->subHour())) {
            $account->forceFill([
                'window_started_at' => now(),
                'sent_in_window' => 0,
            ])->save();
        }

        $account->increment('sent_in_window');
    }

    protected function resetExpiredWindows(): void
    {
        EmailSmtpAccount::query()
            ->whereNotNull('window_started_at')
            ->where('window_started_at', '<=', now()->subHour())
            ->update([
                'sent_in_window' => 0,
                'window_started_at' => null,
            ]);
    }
}
