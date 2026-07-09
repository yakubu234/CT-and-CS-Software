<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmailTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('module:email');
    }

    public function index(Request $request): View
    {
        $templates = TableListing::paginate(
            TableListing::applySearch(
                EmailTemplate::query()->latest('id'),
                $request->string('search')->toString(),
                ['name', 'slug', 'category', 'subject', 'description']
            ),
            $request
        );

        return view('email.templates.index', ['templates' => $templates]);
    }

    public function create(): View
    {
        return view('email.templates.create', [
            'categoryOptions' => $this->categoryOptions(),
            'placeholderHints' => $this->placeholderHints(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        EmailTemplate::create(array_merge($data, [
            'slug' => Str::slug($data['name']),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->route('email.templates.index')->with('status', 'Email template created successfully.');
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('email.templates.edit', [
            'emailTemplate' => $emailTemplate,
            'categoryOptions' => $this->categoryOptions(),
            'placeholderHints' => $this->placeholderHints(),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $data = $this->validated($request, $emailTemplate);

        $emailTemplate->update(array_merge($data, [
            'slug' => Str::slug($data['name']),
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->route('email.templates.index')->with('status', 'Email template updated successfully.');
    }

    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->delete();

        return redirect()->route('email.templates.index')->with('status', 'Email template deleted successfully.');
    }

    protected function validated(Request $request, ?EmailTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', Rule::in(array_keys($this->categoryOptions()))],
            'description' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string'],
            'status' => ['required', 'boolean'],
        ]);
    }

    protected function categoryOptions(): array
    {
        return [
            'member_registration' => 'Member Registration',
            'loan_updates' => 'Loan Application & Approval',
            'repayment_reminders' => 'Repayment Reminders',
            'account_verification' => 'Account Verification',
            'password_resets' => 'Password Resets',
            'general_notice' => 'General Notice',
        ];
    }

    protected function placeholderHints(): array
    {
        return [
            '{{member_name}}',
            '{{member_no}}',
            '{{first_name}}',
            '{{last_name}}',
            '{{branch_name}}',
            '{{email}}',
            '{{society_name}}',
            '{{month_label}}',
            '{{reference_code}}',
        ];
    }
}
