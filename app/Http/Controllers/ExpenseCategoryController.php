<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionCategoryRequest;
use App\Http\Requests\UpdateTransactionCategoryRequest;
use App\Models\TransactionCategory;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = TableListing::paginate(
            TableListing::applySearch(
                TransactionCategory::query()
                    ->where('type_to_transaction', 'expenses')
                    ->latest('id'),
                $request->string('search')->toString(),
                ['name', 'related_to', 'note']
            ),
            $request,
            10
        );

        return view('expense-categories.index', [
            'categories' => $categories,
            'relatedToLabels' => $this->relatedToOptions(),
        ]);
    }

    public function create(): View
    {
        return view('expense-categories.create', [
            'relatedToOptions' => $this->relatedToOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StoreTransactionCategoryRequest $request): RedirectResponse
    {
        TransactionCategory::create([
            ...$request->validated(),
            'type_to_transaction' => 'expenses',
        ]);

        return redirect()
            ->route('expense-categories.index')
            ->with('status', 'Income or expense category created successfully.');
    }

    public function edit(TransactionCategory $expenseCategory): View
    {
        abort_unless($expenseCategory->type_to_transaction === 'expenses', 404);

        return view('expense-categories.edit', [
            'expenseCategory' => $expenseCategory,
            'relatedToOptions' => $this->relatedToOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $expenseCategory): RedirectResponse
    {
        abort_unless($expenseCategory->type_to_transaction === 'expenses', 404);

        $expenseCategory->update([
            ...$request->validated(),
            'type_to_transaction' => 'expenses',
        ]);

        return redirect()
            ->route('expense-categories.index')
            ->with('status', 'Income or expense category updated successfully.');
    }

    public function destroy(TransactionCategory $expenseCategory): RedirectResponse
    {
        abort_unless($expenseCategory->type_to_transaction === 'expenses', 404);

        $expenseCategory->delete();

        return redirect()
            ->route('expense-categories.index')
            ->with('status', 'Income or expense category deleted successfully.');
    }

    protected function relatedToOptions(): array
    {
        return [
            'cr' => 'Income',
            'dr' => 'Expense',
        ];
    }

    protected function statusOptions(): array
    {
        return [
            1 => 'Active',
            0 => 'Inactive',
        ];
    }
}
