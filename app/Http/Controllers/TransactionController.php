<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\TransactionService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TransactionController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected TransactionService $transactionService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing transactions.']);
        }

        $transactionsQuery = Transaction::query()
            ->with(['user.detail', 'account.product', 'creator'])
            ->where('branch_id', $branch->id)
            ->where('tracking_id', 'regular')
            ->where('is_branch', false)
            ->whereNull('deleted_at')
            ->latest('trans_date')
            ->latest('id');

        $search = $request->string('search')->toString();
        if ($search !== '') {
            $transactionsQuery->where(function (Builder $query) use ($search): void {
                $query->where('description', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('dr_cr', 'like', '%' . $search . '%')
                    ->orWhereHas('account', function (Builder $accountQuery) use ($search): void {
                        $accountQuery->where('account_number', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('member_no', 'like', '%' . $search . '%')
                            ->orWhereHas('detail', function (Builder $detailQuery) use ($search): void {
                                $detailQuery->where('member_no', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $transactions = TableListing::paginate($transactionsQuery, $request);

        return view('transactions.index', [
            'branch' => $branch,
            'transactions' => $transactions,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating transactions.']);
        }

        return view('transactions.create', [
            'branch' => $branch,
            'members' => $this->memberOptions($branch->id),
            'initialEntries' => old('entries', [
                ['savings_account_id' => '', 'dr_cr' => 'cr', 'amount' => '', 'description' => ''],
            ]),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating transactions.']);
        }

        $member = User::query()
            ->where('id', $request->integer('member_id'))
            ->where('branch_id', (string) $branch->id)
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->firstOrFail();

        try {
            $transactions = $this->transactionService->createBatch(
                $branch,
                $request->user(),
                $member,
                $request->string('trans_date')->toString(),
                $request->validated('entries')
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['transaction' => $exception->getMessage()]);
        }

        return redirect()
            ->route('transactions.index')
            ->with('status', "{$transactions->count()} transaction entr" . ($transactions->count() === 1 ? 'y has' : 'ies have') . ' been saved successfully.');
    }

    public function show(Request $request, Transaction $transaction): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && (int) $transaction->branch_id === (int) $branch->id
            && ! $transaction->is_branch
            && $transaction->tracking_id === 'regular'
            && $transaction->deleted_at === null,
            404
        );

        $transaction->load(['user.detail', 'account.product', 'creator', 'updater']);

        return view('transactions.show', [
            'branch' => $branch,
            'transaction' => $transaction,
        ]);
    }

    public function edit(Request $request, Transaction $transaction): View
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && (int) $transaction->branch_id === (int) $branch->id
            && ! $transaction->is_branch
            && $transaction->tracking_id === 'regular'
            && $transaction->deleted_at === null,
            404
        );

        $transaction->load(['user.detail', 'account.product']);

        return view('transactions.edit', [
            'branch' => $branch,
            'transaction' => $transaction,
            'member' => $transaction->user,
            'memberAccounts' => $transaction->user
                ? $transaction->user->savingsAccounts()->with('product')->where('is_branch_acount', false)->where('status', 1)->get()
                : collect(),
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && (int) $transaction->branch_id === (int) $branch->id
            && ! $transaction->is_branch
            && $transaction->tracking_id === 'regular'
            && $transaction->deleted_at === null,
            404
        );

        try {
            $transaction = $this->transactionService->updateTransaction(
                $branch,
                $request->user(),
                $transaction,
                $request->validated()
            );
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['transaction' => $exception->getMessage()]);
        }

        return redirect()
            ->route('transactions.show', $transaction)
            ->with('status', 'Transaction updated successfully.');
    }

    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && (int) $transaction->branch_id === (int) $branch->id
            && ! $transaction->is_branch
            && $transaction->tracking_id === 'regular'
            && $transaction->deleted_at === null,
            404
        );

        try {
            $this->transactionService->deleteTransaction($request->user(), $transaction);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['transaction' => $exception->getMessage()]);
        }

        return redirect()
            ->route('transactions.index')
            ->with('status', 'Transaction deleted successfully.');
    }

    protected function memberOptions(int $branchId)
    {
        return User::query()
            ->with([
                'detail',
                'savingsAccounts' => function ($query): void {
                    $query->with('product')
                        ->where('is_branch_acount', false)
                        ->where('status', 1);
                },
            ])
            ->where('branch_id', (string) $branchId)
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->where(function (Builder $query): void {
                $query->where('user_type', 'customer')
                    ->orWhere('society_exco', true)
                    ->orWhere('former_exco', true);
            })
            ->orderBy('name')
            ->get();
    }
}
