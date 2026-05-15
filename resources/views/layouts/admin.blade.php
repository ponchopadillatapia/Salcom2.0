<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif;
            background: var(--gray-soft);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--gray-text);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ── NAVBAR (frosted glass) ── */
        nav.top-nav {
            background: rgba(255,255,255,0.82);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            backdrop-filter: saturate(180%) blur(20px);
            padding: 0 28px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-light);
            position: sticky;
            top: 0;
            z-index: 200;
            flex-shrink: 0;
        }
        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            color: var(--purple);
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.1;
        }
        .nav-logo span {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 2.5px;
            color: var(--purple-mid);
            text-transform: uppercase;
            margin-top: 1px;
        }
        .nav-right { display: flex; align-items: center; gap: 16px; }
        .nav-user { font-size: 13px; color: var(--gray-text); font-weight: 600; letter-spacing: -0.2px; }
        .btn-logout {
            font-size: 12px;
            color: var(--gray-muted);
            padding: 6px 16px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-pill);
            background: var(--gray-soft);
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            transition: var(--transition);
        }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); transform: scale(1.02); }
        .btn-logout:active { transform: scale(0.97); }

        /* ── HERO ── */
        .hero-band {
            background: linear-gradient(135deg, var(--white) 0%, var(--purple-subtle) 100%);
            padding: 28px 32px;
            border-bottom: 1px solid var(--border-light);
            flex-shrink: 0;
        }
        .hero-band h1 { font-size: 22px; color: var(--gray-text); font-weight: 700; letter-spacing: -0.4px; }
        .hero-band p { color: var(--gray-muted); font-size: 14px; margin-top: 4px; font-weight: 500; }

        /* ── WRAPPER ── */
        .wrapper { display: flex; flex: 1; }

        /* ── SIDEBAR (frosted glass) ── */
        .sidebar {
            width: 230px;
            min-width: 230px;
            background: rgba(255,255,255,0.88);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            transition: width .3s cubic-bezier(.4,0,.2,1), min-width .3s cubic-bezier(.4,0,.2,1), box-shadow .3s;
            overflow: hidden;
        }
        .sidebar.collapsed { width: 56px; min-width: 56px; }
        .sidebar.collapsed:hover {
            width: 230px; min-width: 230px;
            box-shadow: 8px 0 24px rgba(0,0,0,0.06);
        }
        .sidebar.collapsed:hover .sb-text { display: inline; }
        .sidebar.collapsed:hover .sb-link { justify-content: flex-start; padding: 8px 16px; margin: 1px 8px; }

        .sb-nav { flex: 1; overflow-y: auto; padding: 12px 0; display: flex; flex-direction: column; }
        .sb-link {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 16px; margin: 1px 8px;
            color: var(--gray-text); text-decoration: none;
            font-size: 13px; font-weight: 500;
            border-radius: 10px; transition: var(--transition);
        }
        .sb-link:hover { background: var(--purple-subtle); color: var(--purple); transform: translateX(2px); }
        .sb-link.active { background: var(--purple-light); color: var(--purple); font-weight: 600; }
        .sb-icon {
            width: 32px; height: 32px; border-radius: 10px;
            background: var(--gray-soft); display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
            transition: var(--transition);
        }
        .sb-link:hover .sb-icon, .sb-link.active .sb-icon { background: var(--purple); box-shadow: 0 2px 8px rgba(107,63,160,0.25); }
        .sb-link:hover .sb-icon svg, .sb-link.active .sb-icon svg { stroke: white !important; }
        .sb-text { flex-shrink: 0; }
        .sidebar.collapsed .sb-link { justify-content: center; padding: 8px; margin: 1px 4px; }
        .sidebar.collapsed .sb-text { display: none; }

        /* ── MAIN ── */
        .main-content {
            flex: 1;
            min-width: 0;
            overflow-y: auto;
            padding: 28px 32px 64px;
        }

        /* ── FOOTER ── */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border-light);
            padding: 18px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        footer p { font-size: 11px; color: var(--gray-muted); font-weight: 500; }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 15px; color: var(--purple); font-weight: 600; letter-spacing: -0.3px; }

        /* ── Volver al panel (dentro del contenido principal) ── */
        .admin-back-nav {
            margin: 0 0 20px;
        }
        .admin-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-muted);
            text-decoration: none;
            padding: 0;
            border: none;
            background: none;
            transition: color 0.15s ease;
        }
        .admin-back-link:hover {
            color: var(--purple);
        }
        .admin-back-link svg {
            flex-shrink: 0;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 20px 16px 48px; }
            nav.top-nav { padding: 0 16px; }
        }
    </style>
    @stack('styles')
