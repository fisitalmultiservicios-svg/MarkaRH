@extends('layout')
@section('title', 'Consultas del sitio')
@section('content')
<div class="page-head"><div><h1>Consultas del sitio</h1><p>Mensajes enviados desde el formulario público, visibles únicamente al Maestro.</p></div></div><div class="stack">@forelse($inquiries as $inquiry)<article class="card"><h3>{{ $inquiry->company }}</h3><p class="muted">{{ $inquiry->name }} · {{ $inquiry->email }}</p><p>{{ $inquiry->message }}</p><small>Consentimiento registrado: {{ $inquiry->consented_at }} UTC</small></article>@empty<div class="card empty">Aún no hay consultas recibidas.</div>@endforelse</div>{{ $inquiries->links() }}
@endsection
