<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Supplier Portal') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
    @stack('styles-before')
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--gray-soft); min-height: 100vh; display: flex; flex-direction: column; color: var(--gray-text); font-size: 14px; line-height: 1.5; -webkit-font-smoothing: antialiased; }

        /* Navbar */
        nav.top-nav { background: rgba(255,255,255,0.72); -webkit-backdrop-filter: saturate(180%) blur(20px); backdrop-filter: saturate(180%) blur(20px); padding: 0 28px; height: 56px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-light); position: sticky; top: 0; z-index: 400; flex-shrink: 0; }
        .nav-left { display: flex; align-items: center; gap: 12px; }
        .nav-title { font-weight: 600; color: var(--purple); font-size: 15px; }
        .nav-right { display: flex; align-items: center; gap: 16px; }
        .btn-logout { background: none; border: 1px solid var(--border); border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; color: var(--gray-text); cursor: pointer; font-family: inherit; }
        .btn-logout:hover { background: var(--gray-soft); }
        .nav-menu-btn { display: none; background: none; border: none; cursor: pointer; padding: 4px; }
        @media (max-width: 1024px) { .nav-menu-btn { display: block; } }

        /* Wrapper + sidebar */
        .wrapper { display: flex; flex: 1; min-height: 0; }
        .sidebar { width: 240px; background: var(--white); border-right: 1px solid var(--border-light); padding: 18px 12px; flex-shrink: 0; overflow-y: auto; }
        .sb-client { display: flex; align-items: center; gap: 10px; padding: 8px 10px 16px; }
        .sb-client-icon { width: 36px; height: 36px; border-radius: 10px; background: var(--purple); display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
        .sb-client-icon img { width: 100%; height: 100%; object-fit: cover; }
        .sb-client-name { font-size: 13px; font-weight: 600; color: var(--gray-text); }
        .sb-section { font-size: 10px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .6px; padding: 14px 10px 6px; }
        .sb-link { display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 10px; text-decoration: none; color: var(--gray-text); font-size: 13.5px; font-weight: 500; transition: var(--transition); }
        .sb-link:hover { background: var(--purple-light); }
        .sb-link.active { background: var(--purple); color: #fff; }
        .sb-link.active svg { stroke: #fff; }
        .sb-icon { width: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sb-hr { height: 1px; background: var(--border-light); margin: 10px 6px; }
        .sb-overlay { display: none; }
        @media (max-width: 1024px) {
            .sidebar { position: fixed; top: 56px; left: 0; bottom: 0; z-index: 350; transform: translateX(-100%); transition: transform .25s; }
            .sidebar.open { transform: translateX(0); }
            .sb-overlay.show { display: block; position: fixed; inset: 56px 0 0 0; background: rgba(0,0,0,.35); z-index: 340; }
        }

        .main-content { flex: 1; padding: 24px 28px; overflow-y: auto; min-width: 0; }
        footer { background: var(--white); border-top: 1px solid var(--border-light); padding: 16px 28px; text-align: center; font-size: 12px; color: var(--gray-muted); flex-shrink: 0; }
    </style>
    @stack('styles')
</head>
<body>
<nav class="top-nav">
    <div class="nav-left">
        <button type="button" class="nav-menu-btn" id="navMenuBtn" aria-label="Open menu" aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--gray-text)" stroke-width="2" stroke-linecap="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        @include('partials.logo-salcom', ['size' => 'sm', 'color' => 'dark'])
        <span class="nav-title">Supplier Portal</span>
    </div>
    <div class="nav-right">
        <form method="POST" action="{{ route('proveedores.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Sign out</button>
        </form>
    </div>
</nav>

<div class="wrapper">
    <div class="sb-overlay" id="sbOverlay"></div>

    <div class="sidebar" id="appSidebar">
        <div class="sb-client">
            <div class="sb-client-icon">
                @if(!empty($proveedorPortal?->foto))
                    <img src="{{ asset('storage/'.$proveedorPortal->foto) }}" alt="Profile photo">
                @else
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                @endif
            </div>
            <div class="sb-client-name">{{ $proveedorPortal?->nombre ?? session('proveedor_nombre', 'Supplier') }}</div>
        </div>

        <nav>
            <div class="sb-section">Menu</div>
            <a href="{{ route('proveedores.foreign-portal') }}" class="sb-link {{ request()->routeIs('proveedores.foreign-portal') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <span>Home</span>
            </a>
            <a href="{{ route('proveedores.foreign-onboarding') }}" class="sb-link {{ request()->routeIs('proveedores.foreign-onboarding') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>
                <span>My Onboarding</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Operations</div>
            <a href="{{ route('proveedores.foreign-invoices') }}" class="sb-link {{ request()->routeIs('proveedores.foreign-invoices') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                <span>Invoices</span>
            </a>
            <a href="{{ route('proveedores.foreign-po') }}" class="sb-link {{ request()->routeIs('proveedores.foreign-po') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                <span>Purchase Orders</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Account</div>
            <a href="{{ route('proveedores.foreign-profile') }}" class="sb-link {{ request()->routeIs('proveedores.foreign-profile') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <span>My Profile</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        @if(session('mensaje'))
        <div style="background:#ecfdf5;border:1px solid #059669;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#059669;font-size:13px;font-weight:600;">{{ session('mensaje') }}</div>
        @endif
        @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#991b1b;font-size:13px;font-weight:600;">{{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</div>

<footer>
    <a href="/aviso-privacidad" style="color:var(--gray-muted);text-decoration:none;" target="_blank">Privacy Notice</a> · &copy; {{ date('Y') }} Industrias Salcom. All rights reserved.
</footer>

<script>
(function () {
    var sidebar = document.getElementById('appSidebar');
    var overlay = document.getElementById('sbOverlay');
    var btn = document.getElementById('navMenuBtn');
    if (!sidebar || !btn) return;
    function setOpen(open) {
        sidebar.classList.toggle('open', open);
        if (overlay) overlay.classList.toggle('show', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    btn.addEventListener('click', function (e) { e.stopPropagation(); setOpen(!sidebar.classList.contains('open')); });
    if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
})();
</script>
@stack('scripts')
</body>
</html>
