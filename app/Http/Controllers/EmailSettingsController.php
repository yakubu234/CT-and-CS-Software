<?php

namespace App\Http\Controllers;

use App\Models\EmailMessage;
use App\Services\Email\EmailDispatchService;
use App\Services\Email\EmailSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailSettingsController extends Controller
{
    public function __construct(
        protected EmailSettingsService $settings,
        protected EmailDispatchService $dispatchService,
    ) {
        $this->middleware('module:email');
    }

    public function edit(): View
    {
        return view('email.settings', [
            'summary' => $this->settings->configSummary(),
            'testSendResult' => session('email_test_send_result'),
        ]);
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
}
