<?php

namespace App\Services\DataBackup;

use App\Models\DataBackup;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GoogleDriveBackupService
{
    public function __construct(protected BackupSettingsService $settings)
    {
    }

    public function upload(DataBackup $backup): DataBackup
    {
        $configuration = $this->settings->get();
        $folderId = trim((string) $configuration['drive_folder_id']);

        if ($folderId === '') {
            throw new RuntimeException('A Google Drive destination folder ID is required.');
        }

        $client = new Client();
        $client->setAuthConfig($this->settings->credentials());
        $client->setScopes([Drive::DRIVE]);
        $drive = new Drive($client);
        $disk = Storage::disk(config('data_backup.storage_disk'));

        $file = $drive->files->create(
            new DriveFile([
                'name' => $backup->file_name,
                'parents' => [$folderId],
            ]),
            [
                'data' => $disk->get($backup->storage_path),
                'mimeType' => match ($backup->format) {
                    'pdf' => 'application/pdf',
                    'sql' => 'application/sql',
                    default => 'application/zip',
                },
                'uploadType' => 'multipart',
                'fields' => 'id,webViewLink',
                'supportsAllDrives' => true,
            ]
        );

        foreach (array_unique($configuration['recipient_emails'] ?? []) as $email) {
            $drive->permissions->create(
                $file->id,
                new Permission([
                    'type' => 'user',
                    'role' => 'reader',
                    'emailAddress' => $email,
                ]),
                ['sendNotificationEmail' => true, 'supportsAllDrives' => true]
            );
        }

        $backup->update([
            'google_drive_file_id' => $file->id,
            'google_drive_url' => $file->webViewLink,
        ]);

        return $backup->refresh();
    }
}
