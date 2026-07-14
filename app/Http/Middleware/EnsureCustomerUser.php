<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== 'customer' || $user->branch_account) {
            return redirect()->route('dashboard');
        }

        if ($user->must_change_password && ! $request->routeIs('customer.password.*')) {
            return redirect()->route('customer.password.edit');
        }

        return $next($request);
    }
}
