<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2d1b4e 0%, #4A2070 30%, #6B3FA0 60%, #9C6DD0 100%);
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow: hidden;
        }
        /* Subtle animated shapes */
        body::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: rgba(107,63,160,0.15);
            top: -200px; right: -150px;
            filter: blur(80px);
        }
        body::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(139,92,246,0.12);
            bottom: -100px; left: -100px;
            filter: blur(60px);
        }

        .login-container {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            width: 100%;
            max-width: 420px;
            padding: 32px 24px;
        }

        /* Brand */
        .brand { text-align: center; margin-bottom: 8px; }
        .brand h1 { font-size: 28px; font-weight: 700; color: #fff; letter-spacing: -0.5px; }
        .brand p { font-size: 12px; font-weight: 500; letter-spacing: 3px; color: rgba(255,255,255,0.5); text-transform: uppercase; margin-top: 4px; }

        /* Card */
        .login-card {
            width: 100%;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 16px;
            padding: 36px 32px;
        }

        .card-title { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .card-sub { font-size: 13px; color: rgba(255,255,255,0.5); margin-bottom: 24px; }

        /* Alerts */
        .alert { border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 18px; }
        .alert-error { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; }
        .alert-success { background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7; }

        /* Fields */
        .field { margin-bottom: 18px; }
        .field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            margin-bottom: 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .field input {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 14px;
            font-family: inherit;
            color: #fff;
            background: rgba(255,255,255,0.06);
            transition: all .2s;
            outline: none;
        }
        .field input::placeholder { color: rgba(255,255,255,0.3); }
        .field input:focus {
            border-color: rgba(107,63,160,0.6);
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(107,63,160,0.15);
        }

        .forgot { text-align: right; margin-top: -10px; margin-bottom: 18px; }
        .forgot a { font-size: 12px; color: rgba(255,255,255,0.5); text-decoration: none; }
        .forgot a:hover { color: rgba(255,255,255,0.8); }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: #6B3FA0;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: all .2s;
            box-shadow: 0 4px 16px rgba(107,63,160,0.3);
        }
        .btn-login:hover { background: #4A2070; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(107,63,160,0.4); }
        .btn-login:active { transform: translateY(0); }

        .register-link {
            text-align: center;
            font-size: 13px;
            color: rgba(255,255,255,0.5);
        }
        .register-link a { color: #c4b5fd; text-decoration: none; font-weight: 600; }
        .register-link a:hover { color: #ddd6fe; }

        .footer-text {
            font-size: 11px;
            color: rgba(255,255,255,0.25);
            text-align: center;
        }

        /* Muestras card */
        .muestras-card {
            width: 100%;
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .muestras-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .muestras-info { flex: 1; }
        .muestras-title { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.9); }
        .muestras-desc { font-size: 11px; color: rgba(255,255,255,0.4); }
        .muestras-badge { font-size: 10px; font-weight: 600; padding: 3px 10px; border-radius: 999px; background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.6); flex-shrink: 0; }

        @media (max-width: 500px) {
            .login-container { padding: 24px 16px; }
            .login-card { padding: 28px 20px; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <a href="/" style="font-size:12px;color:rgba(255,255,255,0.4);text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:8px;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,0.8)'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">← Volver al inicio</a>

    <div class="brand">
        @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        <div style="width:64px;height:64px;border-radius:16px;background:rgba(107,63,160,0.25);border:1px solid rgba(107,63,160,0.35);display:flex;align-items:center;justify-content:center;margin:16px auto 8px;font-size:28px;">🏭</div>
        <p>PORTAL DE PROVEEDORES</p>
    </div>

    <div class="login-card">
        <div class="card-title">Iniciar sesión</div>
        <div class="card-sub">Ingresa tus credenciales para continuar</div>

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if(session('mensaje'))
            <div class="alert alert-success">{{ session('mensaje') }}</div>
        @endif

        <form method="POST" action="/login-proveedor">
            @csrf
            <div class="field">
                <label>Usuario</label>
                <input type="text" name="codigo" placeholder="Tu correo o usuario" value="{{ old('codigo') }}" required autofocus>
            </div>
            <div class="field">
                <label>Contraseña</label>
                <input type="password" name="pwd" placeholder="Tu contraseña" required>
            </div>
            <div class="forgot"><a href="#">¿Olvidaste tu contraseña?</a></div>
            <div class="field">
                <label>Código de compras</label>
                <input type="text" name="codigo_compras" placeholder="Código asignado (opcional)">
            </div>
            <button type="submit" class="btn-login">Ingresar al portal</button>
        </form>

        <p class="register-link" style="margin-top:18px;">
            ¿Eres proveedor nuevo? <a href="{{ route('proveedores.registro') }}">Regístrate aquí</a>
        </p>
    </div>

    <a href="{{ route('muestras.crear') }}" class="muestras-card" style="text-decoration:none;">
        <div class="muestras-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
        <div class="muestras-info"><div class="muestras-title">Envío de muestras</div><div class="muestras-desc">Registro y seguimiento de muestras</div></div>
        <span class="muestras-badge" style="background:rgba(107,63,160,0.3);color:#c4b5fd;">Entrar →</span>
    </a>

    <div class="footer-text">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>

</body>
</html>
