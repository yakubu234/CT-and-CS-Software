<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $permissions = $request->isMethodSafe()
            ? [$module . '.view', $module . '.manage']
            : [$module . '.manage'];

        if (! $user->hasAnyPermission($permissions)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
