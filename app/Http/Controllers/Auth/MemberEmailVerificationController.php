<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MemberEmailVerificationController extends Controller
{
    public function __invoke(User $member, string $hash): RedirectResponse
    {
        abort_unless($member->user_type === 'customer' && ! $member->branch_account && hash_equals(sha1(strtolower($member->email)), $hash), 403);

        $member->forceFill(['is_verified' => true, 'email_verified_at' => now()])->save();

        return redirect()->route('login')->with('status', 'Your email address has been verified. You can sign in.');
    }
}
