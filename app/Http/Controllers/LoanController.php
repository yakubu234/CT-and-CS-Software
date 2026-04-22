<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeclineLoanRequest;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Models\CustomField;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\LoanService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LoanController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected LoanService $loanService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing loans.']);
        }

        $loans = TableListing::paginate(
            Loan::query()
                ->with(['borrower.detail'])
                ->where('branch_id', $branch->id)
                ->where(function (Builder $query): void {
                    $query->where(function (Builder $activeQuery): void {
                        $activeQuery->whereRaw('CAST(COALESCE(amount_due, 0) AS DECIMAL(15,2)) > 0')
                            ->orWhere('status', 1);
                    });
                })
                ->when($request->filled('search'), function (Builder $query) use ($request): void {
                    $search = $request->string('search')->toString();
                    $query->where(function (Builder $builder) use ($search): void {
                        $builder->where('loan_id', 'like', '%' . $search . '%')
                            ->orWhereHas('borrower', function (Builder $borrowerQuery) use ($search): void {
                                $borrowerQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('last_name', 'like', '%' . $search . '%')
                                    ->orWhere('member_no', 'like', '%' . $search . '%')
                                    ->orWhereHas('detail', function (Builder $detailQuery) use ($search): void {
                                        $detailQuery->where('member_no', 'like', '%' . $search . '%');
                                    });
                            });
                    });
                })
                ->latest('id'),
            $request
        );

        return view('loans.index', [
            'branch' => $branch,
            'loans' => $loans,
        ]);
    }

    public function pending(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing pending loans.']);
        }

        $loanRequests = TableListing::paginate(
            LoanDetail::query()
                ->with(['loan.borrower.detail', 'borrower.detail', 'creator'])
                ->where('branch_id', $branch->id)
                ->where('decision_status', LoanDetail::STATUS_PENDING)
                ->when($request->filled('search'), function (Builder $query) use ($request): void {
                    $search = $request->string('search')->toString();
                    $query->whereHas('borrower', function (Builder $borrowerQuery) use ($search): void {
                        $borrowerQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('member_no', 'like', '%' . $search . '%')
                            ->orWhereHas('detail', function (Builder $detailQuery) use ($search): void {
                                $detailQuery->where('member_no', 'like', '%' . $search . '%');
                            });
                    });
                })
                ->latest('id'),
            $request
        );

        return view('loans.pending', [
            'branch' => $branch,
            'loanRequests' => $loanRequests,
            'branchLedgerBalance' => $this->loanService->branchLedgerBalance($branch),
        ]);
    }

    public function active(Request $request): View|RedirectResponse
    {
        return $this->index($request);
    }

    public function declined(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing declined loans.']);
        }

        $loanRequests = TableListing::paginate(
            LoanDetail::query()
                ->with(['loan.borrower.detail', 'borrower.detail', 'creator', 'decliner'])
                ->where('branch_id', $branch->id)
                ->where('decision_status', LoanDetail::STATUS_DECLINED)
                ->when($request->filled('search'), function (Builder $query) use ($request): void {
                    $search = $request->string('search')->toString();
                    $query->where(function (Builder $builder) use ($search): void {
                        $builder->where('decline_reason', 'like', '%' . $search . '%')
                            ->orWhereHas('borrower', function (Builder $borrowerQuery) use ($search): void {
                                $borrowerQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('last_name', 'like', '%' . $search . '%')
                                    ->orWhere('member_no', 'like', '%' . $search . '%')
                                    ->orWhereHas('detail', function (Builder $detailQuery) use ($search): void {
                                        $detailQuery->where('member_no', 'like', '%' . $search . '%');
                                    });
                            });
                    });
                })
                ->latest('id'),
            $request
        );

        return view('loans.declined', [
            'branch' => $branch,
            'loanRequests' => $loanRequests,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating a loan request.']);
        }

        $borrowers = User::query()
            ->with('detail')
            ->where('branch_id', (string) $branch->id)
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->where(function (Builder $query): void {
                $query->where('user_type', 'customer')
                    ->orWhere('society_exco', true)
                    ->orWhere('former_exco', true);
            })
            ->orderBy('name')
            ->get()
            ->map(function (User $borrower) use ($branch) {
                $outstanding = $this->loanService->currentOutstandingForBorrower($branch, $borrower);

                return [
                    'id' => $borrower->id,
                    'name' => $borrower->name,
                    'member_no' => $borrower->detail?->member_no ?: $borrower->member_no,
                    'outstanding' => $outstanding,
                    'label' => trim(($borrower->detail?->member_no ?: $borrower->member_no ?: 'N/A') . ' ' . $borrower->name),
                ];
            })
            ->values();

        return view('loans.create', [
            'branch' => $branch,
            'borrowers' => $borrowers,
            'customFields' => $this->loanCustomFields(),
            'interestWeekIntervals' => $this->interestWeekIntervals(),
        ]);
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating a loan request.']);
        }

        $borrower = User::query()
            ->where('id', $request->integer('borrower_id'))
            ->where('branch_id', (string) $branch->id)
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $detail = $this->loanService->createRequest($branch, $request->user(), $borrower, $request->validated());

        return redirect()
            ->route('loans.requests.show', $detail)
            ->with('status', 'Loan request created successfully and is now awaiting approval.');
    }

    public function show(Request $request, Loan $loan): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch && (int) $loan->branch_id === (int) $branch->id, 404);

        $loan->load([
            'borrower.detail',
            'details.creator',
            'details.approver',
            'details.decliner',
            'details.payments.user',
        ]);

        return view('loans.show', [
            'branch' => $branch,
            'loan' => $loan,
        ]);
    }

    public function showRequest(Request $request, LoanDetail $loanDetail): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch && (int) $loanDetail->branch_id === (int) $branch->id, 404);

        $loanDetail->load(['loan.borrower.detail', 'borrower.detail', 'creator', 'approver', 'decliner', 'payments']);

        return view('loans.requests.show', [
            'branch' => $branch,
            'loanDetail' => $loanDetail,
            'branchLedgerBalance' => $this->loanService->branchLedgerBalance($branch),
        ]);
    }

    public function edit(Request $request, LoanDetail $loanDetail): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch && (int) $loanDetail->branch_id === (int) $branch->id, 404);

        if (! $loanDetail->canBeEdited()) {
            return redirect()
                ->route('loans.requests.show', $loanDetail)
                ->withErrors(['loan' => 'This loan request can no longer be edited.']);
        }

        $loanDetail->load(['loan', 'borrower.detail', 'payments']);

        return view('loans.edit', [
            'branch' => $branch,
            'loanDetail' => $loanDetail,
            'borrower' => $loanDetail->borrower,
            'customFields' => $this->loanCustomFields(),
            'interestWeekIntervals' => $this->interestWeekIntervals(),
            'currentOutstanding' => (float) ($loanDetail->loan?->balanace ?? 0),
            'projectedOutstanding' => $this->loanService->projectedOutstandingForDetail($loanDetail),
            'totalRepaid' => $loanDetail->totalRepaymentMade(),
        ]);
    }

    public function update(UpdateLoanRequest $request, LoanDetail $loanDetail): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch && (int) $loanDetail->branch_id === (int) $branch->id, 404);

        try {
            $loanDetail = $this->loanService->updateRequest($branch, $request->user(), $loanDetail, $request->validated());
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['loan' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loans.requests.show', $loanDetail)
            ->with('status', 'Loan request updated successfully.');
    }

    public function approve(Request $request, LoanDetail $loanDetail): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch && (int) $loanDetail->branch_id === (int) $branch->id, 404);

        try {
            $loanDetail = $this->loanService->approve($branch, $request->user(), $loanDetail);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['loan' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loans.requests.show', $loanDetail)
            ->with('status', 'Loan request approved successfully.');
    }

    public function decline(DeclineLoanRequest $request, LoanDetail $loanDetail): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch && (int) $loanDetail->branch_id === (int) $branch->id, 404);

        try {
            $loanDetail = $this->loanService->decline($request->user(), $loanDetail, $request->validated('decline_reason'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['loan' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loans.requests.show', $loanDetail)
            ->with('status', 'Loan request declined successfully.');
    }

    public function destroy(Request $request, LoanDetail $loanDetail): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch && (int) $loanDetail->branch_id === (int) $branch->id, 404);

        $loanId = $loanDetail->loan_id;

        try {
            $this->loanService->deleteRequest($request->user(), $loanDetail);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['loan' => $exception->getMessage()]);
        }

        $loanStillExists = Loan::query()->whereKey($loanId)->exists();

        return redirect()
            ->route($loanStillExists ? 'loans.show' : 'loans.pending', $loanStillExists ? ['loan' => $loanId] : [])
            ->with('status', 'Loan request deleted successfully.');
    }

    protected function loanCustomFields()
    {
        return CustomField::query()
            ->where('table', 'loans')
            ->where('status', true)
            ->orderBy('order')
            ->orderBy('field_name')
            ->get();
    }

    protected function interestWeekIntervals(): array
    {
        return [
            'weekly' => 'Weekly',
            'every-2-weeks' => 'Every 2 weeks',
            'every-3-weeks' => 'Every 3 weeks',
            'monthly' => 'Monthly',
        ];
    }
}
