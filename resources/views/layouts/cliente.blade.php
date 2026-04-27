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
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%;margin:0;padding:0}
        body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'SF Pro Display',sans-serif;background:var(--gray-soft);min-height:100vh;display:flex;flex-direction:column;color:var(--gray-text);font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased}
        nav.top-nav{background:rgba(255,255,255,0.72);-webkit-backdrop-filter:saturate(180%) blur(20px);backdrop-filter:saturate(180%) blur(20px);padding:0 28px;height:52px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border-light);position:sticky;top:0;z-index:200}
        .nav-logo{font-size:16px;color:var(--purple);font-weight:700}.nav-logo span{display:block;font-size:9px;font-weight:600;letter-spacing:2.5px;color:var(--gray-muted);text-transform:uppercase;margin-top:1px}
        .nav-right{display:flex;align-items:center;gap:16px}.nav-user{font-size:13px;color:var(--gray-text);font-weight:500}
        .btn-logout{font-size:12px;color:var(--gray-muted);padding:5px 14px;border:1px solid var(--border-light);border-radius:var(--radius-pill);background:var(--gray-soft);cursor:pointer;font-family:inherit;font-weight:500;transition:var(--transition)}
        .btn-logout:hover{background:var(--purple-light);color:var(--purple);border-color:var(--purple-mid);transform:scale(1.02)}
        .btn-logout:active{transform:scale(0.97)}
        .hero-band{background:var(--white);padding:24px 32px;border-bottom:1px solid var(--border-light)}
        .hero-band h1{font-size:22px;color:var(--gray-text);font-weight:700;letter-spacing:-0.4px}.hero-band p{color:var(--gray-muted);font-size:14px;margin-top:4px}
        .wrapper{display:flex;flex:1}
        .sidebar{width:220px;min-width:220px;background:rgba(255,255,255,0.8);-webkit-backdrop-filter:blur(20px);backdrop-filter:blur(20px);border-right:1px solid var(--border-light);display:flex;flex-direction:column;transition:width .3s cubic-bezier(.4,0,.2,1),min-width .3s cubic-bezier(.4,0,.2,1);overflow:hidden}
        .sidebar.collapsed{width:56px;min-width:56px}
        .sb-toggle{height:40px;display:flex;align-items:center;justify-content:flex-end;padding:0 14px;border-bottom:1px solid var(--border-light);cursor:pointer;transition:var(--transition)}.sb-toggle:hover{background:var(--purple-subtle)}
        .sb-toggle svg{transition:transform .3s cubic-bezier(.4,0,.2,1);color:var(--gray-muted)}.sidebar.collapsed .sb-toggle{justify-content:center;padding:0}.sidebar.collapsed .sb-toggle svg{transform:rotate(180deg)}
        .sb-nav{flex:1;overflow-y:auto;padding:12px 0;display:flex;flex-direction:column}
        .sb-section{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:1.2px;padding:16px 20px 6px}.sidebar.collapsed .sb-section{display:none}
        .sb-hr{height:1px;background:var(--border-light);margin:8px 16px}
        .sb-link{display:flex;align-items:center;gap:12px;padding:8px 16px;margin:1px 8px;color:var(--gray-text);text-decoration:none;font-size:13px;font-weight:500;border-radius:10px;transition:var(--transition)}
        .sb-link:hover{background:var(--purple-subtle);color:var(--purple);transform:translateX(2px)}
        .sb-link.active{background:var(--purple-light);color:var(--purple);font-weight:600}
        .sb-icon{width:32px;height:32px;border-radius:10px;background:var(--gray-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:var(--transition)}
        .sb-link:hover .sb-icon,.sb-link.active .sb-icon{background:var(--purple);box-shadow:0 2px 8px rgba(107,63,160,0.25)}.sb-link:hover .sb-icon svg,.sb-link.active .sb-icon svg{stroke:white !important}
        .sb-text{flex-shrink:0}.sidebar.collapsed .sb-link{justify-content:center;padding:8px;margin:1px 4px}.sidebar.collapsed .sb-text{display:none}
        .main-content{flex:1;min-width:0;overflow-y:auto;padding:28px 32px 64px}
        footer{background:var(--white);border-top:1px solid var(--border-light);padding:14px 28px;display:flex;align-items:center;justify-content:space-between}
        footer p{font-size:11px;color:var(--gray-muted)}.footer-logo{font-size:14px;color:var(--purple);font-weight:600}
        @media(max-width:768px){.sidebar{display:none}.main-content{padding:20px 16px 48px}}
    </style>
    @stack('styles')
</head>
<body>
<nav class="top-nav">
    <div class="nav-logo" style="display:flex;align-items:center;gap:12px;">@include('partials.logo-salcom', ['size' => 'sm', 'color' => 'dark'])<span>Portal de Clientes</span></div>
    <div class="nav-right">
        <div style="position:relative;cursor:pointer" onclick="toggleNotif()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#86868b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span id="notifBadge" style="position:absolute;top:-6px;right:-8px;background:var(--red);color:#fff;font-size:10px;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center">3</span>
            <div id="notifDrop" style="display:none;position:absolute;right:0;top:32px;width:300px;background:#fff;border:1px solid var(--border-light);border-radius:var(--radius);box-shadow:var(--shadow-lg);z-index:500;overflow:hidden">
                <div style="padding:12px 16px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700;color:var(--gray-text)">Notificaciones</div>
                <div class="notif-item" onclick="markRead(this)" style="padding:10px 16px;border-bottom:1px solid var(--border-light);font-size:12px;cursor:pointer;background:var(--purple-light)"><div style="font-weight:600;color:var(--gray-text)">Pedido PED-2026-004 autorizado</div><div style="color:var(--gray-muted);margin-top:2px">Tu pedido fue aprobado por el área comercial</div></div>
                <div class="notif-item" onclick="markRead(this)" style="padding:10px 16px;border-bottom:1px solid var(--border-light);font-size:12px;cursor:pointer;background:var(--purple-light)"><div style="font-weight:600;color:var(--gray-text)">Factura CFDI-A-001236 por vencer</div><div style="color:var(--gray-muted);margin-top:2px">Vence en 5 días — $5,481.00</div></div>
                <div class="notif-item" onclick="markRead(this)" style="padding:10px 16px;font-size:12px;cursor:pointer;background:var(--purple-light)"><div style="font-weight:600;color:var(--gray-text)">Nuevo producto en catálogo</div><div style="color:var(--gray-muted);margin-top:2px">Refrigerante Industrial disponible</div></div>
            </div>
        </div>
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
            <a href="{{ route('clientes.ia') }}" class="sb-link {{ request()->routeIs('clientes.ia') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg></div><span class="sb-text">Dashboard IA</span></a>
            <a href="{{ route('clientes.forecast') }}" class="sb-link {{ request()->routeIs('clientes.forecast') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><span class="sb-text">Forecast</span></a>
            <a href="{{ route('clientes.catalogo') }}" class="sb-link {{ request()->routeIs('clientes.catalogo') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div><span class="sb-text">Catálogo</span></a>
            <a href="{{ route('clientes.pedidos') }}" class="sb-link {{ request()->routeIs('clientes.pedidos') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><span class="sb-text">Pedidos</span></a>
            <a href="{{ route('clientes.estado-cuenta') }}" class="sb-link {{ request()->routeIs('clientes.estado-cuenta') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><span class="sb-text">Estado de cuenta</span></a>
            <a href="{{ route('clientes.tracking') }}" class="sb-link {{ request()->routeIs('clientes.tracking') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><span class="sb-text">Tracking</span></a>
            <a href="{{ route('clientes.encuesta') }}" class="sb-link {{ request()->routeIs('clientes.encuesta') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div><span class="sb-text">Encuesta</span></a>
            <div class="sb-hr"></div>
            <div class="sb-section">Cuenta</div>
            <a href="{{ route('clientes.perfil') }}" class="sb-link {{ request()->routeIs('clientes.perfil') ? 'active' : '' }}"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div><span class="sb-text">Mi Perfil</span></a>
        </nav>
    </div>
    <div class="main-content @yield('main-class')">@yield('content')</div>
</div>
<footer><div class="footer-logo">Industrias Salcom</div><p>&copy; {{ date('Y') }} Industrias Salcom.</p></footer>
@stack('scripts')
<script>
function toggleNotif(){const d=document.getElementById('notifDrop');d.style.display=d.style.display==='none'?'block':'none'}
function markRead(el){el.style.background='#fff';let c=document.querySelectorAll('.notif-item[style*="purple-light"]').length;document.getElementById('notifBadge').textContent=c;if(c===0)document.getElementById('notifBadge').style.display='none'}
document.addEventListener('click',e=>{if(!e.target.closest('.nav-right [onclick]'))document.getElementById('notifDrop').style.display='none'})
</script>
</body>
</html>
