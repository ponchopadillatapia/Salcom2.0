<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal de Clientes') — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
    @stack('styles-before')
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif;
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
        /* Navbar — tipografía y medidas iguales que layouts/proveedor */
        nav.top-nav {
            background: rgba(255,255,255,0.72);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
            backdrop-filter: saturate(180%) blur(20px);
            padding: 0 28px;
            height: 56px;
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
        .nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-shrink: 0;
        }
        .nav-notif-wrap {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 10px;
            transition: background .15s;
        }
        .nav-notif-wrap:hover { background: var(--purple-subtle); }
        .nav-user {
            font-size: 13px;
            color: var(--gray-text);
            font-weight: 600;
            letter-spacing: -0.2px;
            max-width: min(380px, 42vw);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-logout {
            font-size: 12px;
            color: var(--gray-muted);
            padding: 6px 16px;
            border: 1px solid var(--border-light);
            border-radius: 20px;
            background: var(--gray-soft);
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            transition: var(--transition);
        }
        .btn-logout:hover {
            background: var(--purple-light);
            color: var(--purple);
            border-color: var(--purple-mid);
            transform: scale(1.02);
        }
        .btn-logout:active { transform: scale(0.97); }
        /* Dropdown de notificaciones: hover en desktop + click en touch */
        .nav-notif-wrap { position: relative; }
        .nav-notif-wrap .notif-drop {
            display: none;
            position: absolute;
            right: 0;
            top: 44px;
            width: 300px;
            background: #fff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            z-index: 500;
            overflow: hidden;
        }
        .nav-notif-wrap:hover .notif-drop,
        .nav-notif-wrap.open .notif-drop { display: block; }
        .hero-band {
            background: var(--white);
            padding: 24px 32px;
            border-bottom: 1px solid var(--border-light);
            flex-shrink: 0;
        }
        .hero-band h1 {
            font-size: 22px;
            color: var(--gray-text);
            font-weight: 700;
            letter-spacing: -0.4px;
        }
        .hero-band p {
            color: var(--gray-muted);
            font-size: 14px;
            margin-top: 4px;
            font-weight: 400;
        }
        .wrapper { display: flex; flex: 1; }
        .sidebar {
            width: 240px;
            min-width: 240px;
            background: rgba(255,255,255,0.8);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border-light);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: width .3s cubic-bezier(.4,0,.2,1), min-width .3s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        .sidebar.collapsed { width: 60px; min-width: 60px; }
        /* Auto-despliegue del sidebar al pasar el mouse */
        .sidebar.collapsed:hover { width: 240px; min-width: 240px; }
        .sidebar.collapsed:hover .sb-text,
        .sidebar.collapsed:hover .sb-section { display: block; }
        .sidebar.collapsed:hover .sb-link { justify-content: flex-start; padding: 8px 16px; margin: 1px 8px; }
        .sidebar.collapsed:hover .sb-toggle { justify-content: flex-end; padding: 0 16px; }
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
        .sb-toggle svg { transition: transform 0.2s ease; flex-shrink: 0; color: var(--gray-muted); }
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
        .sb-hr { height: 1px; background: var(--border-light); margin: 8px 16px; flex-shrink: 0; }
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
            border-radius: 10px;
            white-space: nowrap;
            flex-shrink: 0;
            transition: var(--transition);
            border-left: none;
        }
        .sb-link:hover { background: var(--purple-subtle); color: var(--purple); transform: translateX(2px); }
        .sb-link.active { background: var(--purple-light); color: var(--purple); font-weight: 600; }
        .sb-icon {
            position: relative;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: var(--gray-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: var(--transition);
        }
        .sb-link:hover .sb-icon,
        .sb-link.active .sb-icon {
            background: var(--purple);
            box-shadow: 0 2px 8px rgba(107,63,160,0.25);
        }
        .sb-link:hover .sb-icon svg,
        .sb-link.active .sb-icon svg { stroke: white !important; }
        .sb-pedidos-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #6B3FA0;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            display: none;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--white);
            box-shadow: 0 2px 8px rgba(107,63,160,0.35);
            line-height: 1;
            z-index: 2;
        }
        .sb-pedidos-badge.pop { animation: sbPedidosBadgePop 0.5s cubic-bezier(0.34, 1.4, 0.64, 1); }
        @keyframes sbPedidosBadgePop {
            0% { transform: scale(1); }
            35% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .nav-pedidos-quick {
            display: none;
            position: relative;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 10px;
            color: var(--gray-text);
            transition: background .15s;
        }
        .nav-pedidos-quick:hover { background: var(--purple-subtle); color: var(--purple); }
        .nav-pedidos-quick svg { display: block; }
        @media (max-width: 768px) {
            .nav-pedidos-quick { display: flex; }
        }
        .sb-text { flex-shrink: 0; }
        .sidebar.collapsed .sb-link { justify-content: center; padding: 8px; margin: 1px 4px; }
        .sidebar.collapsed .sb-text { display: none; }
        .main-content {
            flex: 1;
            min-width: 0;
            overflow-y: auto;
            padding: 28px 32px 64px;
            font-size: 14px;
            line-height: 1.5;
            letter-spacing: -0.01em;
        }
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
            .nav-user { max-width: 140px; }
        }
    </style>
    @stack('styles')