</head>
<body>

<nav class="top-nav">
    <div class="nav-logo" style="display:flex;align-items:center;gap:14px;">
        @include('partials.logo-salcom', ['size' => 'sm', 'color' => 'dark'])
        <span>Panel Administrativo</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">Administrador</span>
        <form method="POST" action="/logout-admin" style="margin:0;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

@yield('hero')

<div class="wrapper">
    <aside class="sidebar collapsed" id="sidebar">
        <nav class="sb-nav">
            <a href="{{ route('admin.dashboard') }}" class="sb-link {{ request()->is('admin/dashboard*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
                </div>
                <span class="sb-text">Dashboard</span>
            </a>

            <a href="{{ route('admin.proveedores') }}" class="sb-link {{ request()->is('admin/proveedores*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <span class="sb-text">Proveedores / Score</span>
            </a>

            <a href="{{ route('admin.pedidos') }}" class="sb-link {{ request()->is('admin/pedidos*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <span class="sb-text">Pedidos</span>
            </a>

            <a href="{{ route('admin.productos') }}" class="sb-link {{ request()->is('admin/productos*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </div>
                <span class="sb-text">Productos</span>
            </a>

            <a href="{{ route('admin.facturas') }}" class="sb-link {{ request()->is('admin/facturas*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <span class="sb-text">Facturas</span>
            </a>

            <a href="{{ route('admin.reporte-proveedores') }}" class="sb-link {{ request()->is('admin/reporte-proveedores*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                </div>
                <span class="sb-text">Reportes</span>
            </a>

            <a href="{{ route('admin.documentos') }}" class="sb-link {{ request()->is('admin/documentos*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <span class="sb-text">Documentos</span>
            </a>

            <a href="{{ route('admin.gestion-compras') }}" class="sb-link {{ request()->is('admin/gestion-compras*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H2v7l6.29 6.29c.94.94 2.48.94 3.42 0l3.58-3.58c.94-.94.94-2.48 0-3.42L9 5z"/><path d="M6 9.01V9"/></svg>
                </div>
                <span class="sb-text">Gestión Compras</span>
            </a>

            @if(in_array(session('admin_rol'), ['gerente', 'materia_prima']))
            <a href="{{ route('admin.materia-prima') }}" class="sb-link {{ request()->is('admin/materia-prima*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3h6v7l4 8H5l4-8V3z"/><path d="M9 3h6"/></svg>
                </div>
                <span class="sb-text">Materia Prima</span>
            </a>
            @endif

            @if(in_array(session('admin_rol'), ['gerente', 'material_empaque']))
            <a href="{{ route('admin.material-empaque') }}" class="sb-link {{ request()->is('admin/material-empaque*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <span class="sb-text">Material Empaque</span>
            </a>
            @endif

        </nav>
    </aside>

    <div class="main-content">
        @unless(request()->is('admin/dashboard'))
        <nav class="admin-back-nav" aria-label="Navegación secundaria">
            <a href="{{ route('admin.dashboard') }}" class="admin-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                Regresar al panel
            </a>
        </nav>
        @endunless
        @yield('content')
    </div>
</div>

<footer>
    <div class="footer-logo">Industrias Salcom</div>
    <p>&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

@stack('scripts')
</body>
</html>
