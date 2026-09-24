<?php

namespace App\Exports;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InactiveMembersFinancialReportExport implements FromArray, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Branch $branch,
        protected Collection $rows,
        protected array $totals,
        protected string $preparedBy,
    ) {
    }

    public function array(): array
    {
        $data = [
            [$this->branch->name . ' - Inactive / Archived Members Financial Report'],
            ['Generated: ' . now()->format('d M Y, h:i A')],
            [],
            ['Member ID', 'Member No', 'Name', 'Savings', 'Shares', 'Building Fund', 'Authentication', 'Deposits', 'Other Accounts', 'Account Total', 'Loan Amount', 'Loan Repayment', 'Outstanding Loan', 'Net Position'],
        ];

        foreach ($this->rows as $row) {
            $other = collect($row['other'])->map(fn ($amount, $name): string => $name . ': ' . number_format((float) $amount, 2))->implode('; ');
            $data[] = [$row['id'], $row['member_no'], $row['name'], $row['savings'], $row['shares'], $row['building_fund'], $row['authentication'], $row['deposits'], $other, $row['account_total'], $row['loan_amount'], $row['loan_repayment'], $row['outstanding_loan'], $row['net_position']];
        }

        $data[] = ['', '', 'TOTAL', '', '', '', '', '', '', $this->totals['account_total'], '', '', $this->totals['outstanding_loan'], $this->totals['net_position']];
        $data[] = [];
        $data[] = ['Prepared By: ' . strtoupper($this->preparedBy)];

        return $data;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $lastDataRow = 4 + $this->rows->count();
            $totalRow = $lastDataRow + 1;
            $sheet->mergeCells('A1:N1');
            $sheet->mergeCells('A2:N2');
            $sheet->getStyle('A1:N1')->applyFromArray(['font' => ['bold' => true, 'size' => 16], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $sheet->getStyle('A2:N2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A4:N4')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            if ($this->rows->isNotEmpty()) {
                $sheet->getStyle("A5:N{$lastDataRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
            $sheet->getStyle("A{$totalRow}:N{$totalRow}")->getFont()->setBold(true);
            foreach (range('D', 'N') as $column) {
                if ($column !== 'I' && $this->rows->isNotEmpty()) {
                    $sheet->getStyle("{$column}5:{$column}{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
            $sheet->freezePane('A5');
            $sheet->setAutoFilter('A4:N4');
        }];
    }
}