</head>
<body>
<nav class="top-nav">
    <div class="nav-logo" style="display:flex;align-items:center;gap:14px;">
        @include('partials.logo-salcom', ['size' => 'sm', 'color' => 'dark'])
        <span>Portal de Clientes</span>
    </div>
    <div class="nav-right">
        <a href="{{ route('clientes.pedidos') }}" class="nav-pedidos-quick" id="navPedidosQuick" title="Pedidos">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span class="sb-pedidos-badge js-pedidos-nav-badge" style="display:none;top:-2px;right:-2px" aria-hidden="true">0</span>
        </a>
        <div class="nav-notif-wrap" id="notifWrap">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#86868b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span id="notifBadge" style="position:absolute;top:2px;right:2px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;padding:0 5px;border-radius:999px;display:flex;align-items:center;justify-content:center">3</span>
            <div id="notifDrop" class="notif-drop">
                <div style="padding:12px 16px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700;color:var(--gray-text)">Notificaciones</div>
                <div class="notif-item" onclick="markRead(this)" style="padding:10px 16px;border-bottom:1px solid var(--border-light);font-size:12px;cursor:pointer;background:var(--purple-light)"><div style="font-weight:600;color:var(--gray-text)">Pedido PED-2026-004 autorizado</div><div style="color:var(--gray-muted);margin-top:2px">Tu pedido fue aprobado por el área comercial</div></div>
                <div class="notif-item" onclick="markRead(this)" style="padding:10px 16px;border-bottom:1px solid var(--border-light);font-size:12px;cursor:pointer;background:var(--purple-light)"><div style="font-weight:600;color:var(--gray-text)">Factura CFDI-A-001236 por vencer</div><div style="color:var(--gray-muted);margin-top:2px">Vence en 5 días — $5,481.00</div></div>
                <div class="notif-item" onclick="markRead(this)" style="padding:10px 16px;font-size:12px;cursor:pointer;background:var(--purple-light)"><div style="font-weight:600;color:var(--gray-text)">Nuevo producto en catálogo</div><div style="color:var(--gray-muted);margin-top:2px">Refrigerante Industrial disponible</div></div>
            </div>
        </div>
        <span class="nav-user" title="{{ session('cliente_nombre', 'Cliente') }}">{{ session('cliente_nombre', 'Cliente') }}</span>
        <form method="POST" action="{{ route('clientes.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
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
            <a href="{{ route('clientes.ia') }}" class="sb-link {{ request()->routeIs('clientes.ia') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg></div><span class="sb-text">Dashboard IA</span></a>
            <a href="{{ route('clientes.forecast') }}" class="sb-link {{ request()->routeIs('clientes.forecast') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><span class="sb-text">Forecast</span></a>
            <a href="{{ route('clientes.otif') }}" class="sb-link {{ request()->routeIs('clientes.otif') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><span class="sb-text">OTIF</span></a>
            <a href="{{ route('clientes.catalogo') }}" class="sb-link {{ request()->routeIs('clientes.catalogo') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div><span class="sb-text">Catálogo</span></a>
            <a href="{{ route('clientes.pedidos') }}" id="sbLinkPedidos" class="sb-link {{ request()->routeIs('clientes.pedidos') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span class="sb-pedidos-badge js-pedidos-nav-badge" style="display:none" aria-hidden="true">0</span></div><span class="sb-text">Pedidos</span></a>
            <a href="{{ route('clientes.tracking') }}" class="sb-link {{ request()->routeIs('clientes.tracking') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><span class="sb-text">Tracking</span></a>
            <a href="{{ route('clientes.estado-cuenta') }}" class="sb-link {{ request()->routeIs('clientes.estado-cuenta') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><span class="sb-text">Estado de cuenta</span></a>
            <a href="{{ route('clientes.encuesta') }}" class="sb-link {{ request()->routeIs('clientes.encuesta') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div><span class="sb-text">Encuesta</span></a>
            <div class="sb-hr"></div>
            <div class="sb-section">Mi empresa</div>
            <a href="{{ route('clientes.onboarding') }}" class="sb-link {{ request()->routeIs('clientes.onboarding') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div><span class="sb-text">Onboarding</span></a>
            {{-- Fiscal: oculto temporalmente
            <a href="{{ route('clientes.fiscal') }}" class="sb-link {{ request()->routeIs('clientes.fiscal') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></div><span class="sb-text">Fiscal</span></a>
            --}}
            <div class="sb-hr"></div>
            <div class="sb-section">Cuenta</div>
            <a href="{{ route('clientes.perfil') }}" class="sb-link {{ request()->routeIs('clientes.perfil') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><span class="sb-text">Mi Perfil</span></a>
        </nav>
    </div>
    <div class="main-content @yield('main-class')">@yield('content')</div>
