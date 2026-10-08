<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergeMemberRequest;
use App\Models\User;
use App\Services\MemberMergeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class MemberMergeController extends Controller
{
    public function __construct(protected MemberMergeService $memberMergeService)
    {
        $this->middleware('module:members');
    }

    public function create(): View
    {
        $members = User::query()
            ->where('user_type', 'customer')
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->with(['detail', 'branchMemberships.branch'])
            ->withCount(['savingsAccounts', 'loans'])
            ->orderBy('name')
            ->orderBy('last_name')
            ->get();

        return view('members.merge', compact('members'));
    }

    public function store(MergeMemberRequest $request): RedirectResponse
    {
        $member = $this->memberMergeService->merge($request->validated(), $request->user());

        return redirect()
            ->route('members.merge.create')
            ->with('status', "The member accounts were merged successfully into {$member->name} ({$member->email}).");
    }
}
