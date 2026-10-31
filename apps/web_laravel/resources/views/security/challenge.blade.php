@extends('security.frame')
@section('title', 'Verifica tu acceso')
@section('form')
<span class="pill blue">Último paso</span>
<h2>{{ $method === 'recovery' ? 'Usa un código de recuperación.' : 'Confirma que eres tú.' }}</h2>
<p class="muted">{{ $method === 'recovery' ? 'Ingresa uno de los códigos que guardaste al configurar tu autenticador. Cada código funciona una sola vez.' : 'Abre tu app de autenticación e ingresa el código de MarkaRH.' }}</p>
<form method="post" action="{{ route('security.verify') }}">
    @csrf
    <input type="hidden" name="method" value="{{ $method }}">
    @if($method === 'recovery')
    <div class="field"><label for="recovery-code">Código de recuperación</label><input id="recovery-code" name="recovery_code" autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="35" required placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx"></div>
    @else
    <div class="field"><label for="totp-code">Código de 6 dígitos</label><input id="totp-code" class="mfa-code" name="totp_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus><small>Si acabas de usarlo, espera a que aparezca el siguiente código.</small></div>
    @endif
    <button class="btn full-width">Verificar e ingresar →</button>
</form>
<div class="mfa-alternative"><a href="{{ route('security.challenge', $method === 'recovery' ? [] : ['method' => 'recovery']) }}">{{ $method === 'recovery' ? 'Usar mi autenticador' : 'No tengo mi teléfono · Usar código de recuperación' }}</a></div>
@endsection
