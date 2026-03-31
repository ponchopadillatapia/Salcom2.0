<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Proveedor — Salcom Industries</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#EDE7F6;
            --purple-mid:  #9C6DD0;
            --gray-text:   #4A4A6A;
            --gray-soft:   #F7F6FB;
            --border:      #D8CFE8;
            --white:       #FFFFFF;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--gray-soft);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        nav {
            background: var(--white);
            padding: 14px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }
        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--purple);
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .nav-logo span {
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 3px;
            color: var(--purple-mid);
            text-transform: uppercase;
            margin-top: -4px;
        }
        .nav-right { display: flex; align-items: center; gap: 24px; }
        .nav-user { font-size: 13px; color: var(--gray-text); font-weight: 500; }
        .nav-user span { color: var(--purple); font-weight: 600; }
        .btn-logout {
            font-size: 13px;
            color: var(--gray-text);
            text-decoration: none;
            padding: 6px 14px;
            border: 0.5px solid var(--border);
            border-radius: 8px;
            background: none;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s;
        }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        /* ── HERO BAND ── */
        .hero-band {
            background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 60%, var(--purple-mid) 100%);
            padding: 48px 48px 36px;
            position: relative;
            overflow: hidden;
        }
        .hero-band::before {
            content: '';
            position: absolute;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -180px; right: -80px;
        }
        .hero-band h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: var(--white);
            font-weight: 600;
            position: relative; z-index: 1;
        }
        .hero-band p {
            color: rgba(255,255,255,0.75);
            font-size: 14px;
            margin-top: 6px;
            position: relative; z-index: 1;
            font-weight: 300;
        }

        /* ── MAIN ── */
        .main {
            flex: 1;
            padding: 56px 48px 64px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .main-title {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: var(--purple-dark);
            font-weight: 600;
            text-align: center;
            margin-bottom: 8px;
        }

        .main-sub {
            font-size: 14px;
            color: #999;
            text-align: center;
            margin-bottom: 48px;
        }

        /* ── OPCIONES ── */
        .opciones-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            width: 100%;
            max-width: 900px;
        }

        .opcion-card {
            background: var(--white);
            border: 0.5px solid var(--border);
            border-radius: 20px;
            padding: 40px 28px;
            text-align: center;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            transition: all .2s;
            position: relative;
            overflow: hidden;
        }
        .opcion-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--purple);
            transform: scaleX(0);
            transition: transform .2s;
            border-radius: 20px 20px 0 0;
        }
        .opcion-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(107,63,160,0.15);
            border-color: var(--purple-mid);
        }
        .opcion-card:hover::before { transform: scaleX(1); }

        .opcion-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .opcion-icon {
            width: 64px; height: 64px;
            border-radius: 18px;
            background: var(--purple-light);
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        .opcion-card:hover .opcion-icon { background: var(--purple); }
        .opcion-card:hover .opcion-icon svg { stroke: var(--white); }

        .opcion-title {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            color: var(--purple-dark);
            font-weight: 600;
        }

        .opcion-desc { font-size: 13px; color: #999; line-height: 1.6; }

        .opcion-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 999px;
            background: var(--purple-light);
            color: var(--purple);
        }
        .opcion-badge.pronto { background: #FEF3C7; color: #D97706; }

        /* ── FOOTER ── */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 24px 48px;
            display: flex; align-items: center; justify-content: space-between;
        }
        footer p { font-size: 12px; color: #AAA; }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--purple); }

        @media (max-width: 900px) {
            .opciones-grid { grid-template-columns: 1fr; max-width: 400px; }
            .main { padding: 32px 20px 48px; }
            nav { padding: 12px 20px; }
            footer { padding: 18px 20px; }
            .hero-band { padding: 32px 20px 28px; }
        }
    </style>
</head>
<body>

{{-- NAVBAR --}}
<nav>
    <div class="nav-logo">
        Wiese
        <span>Salcom Industries</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">Hola, <span>{{ session('proveedor_nombre', 'Proveedor') }}</span></span>
        <form method="POST" action="{{ route('proveedores.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

{{-- HERO --}}
<div class="hero-band">
    <h1>Bienvenido, {{ session('proveedor_nombre', 'Proveedor') }}</h1>
    <p>Selecciona una opción para continuar — {{ now()->format('d/m/Y') }}</p>
</div>

{{-- MAIN --}}
<div class="main">

    <div class="main-title">¿Qué deseas hacer hoy?</div>
    <div class="main-sub">Selecciona una de las siguientes opciones</div>

    <div class="opciones-grid">

        {{-- Consultar OC --}}
        <a href="{{ route('proveedores.oc') }}" class="opcion-card">
            <div class="opcion-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div class="opcion-title">Consultar OC</div>
            <div class="opcion-desc">Revisa tus órdenes de compra, cantidades, precios y condiciones</div>
            <span class="opcion-badge">Disponible</span>
        </a>

        {{-- Dashboard --}}
        <a href="{{ route('proveedores.dashboard') }}" class="opcion-card">
            <div class="opcion-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
            </div>
            <div class="opcion-title">Dashboard</div>
            <div class="opcion-desc">Consulta tus facturas, pagos y estatus en tiempo real</div>
            <span class="opcion-badge">Disponible</span>
        </a>

        {{-- Envío de muestras --}}
        <a href="#" class="opcion-card disabled">
            <div class="opcion-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                     stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
            </div>
            <div class="opcion-title">Envío de muestras</div>
            <div class="opcion-desc">Registra lotes de muestras y da seguimiento a su validación</div>
            <span class="opcion-badge pronto">Próximamente</span>
        </a>

    </div>

</div>

{{-- FOOTER --}}
<footer>
    <div class="footer-logo">Wiese <span style="font-family:'Nunito';font-size:11px;color:#AAA;font-weight:600;letter-spacing:2px;text-transform:uppercase">Salcom Industries</span></div>
    <p>© {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

</body>
</html>