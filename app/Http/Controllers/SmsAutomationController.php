<?php

namespace App\Http\Controllers;

use App\Models\SmsAutomationRule;
use App\Models\SmsTemplate;
use App\Services\ActiveBranchService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsAutomationController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
    ) {
    }

    public function index(Request $request): View
    {
        $rules = TableListing::paginate(
            TableListing::applySearch(
                SmsAutomationRule::query()
                    ->with(['branch', 'template'])
                    ->latest('id'),
                $request->string('search')->toString(),
                ['name', 'event']
            ),
            $request,
            10
        );

        return view('bulk-sms.automations.index', [
            'rules' => $rules,
            'eventOptions' => $this->eventOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('bulk-sms.automations.create', [
            'templates' => SmsTemplate::query()->where('status', true)->orderBy('name')->get(),
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            'eventOptions' => $this->eventOptions(),
            'currentBranch' => $this->activeBranchService->ensureActiveBranch($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRule($request);

        SmsAutomationRule::create([
            ...$data,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('bulk-sms.automations.index')
            ->with('status', 'SMS automation rule created successfully.');
    }

    public function edit(SmsAutomationRule $smsAutomationRule, Request $request): View
    {
        return view('bulk-sms.automations.edit', [
            'rule' => $smsAutomationRule,
            'templates' => SmsTemplate::query()->where('status', true)->orderBy('name')->get(),
            'branches' => $this->activeBranchService->availableBranches($request->user()),
            'eventOptions' => $this->eventOptions(),
            'currentBranch' => $this->activeBranchService->ensureActiveBranch($request->user()),
        ]);
    }

    public function update(Request $request, SmsAutomationRule $smsAutomationRule): RedirectResponse
    {
        $smsAutomationRule->update([
            ...$this->validateRule($request),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('bulk-sms.automations.index')
            ->with('status', 'SMS automation rule updated successfully.');
    }

    public function destroy(SmsAutomationRule $smsAutomationRule): RedirectResponse
    {
        $smsAutomationRule->delete();

        return redirect()
            ->route('bulk-sms.automations.index')
            ->with('status', 'SMS automation rule deleted successfully.');
    }

    protected function validateRule(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'template_id' => ['required', 'exists:sms_templates,id'],
            'event' => ['required', 'in:' . implode(',', array_keys($this->eventOptions()))],
            'status' => ['required', 'boolean'],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'day_of_month' => ['nullable', 'integer', 'between:1,28'],
        ]);
    }

    protected function eventOptions(): array
    {
        return [
            'transaction_credit' => 'Member Credited',
            'transaction_debit' => 'Member Debited',
            'loan_approved' => 'Loan Approved',
            'birthday' => 'Birthday',
            'monthly_statement' => 'Monthly Statement',
        ];
    }
}
