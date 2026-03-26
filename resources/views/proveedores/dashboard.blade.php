<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Proveedor — Salcom Industries</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Nunito:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

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

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--gray-soft);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        nav {
            background: var(--white);
            padding: 14px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
        }
        .nav-logo {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            color: var(--purple);
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .nav-logo span {
            display: block;
            font-family: 'Nunito', sans-serif;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 3px;
            color: var(--purple-mid);
            text-transform: uppercase;
            margin-top: -4px;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .nav-user {
            font-size: 13px;
            color: var(--gray-text);
            font-weight: 500;
        }
        .nav-user span {
            color: var(--purple);
            font-weight: 600;
        }
        .btn-logout {
            font-size: 13px;
            color: var(--gray-text);
            padding: 6px 14px;
            border: 0.5px solid var(--border);
            border-radius: 8px;
            transition: all .15s;
            background: none;
            cursor: pointer;
            font-family: 'Nunito', sans-serif;
            font-weight: 500;
        }
        .btn-logout:hover {
            background: var(--purple-light);
            color: var(--purple);
            border-color: var(--purple-mid);
        }

        /* ── HERO BAND ── */
        .hero-band {
            background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 60%, var(--purple-mid) 100%);
            padding: 36px 48px 28px;
            position: relative;
            overflow: hidden;
        }
        .hero-band::before {
            content: '';
            position: absolute;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -180px; right: -80px;
        }
        .hero-band h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: var(--white);
            font-weight: 600;
            position: relative; z-index: 1;
        }
        .hero-band p {
            color: rgba(255,255,255,0.75);
            font-size: 14px;
            margin-top: 4px;
            position: relative; z-index: 1;
            font-weight: 300;
        }

        /* ── MAIN ── */
        .main {
            flex: 1;
            padding: 32px 48px 64px;
        }

        /* ── SECCIÓN TÍTULO ── */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--gray-text);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 16px;
            margin-top: 32px;
        }
        .section-title:first-child { margin-top: 0; }

        /* ── METRIC CARDS ── */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .metric-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px 22px;
            border: 0.5px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: transform .15s, box-shadow .15s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(107,63,160,0.10);
        }
        .metric-card .accent {
            position: absolute;
            top: 0; left: 0;
            width: 4px;
            height: 100%;
            border-radius: 12px 0 0 12px;
        }
        .metric-label {
            font-size: 12px;
            color: var(--gray-text);
            font-weight: 500;
            margin-bottom: 8px;
            padding-left: 8px;
        }
        .metric-value {
            font-size: 28px;
            font-weight: 600;
            color: var(--purple-dark);
            padding-left: 8px;
            line-height: 1;
        }
        .metric-sub {
            font-size: 11px;
            color: #AAA;
            padding-left: 8px;
            margin-top: 6px;
        }

        /* ── DOS COLUMNAS ── */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* ── CARD ── */
        .card {
            background: var(--white);
            border-radius: 16px;
            border: 0.5px solid var(--border);
            overflow: hidden;
        }
        .card-head {
            padding: 16px 20px;
            border-bottom: 0.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-head h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--purple-dark);
        }
        .card-head .ver-todo {
            font-size: 12px;
            color: var(--purple-mid);
            text-decoration: none;
            font-weight: 500;
        }
        .card-head .ver-todo:hover { text-decoration: underline; }

        /* ── TABLA ── */
        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th {
            font-size: 11px;
            font-weight: 700;
            color: #AAA;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 20px;
            text-align: left;
            background: var(--gray-soft);
            border-bottom: 0.5px solid var(--border);
        }
        .tabla td {
            padding: 12px 20px;
            font-size: 13px;
            color: var(--gray-text);
            border-bottom: 0.5px solid var(--border);
        }
        .tabla tr:last-child td { border-bottom: none; }
        .tabla tr:hover td { background: var(--gray-soft); }

        /* ── ESTATUS EN TIEMPO REAL ── */
        .estatus-list { padding: 0; }
        .estatus-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 20px;
            border-bottom: 0.5px solid var(--border);
            transition: background .15s;
        }
        .estatus-item:last-child { border-bottom: none; }
        .estatus-item:hover { background: var(--gray-soft); }
        .estatus-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .dot-green  { background: var(--green); }
        .dot-amber  { background: var(--amber); }
        .dot-blue   { background: var(--blue); }
        .estatus-info { flex: 1; }
        .estatus-info .titulo {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-text);
        }
        .estatus-info .sub {
            font-size: 12px;
            color: #AAA;
            margin-top: 2px;
        }
        .estatus-time {
            font-size: 11px;
            color: #CCC;
        }

        /* ── ACCIONES RÁPIDAS ── */
        .acciones-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .accion-card {
            background: var(--white);
            border: 0.5px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            display: block;
        }
        .accion-card:hover {
            background: var(--purple-light);
            border-color: var(--purple-mid);
            transform: translateY(-2px);
        }
        .accion-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: var(--purple-light);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
        }
        .accion-card:hover .accion-icon { background: var(--white); }
        .accion-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--purple-dark);
        }
        .accion-sub {
            font-size: 12px;
            color: #AAA;
            margin-top: 4px;
        }

        /* ── FOOTER ── */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 24px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        footer p { font-size: 12px; color: #AAA; }
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 16px;
            color: var(--purple);
        }

        @media (max-width: 900px) {
            .metrics-grid { grid-template-columns: repeat(2, 1fr); }
            .two-col { grid-template-columns: 1fr; }
            .acciones-grid { grid-template-columns: 1fr; }
            .main { padding: 24px 20px 48px; }
            nav { padding: 12px 20px; }
            footer { padding: 18px 20px; }
            .hero-band { padding: 28px 20px; }
        }
    </style>
