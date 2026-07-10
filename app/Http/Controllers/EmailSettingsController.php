<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\EmailMessage;
use App\Models\EmailPreference;
use App\Models\EmailSmtpAccount;
use App\Models\User;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailSettingsService;
use App\Support\TableListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailSettingsController extends Controller
{
    public function __construct(
        protected EmailSettingsService $settings,
        protected EmailDispatchService $dispatchService,
    ) {
        $this->middleware('module:email');
    }

    public function edit(Request $request): View
    {
        return view('email.settings', [
            'summary' => $this->settings->configSummary(),
            'smtpAccounts' => EmailSmtpAccount::query()->latest('id')->get(),
            'preferences' => TableListing::paginate(
                EmailPreference::query()->with(['branch', 'user.detail'])->latest('id'),
                $request,
                10
            ),
            'branches' => Branch::query()->orderBy('name')->get(),
            'members' => User::query()
                ->with('detail', 'branch')
                ->where('branch_account', false)
                ->whereNull('deleted_at')
                ->whereNotNull('email')
                ->orderBy('name')
                ->get(),
            'testSendResult' => session('email_test_send_result'),
        ]);
    }

    public function storeSmtp(Request $request): RedirectResponse
    {
        $data = $this->validateSmtp($request);

        EmailSmtpAccount::create(array_merge($data, [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->route('email.settings.edit')->with('status', 'SMTP account added successfully.');
    }

    public function updateSmtp(Request $request, EmailSmtpAccount $smtpAccount): RedirectResponse
    {
        $data = $this->validateSmtp($request, false);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $smtpAccount->update(array_merge($data, [
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->route('email.settings.edit')->with('status', 'SMTP account updated successfully.');
    }

    public function destroySmtp(EmailSmtpAccount $smtpAccount): RedirectResponse
    {
        $smtpAccount->delete();

        return redirect()->route('email.settings.edit')->with('status', 'SMTP account deleted successfully.');
    }

    public function storePreference(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope' => ['required', Rule::in(['branch', 'member'])],
            'branch_id' => ['nullable', 'required_if:scope,branch', 'exists:branches,id'],
            'user_id' => ['nullable', 'required_if:scope,member', 'exists:users,id'],
            'email_enabled' => ['required', 'boolean'],
            'paused_until' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $attributes = $data['scope'] === 'branch'
            ? ['branch_id' => $data['branch_id'], 'user_id' => null]
            : ['branch_id' => null, 'user_id' => $data['user_id']];

        EmailPreference::updateOrCreate($attributes, [
            'email_enabled' => (bool) $data['email_enabled'],
            'paused_until' => $data['paused_until'] ?? null,
            'reason' => $data['reason'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('email.settings.edit')->with('status', 'Email preference saved successfully.');
    }

    public function destroyPreference(EmailPreference $emailPreference): RedirectResponse
    {
        $emailPreference->delete();

        return redirect()->route('email.settings.edit')->with('status', 'Email preference removed successfully.');
    }

    public function testSend(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:191'],
            'subject' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string'],
        ]);

        $message = EmailMessage::create([
            'email' => $data['email'],
            'recipient_name' => 'Test Recipient',
            'subject' => $data['subject'],
            'body' => nl2br(e($data['body'])),
            'mailer' => $this->settings->mailer(),
            'status' => EmailMessage::STATUS_PENDING,
            'reference_key' => 'test:' . now()->timestamp,
            'meta' => ['created_via' => 'settings-test'],
        ]);

        $result = $this->dispatchService->dispatch($message);

        return redirect()
            ->route('email.settings.edit')
            ->with('email_test_send_result', [
                'successful' => $result->status === EmailMessage::STATUS_SENT,
                'message' => $result->status === EmailMessage::STATUS_SENT
                    ? 'Test email sent successfully.'
                    : ($result->error_message ?: 'Test email failed.'),
            ]);
    }

    protected function validateSmtp(Request $request, bool $passwordRequired = true): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'host' => ['required', 'string', 'max:191'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
            'username' => ['nullable', 'string', 'max:191'],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'string', 'max:191'],
            'from_address' => ['required', 'email', 'max:191'],
            'from_name' => ['nullable', 'string', 'max:191'],
            'hourly_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'paused_until' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
