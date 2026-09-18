<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Email\EmailAutomationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MemberVerificationRequestController extends Controller
{
    public function create(): View
    {
        return view('auth.member-verification-request');
    }

    public function store(Request $request, EmailAutomationService $emails): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $member = User::query()->where('email', $data['email'])
            ->where('user_type', 'customer')->where('branch_account', false)
            ->where('status', 1)->whereNotNull('email_verification_required_at')
            ->where('is_verified', false)->first();

        if ($member && EmailTemplate::query()->where('category', 'account_verification')->where('status', true)->exists()) {
            $emails->accountVerificationRequested($member, true);
        }

        return back()->with('status', 'If this member account requires verification, a new link will be sent to its registered email address.');
    }
}
