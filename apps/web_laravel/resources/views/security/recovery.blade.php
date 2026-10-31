@extends('layout')
@section('title', 'Códigos de recuperación')
@section('content')
<div class="page-head"><div><h1>Tu respaldo para recuperar el acceso.</h1><p>Guárdalo fuera de tu teléfono y no lo compartas.</p></div></div>
<section class="card mfa-recovery-card">
@if(count($codes))
    <span class="pill">MFA activado</span>
    <h2>Guarda estos 10 códigos.</h2>
    <p class="muted">Se muestran una sola vez. Cada uno te permite ingresar si pierdes el autenticador y deja de funcionar después de usarlo.</p>
    <ul class="mfa-recovery-list" id="recovery-codes">@foreach($codes as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
    <div class="row wrap"><button type="button" class="btn secondary" id="download-recovery">Descargar códigos</button><a class="btn" href="{{ route('master.index') }}">Ya los guardé · Continuar →</a></div>
    <p class="security-fine">Guarda el archivo en un lugar privado, preferiblemente separado de tu contraseña y de tu dispositivo habitual.</p>
@else
    <h2>Estos códigos ya no se muestran.</h2>
    <p class="muted">Puedes renovar el respaldo desde Seguridad. Se invalidarán los códigos anteriores.</p>
    <a class="btn" href="{{ route('security.index') }}">Ir a Seguridad →</a>
@endif
</section>
@push('scripts')<script src="{{ asset('assets/mfa.js') }}" defer></script>@endpush
@endsection
