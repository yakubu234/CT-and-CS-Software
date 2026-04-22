<?php

namespace App\Http\Controllers;

use App\Services\Sms\SmsSettingsService;
use App\Services\Sms\SmsProviderManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsSettingsController extends Controller
{
    public function __construct(
        protected SmsSettingsService $settings,
        protected SmsProviderManager $providerManager,
    ) {
    }

    public function edit(): View
    {
        return view('bulk-sms.settings', [
            'providers' => $this->providerOptions(),
            'activeProvider' => $this->settings->activeProvider(),
            'senderId' => $this->settings->senderId(),
            'callbackUrl' => $this->settings->callbackUrl(),
            'termii' => $this->settings->providerConfig('termii'),
            'bulkSmsNigeria' => $this->settings->providerConfig('bulksmsnigeria'),
            'generic' => $this->settings->providerConfig('generic'),
            'balanceResult' => session('sms_balance_result'),
            'testSendResult' => session('sms_test_send_result'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'active_provider' => ['nullable', 'in:termii,bulksmsnigeria,generic'],
            'sender_id' => ['nullable', 'string', 'max:50'],
            'callback_url' => ['nullable', 'url', 'max:255'],
            'termii.base_url' => ['nullable', 'url', 'max:255'],
            'termii.endpoint' => ['nullable', 'string', 'max:100'],
            'termii.balance_endpoint' => ['nullable', 'string', 'max:100'],
            'termii.api_key' => ['nullable', 'string', 'max:191'],
            'termii.channel' => ['nullable', 'string', 'max:50'],
            'termii.type' => ['nullable', 'string', 'max:50'],
            'bulksmsnigeria.base_url' => ['nullable', 'url', 'max:255'],
            'bulksmsnigeria.endpoint' => ['nullable', 'string', 'max:100'],
            'bulksmsnigeria.balance_endpoint' => ['nullable', 'string', 'max:100'],
            'bulksmsnigeria.api_token' => ['nullable', 'string', 'max:191'],
            'bulksmsnigeria.gateway' => ['nullable', 'string', 'max:100'],
            'generic.base_url' => ['nullable', 'url', 'max:255'],
            'generic.endpoint' => ['nullable', 'string', 'max:100'],
            'generic.balance_endpoint' => ['nullable', 'string', 'max:100'],
            'generic.api_key' => ['nullable', 'string', 'max:191'],
            'generic.auth_mode' => ['nullable', 'in:bearer,header,body,none'],
            'generic.auth_header_name' => ['nullable', 'string', 'max:100'],
            'generic.message_field' => ['nullable', 'string', 'max:100'],
            'generic.phone_field' => ['nullable', 'string', 'max:100'],
            'generic.sender_field' => ['nullable', 'string', 'max:100'],
            'generic.callback_field' => ['nullable', 'string', 'max:100'],
        ]);

        $this->settings->put('sms.active_provider', $data['active_provider'] ?? null);
        $this->settings->put('sms.sender_id', $data['sender_id'] ?? null);
        $this->settings->put('sms.callback_url', $data['callback_url'] ?? null);
        $this->settings->put('sms.providers.termii', $data['termii'] ?? []);
        $this->settings->put('sms.providers.bulksmsnigeria', $data['bulksmsnigeria'] ?? []);
        $this->settings->put('sms.providers.generic', $data['generic'] ?? []);

        return redirect()
            ->route('bulk-sms.settings.edit')
            ->with('status', 'SMS settings updated successfully.');
    }

    public function balance(): RedirectResponse
    {
        try {
            $result = $this->providerManager->provider()->balance();
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('bulk-sms.settings.edit')
                ->with('sms_balance_result', [
                    'successful' => false,
                    'message' => $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('bulk-sms.settings.edit')
            ->with('sms_balance_result', [
                'successful' => $result->successful,
                'balance' => $result->balance,
                'currency' => $result->currency,
                'message' => $result->message,
            ]);
    }

    public function testSend(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'test_phone' => ['required', 'string', 'max:50'],
            'test_message' => ['required', 'string', 'max:1000'],
            'test_sender_id' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $result = $this->providerManager->provider()->sendTest(
                (string) $data['test_phone'],
                (string) $data['test_message'],
                $data['test_sender_id'] ?: $this->settings->senderId()
            );
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('bulk-sms.settings.edit')
                ->withInput()
                ->with('sms_test_send_result', [
                    'successful' => false,
                    'message' => $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('bulk-sms.settings.edit')
            ->withInput()
            ->with('sms_test_send_result', [
                'successful' => $result->successful,
                'message' => $result->successful
                    ? 'Test SMS request submitted successfully.'
                    : ($result->errorMessage ?: 'Test SMS failed.'),
                'external_id' => $result->externalId,
            ]);
    }

    protected function providerOptions(): array
    {
        return [
            'termii' => 'Termii',
            'bulksmsnigeria' => 'BulkSMSNigeria',
            'generic' => 'Generic HTTP Provider',
        ];
    }
}
