<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; margin: 0; padding: 0; }

        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#F3EEFA;
            --purple-mid:  #9C6DD0;
            --purple-subtle:#F8F5FC;
            --gray-text:   #1a1a2e;
            --gray-muted:  #6b7280;
            --gray-soft:   #f9fafb;
            --border:      #e5e7eb;
            --border-light:#f3f4f6;
            --white:       #FFFFFF;
            --green:       #059669;
            --green-bg:    #ECFDF5;
            --amber:       #D97706;
            --amber-bg:    #FFFBEB;
            --blue:        #2563EB;
            --blue-bg:     #EFF6FF;
            --red:         #DC2626;
            --red-bg:      #FEF2F2;
            --shadow-sm:   0 1px 2px rgba(74,32,112,0.04);
            --shadow-md:   0 2px 8px rgba(74,32,112,0.06), 0 1px 2px rgba(74,32,112,0.04);
            --shadow-lg:   0 4px 16px rgba(74,32,112,0.08), 0 2px 4px rgba(74,32,112,0.04);
            --radius:      10px;
            --radius-lg:   14px;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--gray-soft);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--gray-text);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ── NAVBAR ── */
        nav.top-nav {
            background: var(--white);
            padding: 0 28px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 200;
            flex-shrink: 0;
            box-shadow: var(--shadow-sm);
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
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--white);
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            transition: all .15s ease;
        }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        /* ── HERO ── */
        .hero-band {
            background: var(--white);
            padding: 20px 28px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .hero-band h1 { font-size: 20px; color: var(--gray-text); font-weight: 700; letter-spacing: -0.3px; }
        .hero-band p { color: var(--gray-muted); font-size: 13px; margin-top: 2px; }

        /* ── WRAPPER ── */
        .wrapper { display: flex; flex: 1; }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 230px;
            min-width: 230px;
            background: var(--white);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            transition: width .2s, min-width .2s;
            overflow: hidden;
        }
        .sidebar.collapsed { width: 56px; min-width: 56px; }
        .sb-toggle {
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 14px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
        }
        .sb-toggle:hover { background: var(--gray-soft); }
        .sb-toggle svg { transition: transform .2s; color: var(--gray-muted); }
        .sidebar.collapsed .sb-toggle { justify-content: center; padding: 0; }
        .sidebar.collapsed .sb-toggle svg { transform: rotate(180deg); }

        .sb-nav { flex: 1; overflow-y: auto; padding: 12px 0; display: flex; flex-direction: column; }
        .sb-section {
            font-size: 10px; font-weight: 700; color: var(--gray-muted);
            text-transform: uppercase; letter-spacing: 1.2px; padding: 16px 20px 6px;
        }
        .sidebar.collapsed .sb-section { display: none; }
        .sb-hr { height: 1px; background: var(--border); margin: 8px 16px; }
        .sb-link {
            display: flex; align-items: center; gap: 12px;
            padding: 8px 16px; margin: 1px 8px;
            color: var(--gray-text); text-decoration: none;
            font-size: 13px; font-weight: 500;
            border-radius: 8px; transition: all .15s;
        }
        .sb-link:hover { background: var(--purple-light); color: var(--purple); }
        .sb-link.active { background: var(--purple-light); color: var(--purple); font-weight: 600; }
        .sb-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--gray-soft); display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
            transition: all .15s;
        }
        .sb-link:hover .sb-icon, .sb-link.active .sb-icon { background: var(--purple); }
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
            border-top: 1px solid var(--border);
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        footer p { font-size: 11px; color: var(--gray-muted); }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 15px; color: var(--purple); font-weight: 600; }

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
        <span class="nav-user">{{ session('admin_nombre', 'Administrador') }}</span>
        <form method="POST" action="/logout-admin" style="margin:0;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

@yield('hero')

<div class="wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="sb-toggle" onclick="document.getElementById('sidebar').classList.toggle('collapsed')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </div>
        <nav class="sb-nav">
            <div class="sb-section">Gestión</div>

            <a href="{{ route('admin.cliente.alta') }}" class="sb-link {{ request()->is('admin/cliente/alta*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                </div>
                <span class="sb-text">Alta de Cliente</span>
            </a>

            <a href="{{ route('admin.clientes') }}" class="sb-link {{ request()->is('admin/clientes*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <span class="sb-text">Lista de Clientes</span>
            </a>

            <a href="{{ route('admin.proveedores') }}" class="sb-link {{ request()->is('admin/proveedores*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <span class="sb-text">Proveedores / Score</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Consultas</div>

            <a href="{{ route('admin.encuestas') }}" class="sb-link {{ request()->is('admin/encuestas*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <span class="sb-text">Encuestas</span>
            </a>

            <a href="{{ route('admin.pedidos') }}" class="sb-link {{ request()->is('admin/pedidos*') ? 'active' : '' }}">
                <div class="sb-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <span class="sb-text">Pedidos</span>
            </a>
        </nav>
    </aside>

    <div class="main-content">
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
