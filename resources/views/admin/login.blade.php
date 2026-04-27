<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Panel Administrativo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
</head>
<body class="ios-login-bg">
<div class="orb-accent"></div>

<div class="ios-login-container">
    <a href="/" class="ios-back-link">← Volver al inicio</a>

    <div class="ios-brand">
        @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        <div class="ios-icon-badge" style="background:rgba(139,92,246,0.2);border:1px solid rgba(139,92,246,0.3);">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(196,181,253,0.9)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <p>PANEL ADMINISTRATIVO</p>
    </div>

    <div class="ios-login-card">
        <span class="ios-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Acceso restringido
        </span>
        <div class="card-title">Iniciar sesión</div>
        <div class="card-sub">Ingresa tus credenciales de administrador</div>

        @if(session('error'))
            <div class="ios-alert ios-alert-error">{{ session('error') }}</div>
        @endif
        @if(session('mensaje'))
            <div class="ios-alert ios-alert-success">{{ session('mensaje') }}</div>
        @endif

        <form method="POST" action="/login-admin">
            @csrf
            <div class="ios-field">
                <label>Usuario</label>
                <input type="text" name="usuario" placeholder="Tu usuario de administrador" value="{{ old('usuario') }}" required autofocus>
            </div>
            <div class="ios-field">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="Tu contraseña" required>
            </div>
            <button type="submit" class="ios-btn-primary">Ingresar al panel</button>
        </form>
    </div>

    <div class="ios-footer-text">
        <a href="/aviso-privacidad">Aviso de Privacidad</a> · &copy; {{ date('Y') }} Industrias Salcom
    </div>
</div>

</body>
</html>