</head>
<body>

{{-- NAVBAR --}}
<nav>
    <div class="nav-logo">
        Wiese
        <span>Salcom Industries</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">Hola, <span>{{ session('proveedor_nombre', 'Proveedor') }}</span></span>
        {{-- CERRAR SESIÓN CORREGIDO --}}
        <form method="POST" action="{{ route('proveedores.logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-logout">Cerrar sesión</button>
        </form>
    </div>
</nav>

{{-- HERO --}}
<div class="hero-band">
    <h1>Bienvenido, {{ session('proveedor_nombre', 'Proveedor') }}</h1>
    <p>Código: {{ session('proveedor_codigo', '—') }} — {{ now()->format('d/m/Y') }}</p>
</div>

{{-- MAIN --}}
<div class="main">

    {{-- MÉTRICAS --}}
    <div class="section-title">Resumen general</div>
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="accent" style="background: var(--purple)"></div>
            <div class="metric-label">Facturas pendientes</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background: var(--green)"></div>
            <div class="metric-label">Pagos programados</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background: var(--amber)"></div>
            <div class="metric-label">OC abiertas</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background: var(--blue)"></div>
            <div class="metric-label">Muestras en proceso</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
    </div>

    {{-- ACCIONES RÁPIDAS --}}
    <div class="section-title">Acciones rápidas</div>
    <div class="acciones-grid">
        <a href="#" class="accion-card">
            <div class="accion-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div class="accion-title">Consultar OC</div>
            <div class="accion-sub">Ver órdenes de compra</div>
        </a>
        <a href="#" class="accion-card">
            <div class="accion-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
            <div class="accion-title">Subir factura</div>
            <div class="accion-sub">Adjuntar factura a OC</div>
        </a>
        <a href="#" class="accion-card">
            <div class="accion-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                </svg>
            </div>
            <div class="accion-title">Envío de muestras</div>
            <div class="accion-sub">Registrar lote de muestra</div>
        </a>
    </div>

    {{-- FACTURAS Y ESTATUS --}}
    <div class="two-col">

        <div class="card">
            <div class="card-head">
                <h3>Facturas recientes</h3>
                <a href="#" class="ver-todo">Ver todas</a>
            </div>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" style="text-align:center; color:#CCC; padding:24px; font-size:13px">
                            Pendiente de API
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-head">
                <h3>Estatus en tiempo real</h3>
                <span style="font-size:11px; color:var(--green); font-weight:600">● En vivo</span>
            </div>
            <div class="estatus-list">
                <div class="estatus-item">
                    <div class="estatus-dot dot-green"></div>
                    <div class="estatus-info">
                        <div class="titulo">Sistema operativo</div>
                        <div class="sub">Todos los servicios funcionando</div>
                    </div>
                    <div class="estatus-time">ahora</div>
                </div>
                <div class="estatus-item">
                    <div class="estatus-dot dot-amber"></div>
                    <div class="estatus-info">
                        <div class="titulo">Validación de facturas</div>
                        <div class="sub">Pendiente de conexión con API</div>
                    </div>
                    <div class="estatus-time">—</div>
                </div>
                <div class="estatus-item">
                    <div class="estatus-dot dot-blue"></div>
                    <div class="estatus-info">
                        <div class="titulo">Pagos programados</div>
                        <div class="sub">Pendiente de conexión con API</div>
                    </div>
                    <div class="estatus-time">—</div>
                </div>
                <div class="estatus-item">
                    <div class="estatus-dot dot-amber"></div>
                    <div class="estatus-info">
                        <div class="titulo">Órdenes de compra</div>
                        <div class="sub">Pendiente de conexión con API</div>
                    </div>
                    <div class="estatus-time">—</div>
                </div>
            </div>
        </div>

    </div>

    {{-- PAGOS --}}
    <div class="section-title">Pagos</div>
    <div class="card">
        <div class="card-head">
            <h3>Historial de pagos</h3>
            <a href="#" class="ver-todo">Ver todos</a>
        </div>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Referencia</th>
                    <th>Factura</th>
                    <th>Fecha programada</th>
                    <th>Monto</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" style="text-align:center; color:#CCC; padding:24px; font-size:13px">
                        Pendiente de API
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

{{-- FOOTER --}}
<footer>
    <div class="footer-logo">Wiese <span style="font-family:'Nunito';font-size:11px;color:#AAA;font-weight:600;letter-spacing:2px;text-transform:uppercase">Salcom Industries</span></div>
    <p>© {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</p>
</footer>

</body>
</html>