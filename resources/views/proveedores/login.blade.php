<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
</head>
<body class="ios-login-bg">
<div class="orb-accent"></div>

<div class="ios-login-container">
    <a href="/" class="ios-back-link">← Volver al inicio</a>

    <div class="ios-brand">
        @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        <div class="ios-icon-badge" style="background:rgba(107,63,160,0.2);border:1px solid rgba(107,63,160,0.3);">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(196,181,253,0.9)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <p>PORTAL DE PROVEEDORES</p>
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

        <form method="POST" action="/login-proveedor">
            @csrf
            <div class="ios-field">
                <label>Usuario</label>
                <input type="text" name="codigo" placeholder="Tu correo o usuario" value="{{ old('codigo') }}" required autofocus>
            </div>
            <div class="ios-field">
                <label>Contraseña</label>
                <input type="password" name="pwd" placeholder="Tu contraseña" required>
            </div>
            <div class="ios-field">
                <label>Código de compras</label>
                <input type="text" name="codigo_compras" placeholder="Código asignado (opcional)">
            </div>
            <button type="submit" class="ios-btn-primary">Ingresar al portal</button>
        </form>

        <p class="ios-register-link">
            ¿Eres proveedor nuevo? <a href="{{ route('proveedores.registro') }}">Regístrate aquí</a>
        </p>
    </div>

    <a href="{{ route('muestras.crear') }}" class="ios-muestras-card">
        <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <div style="flex:1"><div style="font-size:13px;font-weight:600;color:rgba(255,255,255,0.9)">Envío de muestras</div><div style="font-size:11px;color:rgba(255,255,255,0.4)">Registro y seguimiento</div></div>
        <span style="font-size:10px;font-weight:600;padding:4px 12px;border-radius:999px;background:rgba(107,63,160,0.25);color:#c4b5fd;flex-shrink:0;">Entrar →</span>
    </a>

    <div class="ios-footer-text">
        <a href="/aviso-privacidad">Aviso de Privacidad</a> · &copy; {{ date('Y') }} Industrias Salcom
    </div>
</div>

</body>
</html>
