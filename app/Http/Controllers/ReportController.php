<?php

namespace App\Http\Controllers;

use App\Exports\MemberBalanceReportExport;
use App\Services\ActiveBranchService;
use App\Services\Exports\ExcelDownloadService;
use App\Services\Reports\MemberBalanceReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected MemberBalanceReportService $memberBalanceReportService,
        protected ExcelDownloadService $excelDownloadService,
    ) {
    }

    public function memberBalance(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing reports.']);
        }

        $report = $this->memberBalanceReportService->build($branch, $request);

        return view('reports.member-balance', [
            'branch' => $branch,
            'members' => $report['members'],
            'summaryCards' => $report['summary'],
            'filters' => $report['filters'],
            'memberOptions' => $this->memberBalanceReportService->memberOptions($branch),
        ]);
    }

    public function exportMemberBalance(Request $request)
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before exporting reports.']);
        }

        $report = $this->memberBalanceReportService->buildExportData($branch, $request);

        $filename = sprintf(
            '%s Member Balance from %s to %s.xlsx',
            preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $branch->name),
            str_replace(':', '_', $report['filters']['start_date'] ?: 'Beginning'),
            str_replace(':', '_', $report['filters']['end_date'])
        );

        return $this->excelDownloadService->download(
            new MemberBalanceReportExport(
                $branch,
                $report['members'],
                $report['summary'],
                $report['filters'],
                $request->user()?->name ?? 'SYSTEM'
            ),
            $filename
        );
    }

    public function loanReport(): View
    {
        return $this->placeholder('Loan Report');
    }

    public function loanDueReport(): View
    {
        return $this->placeholder('Loan Due Report');
    }

    public function societyLedgerReport(): View
    {
        return $this->placeholder('Society Ledger Report');
    }

    public function incomeExpenseReport(): View
    {
        return $this->placeholder('Income & Expense Report');
    }

    public function societyReport(): View
    {
        return $this->placeholder('Society Report');
    }

    protected function placeholder(string $title): View
    {
        return view('reports.placeholder', [
            'reportTitle' => $title,
        ]);
    }
}
