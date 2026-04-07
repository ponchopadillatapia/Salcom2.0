@extends('layouts.proveedor')

@section('title', 'Dashboard')

@section('hero')
<div class="hero-band">
    <h1>Bienvenido, {{ session('proveedor_nombre', 'Proveedor') }}</h1>
    <p>Código: {{ session('proveedor_codigo', '—') }} — {{ now()->format('d/m/Y') }}</p>
</div>
@endsection

@push('styles')
<style>
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
    .ver-todo { font-size: 12px; color: var(--purple-mid); text-decoration: none; font-weight: 500; }
    .ver-todo:hover { text-decoration: underline; }
    .card-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .filtro-fechas { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .filtro-fechas input[type="date"] { border: 1px solid var(--border); border-radius: 8px; padding: 5px 10px; font-size: 12px; font-family: inherit; color: var(--gray-text); outline: none; }
    .btn-filtrar { padding: 5px 14px; background: var(--purple); color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; }
    .btn-filtrar:hover { background: var(--purple-dark); }
    .btn-limpiar { padding: 5px 14px; background: var(--gray-soft); color: var(--gray-text); border: 1px solid var(--border); border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; }
    .btn-limpiar:hover { background: var(--purple-light); color: var(--purple); }
    .btn-excel { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: #16a34a; color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; }
    .btn-excel:hover { background: #15803d; }

    .tabla { width: 100%; border-collapse: collapse; }
    .tabla th { font-size: 11px; font-weight: 700; color: #AAA; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; text-align: left; background: var(--gray-soft); border-bottom: 0.5px solid var(--border); }
    .tabla td { padding: 12px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 0.5px solid var(--border); }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: var(--gray-soft); }
    .empty-row td { text-align: center; color: #CCC; padding: 28px; font-size: 13px; }

    .estatus-list { padding: 0; }
    .estatus-item { display: flex; align-items: center; gap: 14px; padding: 14px 20px; border-bottom: 0.5px solid var(--border); transition: background .15s; }
    .estatus-item:last-child { border-bottom: none; }
    .estatus-item:hover { background: var(--gray-soft); }
    .estatus-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .dot-green { background: var(--green); animation: pulse 2s ease-in-out infinite; }
    .dot-amber { background: var(--amber); animation: pulse 2s ease-in-out infinite; }
    .dot-blue  { background: var(--blue);  animation: pulse 2s ease-in-out infinite; }
    .dot-gray  { background: #CCC; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
    .estatus-info { flex: 1; }
    .estatus-info .titulo { font-size: 13px; font-weight: 600; color: var(--gray-text); }
    .estatus-info .sub { font-size: 12px; color: #AAA; margin-top: 2px; }
    .estatus-time { font-size: 11px; color: #CCC; }

    @media (max-width: 768px) { .metrics-row { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')

    {{-- FACTURAS --}}
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
                    Exportar Excel
                </button>
            </div>
        </div>
        <table class="tabla" id="tablaFacturas">
            <thead><tr><th>Folio</th><th>Fecha</th><th>OC relacionada</th><th>Monto</th><th>Estatus</th></tr></thead>
            <tbody><tr class="empty-row"><td colspan="5">Pendiente de API</td></tr></tbody>
        </table>
    </div>

    {{-- PAGOS --}}
    <div class="section-header">
        <div class="section-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>
            </svg>
        </div>
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
                    Exportar Excel
                </button>
            </div>
        </div>
        <table class="tabla" id="tablaPagos">
            <thead><tr><th>Referencia</th><th>Factura</th><th>Fecha programada</th><th>Monto</th><th>Estatus</th></tr></thead>
            <tbody><tr class="empty-row"><td colspan="5">Pendiente de API</td></tr></tbody>
        </table>
    </div>

    {{-- ESTATUS --}}
    <div class="section-header">
        <div class="section-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="section-title">Estatus en tiempo real</div>
        <span class="section-sub" style="color:var(--green);font-weight:600;">● En vivo</span>
    </div>

    <div class="card">
        <div class="estatus-list">
            <div class="estatus-item">
                <div class="estatus-dot dot-green"></div>
                <div class="estatus-info"><div class="titulo">OC generada</div><div class="sub">Salcom generó una orden de compra</div></div>
                <div class="estatus-time">Pendiente de API</div>
            </div>
            <div class="estatus-item">
                <div class="estatus-dot dot-amber"></div>
                <div class="estatus-info"><div class="titulo">Factura en revisión</div><div class="sub">Tu factura está siendo validada</div></div>
                <div class="estatus-time">Pendiente de API</div>
            </div>
            <div class="estatus-item">
                <div class="estatus-dot dot-blue"></div>
                <div class="estatus-info"><div class="titulo">Pago programado</div><div class="sub">Tu pago tiene fecha asignada</div></div>
                <div class="estatus-time">Pendiente de API</div>
            </div>
            <div class="estatus-item">
                <div class="estatus-dot dot-gray"></div>
                <div class="estatus-info"><div class="titulo">Historial de pagos</div><div class="sub">Consulta el historial de todos tus pagos</div></div>
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