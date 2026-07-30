<?php

namespace App\Services\DataBackup;

use App\Models\DataBackup;
use Illuminate\Support\Facades\Log;

class AutomaticBackupRunner
{
    public function __construct(
        protected BackupSettingsService $settings,
        protected DataBackupService $backups,
        protected GoogleDriveBackupService $drive,
    ) {
    }

    public function run(): array
    {
        $configuration = $this->settings->get();

        if (! $configuration['enabled']) {
            return [];
        }

        $results = [];

        foreach ($configuration['formats'] as $format) {
            try {
                $backup = $this->backups->create(
                    $configuration['modules'],
                    $format,
                    'automatic'
                );
                $results[] = $this->drive->upload($backup);
            } catch (\Throwable $exception) {
                if (isset($backup) && $backup instanceof DataBackup) {
                    $backup->update([
                        'status' => 'failed',
                        'error_message' => $exception->getMessage(),
                        'completed_at' => now(),
                    ]);
                }

                Log::error('Automatic data backup failed.', [
                    'format' => $format,
                    'exception' => $exception,
                ]);
            }
        }

        return $results;
    }
}