</div>
<footer>
    <div class="footer-logo">Industrias Salcom</div>
    <p>&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>
<script>
window.SALCOM_CART_STORAGE_KEY = 'salcom_cliente_carrito_v1';
window.SALCOM_PEDIDOS_NAV_BADGE_KEY = 'salcom_cliente_pedidos_nav_badge';
window.salcomCartItemCount = function () {
    var key = window.SALCOM_CART_STORAGE_KEY || 'salcom_cliente_carrito_v1';
    var n = 0;
    try {
        var raw = localStorage.getItem(key);
        if (!raw) return 0;
        var data = JSON.parse(raw);
        if (!Array.isArray(data)) return 0;
        for (var i = 0; i < data.length; i++) {
            n += parseInt(data[i].cantidad, 10) || 0;
        }
    } catch (e) {}
    return n;
};
window.salcomSyncPedidosNavBadge = function () {
    var n = typeof window.salcomCartItemCount === 'function' ? window.salcomCartItemCount() : 0;
    document.querySelectorAll('.js-pedidos-nav-badge').forEach(function (el) {
        if (n > 0) {
            el.textContent = n > 99 ? '99+' : String(n);
            el.style.display = 'flex';
            el.classList.remove('pop');
            void el.offsetWidth;
            el.classList.add('pop');
        } else {
            el.textContent = '0';
            el.style.display = 'none';
            el.classList.remove('pop');
        }
    });
};
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { window.salcomSyncPedidosNavBadge(); });
} else {
    window.salcomSyncPedidosNavBadge();
}
</script>
@stack('scripts')
<script>
// Click-to-toggle para dispositivos touch (en desktop abre con hover por CSS)
(() => {
  const wrap = document.getElementById('notifWrap');
  if (!wrap) return;
  wrap.addEventListener('click', (e) => {
    e.stopPropagation();
    wrap.classList.toggle('open');
  });
})();
function markRead(el){el.style.background='#fff';let c=document.querySelectorAll('.notif-item[style*="purple-light"]').length;document.getElementById('notifBadge').textContent=c;if(c===0)document.getElementById('notifBadge').style.display='none'}
document.addEventListener('click',e=>{const w=document.getElementById('notifWrap');if(w && !e.target.closest('.nav-notif-wrap'))w.classList.remove('open')})
</script>
</body>
</html>
