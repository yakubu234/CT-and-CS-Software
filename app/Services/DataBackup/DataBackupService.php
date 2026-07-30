<?php

namespace App\Services\DataBackup;

use App\Exports\DataBackupWorkbook;
use App\Models\DataBackup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;

class DataBackupService
{
    public function __construct(protected BackupModuleRegistry $registry)
    {
    }

    public function create(array $modules, string $format, string $trigger = 'manual', ?int $createdBy = null): DataBackup
    {
        $backup = $this->queue($modules, $format, $trigger, $createdBy);
        $backup->forceFill([
            'status' => 'processing',
            'processing_started_at' => now(),
        ])->save();

        return $this->process($backup);
    }

    public function queue(array $modules, string $format, string $trigger = 'manual', ?int $createdBy = null): DataBackup
    {
        return DataBackup::create([
            'created_by' => $createdBy,
            'trigger' => $trigger,
            'format' => $format,
            'modules' => $this->registry->validate($modules),
            'status' => 'pending',
            'queued_at' => now(),
        ]);
    }

    public function claimNextManual(): ?DataBackup
    {
        return DB::transaction(function (): ?DataBackup {
            $backup = DataBackup::query()
                ->where('trigger', 'manual')
                ->where('status', 'pending')
                ->orderBy('queued_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $backup) {
                return null;
            }

            $backup->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'error_message' => null,
            ]);

            return $backup->refresh();
        });
    }

    public function process(DataBackup $backup): DataBackup
    {
        try {
            $fileName = sprintf(
                'database-backup-%s-%s.%s',
                now()->format('Ymd-His'),
                Str::lower(Str::random(6)),
                $backup->format
            );
            $path = trim(config('data_backup.storage_directory'), '/') . '/' . $fileName;

            if ($backup->format === 'xlsx') {
                $content = Excel::raw(
                    new DataBackupWorkbook($backup->modules, $this->registry),
                    ExcelWriter::XLSX
                );
            } else {
                $tables = [];
                foreach ($this->registry->tables($backup->modules) as $table) {
                    $columns = $this->registry->columns($table);
                    $tables[] = [
                        'name' => $table,
                        'columns' => $columns,
                        'rows' => DB::table($table)->select($columns)->get(),
                    ];
                }

                $content = Pdf::loadView('data-backups.pdf', compact('tables'))
                    ->setPaper('a4', 'landscape')
                    ->output();
            }

            $disk = Storage::disk(config('data_backup.storage_disk'));
            $disk->put($path, $content);

            $backup->update([
                'status' => 'completed',
                'file_name' => $fileName,
                'storage_path' => $path,
                'file_size' => $disk->size($path),
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $backup->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 65000),
                'completed_at' => now(),
            ]);

            throw $exception;
        }

        return $backup->refresh();
    }
}
