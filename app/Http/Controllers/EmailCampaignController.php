<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\Email\EmailCampaignService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailCampaignController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected EmailCampaignService $campaignService,
    ) {
        $this->middleware('module:email');
    }

    public function index(Request $request): View
    {
        $campaigns = TableListing::paginate(
            TableListing::applySearch(
                EmailCampaign::query()->with(['branch', 'template'])->latest('id'),
                $request->string('search')->toString(),
                ['name', 'subject', 'status', 'audience_type']
            ),
            $request,
            10
        );

        return view('email.campaigns.index', ['campaigns' => $campaigns]);
    }

    public function create(Request $request): View
    {
        $currentBranch = $this->activeBranchService->ensureActiveBranch($request->user());
        $branches = $this->activeBranchService->availableBranches($request->user());

        return view('email.campaigns.create', [
            'templates' => EmailTemplate::query()->where('status', true)->where('category', 'general_notice')->orderBy('name')->get(),
            'branches' => $branches,
            'members' => User::query()
                ->with('detail', 'branch')
                ->where('branch_account', false)
                ->whereNull('deleted_at')
                ->whereNotNull('email')
                ->where('status', 1)
                ->where('user_type', 'customer')
                ->whereIn('branch_id', $branches->pluck('id'))
                ->orderBy('name')
                ->get(),
            'currentBranch' => $currentBranch,
            'designationOptions' => User::query()->whereIn('branch_id', $branches->pluck('id'))
                ->where('user_type', 'customer')->where('status', 1)->whereNull('deleted_at')
                ->whereNotNull('designation')->distinct()->orderBy('designation')->pluck('designation'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedBranchIds = $this->activeBranchService->availableBranches($request->user())->pluck('id')->all();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'branch_id' => ['nullable', Rule::in($allowedBranchIds)],
            'template_id' => ['nullable', 'exists:email_templates,id'],
            'audience_type' => ['required', 'in:branch_members,selected_members'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
            'designation' => ['nullable', 'string', 'max:191'],
            'subject' => ['nullable', 'string', 'max:191'],
            'body' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        if (($data['template_id'] ?? null) === null && (blank($data['subject'] ?? null) || blank($data['body'] ?? null))) {
            return back()->withErrors(['body' => 'Enter a subject/body or choose a template.'])->withInput();
        }

        if (! empty($data['template_id']) && ! EmailTemplate::query()->whereKey($data['template_id'])->where('category', 'general_notice')->where('status', true)->exists()) {
            return back()->withErrors(['template_id' => 'Choose an active General Notice template for a campaign.'])->withInput();
        }

        if (($data['audience_type'] ?? null) === 'selected_members' && empty($data['member_ids'])) {
            return back()->withErrors(['member_ids' => 'Select at least one member for this campaign.'])->withInput();
        }

        if ($allowedBranchIds === []) {
            return back()->withErrors(['branch_id' => 'No accessible branch is available for this campaign.'])->withInput();
        }

        if (! empty($data['member_ids'])) {
            $validCount = User::query()->whereIn('id', $data['member_ids'])
                ->whereIn('branch_id', $allowedBranchIds)
                ->when(! empty($data['branch_id']), fn ($query) => $query->where('branch_id', $data['branch_id']))
                ->where('branch_account', false)->where('user_type', 'customer')
                ->where('status', 1)->whereNotNull('email')->count();
            if ($validCount !== count(array_unique($data['member_ids']))) {
                return back()->withErrors(['member_ids' => 'Select active members with email addresses in accessible branches.'])->withInput();
            }
        }

        $data['allowed_branch_ids'] = $allowedBranchIds;

        $campaign = $this->campaignService->createCampaign($request->user(), $data);

        return redirect()->route('email.campaigns.show', $campaign)->with('status', $campaign->status === EmailCampaign::STATUS_FAILED
            ? 'No eligible recipients were found or one or more messages could not be sent. Review the campaign messages.'
            : 'Email campaign saved successfully.');
    }

    public function show(EmailCampaign $emailCampaign): View
    {
        $emailCampaign->load(['branch', 'template', 'messages.user.detail']);

        return view('email.campaigns.show', ['campaign' => $emailCampaign]);
    }
}
