<?php

namespace App\Exports;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MemberBalanceReportExport implements FromArray, ShouldAutoSize, WithEvents
{
    use Exportable;

    public function __construct(
        protected Branch $branch,
        protected Collection $members,
        protected array $summary,
        protected array $filters,
        protected string $preparedBy,
    ) {
    }

    public function array(): array
    {
        $rows = [
            [$this->branch->name . ' - Member Balance'],
            ['Date Range: ' . ($this->filters['start_date'] ?: 'Beginning') . ' - ' . $this->filters['end_date']],
            [],
            [
                'Member Name',
                'Member No',
                'Loan B/F',
                'Loan Current',
                'Shares B/F',
                'Shares Current',
                'Auth B/F',
                'Auth Current',
                'Deposit B/F',
                'Deposit Current',
                'Savings B/F',
                'Savings Current',
            ],
        ];

        foreach ($this->members as $member) {
            $rows[] = [
                $member['member_name'],
                $member['member_no'],
                $member['loan_opening'],
                $member['loan_current'],
                $member['shares_opening'],
                $member['shares_current'],
                $member['auth_opening'],
                $member['auth_current'],
                $member['deposit_opening'],
                $member['deposit_current'],
                $member['savings_opening'],
                $member['savings_current'],
            ];
        }

        $rows[] = [
            'TOTAL (' . $this->members->count() . ' Members)',
            '',
            $this->summary['loan_opening'],
            $this->summary['loan_current'],
            $this->summary['shares_opening'],
            $this->summary['shares_current'],
            $this->summary['auth_opening'],
            $this->summary['auth_current'],
            $this->summary['deposit_opening'],
            $this->summary['deposit_current'],
            $this->summary['savings_opening'],
            $this->summary['savings_current'],
        ];

        $rows[] = [];
        $rows[] = [
            'Prepared By: ' . strtoupper($this->preparedBy),
            '',
            '',
            'Approved By: ___________________',
            '',
            '',
            'Date: ' . now()->toDateString(),
        ];
        $rows[] = ['', '', '', '', '', '', ''];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = 4 + $this->members->count();
                $totalRow = $lastDataRow + 1;
                $footerRow = $totalRow + 2;
                $signatureRow = $footerRow + 1;

                foreach (range('A', 'L') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(match ($column) {
                        'A' => 32,
                        'B' => 18,
                        default => 16,
                    });
                }

                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');
                $sheet->mergeCells("A{$footerRow}:C{$footerRow}");
                $sheet->mergeCells("D{$footerRow}:F{$footerRow}");
                $sheet->mergeCells("G{$footerRow}:I{$footerRow}");
                $sheet->mergeCells("A{$signatureRow}:C{$signatureRow}");
                $sheet->mergeCells("D{$signatureRow}:F{$signatureRow}");
                $sheet->mergeCells("G{$signatureRow}:I{$signatureRow}");

                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A2:L2')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A4:L4')->applyFromArray([
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

                if ($this->members->count() > 0) {
                    $sheet->getStyle("A5:L{$lastDataRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    foreach (range(5, $lastDataRow) as $row) {
                        if ($row % 2 === 0) {
                            $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8FBFF'],
                                ],
                            ]);
                        }
                    }
                }

                $sheet->getStyle("A{$totalRow}:L{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EAF4EA'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B8C4D0']],
                    ],
                ]);

                $sheet->getStyle("A{$footerRow}:I{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->getStyle("A{$signatureRow}:I{$signatureRow}")->applyFromArray([
                    'borders' => [
                        'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                    ],
                ]);

                foreach (range('C', 'L') as $column) {
                    $sheet->getStyle("{$column}5:{$column}{$totalRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                $sheet->freezePane('A5');
                $sheet->setAutoFilter("A4:L{$lastDataRow}");

                for ($row = 1; $row <= $signatureRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }
            },
        ];
    }
}
