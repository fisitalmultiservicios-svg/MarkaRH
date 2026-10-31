@extends('layout')
@section('title', 'Bitácora')
@section('content')
<div class="page-head"><div><h1>Bitácora</h1><p>Acciones de administración de esta empresa, sin contraseñas ni tokens.</p></div></div><section class="card"><div class="table-wrap"><table><thead><tr><th>Fecha UTC</th><th>Acción</th><th>Usuario</th><th>Entidad</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td><td>{{ $log->action }}</td><td>{{ $log->user_id ?? 'Terminal' }}</td><td>{{ $log->subject_id }}</td></tr>@empty<tr><td colspan="4" class="empty">No hay acciones registradas.</td></tr>@endforelse</tbody></table></div>{{ $logs->links() }}</section>
@endsection
