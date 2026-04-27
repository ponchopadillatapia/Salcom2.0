@extends('layouts.proveedor')

@section('title', 'Dashboard')

@section('hero')
<div class="hero-band">
    <h1>Bienvenido, {{ session('proveedor_nombre', 'Proveedor') }}</h1>
    <p>Código: {{ session('proveedor_codigo', '—') }} — {{ now()->translatedFormat('d \d\e F, Y') }}</p>
</div>
@endsection

@push('styles')
<style>
    /* ── iOS-style Dashboard ── */

    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        margin-top: 40px;
        padding-bottom: 0;
        border-bottom: none;
    }
    .section-header:first-child { margin-top: 0; }
    .section-title {
        font-size: 20px;
        color: var(--gray-text);
        font-weight: 700;
        letter-spacing: -0.4px;
    }
    .section-sub {
        font-size: 12px;
        color: var(--gray-muted);
        margin-left: auto;
        font-weight: 500;
    }

    /* ── Metric Cards (iOS widget style) ── */
    .metrics-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .metric-card {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: 20px 22px;
        border: none;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .metric-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    .metric-card .accent {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }
    .metric-label {
        font-size: 12px;
        color: var(--gray-muted);
        font-weight: 600;
        margin-bottom: 8px;
        letter-spacing: -0.1px;
    }
    .metric-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--gray-text);
        line-height: 1;
        letter-spacing: -0.5px;
    }
    .metric-sub {
        font-size: 12px;
        color: var(--gray-muted);
        margin-top: 6px;
        font-weight: 400;
    }

    /* ── Cards (iOS grouped style) ── */
    .card {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: none;
        overflow: hidden;
        margin-bottom: 16px;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .card:hover {
        box-shadow: var(--shadow-md);
    }
    .card-head {
        padding: 16px 22px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .card-head h3 {
        font-size: 15px;
        font-weight: 700;
        color: var(--gray-text);
        letter-spacing: -0.2px;
    }
    .ver-todo {
        font-size: 13px;
        color: var(--blue);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
    }
    .ver-todo:hover { opacity: 0.7; }

    .card-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* ── Filtros (iOS style inputs) ── */
    .filtro-fechas {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .filtro-fechas input[type="date"] {
        border: 1px solid var(--border-light);
        border-radius: 10px;
        padding: 7px 12px;
        font-size: 13px;
        font-family: inherit;
        color: var(--gray-text);
        outline: none;
        background: var(--gray-soft);
        transition: var(--transition);
    }
    .filtro-fechas input[type="date"]:focus {
        border-color: var(--purple);
        background: var(--white);
        box-shadow: 0 0 0 3px rgba(107,63,160,0.12);
    }

    /* ── Buttons (iOS pill style) ── */
    .btn-filtrar {
        padding: 7px 18px;
        background: var(--purple);
        color: white;
        border: none;
        border-radius: 20px;
        font-size: 13px;
        font-family: inherit;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
        letter-spacing: -0.1px;
    }
    .btn-filtrar:hover {
        background: var(--purple-dark);
        transform: scale(1.03);
    }
    .btn-filtrar:active { transform: scale(0.97); }

    .btn-limpiar {
        padding: 7px 18px;
        background: var(--gray-soft);
        color: var(--gray-muted);
        border: 1px solid var(--border-light);
        border-radius: 20px;
        font-size: 13px;
        font-family: inherit;
        cursor: pointer;
        font-weight: 500;
        transition: var(--transition);
    }
    .btn-limpiar:hover {
        background: var(--purple-light);
        color: var(--purple);
        border-color: var(--purple-mid);
    }

    .btn-excel {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 18px;
        background: var(--green);
        color: white;
        border: none;
        border-radius: 20px;
        font-size: 13px;
        font-family: inherit;
        cursor: pointer;
        font-weight: 600;
        transition: var(--transition);
    }
    .btn-excel:hover {
        opacity: 0.85;
        transform: scale(1.03);
    }
    .btn-excel:active { transform: scale(0.97); }

    /* ── Tables (iOS list style) ── */
    .tabla {
        width: 100%;
        border-collapse: collapse;
    }
    .tabla th {
        font-size: 11px;
        font-weight: 600;
        color: var(--gray-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 22px;
        text-align: left;
        background: var(--gray-soft);
        border-bottom: 1px solid var(--border-light);
    }
    .tabla td {
        padding: 14px 22px;
        font-size: 14px;
        color: var(--gray-text);
        border-bottom: 1px solid var(--border-light);
    }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr {
        transition: var(--transition);
    }
    .tabla tr:hover td {
        background: var(--gray-soft);
    }
    .empty-row td {
        text-align: center;
        color: var(--gray-muted);
        padding: 40px;
        font-size: 14px;
        font-weight: 500;
    }

    /* ── Status list (iOS notification style) ── */
    .estatus-list { padding: 0; }
    .estatus-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 22px;
        border-bottom: 1px solid var(--border-light);
        transition: var(--transition);
    }
    .estatus-item:last-child { border-bottom: none; }
    .estatus-item:hover {
        background: var(--gray-soft);
    }
    .estatus-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .dot-green { background: var(--green); box-shadow: 0 0 8px rgba(52,199,89,0.4); animation: pulse-ios 2.5s ease-in-out infinite; }
    .dot-amber { background: var(--amber); box-shadow: 0 0 8px rgba(255,159,10,0.4); animation: pulse-ios 2.5s ease-in-out infinite; }
    .dot-blue  { background: var(--blue);  box-shadow: 0 0 8px rgba(0,122,255,0.4);  animation: pulse-ios 2.5s ease-in-out infinite; }
    .dot-gray  { background: var(--gray-muted); }

    @keyframes pulse-ios {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.85); }
    }

    .estatus-info { flex: 1; }
    .estatus-info .titulo {
        font-size: 14px;
        font-weight: 600;
        color: var(--gray-text);
        letter-spacing: -0.2px;
    }
    .estatus-info .sub {
        font-size: 13px;
        color: var(--gray-muted);
        margin-top: 2px;
    }
    .estatus-time {
        font-size: 12px;
        color: var(--gray-muted);
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .metrics-row { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

    {{-- FACTURAS --}}
    <div class="section-header">
        <div class="section-title">Facturas</div>
        <span class="section-sub">Pendiente de API</span>
    </div>

    <div class="metrics-row">
        <div class="metric-card">
            <div class="accent" style="background:var(--purple)"></div>
            <div class="metric-label">Facturas pendientes</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--amber)"></div>
            <div class="metric-label">Facturas en revisión</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--green)"></div>
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
                <button class="btn-excel" onclick="exportarExcel('tablaFacturas','facturas')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar
                </button>
            </div>
        </div>
        <table class="tabla" id="tablaFacturas">
            <thead><tr><th>Folio</th><th>Fecha</th><th>OC relacionada</th><th>Monto</th><th>Estatus</th></tr></thead>
            <tbody><tr class="empty-row"><td colspan="5">Sin datos — Pendiente de conexión con API</td></tr></tbody>
        </table>
    </div>

    {{-- PAGOS --}}
    <div class="section-header">
        <div class="section-title">Pagos</div>
        <span class="section-sub">Pendiente de API</span>
    </div>

    <div class="metrics-row">
        <div class="metric-card">
            <div class="accent" style="background:var(--blue)"></div>
            <div class="metric-label">Pagos programados</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--green)"></div>
            <div class="metric-label">Pagos realizados</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--amber)"></div>
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
                <button class="btn-excel" onclick="exportarExcel('tablaPagos','historial-pagos')">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar
                </button>
            </div>
        </div>
        <table class="tabla" id="tablaPagos">
            <thead><tr><th>Referencia</th><th>Factura</th><th>Fecha programada</th><th>Monto</th><th>Estatus</th></tr></thead>
            <tbody><tr class="empty-row"><td colspan="5">Sin datos — Pendiente de conexión con API</td></tr></tbody>
        </table>
    </div>

    {{-- ESTATUS EN TIEMPO REAL --}}
    <div class="section-header">
        <div class="section-title">Estatus en tiempo real</div>
        <span class="section-sub" style="color:var(--green);font-weight:600;display:flex;align-items:center;gap:6px;">
            <span style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block;box-shadow:0 0 8px rgba(52,199,89,0.5);"></span>
            En vivo
        </span>
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

