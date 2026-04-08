<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Proveedor — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#EDE7F6;
            --purple-mid:  #9C6DD0;
            --gray-text:   #4A4A6A;
            --border:      #D8CFE8;
            --white:       #FFFFFF;
            --amber:       #D97706;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            height: 100vh;
            display: flex;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .left {
            width: 52%;
            background: linear-gradient(160deg, var(--purple-dark) 0%, var(--purple) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 56px;
            position: relative;
            overflow: hidden;
        }

        .left-content { position: relative; z-index: 1; text-align: center; width: 100%; }

        .brand-logo {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            color: var(--white);
            font-weight: 600;
            letter-spacing: -1px;
            line-height: 1.2;
        }
        .brand-sub {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 4px;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            margin-top: 6px;
            margin-bottom: 36px;
        }

        .left-divider {
            width: 48px;
            height: 2px;
            background: rgba(255,255,255,0.35);
            margin: 0 auto 28px;
            border-radius: 2px;
        }

        .left-tagline {
            font-size: 18px;
            color: rgba(255,255,255,0.9);
            font-weight: 300;
            line-height: 1.6;
            max-width: 300px;
            margin: 0 auto;
        }
        .left-tagline strong {
            color: var(--white);
            font-weight: 700;
            display: block;
            font-size: 20px;
            margin-bottom: 4px;
        }

        .left-badges {
            display: flex;
            gap: 24px;
            justify-content: center;
            margin-top: 32px;
            flex-wrap: wrap;
        }
        .badge {
            background: none;
            border: none;
            box-shadow: none;
            border-radius: 0;
            padding: 0;
            font-size: 13px;
            color: rgba(255,255,255,0.7);
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .badge::before {
            content: '';
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            flex-shrink: 0;
        }

        /* ── ENVÍO DE MUESTRAS (right panel) ── */
        .muestras-card {
            background: var(--purple-light);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px;
            text-align: center;
            width: 100%;
            max-width: 380px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .muestras-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            background: var(--white);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .muestras-info { text-align: left; flex: 1; }
        .muestras-titulo {
            font-size: 13px;
            font-weight: 600;
            color: var(--purple-dark);
            margin-bottom: 2px;
        }
        .muestras-desc {
            font-size: 12px;
            color: #888;
            line-height: 1.4;
        }
        .muestras-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            background: #FEF3C7;
            color: #D97706;
            border: none;
            flex-shrink: 0;
        }

        .left-footer {
            position: absolute;
            bottom: 24px;
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            letter-spacing: 0.5px;
        }

        .right {
            flex: 1;
            background: var(--white);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            position: relative;
            gap: 20px;
        }

        .deco-blob { display: none; }

        .card {
            background: var(--white);
            border-radius: 20px;
            padding: 44px 40px 48px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 8px 40px rgba(107,63,160,0.10);
            border: 1px solid rgba(107,63,160,0.08);
            animation: fadeUp .45s ease both;
            position: relative; z-index: 1;
        }
        @keyframes fadeUp {
            from { opacity:0; transform: translateY(18px); }
            to   { opacity:1; transform: translateY(0); }
        }

        .card-header { margin-bottom: 30px; }
        .card-header .icon-wrap {
            width: 50px; height: 50px;
            border-radius: 14px;
            background: var(--purple-light);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px;
        }
        .card-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--purple-dark);
            font-weight: 600;
        }
        .card-header p { font-size: 13px; color: #999; margin-top: 4px; }

        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-text);
            margin-bottom: 6px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .field input {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--gray-text);
            background: var(--white);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .field input::placeholder { color: #C4BDD4; }
        .field input:focus {
            border-color: var(--purple-mid);
            box-shadow: 0 0 0 3px rgba(156,109,208,0.12);
        }

        .forgot { text-align: right; margin-top: -8px; margin-bottom: 20px; }
        .forgot a { font-size: 12px; color: var(--purple-mid); text-decoration: none; }
        .forgot a:hover { text-decoration: underline; }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--purple);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(107,63,160,0.25);
            margin-top: 4px;
        }
        .btn-submit:hover { background: var(--purple-dark); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(107,63,160,0.35); }
        .btn-submit:active { transform: translateY(0); }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #999;
        }
        .register-link a { color: var(--purple); text-decoration: none; font-weight: 600; }
        .register-link a:hover { text-decoration: underline; }

        .alert-error { background: #FEE2E2; border-left: 3px solid #C0392B; border-radius: 8px; padding: 10px 14px; font-size: 13px; color: #991B1B; margin-bottom: 18px; }
        .alert-success { background: #D1FAE5; border-left: 3px solid #059669; border-radius: 8px; padding: 10px 14px; font-size: 13px; color: #065F46; margin-bottom: 18px; }

        @media (max-width: 700px) {
            body { flex-direction: column; height: auto; overflow: auto; }
            .left { width: 100%; padding: 40px 24px; min-height: 220px; }
            .brand-logo { font-size: 28px; }
            .left-badges, .left-tagline, .modulos-wrap { display: none; }
            .right { padding: 32px 16px 48px; }
            .card { padding: 28px 22px 36px; }
        }
    </style>
</head>
<body>

<div class="left">
    <div class="left-content">
        <div class="brand-logo">Industrias Salcom</div>
        <div class="brand-sub">Portal de Proveedores</div>
        <div class="left-divider"></div>
        <div class="left-tagline">
            <strong>Bienvenido</strong>
            Gestiona tus pedidos y da seguimiento a tus operaciones con nosotros.
        </div>

        <div class="left-badges">
            <span class="badge">Pedidos en línea</span>
            <span class="badge">Seguimiento</span>
            <span class="badge">Facturas</span>
        </div>

    </div>

    <div class="left-footer">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>

<div class="right">
    <div class="card">
        <div class="card-header">
            <div class="icon-wrap">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h2>Bienvenido de nuevo</h2>
            <p>Ingresa tus credenciales para continuar</p>
        </div>

        @if(session('error'))
            <div class="alert-error">{{ session('error') }}</div>
        @endif

        @if(session('mensaje'))
            <div class="alert-success">{{ session('mensaje') }}</div>
        @endif

        <form method="POST" action="/login-proveedor">
            @csrf

            <div class="field">
                <label>Usuario</label>
                <input type="text" name="codigo"
                       placeholder="Tu correo o usuario"
                       value="{{ old('codigo') }}"
                       required autofocus>
            </div>

            <div class="field">
                <label>Contraseña</label>
                <input type="password" name="pwd"
                       placeholder="Tu contraseña"
                       required>
            </div>

            <div class="forgot">
                <a href="#">¿Olvidaste tu contraseña?</a>
            </div>

            <div class="field">
                <label>Código de compras</label>
                <input type="text" name="codigo_compras"
                       placeholder="Código asignado (opcional por ahora)">
            </div>

            <button type="submit" class="btn-submit">Ingresar al portal</button>
        </form>

        <p class="register-link">
            ¿Eres proveedor nuevo? <a href="{{ route('proveedores.registro') }}">Regístrate aquí</a>
        </p>
    </div>

    {{-- ENVÍO DE MUESTRAS --}}
    <div class="muestras-card">
        <div class="muestras-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <div class="muestras-info">
            <div class="muestras-titulo">Envío de muestras</div>
            <div class="muestras-desc">Registro y seguimiento de muestras</div>
        </div>
        <span class="muestras-badge">Próximamente</span>
    </div>
</div>

</body>
</html>