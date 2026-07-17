<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SocietyLedgerSheetExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    protected Collection $rows;

    public function __construct(
        protected int $sheetNumber,
        protected array $sheet,
        protected array $filters,
    ) {
        $this->rows = collect($sheet['rows'] ?? []);
    }

    public function title(): string
    {
        return 'Member ' . $this->sheetNumber;
    }

    public function array(): array
    {
        $member = $this->sheet['member'] ?? ['name' => 'N/A', 'member_no' => 'N/A'];
        $rows = [
            ["Serial No: {$this->sheetNumber}", '', '', 'Date From:', $this->filters['start_date'] ?: 'Beginning', '', "Member: {$member['member_no']}", '', '', '', '', '', '', '', '', '', '', '', ''],
            ["Name: {$member['name']}", '', '', 'Date To:', $this->filters['end_date'], '', 'Signature:', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['', '', '', 'Reason:', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['Date', 'Particular', 'Ref', 'Shares', '', '', 'Savings', '', '', 'Loans', '', '', 'Loan Interest', 'Deposit', '', '', 'Authentication', '', ''],
            ['', '', '', 'DR', 'CR', 'BAL', 'DR', 'CR', 'BAL', 'DR', 'CR', 'BAL', '', 'DR', 'CR', 'BAL', 'DR', 'CR', 'BAL'],
        ];

        foreach ($this->rows as $row) {
            $rows[] = [
                $row['date'],
                $row['particular'],
                $row['reference'],
                $row['SHARES_debit'],
                $row['SHARES_credit'],
                $row['SHARES_balance'],
                $row['SAVINGS_debit'],
                $row['SAVINGS_credit'],
                $row['SAVINGS_balance'],
                $row['loan_debit'],
                $row['loan_credit'],
                $row['loan_balance'],
                $row['loan_interest'],
                $row['DEPOSIT_debit'],
                $row['DEPOSIT_credit'],
                $row['DEPOSIT_balance'],
                $row['AUTHENTICATION_debit'],
                $row['AUTHENTICATION_credit'],
                $row['AUTHENTICATION_balance'],
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(5 + $this->rows->count(), 6);

                $sheet->mergeCells('A1:C1');
                $sheet->mergeCells('D1:F1');
                $sheet->mergeCells('G1:K1');
                $sheet->mergeCells('A2:C2');
                $sheet->mergeCells('D2:F2');
                $sheet->mergeCells('G2:K2');
                $sheet->mergeCells('A3:C3');
                $sheet->mergeCells('D3:F3');
                $sheet->mergeCells('A4:A5');
                $sheet->mergeCells('B4:B5');
                $sheet->mergeCells('C4:C5');
                $sheet->mergeCells('D4:F4');
                $sheet->mergeCells('G4:I4');
                $sheet->mergeCells('J4:L4');
                $sheet->mergeCells('N4:P4');
                $sheet->mergeCells('Q4:S4');

                $sheet->getStyle('A1:S5')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                    ],
                ]);

                $sheet->getStyle('A4:S5')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E78'],
                    ],
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                ]);

                if ($this->rows->isNotEmpty()) {
                    $sheet->getStyle("A6:S{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                    ]);

                    foreach (range('D', 'S') as $column) {
                        $sheet->getStyle("{$column}6:{$column}{$lastRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }
                }

                $sheet->freezePane('A6');
            },
        ];
    }
}
