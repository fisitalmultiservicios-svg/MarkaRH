<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:200'], 'password' => ['required', 'string', 'max:200']]);
        $key = 'login:'.hash('sha256', mb_strtolower($data['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Espera un minuto.']);
        }
        if (! Auth::attempt(['email' => mb_strtolower($data['email']), 'password' => $data['password']])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'No pudimos validar tus credenciales.']);
        }
        $user = $request->user();
        if (! in_array($user->role, ['master', 'admin'], true) || ($user->role === 'admin' && ! Tenant::whereKey($user->tenant_id)->where('status', 'active')->exists())) {
            Auth::logout();
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'No pudimos validar tus credenciales.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        AuditLog::record($user, 'auth.login');

        return redirect()->route($user->role === 'master' ? 'master.index' : 'admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        AuditLog::record($request->user(), 'auth.logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
