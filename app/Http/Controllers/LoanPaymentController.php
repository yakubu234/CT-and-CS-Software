<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanPaymentRequest;
use App\Http\Requests\UpdateLoanPaymentRequest;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Services\ActiveBranchService;
use App\Services\LoanPaymentService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LoanPaymentController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected LoanPaymentService $loanPaymentService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing loan repayments.']);
        }

        $query = $this->loanPaymentService->repaymentListQuery($branch);

        $search = $request->string('search')->toString();
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('remarks', 'like', '%' . $search . '%')
                    ->orWhereHas('loan', function (Builder $loanQuery) use ($search): void {
                        $loanQuery->where('loan_id', 'like', '%' . $search . '%')
                            ->orWhereHas('borrower', function (Builder $borrowerQuery) use ($search): void {
                                $borrowerQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('last_name', 'like', '%' . $search . '%')
                                    ->orWhere('member_no', 'like', '%' . $search . '%')
                                    ->orWhereHas('detail', function (Builder $detailQuery) use ($search): void {
                                        $detailQuery->where('member_no', 'like', '%' . $search . '%');
                                    });
                            });
                    });
            });
        }

        $repayments = TableListing::paginate($query, $request);

        return view('loan-payments.index', [
            'branch' => $branch,
            'repayments' => $repayments,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating a loan repayment.']);
        }

        $loans = $this->loanPaymentService->activeLoansForBranch($branch);

        return view('loan-payments.create', [
            'branch' => $branch,
            'loans' => $loans,
        ]);
    }

    public function store(StoreLoanPaymentRequest $request): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating a loan repayment.']);
        }

        $loan = Loan::query()
            ->where('branch_id', $branch->id)
            ->findOrFail($request->integer('loan_id'));

        try {
            $repayment = $this->loanPaymentService->create($branch, $request->user(), $loan, $request->validated());
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['loan_payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loan-payments.show', $repayment)
            ->with('status', 'Loan repayment recorded successfully.');
    }

    public function show(Request $request, LoanPayment $loanPayment): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && $loanPayment->loan
            && (int) $loanPayment->loan->branch_id === (int) $branch->id
            && $loanPayment->deleted_at === null,
            404
        );

        $loanPayment->load([
            'loan.borrower.detail',
            'detail',
            'user',
            'principalTransaction',
            'interestTransaction',
        ]);

        return view('loan-payments.show', [
            'branch' => $branch,
            'repayment' => $loanPayment,
        ]);
    }

    public function edit(Request $request, LoanPayment $loanPayment): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && $loanPayment->loan
            && (int) $loanPayment->loan->branch_id === (int) $branch->id
            && $loanPayment->deleted_at === null,
            404
        );

        $loanPayment->load(['loan.borrower.detail', 'detail']);
        $context = $this->loanPaymentService->repaymentContext(
            $loanPayment->loan,
            old('paid_at', optional($loanPayment->paid_at)->format('Y-m-d')),
            $loanPayment
        );

        return view('loan-payments.edit', [
            'branch' => $branch,
            'repayment' => $loanPayment,
            'loan' => $loanPayment->loan,
            'context' => $context,
        ]);
    }

    public function update(UpdateLoanPaymentRequest $request, LoanPayment $loanPayment): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && $loanPayment->loan
            && (int) $loanPayment->loan->branch_id === (int) $branch->id
            && $loanPayment->deleted_at === null,
            404
        );

        try {
            $loanPayment = $this->loanPaymentService->update($branch, $request->user(), $loanPayment, $request->validated());
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['loan_payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loan-payments.show', $loanPayment)
            ->with('status', 'Loan repayment updated successfully.');
    }

    public function destroy(Request $request, LoanPayment $loanPayment): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && $loanPayment->loan
            && (int) $loanPayment->loan->branch_id === (int) $branch->id
            && $loanPayment->deleted_at === null,
            404
        );

        try {
            $this->loanPaymentService->delete($request->user(), $loanPayment);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['loan_payment' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loan-payments.index')
            ->with('status', 'Loan repayment deleted successfully.');
    }
}
