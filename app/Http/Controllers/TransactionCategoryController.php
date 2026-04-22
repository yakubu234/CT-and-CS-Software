<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionCategoryRequest;
use App\Http\Requests\UpdateTransactionCategoryRequest;
use App\Models\TransactionCategory;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = TableListing::paginate(
            TableListing::applySearch(
                TransactionCategory::query()
                    ->where('type_to_transaction', 'transaction')
                    ->latest('id'),
                $request->string('search')->toString(),
                ['name', 'related_to', 'note']
            ),
            $request,
            10
        );

        return view('transaction-categories.index', [
            'categories' => $categories,
            'relatedToLabels' => $this->relatedToOptions(),
        ]);
    }

    public function create(): View
    {
        return view('transaction-categories.create', [
            'relatedToOptions' => $this->relatedToOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StoreTransactionCategoryRequest $request): RedirectResponse
    {
        TransactionCategory::create([
            ...$request->validated(),
            'type_to_transaction' => 'transaction',
        ]);

        return redirect()
            ->route('transaction-categories.index')
            ->with('status', 'Transaction category created successfully.');
    }

    public function edit(TransactionCategory $transactionCategory): View
    {
        abort_unless($transactionCategory->type_to_transaction === 'transaction', 404);

        return view('transaction-categories.edit', [
            'transactionCategory' => $transactionCategory,
            'relatedToOptions' => $this->relatedToOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(UpdateTransactionCategoryRequest $request, TransactionCategory $transactionCategory): RedirectResponse
    {
        abort_unless($transactionCategory->type_to_transaction === 'transaction', 404);

        $transactionCategory->update([
            ...$request->validated(),
            'type_to_transaction' => 'transaction',
        ]);

        return redirect()
            ->route('transaction-categories.index')
            ->with('status', 'Transaction category updated successfully.');
    }

    public function destroy(TransactionCategory $transactionCategory): RedirectResponse
    {
        abort_unless($transactionCategory->type_to_transaction === 'transaction', 404);

        $transactionCategory->delete();

        return redirect()
            ->route('transaction-categories.index')
            ->with('status', 'Transaction category deleted successfully.');
    }

    protected function relatedToOptions(): array
    {
        return [
            'cr' => 'Credit',
            'dr' => 'Debit',
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
