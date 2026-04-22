<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\StoreMemberDocumentRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\CustomField;
use App\Models\MemberDocument;
use App\Models\Designation;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\MemberService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected MemberService $memberService,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before managing members.']);
        }

        $members = TableListing::paginate(
            TableListing::applySearch(
                User::query()
                    ->with(['detail'])
                    ->where('user_type', 'customer')
                    ->where('branch_account', false)
                    ->where('branch_id', (string) $branch->id)
                    ->latest(),
                $request->string('search')->toString(),
                ['name', 'last_name', 'email', 'member_no']
            ),
            $request
        );

        return view('members.index', [
            'branch' => $branch,
            'members' => $members,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before creating members.']);
        }

        return view('members.create', [
            'branch' => $branch,
            'nextMemberNumber' => $this->memberService->nextMemberNumber($branch),
            'designations' => Designation::query()->where('status', 1)->orderBy('sort_order')->orderBy('name')->get(),
            'customFields' => CustomField::query()->forUsers()->active()->orderBy('order')->orderBy('field_name')->get(),
        ]);
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch, 422, 'Please select a branch before creating a member.');

        $member = $this->memberService->create($request->validated(), $branch);

        return redirect()
            ->route('members.show', $member)
            ->with('status', "{$member->name} has been created successfully.");
    }

    public function show(Request $request, User $member): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before viewing members.']);
        }

        abort_unless(
            $member->user_type === 'customer'
            && ! $member->branch_account
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $member->load(['detail', 'documents', 'savingsAccounts.product']);

        return view('members.show', [
            'member' => $member,
        ]);
    }

    public function edit(Request $request, User $member): View|RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        if (! $branch) {
            return redirect()->route('branches.switch.index')
                ->withErrors(['branch' => 'Please select an active branch before editing members.']);
        }

        abort_unless(
            $member->user_type === 'customer'
            && ! $member->branch_account
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $member->load(['detail', 'documents']);

        return view('members.edit', [
            'branch' => $branch,
            'member' => $member,
            'customFields' => CustomField::query()->forUsers()->active()->orderBy('order')->orderBy('field_name')->get(),
        ]);
    }

    public function update(UpdateMemberRequest $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless($branch, 422, 'Please select a branch before updating a member.');
        abort_unless(
            $member->user_type === 'customer'
            && ! $member->branch_account
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $member = $this->memberService->update($member, $request->validated(), $branch);

        return redirect()
            ->route('members.show', $member)
            ->with('status', "{$member->name} has been updated successfully.");
    }

    public function destroy(Request $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && $member->user_type === 'customer'
            && ! $member->branch_account
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        $memberName = $member->name;
        $this->memberService->delete($member);

        return redirect()
            ->route('members.index')
            ->with('status', "{$memberName} has been archived successfully.");
    }

    public function storeDocument(StoreMemberDocumentRequest $request, User $member): RedirectResponse
    {
        $branch = $this->activeBranchService->ensureActiveBranch($request->user());

        abort_unless(
            $branch
            && $member->user_type === 'customer'
            && ! $member->branch_account
            && (string) $member->branch_id === (string) $branch->id,
            404
        );

        MemberDocument::create([
            'user_id' => $member->id,
            'name' => $request->validated('name'),
            'document' => $request->file('document')->store('members/documents', 'public'),
        ]);

        return redirect()
            ->route('members.show', $member)
            ->with('status', 'Document added successfully.');
    }
}
