<?php

namespace App\Exports;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class IncomeExpenseReportExport implements FromArray, ShouldAutoSize, WithEvents
{
    use Exportable;

    public function __construct(
        protected Branch $branch,
        protected Collection $records,
        protected array $summary,
        protected array $filters,
        protected string $preparedBy,
    ) {
    }

    public function array(): array
    {
        $rows = [
            [$this->branch->name . ' - Income & Expenses Report'],
            ['Period: ' . ($this->filters['start_date'] ?: 'Beginning') . ' -> ' . $this->filters['end_date']],
            [],
            ['B/F Credit', $this->summary['bf_credit']],
            ['B/F Debit', $this->summary['bf_debit']],
            ['B/F Net', $this->summary['bf_net']],
            ['Current Credit', $this->summary['current_credit']],
            ['Current Debit', $this->summary['current_debit']],
            ['Current Net', $this->summary['current_net']],
            ['Closing Balance', $this->summary['closing_balance']],
            [],
            ['Date', 'Type', 'Description', 'Credit', 'Debit', 'Balance', 'Status', 'Running Net'],
            [
                $this->filters['start_date'] ?: 'Beginning',
                '',
                'Brought Forward',
                '',
                '',
                $this->summary['bf_net'],
                '',
                $this->summary['bf_net'],
            ],
        ];

        foreach ($this->records as $record) {
            $rows[] = [
                $record['date'],
                $record['type'],
                $record['description'],
                $record['credit'],
                $record['debit'],
                $record['balance'],
                $record['status'],
                $record['running_net'],
            ];
        }

        $rows[] = [];
        $rows[] = [
            'Prepared By: ' . strtoupper($this->preparedBy),
            '',
            '',
            '',
            '',
            '',
            'Date: ' . now()->toDateString(),
        ];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 12;
                $dataStartRow = 13;
                $dataEndRow = $dataStartRow + $this->records->count();
                $footerRow = $dataEndRow + 2;

                foreach (range('A', 'H') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(match ($column) {
                        'A' => 20,
                        'B' => 22,
                        'C' => 42,
                        default => 16,
                    });
                }

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');

                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E78'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D6E0EA']],
                    ],
                ]);

                foreach (['B4', 'B5', 'B6', 'B7', 'B8', 'B9', 'B10'] as $cell) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                $sheet->getStyle("A{$dataStartRow}:H{$dataEndRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                    ],
                ]);

                foreach (['D', 'E', 'F', 'H'] as $column) {
                    $sheet->getStyle("{$column}{$dataStartRow}:{$column}{$dataEndRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                $sheet->getStyle("A{$footerRow}:G{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->freezePane("A{$dataStartRow}");
                $sheet->setAutoFilter("A{$headerRow}:H{$headerRow}");
            },
        ];
    }
}
