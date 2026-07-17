<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SocietyLedgerReportExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected Collection $sheets,
        protected array $filters,
    ) {
    }

    public function sheets(): array
    {
        if ($this->sheets->isEmpty()) {
            return [new SocietyLedgerSheetExport(1, [
                'member' => ['name' => 'No Records', 'member_no' => 'N/A'],
                'rows' => collect(),
            ], $this->filters)];
        }

        return $this->sheets
            ->values()
            ->map(fn (array $sheet, int $index): SocietyLedgerSheetExport => new SocietyLedgerSheetExport($index + 1, $sheet, $this->filters))
            ->all();
    }
}
