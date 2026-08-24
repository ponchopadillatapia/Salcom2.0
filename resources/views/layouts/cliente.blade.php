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
            z-index: 400;
            flex-shrink: 0;
        }
        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 19px;
            color: var(--purple);
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.1;
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }
        .nav-logo span.nav-title {
            display: block;
            font-family: 'Inter', sans-serif;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.6px;
            color: var(--purple-mid);
            text-transform: uppercase;
            max-width: min(420px, 42vw);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
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
        .nav-user { display: none; }
        .btn-logout {
            font-size: 12px;
            color: var(--gray-muted);
            padding: 6px 14px;
            border: 1px solid var(--border-light);
            border-radius: 20px;
            background: transparent;
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
        .skip-to-content {
            position: absolute;
            left: -9999px;
            top: 0;
            z-index: 10000;
            padding: 10px 18px;
            background: var(--purple);
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            border-radius: 10px;
            text-decoration: none;
        }
        .skip-to-content:focus {
            left: 12px;
            top: 62px;
            outline: 2px solid #fff;
            outline-offset: 2px;
        }
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
            transition: width .3s cubic-bezier(.4,0,.2,1), min-width .3s cubic-bezier(.4,0,.2,1), transform .25s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
        }
        .sidebar.collapsed { width: 60px; min-width: 60px; }
        /* Auto-despliegue del sidebar al pasar el mouse */
        .sidebar.collapsed:hover { width: 240px; min-width: 240px; }
        .sidebar.collapsed:hover .sb-text,
        .sidebar.collapsed:hover .sb-section { display: block; }
        .sidebar.collapsed:hover .sb-link { justify-content: flex-start; padding: 8px 16px; margin: 1px 8px; }
        .sb-toggle {
            height: 28px;
            width: 28px;
            min-height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin-left: auto;
            border: none;
            border-radius: 8px;
            background: transparent;
            font: inherit;
            color: inherit;
            cursor: pointer;
            flex-shrink: 0;
            transition: background .15s;
        }
        .sb-toggle:hover { background: var(--purple-subtle); }
        .sb-toggle svg { transition: transform 0.2s ease; flex-shrink: 0; color: var(--gray-muted); }
        .sidebar.collapsed .sb-toggle { display: none; }
        .sidebar.collapsed:hover .sb-toggle { display: inline-flex; }
        .sidebar.collapsed .sb-toggle svg { transform: rotate(180deg); }
        .sidebar.collapsed .sb-client-icon { cursor: pointer; }
        /* Bloque identidad cliente */
        .sb-client {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 12px 10px 16px;
            border-bottom: 1px solid var(--border-light);
            margin-bottom: 6px;
            flex-shrink: 0;
            transition: padding .25s cubic-bezier(.4,0,.2,1), margin .25s cubic-bezier(.4,0,.2,1), gap .25s cubic-bezier(.4,0,.2,1);
        }
        .sidebar.collapsed .sb-client {
            justify-content: center;
            padding: 6px 4px 8px;
            margin-bottom: 2px;
            gap: 0;
        }
        .sidebar.collapsed:hover .sb-client {
            justify-content: flex-start;
            padding: 14px 12px 10px 16px;
            margin-bottom: 6px;
            gap: 12px;
        }
        .sb-client-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .sb-client-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .sb-client-meta { min-width: 0; display: flex; align-items: center; flex: 1; }
        .sb-client-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }
        .sidebar.collapsed .sb-client-meta { display: none; }
        .sidebar.collapsed:hover .sb-client-meta { display: flex; }
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
        .nav-menu-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            margin-right: 2px;
            padding: 0;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: var(--purple);
            cursor: pointer;
            flex-shrink: 0;
            transition: background .15s;
        }
        .nav-menu-btn:hover { background: var(--purple-subtle); }
        .nav-menu-btn svg { display: block; }
        .nav-menu-btn .icon-close { display: none; }
        body.sb-open .nav-menu-btn .icon-open { display: none; }
        body.sb-open .nav-menu-btn .icon-close { display: block; }
        .sb-overlay {
            position: fixed;
            inset: 56px 0 0 0;
            background: rgba(20, 16, 28, 0.42);
            z-index: 250;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .2s ease, visibility .2s ease;
        }
        .sb-overlay.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        @include('partials.portal-shell-responsive')
    </style>
    @stack('styles')
</head>
<body>
<a href="#contenido-principal" class="skip-to-content">Saltar al contenido</a>
@php
    $navClienteNombre = session('cliente_nombre', 'Cliente');
