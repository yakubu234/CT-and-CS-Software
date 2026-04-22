<?php

namespace App\Http\Controllers;

use App\Services\ActiveBranchService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
    ) {
    }

    public function index(Request $request): View
    {
        $branches = TableListing::paginate(
            TableListing::applySearch(
                $this->activeBranchService
                    ->availableBranchesQuery($request->user())
                    ->latest('updated_at'),
                $request->string('search')->toString(),
                [
                    'name',
                    'prefix',
                    'id_prefix',
                    'registration_number',
                    'contact_email',
                    'contact_phone',
                    'address',
                ]
            ),
            $request
        );

        return view('branches.switch', [
            'branches' => $branches,
            'currentBranch' => $this->activeBranchService->ensureActiveBranch($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        $branch = $this->activeBranchService->setCurrentBranch((int) $validated['branch_id'], $request->user());

        if (! $branch) {
            return back()->withErrors([
                'branch_id' => 'The selected branch is not available for this user.',
            ]);
        }

        $redirectTo = $validated['redirect_to'] ?: route('branches.switch.index');

        return redirect()->to($redirectTo)
            ->with('status', "You are now working inside {$branch->name}.");
    }
}
