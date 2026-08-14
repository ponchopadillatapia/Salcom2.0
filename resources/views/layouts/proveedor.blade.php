<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal de Proveedores') — Industrias Salcom</title>
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

        /* ── NAVBAR (frosted glass iOS style) ── */
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
        .nav-chip { display: none; }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-user { display: none; }
        .nav-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple), #8b5cf6);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.02em;
            flex-shrink: 0;
        }
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
        .btn-logout:active {
            transform: scale(0.97);
        }

        /* ── Dropdown notificaciones ── */
        .notif-dropdown{display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:var(--white);border:1px solid var(--border-light);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:9999;overflow:hidden}
        .notif-dropdown.show{display:block}
        .notif-header{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700;color:var(--gray-text)}
        .notif-count{font-size:11px;font-weight:600;color:var(--purple);background:var(--purple-subtle);padding:2px 8px;border-radius:999px}
        .notif-item{padding:12px 16px;border-bottom:1px solid var(--border-light);cursor:pointer;transition:background .15s}
        .notif-item:hover{background:var(--purple-subtle)}
        .notif-item.read{opacity:.6}
        .notif-item-title{font-size:12px;font-weight:600;color:var(--gray-text);margin-bottom:3px}
        .notif-item-desc{font-size:11px;color:var(--gray-muted);line-height:1.4}
        .notif-item-time{font-size:10px;color:var(--gray-muted);margin-top:4px}
        .notif-empty{padding:24px;text-align:center;font-size:12px;color:var(--gray-muted)}
        .notif-footer{display:block;text-align:center;padding:10px;font-size:12px;font-weight:600;color:var(--purple);text-decoration:none;border-top:1px solid var(--border-light)}
        .notif-footer:hover{background:var(--purple-subtle)}

        /* ── HERO ── */
        .hero-band {
            background: transparent;
            padding: 28px 32px 10px;
            border-bottom: none;
            flex-shrink: 0;
        }
        .hero-band h1 {
            font-size: 24px;
            color: var(--gray-text);
            font-weight: 700;
            letter-spacing: -0.4px;
            margin: 0;
        }
        .hero-band p {
            color: var(--gray-muted);
            font-size: 13px;
            margin-top: 4px;
            font-weight: 400;
        }

        /* ── WRAPPER ── */
        .wrapper { display: flex; flex: 1; }

        /* ── SIDEBAR ── */
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
        .sb-toggle svg { transition: transform .2s ease; flex-shrink: 0; color: var(--gray-muted); }
        .sidebar.collapsed .sb-toggle { display: none; }
        /* Al pasar el mouse se abre el menú (hover) y vuelve la flecha para fijarlo */
        .sidebar.collapsed:hover .sb-toggle { display: inline-flex; }
        .sidebar.collapsed .sb-toggle svg { transform: rotate(180deg); }
        .sidebar.collapsed .sb-client-icon { cursor: pointer; }
        /* Bloque identidad proveedor: foto + nombre + flecha en una fila */
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
            flex-wrap: wrap;
        }
        .sidebar.collapsed:hover .sb-client {
            justify-content: flex-start;
            padding: 14px 12px 10px 16px;
            margin-bottom: 6px;
            gap: 12px;
            flex-wrap: nowrap;
        }
        .sb-client-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6B3FA0, #9C6DD0);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .sb-client-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .sb-client-meta {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        .sb-client-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }
        .sb-client-id {
            font-size: 11px;
            color: var(--gray-muted);
            white-space: nowrap;
            flex-shrink: 0;
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
            font-size: 11px;
            font-weight: 600;
            color: #86868b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 22px 20px 8px;
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
            border-radius: 10px;
            white-space: nowrap;
            flex-shrink: 0;
            transition: var(--transition);
            border-left: none;
        }
        .sb-link:hover {
            background: var(--purple-subtle);
            color: var(--purple);
            transform: translateX(2px);
        }
        .sb-link.active {
            background: var(--purple-light);
            color: var(--purple);
            font-weight: 600;
        }
        .sb-icon {
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
            margin-top: auto;
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
@php
    $navNombre = session('proveedor_nombre', 'Proveedor');
    $navProv = null;
    if (session('proveedor_id')) {
        $navProv = \App\Models\ProveedorUser::find(session('proveedor_id'));
        if ($navProv && $navProv->nombre) {
            $navNombre = $navProv->nombre;
            if (session('proveedor_nombre') !== $navProv->nombre) {
                session(['proveedor_nombre' => $navProv->nombre]);
            }
        }
    }
    $navPartes = preg_split('/\s+/u', trim($navNombre)) ?: [];
    $navIniciales = '';
    foreach (array_slice($navPartes, 0, 2) as $p) {
        $navIniciales .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    if ($navIniciales === '') {
        $navIniciales = 'P';
    }
    $navCodigo = null;
    if (isset($navProv) && $navProv) {
        $navCodigo = $navProv->id_proveedor ?: session('proveedor_codigo');
    } else {
        $navCodigo = session('proveedor_codigo');
    }
@endphp
<nav class="top-nav">
    <div class="nav-logo">
        @include('partials.logo-salcom', ['size' => 'sm', 'color' => 'dark'])
        <span class="nav-title">Portal de Proveedores</span>
    </div>
    <div class="nav-right">
        {{-- Campanita de notificaciones con dropdown --}}
        @php
            $alertasSinLeer = \App\Models\Alerta::where('destinatario_tipo', 'proveedor')
                ->where('destinatario_id', session('proveedor_id'))
                ->where('estatus', '!=', 'leida')
                ->where('estatus', '!=', 'accionada')
                ->count();
            $alertasRecientes = \App\Models\Alerta::where('destinatario_tipo', 'proveedor')
                ->where('destinatario_id', session('proveedor_id'))
                ->where('estatus', '!=', 'leida')
                ->where('estatus', '!=', 'accionada')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        @endphp
        <div class="notif-wrapper" style="position:relative;"
             data-alertas-url="{{ route('proveedores.alertas.recientes') }}"
             data-alertas-leer-url="{{ url('/proveedor/alertas') }}"
             data-csrf="{{ csrf_token() }}">
            <button type="button" class="notif-bell" id="notifBellBtn" onclick="document.getElementById('notifDropdown').classList.toggle('show')" style="background:none;border:none;cursor:pointer;position:relative;padding:4px;" title="Notificaciones" aria-label="Notificaciones">
                <svg id="notifBellIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $alertasSinLeer > 0 ? 'var(--purple)' : 'var(--gray-muted)' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span id="notifBadge" style="position:absolute;top:-2px;right:-4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;width:18px;height:18px;border-radius:50%;{{ $alertasSinLeer > 0 ? 'display:flex' : 'display:none' }};align-items:center;justify-content:center;">{{ $alertasSinLeer > 9 ? '9+' : $alertasSinLeer }}</span>
            </button>
            <div id="notifDropdown" class="notif-dropdown">
                <div class="notif-header">
                    <span>Notificaciones</span>
                    <span class="notif-count" id="notifCountLabel" style="{{ $alertasSinLeer > 0 ? '' : 'display:none;' }}">{{ $alertasSinLeer }} nuevas</span>
                </div>
                <div id="notifItems">
                @forelse($alertasRecientes as $notif)
                <div class="notif-item" data-alerta-id="{{ $notif->id }}" data-leida="0">
                    <div class="notif-item-title">{{ Str::limit($notif->titulo, 50) }}</div>
                    <div class="notif-item-desc">{{ Str::limit($notif->contenido, 80) }}</div>
                    <div class="notif-item-time">{{ $notif->created_at->diffForHumans() }}</div>
                </div>
                @empty
                <div class="notif-empty">Sin notificaciones nuevas</div>
                @endforelse
                </div>
                <a href="{{ route('proveedores.ia') }}" class="notif-footer">Ir a Módulo IA</a>
            </div>
        </div>
        <form method="POST" action="{{ route('proveedores.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Salir</button>
        </form>
    </div>
</nav>

{{-- HERO --}}
@yield('hero')

{{-- WRAPPER --}}
<div class="wrapper">

    {{-- SIDEBAR --}}
    <div class="sidebar" id="appSidebar">
        {{-- Foto + nombre + flecha en la misma fila --}}
        <div class="sb-client">
            <div class="sb-client-icon" id="sbClientIcon" title="Expandir menú" onclick="if(document.getElementById('appSidebar')?.classList.contains('collapsed')){sbToggle();}">
                @if(!empty($navProv?->foto))
                    <img src="{{ asset('storage/'.$navProv->foto) }}" alt="Foto de perfil">
                @else
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                @endif
            </div>
            <div class="sb-client-meta">
                <span class="sb-client-name" title="{{ $navNombre }}">{{ $navNombre }}</span>
            </div>
            <button type="button" class="sb-toggle" id="sbToggleBtn" aria-expanded="true" aria-label="Contraer o expandir menú lateral" onclick="sbToggle(this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
        </div>
        <nav class="sb-nav">
            @php
                $portalOk = $proveedorPortalActivo ?? true;
                $lockHref = route('proveedores.onboarding');
                $lockAttr = ! $portalOk ? 'style="opacity:.45" title="Completa onboarding"' : '';
            @endphp

            <div class="sb-section">Inicio</div>
            <a href="{{ $portalOk ? route('proveedores.portal') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.portal') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <span class="sb-text">Inicio</span>
            </a>
            <a href="{{ $portalOk ? route('proveedores.dashboard') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.dashboard') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></div>
                <span class="sb-text">Dashboard</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Productos</div>
            <a href="{{ $portalOk ? route('proveedores.mis-productos') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.mis-productos') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></div>
                <span class="sb-text">Mis productos</span>
            </a>
            <a href="{{ $portalOk ? route('proveedores.alta-producto') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.alta-producto') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                <span class="sb-text">Alta de producto</span>
            </a>
            <a href="{{ $portalOk ? route('proveedores.inventario') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.inventario') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
                <span class="sb-text">Inventario</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Facturas</div>
            <a href="{{ $portalOk ? route('proveedores.fiscal') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.fiscal') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg></div>
                <span class="sb-text">Alta Facturas</span>
            </a>
            <a href="{{ $portalOk ? route('proveedores.facturas') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.facturas*') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                <span class="sb-text">Facturas</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Pagos</div>
            <a href="{{ $portalOk ? route('proveedores.payment-history') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.payment-history') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                <span class="sb-text">Pagos</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Operación</div>
            <a href="{{ $portalOk ? route('proveedores.oc') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.oc') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                <span class="sb-text">Órdenes de Compra</span>
            </a>
            <a href="{{ $portalOk ? route('proveedores.otif') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.otif') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <span class="sb-text">OTIF</span>
            </a>
            <a href="{{ $portalOk ? route('proveedores.forecast') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.forecast') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                <span class="sb-text">Forecast</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Mi empresa</div>
            <a href="{{ route('proveedores.onboarding') }}" class="sb-link {{ request()->routeIs('proveedores.onboarding') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>
                <span class="sb-text">Onboarding</span>
            </a>
            <a href="{{ $portalOk ? route('proveedores.business') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.business') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
                <span class="sb-text">Business</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Inteligencia</div>
            <a href="{{ $portalOk ? route('proveedores.ia') : $lockHref }}" class="sb-link {{ request()->routeIs('proveedores.ia*') ? 'active' : '' }}" {!! $lockAttr !!}>
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg></div>
                <span class="sb-text">Módulo IA</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Cuenta</div>
            <a href="{{ route('proveedores.perfil') }}" class="sb-link {{ request()->routeIs('proveedores.perfil') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <span class="sb-text">Mi Perfil</span>
            </a>
        </nav>
    </div>

    {{-- CONTENIDO --}}
    <div class="main-content @yield('main-class')">
        @if(isset($proveedorPortalActivo) && ! $proveedorPortalActivo && ! request()->routeIs('proveedores.onboarding'))
        <div style="background:#fff7ed;border:1px solid #fcd34d;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#92400e;">
            Cuenta en onboarding. <a href="{{ route('proveedores.onboarding') }}" style="color:var(--purple);font-weight:600;">Ir a Onboarding</a>
        </div>
        @endif
        @yield('content')
    </div>

</div>

<footer>
    <div class="footer-logo">Industrias Salcom</div>
    <p><a href="/aviso-privacidad" style="color:var(--gray-muted);text-decoration:none;" target="_blank">Aviso de Privacidad</a> · &copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

<script>
function sbToggle(btn) {
    var s = document.getElementById('appSidebar');
    s.classList.toggle('collapsed');
    var el = btn || document.getElementById('sbToggleBtn');
    if (el) el.setAttribute('aria-expanded', s.classList.contains('collapsed') ? 'false' : 'true');
}
</script>
<script>
// Cerrar dropdown de notificaciones al hacer clic fuera
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('notifDropdown');
    var wrapper = document.querySelector('.notif-wrapper');
    if (dropdown && wrapper && !wrapper.contains(e.target)) {
        dropdown.classList.remove('show');
    }
});

