<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        abort_unless($request->user()?->role === $role, 403);
        if ($role === 'admin') {
            abort_unless(Tenant::whereKey($request->user()->tenant_id)->where('status', 'active')->exists(), 403);
        }

        return $next($request);
    }
}
