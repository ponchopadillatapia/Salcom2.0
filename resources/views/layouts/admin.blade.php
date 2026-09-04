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

        /* ── NAVBAR (frosted glass, igual que proveedores) ── */
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
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-user { display: none; }
        .sb-badge{margin-left:auto;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:var(--red);color:#fff;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
        .sidebar.collapsed .sb-badge{display:none}
        .sidebar.collapsed:hover .sb-badge{display:inline-flex}
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
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); transform: scale(1.02); }
        .btn-logout:active { transform: scale(0.97); }

        /* ── Dropdown notificaciones ── */
        .notif-dropdown{display:none;position:absolute;top:calc(100% + 8px);right:0;width:320px;background:var(--white);border:1px solid var(--border-light);border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.12);z-index:9999;overflow:hidden}
        .notif-dropdown.show{display:block}
        .notif-header{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700;color:var(--gray-text)}
        .notif-count{font-size:11px;font-weight:600;color:var(--purple);background:var(--purple-subtle);padding:2px 8px;border-radius:999px}
        .notif-item{padding:12px 16px;border-bottom:1px solid var(--border-light);cursor:pointer;transition:background .15s}
        .notif-item:hover{background:var(--purple-subtle)}
        .notif-item-title{font-size:12px;font-weight:600;color:var(--gray-text);margin-bottom:3px}
        .notif-item-desc{font-size:11px;color:var(--gray-muted);line-height:1.4}
        .notif-item-time{font-size:10px;color:var(--gray-muted);margin-top:4px}
        .notif-empty{padding:24px;text-align:center;font-size:12px;color:var(--gray-muted)}
        .notif-footer{display:block;text-align:center;padding:10px;font-size:12px;font-weight:600;color:var(--purple);text-decoration:none;border-top:1px solid var(--border-light)}
        .notif-footer:hover{background:var(--purple-subtle)}

        /* ── HERO (inside main-content, beside the sidebar) ── */
        .hero-band {
            background: transparent;
            padding: 0 0 18px;
            border-bottom: none;
        }
        .hero-band h1 { font-size: 24px; color: var(--gray-text); font-weight: 700; letter-spacing: -0.4px; margin: 0; }
        .hero-band p { color: var(--gray-muted); font-size: 13px; margin-top: 4px; font-weight: 400; }

        /* ── WRAPPER ── */
        .wrapper { display: flex; flex: 1; }

        /* ── SIDEBAR (frosted glass) ── */
        .sidebar {
            width: 240px;
            min-width: 240px;
            background: rgba(255,255,255,0.8);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            transition: width .3s cubic-bezier(.4,0,.2,1), min-width .3s cubic-bezier(.4,0,.2,1), box-shadow .3s, transform .25s cubic-bezier(.4,0,.2,1);
            overflow: hidden;
            flex-shrink: 0;
        }
        .sidebar.collapsed { width: 60px; min-width: 60px; }
        .sidebar.collapsed:hover {
            width: 240px; min-width: 240px;
            box-shadow: 8px 0 24px rgba(0,0,0,0.06);
        }
        .sidebar.collapsed:hover .sb-text { display: inline; }
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
        .sidebar.collapsed:hover .sb-toggle { display: inline-flex; }
        .sidebar.collapsed .sb-toggle svg { transform: rotate(180deg); }
        .sidebar.collapsed .sb-client-icon { cursor: pointer; }

        .sb-client {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 12px 10px 16px;
            border-bottom: 1px solid var(--border-light);
            margin-bottom: 6px;
            flex-shrink: 0;
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
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #6B3FA0, #9C6DD0);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; overflow: hidden;
        }
        .sb-client-icon img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .sb-client-meta { min-width: 0; display: flex; align-items: center; flex: 1; }
        .sb-client-name {
            font-size: 13px; font-weight: 700; color: var(--gray-text);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0;
        }
        .sidebar.collapsed .sb-client-meta { display: none; }
        .sidebar.collapsed:hover .sb-client-meta { display: flex; }

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

        .sb-section {
            font-size: 10px;
            font-weight: 700;
            color: var(--gray-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 16px 16px 6px;
        }
        .sidebar.collapsed .sb-section { display: none; }
        .sidebar.collapsed:hover .sb-section { display: block; }

        .sb-hr {
            height: 1px;
            background: var(--border-light);
            margin: 8px 16px;
            flex-shrink: 0;
        }
        .sidebar.collapsed .sb-hr { margin: 6px 10px; }

        /* ── Submenu desplegable ── */
        .sb-submenu { position: relative; }
        .sb-submenu-toggle { width: 100%; text-align: left; background: none; border: none; font-family: inherit; position: relative; }
        .sb-submenu-toggle .sb-chevron { margin-left: auto; transition: transform .2s; flex-shrink: 0; color: var(--gray-muted); }
        .sb-submenu.open .sb-submenu-toggle .sb-chevron { transform: rotate(180deg); }
        .sb-submenu-items { display: none; padding-left: 20px; }
        .sb-submenu.open .sb-submenu-items { display: flex; flex-direction: column; }
        .sb-sublink { font-size: 12px !important; padding: 6px 16px !important; }
        .sb-sublink::before { content: ''; width: 4px; height: 4px; border-radius: 50%; background: var(--gray-muted); flex-shrink: 0; }
        .sb-sublink.active::before { background: var(--purple); }
        .sb-submenu-nested .sb-nested-items { display: none; padding-left: 16px; }
        .sb-submenu-nested.open .sb-nested-items { display: flex; flex-direction: column; }
        .sb-nested-toggle { border: none !important; outline: none !important; background: none !important; box-shadow: none !important; }
        .sb-nested-toggle .sb-chevron { margin-left: auto; transition: transform .2s; flex-shrink: 0; color: var(--gray-muted); }
        .sb-submenu-nested.open .sb-nested-toggle .sb-chevron { transform: rotate(180deg); }
        .sb-deep { font-size: 11px !important; padding: 5px 12px !important; }
        .sb-deep::before { width: 3px; height: 3px; }
        .sidebar.collapsed .sb-submenu-items { display: none !important; }
        .sidebar.collapsed:hover .sb-submenu.open .sb-submenu-items { display: flex; flex-direction: column; }
        /* Auto-abrir si estamos en esa sección */
        .sb-submenu:has(.sb-submenu-toggle.active) { }

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

        /* Separador de fecha estandar para tablas. Uso: fila tr.date-row con un td colspan que muestre la fecha. */
        tr.date-row td,
        .admin-table tr.date-row td {
            background: var(--purple-subtle) !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            color: var(--purple) !important;
            padding: 8px 16px !important;
            border-top: 2px solid var(--purple) !important;
            border-bottom: 2px solid var(--purple) !important;
            text-transform: capitalize;
        }

        @include('partials.portal-shell-responsive')
    </style>
    @stack('styles')
</head>
<body>

@php
    $navAdminNombre = session('admin_nombre', 'Admin');
    $navAdmin = null;
    if (session('admin_id')) {
        $navAdmin = \App\Models\AdminUser::find(session('admin_id'));
        if ($navAdmin && $navAdmin->nombre) {
            $navAdminNombre = $navAdmin->nombre;
        }
    }
@endphp
<nav class="top-nav">
    <div class="nav-logo">
        <button type="button" class="nav-menu-btn" id="navMenuBtn" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
            <svg class="icon-open" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
            <svg class="icon-close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        @include('partials.logo-salcom', ['size' => 'sm', 'color' => 'dark'])
        <span class="nav-title">Portal Administrativo</span>
    </div>
    <div class="nav-right">
        @php
            $adminPagosSinLeer = \App\Models\Alerta::where('destinatario_tipo', 'admin')
                ->whereIn('tipo', ['factura_pago_pendiente', 'abono_interno_registrado', 'pago_programado', 'pago_realizado'])
                ->whereNotIn('estatus', ['leida', 'accionada'])
                ->count();
            $adminAlertasRecientes = \App\Models\Alerta::where('destinatario_tipo', 'admin')
                ->whereIn('tipo', ['factura_pago_pendiente', 'abono_interno_registrado', 'pago_programado', 'pago_realizado'])
                ->whereNotIn('estatus', ['leida', 'accionada'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        @endphp
        <div class="notif-wrapper" style="position:relative;"
             data-alertas-url="{{ route('admin.pagos.alertas') }}"
             data-alertas-leer-url="{{ url('/admin/pagos/alertas') }}"
             data-csrf="{{ csrf_token() }}">
            <button type="button" class="notif-bell" id="notifBellBtn" onclick="document.getElementById('notifDropdown').classList.toggle('show')" style="background:none;border:none;cursor:pointer;position:relative;padding:4px;" title="Notificaciones" aria-label="Notificaciones">
                <svg id="notifBellIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="{{ $adminPagosSinLeer > 0 ? 'var(--purple)' : 'var(--gray-muted)' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span id="notifBadge" style="position:absolute;top:-2px;right:-4px;background:var(--red);color:#fff;font-size:10px;font-weight:700;width:18px;height:18px;border-radius:50%;{{ $adminPagosSinLeer > 0 ? 'display:flex' : 'display:none' }};align-items:center;justify-content:center;">{{ $adminPagosSinLeer > 9 ? '9+' : $adminPagosSinLeer }}</span>
            </button>
            <div id="notifDropdown" class="notif-dropdown">
                <div class="notif-header">
                    <span>Notificaciones</span>
                    <span class="notif-count" id="notifCountLabel" style="{{ $adminPagosSinLeer > 0 ? '' : 'display:none;' }}">{{ $adminPagosSinLeer }} nuevas</span>
                </div>
                <div id="notifItems">
                @forelse($adminAlertasRecientes as $notif)
                <div class="notif-item" data-alerta-id="{{ $notif->id }}" data-url="{{ isset($notif->datos['codigo_proveedor']) ? route('admin.pagos.proveedor', $notif->datos['codigo_proveedor']) : route('admin.pagos') }}">
                    <div class="notif-item-title">{{ \Illuminate\Support\Str::limit($notif->titulo, 50) }}</div>
                    <div class="notif-item-desc">{{ \Illuminate\Support\Str::limit($notif->contenido, 80) }}</div>
                    <div class="notif-item-time">{{ $notif->created_at->diffForHumans() }}</div>
                </div>
                @empty
                <div class="notif-empty">Sin notificaciones nuevas</div>
                @endforelse
                </div>
                <a href="{{ route('admin.pagos') }}" class="notif-footer">Ir a Pagos al proveedor</a>
            </div>
        </div>
        <form method="POST" action="/logout-admin" style="margin:0;">
            @csrf
            <button type="submit" class="btn-logout">Salir</button>
        </form>
    </div>
</nav>

<div class="wrapper">
    <div class="sb-overlay" id="sbOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sb-client">
            <div class="sb-client-icon" title="Expandir menú" onclick="if(document.getElementById('sidebar')?.classList.contains('collapsed')){sbToggle();}">
                @if(!empty($navAdmin?->foto))
                    <img src="{{ asset('storage/'.$navAdmin->foto) }}" alt="Foto">
                @else
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                @endif
            </div>
            <div class="sb-client-meta">
                <span class="sb-client-name" title="{{ $navAdminNombre }}">{{ $navAdminNombre }}</span>
            </div>
            <button type="button" class="sb-toggle" id="sbToggleBtn" aria-expanded="true" aria-label="Contraer o expandir menú lateral" onclick="sbToggle(this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
        </div>
        <nav class="sb-nav">
            {{-- Orden alineado al portal de proveedores --}}
            <div class="sb-section">Inicio</div>
            <a href="{{ route('admin.dashboard') }}" class="sb-link {{ request()->is('admin/dashboard*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <span class="sb-text">Inicio</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Productos</div>
            <div class="sb-submenu">
                <button type="button" class="sb-link sb-submenu-toggle {{ request()->is('admin/alta-producto*') ? 'active' : '' }}" onclick="this.parentElement.classList.toggle('open')">
                    <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
                    <span class="sb-text">Alta de Producto</span>
                    <svg class="sb-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="sb-submenu-items">
                    <a href="{{ route('admin.alta-producto') }}" class="sb-link sb-sublink {{ request()->is('admin/alta-producto') ? 'active' : '' }}">
                        <span class="sb-text">Compras</span>
                    </a>
                    <a href="{{ route('admin.alta-producto-mto') }}" class="sb-link sb-sublink {{ request()->is('admin/alta-producto-mto*') ? 'active' : '' }}">
                        <span class="sb-text">Mantenimiento</span>
                    </a>
                    <a href="{{ route('admin.alta-producto-pt') }}" class="sb-link sb-sublink {{ request()->is('admin/alta-producto-pt*') ? 'active' : '' }}">
                        <span class="sb-text">Comercial</span>
                    </a>
                </div>
            </div>
            <a href="{{ route('admin.productos') }}" class="sb-link {{ request()->is('admin/productos*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></div>
                <span class="sb-text">Productos</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Pagos</div>
            <div class="sb-submenu">
                <button type="button" class="sb-link sb-submenu-toggle {{ request()->is('admin/pagos*') || request()->is('admin/pago-proveedores*') ? 'active' : '' }}" onclick="this.parentElement.classList.toggle('open')">
                    <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                    <span class="sb-text">Pagos</span>
                    <svg class="sb-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="sb-submenu-items">
                    @php
                        $badgePorCuenta = \App\Models\AbonoProveedor::query()
                            ->whereIn('estatus', ['guardado', 'pagado'])
                            ->selectRaw('poliza_key, count(*) as total')
                            ->groupBy('poliza_key')
                            ->pluck('total', 'poliza_key');
                    @endphp
                    <a href="{{ route('admin.anticipos') }}" class="sb-link sb-sublink {{ request()->is('admin/anticipos*') ? 'active' : '' }}">
                        <span class="sb-text">Anticipo</span>
                    </a>
                    <a href="{{ route('admin.pagos') }}" class="sb-link sb-sublink {{ request()->is('admin/pagos') || request()->is('admin/pagos/*') ? 'active' : '' }}">
                        <span class="sb-text">Formato para pago</span>
                    </a>
                    <a href="{{ route('admin.pago-proveedores') }}" class="sb-link sb-sublink {{ request()->is('admin/pago-proveedores*') ? 'active' : '' }}">
                        <span class="sb-text">Pago a proveedor</span>
                    </a>
                    {{-- Submenú Abono al proveedor --}}
                    <div class="sb-submenu-nested {{ request()->is('admin/abono-proveedor*') || request()->is('admin/historial-abonos*') ? 'open' : '' }}">
                        <button type="button" class="sb-link sb-sublink sb-nested-toggle {{ request()->is('admin/abono-proveedor*') || request()->is('admin/historial-abonos*') ? 'active' : '' }}" onclick="this.parentElement.classList.toggle('open')">
                            <span class="sb-text">Abono al proveedor</span>
                            <svg class="sb-chevron" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="sb-nested-items">
                            <a href="{{ route('admin.historial-abonos', ['cuenta' => '8969_mxn']) }}" class="sb-link sb-sublink sb-deep {{ request()->is('admin/historial-abonos*') && request('cuenta') === '8969_mxn' ? 'active' : '' }}">
                                <span class="sb-text">8969 — Nacionales MXN</span>
                            </a>
                            <a href="{{ route('admin.historial-abonos', ['cuenta' => '8969_aduanal']) }}" class="sb-link sb-sublink sb-deep {{ request()->is('admin/historial-abonos*') && request('cuenta') === '8969_aduanal' ? 'active' : '' }}">
                                <span class="sb-text">8969 — Agente aduanal</span>
                            </a>
                            <a href="{{ route('admin.historial-abonos', ['cuenta' => '2026_base']) }}" class="sb-link sb-sublink sb-deep {{ request()->is('admin/historial-abonos*') && request('cuenta') === '2026_base' ? 'active' : '' }}">
                                <span class="sb-text">2026 — Banco Base Dollar</span>
                            </a>
                            <a href="{{ route('admin.historial-abonos', ['cuenta' => '2026_extranjera']) }}" class="sb-link sb-sublink sb-deep {{ request()->is('admin/historial-abonos*') && request('cuenta') === '2026_extranjera' ? 'active' : '' }}">
                                <span class="sb-text">2026 — Extranjera</span>
                            </a>
                        </div>
                    </div>
                    <a href="{{ route('admin.reembolsos') }}" class="sb-link sb-sublink {{ request()->is('admin/reembolsos') ? 'active' : '' }}">
                        <span class="sb-text">Reembolsos</span>
                    </a>
                    <a href="{{ route('admin.reembolsos-viaje') }}" class="sb-link sb-sublink {{ request()->is('admin/reembolsos-viaje*') ? 'active' : '' }}">
                        <span class="sb-text">Reembolsos Viaje</span>
                    </a>
                    <a href="{{ route('admin.bitacora-gasolina') }}" class="sb-link sb-sublink {{ request()->is('admin/bitacora-gasolina*') ? 'active' : '' }}">
                        <span class="sb-text">Bitácora Gasolina</span>
                    </a>
                </div>
            </div>

            <div class="sb-hr"></div>
            <div class="sb-section">Operación</div>
            <a href="{{ route('admin.pedidos') }}" class="sb-link {{ request()->is('admin/pedidos*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
                <span class="sb-text">OC</span>
            </a>
            <a href="{{ route('admin.otif') }}" class="sb-link {{ request()->is('admin/otif*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <span class="sb-text">OTIF</span>
            </a>
            <div class="sb-submenu">
                <button type="button" class="sb-link sb-submenu-toggle {{ request()->is('admin/wiese-banco*') ? 'active' : '' }}" onclick="this.parentElement.classList.toggle('open')">
                    <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 20 7 4 7"/><line x1="6" y1="11" x2="6" y2="18"/><line x1="10" y1="11" x2="10" y2="18"/><line x1="14" y1="11" x2="14" y2="18"/><line x1="18" y1="11" x2="18" y2="18"/><line x1="3" y1="22" x2="21" y2="22"/></svg></div>
                    <span class="sb-text">WieseBanco</span>
                    <svg class="sb-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="sb-submenu-items">
                    @foreach(config('wiese_bancos') as $wbKey => $wbNombre)
                    <a href="{{ route('admin.wiese-banco', ['banco' => $wbKey]) }}" class="sb-link sb-sublink {{ request()->is('admin/wiese-banco/'.$wbKey) ? 'active' : '' }}">
                        <span class="sb-text">{{ $wbNombre }}</span>
                    </a>
                    @endforeach
                </div>
            </div>

            <div class="sb-hr"></div>
            <div class="sb-section">Proveedores</div>
            <a href="{{ route('admin.proveedores') }}" class="sb-link {{ request()->is('admin/proveedores') || request()->is('admin/proveedores/*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
                <span class="sb-text">Proveedores / Score</span>
            </a>
            <a href="{{ route('admin.solicitudes-alta') }}" class="sb-link {{ request()->is('admin/solicitudes-alta*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg></div>
                <span class="sb-text">Solicitudes de alta</span>
            </a>
            <a href="{{ route('admin.solicitudes-docs') }}" class="sb-link {{ request()->is('admin/solicitudes-docs*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                <span class="sb-text">Actualización docs</span>
            </a>
            <a href="{{ route('admin.expediente-fiscal') }}" class="sb-link {{ request()->is('admin/expediente-fiscal*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>
                <span class="sb-text">Expediente Fiscal</span>
            </a>
            <a href="{{ route('admin.opinion-positiva') }}" class="sb-link {{ request()->is('admin/opinion-positiva*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <span class="sb-text">Opinión Positiva</span>
            </a>
            <a href="{{ route('admin.reporte-proveedores') }}" class="sb-link {{ request()->is('admin/reporte-proveedores*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg></div>
                <span class="sb-text">Reportes</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Negocio</div>
            <a href="{{ route('admin.clientes') }}" class="sb-link {{ request()->is('admin/clientes*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <span class="sb-text">Clientes</span>
            </a>
            <a href="{{ route('admin.negocio') }}" class="sb-link {{ request()->is('admin/negocio*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <span class="sb-text">Negocio</span>
            </a>
            <a href="{{ route('admin.fiscal') }}" class="sb-link {{ request()->is('admin/fiscal*') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg></div>
                <span class="sb-text">Fiscal</span>
            </a>

            <div class="sb-hr"></div>
            <div class="sb-section">Cuenta</div>
            <a href="{{ route('admin.perfil') }}" class="sb-link {{ request()->routeIs('admin.perfil') ? 'active' : '' }}">
                <div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                <span class="sb-text">Mi Perfil</span>
            </a>
        </nav>
    </aside>

    <div class="main-content">
        @yield('hero')
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
<script>
function sbToggle(btn) {
    var s = document.getElementById('sidebar');
    s.classList.toggle('collapsed');
    var el = btn || document.getElementById('sbToggleBtn');
    if (el) el.setAttribute('aria-expanded', s.classList.contains('collapsed') ? 'false' : 'true');
}
(function () {
    var sidebar = document.getElementById('sidebar');
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

// Auto-abrir submenús si tienen un item activo
document.querySelectorAll('.sb-submenu').forEach(function(menu) {
    if (menu.querySelector('.sb-submenu-toggle.active') || menu.querySelector('.sb-sublink.active')) {
        menu.classList.add('open');
    }
});

(function () {
    document.addEventListener('click', function (e) {
        var dropdown = document.getElementById('notifDropdown');
        var wrapper = document.querySelector('.notif-wrapper');
        if (dropdown && wrapper && !wrapper.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });

    var wrap = document.querySelector('.notif-wrapper');
    if (!wrap) return;
    var leerBase = wrap.getAttribute('data-alertas-leer-url');
    var csrf = wrap.getAttribute('data-csrf');

    var itemsBox = document.getElementById('notifItems');
    if (itemsBox) {
        itemsBox.addEventListener('click', function (e) {
            var item = e.target.closest('.notif-item');
            if (!item) return;
            var id = item.getAttribute('data-alerta-id');
            var url = item.getAttribute('data-url') || @json(route('admin.pagos'));
            if (id && leerBase) {
                fetch(leerBase + '/' + id + '/leer', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }).finally(function () {
                    window.location = url;
                });
            } else {
                window.location = url;
            }
        });
    }
})();
</script>

{{-- Polling admin: notificaciones en tiempo real cada 1.5s --}}
<div id="admin-toast-container" style="position:fixed;top:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;max-width:380px"></div>
<style>
.admin-toast{display:flex;align-items:flex-start;gap:10px;padding:14px 18px;background:#fff;border:1px solid #e5e7eb;border-left:4px solid #6B3FA0;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.12);animation:aToastIn .3s ease;font-family:inherit}
.admin-toast.exit{animation:aToastOut .3s ease forwards}
@keyframes aToastIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
@keyframes aToastOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(40px)}}
</style>
<script>
(function(){
    var container = document.getElementById('admin-toast-container');
    var url = '{{ route("admin.pagos.alertas") }}';
    var lastCount = {{ $adminPagosSinLeer ?? 0 }};

    var MAX_TOASTS = 3; // máximo de toasts en pantalla a la vez

    function showToast(titulo, contenido) {
        // Limitar cantidad: quitar los más viejos si se pasa del máximo
        var actuales = container.querySelectorAll('.admin-toast');
        while (actuales.length >= MAX_TOASTS) {
            actuales[0].remove();
            actuales = container.querySelectorAll('.admin-toast');
        }
        var t = document.createElement('div');
        t.className = 'admin-toast';
        t.innerHTML = '<div style="flex:1"><p style="font-size:13px;font-weight:700;color:#1f2937;margin:0 0 2px">'+titulo+'</p><p style="font-size:12px;color:#6b7280;margin:0">'+contenido+'</p></div><button onclick="this.parentElement.classList.add(\'exit\');setTimeout(function(){this.parentElement.remove()}.bind(this),300)" style="background:none;border:none;color:#9ca3af;cursor:pointer;font-size:16px">&times;</button>';
        container.appendChild(t);
        setTimeout(function(){ if(t.parentElement){t.classList.add('exit');setTimeout(function(){t.remove()},300)} }, 5000);
    }

    var primerPoll = true;
    function poll() {
        fetch(url, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){return r.json()})
            .then(function(data){
                // En el primer poll NO mostramos toasts (evita la avalancha al cargar la página)
                if (!primerPoll && data.sin_leer > lastCount && data.items && data.items.length) {
                    // Solo mostrar las NUEVAS (las que se sumaron desde el último conteo), máximo 3
                    var nuevas = data.sin_leer - lastCount;
                    var aMostrar = data.items.slice(0, Math.min(nuevas, MAX_TOASTS));
                    aMostrar.forEach(function(item){
                        showToast(item.titulo, item.contenido);
                    });
                }
                // Actualizar badge siempre
                var badge = document.getElementById('notifBadge');
                if (badge) {
                    if (data.sin_leer > 0) { badge.textContent = data.sin_leer > 9 ? '9+' : data.sin_leer; badge.style.display = 'flex'; }
                    else { badge.style.display = 'none'; }
                }
                var label = document.getElementById('notifCountLabel');
                if (label) {
                    if (data.sin_leer > 0) { label.textContent = data.sin_leer + ' nuevas'; label.style.display = ''; }
                    else { label.style.display = 'none'; }
                }
                lastCount = data.sin_leer;
                primerPoll = false;
            })
            .catch(function(){});
    }
    setInterval(poll, 1500);
})();
</script>
</body>
</html>
