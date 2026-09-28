<?php

namespace App\Services\DataBackup;

use App\Models\DataBackup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QueuedDriveBackupRunner
{
    public function __construct(
        protected DataBackupService $backups,
        protected GoogleDriveBackupService $drive,
    ) {
    }

    public function runPending(): array
    {
        $results = [];

        while ($backup = $this->backups->claimNextDrive()) {
            try {
                $this->backups->process($backup);
                $results[] = $this->drive->upload($backup->refresh());
            } catch (\Throwable $exception) {
                $backup->update([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 65000),
                    'completed_at' => now(),
                ]);

                Log::error('Queued Google Drive backup failed.', [
                    'backup_id' => $backup->id,
                    'exception' => $exception,
                ]);
            }
        }

        return $results;
    }
}
