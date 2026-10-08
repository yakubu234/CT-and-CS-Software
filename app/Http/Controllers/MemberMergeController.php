<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergeMemberRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\MemberMergeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberMergeController extends Controller
{
    public function __construct(protected MemberMergeService $memberMergeService)
    {
        $this->middleware('module:members');
    }

    public function create(Request $request): View
    {
        $selectedIds = collect([
            old('canonical_user_id'),
            old('merged_user_id'),
        ])->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $selectedMembers = $selectedIds->isEmpty()
            ? collect()
            : $this->candidateQuery()->whereIn('id', $selectedIds)->get()->mapWithKeys(
                fn (User $member) => [(string) $member->id => $this->candidatePayload($member)]
            );

        $branches = Branch::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('members.merge', compact('selectedMembers', 'branches'));
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());
        $branchId = $request->integer('branch_id');

        if ($branchId <= 0) {
            return response()->json(['results' => []]);
        }

        $members = $this->candidateQuery()
            ->whereHas('branchMemberships', fn ($membership) => $membership
                ->where('branch_id', $branchId)
                ->where('status', true))
            ->when($term !== '', function ($query) use ($term): void {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $query->where(function ($query) use ($like): void {
                    $query->where('name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('member_no', 'like', $like)
                        ->orWhereHas('detail', fn ($detail) => $detail
                            ->where('mobile', 'like', $like)
                            ->orWhere('member_no', 'like', $like))
                        ->orWhereHas('branchMemberships', fn ($membership) => $membership
                            ->where('member_number', 'like', $like));
                });
            })
            ->orderBy('name')
            ->orderBy('last_name')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $members->map(function (User $member): array {
                $payload = $this->candidatePayload($member);
                $payload['text'] = $member->name . ' — ' . $member->email . ' — ' . ($member->detail?->mobile ?: 'no phone');

                return $payload;
            })->values(),
        ]);
    }

    public function store(MergeMemberRequest $request): RedirectResponse
    {
        $member = $this->memberMergeService->merge($request->validated(), $request->user());

        return redirect()
            ->route('members.merge.create')
            ->with('status', "The member accounts were merged successfully into {$member->name} ({$member->email}).");
    }

    private function candidateQuery()
    {
        return User::query()
            ->where('user_type', 'customer')
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->with(['detail', 'branchMemberships.branch'])
            ->withCount(['savingsAccounts', 'loans']);
    }

    private function candidatePayload(User $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'mobile' => $member->detail?->mobile,
            'accounts' => $member->savings_accounts_count,
            'loans' => $member->loans_count,
            'memberships' => $member->branchMemberships->map(fn ($membership) => [
                'id' => $membership->id,
                'branch_id' => $membership->branch_id,
                'branch' => $membership->branch?->name ?: 'Unknown society',
                'member_number' => $membership->member_number,
                'is_primary' => $membership->is_primary,
            ])->values()->all(),
        ];
    }
}
