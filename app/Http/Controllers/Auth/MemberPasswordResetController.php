<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Email\EmailAutomationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

class MemberPasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.member-password-request');
    }

    public function requestReset(Request $request, EmailAutomationService $emails): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $member = User::query()->where('email', $data['email'])
            ->where('user_type', 'customer')->where('branch_account', false)
            ->where('status', 1)->first();

        if ($member && EmailTemplate::query()->where('category', 'password_resets')->where('status', true)->exists()) {
            $token = Password::broker()->createToken($member);
            $emails->passwordResetRequested($member, route('member-password.reset', ['token' => $token, 'email' => $member->email]));
        }

        return back()->with('status', 'If this is an active member account and password reset email is configured, a reset link will be sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.member-password-reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker()->reset($data, function (User $user, string $password): void {
            abort_unless($user->user_type === 'customer' && ! $user->branch_account && $user->status == 1 && ! $user->trashed(), 403);
            $user->forceFill(['password' => Hash::make($password), 'must_change_password' => false])->save();
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password has been reset. You can sign in now.')
            : back()->withErrors(['email' => 'This reset link is invalid or expired.'])->withInput($request->only('email'));
    }
}
