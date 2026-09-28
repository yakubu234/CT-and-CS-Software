<?php

namespace App\Services\DataBackup;

use App\Models\DataBackup;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GoogleDriveBackupService
{
    public function __construct(
        protected BackupSettingsService $settings,
        protected GoogleDriveOAuthService $oauth,
    ) {
    }

    public function upload(DataBackup $backup): DataBackup
    {
        $configuration = $this->settings->get();
        $folderId = trim((string) $configuration['google_folder_id']);

        if ($folderId === '') {
            throw new RuntimeException('Google Drive is not connected or its backup folder is missing.');
        }

        $drive = new Drive($this->oauth->authorizedClient());
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
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    default => 'application/zip',
                },
                'uploadType' => 'multipart',
                'fields' => 'id,webViewLink',
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
                ['sendNotificationEmail' => true]
            );
        }

        $backup->update([
            'google_drive_file_id' => $file->id,
            'google_drive_url' => $file->webViewLink,
        ]);

        return $backup->refresh();
    }

    public function testConnection(): void
    {
        $configuration = $this->settings->get();
        $folderId = trim((string) $configuration['google_folder_id']);

        if ($folderId === '') {
            throw new RuntimeException('Google Drive is not connected.');
        }

        $drive = new Drive($this->oauth->authorizedClient());
        $file = $drive->files->create(
            new DriveFile([
                'name' => 'backup-connection-test-' . now()->format('Ymd-His') . '.txt',
                'parents' => [$folderId],
            ]),
            [
                'data' => 'Google Drive backup connection test completed at ' . now()->toIso8601String(),
                'mimeType' => 'text/plain',
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]
        );

        $drive->files->delete($file->getId());
    }
}