@endphp
<nav class="top-nav">
    <div class="nav-logo">
        <button type="button" class="nav-menu-btn" id="navMenuBtn" aria-label="Abrir menú" aria-controls="appSidebar" aria-expanded="false">
            <svg class="icon-open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            <svg class="icon-close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        @include('partials.logo-salcom', ['size' => 'sm', 'color' => 'dark'])
        <span class="nav-title">Portal de Clientes</span>
    </div>
    <div class="nav-right">
        <a href="{{ route('clientes.pedidos') }}" class="nav-pedidos-quick" id="navPedidosQuick" title="Pedidos" aria-label="Ir a pedidos y carrito">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span class="sb-pedidos-badge js-pedidos-nav-badge" style="display:none;top:-2px;right:-2px" aria-hidden="true">0</span>
        </a>
        <div class="nav-notif-wrap" id="notifWrap" role="button" tabindex="0" aria-label="Notificaciones" aria-haspopup="true" aria-expanded="false" onclick="document.getElementById('notifDrop').classList.toggle('show')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#86868b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            @php
                $alertasClienteSinLeer = \App\Models\Alerta::where('destinatario_tipo', 'cliente')
                    ->where('destinatario_id', session('cliente_id'))
                    ->whereNotIn('estatus', ['leida', 'accionada'])
                    ->count();
            @endphp
            @if($alertasClienteSinLeer > 0)
            <span id="notifBadge" style="position:absolute;top:2px;right:2px;background:var(--red);color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;padding:0 5px;border-radius:999px;display:flex;align-items:center;justify-content:center">{{ $alertasClienteSinLeer > 9 ? '9+' : $alertasClienteSinLeer }}</span>
            @endif
            <div id="notifDrop" class="notif-drop">
                <div style="padding:12px 16px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700;color:var(--gray-text)">Notificaciones</div>
                @php
                    $notisCliente = \App\Models\Alerta::where('destinatario_tipo', 'cliente')
                        ->where('destinatario_id', session('cliente_id'))
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get();
                @endphp
                @forelse($notisCliente as $noti)
                <div class="notif-item" style="padding:10px 16px;border-bottom:1px solid var(--border-light);font-size:12px;">
                    <div style="font-weight:600;color:var(--gray-text)">{{ Str::limit($noti->titulo, 40) }}</div>
                    <div style="color:var(--gray-muted);margin-top:2px">{{ Str::limit($noti->contenido, 50) }}</div>
                </div>
                @empty
                <div style="padding:16px;text-align:center;font-size:12px;color:var(--gray-muted);">Sin notificaciones</div>
                @endforelse
            </div>
        </div>
        <form method="POST" action="{{ route('clientes.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Salir</button>
        </form>
    </div>
</nav>
@yield('hero')
<div class="wrapper">
    <div class="sb-overlay" id="sbOverlay"></div>
        <div class="sidebar" id="appSidebar">
        <div class="sb-client">
            <div class="sb-client-icon" title="Expandir menú" onclick="if(document.getElementById('appSidebar')?.classList.contains('collapsed')){document.getElementById('sbToggleBtnCliente')?.click();}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="sb-client-meta">
                <span class="sb-client-name" title="{{ $navClienteNombre }}">{{ $navClienteNombre }}</span>
            </div>
            <button type="button" class="sb-toggle" id="sbToggleBtnCliente" aria-expanded="true" aria-label="Contraer o expandir menú lateral" onclick="(function(){var s=document.getElementById('appSidebar');s.classList.toggle('collapsed');this.setAttribute('aria-expanded',s.classList.contains('collapsed')?'false':'true');}).call(this)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg></button>
        </div>
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
            <a href="{{ route('clientes.tracking') }}" class="sb-link {{ request()->routeIs('clientes.tracking') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div><span class="sb-text">Tracking</span></a>
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
    <div class="main-content @yield('main-class')" id="contenido-principal" tabindex="-1">@yield('content')</div>
</div>
<footer>
    <div class="footer-logo">Industrias Salcom</div>
    <p>&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>
<script>
(function () {
    var sidebar = document.getElementById('appSidebar');
    var overlay = document.getElementById('sbOverlay');
    var btn = document.getElementById('navMenuBtn');
    if (!sidebar || !btn) return;
    function isMobile() { return window.matchMedia('(max-width: 1024px)').matches; }
    function setOpen(open) {
        sidebar.classList.toggle('open', open);
        if (overlay) overlay.classList.toggle('show', open);
        document.body.classList.toggle('sb-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
    }
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        setOpen(!sidebar.classList.contains('open'));
    });
    if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
    window.addEventListener('resize', function () { if (!isMobile()) setOpen(false); });
    sidebar.addEventListener('click', function (e) {
        if (!isMobile()) return;
        if (e.target.closest('a.sb-link, a.sb-sublink, a.sb-deep')) setOpen(false);
    });
})();
window.SALCOM_CART_STORAGE_KEY = 'salcom_cliente_carrito_v1';
window.SALCOM_PEDIDOS_NAV_BADGE_KEY = 'salcom_cliente_pedidos_nav_badge';
window.SALCOM_PEDIDOS_HISTORIAL_KEY = @json(config('cliente_portal.historial_pedidos.storage_key'));
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
// Click-to-toggle notificaciones (touch); desktop también con hover por CSS
(() => {
  const wrap = document.getElementById('notifWrap');
  if (!wrap) return;
  const setOpen = (open) => {
    wrap.classList.toggle('open', !!open);
    wrap.setAttribute('aria-expanded', wrap.classList.contains('open') ? 'true' : 'false');
  };
  wrap.addEventListener('click', (e) => {
    e.stopPropagation();
    setOpen(!wrap.classList.contains('open'));
  });
  wrap.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      setOpen(!wrap.classList.contains('open'));
    }
  });
})();
function markRead(el){el.style.background='#fff';let c=document.querySelectorAll('.notif-item[style*="purple-light"]').length;document.getElementById('notifBadge').textContent=c;if(c===0)document.getElementById('notifBadge').style.display='none'}
document.addEventListener('click',e=>{const w=document.getElementById('notifWrap');if(w && !e.target.closest('.nav-notif-wrap')){w.classList.remove('open');w.setAttribute('aria-expanded','false');}})
</script>
</body>
</html>
