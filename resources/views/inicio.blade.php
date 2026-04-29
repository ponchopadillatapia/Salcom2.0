<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
    <style>
        /* Ajustes para evitar desbordes en pantallas angostas/zoom alto */
        .ios-login-container { padding-inline: 16px; }
        .ios-login-card { width: 100%; }
        .portal-btn {
            display: flex;
            align-items: center;
            gap: 16px;
            width: 100%;
            padding: 20px 22px;
            box-sizing: border-box;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: var(--radius);
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            min-width: 0;
        }
        .portal-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.18);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .portal-btn:active { transform: translateY(0) scale(0.98); }
        .portal-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .portal-icon.proveedores { background: rgba(107,63,160,0.25); }
        .portal-icon.clientes { background: rgba(0,122,255,0.2); }
        .portal-name { font-size: 16px; font-weight: 600; color: #fff; letter-spacing: -0.2px; }
        .portal-desc { font-size: 13px; color: rgba(255,255,255,0.4); margin-top: 3px; }
        .portal-arrow { color: rgba(255,255,255,0.25); transition: var(--transition); flex-shrink: 0; }
        .portal-btn:hover .portal-arrow { color: rgba(255,255,255,0.6); transform: translateX(3px); }
        /* Evita que el texto empuje la flecha fuera del contenedor */
        .portal-meta { flex: 1; min-width: 0; }
        .portal-name, .portal-desc { overflow: hidden; text-overflow: ellipsis; }
        .admin-link {
            font-size: 13px; color: rgba(255,255,255,0.3); text-decoration: none;
            transition: var(--transition); display: inline-flex; align-items: center; gap: 6px;
        }
        .admin-link:hover { color: rgba(255,255,255,0.6); }

        @media (max-width: 420px) {
            .portal-btn { padding: 16px 14px; gap: 12px; }
            .portal-icon { width: 42px; height: 42px; border-radius: 12px; }
            .portal-name { font-size: 15px; }
            .portal-desc { font-size: 12px; }
        }
    </style>
</head>
<body class="ios-login-bg">
<div class="orb-accent"></div>

<div class="ios-login-container">
    <div class="ios-brand">
        <div class="ios-brand-logo">
            @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        </div>
        <p>PLATAFORMA DE GESTIÓN EMPRESARIAL</p>
    </div>

    <div class="ios-login-card">
        <div class="card-title" style="text-align:center;">Selecciona tu portal</div>
        <div class="card-sub" style="text-align:center;">Elige el acceso correspondiente a tu perfil</div>

        <div style="display:flex;flex-direction:column;gap:14px;">
            <a href="/login-proveedor" class="portal-btn">
                <div class="portal-icon proveedores">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c4b5fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <div class="portal-meta">
                    <div class="portal-name">Portal Proveedores</div>
                    <div class="portal-desc">Órdenes de compra, facturas y documentos</div>
                </div>
                <svg class="portal-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </a>

            <a href="/login-cliente" class="portal-btn">
                <div class="portal-icon clientes">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#93c5fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="portal-meta">
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

    <div class="ios-footer-text">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>

</body>
</html>
