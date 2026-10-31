@extends('layout')
@section('title', 'Editar empleado')
@section('content')
<section class="card"><h1>Editar empleado</h1><form method="post" action="{{ route('admin.employees.update', $employee) }}">@csrf @method('PATCH') @include('admin.employee-fields')<div class="field"><label for="active">Estado</label><select id="active" name="active"><option value="1" @selected(old('active', $employee->active) == 1)>Activo</option><option value="0" @selected(old('active', $employee->active) == 0)>Inactivo</option></select></div><div class="row"><button class="btn">Guardar cambios</button><a class="btn secondary" href="{{ route('admin.employees') }}">Volver</a></div></form></section>
@endsection
