<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DataBackupSheet implements FromQuery, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        protected string $table,
        protected array $columns,
    ) {
    }

    public function query()
    {
        return DB::table($this->table)->select($this->columns)->orderBy($this->columns[0]);
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function title(): string
    {
        return mb_substr($this->table, 0, 31);
    }
}
