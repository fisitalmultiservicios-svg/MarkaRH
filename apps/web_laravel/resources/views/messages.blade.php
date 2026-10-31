@if(session('status'))<div class="notice official-notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice warn official-notice" role="alert"><b>Revisa los datos del formulario.</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
