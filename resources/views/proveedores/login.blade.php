<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Proveedor — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
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
        }

        body {
            font-family: 'Nunito', sans-serif;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        .left {
            width: 52%;
            background: linear-gradient(150deg, var(--purple-dark) 0%, var(--purple) 55%, var(--purple-mid) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 56px;
            position: relative;
            overflow: hidden;
        }

        .blob { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.06); }
        .blob-1 { width: 380px; height: 380px; top: -140px; right: -100px; }
        .blob-2 { width: 240px; height: 240px; bottom: -80px; left: -60px; }
        .blob-3 { width: 120px; height: 120px; top: 42%; left: 12%; background: rgba(255,255,255,0.04); }

        .left-content { position: relative; z-index: 1; text-align: center; }

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
            color: rgba(255,255,255,0.6);
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
        }
        .left-tagline strong {
            color: var(--white);
            font-weight: 600;
            display: block;
            font-size: 20px;
            margin-bottom: 4px;
        }

        .left-badges {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 44px;
            flex-wrap: wrap;
        }
        .badge {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 12px;
            color: rgba(255,255,255,0.85);
            font-weight: 500;
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
            background: #F7F6FB;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            position: relative;
        }

        .deco-blob {
            position: absolute;
            bottom: -40px;
            right: -40px;
            opacity: 0.07;
            pointer-events: none;
        }

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
            font-family: 'Nunito', sans-serif;
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

        .forgot {
            text-align: right;
            margin-top: -8px;
            margin-bottom: 20px;
        }
        .forgot a {
            font-size: 12px;
            color: var(--purple-mid);
            text-decoration: none;
        }
        .forgot a:hover { text-decoration: underline; }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--purple);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-family: 'Nunito', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: 0 4px 16px rgba(107,63,160,0.25);
            margin-top: 4px;
        }
        .btn-submit:hover {
            background: var(--purple-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(107,63,160,0.35);
        }
        .btn-submit:active { transform: translateY(0); }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #999;
        }
        .register-link a {
            color: var(--purple);
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover { text-decoration: underline; }

        .alert-error {
            background: #FEE2E2;
            border-left: 3px solid #C0392B;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #991B1B;
            margin-bottom: 18px;
        }
        .alert-success {
            background: #D1FAE5;
            border-left: 3px solid #059669;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #065F46;
            margin-bottom: 18px;
        }

        @media (max-width: 700px) {
            body { flex-direction: column; height: auto; overflow: auto; }
            .left { width: 100%; padding: 40px 24px; min-height: 220px; }
            .brand-logo { font-size: 28px; }
            .left-badges, .left-tagline { display: none; }
            .right { padding: 32px 16px 48px; }
            .card { padding: 28px 22px 36px; }
        }
    </style>
</head>
<body>

<div class="left">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

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

    <div class="left-footer">© {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>

<div class="right">

    <svg class="deco-blob" width="320" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <path fill="#6B3FA0" d="M39.5,-51.5C50.8,-42.6,59.2,-29.5,63.1,-14.8C67,0,66.3,16.4,59.7,29.7C53.1,43,40.5,53.2,26.4,59.3C12.3,65.4,-3.3,67.4,-17.8,63.1C-32.3,58.8,-45.7,48.2,-54.3,34.5C-62.9,20.8,-66.7,4,-63.9,-11.5C-61.1,-27,-51.7,-41.2,-39.4,-50.2C-27.1,-59.2,-11.9,-63,2,-65.4C15.9,-67.8,28.2,-60.4,39.5,-51.5Z" transform="translate(100 100)" />
    </svg>

    <div class="card">

        <div class="card-header">
            <div class="icon-wrap">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                     stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
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
</div>

</body>
</html>