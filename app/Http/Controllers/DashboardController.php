<?php

namespace App\Http\Controllers;

use App\Services\ActiveBranchService;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected DashboardService $dashboardService,
    ) {
    }

    public function __invoke(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing the dashboard.']);
        }

        return view('dashboard', [
            'branch' => $branch,
            'dashboard' => $this->dashboardService->build($branch),
        ]);
    }
}
