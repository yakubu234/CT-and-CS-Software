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

        return view('email.campaigns.create', [
            'templates' => EmailTemplate::query()->where('status', true)->orderBy('name')->get(),
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            'members' => User::query()
                ->with('detail', 'branch')
                ->where('branch_account', false)
                ->whereNull('deleted_at')
                ->whereNotNull('email')
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
            'template_id' => ['nullable', 'exists:email_templates,id'],
            'audience_type' => ['required', 'in:branch_members,selected_members'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
            'subject' => ['nullable', 'string', 'max:191'],
            'body' => ['nullable', 'string'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        if (($data['template_id'] ?? null) === null && (blank($data['subject'] ?? null) || blank($data['body'] ?? null))) {
            return back()->withErrors(['body' => 'Enter a subject/body or choose a template.'])->withInput();
        }

        if (($data['audience_type'] ?? null) === 'selected_members' && empty($data['member_ids'])) {
            return back()->withErrors(['member_ids' => 'Select at least one member for this campaign.'])->withInput();
        }

        $campaign = $this->campaignService->createCampaign($request->user(), $data);

        return redirect()->route('email.campaigns.show', $campaign)->with('status', 'Email campaign saved successfully.');
    }

    public function show(EmailCampaign $emailCampaign): View
    {
        $emailCampaign->load(['branch', 'template', 'messages.user.detail']);

        return view('email.campaigns.show', ['campaign' => $emailCampaign]);
    }
}
