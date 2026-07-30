<?php

namespace App\Services\DataBackup;

use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BackupModuleRegistry
{
    public function modules(): array
    {
        return config('data_backup.modules', []);
    }

    public function validate(array $modules): array
    {
        $modules = array_values(array_unique($modules));
        $invalid = array_diff($modules, array_keys($this->modules()));

        if ($modules === [] || $invalid !== []) {
            throw ValidationException::withMessages([
                'modules' => 'Select at least one valid backup module.',
            ]);
        }

        return $modules;
    }

    public function tables(array $modules): array
    {
        $tables = [];

        foreach ($this->validate($modules) as $module) {
            foreach ($this->modules()[$module]['tables'] as $table) {
                if (Schema::hasTable($table) && ! in_array($table, $tables, true)) {
                    $tables[] = $table;
                }
            }
        }

        return $tables;
    }

    public function columns(string $table): array
    {
        return array_values(array_diff(
            Schema::getColumnListing($table),
            config("data_backup.excluded_columns.{$table}", [])
        ));
    }
}