(function () {
    var wrap = document.querySelector('.notif-wrapper');
    if (!wrap) return;
    var url = wrap.getAttribute('data-alertas-url');
    var leerBase = wrap.getAttribute('data-alertas-leer-url');
    var csrf = wrap.getAttribute('data-csrf') || '';
    if (!url) return;

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function updateBadge(n) {
        n = Math.max(0, parseInt(n, 10) || 0);
        var badge = document.getElementById('notifBadge');
        var icon = document.getElementById('notifBellIcon');
        var label = document.getElementById('notifCountLabel');
        if (badge) {
            badge.style.display = n > 0 ? 'flex' : 'none';
            badge.textContent = n > 9 ? '9+' : String(n);
        }
        if (icon) icon.setAttribute('stroke', n > 0 ? 'var(--purple)' : 'var(--gray-muted)');
        if (label) {
            label.style.display = n > 0 ? '' : 'none';
            label.textContent = n + ' nuevas';
        }
    }

    function render(data) {
        updateBadge(data.sin_leer || 0);
        var items = document.getElementById('notifItems');
        if (items) {
            var list = (data.items || []).filter(function (it) { return !it.leida; });
            if (!list.length) {
                items.innerHTML = '<div class="notif-empty">Sin notificaciones nuevas</div>';
            } else {
                items.innerHTML = list.map(function (it) {
                    return '<div class="notif-item" data-alerta-id="' + esc(it.id) + '" data-leida="0">'
                        + '<div class="notif-item-title">' + esc((it.titulo || '').substring(0, 50)) + '</div>'
                        + '<div class="notif-item-desc">' + esc((it.contenido || '').substring(0, 80)) + '</div>'
                        + '<div class="notif-item-time">' + esc(it.hace || '') + '</div>'
                        + '</div>';
                }).join('');
            }
        }

        if (data.onboarding && window.__salcomOnboardingWatch) {
            var o = data.onboarding;
            var w = window.__salcomOnboardingWatch;
            var nuevoEstatus = o.estatus || '';
            var nuevoActivo = o.activo ? '1' : '0';
            if (nuevoEstatus !== w.estatus || nuevoActivo !== w.activo) {
                window.__salcomOnboardingWatch = { estatus: nuevoEstatus, activo: nuevoActivo };
                if (window.location.pathname.indexOf('onboarding') !== -1) {
                    window.location.reload();
                }
            }
        }
    }

    function marcarLeida(el) {
        if (!el || el.getAttribute('data-leida') === '1') return;
        var id = el.getAttribute('data-alerta-id');
        if (!id || !leerBase) return;

        el.setAttribute('data-leida', '1');
        el.style.transition = 'opacity .15s, transform .15s';
        el.style.opacity = '0';
        el.style.transform = 'translateX(8px)';

        var badge = document.getElementById('notifBadge');
        var actual = badge && badge.style.display !== 'none'
            ? (parseInt(badge.textContent, 10) || 0)
            : 0;
        if (actual > 0) updateBadge(actual - 1);

        setTimeout(function () {
            if (el && el.parentNode) el.remove();
            var box = document.getElementById('notifItems');
            if (box && !box.querySelector('.notif-item')) {
                box.innerHTML = '<div class="notif-empty">Sin notificaciones nuevas</div>';
            }
        }, 160);

        fetch(leerBase + '/' + id + '/leer', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ _token: csrf })
        })
            .then(function (r) {
                if (!r.ok) throw new Error('mark-failed');
                return r.json();
            })
            .then(function (data) {
                if (data && typeof data.sin_leer !== 'undefined') {
                    updateBadge(data.sin_leer);
                }
            })
            .catch(function () {
                // Si falló, el próximo poll restaura el badge/lista
                poll();
            });
    }

    var itemsBox = document.getElementById('notifItems');
    if (itemsBox) {
        itemsBox.addEventListener('click', function (e) {
            var item = e.target.closest('.notif-item');
            if (!item || !itemsBox.contains(item)) return;
            e.stopPropagation();
            marcarLeida(item);
        });
    }

    function poll() {
        if (document.hidden) return;
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) { if (data) render(data); })
            .catch(function () {});
    }

    // Al instante + cada 3s (no 8)
    poll();
    setInterval(poll, 3000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
    window.addEventListener('focus', poll);
})();
</script>
@stack('scripts')

