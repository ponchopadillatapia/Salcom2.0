<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Industrias Salcom</title>
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

        body { font-family: 'Nunito', sans-serif; background: var(--gray-soft); min-height: 100vh; display: flex; flex-direction: column; }

        nav { background: var(--white); padding: 14px 48px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }
        .nav-logo { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--purple); font-weight: 600; letter-spacing: -0.5px; }
        .nav-logo span { display: block; font-family: 'Nunito', sans-serif; font-size: 10px; font-weight: 600; letter-spacing: 3px; color: var(--purple-mid); text-transform: uppercase; margin-top: -4px; }
        .nav-right { display: flex; align-items: center; gap: 24px; }
        .nav-user { font-size: 13px; color: var(--gray-text); font-weight: 500; }
        .nav-user span { color: var(--purple); font-weight: 600; }
        .nav-back { font-size: 13px; color: var(--gray-text); text-decoration: none; padding: 6px 14px; border: 0.5px solid var(--border); border-radius: 8px; transition: all .15s; }
        .nav-back:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }
        .btn-logout { font-size: 13px; color: var(--gray-text); padding: 6px 14px; border: 0.5px solid var(--border); border-radius: 8px; background: none; cursor: pointer; font-family: inherit; transition: all .15s; }
        .btn-logout:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }

        .hero-band { background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 60%, var(--purple-mid) 100%); padding: 36px 48px 28px; position: relative; overflow: hidden; }
        .hero-band::before { content: ''; position: absolute; width: 420px; height: 420px; border-radius: 50%; background: rgba(255,255,255,0.06); top: -180px; right: -80px; }
        .hero-band h1 { font-family: 'Playfair Display', serif; font-size: 28px; color: var(--white); font-weight: 600; position: relative; z-index: 1; }
        .hero-band p { color: rgba(255,255,255,0.75); font-size: 14px; margin-top: 4px; position: relative; z-index: 1; font-weight: 300; }

        .main { flex: 1; padding: 32px 48px 64px; }

        .section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; margin-top: 36px; padding-bottom: 12px; border-bottom: 1.5px solid var(--border); }
        .section-header:first-child { margin-top: 0; }
        .section-icon { width: 36px; height: 36px; border-radius: 10px; background: var(--purple-light); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .section-title { font-family: 'Playfair Display', serif; font-size: 18px; color: var(--purple-dark); font-weight: 600; }
        .section-sub { font-size: 12px; color: #AAA; margin-left: auto; }

        .metrics-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px; }
        .metric-card { background: var(--white); border-radius: 12px; padding: 18px 20px; border: 0.5px solid var(--border); position: relative; overflow: hidden; }
        .metric-card .accent { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 12px 0 0 12px; }
        .metric-label { font-size: 12px; color: var(--gray-text); font-weight: 500; margin-bottom: 6px; padding-left: 8px; }
        .metric-value { font-size: 26px; font-weight: 600; color: var(--purple-dark); padding-left: 8px; line-height: 1; }
        .metric-sub { font-size: 11px; color: #AAA; padding-left: 8px; margin-top: 4px; }

        .card { background: var(--white); border-radius: 14px; border: 0.5px solid var(--border); overflow: hidden; margin-bottom: 8px; }
        .card-head { padding: 14px 20px; border-bottom: 0.5px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .card-head h3 { font-size: 14px; font-weight: 600; color: var(--purple-dark); }
        .card-head .ver-todo { font-size: 12px; color: var(--purple-mid); text-decoration: none; font-weight: 500; }
        .card-head .ver-todo:hover { text-decoration: underline; }

        /* ── FILTRO FECHAS ── */
        .filtro-fechas { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .filtro-fechas input[type="date"] { border: 1px solid var(--border); border-radius: 8px; padding: 5px 10px; font-size: 12px; font-family: inherit; color: var(--gray-text); outline: none; transition: border-color .2s; }
        .filtro-fechas input[type="date"]:focus { border-color: var(--purple-mid); }
        .btn-filtrar { padding: 5px 14px; background: var(--purple); color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; transition: background .2s; }
        .btn-filtrar:hover { background: var(--purple-dark); }
        .btn-limpiar { padding: 5px 14px; background: var(--gray-soft); color: var(--gray-text); border: 1px solid var(--border); border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; transition: background .2s; }
        .btn-limpiar:hover { background: var(--purple-light); color: var(--purple); }

        /* ── BOTON EXCEL ── */
        .btn-excel {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            background: #16a34a;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-family: inherit;
            cursor: pointer;
            font-weight: 600;
            transition: background .2s;
            text-decoration: none;
        }
        .btn-excel:hover { background: #15803d; }

        .card-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        .tabla { width: 100%; border-collapse: collapse; }
        .tabla th { font-size: 11px; font-weight: 700; color: #AAA; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; text-align: left; background: var(--gray-soft); border-bottom: 0.5px solid var(--border); }
        .tabla td { padding: 12px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 0.5px solid var(--border); }
        .tabla tr:last-child td { border-bottom: none; }
        .tabla tr:hover td { background: var(--gray-soft); }
        .empty-row td { text-align: center; color: #CCC; padding: 28px; font-size: 13px; }

        .badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
        .badge-green  { background: var(--green-bg);  color: var(--green); }
        .badge-amber  { background: var(--amber-bg);  color: var(--amber); }
        .badge-blue   { background: var(--blue-bg);   color: var(--blue); }
        .badge-red    { background: var(--red-bg);    color: var(--red); }
        .badge-purple { background: var(--purple-light); color: var(--purple); }

        .estatus-list { padding: 0; }
        .estatus-item { display: flex; align-items: center; gap: 14px; padding: 14px 20px; border-bottom: 0.5px solid var(--border); transition: background .15s; }
        .estatus-item:last-child { border-bottom: none; }
        .estatus-item:hover { background: var(--gray-soft); }
        .estatus-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        .dot-green  { background: var(--green); animation: pulse 2s ease-in-out infinite; }
        .dot-amber  { background: var(--amber); animation: pulse 2s ease-in-out infinite; }
        .dot-blue   { background: var(--blue); animation: pulse 2s ease-in-out infinite; }
        .dot-gray   { background: #CCC; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .estatus-info { flex: 1; }
        .estatus-info .titulo { font-size: 13px; font-weight: 600; color: var(--gray-text); }
        .estatus-info .sub { font-size: 12px; color: #AAA; margin-top: 2px; }
        .estatus-time { font-size: 11px; color: #CCC; }

        footer { background: var(--white); border-top: 1px solid var(--border); padding: 24px 48px; display: flex; align-items: center; justify-content: space-between; }
        footer p { font-size: 12px; color: #AAA; }
        .footer-logo { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--purple); }

        @media (max-width: 900px) {
            .metrics-row { grid-template-columns: 1fr 1fr; }
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
        Industrias Salcom
        <span>Portal de Proveedores</span>
    </div>
    <div class="nav-right">
        <span class="nav-user">Hola, <span>{{ session('proveedor_nombre', 'Proveedor') }}</span></span>
        <a href="{{ route('proveedores.portal') }}" class="nav-back">← Portal</a>
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

    {{-- ══ SECCIÓN 1: FACTURAS ══ --}}
    <div class="section-header">
        <div class="section-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
        </div>
        <div class="section-title">Facturas</div>
        <span class="section-sub">Pendiente de API</span>
    </div>

    <div class="metrics-row">
        <div class="metric-card">
            <div class="accent" style="background: var(--purple)"></div>
            <div class="metric-label">Facturas pendientes</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background: var(--amber)"></div>
            <div class="metric-label">Facturas en revisión</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background: var(--green)"></div>
            <div class="metric-label">Facturas aprobadas</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Facturas recientes</h3>
            <div class="card-actions">
                <a href="#" class="ver-todo">Ver todas</a>
                <button class="btn-excel" onclick="exportarExcel('tablaFacturas', 'facturas')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Exportar Excel
                </button>
            </div>
        </div>
        <table class="tabla" id="tablaFacturas">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>OC relacionada</th>
                    <th>Monto</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                <tr class="empty-row">
                    <td colspan="5">Pendiente de API</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ══ SECCIÓN 2: PAGOS ══ --}}
    <div class="section-header">
        <div class="section-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
                <line x1="2" y1="10" x2="22" y2="10"/>
            </svg>
        </div>
        <div class="section-title">Pagos</div>
        <span class="section-sub">Pendiente de API</span>
    </div>

    <div class="metrics-row">
        <div class="metric-card">
            <div class="accent" style="background: var(--blue)"></div>
            <div class="metric-label">Pagos programados</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background: var(--green)"></div>
            <div class="metric-label">Pagos realizados</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background: var(--amber)"></div>
            <div class="metric-label">Monto pendiente</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Historial de pagos</h3>
            <div class="card-actions">
                <div class="filtro-fechas">
                    <input type="date" id="fechaDesde" title="Desde">
                    <input type="date" id="fechaHasta" title="Hasta">
                    <button class="btn-filtrar" onclick="filtrarPagos()">Filtrar</button>
                    <button class="btn-limpiar" onclick="limpiarFiltro()">Limpiar</button>
                </div>
                <button class="btn-excel" onclick="exportarExcel('tablaPagos', 'historial-pagos')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Exportar Excel
                </button>
            </div>
        </div>
        <table class="tabla" id="tablaPagos">
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
                <tr class="empty-row">
                    <td colspan="5">Pendiente de API</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ══ SECCIÓN 3: ESTATUS EN TIEMPO REAL ══ --}}
    <div class="section-header">
        <div class="section-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="section-title">Estatus en tiempo real</div>
        <span class="section-sub" style="color: var(--green); font-weight: 600;">● En vivo</span>
    </div>

    <div class="card">
        <div class="estatus-list">
            <div class="estatus-item">
                <div class="estatus-dot dot-green"></div>
                <div class="estatus-info">
                    <div class="titulo">OC generada</div>
                    <div class="sub">Salcom generó una orden de compra</div>
                </div>
                <div class="estatus-time">Pendiente de API</div>
            </div>
            <div class="estatus-item">
                <div class="estatus-dot dot-amber"></div>
                <div class="estatus-info">
                    <div class="titulo">Factura en revisión</div>
                    <div class="sub">Tu factura está siendo validada</div>
                </div>
                <div class="estatus-time">Pendiente de API</div>
            </div>
            <div class="estatus-item">
                <div class="estatus-dot dot-blue"></div>
                <div class="estatus-info">
                    <div class="titulo">Pago programado</div>
                    <div class="sub">Tu pago tiene fecha asignada</div>
                </div>
                <div class="estatus-time">Pendiente de API</div>
            </div>
            <div class="estatus-item">
                <div class="estatus-dot dot-gray"></div>
                <div class="estatus-info">
                    <div class="titulo">Historial de pagos</div>
                    <div class="sub">Consulta el historial de todos tus pagos</div>
                </div>
                <div class="estatus-time">Pendiente de API</div>
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
    function exportarExcel(tablaId, nombre) {
        const tabla = document.getElementById(tablaId);
        if (!tabla) return;
        let csv = '';
        const filas = tabla.querySelectorAll('tr');
        filas.forEach(fila => {
            if (fila.classList.contains('empty-row')) return;
            const celdas = fila.querySelectorAll('th, td');
            const fila_data = Array.from(celdas).map(c => '"' + c.textContent.trim().replace(/"/g, '""') + '"');
            csv += fila_data.join(',') + '\n';
        });
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = nombre + '-' + new Date().toISOString().slice(0,10) + '.csv';
        link.click();
    }

    function filtrarPagos() {
        const desde = document.getElementById('fechaDesde').value;
        const hasta = document.getElementById('fechaHasta').value;
        const filas = document.querySelectorAll('#tablaPagos tbody tr');
        filas.forEach(fila => {
            const fechaTd = fila.querySelector('td:nth-child(3)');
            if (!fechaTd) return;
            const fecha = fechaTd.textContent.trim();
            if (!desde && !hasta) { fila.style.display = ''; return; }
            const partes = fecha.split('/');
            if (partes.length !== 3) return;
            const fechaRow = new Date(`${partes[2]}-${partes[1]}-${partes[0]}`);
            const fdDesde = desde ? new Date(desde) : null;
            const fdHasta = hasta ? new Date(hasta) : null;
            let mostrar = true;
            if (fdDesde && fechaRow < fdDesde) mostrar = false;
            if (fdHasta && fechaRow > fdHasta) mostrar = false;
            fila.style.display = mostrar ? '' : 'none';
        });
    }

    function limpiarFiltro() {
        document.getElementById('fechaDesde').value = '';
        document.getElementById('fechaHasta').value = '';
        document.querySelectorAll('#tablaPagos tbody tr').forEach(f => f.style.display = '');
    }
</script>

</body>
</html>