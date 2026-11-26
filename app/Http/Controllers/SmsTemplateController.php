<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SmsTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:sms');
    }

    public function index(Request $request): View
    {
        $templates = TableListing::paginate(
            TableListing::applySearch(
                SmsTemplate::query()->latest('id'),
                $request->string('search')->toString(),
                ['name', 'category', 'description', 'body']
            ),
            $request,
            10
        );

        return view('bulk-sms.templates.index', [
            'templates' => $templates,
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function create(): View
    {
        return view('bulk-sms.templates.create', [
            'categoryOptions' => $this->categoryOptions(),
            'placeholderHints' => $this->placeholderHints(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['required', 'boolean'],
        ]);

        SmsTemplate::create([
            ...$data,
            'slug' => Str::slug($data['name']),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('bulk-sms.templates.index')
            ->with('status', 'SMS template created successfully.' . $this->placeholderWarning($data['body'], $data['category']));
    }

    public function edit(SmsTemplate $smsTemplate): View
    {
        return view('bulk-sms.templates.edit', [
            'smsTemplate' => $smsTemplate,
            'categoryOptions' => $this->categoryOptions(),
            'placeholderHints' => $this->placeholderHints(),
        ]);
    }

    public function update(Request $request, SmsTemplate $smsTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'status' => ['required', 'boolean'],
        ]);

        $smsTemplate->update([
            ...$data,
            'slug' => Str::slug($data['name']),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('bulk-sms.templates.index')
            ->with('status', 'SMS template updated successfully.' . $this->placeholderWarning($data['body'], $data['category']));
    }

    public function destroy(SmsTemplate $smsTemplate): RedirectResponse
    {
        $smsTemplate->delete();

        return redirect()
            ->route('bulk-sms.templates.index')
            ->with('status', 'SMS template deleted successfully.');
    }

    protected function categoryOptions(): array
    {
        return [
            'manual' => 'Manual Campaign',
            'holiday' => 'Holiday',
            'celebration' => 'Celebration',
            'birthday' => 'Birthday',
            'monthly_statement' => 'Monthly Statement',
            'transaction_credit' => 'Credit Alert',
            'transaction_debit' => 'Debit Alert',
            'loan_repayment' => 'Loan Repayment Alert',
            'loan_approved' => 'Loan Approved',
        ];
    }

    /** A short warning about broken or unknown placeholders, appended to the save message. */
    protected function placeholderWarning(string $body, string $category): string
    {
        $catalog = app(\App\Services\Sms\SmsPlaceholderCatalog::class);
        $event = array_key_exists($category, $catalog->eventLabels()) ? $category : 'manual';
        $issues = $catalog->analyze($body, $event);
        $wrap = fn ($name) => str_repeat('{', 2) . $name . str_repeat('}', 2);
        $problems = [];

        if ($issues['malformed']) {
            $problems[] = 'broken placeholder(s) ' . implode(', ', $issues['malformed']) . ' (use two braces on both sides)';
        }
        if ($issues['unknown']) {
            $problems[] = 'unknown placeholder(s) ' . implode(', ', array_map($wrap, $issues['unknown']));
        }
        if ($issues['other_event']) {
            $problems[] = 'placeholder(s) not available for this category: ' . implode(', ', array_map($wrap, $issues['other_event']));
        }

        return $problems ? ' Please check: ' . implode('; ', $problems) . '. These would show blank in messages.' : '';
    }

    /** Placeholders grouped by alert type: [event label => [name => description]]. */
    protected function placeholderHints(): array
    {
        $catalog = app(\App\Services\Sms\SmsPlaceholderCatalog::class);

        return collect($catalog->eventLabels())
            ->mapWithKeys(fn ($label, $event) => [$event => [
                'label' => $label,
                'placeholders' => collect($catalog->forEvent($event))->map(fn ($entry) => $entry[0])->all(),
            ]])
            ->all();
    }
}
