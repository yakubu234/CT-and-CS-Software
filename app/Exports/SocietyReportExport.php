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

class SocietyReportExport implements FromArray, ShouldAutoSize, WithEvents
{
    use Exportable;

    public function __construct(
        protected Branch $branch,
        protected Collection $rows,
        protected array $expenses,
        protected array $loans,
        protected array $summary,
        protected array $reconciliation,
        protected array $warnings,
        protected array $filters,
        protected string $preparedBy,
    ) {
    }

    public function array(): array
    {
        $rows = [
            ['Society Balance Report for ' . $this->branch->name],
            ['Period: ' . ($this->filters['start_date'] ?: 'Beginning') . ' -> ' . $this->filters['end_date']],
            ['Registration Number: ' . ($this->branch->registration_number ?: 'N/A')],
            [],
            ['Reconciliation'],
            ['Opening Balance', $this->reconciliation['opening_balance']],
            ['Member Account Credits', $this->reconciliation['member_account_credit']],
            ['Member Account Debits', $this->reconciliation['member_account_debit']],
            ['Income', $this->reconciliation['income']],
            ['Expenses', $this->reconciliation['expenses']],
            ['Loan Disbursements', $this->reconciliation['loan_disbursements']],
            ['Principal Repayments', $this->reconciliation['principal_repayments']],
            ['Interest Repayments', $this->reconciliation['interest_repayments']],
            ['Closing Balance', $this->reconciliation['closing_balance']],
            [],
            ['Date', 'Details', 'Debit', 'Credit', 'Balance'],
            ['', 'Brought Forward', '', '', $this->summary['brought_forward']],
        ];

        foreach ($this->rows as $row) {
            $rows[] = [
                '',
                $row['name'],
                $row['total_debit'],
                $row['total_credit'],
                $row['balance'],
            ];
        }

        $rows[] = ['', '', '', 'Sub Total', $this->summary['product_subtotal']];
        $rows[] = [
            '',
            'Brought Forward + Sub Total',
            $this->summary['product_subtotal'] . ' + ' . $this->summary['brought_forward'],
            '=',
            $this->summary['current_total'],
        ];
        $rows[] = ['', 'Expenses', $this->expenses['total_debit'], $this->expenses['total_credit'], ''];
        $rows[] = ['', 'Loans', $this->loans['total_debit'], $this->loans['total_credit'], ''];
        $rows[] = ['', 'Sum (Loan + Expenses)', $this->summary['loan_expense_debit'], $this->summary['loan_expense_credit'], ''];
        $rows[] = ['', '', '', 'Final Total', $this->summary['final_total']];
        if ($this->warnings !== []) {
            $rows[] = [];
            $rows[] = ['Warnings'];

            foreach ($this->warnings as $warning) {
                $rows[] = [$warning];
            }
        }
        $rows[] = [];
        $rows[] = [
            'Prepared By: ' . strtoupper($this->preparedBy),
            '',
            '',
            'Date: ' . now()->toDateString(),
            '',
        ];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = count($this->array());
                $headerRow = 16;

                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->mergeCells('A3:E3');

                $sheet->getStyle('A1:E1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:E3')->applyFromArray([
                    'font' => ['italic' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A5:E5')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EAF4EA'],
                    ],
                ]);

                foreach (range(6, 14) as $row) {
                    $sheet->getStyle("B{$row}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                $sheet->getStyle("A{$headerRow}:E{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E78'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D6E0EA']],
                    ],
                ]);

                $sheet->getStyle("A{$headerRow}:E{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                    ],
                ]);

                foreach (['C', 'D', 'E'] as $column) {
                    $sheet->getStyle("{$column}6:{$column}{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                $sheet->getStyle('A17:E17')->getFont()->setBold(true);
                $sheet->getStyle('A' . ($lastRow - 1) . ':E' . ($lastRow - 1))->getFont()->setBold(true);
                $sheet->freezePane('A17');
            },
        ];
    }
}
