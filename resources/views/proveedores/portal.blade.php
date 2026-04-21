<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Proveedores — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #6B3FA0; --primary-dark: #4A2070; --primary-light: #F3EEFA;
            --primary-mid: #9C6DD0; --text: #1a1a2e; --text-secondary: #6b7280;
            --bg: #f9fafb; --border: #e5e7eb; --border-accent: #d4c5e8;
            --white: #fff; --green: #059669; --green-bg: #ecfdf5;
            --amber: #d97706; --amber-bg: #fffbeb; --blue: #2563eb;
        }
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--bg); min-height: 100vh; display: flex; flex-direction: column; -webkit-font-smoothing: antialiased; color: var(--text); font-size: 14px; }

        /* ── NAVBAR (white, professional like Retail Link) ── */
        .portal-nav { background: var(--white); padding: 0 32px; height: 52px; display: flex; align-items: center; gap: 32px; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 200; }
        .nav-brand { font-weight: 700; font-size: 16px; color: var(--primary); white-space: nowrap; }
        .portal-menu { display: flex; align-items: center; gap: 0; flex: 1; }
        .portal-menu a { font-size: 13px; font-weight: 500; color: var(--text-secondary); text-decoration: none; padding: 16px 14px; border-bottom: 2px solid transparent; transition: all .15s; }
        .portal-menu a:hover { color: var(--text); border-bottom-color: var(--border); }
        .portal-menu a.active { color: var(--primary); font-weight: 600; border-bottom-color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 16px; margin-left: auto; }
        .nav-user { font-size: 13px; color: var(--text); font-weight: 500; }
        .btn-logout { font-size: 12px; color: var(--text-secondary); padding: 5px 14px; border: 1px solid var(--border); border-radius: 6px; background: var(--white); cursor: pointer; font-family: inherit; font-weight: 500; transition: all .15s; }
        .btn-logout:hover { background: var(--bg); color: var(--text); }

        /* ── CONTENT ── */
        .portal-body { max-width: 1140px; margin: 0 auto; padding: 28px 32px 64px; width: 100%; }
        .greeting { font-size: 22px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
        .greeting-sub { font-size: 14px; color: var(--text-secondary); margin-bottom: 28px; }

        /* ── CARDS GRID (top row) ── */
        .top-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .card { background: var(--white); border: 1px solid var(--border-accent); border-radius: 10px; padding: 20px; }
        .card h4 { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 12px; }

        /* Stat cards */
        .stat-val { font-size: 28px; font-weight: 700; color: var(--text); line-height: 1; }
        .stat-label { font-size: 12px; color: var(--text-secondary); margin-top: 4px; }

        /* ── MIDDLE ROW (calendar + activity + onboarding) ── */
        .mid-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px; }

        /* Calendar */
        .cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .cal-header h4 { font-size: 13px; font-weight: 600; color: var(--text); }
        .cal-nav { display: flex; gap: 4px; }
        .cal-nav button { width: 28px; height: 28px; border: 1px solid var(--border); border-radius: 6px; background: var(--white); cursor: pointer; font-size: 14px; color: var(--text-secondary); display: flex; align-items: center; justify-content: center; transition: all .1s; }
        .cal-nav button:hover { background: var(--primary-light); color: var(--primary); border-color: var(--border-accent); }
        .cal-month { font-size: 13px; font-weight: 600; color: var(--text); }
        .cal-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .cal-table th { font-weight: 600; color: var(--text-secondary); padding: 6px 2px; text-align: center; font-size: 11px; text-transform: uppercase; }
        .cal-table td { padding: 5px 2px; text-align: center; color: var(--text-secondary); cursor: default; }
        .cal-table td.today { background: var(--primary); color: var(--white); border-radius: 6px; font-weight: 600; }
        .cal-table td.has-data { cursor: pointer; font-weight: 600; color: var(--text); }
        .cal-table td.has-data:hover { background: var(--primary-light); border-radius: 6px; }
        .cal-week { font-size: 11px; color: var(--primary); font-weight: 700; }

        /* Activity & Onboarding lists */
        .list-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
        .list-item:last-child { border-bottom: none; }
        .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .dot-g { background: var(--green); } .dot-a { background: var(--amber); } .dot-x { background: #d1d5db; }
        .list-text { flex: 1; color: var(--text); }
        .list-time { font-size: 11px; color: #9ca3af; }
        .card-link { font-size: 12px; color: var(--primary); font-weight: 600; text-decoration: none; }
        .card-link:hover { text-decoration: underline; }

        /* ── WEEK DETAIL PANEL ── */
        .week-panel { display: none; background: var(--white); border: 1px solid var(--border-accent); border-radius: 10px; padding: 20px; margin-bottom: 24px; }
        .week-panel.active { display: block; }
        .week-panel h4 { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 14px; }
        .week-panel .close-btn { float: right; background: none; border: none; font-size: 18px; cursor: pointer; color: var(--text-secondary); }
        .week-section { margin-bottom: 14px; }
        .week-section h5 { font-size: 12px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .week-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
        .week-row:last-child { border-bottom: none; }

        /* ── FOOTER ── */
        footer { background: var(--white); border-top: 1px solid var(--border); padding: 14px 32px; display: flex; align-items: center; justify-content: space-between; margin-top: auto; }
        footer p { font-size: 11px; color: #9ca3af; }
        .footer-brand { font-size: 14px; color: var(--primary); font-weight: 600; }

        @media (max-width: 900px) { .top-grid { grid-template-columns: 1fr 1fr; } .mid-grid { grid-template-columns: 1fr 1fr; } .portal-menu { display: none; } }
        @media (max-width: 600px) { .mid-grid { grid-template-columns: 1fr; } }

        /* Score donut */
        .score-card{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:20px}
        .score-donut{position:relative;width:120px;height:120px}
        .score-donut canvas{position:absolute;top:0;left:0}
        .score-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
        .score-num{font-size:28px;font-weight:700;line-height:1}
        .score-lbl{font-size:10px;color:#9ca3af;margin-top:2px}
        .score-legend{width:100%;font-size:11px;color:var(--text-secondary)}
        .score-legend-row{display:flex;align-items:center;gap:6px;margin-bottom:4px}
        .score-legend-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
        .score-legend-val{margin-left:auto;font-weight:700}

        /* Forecast */
        .forecast-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
        .forecast-card{background:var(--white);border:1px solid var(--border-accent);border-radius:10px;padding:20px}
        .forecast-card h4{font-size:13px;font-weight:600;color:var(--text);margin-bottom:14px}
        .forecast-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
        .forecast-row:last-child{border-bottom:none}
        .forecast-name{flex:1;font-weight:600;color:var(--text)}
        .forecast-bar{width:60px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden}
        .forecast-fill{height:100%;border-radius:3px}
        .forecast-trend{font-size:12px;font-weight:700;width:50px;text-align:right}
        .trend-up{color:var(--green)}.trend-down{color:#dc2626}.trend-flat{color:#9ca3af}

        /* ── HOVER SIDEBAR ── */
        .sidebar-trigger { position: fixed; left: 0; top: 52px; width: 20px; height: calc(100vh - 52px); z-index: 300; display:flex; align-items:center; }
        .sidebar-tab { width:20px; height:48px; background:#6B3FA0; border-radius:0 8px 8px 0; display:flex; align-items:center; justify-content:center; cursor:pointer; opacity:.7; transition:all .2s; box-shadow:2px 0 8px rgba(107,63,160,.15); }
        .sidebar-tab:hover { opacity:1; width:24px; }
        .sidebar-tab svg { stroke:#fff; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.15); z-index: 299; opacity: 0; pointer-events: none; transition: opacity .2s; }
        .sidebar-overlay.active { opacity: 1; pointer-events: auto; }
        .hover-sidebar {
            position: fixed; left: -240px; top: 52px; width: 240px; height: calc(100vh - 52px);
            background: var(--white); border-right: 1px solid var(--border); z-index: 301;
            transition: left .2s ease; overflow-y: auto; box-shadow: 4px 0 20px rgba(0,0,0,0.08);
            display: flex; flex-direction: column;
        }
        .hover-sidebar.open { left: 0; }
        .sb-section { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 1.2px; padding: 16px 20px 6px; }
        .sb-link { display: flex; align-items: center; gap: 12px; padding: 9px 16px; margin: 1px 8px; color: var(--text); text-decoration: none; font-size: 13px; font-weight: 500; border-radius: 8px; transition: all .12s; }
        .sb-link:hover { background: var(--primary-light); color: var(--primary); }
        .sb-link .sb-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--bg); display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .12s; }
        .sb-link:hover .sb-icon { background: var(--primary); }
        .sb-link:hover .sb-icon svg { stroke: white !important; }
        .sb-hr { height: 1px; background: var(--border); margin: 8px 16px; }
        .sb-collapse-btn { display: none; }

        .portal-body { transition: none; }
    </style>
</head>
<body>

<nav class="portal-nav">
    <div class="nav-brand">Industrias Salcom</div>
    <div class="portal-menu">
        <a href="{{ route('proveedores.portal') }}" class="active">Inicio</a>
    </div>
    <div class="nav-right">
        <span class="nav-user">{{ session('proveedor_nombre', 'Proveedor') }}</span>
        <form method="POST" action="{{ route('proveedores.logout') }}" style="display:inline;">@csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

{{-- HOVER SIDEBAR --}}
<div class="sidebar-trigger" id="sbTrigger">
    <div class="sidebar-tab"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg></div>
</div>
<div class="sidebar-overlay" id="sbOverlay"></div>
<div class="hover-sidebar" id="hoverSidebar">
    <div class="sb-section">Principal</div>
    <a href="{{ route('proveedores.portal') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>Inicio</a>
    <a href="{{ route('proveedores.dashboard') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg></div>Dashboard</a>
    <div class="sb-hr"></div>
    <div class="sb-section">Operaciones</div>
    <a href="{{ route('proveedores.ia') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg></div>Dashboard IA</a>
    <a href="{{ route('proveedores.forecast') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>Forecast</a>
    <a href="{{ route('proveedores.oc') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>Consultar OC</a>
    <a href="{{ route('proveedores.alta-producto') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>Alta de producto</a>
    <a href="{{ route('muestras.crear') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>Envío de muestras</a>
    <a href="{{ route('proveedores.payment-history') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>Historial de pagos</a>
    <div class="sb-hr"></div>
    <div class="sb-section">Mi empresa</div>
    <a href="{{ route('proveedores.onboarding') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>Onboarding</a>
    <a href="{{ route('proveedores.business') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>Business</a>
    <div class="sb-hr"></div>
    <div class="sb-section">Cuenta</div>
    <a href="{{ route('proveedores.perfil') }}" class="sb-link"><div class="sb-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>Mi Perfil</a>
</div>

<div class="portal-body">
    <div class="greeting">Hola, {{ session('proveedor_nombre', 'Proveedor') }}</div>
    <div class="greeting-sub">Bienvenido al Portal de Proveedores de Industrias Salcom</div>

    {{-- TOP: Stats --}}
    <div class="top-grid">
        <div class="card"><h4>📊 Mi Score como Proveedor</h4><div class="stat-val" style="color:var(--green)">0%</div><div class="stat-label">50% entrega a tiempo + 50% puntualidad</div></div>
        <div class="card"><h4>OC Abiertas</h4><div class="stat-val">3</div><div class="stat-label">Datos de prueba</div></div>
        <div class="card"><h4>Facturas pendientes</h4><div class="stat-val">—</div><div class="stat-label">Pendiente de API</div></div>
        <div class="card"><h4>Onboarding</h4><div class="stat-val">40%</div><div class="stat-label">2 de 5 pasos</div></div>
    </div>

    {{-- WEEK DETAIL (hidden by default) --}}
    <div class="week-panel" id="weekPanel">
        <button class="close-btn" onclick="closeWeekPanel()">✕</button>
        <h4 id="weekTitle">Semana W1</h4>
        <div class="week-section">
            <h5>Órdenes de compra</h5>
            <div id="weekOC"></div>
        </div>
        <div class="week-section">
            <h5>Pagos</h5>
            <div id="weekPagos"></div>
        </div>
        <div class="week-section">
            <h5>Facturas</h5>
            <div id="weekFacturas"></div>
        </div>
    </div>

    {{-- MIDDLE: Calendar + Score + Activity + Onboarding --}}
    <div class="mid-grid" style="grid-template-columns:1fr 200px 1fr 1fr;">
        <div class="card">
            <div class="cal-header">
                <h4>Calendario</h4>
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="cal-nav">
                        <button onclick="calPrev()">◀</button>
                        <button onclick="calNext()">▶</button>
                    </div>
                    <span class="cal-month" id="calMonth"></span>
                </div>
            </div>
            <table class="cal-table" id="calTable">
                <thead><tr><th>WK</th><th>DOM</th><th>LUN</th><th>MAR</th><th>MIÉ</th><th>JUE</th><th>VIE</th><th>SÁB</th></tr></thead>
                <tbody id="calBody"></tbody>
            </table>
        </div>

        {{-- SCORE DONUT --}}
        <div class="card score-card">
            <h4 style="font-size:13px;font-weight:600;color:var(--text);margin-bottom:8px;align-self:flex-start;">📊 Mi Score</h4>
            <div class="score-donut">
                <canvas id="scoreDonut" width="120" height="120"></canvas>
                <div class="score-center">
                    <div class="score-num" style="color:var(--green)">0%</div>
                    <div class="score-lbl">Total</div>
                </div>
            </div>
            <div class="score-legend">
                <div class="score-legend-row"><div class="score-legend-dot" style="background:var(--green)"></div>Entrega<span class="score-legend-val">0%</span></div>
                <div class="score-legend-row"><div class="score-legend-dot" style="background:var(--primary)"></div>Puntualidad<span class="score-legend-val">0%</span></div>
            </div>
        </div>

        <div class="card">
            <h4>Actividad reciente <a href="{{ route('proveedores.dashboard') }}" class="card-link" style="float:right">Ver todo →</a></h4>
            <div class="list-item"><div class="dot dot-g"></div><div class="list-text">OC #10045 generada</div><div class="list-time">Pendiente</div></div>
            <div class="list-item"><div class="dot dot-a"></div><div class="list-text">Factura en revisión</div><div class="list-time">Pendiente</div></div>
            <div class="list-item"><div class="dot dot-g"></div><div class="list-text">Pago programado</div><div class="list-time">Pendiente</div></div>
            <div class="list-item"><div class="dot dot-x"></div><div class="list-text">Documentos verificados</div><div class="list-time">Completado</div></div>
        </div>

        <div class="card">
            <h4>Onboarding <a href="{{ route('proveedores.onboarding') }}" class="card-link" style="float:right">Ver →</a></h4>
            <div class="list-item"><div class="dot dot-g"></div><div class="list-text">Registro de proveedor</div><div class="list-time">Completado</div></div>
            <div class="list-item"><div class="dot dot-g"></div><div class="list-text">Documentos fiscales</div><div class="list-time">Completado</div></div>
            <div class="list-item"><div class="dot dot-a"></div><div class="list-text">Validación por Salcom</div><div class="list-time">En revisión</div></div>
            <div class="list-item"><div class="dot dot-x"></div><div class="list-text">Primera OC</div><div class="list-time">Pendiente</div></div>
        </div>
    </div>

    {{-- FORECAST: Productos al alza / baja --}}
    <div class="forecast-grid">
        <div class="forecast-card">
            <h4>📈 Productos al alza</h4>
            <div class="forecast-row">
                <span class="forecast-name">Resina epóxica</span>
                <div class="forecast-bar"><div class="forecast-fill" style="width:92%;background:var(--green)"></div></div>
                <span class="forecast-trend trend-up">↑ +12%</span>
            </div>
            <div class="forecast-row">
                <span class="forecast-name">Solvente técnico</span>
                <div class="forecast-bar"><div class="forecast-fill" style="width:78%;background:var(--green)"></div></div>
                <span class="forecast-trend trend-up">↑ +8%</span>
            </div>
            <div class="forecast-row">
                <span class="forecast-name">Pigmento base agua</span>
                <div class="forecast-bar"><div class="forecast-fill" style="width:65%;background:var(--green)"></div></div>
                <span class="forecast-trend trend-up">↑ +5%</span>
            </div>
            <p style="font-size:11px;color:#9ca3af;margin-top:8px">Basado en historial de pedidos · Datos de prueba</p>
        </div>
        <div class="forecast-card">
            <h4>📉 Productos a la baja</h4>
            <div class="forecast-row">
                <span class="forecast-name">Aditivo antioxidante</span>
                <div class="forecast-bar"><div class="forecast-fill" style="width:35%;background:#dc2626"></div></div>
                <span class="forecast-trend trend-down">↓ -15%</span>
            </div>
            <div class="forecast-row">
                <span class="forecast-name">Catalizador rápido</span>
                <div class="forecast-bar"><div class="forecast-fill" style="width:50%;background:#f59e0b"></div></div>
                <span class="forecast-trend trend-down">↓ -5%</span>
            </div>
            <div class="forecast-row">
                <span class="forecast-name">Fibra de refuerzo</span>
                <div class="forecast-bar"><div class="forecast-fill" style="width:55%;background:#9ca3af"></div></div>
                <span class="forecast-trend trend-flat">→ Estable</span>
            </div>
            <p style="font-size:11px;color:#9ca3af;margin-top:8px">Basado en historial de pedidos · Datos de prueba</p>
        </div>
    </div>

    {{-- BOTTOM: Quick access --}}
    <div class="top-grid" style="grid-template-columns: repeat(5,1fr);">
        <a href="{{ route('proveedores.ia') }}" class="card" style="text-decoration:none;display:flex;align-items:center;gap:14px;">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg></div>
            <div><div style="font-weight:600;color:var(--text);font-size:14px;">Dashboard IA</div><div style="font-size:12px;color:var(--text-secondary);">Análisis con Claude</div></div>
        </a>
        <a href="{{ route('proveedores.oc') }}" class="card" style="text-decoration:none;display:flex;align-items:center;gap:14px;">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
            <div><div style="font-weight:600;color:var(--text);font-size:14px;">Consultar OC</div><div style="font-size:12px;color:var(--text-secondary);">Órdenes de compra</div></div>
        </a>
        <a href="{{ route('proveedores.payment-history') }}" class="card" style="text-decoration:none;display:flex;align-items:center;gap:14px;">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div><div style="font-weight:600;color:var(--text);font-size:14px;">Historial de pagos</div><div style="font-size:12px;color:var(--text-secondary);">Pagos y facturas</div></div>
        </a>
        <a href="{{ route('proveedores.alta-producto') }}" class="card" style="text-decoration:none;display:flex;align-items:center;gap:14px;">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
            <div><div style="font-weight:600;color:var(--text);font-size:14px;">Alta de producto</div><div style="font-size:12px;color:var(--text-secondary);">Nuevo producto</div></div>
        </a>
        <a href="{{ route('muestras.crear') }}" class="card" style="text-decoration:none;display:flex;align-items:center;gap:14px;">
            <div style="width:40px;height:40px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg></div>
            <div><div style="font-weight:600;color:var(--text);font-size:14px;">Envío de muestras</div><div style="font-size:12px;color:var(--text-secondary);">Registro y seguimiento</div></div>
        </a>
    </div>
</div>

<footer>
    <div class="footer-brand">Industrias Salcom</div>
    <p>&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

<script>
// ── Hover sidebar ──
const trigger = document.getElementById('sbTrigger');
const sidebar = document.getElementById('hoverSidebar');
const overlay = document.getElementById('sbOverlay');
let closeTimer = null;

function openSB() { clearTimeout(closeTimer); sidebar.classList.add('open'); overlay.classList.add('active'); }
function closeSB() { closeTimer = setTimeout(() => { sidebar.classList.remove('open'); overlay.classList.remove('active'); }, 400); }

trigger.addEventListener('mouseenter', openSB);
sidebar.addEventListener('mouseenter', () => clearTimeout(closeTimer));
sidebar.addEventListener('mouseleave', closeSB);
overlay.addEventListener('click', () => { clearTimeout(closeTimer); sidebar.classList.remove('open'); overlay.classList.remove('active'); });

// ── Calendar with supplier weeks ──
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const regDate = new Date('2026-03-25'); // fecha de registro del proveedor
let calYear = new Date().getFullYear(), calMon = new Date().getMonth();

// Mock data per week
const weekData = {
    1: { oc: [{f:'#10045',m:'$12,500'}], pagos: [{r:'PAG-001',m:'$12,500'}], facturas: [{f:'FAC-001',m:'$12,500'}] },
    2: { oc: [{f:'#10046',m:'$8,200'}], pagos: [], facturas: [{f:'FAC-002',m:'$8,200'}] },
    3: { oc: [{f:'#10047',m:'$27,300'},{f:'#10048',m:'$5,800'}], pagos: [{r:'PAG-002',m:'$33,100'}], facturas: [] },
    4: { oc: [], pagos: [{r:'PAG-003',m:'$15,100'}], facturas: [{f:'FAC-003',m:'$15,100'}] },
};

function getSupplierWeek(date) {
    const diff = date - regDate;
    if (diff < 0) return null;
    return Math.floor(diff / (7*24*60*60*1000)) + 1;
}

function renderCal() {
    document.getElementById('calMonth').textContent = MESES[calMon] + ' ' + calYear;
    const first = new Date(calYear, calMon, 1);
    const last = new Date(calYear, calMon+1, 0);
    const startDay = first.getDay();
    const today = new Date();
    let html = '', day = 1;
    for (let row = 0; row < 6; row++) {
        if (day > last.getDate()) break;
        const weekStart = new Date(calYear, calMon, day);
        const wk = getSupplierWeek(weekStart);
        html += '<tr>';
        html += '<td class="cal-week">' + (wk ? 'W'+wk : '') + '</td>';
        for (let col = 0; col < 7; col++) {
            if (row === 0 && col < startDay) { html += '<td></td>'; continue; }
            if (day > last.getDate()) { html += '<td></td>'; continue; }
            const d = new Date(calYear, calMon, day);
            const isToday = d.toDateString() === today.toDateString();
            const sw = getSupplierWeek(d);
            const hasData = sw && weekData[sw];
            let cls = '';
            if (isToday) cls = 'today';
            else if (hasData) cls = 'has-data';
            const onclick = hasData ? ' onclick="showWeek('+sw+')"' : '';
            html += '<td class="'+cls+'"'+onclick+'>'+day+'</td>';
            day++;
        }
        html += '</tr>';
    }
    document.getElementById('calBody').innerHTML = html;
}

function calPrev() { calMon--; if(calMon<0){calMon=11;calYear--;} renderCal(); }
function calNext() { calMon++; if(calMon>11){calMon=0;calYear++;} renderCal(); }

function showWeek(wk) {
    const d = weekData[wk] || {oc:[],pagos:[],facturas:[]};
    document.getElementById('weekTitle').textContent = 'Semana W' + wk;
    document.getElementById('weekOC').innerHTML = d.oc.length ? d.oc.map(o=>'<div class="week-row"><span>'+o.f+'</span><span>'+o.m+'</span></div>').join('') : '<div style="font-size:13px;color:#9ca3af;">Sin órdenes esta semana</div>';
    document.getElementById('weekPagos').innerHTML = d.pagos.length ? d.pagos.map(p=>'<div class="week-row"><span>'+p.r+'</span><span>'+p.m+'</span></div>').join('') : '<div style="font-size:13px;color:#9ca3af;">Sin pagos esta semana</div>';
    document.getElementById('weekFacturas').innerHTML = d.facturas.length ? d.facturas.map(f=>'<div class="week-row"><span>'+f.f+'</span><span>'+f.m+'</span></div>').join('') : '<div style="font-size:13px;color:#9ca3af;">Sin facturas esta semana</div>';
    document.getElementById('weekPanel').classList.add('active');
}

function closeWeekPanel() { document.getElementById('weekPanel').classList.remove('active'); }

renderCal();

// ── Score donut ──
(function(){
    const c = document.getElementById('scoreDonut');
    if(!c) return;
    const ctx = c.getContext('2d');
    const cx=60,cy=60,r=48,sw=14;
    // Background
    ctx.beginPath();ctx.arc(cx,cy,r,0,Math.PI*2);ctx.strokeStyle='#e5e7eb';ctx.lineWidth=sw;ctx.stroke();
    // Entrega (50%)
    const entrega=0, puntualidad=0; // Will be replaced with real data
    const segs=[{pct:0.5,color:'#059669'},{pct:0.5,color:'#6B3FA0'}];
    let start=-Math.PI/2;
    segs.forEach(s=>{
        const end=start+(Math.PI*2*s.pct)-0.04;
        ctx.beginPath();ctx.arc(cx,cy,r,start,end);ctx.strokeStyle=s.color;ctx.lineWidth=sw;ctx.lineCap='round';ctx.stroke();
        start=end+0.04;
    });
})();
</script>
</body>
</html>
