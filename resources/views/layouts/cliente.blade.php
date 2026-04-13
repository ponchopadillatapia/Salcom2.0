<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal de Clientes') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @stack('styles-before')
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%;margin:0;padding:0}
        :root{--purple:#6B3FA0;--purple-dark:#4A2070;--purple-light:#F3EEFA;--purple-mid:#9C6DD0;--gray-text:#1a1a2e;--gray-muted:#6b7280;--gray-soft:#f9fafb;--border:#e5e7eb;--white:#fff;--green:#059669;--green-bg:#ecfdf5;--amber:#d97706;--amber-bg:#fffbeb;--red:#DC2626;--red-bg:#fef2f2}
        body{font-family:'Inter',-apple-system,sans-serif;background:var(--gray-soft);min-height:100vh;display:flex;flex-direction:column;color:var(--gray-text);font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased}
        nav.top-nav{background:var(--white);padding:0 28px;height:52px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:200}
        .nav-logo{font-size:16px;color:var(--purple);font-weight:700}.nav-logo span{display:block;font-size:9px;font-weight:600;letter-spacing:2.5px;color:var(--gray-muted);text-transform:uppercase;margin-top:1px}
        .nav-right{display:flex;align-items:center;gap:16px}.nav-user{font-size:13px;color:var(--gray-text);font-weight:500}
        .btn-logout{font-size:12px;color:var(--gray-muted);padding:5px 14px;border:1px solid var(--border);border-radius:6px;background:var(--white);cursor:pointer;font-family:inherit;font-weight:500;transition:all .15s}
        .btn-logout:hover{background:var(--gray-soft);color:var(--gray-text)}
        .hero-band{background:var(--white);padding:20px 28px;border-bottom:1px solid var(--border)}
        .hero-band h1{font-size:20px;color:var(--gray-text);font-weight:700}.hero-band p{color:var(--gray-muted);font-size:13px;margin-top:2px}
        .wrapper{display:flex;flex:1}
        .sidebar{width:220px;min-width:220px;background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column;transition:width .2s,min-width .2s;overflow:hidden}
        .sidebar.collapsed{width:56px;min-width:56px}
        .sb-toggle{height:40px;display:flex;align-items:center;justify-content:flex-end;padding:0 14px;border-bottom:1px solid var(--border);cursor:pointer}.sb-toggle:hover{background:var(--gray-soft)}
        .sb-toggle svg{transition:transform .2s;color:var(--gray-muted)}.sidebar.collapsed .sb-toggle{justify-content:center;padding:0}.sidebar.collapsed .sb-toggle svg{transform:rotate(180deg)}
        .sb-nav{flex:1;overflow-y:auto;padding:12px 0;display:flex;flex-direction:column}
        .sb-section{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:1.2px;padding:16px 20px 6px}.sidebar.collapsed .sb-section{display:none}
        .sb-hr{height:1px;background:var(--border);margin:8px 16px}
        .sb-link{display:flex;align-items:center;gap:12px;padding:8px 16px;margin:1px 8px;color:var(--gray-text);text-decoration:none;font-size:13px;font-weight:500;border-radius:8px;transition:all .15s}
        .sb-link:hover{background:var(--purple-light);color:var(--purple)}
        .sb-link.active{background:var(--purple-light);color:var(--purple);font-weight:600}
        .sb-icon{width:32px;height:32px;border-radius:8px;background:var(--gray-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .15s}
        .sb-link:hover .sb-icon,.sb-link.active .sb-icon{background:var(--purple)}.sb-link:hover .sb-icon svg,.sb-link.active .sb-icon svg{stroke:white !important}
        .sb-text{flex-shrink:0}.sidebar.collapsed .sb-link{justify-content:center;padding:8px;margin:1px 4px}.sidebar.collapsed .sb-text{display:none}
        .main-content{flex:1;min-width:0;overflow-y:auto;padding:28px 32px 64px}
        footer{background:var(--white);border-top:1px solid var(--border);padding:14px 28px;display:flex;align-items:center;justify-content:space-between}
        footer p{font-size:11px;color:var(--gray-muted)}.footer-logo{font-size:14px;color:var(--purple);font-weight:600}
        @media(max-width:768px){.sidebar{display:none}.main-content{padding:20px 16px 48px}}
    </style>
    @stack('styles')
</head>
<body>
<nav class="top-nav">
    <div class="nav-logo">Industrias Salcom<span>Portal de Clientes</span></div>
    <div class="nav-right">
        <span class="nav-user">{{ session('cliente_nombre', 'Cliente') }}</span>
        <form method="POST" action="{{ route('clientes.logout') }}" style="display:inline">@csrf<button type="submit" class="btn-logout">Cerrar sesión</button></form>
    </div>
</nav>
@yield('hero')
<div class="wrapper">
    <div class="sidebar" id="appSidebar">
        <div class="sb-toggle" onclick="document.getElementById('appSidebar').classList.toggle('collapsed')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></div>
        <nav class="sb-nav">
            <div class="sb-section">Principal</div>
            <a href="{{ route('clientes.portal') }}" class="sb-link {{ request()->routeIs('clientes.portal') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div><span class="sb-text">Inicio</span></a>
            <a href="{{ route('clientes.dashboard') }}" class="sb-link {{ request()->routeIs('clientes.dashboard') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></div><span class="sb-text">Dashboard</span></a>
            <div class="sb-hr"></div>
            <div class="sb-section">Operaciones</div>
            <a href="{{ route('clientes.catalogo') }}" class="sb-link {{ request()->routeIs('clientes.catalogo') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div><span class="sb-text">Catálogo</span></a>
            <a href="{{ route('clientes.pedidos') }}" class="sb-link {{ request()->routeIs('clientes.pedidos') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><span class="sb-text">Pedidos</span></a>
        </nav>
    </div>
    <div class="main-content @yield('main-class')">@yield('content')</div>
</div>
<footer><div class="footer-logo">Industrias Salcom</div><p>&copy; {{ date('Y') }} Industrias Salcom.</p></footer>
@stack('scripts')
</body>
</html>
