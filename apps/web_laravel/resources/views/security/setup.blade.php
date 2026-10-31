@extends('security.frame')
@section('title', 'Configura tu autenticador')
@section('form')
<span class="pill blue">Configuración inicial</span>
<h2>Activa la verificación en dos pasos.</h2>
<p class="muted">En tu app de autenticación, elige agregar cuenta y escanea este QR. Puedes usar Google Authenticator, Microsoft Authenticator o una app compatible.</p>
<div class="mfa-qr"><img src="{{ $qr }}" width="240" height="240" alt="QR privado para agregar tu cuenta MarkaRH al autenticador"></div>
<details class="mfa-manual"><summary>¿No puedes escanear el QR?</summary><p>Agrega una cuenta manual: código temporal TOTP, 6 dígitos, cada 30 segundos.</p><code class="secret-code">{{ $secret }}</code></details>
<form method="post" action="{{ route('security.enable') }}">
    @csrf
    <div class="field"><label for="totp-code">Código de 6 dígitos de tu app</label><input id="totp-code" class="mfa-code" name="totp_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required><small>Ingresa el código para comprobar que el autenticador quedó vinculado.</small></div>
    <button class="btn full-width">Activar y guardar códigos →</button>
</form>
@endsection
