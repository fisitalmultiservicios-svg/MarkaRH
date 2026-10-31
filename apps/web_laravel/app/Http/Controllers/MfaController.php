<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\MfaService;
use App\Services\MfaSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class MfaController extends Controller
{
    public function __construct(private MfaService $mfa) {}

    public function setup(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->mfa_confirmed_at) {
            return redirect()->route('security.challenge');
        }
        if (! MfaSession::pendingIsFresh($request, $user)) {
            return $this->expired($request);
        }
        $secret = $request->session()->get('mfa.enrollment_secret');
        if (! is_string($secret)) {
            $secret = $this->mfa->secret();
            $request->session()->put('mfa.enrollment_secret', $secret);
        }

        return view('security.setup', ['secret' => $secret, 'qr' => $this->mfa->qr($user, $secret)]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! MfaSession::pendingIsFresh($request, $user)) {
            return $this->expired($request);
        }
        $data = $request->validate(['totp_code' => ['required', 'string', 'digits:6']]);
        $secret = $request->session()->get('mfa.enrollment_secret');
        if (! is_string($secret)) {
            return redirect()->route('security.setup');
        }
        $result = $this->guarded($request, fn (): array => $this->mfa->enable($user, $secret, $data['totp_code']));
        Auth::setUser($result['user']);
        MfaSession::complete($request, $result['user']);

        return redirect()->route('security.recovery')->with('new_recovery_codes', $result['codes'])->with('status', 'Autenticación en dos pasos activada. Guarda tus códigos de recuperación.');
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->mfa_confirmed_at) {
            return redirect()->route('security.setup');
        }
        if (MfaSession::verified($request, $user)) {
            return redirect()->route('master.index');
        }
        if (! MfaSession::pendingIsFresh($request, $user)) {
            return $this->expired($request);
        }

        return view('security.challenge', ['method' => $request->query('method') === 'recovery' ? 'recovery' : 'totp']);
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->mfa_confirmed_at) {
            return redirect()->route('security.setup');
        }
        if (! MfaSession::pendingIsFresh($request, $user)) {
            return $this->expired($request);
        }
        $data = $request->validate(['method' => ['required', 'in:totp,recovery'], 'totp_code' => ['required_if:method,totp', 'nullable', 'string', 'digits:6'], 'recovery_code' => ['required_if:method,recovery', 'nullable', 'string', 'regex:/^(?:[a-fA-F0-9]{32}|[a-fA-F0-9]{8}(?:-[a-fA-F0-9]{8}){3})$/']]);
        $code = $data['method'] === 'recovery' ? $data['recovery_code'] : $data['totp_code'];
        $verified = $this->guarded($request, fn () => $this->mfa->challenge($user, $data['method'], $code));
        Auth::setUser($verified);
        MfaSession::complete($request, $verified, $data['method'] === 'recovery' ? now()->timestamp : null);

        return redirect()->route('master.index')->with('status', $data['method'] === 'recovery' ? 'Acceso confirmado. El código de recuperación utilizado ya no es válido. Puedes reconfigurar tu autenticador desde Seguridad.' : 'Acceso confirmado.');
    }

    public function index(Request $request): View
    {
        return view('security.index', ['remaining' => count($request->user()->mfa_recovery_codes ?? []), 'recentRecovery' => MfaSession::recentRecovery($request, $request->user())]);
    }

    public function recovery(Request $request): View
    {
        return view('security.recovery', ['codes' => $request->session()->pull('new_recovery_codes', [])]);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $recent = MfaSession::recentRecovery($request, $request->user());
        $data = $this->actionData($request, $recent);
        $recoveryAt = $recent ? $request->session()->get('mfa.recovery_at') : null;
        $result = $this->guarded($request, fn () => $this->mfa->regenerate($request->user(), $data['current_password'], $data['totp_code'] ?? '', $recent));
        Auth::setUser($result['user']);
        MfaSession::complete($request, $result['user'], $recoveryAt);

        return redirect()->route('security.recovery')->with('new_recovery_codes', $result['codes'])->with('status', 'Códigos renovados. Los códigos anteriores y las verificaciones MFA de otras sesiones quedaron invalidados.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $recent = MfaSession::recentRecovery($request, $request->user());
        $data = $this->actionData($request, $recent);
        $request->validate(['understand' => 'accepted']);
        $user = $this->guarded($request, fn () => $this->mfa->reset($request->user(), $data['current_password'], $data['totp_code'] ?? '', $recent));
        Auth::setUser($user);
        MfaSession::begin($request, $user);

        return redirect()->route('security.setup')->with('status', 'Configura tu nuevo autenticador. El acceso al panel permanece bloqueado hasta confirmar el nuevo código.');
    }

    private function actionData(Request $request, bool $recentRecovery): array
    {
        return $request->validate(['current_password' => ['required', 'string', 'max:200'], 'totp_code' => [$recentRecovery ? 'nullable' : 'required', 'string', 'digits:6']]);
    }

    private function guarded(Request $request, callable $operation): mixed
    {
        $key = 'mfa-attempts:'.$request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['totp_code' => 'Demasiados intentos. Espera cinco minutos antes de volver a intentarlo.']);
        }
        try {
            $result = $operation();
        } catch (ValidationException $exception) {
            RateLimiter::hit($key, 300);
            AuditLog::record($request->user(), 'mfa.verification_failed');
            throw $exception;
        }
        RateLimiter::clear($key);

        return $result;
    }

    private function expired(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => 'Por seguridad, vuelve a ingresar tu contraseña para continuar.']);
    }
}
