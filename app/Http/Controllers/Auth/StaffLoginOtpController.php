<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActiveBranchService;
use App\Services\Auth\StaffLoginOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffLoginOtpController extends Controller
{
    public function __construct(
        protected StaffLoginOtpService $otpService,
        protected ActiveBranchService $activeBranchService,
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        $challenge = $this->otpService->findActive(
            (string) $request->session()->get('staff_login_otp_token')
        );

        if (! $challenge) {
            return redirect()->route('login')->withErrors([
                'login' => 'Your staff verification session expired. Please sign in again.',
            ]);
        }

        return view('auth.staff-otp', [
            'maskedEmail' => $this->maskEmail($challenge->user->email),
            'expiresAt' => $challenge->expires_at,
            'resendAt' => $challenge->last_sent_at?->addSeconds(StaffLoginOtpService::RESEND_SECONDS),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);
        $token = (string) $request->session()->get('staff_login_otp_token');
        $user = $this->otpService->verify($token, $validated['code']);

        if (! $user || ! $user->isAdminSideUser()) {
            return back()->withErrors([
                'code' => 'The verification code is invalid, expired, or has reached its attempt limit.',
            ]);
        }

        $remember = (bool) $request->session()->pull('staff_login_otp_remember', false);
        $request->session()->forget('staff_login_otp_token');
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->activeBranchService->ensureActiveBranch($user);

        return redirect()->intended(route('dashboard'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $token = (string) $request->session()->get('staff_login_otp_token');

        try {
            $result = $this->otpService->resend($token, $request);
            $request->session()->put('staff_login_otp_token', $result['token']);
        } catch (\Throwable $exception) {
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with(
            'status',
            $result['sent']
                ? 'A verification code was sent to your email address.'
                : 'Your existing code is still active. Please wait before requesting another email.'
        );
    }

    protected function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = Str::substr($name, 0, min(2, Str::length($name)));

        return $visible . str_repeat('*', max(2, Str::length($name) - Str::length($visible))) . '@' . $domain;
    }
}
