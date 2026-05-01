<?php

namespace App\Exports;

use App\Models\Branch;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InterestReportMemberSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    use Exportable;

    public function __construct(
        protected Branch $branch,
        protected array $filters,
        protected array $memberSheet,
        protected string $title,
        protected string $preparedBy,
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        $rows = [
            [strtoupper($this->branch->name) . ' - MEMBER INTEREST REPORT'],
            ['Member No: ' . $this->memberSheet['member_no']],
            ['Member Name: ' . $this->memberSheet['member_name']],
            ['Period: ' . ($this->filters['start_date'] ?: 'Beginning') . ' to ' . $this->filters['end_date']],
            [],
            ['Metric', 'Amount'],
            ['Brought Forward Interest', $this->memberSheet['interest_brought_forward']],
            ['Current Period Interest', $this->memberSheet['interest_current']],
            ['Total Interest To Date', $this->memberSheet['interest_total']],
            ['Outstanding Interest Balance', $this->memberSheet['outstanding_interest']],
            ['Loans In Scope', $this->memberSheet['loan_count']],
            ['Last Interest Date', $this->memberSheet['last_interest_date']],
            [],
            ['Payment Date', 'Loan ID', 'Interest Rate', 'Interest Paid', 'Outstanding Interest', 'Principal Paid', 'Total Payment', 'Carry Forward', 'Remarks'],
        ];

        foreach ($this->memberSheet['rows'] as $row) {
            $rows[] = [
                $row['paid_at'],
                $row['loan_id'],
                $row['interest_rate'],
                $row['interest_paid'],
                $row['outstanding_interest'],
                $row['repayment_amount'],
                $row['total_amount'],
                $row['carry_forward'],
                $row['remarks'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Prepared By: ' . strtoupper($this->preparedBy), '', '', 'Generated: ' . now()->toDateTimeString()];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 14;
                $dataStartRow = 15;
                $dataEndRow = $dataStartRow + max(count($this->memberSheet['rows']) - 1, 0);
                $footerRow = $dataEndRow + 2;

                foreach (range('A', 'I') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(match ($column) {
                        'A' => 16,
                        'B' => 18,
                        'C' => 14,
                        'D', 'E', 'F', 'G', 'H' => 18,
                        default => 32,
                    });
                }

                $sheet->mergeCells('A1:I1');
                $sheet->mergeCells('A2:I2');
                $sheet->mergeCells('A3:I3');
                $sheet->mergeCells('A4:I4');

                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:I4')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A6:B12')->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D6E0EA']],
                    ],
                ]);

                $sheet->getStyle('A6:B6')->applyFromArray($this->headerStyle());
                $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray($this->headerStyle());

                foreach (['B7', 'B8', 'B9', 'B10'] as $cell) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                if (count($this->memberSheet['rows']) > 0) {
                    $sheet->getStyle("A{$dataStartRow}:I{$dataEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                    ]);

                    foreach (['C', 'D', 'E', 'F', 'G', 'H'] as $column) {
                        $sheet->getStyle("{$column}{$dataStartRow}:{$column}{$dataEndRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }
                }

                $sheet->getStyle("A{$footerRow}:D{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->freezePane("A{$dataStartRow}");
                $sheet->setAutoFilter("A{$headerRow}:I{$headerRow}");
            },
        ];
    }

    protected function headerStyle(): array
    {
        return [
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
        ];
    }
}
