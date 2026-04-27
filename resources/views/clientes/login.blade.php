<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal de Clientes</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
</head>
<body class="ios-login-bg">
<div class="orb-accent"></div>

<div class="ios-login-container">
    <a href="{{ url('/') }}" class="ios-back-link">← Volver al inicio</a>

    <div class="ios-brand">
        <div class="ios-brand-logo">
            @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        </div>
        <div class="ios-icon-badge" style="background:rgba(0,122,255,0.15);border:1px solid rgba(0,122,255,0.25);">
            <svg viewBox="0 0 24 24" fill="none" stroke="rgba(147,197,253,0.9)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <p>Portal de clientes</p>
    </div>

    <div class="ios-login-card">
        <div class="card-title">Iniciar sesión</div>
        <div class="card-sub">Ingresa tus credenciales para continuar</div>

        @if(session('error'))
            <div class="ios-alert ios-alert-error">{{ session('error') }}</div>
        @endif
        @if(session('mensaje'))
            <div class="ios-alert ios-alert-success">{{ session('mensaje') }}</div>
        @endif

        <form method="POST" action="{{ route('clientes.login.procesar') }}">
            @csrf
            <div class="ios-field">
                <label>Usuario</label>
                <input type="text" name="usuario" placeholder="Tu usuario asignado" value="{{ old('usuario') }}" required autofocus>
            </div>
            <div class="ios-field">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="Tu contraseña" required>
            </div>
            <button type="submit" class="ios-btn-primary">Ingresar al portal</button>
        </form>

        <p class="ios-login-card-foot">
            ¿No tienes cuenta? Contacta a Industrias Salcom para ser dado de alta.
        </p>
    </div>

    <div class="ios-footer-text">
        <a href="{{ route('aviso.privacidad') }}">Aviso de Privacidad</a> · &copy; {{ date('Y') }} Industrias Salcom
    </div>
</div>

</body>
</html>
