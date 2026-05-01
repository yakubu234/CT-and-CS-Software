<?php

namespace App\Exports;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InterestReportExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        $sheets = [
            new InterestReportSummarySheet(
                $this->branch,
                $this->summary,
                $this->filters,
                $this->weeklyTrend,
                $this->memberSheets,
                $this->preparedBy
            ),
        ];

        $usedTitles = ['Summary'];

        foreach ($this->memberSheets as $memberSheet) {
            $title = $this->uniqueSheetTitle($memberSheet['member_no'] ?? ('MEMBER-' . $memberSheet['member_id']), $usedTitles);
            $usedTitles[] = $title;

            $sheets[] = new InterestReportMemberSheet(
                $this->branch,
                $this->filters,
                $memberSheet,
                $title,
                $this->preparedBy
            );
        }

        return $sheets;
    }

    protected function uniqueSheetTitle(string $memberNo, array $usedTitles): string
    {
        $base = preg_replace('/[\[\]\*\/\\\\\?:]+/', '-', trim($memberNo)) ?: 'Member';
        $base = mb_substr($base, 0, 31);
        $candidate = $base;
        $counter = 2;

        while (in_array($candidate, $usedTitles, true)) {
            $suffix = '-' . $counter;
            $candidate = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
            $counter++;
        }

        return $candidate;
    }
}
