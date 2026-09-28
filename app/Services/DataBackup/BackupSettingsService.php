<?php

namespace App\Services\DataBackup;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

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
            'google_connections' => [],
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
            $settings['google_connections'] = $this->normalizeGoogleConnections($settings);
            unset(
                $settings['google_refresh_token'],
                $settings['google_account_email'],
                $settings['google_account_name'],
                $settings['google_connected_at'],
                $settings['google_folder_id'],
                $settings['google_folder_name'],
            );

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
        $configuration = $this->get();
        $connections = $configuration['google_connections'];
        $email = strtolower(trim((string) ($connection['account_email'] ?? '')));
        $existingId = collect($connections)->search(
            fn (array $item): bool => $email !== '' && strtolower((string) $item['account_email']) === $email
        );
        $id = is_string($existingId) ? $existingId : (string) Str::uuid();

        $connections[$id] = [
            'id' => $id,
            'refresh_token' => $connection['refresh_token'],
            'account_email' => $email,
            'account_name' => $connection['account_name'] ?? '',
            'connected_at' => now()->toIso8601String(),
            'folder_id' => $connection['folder_id'],
            'folder_name' => $connection['folder_name'],
        ];
        $configuration['google_connections'] = $connections;

        return $this->persist($configuration);
    }

    public function removeGoogleConnection(string $connectionId): array
    {
        $configuration = $this->get();
        unset($configuration['google_connections'][$connectionId]);

        if ($configuration['google_connections'] === []) {
            $configuration['enabled'] = false;
        }

        return $this->persist($configuration);
    }

    public function googleConnections(): array
    {
        return $this->get()['google_connections'];
    }

    public function googleConnection(string $connectionId): ?array
    {
        return $this->googleConnections()[$connectionId] ?? null;
    }

    public function isGoogleConnected(): bool
    {
        return $this->googleConnections() !== [];
    }

    private function persist(array $configuration): array
    {
        Setting::query()->updateOrCreate(
            ['name' => self::KEY],
            ['value' => Crypt::encryptString(json_encode($configuration, JSON_THROW_ON_ERROR))]
        );

        return $configuration;
    }

    private function normalizeGoogleConnections(array $settings): array
    {
        $connections = is_array($settings['google_connections'] ?? null)
            ? $settings['google_connections']
            : [];

        if ($connections === [] && ! empty($settings['google_refresh_token']) && ! empty($settings['google_folder_id'])) {
            $id = 'legacy-' . substr(hash('sha256', (string) ($settings['google_account_email'] ?? '') . $settings['google_folder_id']), 0, 16);
            $connections[$id] = [
                'id' => $id,
                'refresh_token' => $settings['google_refresh_token'],
                'account_email' => strtolower((string) ($settings['google_account_email'] ?? '')),
                'account_name' => $settings['google_account_name'] ?? '',
                'connected_at' => $settings['google_connected_at'] ?? null,
                'folder_id' => $settings['google_folder_id'],
                'folder_name' => $settings['google_folder_name'] ?? '',
            ];
        }

        return collect($connections)
            ->filter(fn ($connection): bool => is_array($connection)
                && ! empty($connection['id'])
                && ! empty($connection['refresh_token'])
                && ! empty($connection['folder_id']))
            ->mapWithKeys(fn (array $connection): array => [(string) $connection['id'] => $connection])
            ->all();
    }
}
