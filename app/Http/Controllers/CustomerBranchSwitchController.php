<?php

namespace App\Http\Controllers;

use App\Services\ActiveMemberBranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerBranchSwitchController extends Controller
{
    public function __invoke(Request $request, ActiveMemberBranchService $branches): RedirectResponse
    {
        $validated = $request->validate([
            'membership_id' => ['required', 'integer'],
        ]);

        $membership = $branches->switch($request->user(), (int) $validated['membership_id']);

        if (! $membership) {
            return back()->withErrors(['membership_id' => 'That society is not available for your account.']);
        }

        return redirect()->route('customer.dashboard')
            ->with('status', "You are now viewing {$membership->branch->name}.");
    }
}
