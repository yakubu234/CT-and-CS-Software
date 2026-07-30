<?php

namespace App\Services\DataBackup;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BackupSettingsService
{
    private const KEY = 'data_backup.configuration';

    public function get(): array
    {
        $defaults = [
            'enabled' => false,
            'formats' => ['xlsx'],
            'modules' => array_keys(config('data_backup.modules', [])),
            'drive_folder_id' => '',
            'recipient_emails' => [],
            'service_account_email' => '',
        ];

        $value = Setting::query()->where('name', self::KEY)->value('value');

        if (! $value) {
            return $defaults;
        }

        try {
            $decoded = json_decode(Crypt::decryptString($value), true, flags: JSON_THROW_ON_ERROR);

            return array_replace($defaults, is_array($decoded) ? $decoded : []);
        } catch (\Throwable) {
            return $defaults;
        }
    }

    public function update(array $configuration, ?UploadedFile $credentials = null): array
    {
        $current = $this->get();

        if ($credentials) {
            $json = json_decode($credentials->getContent(), true);

            if (! is_array($json) || ($json['type'] ?? null) !== 'service_account' || empty($json['client_email']) || empty($json['private_key'])) {
                throw new RuntimeException('The uploaded file is not a valid Google service-account JSON credential.');
            }

            Storage::disk(config('data_backup.storage_disk'))->put(
                config('data_backup.credentials_path'),
                Crypt::encryptString($credentials->getContent())
            );
            $configuration['service_account_email'] = $json['client_email'];
        } else {
            $configuration['service_account_email'] = $current['service_account_email'];
        }

        Setting::query()->updateOrCreate(
            ['name' => self::KEY],
            ['value' => Crypt::encryptString(json_encode($configuration, JSON_THROW_ON_ERROR))]
        );

        return $configuration;
    }

    public function credentials(): array
    {
        $disk = Storage::disk(config('data_backup.storage_disk'));
        $path = config('data_backup.credentials_path');

        if (! $disk->exists($path)) {
            throw new RuntimeException('Upload Google service-account credentials first.');
        }

        return json_decode(Crypt::decryptString($disk->get($path)), true, flags: JSON_THROW_ON_ERROR);
    }
}
