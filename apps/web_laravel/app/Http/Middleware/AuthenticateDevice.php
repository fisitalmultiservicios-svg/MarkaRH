<?php

namespace App\Http\Middleware;

use App\Models\Device;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $device = $token ? Device::where('token_hash', hash('sha256', $token))->whereNull('revoked_at')->first() : null;
        abort_unless($device && Tenant::whereKey($device->tenant_id)->where('status', 'active')->exists(), 401);
        $request->attributes->set('device', $device);

        return $next($request);
    }
}
