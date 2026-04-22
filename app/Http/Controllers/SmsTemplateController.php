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
            ->with('status', 'SMS template created successfully.');
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
            ->with('status', 'SMS template updated successfully.');
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
            'transaction_credit' => 'Transaction Credit',
            'transaction_debit' => 'Transaction Debit',
            'loan_approved' => 'Loan Approved',
        ];
    }

    protected function placeholderHints(): array
    {
        return [
            '{{member_name}}',
            '{{member_no}}',
            '{{branch_name}}',
            '{{amount}}',
            '{{transaction_date}}',
            '{{transaction_type}}',
            '{{account_number}}',
            '{{account_type}}',
            '{{current_balance}}',
            '{{loan_id}}',
            '{{loan_amount}}',
            '{{release_date}}',
            '{{due_date}}',
            '{{birth_day}}',
            '{{month_label}}',
            '{{statement_summary}}',
            '{{society_name}}',
        ];
    }
}
