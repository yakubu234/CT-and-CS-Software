<?php

namespace App\Exports;

use App\Services\DataBackup\BackupModuleRegistry;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DataBackupWorkbook implements WithMultipleSheets
{
    public function __construct(
        protected array $modules,
        protected BackupModuleRegistry $registry,
    ) {
    }

    public function sheets(): array
    {
        return array_map(
            fn (string $table) => new DataBackupSheet($table, $this->registry->columns($table)),
            $this->registry->tables($this->modules)
        );
    }
}
