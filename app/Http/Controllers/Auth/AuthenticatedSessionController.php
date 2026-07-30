<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActiveBranchService;
use App\Services\Auth\StaffLoginOtpService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
        protected StaffLoginOtpService $staffLoginOtpService,
    ) {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ], attributes: [
            'login' => 'email or member number',
        ]);

        $remember = $request->boolean('remember');
        $user = $this->findUserForLogin($validated['login']);

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors([
                    'login' => 'The provided login details do not match our records.',
                ]);
        }

        if ($user->branch_account) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors([
                    'login' => 'This login cannot access the application.',
                ]);
        }

        if ($user->user_type === 'customer') {
            Auth::login($user, $remember);
            $request->session()->regenerate();
            $this->activeBranchService->ensureActiveBranch($user);

            if ($user->must_change_password) {
                return redirect()->intended(route('customer.password.edit'));
            }

            return redirect()->intended(route('customer.dashboard'));
        }

        try {
            $result = $this->staffLoginOtpService->begin($user, $request);
        } catch (\Throwable $exception) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Unable to send the staff verification code: ' . $exception->getMessage()]);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'staff_login_otp_token' => $result['token'],
            'staff_login_otp_remember' => $remember,
        ]);

        return redirect()
            ->route('staff-otp.create')
            ->with('status', $result['sent']
                ? 'A six-digit verification code was sent to your email address.'
                : 'Your existing verification code is still active.');
    }

    protected function findUserForLogin(string $login): ?User
    {
        $login = trim($login);

        return User::query()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($login): void {
                $query->where('email', $login)
                    ->orWhere('member_no', $login)
                    ->orWhereHas('detail', function ($detailQuery) use ($login): void {
                        $detailQuery->where('member_no', $login);
                    });
            })
            ->first();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
