<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business — Industrias Salcom</title>
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
            --red:         #DC2626;
            --red-bg:      #FEE2E2;
            --blue:        #1D4ED8;
            --blue-bg:     #DBEAFE;
        }

        body { font-family: 'Nunito', sans-serif; background: var(--gray-soft); min-height: 100vh; display: flex; flex-direction: column; }

        nav { background: var(--white); padding: 0 32px; height: 57px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 200; flex-shrink: 0; }
        .nav-logo { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--purple); font-weight: 600; }
        .nav-logo span { display: block; font-family: 'Nunito', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 3px; color: var(--purple-mid); text-transform: uppercase; margin-top: -4px; }
        .nav-right { display: flex; align-items: center; gap: 20px; }
        .nav-user { font-size: 13px; color: var(--gray-text); font-weight: 500; }
        .nav-user span { color: var(--purple); font-weight: 600; }
        .btn-logout { font-size: 13px; color: var(--gray-text); padding: 6px 14px; border: 0.5px solid var(--border); border-radius: 8px; background: none; cursor: pointer; font-family: inherit; transition: all .15s; }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        .hero-band { background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 60%, var(--purple-mid) 100%); padding: 24px 32px; position: relative; overflow: hidden; flex-shrink: 0; }
        .hero-band::before { content: ''; position: absolute; width: 420px; height: 420px; border-radius: 50%; background: rgba(255,255,255,0.06); top: -180px; right: -80px; }
        .hero-band h1 { font-family: 'Playfair Display', serif; font-size: 24px; color: #fff; font-weight: 600; position: relative; z-index: 1; }
        .hero-band p { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 4px; position: relative; z-index: 1; }

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

        .main-content { flex: 1; min-width: 0; padding: 32px; overflow-y: auto; }

        /* RESUMEN */
        .resumen-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .resumen-card { background: var(--white); border-radius: 12px; padding: 18px 20px; border: 0.5px solid var(--border); position: relative; overflow: hidden; }
        .resumen-card .accent { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 12px 0 0 12px; }
        .resumen-label { font-size: 12px; color: var(--gray-text); font-weight: 500; margin-bottom: 6px; padding-left: 8px; }
        .resumen-value { font-size: 28px; font-weight: 700; padding-left: 8px; line-height: 1; }
        .resumen-sub { font-size: 11px; color: #AAA; padding-left: 8px; margin-top: 4px; }
        .val-red   { color: var(--red); }
        .val-amber { color: var(--amber); }
        .val-green { color: var(--green); }
        .val-blue  { color: var(--blue); }

        /* SECCIÓN */
        .seccion { margin-bottom: 28px; }
        .seccion-titulo { display: flex; align-items: center; gap: 10px; font-family: 'Playfair Display', serif; font-size: 17px; color: var(--purple-dark); font-weight: 600; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1.5px solid var(--border); }
        .seccion-titulo .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .seccion-sub-label { font-size: 11px; color: #AAA; margin-left: auto; font-family: 'Nunito', sans-serif; font-weight: 500; }

        /* ══ ITEM 360 STYLE ══ */
        .item360-wrap { background: var(--white); border-radius: 16px; border: 0.5px solid var(--border); padding: 28px; }

        /* Action items arriba — como Item 360 */
        .action-items { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 28px; }
        .action-card { background: var(--gray-soft); border-radius: 12px; padding: 16px 20px; border: 0.5px solid var(--border); }
        .action-card-label { font-size: 12px; color: var(--gray-text); margin-bottom: 6px; }
        .action-card-value { font-size: 28px; font-weight: 700; color: var(--purple-dark); line-height: 1; }
        .action-card-sub { font-size: 12px; color: #AAA; margin-top: 4px; }
        .action-card.alerta .action-card-value { color: var(--amber); }
        .action-card.ok .action-card-value { color: var(--green); }

        /* Dona + leyenda */
        .item360-body { display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: center; }

        .dona-wrap { display: flex; flex-direction: column; align-items: center; gap: 12px; }
        .dona-container { position: relative; width: 200px; height: 200px; }
        .dona-container canvas { position: absolute; top: 0; left: 0; }
        .dona-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
        .dona-score { font-size: 36px; font-weight: 700; color: var(--purple-dark); line-height: 1; }
        .dona-label { font-size: 11px; color: #999; margin-top: 2px; }

        .dona-legend { width: 100%; }
        .legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--gray-text); margin-bottom: 6px; }
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .legend-pct { font-weight: 700; margin-left: auto; }

        /* Tabla productos lado derecho */
        .prod-right { display: flex; flex-direction: column; gap: 12px; }
        .prod-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--gray-soft); border-radius: 10px; }
        .prod-name { font-size: 13px; font-weight: 700; color: var(--purple-dark); width: 70px; flex-shrink: 0; }
        .prod-bar-wrap { flex: 1; }
        .prod-bar { height: 8px; background: var(--border); border-radius: 999px; overflow: hidden; margin-bottom: 3px; }
        .prod-bar-fill { height: 100%; border-radius: 999px; }
        .prod-bar-label { font-size: 11px; color: #999; }
        .prod-monto { font-size: 13px; font-weight: 600; color: var(--gray-text); white-space: nowrap; }
        .prod-trend { font-size: 12px; font-weight: 700; white-space: nowrap; }
        .up   { color: var(--green); }
        .flat { color: #AAA; }
        .down { color: var(--red); }

        .api-note { font-size: 11px; color: #AAA; text-align: center; margin-top: 20px; }

        /* TAREA CARD */
        .tarea-card { background: var(--white); border-radius: 12px; border: 0.5px solid var(--border); padding: 16px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 10px; transition: box-shadow .15s; }
        .tarea-card:hover { box-shadow: 0 4px 16px rgba(107,63,160,0.08); }
        .tarea-card.urgente    { border-left: 4px solid var(--red); }
        .tarea-card.advertencia{ border-left: 4px solid var(--amber); }
        .tarea-card.info       { border-left: 4px solid var(--blue); }
        .tarea-card.ok         { border-left: 4px solid var(--green); }
        .tarea-icono { font-size: 22px; flex-shrink: 0; width: 40px; text-align: center; }
        .tarea-info { flex: 1; min-width: 0; }
        .tarea-titulo { font-size: 14px; font-weight: 700; color: var(--purple-dark); margin-bottom: 2px; }
        .tarea-desc { font-size: 12px; color: #999; line-height: 1.5; }
        .tarea-fecha { font-size: 11px; color: #BBB; white-space: nowrap; flex-shrink: 0; }
        .badge-urgente     { background: var(--red-bg);   color: var(--red);   font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
        .badge-advertencia { background: var(--amber-bg); color: var(--amber); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
        .badge-info        { background: var(--blue-bg);  color: var(--blue);  font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
        .badge-ok          { background: var(--green-bg); color: var(--green); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
        .btn-accion { padding: 6px 16px; background: var(--purple); color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; text-decoration: none; white-space: nowrap; flex-shrink: 0; transition: background .15s; }
        .btn-accion:hover { background: var(--purple-dark); }

        footer { background: var(--white); border-top: 1px solid var(--border); padding: 18px 32px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
        footer p { font-size: 12px; color: #AAA; }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--purple); }

        @media (max-width: 900px) {
            .sidebar { display: none; }
            .resumen-grid, .action-items { grid-template-columns: 1fr 1fr; }
            .item360-body { grid-template-columns: 1fr; }
            .main-content { padding: 20px 16px; }
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
    <h1>Business</h1>
    <p>Tus tareas pendientes y alertas importantes — {{ now()->format('d/m/Y') }}</p>
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
            <a href="{{ route('proveedores.business') }}" class="sb-link active">
                <div class="sb-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
                <span class="sb-text">Business</span>
            </a>
        </nav>
    </div>

    {{-- CONTENIDO --}}
    <div class="main-content">

        {{-- RESUMEN --}}
        <div class="resumen-grid">
            <div class="resumen-card">
                <div class="accent" style="background:var(--red)"></div>
                <div class="resumen-label">Documentos por vencer</div>
                <div class="resumen-value val-red">2</div>
                <div class="resumen-sub">Acción requerida</div>
            </div>
            <div class="resumen-card">
                <div class="accent" style="background:var(--amber)"></div>
                <div class="resumen-label">Facturas pendientes</div>
                <div class="resumen-value val-amber">3</div>
                <div class="resumen-sub">OC sin factura</div>
            </div>
            <div class="resumen-card">
                <div class="accent" style="background:var(--blue)"></div>
                <div class="resumen-label">Pagos próximos</div>
                <div class="resumen-value val-blue">1</div>
                <div class="resumen-sub">Esta semana</div>
            </div>
            <div class="resumen-card">
                <div class="accent" style="background:var(--green)"></div>
                <div class="resumen-label">Notificaciones</div>
                <div class="resumen-value val-green">4</div>
                <div class="resumen-sub">Sin leer</div>
            </div>
        </div>

        {{-- ══ PROMEDIO DEL PRODUCTO — ESTILO ITEM 360 ══ --}}
        <div class="seccion">
            <div class="seccion-titulo">
                <div class="dot" style="background:var(--purple)"></div>
                Business — Promedio del producto
                <span class="seccion-sub-label">⚠ Datos de prueba — Pendiente de API</span>
            </div>

            <div class="item360-wrap">

                {{-- Action items arriba --}}
                <div class="action-items">
                    <div class="action-card alerta">
                        <div class="action-card-label">OC sin factura</div>
                        <div class="action-card-value">3</div>
                        <div class="action-card-sub">Requieren atención</div>
                    </div>
                    <div class="action-card ok">
                        <div class="action-card-label">OC completadas este mes</div>
                        <div class="action-card-value">8</div>
                        <div class="action-card-sub">97.6% cumplimiento</div>
                    </div>
                    <div class="action-card">
                        <div class="action-card-label">Recomendaciones de mejora</div>
                        <div class="action-card-value">2</div>
                        <div class="action-card-sub">Ver detalles →</div>
                    </div>
                </div>

                {{-- Dona + productos --}}
                <div class="item360-body">

                    {{-- DONA --}}
                    <div class="dona-wrap">
                        <div class="dona-container">
                            <canvas id="donaChart" width="200" height="200"></canvas>
                            <div class="dona-center">
                                <div class="dona-score">86.4</div>
                                <div class="dona-label">Score promedio</div>
                            </div>
                        </div>
                        <div class="dona-legend">
                            <div class="legend-item">
                                <div class="legend-dot" style="background:#059669"></div>
                                <span>Bueno (80-100)</span>
                                <span class="legend-pct" style="color:#059669">3 productos (60%)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background:#D97706"></div>
                                <span>Regular (60-79)</span>
                                <span class="legend-pct" style="color:#D97706">1 producto (20%)</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background:#DC2626"></div>
                                <span>Bajo (&lt;60)</span>
                                <span class="legend-pct" style="color:#DC2626">1 producto (20%)</span>
                            </div>
                        </div>
                    </div>

                    {{-- PRODUCTOS --}}
                    <div class="prod-right">
                        <div class="prod-row">
                            <div class="prod-name">PROD-001</div>
                            <div class="prod-bar-wrap">
                                <div class="prod-bar"><div class="prod-bar-fill" style="width:92%;background:#059669"></div></div>
                                <div class="prod-bar-label">Score: 92 — Bueno</div>
                            </div>
                            <div class="prod-monto">$800 prom.</div>
                            <div class="prod-trend up">↑ +12%</div>
                        </div>
                        <div class="prod-row">
                            <div class="prod-name">PROD-002</div>
                            <div class="prod-bar-wrap">
                                <div class="prod-bar"><div class="prod-bar-fill" style="width:88%;background:#059669"></div></div>
                                <div class="prod-bar-label">Score: 88 — Bueno</div>
                            </div>
                            <div class="prod-monto">$900 prom.</div>
                            <div class="prod-trend up">↑ +8%</div>
                        </div>
                        <div class="prod-row">
                            <div class="prod-name">PROD-003</div>
                            <div class="prod-bar-wrap">
                                <div class="prod-bar"><div class="prod-bar-fill" style="width:81%;background:#059669"></div></div>
                                <div class="prod-bar-label">Score: 81 — Bueno</div>
                            </div>
                            <div class="prod-monto">$410 prom.</div>
                            <div class="prod-trend flat">→ Estable</div>
                        </div>
                        <div class="prod-row">
                            <div class="prod-name">PROD-004</div>
                            <div class="prod-bar-wrap">
                                <div class="prod-bar"><div class="prod-bar-fill" style="width:72%;background:#D97706"></div></div>
                                <div class="prod-bar-label">Score: 72 — Regular</div>
                            </div>
                            <div class="prod-monto">$511 prom.</div>
                            <div class="prod-trend down">↓ -5%</div>
                        </div>
                        <div class="prod-row">
                            <div class="prod-name">PROD-005</div>
                            <div class="prod-bar-wrap">
                                <div class="prod-bar"><div class="prod-bar-fill" style="width:58%;background:#DC2626"></div></div>
                                <div class="prod-bar-label">Score: 58 — Bajo</div>
                            </div>
                            <div class="prod-monto">$725 prom.</div>
                            <div class="prod-trend down">↓ -15%</div>
                        </div>
                    </div>
                </div>

                <p class="api-note">⚠ Datos de prueba — se reemplazarán con la API de Alan</p>
            </div>
        </div>

        {{-- DOCUMENTOS POR VENCER --}}
        <div class="seccion">
            <div class="seccion-titulo"><div class="dot" style="background:var(--red)"></div>Documentos por vencer</div>
            <div class="tarea-card urgente">
                <div class="tarea-icono">📄</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">CIF — Constancia de Situación Fiscal</div>
                    <div class="tarea-desc">Tu Constancia de Situación Fiscal vence en 5 días. Actualízala para continuar operando sin interrupciones.</div>
                </div>
                <span class="badge-urgente">Urgente</span>
                <div class="tarea-fecha">Vence: 11/04/2026</div>
                <a href="/empresa" class="btn-accion">Actualizar</a>
            </div>
            <div class="tarea-card advertencia">
                <div class="tarea-icono">✅</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">Opinión de Cumplimiento del SAT</div>
                    <div class="tarea-desc">Tu Opinión Positiva vence en 18 días. Te recomendamos renovarla pronto para evitar retrasos en tus pagos.</div>
                </div>
                <span class="badge-advertencia">Próximo</span>
                <div class="tarea-fecha">Vence: 24/04/2026</div>
                <a href="/empresa" class="btn-accion">Actualizar</a>
            </div>
        </div>

        {{-- FACTURAS PENDIENTES --}}
        <div class="seccion">
            <div class="seccion-titulo"><div class="dot" style="background:var(--amber)"></div>Facturas pendientes de subir</div>
            <div class="tarea-card advertencia">
                <div class="tarea-icono">🧾</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">OC #10045 — $12,500.00</div>
                    <div class="tarea-desc">Esta orden de compra no tiene factura asociada. Súbela para iniciar el proceso de pago.</div>
                </div>
                <span class="badge-advertencia">Sin factura</span>
                <div class="tarea-fecha">OC: 01/03/2026</div>
                <a href="{{ route('proveedores.oc') }}" class="btn-accion">Ver OC</a>
            </div>
            <div class="tarea-card advertencia">
                <div class="tarea-icono">🧾</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">OC #10046 — $8,200.00</div>
                    <div class="tarea-desc">Esta orden de compra no tiene factura asociada. Súbela para iniciar el proceso de pago.</div>
                </div>
                <span class="badge-advertencia">Sin factura</span>
                <div class="tarea-fecha">OC: 05/03/2026</div>
                <a href="{{ route('proveedores.oc') }}" class="btn-accion">Ver OC</a>
            </div>
            <div class="tarea-card advertencia">
                <div class="tarea-icono">🧾</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">OC #10049 — $15,100.00</div>
                    <div class="tarea-desc">Esta orden de compra no tiene factura asociada. Súbela para iniciar el proceso de pago.</div>
                </div>
                <span class="badge-advertencia">Sin factura</span>
                <div class="tarea-fecha">OC: 20/03/2026</div>
                <a href="{{ route('proveedores.oc') }}" class="btn-accion">Ver OC</a>
            </div>
        </div>

        {{-- PAGOS PROXIMOS --}}
        <div class="seccion">
            <div class="seccion-titulo"><div class="dot" style="background:var(--blue)"></div>Pagos próximos</div>
            <div class="tarea-card info">
                <div class="tarea-icono">💳</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">Pago programado — $27,300.00</div>
                    <div class="tarea-desc">Pago correspondiente a la OC #10047 programado para esta semana. Verifica que tus datos bancarios estén actualizados.</div>
                </div>
                <span class="badge-info">Esta semana</span>
                <div class="tarea-fecha">09/04/2026</div>
                <a href="{{ route('proveedores.dashboard') }}" class="btn-accion">Ver detalle</a>
            </div>
        </div>

        {{-- NOTIFICACIONES --}}
        <div class="seccion">
            <div class="seccion-titulo"><div class="dot" style="background:var(--purple)"></div>Notificaciones de Industrias Salcom</div>
            <div class="tarea-card ok">
                <div class="tarea-icono">🎉</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">¡Bienvenido al portal de proveedores!</div>
                    <div class="tarea-desc">Tu cuenta ha sido creada exitosamente. Completa tu onboarding para activar tu cuenta al 100%.</div>
                </div>
                <span class="badge-ok">Nuevo</span>
                <div class="tarea-fecha">06/04/2026</div>
            </div>
            <div class="tarea-card info">
                <div class="tarea-icono">📢</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">Nueva orden de compra generada</div>
                    <div class="tarea-desc">Industrias Salcom ha generado una nueva OC #10049 por $15,100.00. Revísala en el módulo de consultar OC.</div>
                </div>
                <span class="badge-info">OC Nueva</span>
                <div class="tarea-fecha">20/03/2026</div>
                <a href="{{ route('proveedores.oc') }}" class="btn-accion">Ver OC</a>
            </div>
            <div class="tarea-card info">
                <div class="tarea-icono">📋</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">Documentos en revisión</div>
                    <div class="tarea-desc">Tu CIF y Opinión Positiva están siendo revisados por el equipo de Salcom. Te notificaremos cuando estén aprobados.</div>
                </div>
                <span class="badge-info">En revisión</span>
                <div class="tarea-fecha">15/03/2026</div>
            </div>
            <div class="tarea-card ok">
                <div class="tarea-icono">✅</div>
                <div class="tarea-info">
                    <div class="tarea-titulo">Registro completado</div>
                    <div class="tarea-desc">Tu registro como proveedor fue completado exitosamente. Ya puedes acceder al portal.</div>
                </div>
                <span class="badge-ok">Completado</span>
                <div class="tarea-fecha">01/03/2026</div>
            </div>
        </div>

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

// ── DONA CHART ──
window.addEventListener('load', function() {
    const canvas = document.getElementById('donaChart');
    const ctx = canvas.getContext('2d');
    const cx = 100, cy = 100, r = 80, stroke = 22;

    const segmentos = [
        { pct: 0.60, color: '#059669' },
        { pct: 0.20, color: '#D97706' },
        { pct: 0.20, color: '#DC2626' },
    ];

    const gap = 0.03;
    let start = -Math.PI / 2;

    ctx.clearRect(0, 0, 200, 200);

    // Fondo gris
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.strokeStyle = '#E5E7EB';
    ctx.lineWidth = stroke;
    ctx.stroke();

    segmentos.forEach(seg => {
        const end = start + (Math.PI * 2 * seg.pct) - gap;
        ctx.beginPath();
        ctx.arc(cx, cy, r, start, end);
        ctx.strokeStyle = seg.color;
        ctx.lineWidth = stroke;
        ctx.lineCap = 'round';
        ctx.stroke();
        start = end + gap;
    });
});
</script>
</body>
</html>