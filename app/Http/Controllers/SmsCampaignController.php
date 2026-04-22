<?php

namespace App\Http\Controllers;

use App\Models\SmsCampaign;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\Sms\SmsCampaignService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsCampaignController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected SmsCampaignService $campaignService,
    ) {
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

        return view('bulk-sms.campaigns.create', [
            'templates' => SmsTemplate::query()->where('status', true)->orderBy('name')->get(),
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            'members' => User::query()
                ->with('detail', 'branch')
                ->where('branch_account', false)
                ->whereNull('deleted_at')
                ->where(function ($query): void {
                    $query->where('user_type', 'customer')
                        ->orWhere('society_exco', true)
                        ->orWhere('former_exco', true);
                })
                ->orderBy('name')
                ->get(),
            'currentBranch' => $currentBranch,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'branch_id' => ['nullable', 'exists:branches,id'],
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

        $campaign = $this->campaignService->createCampaign($request->user(), $data);

        return redirect()
            ->route('bulk-sms.campaigns.show', $campaign)
            ->with('status', 'SMS campaign saved successfully.');
    }

    public function show(SmsCampaign $smsCampaign): View
    {
        $smsCampaign->load(['branch', 'template', 'messages.user.detail']);

        return view('bulk-sms.campaigns.show', [
            'campaign' => $smsCampaign,
        ]);
    }
}
