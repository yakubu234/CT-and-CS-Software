<?php

namespace App\Http\Controllers;

use App\Models\SmsAutomationRule;
use App\Models\SmsTemplate;
use App\Services\ActiveBranchService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmsAutomationController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
    ) {
        $this->middleware('module:sms');
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
        $branches = $this->activeBranchService->availableBranches($request->user());
        $allowedBranchIds = $branches->pluck('id')->map(fn ($id) => (string) $id)->all();

        $data = $this->validateRule($request, [
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', Rule::in(['all', ...$allowedBranchIds])],
        ]);

        // "All" stays a single global rule (branch_id = null) so branches added later are
        // covered too; specific branches each get their own copy of the rule.
        $branchIds = in_array('all', $data['branch_ids'], true)
            ? [null]
            : array_values(array_unique(array_map('intval', $data['branch_ids'])));
        unset($data['branch_ids']);

        $branchNames = $branches->pluck('name', 'id');
        $multiple = count($branchIds) > 1;

        foreach ($branchIds as $branchId) {
            SmsAutomationRule::create([
                ...$data,
                'name' => $multiple ? $data['name'] . ' - ' . ($branchNames[$branchId] ?? 'Branch ' . $branchId) : $data['name'],
                'branch_id' => $branchId,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('bulk-sms.automations.index')
            ->with('status', $multiple
                ? count($branchIds) . ' SMS automation rules created, one for each selected branch.'
                : 'SMS automation rule created successfully.');
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
        $allowedBranchIds = $this->activeBranchService->availableBranches($request->user())->pluck('id')->all();

        $smsAutomationRule->update([
            ...$this->validateRule($request, [
                'branch_id' => ['nullable', Rule::in($allowedBranchIds)],
            ]),
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

    protected function validateRule(Request $request, array $branchRules): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            ...$branchRules,
            'template_id' => ['required', 'exists:sms_templates,id'],
            'event' => ['required', 'in:' . implode(',', array_keys($this->eventOptions()))],
            'status' => ['required', 'boolean'],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'day_of_month' => [
                Rule::requiredIf($request->input('event') === SmsAutomationRule::EVENT_MONTHLY_STATEMENT),
                'nullable',
                'integer',
                'between:1,31',
            ],
        ]);

        // The day only matters for monthly statements; don't keep a stale one on other events.
        if ($data['event'] !== SmsAutomationRule::EVENT_MONTHLY_STATEMENT) {
            $data['day_of_month'] = null;
        }

        return $data;
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
