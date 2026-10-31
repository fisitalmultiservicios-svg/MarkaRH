<!doctype html>
<html lang="es-CR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>@yield('title') · MarkaRH</title>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/official.css') }}">
</head>
<body>
<header class="public-nav security-nav">
    <a href="{{ route('home') }}"><img class="brand" src="{{ asset('assets/logo.svg') }}" alt="MarkaRH"></a>
    <form action="{{ route('logout') }}" method="post">@csrf<button class="btn secondary small">Cerrar sesión</button></form>
</header>
<main class="security-shell">
    <aside class="security-intro">
        <span class="security-shield" aria-hidden="true">✓</span>
        <div class="eyebrow">PROTEGE TU ESPACIO MAESTRO</div>
        <h1>Un paso más.<br>Una cuenta más segura.</h1>
        <p>Tu contraseña es el primer paso. El segundo es un código que solo tú puedes generar.</p>
        <div class="security-checklist"><span>✓ Contraseña verificada</span><span>2 · Confirma tu autenticador</span><span>3 · Ingresa a tu espacio</span></div>
        <p class="security-fine">La verificación pendiente vence a los 10 minutos. Nunca compartas tus códigos ni la clave de configuración.</p>
    </aside>
    <section class="card security-card">
        @include('messages')
        @yield('form')
    </section>
</main>
</body>
</html>
