@extends('layouts.proveedor')

@section('title', 'Consultar OC')

@section('hero')
<div class="hero-band">
    <h1>Consultar Órdenes de Compra</h1>
    <p>Revisa tus órdenes de compra, cantidades, precios y condiciones</p>
</div>
@endsection

@push('styles')
<style>
    .search-bar { display: flex; gap: 12px; margin-bottom: 28px; }
    .search-input { flex: 1; border: 1.5px solid var(--border); border-radius: 10px; padding: 11px 16px; font-size: 14px; font-family: 'Nunito', sans-serif; color: var(--gray-text); background: var(--white); outline: none; transition: border-color .2s, box-shadow .2s; }
    .search-input::placeholder { color: #BDB8CC; }
    .search-input:focus { border-color: var(--purple-mid); box-shadow: 0 0 0 3px rgba(156,109,208,0.12); }
    .btn-search { padding: 11px 24px; background: var(--purple); color: var(--white); border: none; border-radius: 10px; font-family: 'Nunito', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer; transition: background .2s; }
    .btn-search:hover { background: var(--purple-dark); }

    .metrics-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
    .metric-card { background: var(--white); border-radius: 12px; padding: 18px 20px; border: 0.5px solid var(--border); position: relative; overflow: hidden; }
    .metric-card .accent { position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 12px 0 0 12px; }
    .metric-label { font-size: 12px; color: var(--gray-text); font-weight: 500; margin-bottom: 6px; padding-left: 8px; }
    .metric-value { font-size: 26px; font-weight: 600; color: var(--purple-dark); padding-left: 8px; line-height: 1; }
    .metric-sub { font-size: 11px; color: #AAA; padding-left: 8px; margin-top: 4px; }

    .card { background: var(--white); border-radius: 14px; border: 0.5px solid var(--border); overflow: hidden; }
    .card-head { padding: 14px 20px; border-bottom: 0.5px solid var(--border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .card-head h3 { font-size: 14px; font-weight: 600; color: var(--purple-dark); }
    .card-head-right { display: flex; align-items: center; gap: 10px; }
    .badge-api { font-size: 11px; color: var(--amber); font-weight: 600; background: var(--amber-bg); padding: 3px 10px; border-radius: 999px; }
    .btn-excel { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; background: #16a34a; color: white; border: none; border-radius: 8px; font-size: 12px; font-family: inherit; cursor: pointer; font-weight: 600; transition: background .2s; }
    .btn-excel:hover { background: #15803d; }

    .tabla { width: 100%; border-collapse: collapse; }
    .tabla th { font-size: 11px; font-weight: 700; color: #AAA; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 20px; text-align: left; background: var(--gray-soft); border-bottom: 0.5px solid var(--border); }
    .tabla td { padding: 12px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 0.5px solid var(--border); }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: var(--gray-soft); cursor: pointer; }

    .badge { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
    .badge-green  { background: var(--green-bg); color: var(--green); }
    .badge-amber  { background: var(--amber-bg); color: var(--amber); }
    .badge-blue   { background: var(--blue-bg);  color: var(--blue); }

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

    @media (max-width: 768px) { .metrics-row { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')

    {{-- ═══ OC Sugerida ═══ --}}
    <div style="background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 22px; margin-bottom: 28px; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 14px; font-weight: 700; color: var(--gray-text); margin-bottom: 16px;">Generación automática de OC sugerida</h3>
        <div style="background: var(--purple-subtle); border-radius: 10px; padding: 14px 18px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: var(--gray-muted); font-weight: 600; margin-bottom: 4px;">Fórmula</div>
            <div style="font-size: 13px; color: var(--gray-text); font-weight: 600;">OC sugerida = (Consumo promedio anual / 2) + Necesidades adicionales</div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div>
                <div style="font-size: 11px; color: var(--gray-muted); font-weight: 600;">Consumo promedio anual</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--gray-text); margin-top: 4px;">$2,000,000 <span style="font-size: 11px; color: var(--gray-muted); font-weight: 500;">MXN</span></div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--gray-muted); font-weight: 600;">Inventario autorizado</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--gray-text); margin-top: 4px;">~180 <span style="font-size: 11px; color: var(--gray-muted); font-weight: 500;">días</span></div>
            </div>
            <div>
                <div style="font-size: 11px; color: var(--gray-muted); font-weight: 600;">OC sugerida</div>
                <div style="font-size: 18px; font-weight: 700; color: var(--purple); margin-top: 4px;">~$1,200,000 <span style="font-size: 11px; color: var(--gray-muted); font-weight: 500;">MXN</span></div>
            </div>
        </div>
        <button disabled style="display: inline-block; padding: 8px 20px; background: var(--purple); color: var(--white); font-size: 12px; font-weight: 600; border-radius: 999px; border: none; cursor: not-allowed; opacity: 0.6;">Generar OC — Pendiente de formato</button>
    </div>

    {{-- BUSCADOR --}}
    <div class="search-bar">
        <input type="text" class="search-input" id="buscarFolio" placeholder="Buscar por número de folio...">
        <button class="btn-search" onclick="buscarOC()">Buscar</button>
    </div>

    {{-- MÉTRICAS --}}
    <div class="metrics-row">
        <div class="metric-card">
            <div class="accent" style="background:var(--purple)"></div>
            <div class="metric-label">OC Abiertas</div>
            <div class="metric-value">3</div>
            <div class="metric-sub">Datos de prueba</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--green)"></div>
            <div class="metric-label">OC Completadas</div>
            <div class="metric-value">8</div>
            <div class="metric-sub">Datos de prueba</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--amber)"></div>
            <div class="metric-label">OC En proceso</div>
            <div class="metric-value">2</div>
            <div class="metric-sub">Datos de prueba</div>
        </div>
        <div class="metric-card">
            <div class="accent" style="background:var(--blue)"></div>
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
        <div style="overflow-x:auto;">
        <table class="tabla" id="tablaOC">
            <thead>
                <tr><th>Folio</th><th>Fecha</th><th>Cód. Proveedor</th><th>Referencia</th><th>Productos</th><th>Importe</th><th>Vencimiento</th><th>Observaciones</th><th>Estatus</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>#10045</strong></td>
                    <td>01/03/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">102003240</td>
                    <td>REF-2026-001</td>
                    <td style="font-size:12px;"><strong>PROD-001</strong> Resina epóxica (10u)<br><strong>PROD-002</strong> Solvente técnico (5u)</td>
                    <td style="font-weight:600;">$12,500.00</td>
                    <td>31/03/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">Entrega en almacén central</td>
                    <td><span class="badge badge-amber">En proceso</span></td>
                </tr>
                <tr>
                    <td><strong>#10046</strong></td>
                    <td>05/03/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">102003240</td>
                    <td>REF-2026-002</td>
                    <td style="font-size:12px;"><strong>PROD-003</strong> Pigmento base agua (20u)</td>
                    <td style="font-weight:600;">$8,200.00</td>
                    <td>05/04/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">—</td>
                    <td><span class="badge badge-blue">Abierta</span></td>
                </tr>
                <tr>
                    <td><strong>#10047</strong></td>
                    <td>10/03/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">102003240</td>
                    <td>REF-2026-003</td>
                    <td style="font-size:12px;"><strong>PROD-001</strong> Resina epóxica (15u)<br><strong>PROD-004</strong> Catalizador rápido (30u)</td>
                    <td style="font-weight:600;">$27,300.00</td>
                    <td>10/04/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">Urgente, prioridad alta</td>
                    <td><span class="badge badge-green">Completada</span></td>
                </tr>
                <tr>
                    <td><strong>#10048</strong></td>
                    <td>15/03/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">102003240</td>
                    <td>REF-2026-004</td>
                    <td style="font-size:12px;"><strong>PROD-005</strong> Aditivo antioxidante (8u)</td>
                    <td style="font-weight:600;">$5,800.00</td>
                    <td>15/04/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">—</td>
                    <td><span class="badge badge-amber">En proceso</span></td>
                </tr>
                <tr>
                    <td><strong>#10049</strong></td>
                    <td>20/03/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">102003240</td>
                    <td>REF-2026-005</td>
                    <td style="font-size:12px;"><strong>PROD-002</strong> Solvente técnico (10u)<br><strong>PROD-003</strong> Pigmento base agua (15u)</td>
                    <td style="font-weight:600;">$15,100.00</td>
                    <td>20/04/2026</td>
                    <td style="font-size:12px;color:var(--gray-muted);">Verificar existencias</td>
                    <td><span class="badge badge-blue">Abierta</span></td>
                </tr>
            </tbody>
        </table>
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
        const data = Array.from(fila.querySelectorAll('th,td')).map(c => '"' + c.textContent.trim().replace(/"/g,'""').replace(/\n/g,' ') + '"');
        csv += data.join(',') + '\n';
    });
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = nombre + '-' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

function buscarOC() {
    const folio = document.getElementById('buscarFolio').value.toLowerCase();
    document.querySelectorAll('#tablaOC tbody tr').forEach(fila => {
        fila.style.display = fila.textContent.toLowerCase().includes(folio) ? '' : 'none';
    });
}
document.getElementById('buscarFolio').addEventListener('keyup', e => { if (e.key === 'Enter') buscarOC(); });
</script>
@endpush