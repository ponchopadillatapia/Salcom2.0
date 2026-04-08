<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal de Proveedores') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    @stack('styles-before')
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; margin: 0; padding: 0; }

        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#F3EEFA;
            --purple-mid:  #9C6DD0;
            --purple-subtle:#F8F5FC;
            --gray-text:   #3D3D5C;
            --gray-muted:  #8B8BA3;
            --gray-soft:   #F5F4F8;
            --border:      #E5E0EE;
            --border-light:#EEEAF4;
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
            -moz-osx-font-smoothing: grayscale;
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
        .nav-user {
            font-size: 13px;
            color: var(--gray-muted);
            font-weight: 500;
        }
        .nav-user strong { color: var(--gray-text); font-weight: 600; }
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
        .btn-logout:hover {
            background: var(--purple-light);
            color: var(--purple);
            border-color: var(--purple-mid);
        }

        /* ── HERO ── */
        .hero-band {
            background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 50%, var(--purple-mid) 100%);
            padding: 22px 28px;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        .hero-band::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            top: -220px; right: -100px;
        }
        .hero-band h1 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: #fff;
            font-weight: 600;
            position: relative; z-index: 1;
            letter-spacing: -0.3px;
        }
        .hero-band p {
            color: rgba(255,255,255,0.65);
            font-size: 13px;
            margin-top: 3px;
            position: relative; z-index: 1;
            font-weight: 400;
        }

        /* ── WRAPPER ── */
        .wrapper { display: flex; flex: 1; }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 240px;
            min-width: 240px;
            background: var(--white);
            border-right: 1px solid var(--border);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: width .2s ease, min-width .2s ease;
            overflow: hidden;
        }
        .sidebar.collapsed { width: 60px; min-width: 60px; }

        .sb-toggle {
            height: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 16px;
            border-bottom: 1px solid var(--border-light);
            cursor: pointer;
            flex-shrink: 0;
            transition: background .15s;
        }
        .sb-toggle:hover { background: var(--purple-subtle); }
        .sb-toggle svg { transition: transform .2s ease; flex-shrink: 0; color: var(--gray-muted); }
        .sidebar.collapsed .sb-toggle { justify-content: center; padding: 0; }
        .sidebar.collapsed .sb-toggle svg { transform: rotate(180deg); }

        .sb-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 12px 0;
            display: flex;
            flex-direction: column;
        }
        .sb-section {
            font-size: 10px;
            font-weight: 700;
            color: var(--gray-muted);
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 16px 20px 6px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .sidebar.collapsed .sb-section { display: none; }

        .sb-hr {
            height: 1px;
            background: var(--border-light);
            margin: 8px 16px;
            flex-shrink: 0;
        }
        .sidebar.collapsed .sb-hr { margin: 6px 10px; }

        .sb-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            margin: 1px 8px;
            color: var(--gray-text);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 8px;
            white-space: nowrap;
            flex-shrink: 0;
            transition: all .15s ease;
            border-left: none;
        }
        .sb-link:hover {
            background: var(--purple-subtle);
            color: var(--purple);
        }
        .sb-link.active {
            background: var(--purple-light);
            color: var(--purple);
            font-weight: 600;
        }
        .sb-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--gray-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all .15s ease;
        }
        .sb-link:hover .sb-icon {
            background: var(--purple);
            box-shadow: 0 2px 8px rgba(107,63,160,0.25);
        }
        .sb-link:hover .sb-icon svg { stroke: white !important; }
        .sb-link.active .sb-icon {
            background: var(--purple);
            box-shadow: 0 2px 8px rgba(107,63,160,0.25);
        }
        .sb-link.active .sb-icon svg { stroke: white !important; }
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
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 15px;
            color: var(--purple);
            font-weight: 600;
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

{{-- NAVBAR --}}
<nav class="top-nav">
    <div class="nav-logo">
        Industrias Salcom
        <span>Portal de Proveedores</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">Hola, <strong>{{ session('proveedor_nombre', 'Proveedor') }}</strong></span>
        <form method="POST" action="{{ route('proveedores.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

{{-- HERO --}}
@yield('hero')

{{-- WRAPPER --}}
<div class="wrapper">

    {{-- SIDEBAR --}}
    <div class="sidebar" id="appSidebar">
        <div class="sb-toggle" onclick="sbToggle()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </div>
        <nav class="sb-nav">
            <div class="sb-section">Principal</div>
            <a href="{{ route('proveedores.portal') }}" class="sb-link {{ request()->routeIs('proveedores.portal') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <span class="sb-text">Inicio</span>
            </a>
            <a href="{{ route('proveedores.dashboard') }}" class="sb-link {{ request()->routeIs('proveedores.dashboard') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></div>
                <span class="sb-text">Dashboard</span>
            </a>
            <div class="sb-hr"></div>
            <div class="sb-section">Operaciones</div>
            <a href="{{ route('proveedores.oc') }}" class="sb-link {{ request()->routeIs('proveedores.oc') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                <span class="sb-text">Consultar OC</span>
            </a>
            <a href="{{ route('proveedores.alta-producto') }}" class="sb-link {{ request()->routeIs('proveedores.alta-producto') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                <span class="sb-text">Alta de producto</span>
            </a>
            <a href="{{ route('proveedores.dashboard') }}" class="sb-link">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                <span class="sb-text">Facturas</span>
            </a>
            <a href="{{ route('proveedores.dashboard') }}" class="sb-link">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <span class="sb-text">Pagos</span>
            </a>
            <div class="sb-hr"></div>
            <div class="sb-section">Mi empresa</div>
            <a href="{{ route('proveedores.onboarding') }}" class="sb-link {{ request()->routeIs('proveedores.onboarding') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>
                <span class="sb-text">Onboarding</span>
            </a>
            <a href="{{ route('proveedores.business') }}" class="sb-link {{ request()->routeIs('proveedores.business') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
                <span class="sb-text">Business</span>
            </a>
        </nav>
    </div>

    {{-- CONTENIDO --}}
    <div class="main-content @yield('main-class')">
        @yield('content')
    </div>

</div>

<footer>
    <div class="footer-logo">Industrias Salcom</div>
    <p>&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

<script>
function sbToggle() {
    document.getElementById('appSidebar').classList.toggle('collapsed');
}
</script>
@stack('scripts')
</body>
</html>
