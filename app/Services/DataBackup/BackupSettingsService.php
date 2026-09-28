<?php

namespace App\Services\DataBackup;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class BackupSettingsService
{
    private const KEY = 'data_backup.configuration';

    public function get(): array
    {
        $defaults = [
            'enabled' => false,
            'formats' => ['csv'],
            'modules' => array_keys(config('data_backup.modules', [])),
            'recipient_emails' => [],
            'google_refresh_token' => '',
            'google_account_email' => '',
            'google_account_name' => '',
            'google_connected_at' => null,
            'google_folder_id' => '',
            'google_folder_name' => '',
        ];

        $value = Setting::query()->where('name', self::KEY)->value('value');

        if (! $value) {
            return $defaults;
        }

        try {
            $decoded = json_decode(Crypt::decryptString($value), true, flags: JSON_THROW_ON_ERROR);

            $settings = array_replace($defaults, is_array($decoded) ? $decoded : []);
            $settings['formats'] = array_values(array_intersect(
                (array) $settings['formats'],
                ['xlsx', 'csv', 'pdf', 'sql']
            )) ?: ['csv'];

            return $settings;
        } catch (\Throwable) {
            return $defaults;
        }
    }

    public function update(array $configuration): array
    {
        $current = $this->get();

        $configuration = array_replace($current, $configuration);

        return $this->persist($configuration);
    }

    public function storeGoogleConnection(array $connection): array
    {
        $configuration = array_replace($this->get(), [
            'google_refresh_token' => $connection['refresh_token'],
            'google_account_email' => $connection['account_email'] ?? '',
            'google_account_name' => $connection['account_name'] ?? '',
            'google_connected_at' => now()->toIso8601String(),
            'google_folder_id' => $connection['folder_id'],
            'google_folder_name' => $connection['folder_name'],
        ]);

        return $this->persist($configuration);
    }

    public function clearGoogleConnection(): array
    {
        $configuration = array_replace($this->get(), [
            'enabled' => false,
            'google_refresh_token' => '',
            'google_account_email' => '',
            'google_account_name' => '',
            'google_connected_at' => null,
            'google_folder_id' => '',
            'google_folder_name' => '',
        ]);

        return $this->persist($configuration);
    }

    public function isGoogleConnected(): bool
    {
        $configuration = $this->get();

        return $configuration['google_refresh_token'] !== ''
            && $configuration['google_folder_id'] !== '';
    }

    private function persist(array $configuration): array
    {
        Setting::query()->updateOrCreate(
            ['name' => self::KEY],
            ['value' => Crypt::encryptString(json_encode($configuration, JSON_THROW_ON_ERROR))]
        );

        return $configuration;
    }
}
