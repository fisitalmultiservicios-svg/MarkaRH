<?php

use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\RequireRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => RequireRole::class, 'device' => AuthenticateDevice::class]);
        $middleware->append(SecurityHeaders::class);
        $middleware->prependToPriorityList(ThrottleRequests::class, AuthenticateDevice::class);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $r) => route($r->user()->role === 'master' ? 'master.index' : 'admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $r) => $r->is('api/*') || $r->expectsJson());
        $exceptions->dontFlash(['password', 'password_confirmation', 'enrollment_code', 'token']);
    })->create();
