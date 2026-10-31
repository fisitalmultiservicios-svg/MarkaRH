<?php

namespace App\Http\Middleware;

use App\Services\MfaSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireMfa
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->role === 'master' && ! MfaSession::verified($request, $user)) {
            return redirect()->route($user->mfa_confirmed_at ? 'security.challenge' : 'security.setup');
        }

        return $next($request);
    }
}
