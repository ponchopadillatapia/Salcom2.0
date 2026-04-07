<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar OC — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }

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

        body { font-family: 'Nunito', sans-serif; background: var(--gray-soft); min-height: 100vh; display: flex; flex-direction: column; }

        /* NAVBAR */
        nav { background: var(--white); padding: 0 32px; height: 57px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 200; flex-shrink: 0; }
        .nav-logo { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--purple); font-weight: 600; }
        .nav-logo span { display: block; font-family: 'Nunito', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 3px; color: var(--purple-mid); text-transform: uppercase; margin-top: -4px; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .nav-user { font-size: 13px; color: var(--gray-text); font-weight: 500; }
        .nav-user span { color: var(--purple); font-weight: 600; }
        .btn-logout { font-size: 13px; color: var(--gray-text); padding: 6px 14px; border: 0.5px solid var(--border); border-radius: 8px; background: none; cursor: pointer; font-family: inherit; transition: all .15s; }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        /* HERO */
        .hero-band { background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 60%, var(--purple-mid) 100%); padding: 24px 32px; position: relative; overflow: hidden; flex-shrink: 0; }
        .hero-band::before { content: ''; position: absolute; width: 420px; height: 420px; border-radius: 50%; background: rgba(255,255,255,0.06); top: -180px; right: -80px; }
        .hero-band h1 { font-family: 'Playfair Display', serif; font-size: 24px; color: #fff; font-weight: 600; position: relative; z-index: 1; }
        .hero-band p { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 4px; position: relative; z-index: 1; }

        /* WRAPPER */
        .wrapper { display: flex; flex: 1; }

        /* SIDEBAR */
        .sidebar { width: 220px; min-width: 220px; background: var(--white); border-right: 1px solid var(--border); flex-shrink: 0; display: flex; flex-direction: column; transition: width .25s, min-width .25s; overflow: hidden; }
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

        /* MAIN */
        .main-content { flex: 1; min-width: 0; overflow-y: auto; padding: 28px 32px 64px; }

        /* BUSCADOR */
        .search-bar { display: flex; gap: 12px; margin-bottom: 28px; }
        .search-input { flex: 1; border: 1.5px solid var(--border); border-radius: 10px; padding: 11px 16px; font-size: 14px; font-family: 'Nunito', sans-serif; color: var(--gray-text); background: var(--white); outline: none; transition: border-color .2s, box-shadow .2s; }
        .search-input::placeholder { color: #BDB8CC; }
        .search-input:focus { border-color: var(--purple-mid); box-shadow: 0 0 0 3px rgba(156,109,208,0.12); }
        .btn-search { padding: 11px 24px; background: var(--purple); color: var(--white); border: none; border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background .2s; }
        .btn-search:hover { background: var(--purple-dark); }

        /* MÉTRICAS */
        .metrics-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .metric-card { background: var(--white); border-radius: 12px; padding: 18px 20px; border: 0.5px solid var(--border); position: relative; overflow: hidden; }
        .metric-card .accent { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 12px 0 0 12px; }
        .metric-label { font-size: 12px; color: var(--gray-text); font-weight: 500; margin-bottom: 6px; padding-left: 8px; }
        .metric-value { font-size: 26px; font-weight: 600; color: var(--purple-dark); padding-left: 8px; line-height: 1; }
        .metric-sub { font-size: 11px; color: #AAA; padding-left: 8px; margin-top: 4px; }

        /* CARD */
        .card { background: var(--white); border-radius: 14px; border: 0.5px solid var(--border); overflow: hidden; }
        .card-head { padding: 14px 20px; border-bottom: 0.5px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .card-head h3 { font-size: 14px; font-weight: 600; color: var(--purple-dark); }
        .card-head-right { display: flex; align-items: center; gap: 10px; }
        .badge-api { font-size: 11px; color: var(--amber); font-weight: 600; background: var(--amber-bg); padding: 3px 10px; border-radius: 999px; }
        .btn-excel { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: #16a34a; color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; transition: background .2s; }
        .btn-excel:hover { background: #15803d; }

        /* TABLA */
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th { font-size: 11px; font-weight: 700; color: #AAA; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; text-align: left; background: var(--gray-soft); border-bottom: 0.5px solid var(--border); }
        .tabla td { padding: 12px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 0.5px solid var(--border); }
        .tabla tr:last-child td { border-bottom: none; }
        .tabla tr:hover td { background: var(--gray-soft); cursor: pointer; }

        .badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
        .badge-green  { background: var(--green-bg);  color: var(--green); }
        .badge-amber  { background: var(--amber-bg);  color: var(--amber); }
        .badge-blue   { background: var(--blue-bg);   color: var(--blue); }
        .badge-purple { background: var(--purple-light); color: var(--purple); }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 300; align-items: center; justify-content: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: var(--white); border-radius: 20px; padding: 32px; width: 100%; max-width: 720px; max-height: 85vh; overflow-y: auto; animation: fadeUp .3s ease both; }
        @keyframes fadeUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }
        .modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 0.5px solid var(--border); }
        .modal-head h3 { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--purple-dark); }
        .btn-close { width: 32px; height: 32px; border-radius: 50%; border: none; background: var(--gray-soft); cursor: pointer; font-size: 16px; color: var(--gray-text); display: flex; align-items: center; justify-content: center; }
        .btn-close:hover { background: var(--purple-light); color: var(--purple); }
        .modal-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .info-label { font-size: 11px; font-weight: 700; color: #AAA; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .info-value { font-size: 14px; color: var(--gray-text); font-weight: 500; }
        .info-item.full { grid-column: 1 / -1; }
        .section-label { font-size: 12px; font-weight: 700; color: #AAA; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; margin-top: 4px; }

        footer { background: var(--white); border-top: 1px solid var(--border); padding: 18px 32px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
        footer p { font-size: 12px; color: #AAA; }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--purple); }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .metrics-row { grid-template-columns: 1fr 1fr; }
            .main-content { padding: 20px 16px 48px; }
        }
    </style>
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

{{-- HERO --}}
<div class="hero-band">
    <h1>Consultar Órdenes de Compra</h1>
    <p>Revisa tus órdenes de compra, cantidades, precios y condiciones</p>
</div>

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
            <a href="{{ route('proveedores.dashboard') }}" class="sb-link">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
                <span class="sb-text">Facturas</span>
            </a>
            <a href="{{ route('proveedores.dashboard') }}" class="sb-link">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                <span class="sb-text">Pagos</span>
            </a>
            <div class="sb-hr"></div>
            <div class="sb-section">Mi empresa</div>
            <a href="{{ route('proveedores.onboarding') }}" class="sb-link">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
                <span class="sb-text">Onboarding</span>
            </a>
            <a href="{{ route('proveedores.business') }}" class="sb-link">
                <div class="sb-icon">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
            <span class="sb-text">Business</span>
            </a>
        </nav>
    </div>

    {{-- CONTENIDO --}}
    <div class="main-content">

        {{-- BUSCADOR --}}
        <div class="search-bar">
            <input type="text" class="search-input" id="buscarFolio" placeholder="Buscar por número de folio...">
            <button class="btn-search" onclick="buscarOC()">Buscar</button>
        </div>

        {{-- MÉTRICAS --}}
        <div class="metrics-row">
            <div class="metric-card">
                <div class="accent" style="background: var(--purple)"></div>
                <div class="metric-label">OC Abiertas</div>
                <div class="metric-value">3</div>
                <div class="metric-sub">Datos de prueba</div>
            </div>
            <div class="metric-card">
                <div class="accent" style="background: var(--green)"></div>
                <div class="metric-label">OC Completadas</div>
                <div class="metric-value">8</div>
                <div class="metric-sub">Datos de prueba</div>
            </div>
            <div class="metric-card">
                <div class="accent" style="background: var(--amber)"></div>
                <div class="metric-label">OC En proceso</div>
                <div class="metric-value">2</div>
                <div class="metric-sub">Datos de prueba</div>
            </div>
            <div class="metric-card">
                <div class="accent" style="background: var(--blue)"></div>
                <div class="metric-label">Monto total</div>
                <div class="metric-value">$48k</div>
                <div class="metric-sub">Datos de prueba</div>
            </div>
        </div>

        {{-- TABLA --}}
        <div class="card">
            <div class="card-head">
                <h3>Órdenes de Compra</h3>
                <div class="card-head-right">
                    <span class="badge-api">⚠ Datos de prueba — Pendiente de API</span>
                    <button class="btn-excel" onclick="exportarExcel('tablaOC','ordenes-compra')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Exportar Excel
                    </button>
                </div>
            </div>
            <table class="tabla" id="tablaOC">
                <thead>
                    <tr>
                        <th>Folio</th><th>Fecha</th><th>Referencia</th><th>Importe</th><th>Vencimiento</th><th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr onclick="verDetalle(1)"><td><strong>#10045</strong></td><td>01/03/2026</td><td>REF-2026-001</td><td>$12,500.00</td><td>31/03/2026</td><td><span class="badge badge-amber">En proceso</span></td></tr>
                    <tr onclick="verDetalle(2)"><td><strong>#10046</strong></td><td>05/03/2026</td><td>REF-2026-002</td><td>$8,200.00</td><td>05/04/2026</td><td><span class="badge badge-blue">Abierta</span></td></tr>
                    <tr onclick="verDetalle(3)"><td><strong>#10047</strong></td><td>10/03/2026</td><td>REF-2026-003</td><td>$27,300.00</td><td>10/04/2026</td><td><span class="badge badge-green">Completada</span></td></tr>
                    <tr onclick="verDetalle(4)"><td><strong>#10048</strong></td><td>15/03/2026</td><td>REF-2026-004</td><td>$5,800.00</td><td>15/04/2026</td><td><span class="badge badge-amber">En proceso</span></td></tr>
                    <tr onclick="verDetalle(5)"><td><strong>#10049</strong></td><td>20/03/2026</td><td>REF-2026-005</td><td>$15,100.00</td><td>20/04/2026</td><td><span class="badge badge-blue">Abierta</span></td></tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

{{-- MODAL --}}
<div class="modal-overlay" id="modalOverlay" onclick="cerrarModal(event)">
    <div class="modal">
        <div class="modal-head">
            <h3 id="modalTitulo">Detalle OC</h3>
            <button class="btn-close" onclick="cerrarModalBtn()">✕</button>
        </div>
        <div class="modal-info-grid">
            <div class="info-item"><div class="info-label">Folio</div><div class="info-value" id="detFolio">—</div></div>
            <div class="info-item"><div class="info-label">Fecha</div><div class="info-value" id="detFecha">—</div></div>
            <div class="info-item"><div class="info-label">Código Proveedor</div><div class="info-value" id="detCodigoCteProv">—</div></div>
            <div class="info-item"><div class="info-label">Referencia</div><div class="info-value" id="detReferencia">—</div></div>
            <div class="info-item"><div class="info-label">Importe total</div><div class="info-value" id="detImporte">—</div></div>
            <div class="info-item"><div class="info-label">Fecha vencimiento</div><div class="info-value" id="detVencimiento">—</div></div>
            <div class="info-item"><div class="info-label">Estatus</div><div class="info-value" id="detEstatus">—</div></div>
            <div class="info-item full"><div class="info-label">Observaciones</div><div class="info-value" id="detObservacion">—</div></div>
        </div>
        <div class="section-label">Productos</div>
        <table class="tabla">
            <thead><tr><th>Producto</th><th>Unidades</th><th>Precio</th><th>IVA</th><th>Total</th></tr></thead>
            <tbody id="detMovimientos"></tbody>
        </table>
        <p style="font-size:11px; color:#AAA; margin-top:16px; text-align:center;">⚠ Datos de prueba — se reemplazarán con la API de Alan</p>
    </div>
</div>

{{-- FOOTER --}}
<footer>
    <div class="footer-logo">Industrias Salcom</div>
    <p>© {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

<script>
function sbToggle() {
    document.getElementById('appSidebar').classList.toggle('collapsed');
}

function exportarExcel(tablaId, nombre) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;
    let csv = '';
    tabla.querySelectorAll('tr').forEach(fila => {
        const data = Array.from(fila.querySelectorAll('th,td')).map(c => '"' + c.textContent.trim().replace(/"/g,'""') + '"');
        csv += data.join(',') + '\n';
    });
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = nombre + '-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

const ocs = {
    1: { folio: '#10045', fecha: '01/03/2026', codigoCteProv: '102003240', referencia: 'REF-2026-001', importe: '$12,500.00', vencimiento: '31/03/2026', estatus: 'En proceso', observacion: 'Entrega en almacén central', movimientos: [ { producto: 'PROD-001', unidades: 10, precio: '$800.00', iva: '$128.00', total: '$8,000.00' }, { producto: 'PROD-002', unidades: 5, precio: '$900.00', iva: '$72.00', total: '$4,500.00' } ] },
    2: { folio: '#10046', fecha: '05/03/2026', codigoCteProv: '102003240', referencia: 'REF-2026-002', importe: '$8,200.00', vencimiento: '05/04/2026', estatus: 'Abierta', observacion: '—', movimientos: [ { producto: 'PROD-003', unidades: 20, precio: '$410.00', iva: '$65.60', total: '$8,200.00' } ] },
    3: { folio: '#10047', fecha: '10/03/2026', codigoCteProv: '102003240', referencia: 'REF-2026-003', importe: '$27,300.00', vencimiento: '10/04/2026', estatus: 'Completada', observacion: 'Urgente, prioridad alta', movimientos: [ { producto: 'PROD-001', unidades: 15, precio: '$800.00', iva: '$192.00', total: '$12,000.00' }, { producto: 'PROD-004', unidades: 30, precio: '$511.00', iva: '$244.80', total: '$15,300.00' } ] },
    4: { folio: '#10048', fecha: '15/03/2026', codigoCteProv: '102003240', referencia: 'REF-2026-004', importe: '$5,800.00', vencimiento: '15/04/2026', estatus: 'En proceso', observacion: '—', movimientos: [ { producto: 'PROD-005', unidades: 8, precio: '$725.00', iva: '$116.00', total: '$5,800.00' } ] },
    5: { folio: '#10049', fecha: '20/03/2026', codigoCteProv: '102003240', referencia: 'REF-2026-005', importe: '$15,100.00', vencimiento: '20/04/2026', estatus: 'Abierta', observacion: 'Verificar existencias antes de confirmar', movimientos: [ { producto: 'PROD-002', unidades: 10, precio: '$900.00', iva: '$144.00', total: '$9,000.00' }, { producto: 'PROD-003', unidades: 15, precio: '$410.00', iva: '$98.40', total: '$6,100.00' } ] }
};

function verDetalle(id) {
    const oc = ocs[id];
    document.getElementById('modalTitulo').textContent = 'Detalle OC ' + oc.folio;
    document.getElementById('detFolio').textContent = oc.folio;
    document.getElementById('detFecha').textContent = oc.fecha;
    document.getElementById('detCodigoCteProv').textContent = oc.codigoCteProv;
    document.getElementById('detReferencia').textContent = oc.referencia;
    document.getElementById('detImporte').textContent = oc.importe;
    document.getElementById('detVencimiento').textContent = oc.vencimiento;
    document.getElementById('detEstatus').textContent = oc.estatus;
    document.getElementById('detObservacion').textContent = oc.observacion;
    let movHtml = '';
    oc.movimientos.forEach(m => {
        movHtml += `<tr><td>${m.producto}</td><td>${m.unidades}</td><td>${m.precio}</td><td>${m.iva}</td><td>${m.total}</td></tr>`;
    });
    document.getElementById('detMovimientos').innerHTML = movHtml;
    document.getElementById('modalOverlay').classList.add('active');
}

function cerrarModal(e) {
    if (e.target === document.getElementById('modalOverlay')) document.getElementById('modalOverlay').classList.remove('active');
}
function cerrarModalBtn() { document.getElementById('modalOverlay').classList.remove('active'); }

function buscarOC() {
    const folio = document.getElementById('buscarFolio').value.toLowerCase();
    document.querySelectorAll('#tablaOC tbody tr').forEach(fila => {
        fila.style.display = fila.querySelector('td').textContent.toLowerCase().includes(folio) ? '' : 'none';
    });
}
document.getElementById('buscarFolio').addEventListener('keyup', e => { if (e.key === 'Enter') buscarOC(); });
</script>
</body>
</html>