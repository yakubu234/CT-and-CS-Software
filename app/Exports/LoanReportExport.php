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

class LoanReportExport implements FromArray, ShouldAutoSize, WithEvents
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
            [$this->branch->name . ' - Loan Report'],
            ['Period: ' . ($this->filters['start_date'] ?: 'Beginning') . ' - ' . $this->filters['end_date']],
            [],
            ['Summary'],
            ['Total Disbursed', $this->summary['total_disbursed'] ?? 0],
            ['Paid In Period', $this->summary['principal_paid_period'] ?? 0],
            ['Interest In Period', $this->summary['interest_paid_period'] ?? 0],
            ['Additional Loans', $this->summary['additional_loans'] ?? 0],
            ['Outstanding', $this->summary['outstanding_amount'] ?? 0],
            [],
            [
                'Loan ID',
                'Member',
                'Member No',
                'Release Date',
                'Due Date',
                'Original Loan',
                'Additional Loan',
                'Paid In Period',
                'Interest In Period',
                'Outstanding',
                'Last Payment',
                'Status',
            ],
        ];

        foreach ($this->loans as $loan) {
            $rows[] = [
                $loan['loan_id'],
                $loan['borrower_name'],
                $loan['member_no'],
                $loan['first_release_date'],
                $loan['due_date'],
                $loan['original_amount'],
                $loan['additional_amount'],
                $loan['principal_paid_period'],
                $loan['interest_paid_period'],
                $loan['outstanding_amount'],
                $loan['last_payment_date'],
                $loan['status'],
            ];
        }

        $rows[] = [];
        $rows[] = [
            'Prepared By: ' . strtoupper($this->preparedBy),
            '',
            '',
            'Active Loans: ' . ($this->summary['active_count'] ?? 0),
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
                $headerRow = 11;
                $dataStartRow = 12;
                $dataEndRow = $dataStartRow + $this->loans->count() - 1;
                $footerRow = $dataEndRow + 3;

                foreach (range('A', 'L') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(match ($column) {
                        'A' => 18,
                        'B' => 28,
                        'C' => 18,
                        default => 16,
                    });
                }

                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');
                $sheet->mergeCells('A4:B4');

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

                if ($this->loans->count() > 0) {
                    $sheet->getStyle("A{$dataStartRow}:L{$dataEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }

                foreach (['B5', 'B6', 'B7', 'B8', 'B9'] as $cell) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                if ($this->loans->count() > 0) {
                    foreach (['F', 'G', 'H', 'I', 'J'] as $column) {
                        $sheet->getStyle("{$column}{$dataStartRow}:{$column}{$dataEndRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }
                }

                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->freezePane("A{$dataStartRow}");
                $sheet->setAutoFilter("A{$headerRow}:L{$headerRow}");
            },
        ];
    }
}
