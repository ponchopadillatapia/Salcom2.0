<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Clientes — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--primary:#6B3FA0;--primary-light:#F3EEFA;--text:#1a1a2e;--text-secondary:#6b7280;--bg:#f9fafb;--border:#e5e7eb;--border-accent:#d4c5e8;--white:#fff;--green:#059669;--amber:#d97706}
        body{font-family:'Inter',-apple-system,sans-serif;background:var(--bg);min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased;color:var(--text);font-size:14px}
        .portal-nav{background:var(--white);padding:0 32px;height:52px;display:flex;align-items:center;gap:32px;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:200}
        .nav-brand{font-weight:700;font-size:16px;color:var(--primary)}.portal-menu{display:flex;align-items:center}.portal-menu a{font-size:13px;font-weight:600;color:var(--primary);text-decoration:none;padding:16px 14px;border-bottom:2px solid var(--primary)}
        .nav-right{display:flex;align-items:center;gap:16px;margin-left:auto}.nav-user{font-size:13px;color:var(--text);font-weight:500}
        .btn-logout{font-size:12px;color:var(--text-secondary);padding:5px 14px;border:1px solid var(--border);border-radius:6px;background:var(--white);cursor:pointer;font-family:inherit;font-weight:500;transition:all .15s}.btn-logout:hover{background:var(--bg);color:var(--text)}
        .sidebar-trigger{position:fixed;left:0;top:52px;width:20px;height:calc(100vh - 52px);z-index:300;display:flex;align-items:center}
        .sidebar-tab{width:20px;height:48px;background:#6B3FA0;border-radius:0 8px 8px 0;display:flex;align-items:center;justify-content:center;cursor:pointer;opacity:.7;transition:all .2s;box-shadow:2px 0 8px rgba(107,63,160,.15)}
        .sidebar-tab:hover{opacity:1;width:24px}
        .sidebar-tab svg{stroke:#fff}
        .sidebar-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.15);z-index:299;opacity:0;pointer-events:none;transition:opacity .2s}.sidebar-overlay.active{opacity:1;pointer-events:auto}
        .hover-sidebar{position:fixed;left:-240px;top:52px;width:240px;height:calc(100vh - 52px);background:var(--white);border-right:1px solid var(--border);z-index:301;transition:left .2s;overflow-y:auto;box-shadow:4px 0 20px rgba(0,0,0,0.08);display:flex;flex-direction:column}
        .hover-sidebar.open{left:0}
        .sb-section{font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:1.2px;padding:16px 20px 6px}
        .sb-link{display:flex;align-items:center;gap:12px;padding:9px 16px;margin:1px 8px;color:var(--text);text-decoration:none;font-size:13px;font-weight:500;border-radius:8px;transition:all .12s}.sb-link:hover{background:var(--primary-light);color:var(--primary)}
        .sb-link .sb-icon{width:32px;height:32px;border-radius:8px;background:var(--bg);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .12s}.sb-link:hover .sb-icon{background:var(--primary)}.sb-link:hover .sb-icon svg{stroke:white !important}
        .sb-hr{height:1px;background:var(--border);margin:8px 16px}
        .portal-body{max-width:1140px;margin:0 auto;padding:28px 32px 64px;width:100%}
        .greeting{font-size:22px;font-weight:700;color:var(--text);margin-bottom:4px}.greeting-sub{font-size:14px;color:var(--text-secondary);margin-bottom:28px}
        .top-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
        .card{background:var(--white);border:1px solid var(--border-accent);border-radius:10px;padding:20px}.card h4{font-size:13px;font-weight:600;color:var(--text);margin-bottom:12px}
        .stat-val{font-size:28px;font-weight:700;color:var(--text);line-height:1}.stat-label{font-size:12px;color:var(--text-secondary);margin-top:4px}
        .quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
        .quick-card{background:var(--white);border:1px solid var(--border-accent);border-radius:10px;padding:20px;text-decoration:none;display:flex;align-items:center;gap:14px;transition:all .15s}.quick-card:hover{border-color:var(--primary);box-shadow:0 2px 8px rgba(107,63,160,0.08)}
        .quick-icon{width:40px;height:40px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .quick-title{font-weight:600;color:var(--text);font-size:14px}.quick-desc{font-size:12px;color:var(--text-secondary)}
        .section-label{font-size:13px;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px;margin-bottom:14px}
        footer{background:var(--white);border-top:1px solid var(--border);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;margin-top:auto}footer p{font-size:11px;color:#9ca3af}.footer-brand{font-size:14px;color:var(--primary);font-weight:600}
        @media(max-width:900px){.top-grid{grid-template-columns:1fr 1fr}.quick-grid{grid-template-columns:1fr}.portal-menu{display:none}}
    </style>
</head>
<body>
<nav class="portal-nav">
    <div class="nav-brand">Industrias Salcom</div>
    <div class="portal-menu"><a href="{{ route('clientes.portal') }}">Inicio</a></div>
    <div class="nav-right"><span class="nav-user">{{ session('cliente_nombre', 'Cliente') }}</span><form method="POST" action="{{ route('clientes.logout') }}" style="display:inline">@csrf<button type="submit" class="btn-logout">Cerrar sesión</button></form></div>
</nav>
<div class="sidebar-trigger" id="sbTrigger">
    <div class="sidebar-tab"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg></div>
</div>
<div class="sidebar-overlay" id="sbOverlay"></div>
<div class="hover-sidebar" id="hoverSidebar">
    <div class="sb-section">Principal</div>
    <a href="{{ route('clientes.portal') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>Inicio</a>
    <a href="{{ route('clientes.dashboard') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></div>Dashboard</a>
    <div class="sb-hr"></div>
    <div class="sb-section">Operaciones</div>
    <a href="{{ route('clientes.ia') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg></div>Dashboard IA</a>
    <a href="{{ route('clientes.forecast') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>Forecast</a>
    <a href="{{ route('clientes.catalogo') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>Catálogo</a>
    <a href="{{ route('clientes.pedidos') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>Pedidos</a>
</div>
<div class="portal-body">
    <div class="greeting">Hola, {{ session('cliente_nombre', 'Cliente') }}</div>
    <div class="greeting-sub">Bienvenido al Portal de Clientes de Industrias Salcom</div>
    <div class="top-grid">
        <div class="card"><h4>Pedidos activos</h4><div class="stat-val">3</div><div class="stat-label">En proceso</div></div>
        <div class="card"><h4>Último pedido</h4><div class="stat-val">PED-005</div><div class="stat-label">09/04/2026</div></div>
        <div class="card"><h4>Saldo pendiente</h4><div class="stat-val">$0.00</div><div class="stat-label">Al corriente</div></div>
        <div class="card"><h4>Tipo de cliente</h4><div class="stat-val">{{ ucfirst(session('cliente_tipo', '—')) }}</div><div class="stat-label">Clasificación</div></div>
    </div>
    <div class="section-label">Acceso rápido</div>
    <div class="quick-grid" style="margin-bottom:24px">
        <a href="{{ route('clientes.ia') }}" class="quick-card"><div class="quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg></div><div><div class="quick-title">Dashboard IA</div><div class="quick-desc">Análisis con Claude</div></div></a>
        <a href="{{ route('clientes.forecast') }}" class="quick-card"><div class="quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><div><div class="quick-title">Forecast</div><div class="quick-desc">Tendencias de compras</div></div></a>
        <a href="{{ route('clientes.catalogo') }}" class="quick-card"><div class="quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div><div><div class="quick-title">Catálogo</div><div class="quick-desc">Productos y precios</div></div></a>
        <a href="{{ route('clientes.pedidos') }}" class="quick-card"><div class="quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="quick-title">Mis Pedidos</div><div class="quick-desc">Estatus y seguimiento</div></div></a>
        <a href="{{ route('clientes.estado-cuenta') }}" class="quick-card"><div class="quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="quick-title">Estado de cuenta</div><div class="quick-desc">Facturas y saldos</div></div></a>
        <a href="{{ route('clientes.tracking') }}" class="quick-card"><div class="quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="quick-title">Tracking</div><div class="quick-desc">Seguimiento de envíos</div></div></a>
    </div>

    {{-- FORECAST PREVIEW --}}
    <div class="section-label">📊 Forecast — Tendencias de tus compras</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
        <div class="card">
            <h4 style="color:var(--green)">📈 Al alza</h4>
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px;border-bottom:1px solid var(--border)"><span style="flex:1;font-weight:600">Resina epóxica</span><span style="font-size:12px;font-weight:700;color:var(--green)">↑ +12%</span></div>
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px;border-bottom:1px solid var(--border)"><span style="flex:1;font-weight:600">Solvente técnico</span><span style="font-size:12px;font-weight:700;color:var(--green)">↑ +8%</span></div>
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px"><span style="flex:1;font-weight:600">Pigmento base agua</span><span style="font-size:12px;font-weight:700;color:var(--green)">↑ +5%</span></div>
            <a href="{{ route('clientes.forecast') }}" style="display:block;text-align:right;font-size:12px;color:var(--primary);font-weight:600;text-decoration:none;margin-top:8px">Ver todo →</a>
        </div>
        <div class="card">
            <h4 style="color:#dc2626">📉 A la baja</h4>
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px;border-bottom:1px solid var(--border)"><span style="flex:1;font-weight:600">Aditivo antioxidante</span><span style="font-size:12px;font-weight:700;color:#dc2626">↓ -15%</span></div>
            <div style="display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px"><span style="flex:1;font-weight:600">Catalizador rápido</span><span style="font-size:12px;font-weight:700;color:#dc2626">↓ -5%</span></div>
            <a href="{{ route('clientes.forecast') }}" style="display:block;text-align:right;font-size:12px;color:var(--primary);font-weight:600;text-decoration:none;margin-top:8px">Ver todo →</a>
        </div>
    </div>
    <div class="section-label">Actividad reciente</div>
    <div style="background:var(--white);border:1px solid var(--border-accent);border-radius:10px;padding:16px 20px">
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px"><div style="width:7px;height:7px;border-radius:50%;background:#059669;flex-shrink:0"></div><span style="flex:1">PED-2026-004 autorizado por área comercial</span><span style="font-size:11px;color:#9ca3af">07/04</span></div>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px"><div style="width:7px;height:7px;border-radius:50%;background:#d97706;flex-shrink:0"></div><span style="flex:1">Factura CFDI-A-001236 pendiente de pago</span><span style="font-size:11px;color:#9ca3af">05/04</span></div>
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:13px"><div style="width:7px;height:7px;border-radius:50%;background:#2563eb;flex-shrink:0"></div><span style="flex:1">PED-2026-002 salió de planta</span><span style="font-size:11px;color:#9ca3af">06/04</span></div>
    </div>
</div>
<footer><div class="footer-brand">Industrias Salcom</div><p>&copy; {{ date('Y') }} Industrias Salcom.</p></footer>
<script>
const trigger=document.getElementById('sbTrigger'),sidebar=document.getElementById('hoverSidebar'),overlay=document.getElementById('sbOverlay');let ct=null;
function oSB(){clearTimeout(ct);sidebar.classList.add('open');overlay.classList.add('active')}
function cSB(){ct=setTimeout(()=>{sidebar.classList.remove('open');overlay.classList.remove('active')},400)}
trigger.addEventListener('mouseenter',oSB);sidebar.addEventListener('mouseenter',()=>clearTimeout(ct));sidebar.addEventListener('mouseleave',cSB);overlay.addEventListener('click',()=>{clearTimeout(ct);sidebar.classList.remove('open');overlay.classList.remove('active')});
</script>
</body>
</html>
