<?php

namespace App\Http\Controllers;

use App\Models\SavingsAccount;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\MemberService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected MemberService $memberService,
    ) {
        $this->middleware('module:accounts');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing accounts.']);
        }

        $usersQuery = User::query()
            ->with([
                'detail',
                'savingsAccounts' => function ($query): void {
                    $query->with('product')
                        ->where('is_branch_acount', false);
                },
            ])
            ->where('branch_id', (string) $branch->id)
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->where(function (Builder $query): void {
                $query->where('user_type', 'customer')
                    ->orWhere('society_exco', true)
                    ->orWhere('former_exco', true);
            })
            ->latest();

        $users = TableListing::paginate(
            TableListing::applySearch(
                $usersQuery,
                $request->string('search')->toString(),
                ['name', 'last_name', 'email', 'member_no']
            ),
            $request
        );

        $accountTypes = ['SAVINGS', 'SHARES', 'AUTHENTICATION', 'DEPOSIT'];

        return view('accounts.index', [
            'branch' => $branch,
            'users' => $users,
            'accountTypes' => $accountTypes,
        ]);
    }

    public function inactive(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing inactive accounts.']);
        }

        $accounts = TableListing::paginate(
            TableListing::applySearch(
                SavingsAccount::query()
                    ->with([
                        'product',
                        'user' => fn ($query) => $query->withTrashed()->with('detail'),
                    ])
                    ->where('status', 0)
                    ->where('is_branch_acount', false)
                    ->whereHas('user', function (Builder $query) use ($branch): void {
                        $query->withTrashed()
                            ->where('branch_id', (string) $branch->id)
                            ->where('branch_account', false);
                    })
                    ->latest('disabled_at')
                    ->latest('updated_at'),
                $request->string('search')->toString(),
                ['account_number', 'description']
            ),
            $request
        );

        return view('accounts.inactive', [
            'branch' => $branch,
            'accounts' => $accounts,
        ]);
    }

    public function reactivate(Request $request, SavingsAccount $account): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        $member = $account->user()->withTrashed()->first();

        abort_unless(
            $branch
            && ! $account->is_branch_acount
            && $member
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        if ($member->trashed()) {
            $this->memberService->restore($member);

            return redirect()
                ->route('accounts.inactive')
                ->with('status', "{$member->name} and the accounts archived with this member have been restored.");
        }

        $account->update([
            'status' => 1,
            'disabled_at' => null,
            'archived_with_member_at' => null,
            'updated_user_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('accounts.inactive')
            ->with('status', "{$account->account_number} has been re-enabled successfully.");
    }
}
