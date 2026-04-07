<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal de Proveedores') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    @stack('styles-before')
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; margin: 0; padding: 0; }

        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#EDE7F6;
            --purple-mid:  #9C6DD0;
            --gray-text:   #4A4A6A;
            --gray-soft:   #F7F6FB;
            --border:      #D8CFE8;
            --white:       #FFFFFF;
            --green:       #059669;
            --green-bg:    #D1FAE5;
            --amber:       #D97706;
            --amber-bg:    #FEF3C7;
            --blue:        #1D4ED8;
            --blue-bg:     #DBEAFE;
            --red:         #DC2626;
            --red-bg:      #FEE2E2;
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
            padding: 0 32px;
            height: 57px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 200;
            flex-shrink: 0;
        }
        .nav-logo { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--purple); font-weight: 600; }
        .nav-logo span { display: block; font-family: 'Nunito', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 3px; color: var(--purple-mid); text-transform: uppercase; margin-top: -4px; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .nav-user { font-size: 13px; color: var(--gray-text); font-weight: 500; }
        .nav-user span { color: var(--purple); font-weight: 600; }
        .btn-logout { font-size: 13px; color: var(--gray-text); padding: 6px 14px; border: 0.5px solid var(--border); border-radius: 8px; background: none; cursor: pointer; font-family: inherit; transition: all .15s; }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        /* ── HERO ── */
        .hero-band {
            background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 60%, var(--purple-mid) 100%);
            padding: 24px 32px;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        .hero-band::before { content: ''; position: absolute; width: 420px; height: 420px; border-radius: 50%; background: rgba(255,255,255,0.06); top: -180px; right: -80px; }
        .hero-band h1 { font-family: 'Playfair Display', serif; font-size: 24px; color: #fff; font-weight: 600; position: relative; z-index: 1; }
        .hero-band p { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 4px; position: relative; z-index: 1; }

        /* ── WRAPPER ── */
        .wrapper { display: flex; flex: 1; }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 220px;
            min-width: 220px;
            background: var(--white);
            border-right: 1px solid var(--border);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: width .25s, min-width .25s;
            overflow: hidden;
        }
        .sidebar.collapsed { width: 56px; min-width: 56px; }
        .sb-toggle { height: 42px; min-height: 42px; display: flex; align-items: center; justify-content: flex-end; padding: 0 14px; border-bottom: 1px solid var(--border); cursor: pointer; flex-shrink: 0; }
        .sb-toggle:hover { background: var(--purple-light); }
        .sb-toggle svg { transition: transform .25s; flex-shrink: 0; }
        .sidebar.collapsed .sb-toggle { justify-content: center; padding: 0; }
        .sidebar.collapsed .sb-toggle svg { transform: rotate(180deg); }
        .sb-nav { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 8px 0; display: flex; flex-direction: column; }
        .sb-section { font-size: 10px; font-weight: 700; color: #bbb; text-transform: uppercase; letter-spacing: 1px; padding: 10px 16px 4px; white-space: nowrap; flex-shrink: 0; }
        .sidebar.collapsed .sb-section { display: none; }
        .sb-hr { height: 1px; background: var(--border); margin: 4px 12px; flex-shrink: 0; }
        .sidebar.collapsed .sb-hr { margin: 4px 8px; }
        .sb-link { display: flex; align-items: center; gap: 10px; padding: 9px 14px; color: var(--gray-text); text-decoration: none; font-size: 13px; font-weight: 500; border-left: 3px solid transparent; white-space: nowrap; flex-shrink: 0; transition: background .15s, color .15s; }
        .sb-link:hover { background: var(--purple-light); color: var(--purple); border-left-color: var(--purple-mid); }
        .sb-link.active { background: var(--purple-light); color: var(--purple); border-left-color: var(--purple); font-weight: 600; }
        .sb-icon { width: 30px; height: 30px; border-radius: 8px; background: var(--gray-soft); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background .15s; }
        .sb-link:hover .sb-icon, .sb-link.active .sb-icon { background: var(--purple); }
        .sb-link:hover .sb-icon svg, .sb-link.active .sb-icon svg { stroke: white !important; }
        .sb-text { flex-shrink: 0; }
        .sidebar.collapsed .sb-link { justify-content: center; padding: 9px; border-left: none; }
        .sidebar.collapsed .sb-text { display: none; }

        /* ── MAIN ── */
        .main-content { flex: 1; min-width: 0; overflow-y: auto; padding: 28px 32px 64px; }

        /* ── FOOTER ── */
        footer { background: var(--white); border-top: 1px solid var(--border); padding: 18px 32px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
        footer p { font-size: 12px; color: #AAA; }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--purple); }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { padding: 20px 16px 48px; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- NAVBAR --}}
<nav>
    <div class="nav-logo">
        Industrias Salcom
        <span>Portal de Proveedores</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">Hola, <span>{{ session('proveedor_nombre', 'Proveedor') }}</span></span>
        <form method="POST" action="{{ route('proveedores.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

{{-- HERO — cada vista define su propio hero --}}
@yield('hero')

{{-- WRAPPER --}}
<div class="wrapper">

    {{-- SIDEBAR --}}
    <div class="sidebar" id="appSidebar">
        <div class="sb-toggle" onclick="sbToggle()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </div>
        <nav class="sb-nav">
            <div class="sb-section">Principal</div>
            <a href="{{ route('proveedores.portal') }}" class="sb-link {{ request()->routeIs('proveedores.portal') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <span class="sb-text">Inicio</span>
            </a>
            <a href="{{ route('proveedores.dashboard') }}" class="sb-link {{ request()->routeIs('proveedores.dashboard') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></div>
                <span class="sb-text">Dashboard</span>
            </a>
            <div class="sb-hr"></div>
            <div class="sb-section">Operaciones</div>
            <a href="{{ route('proveedores.oc') }}" class="sb-link {{ request()->routeIs('proveedores.oc') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                <span class="sb-text">Consultar OC</span>
            </a>

            <a href="{{ route('proveedores.alta-producto') }}" class="sb-link {{ request()->routeIs('proveedores.alta-producto') ? 'active' : '' }}">
            <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div>
            <span class="sb-text">Alta de producto</span>
            </a>

            <a href="{{ route('proveedores.dashboard') }}" class="sb-link {{ request()->routeIs('proveedores.dashboard') && request()->has('seccion') && request()->seccion == 'facturas' ? 'active' : '' }}">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                <span class="sb-text">Facturas</span>
            </a>
            <a href="{{ route('proveedores.dashboard') }}" class="sb-link">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <span class="sb-text">Pagos</span>
            </a>
            <div class="sb-hr"></div>
            <div class="sb-section">Mi empresa</div>
            <a href="{{ route('proveedores.onboarding') }}" class="sb-link {{ request()->routeIs('proveedores.onboarding') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <span class="sb-text">Onboarding</span>
            </a>
            <a href="{{ route('proveedores.business') }}" class="sb-link {{ request()->routeIs('proveedores.business') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
                <span class="sb-text">Business</span>
            </a>
        </nav>
    </div>

    {{-- CONTENIDO DE CADA VISTA --}}
    <div class="main-content @yield('main-class')">
        @yield('content')
    </div>

</div>

<footer>
    <div class="footer-logo">Industrias Salcom</div>
    <p>© {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

<script>
function sbToggle() {
    document.getElementById('appSidebar').classList.toggle('collapsed');
}
</script>
@stack('scripts')
</body>
</html>