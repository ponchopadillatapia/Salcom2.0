<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
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

        .container {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
            width: 100%;
            max-width: 520px;
            padding: 40px 24px;
        }

        .brand { text-align: center; }
        .brand svg { display: block; margin: 0 auto 12px; }
        .brand p {
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 3px;
            color: rgba(255,255,255,0.45);
            text-transform: uppercase;
            margin-top: 6px;
        }

        /* Card */
        .portals-card {
            width: 100%;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 16px;
            padding: 36px 32px;
        }
        .portals-title {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
            text-align: center;
        }
        .portals-sub {
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            margin-bottom: 28px;
            text-align: center;
        }

        .portals-grid {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .portal-btn {
            display: flex;
            align-items: center;
            gap: 16px;
            width: 100%;
            padding: 18px 20px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px;
            text-decoration: none;
            transition: all .2s;
            cursor: pointer;
        }
        .portal-btn:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .portal-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .portal-icon.proveedores { background: rgba(107,63,160,0.3); }
        .portal-icon.clientes { background: rgba(37,99,235,0.3); }
        .portal-info { flex: 1; }
        .portal-name {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
        }
        .portal-desc {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            margin-top: 2px;
        }
        .portal-arrow {
            color: rgba(255,255,255,0.3);
            transition: all .2s;
            flex-shrink: 0;
        }
        .portal-btn:hover .portal-arrow {
            color: rgba(255,255,255,0.7);
            transform: translateX(3px);
        }

        /* Admin link */
        .admin-link {
            font-size: 12px;
            color: rgba(255,255,255,0.3);
            text-decoration: none;
            transition: color .2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .admin-link:hover { color: rgba(255,255,255,0.6); }

        .footer-text {
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            text-align: center;
        }

        @media (max-width: 500px) {
            .container { padding: 32px 16px; }
            .portals-card { padding: 28px 20px; }
            .brand h1 { font-size: 28px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="brand">
        @include('partials.logo-salcom', ['height' => 52, 'color' => 'light'])
        <p>Plataforma de gestión empresarial</p>
    </div>

    <div class="portals-card">
        <div class="portals-title">Selecciona tu portal</div>
        <div class="portals-sub">Elige el acceso correspondiente a tu perfil</div>

        <div class="portals-grid">
            <a href="/login-proveedor" class="portal-btn">
                <div class="portal-icon proveedores">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c4b5fd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </div>
                <div class="portal-info">
                    <div class="portal-name">Portal Proveedores</div>
                    <div class="portal-desc">Gestión de órdenes, productos y documentos</div>
                </div>
                <svg class="portal-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a href="/login-cliente" class="portal-btn">
                <div class="portal-icon clientes">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#93c5fd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="portal-info">
                    <div class="portal-name">Portal Clientes</div>
                    <div class="portal-desc">Pedidos, catálogo y estado de cuenta</div>
                </div>
                <svg class="portal-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
        </div>
    </div>

    <a href="/login-admin" class="admin-link">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Acceso administrador
    </a>

    <div class="footer-text">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>

</body>
</html>