@endsection

@push('scripts')
<script>
function exportarExcel(tablaId, nombre) {
    const tabla = document.getElementById(tablaId);
    if (!tabla) return;
    let csv = '';
    tabla.querySelectorAll('tr').forEach(fila => {
        if (fila.classList.contains('empty-row')) return;
        const data = Array.from(fila.querySelectorAll('th,td')).map(c => '"' + c.textContent.trim().replace(/"/g,'""') + '"');
        csv += data.join(',') + '\n';
    });
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = nombre + '-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

function filtrarPagos() {
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    document.querySelectorAll('#tablaPagos tbody tr').forEach(fila => {
        const td = fila.querySelector('td:nth-child(3)');
        if (!td) return;
        if (!desde && !hasta) { fila.style.display = ''; return; }
        const p = td.textContent.trim().split('/');
        if (p.length !== 3) return;
        const f = new Date(`${p[2]}-${p[1]}-${p[0]}`);
        let ok = true;
        if (desde && f < new Date(desde)) ok = false;
        if (hasta && f > new Date(hasta)) ok = false;
        fila.style.display = ok ? '' : 'none';
    });
}

function limpiarFiltro() {
    document.getElementById('fechaDesde').value = '';
    document.getElementById('fechaHasta').value = '';
    document.querySelectorAll('#tablaPagos tbody tr').forEach(f => f.style.display = '');
}
</script>
@endpush
