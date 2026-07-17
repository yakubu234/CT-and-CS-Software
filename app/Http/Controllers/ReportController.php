<?php

namespace App\Http\Controllers;

use App\Exports\MemberBalanceReportExport;
use App\Exports\LoanDueReportExport;
use App\Exports\LoanReportExport;
use App\Exports\IncomeExpenseReportExport;
use App\Exports\InterestReportExport;
use App\Exports\SocietyLedgerReportExport;
use App\Exports\SocietyReportExport;
use App\Services\ActiveBranchService;
use App\Services\Exports\ExcelDownloadService;
use App\Services\Reports\IncomeExpenseReportService;
use App\Services\Reports\InterestReportService;
use App\Services\Reports\LoanDueReportService;
use App\Services\Reports\MemberBalanceReportService;
use App\Services\Reports\LoanReportService;
use App\Services\Reports\SocietyLedgerReportService;
use App\Services\Reports\SocietyReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected MemberBalanceReportService $memberBalanceReportService,
        protected LoanReportService $loanReportService,
        protected LoanDueReportService $loanDueReportService,
        protected IncomeExpenseReportService $incomeExpenseReportService,
        protected InterestReportService $interestReportService,
        protected SocietyLedgerReportService $societyLedgerReportService,
        protected SocietyReportService $societyReportService,
        protected ExcelDownloadService $excelDownloadService,
    ) {
        $this->middleware('module:reports');
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

    public function loanReport(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing reports.']);
        }

        $report = $this->loanReportService->build($branch, $request);

        return view('reports.loan-report', [
            'branch' => $branch,
            'loans' => $report['loans'],
            'summaryCards' => $report['summary'],
            'filters' => $report['filters'],
            'memberOptions' => $this->loanReportService->memberOptions($branch),
            'summaryMeta' => $report['summary_meta'],
        ]);
    }

    public function exportLoanReport(Request $request)
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before exporting reports.']);
        }

        $report = $this->loanReportService->buildExportData($branch, $request);

        $filename = sprintf(
            '%s Loan Report %s to %s.xlsx',
            preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $branch->name),
            str_replace(':', '_', $report['filters']['start_date'] ?: 'Beginning'),
            str_replace(':', '_', $report['filters']['end_date'])
        );

        return $this->excelDownloadService->download(
            new LoanReportExport(
                $branch,
                $report['loans'],
                $report['summary'],
                $report['filters'],
                $request->user()?->name ?? 'SYSTEM'
            ),
            $filename
        );
    }

    public function loanDueReport(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing reports.']);
        }

        $report = $this->loanDueReportService->build($branch, $request);

        return view('reports.loan-due-report', [
            'branch' => $branch,
            'loans' => $report['loans'],
            'summaryCards' => $report['summary'],
            'filters' => $report['filters'],
            'memberOptions' => $this->loanDueReportService->memberOptions($branch),
            'summaryMeta' => $report['summary_meta'],
        ]);
    }

    public function exportLoanDueReport(Request $request)
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before exporting reports.']);
        }

        $report = $this->loanDueReportService->buildExportData($branch, $request);

        $filename = sprintf(
            '%s Loan Due %s to %s.xlsx',
            preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $branch->name),
            str_replace(':', '_', $report['filters']['start_date'] ?: 'Beginning'),
            str_replace(':', '_', $report['filters']['end_date'])
        );

        return $this->excelDownloadService->download(
            new LoanDueReportExport(
                $branch,
                $report['loans'],
                $report['summary'],
                $report['filters'],
                $request->user()?->name ?? 'SYSTEM'
            ),
            $filename
        );
    }

    public function societyLedgerReport(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing reports.']);
        }

        $report = $this->societyLedgerReportService->build($branch, $request);

        return view('reports.society-ledger-report', [
            'branch' => $branch,
            'records' => $report['records'],
            'memberLedgers' => $report['member_ledgers'],
            'summary' => $report['summary'],
            'filters' => $report['filters'],
            'memberOptions' => $this->societyLedgerReportService->memberOptions($branch),
        ]);
    }

    public function exportSocietyLedgerReport(Request $request)
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before exporting reports.']);
        }

        $report = $this->societyLedgerReportService->buildExportData($branch, $request);

        $filename = sprintf(
            '%s Society Ledger %s to %s.xlsx',
            preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $branch->name),
            str_replace(':', '_', $report['filters']['start_date'] ?: 'Beginning'),
            str_replace(':', '_', $report['filters']['end_date'])
        );

        return $this->excelDownloadService->download(
            new SocietyLedgerReportExport(
                $report['sheets'],
                $report['filters']
            ),
            $filename
        );
    }

    public function incomeExpenseReport(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing reports.']);
        }

        $report = $this->incomeExpenseReportService->build($branch, $request);

        return view('reports.income-expense-report', [
            'branch' => $branch,
            'records' => $report['records'],
            'filters' => $report['filters'],
            'summary' => $report['summary'],
            'categoryOptions' => $this->incomeExpenseReportService->categoryOptions($branch),
        ]);
    }

    public function exportIncomeExpenseReport(Request $request)
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before exporting reports.']);
        }

        $report = $this->incomeExpenseReportService->buildExportData($branch, $request);

        $filename = sprintf(
            '%s Income and Expenses Report %s to %s.xlsx',
            preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $branch->name),
            str_replace(':', '_', $report['filters']['start_date'] ?: 'Beginning'),
            str_replace(':', '_', $report['filters']['end_date'])
        );

        return $this->excelDownloadService->download(
            new IncomeExpenseReportExport(
                $branch,
                $report['records'],
                $report['summary'],
                $report['filters'],
                $request->user()?->name ?? 'SYSTEM'
            ),
            $filename
        );
    }

    public function societyReport(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing reports.']);
        }

        $report = $this->societyReportService->build($branch, $request);

        return view('reports.society-report', [
            'branch' => $branch,
            'rows' => $report['rows'],
            'expenses' => $report['expenses'],
            'loans' => $report['loans'],
            'summary' => $report['summary'],
            'reconciliation' => $report['reconciliation'],
            'warnings' => $report['warnings'],
            'filters' => $report['filters'],
        ]);
    }

    public function exportSocietyReport(Request $request)
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before exporting reports.']);
        }

        $report = $this->societyReportService->buildExportData($branch, $request);

        $filename = sprintf(
            '%s Society Report %s to %s.xlsx',
            preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $branch->name),
            str_replace(':', '_', $report['filters']['start_date'] ?: 'Beginning'),
            str_replace(':', '_', $report['filters']['end_date'])
        );

        return $this->excelDownloadService->download(
            new SocietyReportExport(
                $branch,
                $report['rows'],
                $report['expenses'],
                $report['loans'],
                $report['summary'],
                $report['reconciliation'],
                $report['warnings'],
                $report['filters'],
                $request->user()?->name ?? 'SYSTEM'
            ),
            $filename
        );
    }

    public function interestReport(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing reports.']);
        }

        $report = $this->interestReportService->build($branch, $request);

        return view('reports.interest-report', [
            'branch' => $branch,
            'members' => $report['members'],
            'summary' => $report['summary'],
            'filters' => $report['filters'],
            'weeklyTrend' => $report['weekly_trend'],
            'memberOptions' => $this->interestReportService->memberOptions($branch),
        ]);
    }

    public function exportInterestReport(Request $request)
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before exporting reports.']);
        }

        $report = $this->interestReportService->buildExportData($branch, $request);

        $filename = sprintf(
            '%s Interest Report %s to %s.xlsx',
            preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $branch->name),
            str_replace(':', '_', $report['filters']['start_date'] ?: 'Beginning'),
            str_replace(':', '_', $report['filters']['end_date'])
        );

        return $this->excelDownloadService->download(
            new InterestReportExport(
                $branch,
                $report['summary'],
                $report['filters'],
                $report['weekly_trend'],
                $report['member_sheets'],
                $request->user()?->name ?? 'SYSTEM'
            ),
            $filename
        );
    }

    protected function placeholder(string $title): View
    {
        return view('reports.placeholder', [
            'reportTitle' => $title,
        ]);
    }
}
