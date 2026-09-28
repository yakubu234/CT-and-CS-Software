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
        $connections = $configuration['google_connections'];

        if ($connections === []) {
            throw new RuntimeException('No Google Drive destination is connected.');
        }

        $disk = Storage::disk(config('data_backup.storage_disk'));
        $contents = $disk->get($backup->storage_path);
        $firstFile = null;
        $failures = [];
        $backup->update([
            'status' => 'uploading',
            'upload_started_at' => now(),
            'drive_destination_count' => count($connections),
            'drive_completed_count' => 0,
            'error_message' => null,
        ]);

        foreach ($connections as $connection) {
            try {
                $drive = new Drive($this->oauth->authorizedClient($connection));
                $file = $drive->files->create(
                    new DriveFile([
                        'name' => $backup->file_name,
                        'parents' => [$connection['folder_id']],
                    ]),
                    [
                        'data' => $contents,
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

                $firstFile ??= $file;
                $backup->increment('drive_completed_count');
            } catch (\Throwable $exception) {
                $account = $connection['account_email'] ?: $connection['account_name'] ?: $connection['id'];
                $failures[] = $account . ': ' . $exception->getMessage();
            }
        }

        if ($firstFile) {
            $backup->update([
                'google_drive_file_id' => $firstFile->id,
                'google_drive_url' => $firstFile->webViewLink,
            ]);
        }

        if ($failures !== []) {
            throw new RuntimeException('Backup delivery failed for: ' . implode('; ', $failures));
        }

        $backup->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return $backup->refresh();
    }

    public function testConnection(): void
    {
        $connections = $this->settings->googleConnections();

        if ($connections === []) {
            throw new RuntimeException('No Google Drive destination is connected.');
        }

        $failures = [];

        foreach ($connections as $connection) {
            try {
                $drive = new Drive($this->oauth->authorizedClient($connection));
                $file = $drive->files->create(
                    new DriveFile([
                        'name' => 'backup-connection-test-' . now()->format('Ymd-His') . '.txt',
                        'parents' => [$connection['folder_id']],
                    ]),
                    [
                        'data' => 'Google Drive backup connection test completed at ' . now()->toIso8601String(),
                        'mimeType' => 'text/plain',
                        'uploadType' => 'multipart',
                        'fields' => 'id',
                    ]
                );

                $drive->files->delete($file->getId());
            } catch (\Throwable $exception) {
                $account = $connection['account_email'] ?: $connection['account_name'] ?: $connection['id'];
                $failures[] = $account . ': ' . $exception->getMessage();
            }
        }

        if ($failures !== []) {
            throw new RuntimeException('Connection test failed for: ' . implode('; ', $failures));
        }
    }
}
