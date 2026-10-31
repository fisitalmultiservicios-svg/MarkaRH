@extends('layout')
@section('title', 'Seguridad de tu cuenta')
@section('content')
<div class="page-head"><div><h1>Seguridad de tu cuenta</h1><p>Verificación en dos pasos obligatoria para el Maestro.</p></div><span class="pill">MFA activo</span></div>
<section class="card official-notice"><h2>Tu respaldo de acceso</h2><p>Tienes <strong>{{ $remaining }}</strong> códigos de recuperación disponibles. Cada código se consume una sola vez.</p></section>
@if($recentRecovery)<div class="notice official-notice">Acabas de ingresar con un código de recuperación. Durante cinco minutos puedes confirmar tu contraseña para cambiar el autenticador o renovar tus códigos, aunque no tengas el teléfono.</div>@endif
<div class="grid2">
    <section class="card">
        <h2>Renovar códigos</h2>
        <p class="muted">Obtendrás 10 códigos nuevos. Los anteriores y las verificaciones MFA de tus otras sesiones quedarán invalidados.</p>
        <form method="post" action="{{ route('security.regenerate') }}">
            @csrf
            <div class="field"><label for="rotate-password">Confirma tu contraseña</label><input id="rotate-password" name="current_password" type="password" autocomplete="current-password" required maxlength="200"></div>
            @unless($recentRecovery)<div class="field"><label for="rotate-code">Código nuevo de tu autenticador</label><input id="rotate-code" name="totp_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></div>@endunless
            <button class="btn">Renovar respaldo</button>
        </form>
    </section>
    <section class="card">
        <h2>Cambiar de autenticador</h2>
        <p class="muted">Vincula otro teléfono o aplicación. Se invalidarán el autenticador y los códigos de recuperación anteriores.</p>
        <form method="post" action="{{ route('security.reset') }}">
            @csrf
            <div class="field"><label for="reset-password">Confirma tu contraseña</label><input id="reset-password" name="current_password" type="password" autocomplete="current-password" required maxlength="200"></div>
            @unless($recentRecovery)<div class="field"><label for="reset-code">Código nuevo de tu autenticador</label><input id="reset-code" name="totp_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></div>@endunless
            <label class="checkbox"><input type="checkbox" name="understand" value="1" required> Entiendo que debo configurar el nuevo autenticador antes de volver a entrar al panel.</label>
            <button class="btn secondary">Reconfigurar autenticador</button>
        </form>
    </section>
</div>
@endsection
