<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncomeExpenseRequest;
use App\Http\Requests\UpdateIncomeExpenseRequest;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Services\ActiveBranchService;
use App\Services\IncomeExpenseService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class IncomeExpenseController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected IncomeExpenseService $incomeExpenseService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing income and expenses.']);
        }

        $recordsQuery = Transaction::query()
            ->with(['creator', 'updater'])
            ->where('branch_id', $branch->id)
            ->where('tracking_id', 'expenses')
            ->where('is_branch', true)
            ->whereNull('deleted_at')
            ->latest('trans_date')
            ->latest('id');

        $search = $request->string('search')->toString();
        if ($search !== '') {
            $recordsQuery->where(function (Builder $query) use ($search): void {
                $query->where('type', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('dr_cr', 'like', '%' . $search . '%');
            });
        }

        $records = TableListing::paginate($recordsQuery, $request, 10);

        return view('income-expenses.index', [
            'branch' => $branch,
            'records' => $records,
            'relatedToLabels' => $this->relatedToOptions(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating an income or expense entry.']);
        }

        return view('income-expenses.create', [
            'branch' => $branch,
            'categories' => $this->expenseCategoryOptions(),
            'initialEntries' => old('entries', [
                ['transaction_category_id' => '', 'amount' => '', 'description' => ''],
            ]),
        ]);
    }

    public function store(StoreIncomeExpenseRequest $request): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating an income or expense entry.']);
        }

        try {
            $records = $this->incomeExpenseService->createBatch(
                $branch,
                $request->user(),
                $request->string('trans_date')->toString(),
                $request->validated('entries')
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['income_expense' => $exception->getMessage()]);
        }

        return redirect()
            ->route('income-expenses.index')
            ->with('status', "{$records->count()} income or expense entr" . ($records->count() === 1 ? 'y has' : 'ies have') . ' been saved successfully.');
    }

    public function show(Request $request, Transaction $incomeExpense): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($this->isBranchExpenseRecord($branch?->id, $incomeExpense), 404);

        $incomeExpense->load(['creator', 'updater']);

        return view('income-expenses.show', [
            'branch' => $branch,
            'incomeExpense' => $incomeExpense,
            'category' => $this->expenseCategoryFromTransaction($incomeExpense),
            'relatedToLabels' => $this->relatedToOptions(),
        ]);
    }

    public function edit(Request $request, Transaction $incomeExpense): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($this->isBranchExpenseRecord($branch?->id, $incomeExpense), 404);

        return view('income-expenses.edit', [
            'branch' => $branch,
            'incomeExpense' => $incomeExpense,
            'categories' => $this->expenseCategoryOptions(),
            'category' => $this->expenseCategoryFromTransaction($incomeExpense),
            'relatedToLabels' => $this->relatedToOptions(),
        ]);
    }

    public function update(UpdateIncomeExpenseRequest $request, Transaction $incomeExpense): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($this->isBranchExpenseRecord($branch?->id, $incomeExpense), 404);

        $category = $this->resolveExpenseCategory($request->integer('transaction_category_id'));

        try {
            $record = $this->incomeExpenseService->update($branch, $request->user(), $incomeExpense, $category, $request->validated());
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['income_expense' => $exception->getMessage()]);
        }

        return redirect()
            ->route('income-expenses.show', $record)
            ->with('status', 'Income or expense entry updated successfully.');
    }

    public function destroy(Request $request, Transaction $incomeExpense): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($this->isBranchExpenseRecord($branch?->id, $incomeExpense), 404);

        try {
            $this->incomeExpenseService->delete($request->user(), $incomeExpense);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['income_expense' => $exception->getMessage()]);
        }

        return redirect()
            ->route('income-expenses.index')
            ->with('status', 'Income or expense entry deleted successfully.');
    }

    protected function expenseCategoryOptions()
    {
        return TransactionCategory::query()
            ->where('type_to_transaction', 'expenses')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'related_to', 'note']);
    }

    protected function resolveExpenseCategory(int $categoryId): TransactionCategory
    {
        return TransactionCategory::query()
            ->whereKey($categoryId)
            ->where('type_to_transaction', 'expenses')
            ->where('status', 1)
            ->firstOrFail();
    }

    protected function relatedToOptions(): array
    {
        return [
            'cr' => 'Income',
            'dr' => 'Expense',
        ];
    }

    protected function isBranchExpenseRecord(?int $branchId, Transaction $transaction): bool
    {
        return $branchId !== null
            && (int) $transaction->branch_id === (int) $branchId
            && $transaction->tracking_id === 'expenses'
            && $transaction->is_branch
            && $transaction->deleted_at === null;
    }

    protected function expenseCategoryFromTransaction(Transaction $transaction): ?TransactionCategory
    {
        $categoryId = (int) (data_get($transaction->transaction_details, 'category_id') ?? $transaction->detail_id);

        if ($categoryId < 1) {
            return null;
        }

        return TransactionCategory::query()->find($categoryId);
    }
}
