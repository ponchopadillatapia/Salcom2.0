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
    <div style="margin:0 0 24px;display:flex;align-items:center;">
        <div style="height:3px;flex:1;background:var(--purple);border-radius:2px;"></div>
    </div>

    <div class="section-header">
        <div class="section-title" style="font-size:20px;font-weight:800;">Facturas</div>
        <span class="section-sub">Haz click en cada etapa para ver el detalle</span>
    </div>

    <div class="metrics-row" style="grid-template-columns: repeat(5, 1fr);">
        <div class="metric-card" style="cursor:pointer;transition:all .15s;" onclick="toggleDetalle('det-incomings', this)">
            <div class="accent" style="background:var(--amber)"></div>
            <div class="metric-label">Pendientes de aprobación (Incomings)</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card" style="cursor:pointer;transition:all .15s;" onclick="toggleDetalle('det-fiscal', this)">
            <div class="accent" style="background:var(--red)"></div>
            <div class="metric-label">Cumplimiento fiscal</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card" style="cursor:pointer;transition:all .15s;" onclick="toggleDetalle('det-sat', this)">
            <div class="accent" style="background:var(--purple)"></div>
            <div class="metric-label">Compulsa contra el SAT</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card" style="cursor:pointer;transition:all .15s;" onclick="toggleDetalle('det-revision', this)">
            <div class="accent" style="background:var(--blue)"></div>
            <div class="metric-label">En revisión</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card" style="cursor:pointer;transition:all .15s;" onclick="toggleDetalle('det-pago', this)">
            <div class="accent" style="background:var(--green)"></div>
            <div class="metric-label">Listas para pago</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
    </div>

    {{-- Paneles expandibles por cada etapa --}}
    <div id="det-incomings" class="detalle-panel" style="display:none;">
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--amber);">
            <div style="padding:18px 22px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">Pendientes de aprobación (Incomings)</h4>
                <p style="font-size:12px;color:var(--gray-muted);margin-bottom:12px;">Facturas que subiste y están esperando que Salcom las revise y apruebe para continuar el proceso.</p>
                <div style="background:var(--gray-soft);border-radius:8px;padding:16px;text-align:center;color:var(--gray-muted);font-size:13px;">Sin facturas pendientes — Se mostrarán aquí cuando se conecte la API</div>
            </div>
        </div>
    </div>
    <div id="det-fiscal" class="detalle-panel" style="display:none;">
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--red);">
            <div style="padding:18px 22px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">Cumplimiento fiscal</h4>
                <p style="font-size:12px;color:var(--gray-muted);margin-bottom:12px;">Se verifica que tengas tu opinión SAT positiva, CIF vigente y documentos fiscales al día. Si algo falta, la factura se detiene aquí.</p>
                <div style="background:var(--gray-soft);border-radius:8px;padding:16px;text-align:center;color:var(--gray-muted);font-size:13px;">Sin facturas en esta etapa — Se mostrarán aquí cuando se conecte la API</div>
            </div>
        </div>
    </div>
    <div id="det-sat" class="detalle-panel" style="display:none;">
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--purple);">
            <div style="padding:18px 22px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">Compulsa contra el SAT</h4>
                <p style="font-size:12px;color:var(--gray-muted);margin-bottom:12px;">Se valida el CFDI directamente con el SAT para confirmar que la factura es auténtica, no está cancelada y los datos coinciden.</p>
                <div style="background:var(--gray-soft);border-radius:8px;padding:16px;text-align:center;color:var(--gray-muted);font-size:13px;">Sin facturas en compulsa — Se mostrarán aquí cuando se conecte la API</div>
            </div>
        </div>
    </div>
    <div id="det-revision" class="detalle-panel" style="display:none;">
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--blue);">
            <div style="padding:18px 22px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">En revisión (Contabilidad)</h4>
                <p style="font-size:12px;color:var(--gray-muted);margin-bottom:12px;">El área de contabilidad de Salcom revisa montos, conceptos, OC relacionada y da el visto bueno final antes de programar el pago.</p>
                <div style="background:var(--gray-soft);border-radius:8px;padding:16px;text-align:center;color:var(--gray-muted);font-size:13px;">Sin facturas en revisión — Se mostrarán aquí cuando se conecte la API</div>
            </div>
        </div>
    </div>
    <div id="det-pago" class="detalle-panel" style="display:none;">
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--green);">
            <div style="padding:18px 22px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">Listas para pago</h4>
                <p style="font-size:12px;color:var(--gray-muted);margin-bottom:12px;">Facturas aprobadas en todas las etapas. Finanzas programa la fecha de pago según los días de crédito del proveedor.</p>
                <div style="background:var(--gray-soft);border-radius:8px;padding:16px;text-align:center;color:var(--gray-muted);font-size:13px;">Sin facturas listas — Se mostrarán aquí cuando se conecte la API</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>Revisión</h3>
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
        <div style="padding:12px 22px;font-size:11px;color:var(--gray-muted);border-top:1px solid var(--border-light)">
            Flujo de estatus: Liberación Incomings → Cumplimiento Fiscal → Compulsa SAT → Liberación Contabilidad → Finanzas
        </div>
        <div style="padding:16px 22px;border-top:1px solid var(--border-light);">
            <div style="font-size:12px;font-weight:700;color:var(--gray-text);margin-bottom:10px;">Estatus — Columna de estados posibles</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:var(--red);flex-shrink:0;"></span>
                    <span style="font-weight:600;color:var(--gray-text);">Liberación — Incomings</span>
                    <span style="font-size:11px;color:var(--red);font-weight:600;margin-left:auto;">Pendiente</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:var(--red);flex-shrink:0;"></span>
                    <span style="font-weight:600;color:var(--gray-text);">Cumplimiento fiscal</span>
                    <span style="font-size:11px;color:var(--red);font-weight:600;margin-left:auto;">Pendiente</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:var(--red);flex-shrink:0;"></span>
                    <span style="font-weight:600;color:var(--gray-text);">Proceso SAT interno</span>
                    <span style="font-size:11px;color:var(--red);font-weight:600;margin-left:auto;">Pendiente</span>
                </div>
                <div style="display:flex;align-items:center;gap:10px;font-size:13px;">
                    <span style="width:10px;height:10px;border-radius:50%;background:var(--green);flex-shrink:0;"></span>
                    <span style="font-weight:600;color:var(--gray-text);">Liberación Contabilidad / Finanzas</span>
                    <span style="font-size:11px;color:var(--green);font-weight:600;margin-left:auto;">Aprobado</span>
                </div>
            </div>
        </div>
    </div>

    {{-- SEPARADOR VISUAL FACTURAS/PAGOS --}}
    <div style="margin:36px 0 24px;display:flex;align-items:center;">
        <div style="height:3px;flex:1;background:var(--purple);border-radius:2px;"></div>
    </div>

    {{-- PAGOS --}}
    <div class="section-header">
        <div class="section-title" style="font-size:20px;font-weight:800;">Pagos</div>
        <span class="section-sub">Pendiente de API</span>
    </div>

    <div class="metrics-row">
        <div class="metric-card" style="cursor:pointer;transition:all .15s;" onclick="toggleDetalle('det-pagos-prog', this)">
            <div class="accent" style="background:var(--blue)"></div>
            <div class="metric-label">Pagos programados</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card" style="cursor:pointer;transition:all .15s;" onclick="toggleDetalle('det-pagos-real', this)">
            <div class="accent" style="background:var(--green)"></div>
            <div class="metric-label">Pagos realizados</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
        <div class="metric-card" style="cursor:pointer;transition:all .15s;" onclick="toggleDetalle('det-pagos-pend', this)">
            <div class="accent" style="background:var(--amber)"></div>
            <div class="metric-label">Monto pendiente</div>
            <div class="metric-value">—</div>
            <div class="metric-sub">Pendiente de API</div>
        </div>
    </div>

    {{-- Paneles expandibles de pagos --}}
    <div id="det-pagos-prog" class="detalle-panel" style="display:none;">
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--blue);">
            <div style="padding:18px 22px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">Pagos programados</h4>
                <p style="font-size:12px;color:var(--gray-muted);margin-bottom:12px;">Facturas aprobadas con fecha de pago asignada. Aquí verás cuándo recibirás cada pago.</p>
                <div style="background:var(--gray-soft);border-radius:8px;padding:16px;text-align:center;color:var(--gray-muted);font-size:13px;">Sin pagos programados — Se mostrarán aquí cuando se conecte la API</div>
            </div>
        </div>
    </div>
    <div id="det-pagos-real" class="detalle-panel" style="display:none;">
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--green);">
            <div style="padding:18px 22px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">Pagos realizados</h4>
                <p style="font-size:12px;color:var(--gray-muted);margin-bottom:12px;">Transferencias ya ejecutadas. Aquí verás el comprobante de pago y la fecha en que se depositó.</p>
                <div style="background:var(--gray-soft);border-radius:8px;padding:16px;text-align:center;color:var(--gray-muted);font-size:13px;">Sin pagos realizados — Se mostrarán aquí cuando se conecte la API</div>
            </div>
        </div>
    </div>
    <div id="det-pagos-pend" class="detalle-panel" style="display:none;">
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--amber);">
            <div style="padding:18px 22px;">
                <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:8px;">Monto pendiente</h4>
                <p style="font-size:12px;color:var(--gray-muted);margin-bottom:12px;">Total que Salcom te debe. Incluye facturas aprobadas que aún no se pagan.</p>
                <div style="background:var(--gray-soft);border-radius:8px;padding:16px;text-align:center;color:var(--gray-muted);font-size:13px;">$0.00 pendiente — Se actualizará cuando se conecte la API</div>
            </div>
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

@endsection

@push('scripts')
<script>
function toggleDetalle(id, card) {
    var panel = document.getElementById(id);
    if (!panel) return;
    var isVisible = panel.style.display !== 'none';

    // Cerrar todos los paneles
    document.querySelectorAll('.detalle-panel').forEach(p => p.style.display = 'none');
    // Quitar resaltado de todas las cards
    document.querySelectorAll('.metric-card').forEach(c => { c.style.boxShadow = ''; c.style.border = ''; });

    if (!isVisible) {
        panel.style.display = 'block';
        if (card) {
            card.style.boxShadow = '0 0 0 2px var(--purple)';
            card.style.border = '1.5px solid var(--purple)';
        }
    }
}

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
