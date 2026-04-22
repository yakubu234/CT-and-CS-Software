<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActiveBranchService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected ActiveBranchService $activeBranchService,
    ) {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'The provided login details do not match our staff records.',
                ]);
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || $user->user_type === 'customer' || $user->branch_account) {
            Auth::guard('web')->logout();

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'This login is restricted to the admin side of the application.',
                ]);
        }

        $request->session()->regenerate();
        $this->activeBranchService->ensureActiveBranch($user);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
