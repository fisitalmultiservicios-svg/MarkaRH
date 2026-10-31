@extends('layout')
@section('title', 'Registros recibidos')
@section('content')
<div class="page-head"><div><h1>Registros recibidos</h1><p>Hechos originales recibidos por la API de terminales; sin edición ni creación desde la web.</p></div></div><section class="card">@include('admin.event-table'){{ $events->links() }}</section>
@endsection
