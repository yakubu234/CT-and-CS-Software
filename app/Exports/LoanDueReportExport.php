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

class LoanDueReportExport implements FromArray, ShouldAutoSize, WithEvents
{
    use Exportable;

    public function __construct(
        protected Branch $branch,
        protected Collection $loans,
        protected array $summary,
        protected array $filters,
        protected string $preparedBy,
    ) {
    }

    public function array(): array
    {
        $rows = [
            [$this->branch->name . ' - Loan Due Report'],
            ['Period: ' . ($this->filters['start_date'] ?: 'Beginning') . ' - ' . $this->filters['end_date']],
            [],
            ['Total Amount', $this->summary['total_amount'] ?? 0],
            ['Total Paid', $this->summary['total_paid'] ?? 0],
            ['Outstanding', $this->summary['outstanding'] ?? 0],
            [],
            [
                'Loan ID',
                'Branch',
                'Member Name',
                'Member No',
                'Member Phone',
                'Approval Date',
                'Approved By',
                'Interest',
                'Total Amount',
                'Total Paid',
                'Due Date',
                'Balance',
            ],
        ];

        foreach ($this->loans as $loan) {
            $rows[] = [
                $loan['loan_id'],
                $this->branch->name,
                $loan['borrower_name'],
                $loan['member_no'],
                $loan['member_phone'],
                $loan['approval_date'],
                $loan['approved_by_name'],
                $loan['interest_rate'],
                $loan['total_amount'],
                $loan['total_paid'],
                $loan['due_date'],
                $loan['outstanding_amount'],
            ];
        }

        $rows[] = [];
        $rows[] = [
            'Prepared By: ' . strtoupper($this->preparedBy),
            '',
            '',
            'Loans: ' . ($this->summary['loan_count'] ?? 0),
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
                $headerRow = 8;
                $dataStartRow = 9;
                $dataEndRow = $dataStartRow + $this->loans->count() - 1;
                $footerRow = $dataEndRow + 3;

                foreach (range('A', 'L') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(match ($column) {
                        'A' => 18,
                        'B' => 24,
                        'C' => 26,
                        'D' => 18,
                        'E' => 18,
                        'G' => 20,
                        default => 16,
                    });
                }

                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');

                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:L2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A{$headerRow}:L{$headerRow}")->applyFromArray([
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

                foreach (['B4', 'B5', 'B6'] as $cell) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                if ($this->loans->count() > 0) {
                    $sheet->getStyle("A{$dataStartRow}:L{$dataEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                    ]);

                    foreach (['H', 'I', 'J', 'L'] as $column) {
                        $sheet->getStyle("{$column}{$dataStartRow}:{$column}{$dataEndRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }
                }

                $sheet->getStyle("A{$footerRow}:G{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->freezePane("A{$dataStartRow}");
                $sheet->setAutoFilter("A{$headerRow}:L{$headerRow}");
            },
        ];
    }
}
