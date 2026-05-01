<?php

namespace App\Exports;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InterestReportSummarySheet implements FromArray, ShouldAutoSize, WithCharts, WithEvents, WithTitle
{
    use Exportable;

    public function __construct(
        protected Branch $branch,
        protected array $summary,
        protected array $filters,
        protected Collection $weeklyTrend,
        protected Collection $memberSheets,
        protected string $preparedBy,
    ) {
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function array(): array
    {
        $periodStart = $this->filters['start_date'] ?: 'Beginning';
        $periodEnd = $this->filters['end_date'];

        $rows = [
            [strtoupper($this->branch->name) . ' - INTEREST REPORT'],
            ['Prepared For: ' . strtoupper($this->branch->name)],
            ['Period: ' . $periodStart . ' to ' . $periodEnd],
            [],
            ['Metric', 'Amount'],
            ['Brought Forward Interest', $this->summary['interest_brought_forward'] ?? 0],
            ['Current Period Interest', $this->summary['interest_current'] ?? 0],
            ['Total Interest To Date', $this->summary['interest_total'] ?? 0],
            ['Outstanding Interest Balance', $this->summary['outstanding_interest'] ?? 0],
            ['Members In Scope', $this->summary['member_count'] ?? 0],
            [],
            ['Week By Week Trend'],
            ['Week', 'Week Start', 'Week End', 'Interest Total'],
        ];

        foreach ($this->weeklyTrend as $week) {
            $rows[] = [
                $week['week_label'],
                $week['week_start'],
                $week['week_end'],
                $week['total_interest'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Member Summary'];
        $rows[] = ['Member No', 'Member Name', 'B/F Interest', 'Current Interest', 'Total Interest', 'Outstanding Interest', 'Loans', 'Last Interest Date'];

        foreach ($this->memberSheets as $memberSheet) {
            $rows[] = [
                $memberSheet['member_no'],
                $memberSheet['member_name'],
                $memberSheet['interest_brought_forward'],
                $memberSheet['interest_current'],
                $memberSheet['interest_total'],
                $memberSheet['outstanding_interest'],
                $memberSheet['loan_count'],
                $memberSheet['last_interest_date'],
            ];
        }

        $rows[] = [];
        $rows[] = ['Prepared By: ' . strtoupper($this->preparedBy), '', '', 'Generated: ' . now()->toDateTimeString()];

        return $rows;
    }

    public function charts(): array
    {
        if ($this->weeklyTrend->isEmpty()) {
            return [];
        }

        $startRow = 14;
        $endRow = $startRow + $this->weeklyTrend->count() - 1;

        $labels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Summary'!\$D\$13", null, 1),
        ];

        $categories = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Summary'!\$A\${$startRow}:\$A\${$endRow}", null, $this->weeklyTrend->count()),
        ];

        $values = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Summary'!\$D\${$startRow}:\$D\${$endRow}", null, $this->weeklyTrend->count()),
        ];

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, count($values) - 1),
            $labels,
            $categories,
            $values
        );

        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $chart = new Chart(
            'weekly_interest_chart',
            new Title('Weekly Interest Collection'),
            new Legend(Legend::POSITION_RIGHT, null, false),
            new PlotArea(null, [$series]),
            true,
            0,
            new Title('Week'),
            new Title('Interest')
        );

        $chart->setTopLeftPosition('F5');
        $chart->setBottomRightPosition('M20');

        return [$chart];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $weeklyHeaderRow = 13;
                $weeklyDataStartRow = 14;
                $weeklyDataEndRow = $weeklyDataStartRow + max($this->weeklyTrend->count() - 1, 0);
                $memberHeaderRow = $weeklyDataEndRow + 4;
                $memberTableHeaderRow = $memberHeaderRow + 1;
                $memberDataStartRow = $memberTableHeaderRow + 1;
                $memberDataEndRow = $memberDataStartRow + max($this->memberSheets->count() - 1, 0);
                $footerRow = $memberDataEndRow + 3;

                foreach (range('A', 'H') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(match ($column) {
                        'A' => 24,
                        'B' => 20,
                        'C' => 16,
                        'D' => 18,
                        default => 17,
                    });
                }

                foreach (range('I', 'M') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(16);
                }

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');
                $sheet->mergeCells('A12:D12');
                $sheet->mergeCells("A{$memberHeaderRow}:H{$memberHeaderRow}");

                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2:H3')->applyFromArray([
                    'font' => ['italic' => true, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A5:B10')->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D6E0EA']],
                    ],
                ]);

                $sheet->getStyle('A5:B5')->applyFromArray($this->headerStyle());
                $sheet->getStyle("A{$weeklyHeaderRow}:D{$weeklyHeaderRow}")->applyFromArray($this->headerStyle());
                $sheet->getStyle("A{$memberTableHeaderRow}:H{$memberTableHeaderRow}")->applyFromArray($this->headerStyle());

                foreach (['B6', 'B7', 'B8', 'B9'] as $cell) {
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0.00');
                }

                if ($this->weeklyTrend->isNotEmpty()) {
                    $sheet->getStyle("A{$weeklyDataStartRow}:D{$weeklyDataEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                    ]);
                    $sheet->getStyle("D{$weeklyDataStartRow}:D{$weeklyDataEndRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                if ($this->memberSheets->isNotEmpty()) {
                    $sheet->getStyle("A{$memberDataStartRow}:H{$memberDataEndRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']],
                        ],
                    ]);

                    foreach (['C', 'D', 'E', 'F'] as $column) {
                        $sheet->getStyle("{$column}{$memberDataStartRow}:{$column}{$memberDataEndRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }
                }

                $sheet->getStyle("A{$footerRow}:D{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $sheet->freezePane('A14');
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
