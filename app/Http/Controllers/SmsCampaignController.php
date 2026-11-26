<?php

namespace App\Http\Controllers;

use App\Models\SmsCampaign;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\Sms\SmsCampaignService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmsCampaignController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected SmsCampaignService $campaignService,
    ) {
        $this->middleware('module:sms');
    }

    public function index(Request $request): View
    {
        $campaigns = TableListing::paginate(
            TableListing::applySearch(
                SmsCampaign::query()
                    ->with(['branch', 'template'])
                    ->latest('id'),
                $request->string('search')->toString(),
                ['name', 'message', 'status', 'audience_type']
            ),
            $request,
            10
        );

        return view('bulk-sms.campaigns.index', [
            'campaigns' => $campaigns,
        ]);
    }

    public function create(Request $request): View
    {
        $currentBranch = $this->activeBranchService->ensureActiveBranch($request->user());

        $oldMemberIds = collect(old('member_ids', []))->map(fn ($id) => (int) $id)->filter();

        return view('bulk-sms.campaigns.create', [
            'templates' => SmsTemplate::query()->where('status', true)->orderBy('name')->get(),
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            // Only members re-selected after a failed submit are rendered up front;
            // everything else is loaded on demand via searchMembers().
            'members' => $oldMemberIds->isEmpty()
                ? collect()
                : $this->smsMemberQuery()
                    ->with('detail', 'branch')
                    ->whereIn('id', $oldMemberIds)
                    ->orderBy('name')
                    ->get(),
            'currentBranch' => $currentBranch,
        ]);
    }

    public function searchMembers(Request $request): JsonResponse
    {
        $allowedBranchIds = $this->allowedBranchIds($request);
        $branchIds = $this->resolveBranchSelection((array) $request->input('branch_ids', []), $allowedBranchIds);

        $search = trim((string) $request->input('q'));
        if (mb_strlen($search) < 2) {
            return response()->json(['results' => [], 'pagination' => ['more' => false]]);
        }

        $members = $this->smsMemberQuery()
            ->with('detail', 'branch')
            ->tap(fn ($query) => $this->inBranches($query, $branchIds ?: $allowedBranchIds))
            ->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('member_no', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('detail', fn ($detail) => $detail->where(fn ($inner) => $inner
                        ->where('member_no', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')));
            })
            ->orderBy('name')
            ->simplePaginate(20);

        return response()->json([
            'results' => collect($members->items())->map(fn (User $member): array => [
                'id' => $member->id,
                'text' => ($member->display_member_no ?: 'N/A') . ' - ' . $member->name . ' (' . ($member->branch?->name ?: 'No branch') . ')',
            ])->values(),
            'pagination' => ['more' => $members->hasMorePages()],
        ]);
    }

    protected function smsMemberQuery(): Builder
    {
        return User::query()
            ->where('branch_account', false)
            ->whereNull('deleted_at')
            ->where(function ($query): void {
                $query->where('user_type', 'customer')
                    ->orWhere('society_exco', true)
                    ->orWhere('former_exco', true);
            });
    }

    /** Members with an active membership in any of the branches (non-customer excos by branch). */
    protected function inBranches($query, array $branchIds)
    {
        return $query->where(function ($builder) use ($branchIds): void {
            $builder->whereHas('branchMemberships', fn ($membership) => $membership->whereIn('branch_id', $branchIds)->where('status', true))
                ->orWhere(fn ($exco) => $exco->where('user_type', '<>', 'customer')->whereIn('branch_id', $branchIds));
        });
    }

    protected function allowedBranchIds(Request $request): array
    {
        return $this->activeBranchService->availableBranches($request->user())
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Turn the branch multi-select into concrete branch ids. "all" expands to every
     * branch the user can access; anything outside that list is dropped.
     */
    protected function resolveBranchSelection(array $selection, array $allowedBranchIds): array
    {
        if (in_array('all', $selection, true)) {
            return $allowedBranchIds;
        }

        return array_values(array_intersect(
            $allowedBranchIds,
            array_map('intval', $selection)
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedBranchIds = $this->allowedBranchIds($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', Rule::in(['all', ...array_map('strval', $allowedBranchIds)])],
            'template_id' => ['nullable', 'exists:sms_templates,id'],
            'audience_type' => ['required', 'in:branch_members,selected_members'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
            'message' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        if (($data['template_id'] ?? null) === null && blank($data['message'] ?? null)) {
            return back()
                ->withErrors(['message' => 'Enter a message body or choose a template.'])
                ->withInput();
        }

        if (($data['audience_type'] ?? null) === 'selected_members' && empty($data['member_ids'])) {
            return back()
                ->withErrors(['member_ids' => 'Select at least one member for this campaign.'])
                ->withInput();
        }

        $branchIds = $this->resolveBranchSelection($data['branch_ids'], $allowedBranchIds);

        if ($branchIds === []) {
            return back()
                ->withErrors(['branch_ids' => 'Choose at least one branch you have access to.'])
                ->withInput();
        }

        // For hand-picked members, each member goes into the campaign of their own branch.
        $memberBranches = collect();
        if ($data['audience_type'] === 'selected_members') {
            $memberIds = array_values(array_unique(array_map('intval', $data['member_ids'])));
            // Each member goes to one selected society they actively belong to (their
            // primary one when it is selected), so they get exactly one message.
            $memberBranches = $this->smsMemberQuery()
                ->with(['branchMemberships' => fn ($membership) => $membership->where('status', true)])
                ->whereIn('id', $memberIds)
                ->get()
                ->mapWithKeys(function (User $member) use ($branchIds): array {
                    $candidates = $member->branchMemberships
                        ->filter(fn ($membership) => in_array((int) $membership->branch_id, $branchIds, true))
                        ->sortByDesc('is_primary')
                        ->pluck('branch_id')
                        ->map(fn ($id) => (int) $id);

                    if ($candidates->isEmpty() && in_array((int) $member->branch_id, $branchIds, true)) {
                        $candidates = collect([(int) $member->branch_id]);
                    }

                    return [$member->id => $candidates->first()];
                });

            $outside = $memberBranches->filter(fn ($branchId) => $branchId === null);
            if ($memberBranches->count() !== count($memberIds) || $outside->isNotEmpty()) {
                return back()
                    ->withErrors(['member_ids' => 'Every selected member must belong to one of the selected branches.'])
                    ->withInput();
            }
        }

        $branchNames = $this->activeBranchService->availableBranches($request->user())->pluck('name', 'id');
        $multiple = count($branchIds) > 1;
        $campaigns = [];

        foreach ($branchIds as $branchId) {
            $memberIds = $memberBranches->filter(fn ($memberBranch) => $memberBranch === $branchId)->keys()->all();

            if ($data['audience_type'] === 'selected_members' && $memberIds === []) {
                continue;
            }

            $campaigns[] = $this->campaignService->createCampaign($request->user(), [
                ...$data,
                'name' => $multiple ? $data['name'] . ' - ' . ($branchNames[$branchId] ?? 'Branch ' . $branchId) : $data['name'],
                'branch_id' => $branchId,
                'member_ids' => $memberIds,
            ]);
        }

        if (count($campaigns) === 1) {
            return redirect()
                ->route('bulk-sms.campaigns.show', $campaigns[0])
                ->with('status', 'SMS campaign saved successfully.');
        }

        return redirect()
            ->route('bulk-sms.campaigns.index')
            ->with('status', count($campaigns) . ' SMS campaigns saved, one for each selected branch.');
    }

    public function show(SmsCampaign $smsCampaign): View
    {
        $smsCampaign->load(['branch', 'template', 'messages.user.detail']);

        return view('bulk-sms.campaigns.show', [
            'campaign' => $smsCampaign,
        ]);
    }
}
