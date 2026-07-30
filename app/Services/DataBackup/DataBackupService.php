<?php

namespace App\Services\DataBackup;

use App\Exports\DataBackupWorkbook;
use App\Models\DataBackup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

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
            $extension = $backup->format === 'csv' ? 'zip' : $backup->format;
            $fileName = sprintf(
                'database-backup-%s-%s.%s',
                now()->format('Ymd-His'),
                Str::lower(Str::random(6)),
                $extension
            );
            $path = trim(config('data_backup.storage_directory'), '/') . '/' . $fileName;
            $disk = Storage::disk(config('data_backup.storage_disk'));

            if ($backup->format === 'xlsx') {
                $disk->put(
                    $path,
                    Excel::raw(
                        new DataBackupWorkbook($backup->modules, $this->registry),
                        ExcelWriter::XLSX
                    )
                );
            } elseif ($backup->format === 'csv') {
                $this->writeCsvArchive($disk->path($path), $backup->modules);
            } elseif ($backup->format === 'sql') {
                $this->writeSqlDump($disk->path($path), $backup->modules);
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
                $disk->put($path, $content);
            }

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

    protected function writeCsvArchive(string $absolutePath, array $modules): void
    {
        File::ensureDirectoryExists(dirname($absolutePath));
        $zip = new ZipArchive();

        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create the CSV archive.');
        }

        $temporaryFiles = [];

        try {
            foreach ($this->registry->tables($modules) as $table) {
                $columns = $this->registry->columns($table);
                $temporaryPath = tempnam(dirname($absolutePath), 'csv-');

                if ($temporaryPath === false) {
                    throw new \RuntimeException("Unable to create a temporary CSV file for {$table}.");
                }

                $temporaryFiles[] = $temporaryPath;
                $stream = fopen($temporaryPath, 'wb');
                fputcsv($stream, $columns);

                foreach (DB::table($table)->select($columns)->orderBy($columns[0])->cursor() as $row) {
                    fputcsv($stream, array_map(
                        fn (string $column) => $row->{$column},
                        $columns
                    ));
                }

                fclose($stream);
                $zip->addFile($temporaryPath, $table . '.csv');
            }
        } finally {
            $zip->close();

            foreach ($temporaryFiles as $temporaryFile) {
                @unlink($temporaryFile);
            }
        }
    }

    protected function writeSqlDump(string $absolutePath, array $modules): void
    {
        File::ensureDirectoryExists(dirname($absolutePath));
        $stream = fopen($absolutePath, 'wb');

        if ($stream === false) {
            throw new \RuntimeException('Unable to create the SQL dump.');
        }

        try {
            fwrite($stream, "-- Database backup generated " . now()->toDateTimeString() . "\n");
            fwrite($stream, "SET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            foreach ($this->registry->tables($modules) as $table) {
                $quotedTable = '`' . str_replace('`', '``', $table) . '`';
                $definition = DB::selectOne("SHOW CREATE TABLE {$quotedTable}");
                $createSql = array_values((array) $definition)[1] ?? null;

                if (! $createSql) {
                    throw new \RuntimeException("Unable to read the structure for {$table}.");
                }

                fwrite($stream, "DROP TABLE IF EXISTS {$quotedTable};\n{$createSql};\n\n");
                $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
                $columnSql = implode(', ', array_map(
                    fn (string $column) => '`' . str_replace('`', '``', $column) . '`',
                    $columns
                ));

                foreach (DB::table($table)->select($columns)->orderBy($columns[0])->cursor() as $row) {
                    $values = array_map(
                        fn (string $column) => $this->sqlValue($row->{$column}),
                        $columns
                    );
                    fwrite(
                        $stream,
                        "INSERT INTO {$quotedTable} ({$columnSql}) VALUES (" . implode(', ', $values) . ");\n"
                    );
                }

                fwrite($stream, "\n");
            }

            fwrite($stream, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($stream);
        }
    }

    protected function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return DB::connection()->getPdo()->quote((string) $value);
    }
}