{{-- Polling en tiempo real: toast de notificaciones --}}
<div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:380px"></div>
<style>
.salcom-toast{display:flex;align-items:flex-start;gap:10px;padding:14px 18px;background:#fff;border:1px solid #e5e7eb;border-left:4px solid #6B3FA0;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);animation:toastIn .3s ease;font-family:inherit}
.salcom-toast.salcom-toast-exit{animation:toastOut .3s ease forwards}
.salcom-toast-icon{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#f3e8ff,#ede9fe);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.salcom-toast-icon svg{width:16px;height:16px;stroke:#6B3FA0}
.salcom-toast-body{flex:1;min-width:0}
.salcom-toast-title{font-size:13px;font-weight:700;color:#1f2937;margin:0 0 2px}
.salcom-toast-msg{font-size:12px;color:#6b7280;margin:0;line-height:1.4}
.salcom-toast-close{background:none;border:none;color:#9ca3af;cursor:pointer;font-size:16px;padding:0 0 0 8px;line-height:1}
@keyframes toastIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
@keyframes toastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(40px)}}
</style>
<script>
(function(){
    var container = document.getElementById('toast-container');
    var url = '{{ route("proveedores.alertas.recientes") }}';
    var ultimoCount = {{ $alertasSinLeer ?? 0 }};
    var ultimosIds = [];

    function showToast(titulo, contenido) {
        var toast = document.createElement('div');
        toast.className = 'salcom-toast';
        toast.innerHTML = '<div class="salcom-toast-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c1.1 0 2-.9 2-2H10a2 2 0 0 0 2 2z"/><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg></div>'
            + '<div class="salcom-toast-body"><p class="salcom-toast-title">' + titulo + '</p><p class="salcom-toast-msg">' + contenido + '</p></div>'
            + '<button class="salcom-toast-close" onclick="this.parentElement.classList.add(\'salcom-toast-exit\');setTimeout(function(){this.parentElement.remove()}.bind(this),300)">&times;</button>';
        container.appendChild(toast);
        // Auto-remove after 8s
        setTimeout(function(){ if(toast.parentElement){toast.classList.add('salcom-toast-exit');setTimeout(function(){toast.remove()},300)} }, 8000);
    }

    function poll() {
        fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){return r.json()})
            .then(function(data){
                if (data.sin_leer > ultimoCount && data.items && data.items.length) {
                    // Mostrar toast solo para alertas nuevas
                    data.items.forEach(function(item){
                        if (ultimosIds.indexOf(item.id) === -1) {
                            showToast(item.titulo, item.contenido);
                        }
                    });
                    // Actualizar campanita
                    var badge = document.querySelector('.notif-badge');
                    if (badge) { badge.textContent = data.sin_leer; badge.style.display = 'flex'; }
                }
                ultimoCount = data.sin_leer;
                ultimosIds = (data.items || []).map(function(i){return i.id});
            })
            .catch(function(){});
    }

    // Poll cada 15 segundos
    setInterval(poll, 15000);
    // Guardar IDs actuales para no mostrar toast de las que ya estaban
    @if(isset($alertasSinLeer) && $alertasSinLeer > 0)
    fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
        .then(function(r){return r.json()})
        .then(function(data){ ultimosIds = (data.items || []).map(function(i){return i.id}); })
        .catch(function(){});
    @endif
})();
</script>
</body>
</html>
