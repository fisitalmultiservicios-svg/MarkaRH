@extends('layout')
@section('title', 'Sucursales')
@section('content')
<div class="page-head"><div><h1>Sucursales</h1><p>Ubicaciones de esta empresa.</p></div></div><div class="grid2 official-notice">@foreach($branches as $branch)<article class="card"><h3>{{ $branch->name }}</h3><span class="muted">{{ $branch->timezone }}</span></article>@endforeach</div><section class="card"><h2>Agregar sucursal</h2><form method="post" action="{{ route('admin.branches.store') }}">@csrf<div class="field"><label for="branch-name">Nombre</label><input id="branch-name" name="name" value="{{ old('name') }}" required maxlength="100"></div><p class="muted">Zona horaria: America/Costa_Rica.</p><button class="btn">Crear sucursal</button></form></section>
@endsection
