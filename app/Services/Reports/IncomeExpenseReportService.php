<?php

namespace App\Services\Reports;

use App\Models\Branch;
use App\Models\Transaction;
use App\Support\TableListing;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IncomeExpenseReportService
{
    public function build(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $periodQuery = $this->periodQuery($branch, $request, $startDate, $endDate)
            ->orderBy('trans_date')
            ->orderBy('id');

        $records = TableListing::paginate($periodQuery, $request, 10);
        $summary = $this->summary($branch, $request, $startDate, $endDate);

        return [
            'records' => $records,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate->toDateString(),
                'category' => $request->string('category')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ];
    }

    public function buildExportData(Branch $branch, Request $request): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $query = $this->periodQuery($branch, $request, $startDate, $endDate)
            ->orderBy('trans_date')
            ->orderBy('id');

        $records = $query->get()->map(function (Transaction $record) {
            return [
                'date' => optional($record->trans_date)?->format('Y-m-d H:i:s'),
                'type' => $record->type ?: 'N/A',
                'description' => $record->description ?: $record->type ?: 'N/A',
                'credit' => strtolower((string) $record->dr_cr) === 'cr' ? round((float) $record->amount, 2) : 0,
                'debit' => strtolower((string) $record->dr_cr) === 'dr' ? round((float) $record->amount, 2) : 0,
                'balance' => round((float) data_get($record->transaction_details, 'balance_after', 0), 2),
                'status' => $this->statusLabel((int) ($record->status ?? 0)),
                'running_net' => round((float) data_get($record->transaction_details, 'balance_after', 0), 2),
            ];
        })->values();

        return [
            'branch' => $branch,
            'records' => $records,
            'summary' => $this->summary($branch, $request, $startDate, $endDate),
            'filters' => [
                'start_date' => $startDate?->toDateString(),
                'end_date' => $endDate->toDateString(),
                'category' => $request->string('category')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ];
    }

    public function categoryOptions(Branch $branch): Collection
    {
        return Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->filter()
            ->values();
    }

    protected function baseQuery(Branch $branch, Request $request): Builder
    {
        $query = Transaction::query()
            ->where('branch_id', $branch->id)
            ->where('is_branch', true)
            ->whereNull('deleted_at');

        $category = trim((string) $request->input('category'));
        if ($category !== '') {
            $query->where('type', $category);
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('type', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('dr_cr', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    protected function periodQuery(Branch $branch, Request $request, ?Carbon $startDate, Carbon $endDate): Builder
    {
        $query = $this->baseQuery($branch, $request)
            ->where('trans_date', '<=', $endDate);

        if ($startDate) {
            $query->where('trans_date', '>=', $startDate);
        }

        return $query;
    }

    protected function summary(Branch $branch, Request $request, ?Carbon $startDate, Carbon $endDate): array
    {
        $broughtForwardQuery = $this->baseQuery($branch, $request);

        if ($startDate) {
            $broughtForwardQuery->where('trans_date', '<', $startDate);
        } else {
            $broughtForwardQuery->whereRaw('1 = 0');
        }

        $currentPeriodQuery = $this->periodQuery($branch, $request, $startDate, $endDate);

        $bfCredit = $this->sumByDrCr(clone $broughtForwardQuery, 'cr');
        $bfDebit = $this->sumByDrCr(clone $broughtForwardQuery, 'dr');
        $currentCredit = $this->sumByDrCr(clone $currentPeriodQuery, 'cr');
        $currentDebit = $this->sumByDrCr(clone $currentPeriodQuery, 'dr');

        $bfNet = round($bfCredit - $bfDebit, 2);
        $currentNet = round($currentCredit - $currentDebit, 2);
        $closingBalance = round($bfNet + $currentNet, 2);

        return [
            'bf_credit' => $bfCredit,
            'bf_debit' => $bfDebit,
            'bf_net' => $bfNet,
            'current_credit' => $currentCredit,
            'current_debit' => $currentDebit,
            'current_net' => $currentNet,
            'closing_balance' => $closingBalance,
        ];
    }

    protected function sumByDrCr(Builder $query, string $drCr): float
    {
        return round(
            (float) $query->whereRaw('LOWER(dr_cr) = ?', [strtolower($drCr)])->sum('amount'),
            2
        );
    }

    protected function statusLabel(int $status): string
    {
        return match ($status) {
            2 => 'Completed',
            1 => 'Pending',
            default => 'Draft',
        };
    }

    protected function resolveDateRange(Request $request): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse((string) $request->input('start_date'))->startOfDay()
            : null;

        $endDate = $request->filled('end_date')
            ? Carbon::parse((string) $request->input('end_date'))->endOfDay()
            : now()->endOfDay();

        return [$startDate, $endDate];
    }
}
