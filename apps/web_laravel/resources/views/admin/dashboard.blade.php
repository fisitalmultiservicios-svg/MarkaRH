@extends('layout')
@section('title', 'Vista general')
@section('content')
<div class="page-head"><div><h1>{{ $tenant->name }}</h1><p>Información recibida por el backend de esta empresa.</p></div></div><div class="stats"><div class="stat"><span class="muted">Personal activo</span><div class="stat-value">{{ $employeeCount }}</div></div><div class="stat"><span class="muted">Terminales autorizadas</span><div class="stat-value">{{ $deviceCount }}</div></div><div class="stat"><span class="muted">Eventos recibidos</span><div class="stat-value">{{ $eventCount }}</div></div></div><div class="notice official-notice">La falta de eventos de un dispositivo desconectado no demuestra ausencia. Esta base no calcula horas ni salarios.</div><section class="card"><h2>Últimos eventos recibidos</h2>@include('admin.event-table')</section>
@endsection
