@extends('layouts.proveedor')

@section('title', 'Inicio')

{{-- sin hero: el nombre ya va en la barra superior --}}

@push('styles')
<style>
    .pp-wrap {
        max-width: 1140px;
        margin: 0 auto;
    }

    /* ── Section grids ── */
    .pp-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 14px;
    }

    /* ── Card base ── */
    .pp-card {
        background: var(--white);
        border: 2px solid var(--purple);
        border-radius: var(--radius-lg);
        padding: 22px;
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }
    .pp-card:hover {
        border-color: var(--purple-dark);
        box-shadow: var(--shadow-md);
    }
    .pp-card h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--gray-text);
        margin-bottom: 16px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    /* ── Negocio card ── */
    .pp-negocio-row {
        margin-bottom: 12px;
    }
    .pp-negocio-label {
        font-size: 12px;
        color: var(--gray-muted);
        font-weight: 500;
        margin-bottom: 2px;
    }
    .pp-negocio-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--gray-text);
        display: flex;
        align-items: baseline;
        gap: 10px;
    }
    .pp-negocio-sub {
        font-size: 12px;
        color: var(--gray-muted);
        margin-top: 4px;
        font-weight: 400;
        line-height: 1.35;
    }
    .pp-variation {
        font-size: 20px;
        font-weight: 700;
    }
    .pp-variation-up {
        color: var(--green);
    }
    .pp-variation-down {
        color: var(--red);
    }

    /* ── OTIF card ── */
    .pp-otif-wrap {
        display: flex;
        gap: 32px;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }
    .pp-otif-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .pp-otif-canvas-wrap {
        position: relative;
        width: 100px;
        height: 100px;
    }
    .pp-otif-canvas-wrap canvas {
        position: absolute;
        top: 0;
        left: 0;
    }
    .pp-otif-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    .pp-otif-percent {
        font-size: 18px;
        font-weight: 700;
        color: var(--green);
        line-height: 1;
    }
    .pp-otif-label {
        font-size: 11px;
        color: var(--gray-muted);
        font-weight: 600;
        margin-top: 4px;
    }

    /* ── Detail link ── */
    .pp-detail-link {
        font-size: 13px;
        color: var(--blue);
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        margin-top: 8px;
    }
    .pp-detail-link:hover {
        text-decoration: underline;
    }

    /* ── Activity list ── */
    .pp-list-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
    }
    .pp-list-item:last-child {
        border-bottom: none;
    }
    .pp-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .pp-dot-green { background: var(--green); }
    .pp-dot-amber { background: var(--amber); }
    .pp-dot-red { background: var(--red); }
    .pp-list-text {
        flex: 1;
        color: var(--gray-text);
        font-weight: 500;
    }
    .pp-list-status {
        font-size: 11px;
        color: var(--gray-muted);
        font-weight: 500;
    }

    /* ── Pill button ── */
    .pp-btn-pill {
        display: inline-block;
        margin-top: 14px;
        padding: 8px 20px;
        background: var(--purple);
        color: var(--white);
        font-size: 12px;
        font-weight: 600;
        border-radius: var(--radius-pill);
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: var(--transition);
    }
    .pp-btn-pill:hover {
        background: var(--purple-dark);
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    /* ── Onboarding progress ── */
    .pp-onboarding-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .pp-onboarding-progress {
        font-size: 22px;
        font-weight: 700;
    }
    .pp-onboarding-steps {
        font-size: 12px;
        color: var(--gray-muted);
        margin-bottom: 12px;
    }

    /* ── OC Sugerida formula ── */
    .pp-formula-box {
        background: var(--purple-subtle);
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 16px;
    }
    .pp-formula-label {
        font-size: 11px;
        color: var(--gray-muted);
        font-weight: 600;
        margin-bottom: 4px;
    }
    .pp-formula-text {
        font-size: 13px;
        color: var(--gray-text);
        font-weight: 600;
    }
    .pp-formula-values {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 16px;
    }
    .pp-formula-val-label {
        font-size: 11px;
        color: var(--gray-muted);
        font-weight: 600;
    }
    .pp-formula-val-num {
        font-size: 18px;
        font-weight: 700;
        color: var(--gray-text);
        margin-top: 4px;
    }
    .pp-formula-val-num span {
        font-size: 11px;
        color: var(--gray-muted);
        font-weight: 500;
    }
    .pp-formula-val-num.pp-purple {
        color: var(--purple);
    }

    /* ── Totales expandable ── */
    .pp-totales-summary {
        padding: 16px 22px;
        font-size: 14px;
        font-weight: 700;
        color: var(--gray-text);
        list-style: none;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .pp-totales-summary::-webkit-details-marker { display: none; }
    .pp-totales-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .pp-totales-table th {
        text-align: left;
        padding: 10px 10px;
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--border-light);
    }
    .pp-totales-table th:nth-child(n+3) { text-align: right; }
    .pp-totales-table td {
        padding: 10px 10px;
        border-bottom: 1px solid var(--border-light);
    }
    .pp-totales-table td:nth-child(n+3) { text-align: right; }
    .pp-totales-table tr:last-child td { border-bottom: none; }

    /* ── Productos no entregados ── */
    .pp-fail-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid var(--border-light);
    }
    .pp-fail-item:last-child { border-bottom: none; }
    .pp-fail-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--red);
        flex-shrink: 0;
    }
    .pp-fail-name {
        flex: 1;
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-text);
    }
    .pp-fail-reason {
        font-size: 12px;
        color: var(--red);
        font-weight: 700;
    }

    /* ── Quick access grid ── */
    .pp-quick-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
    .pp-quick-card {
        min-height: auto;
        max-height: none;
    }
    .pp-quick-card {
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px !important;
    }
    .pp-quick-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: var(--purple-light);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: var(--transition);
    }
    .pp-quick-card:hover .pp-quick-icon {
        background: var(--purple);
        box-shadow: 0 2px 8px rgba(107,63,160,0.25);
    }
    .pp-quick-card:hover .pp-quick-icon svg {
        stroke: white;
    }
    .pp-quick-title {
        font-weight: 600;
        color: var(--gray-text);
        font-size: 13px;
    }
    .pp-quick-sub {
        font-size: 11px;
        color: var(--gray-muted);
        margin-top: 2px;
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
        .pp-grid-2 {
            grid-template-columns: 1fr !important;
        }
        .pp-quick-grid {
            grid-template-columns: 1fr 1fr;
        }
        .pp-formula-values {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .pp-otif-wrap {
            gap: 20px;
        }
    }
    @media (max-width: 1024px) and (min-width: 769px) {
        .pp-grid-2 {
            grid-template-columns: 1fr 1fr !important;
        }
    }
    .pp-kpi-section{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:14px}
    .pp-kpi-section>a{display:flex;text-decoration:none;color:inherit;min-height:0}
    .pp-kpi-section .pp-card{flex:1;width:100%;min-height:188px;max-height:188px;padding:16px 18px;overflow:hidden;box-sizing:border-box;display:flex;flex-direction:column;font-family:'Inter',-apple-system,BlinkMacSystemFont,'SF Pro Display',sans-serif}
    .pp-kpi-section .pp-card h4{margin-bottom:10px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;line-height:1.2}
    .pp-kpi-section .pp-negocio-row{margin-bottom:8px}
    .pp-kpi-section .pp-negocio-row:last-of-type{margin-bottom:0}
    .pp-kpi-section .pp-negocio-label{font-size:12px;font-weight:500;line-height:1.2}
    .pp-kpi-section .pp-negocio-value{font-size:20px;font-weight:700;line-height:1.1}
    .pp-kpi-section .pp-negocio-sub{font-size:12px;font-weight:400;line-height:1.35;margin-top:4px}
    .pp-kpi-section .pp-detail-link{font-size:13px;font-weight:600;margin-top:8px}
    .pp-kpi-gauges{display:flex;gap:12px;align-items:center;justify-content:center;min-height:88px}
    .pp-kpi-section .pp-otif-canvas-wrap{position:relative;width:80px;height:80px}
    .pp-kpi-section .pp-otif-canvas-wrap canvas{position:absolute;top:0;left:0}
    .pp-kpi-section .pp-otif-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
    .pp-kpi-section .pp-otif-percent{font-size:14px;font-weight:700;color:var(--green);line-height:1}
    .pp-kpi-section .pp-otif-label{font-size:10px;color:var(--gray-muted);font-weight:600;margin-top:4px}
    .pp-kpi-fact-body{display:flex;align-items:center;justify-content:space-between;gap:8px;flex:1;min-height:0}
    .pp-kpi-fact-stats{flex:1;min-width:0}
    .pp-kpi-fact-stats .pp-negocio-sub{margin:0;font-size:11px;line-height:1.35}
    .pp-chart-wrap-xs{position:relative;width:72px;height:72px;flex-shrink:0}
    .pp-section-title{font-size:15px;font-weight:700;color:var(--gray-text);margin:16px 0 10px;letter-spacing:-0.2px}
    .pp-quick-grid .pp-quick-title{font-size:13px;font-weight:600;line-height:1.2}
    .pp-quick-grid .pp-quick-sub{font-size:11px;font-weight:400;line-height:1.3;margin-top:2px}
    @media (max-width: 768px) {
        .pp-kpi-section{grid-template-columns:1fr 1fr}
        .pp-kpi-section .pp-card{min-height:170px;max-height:none}
    }
    @media (max-width: 480px) {
        .pp-quick-grid {
            grid-template-columns: 1fr;
        }
        .pp-kpi-section{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
<div class="pp-wrap">

    <div class="pp-kpi-section">
        <a href="{{ route('proveedores.alta-producto') }}">
        <div class="pp-card">
            <h4>Alta de producto</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Altas del mes</div>
                <div class="pp-negocio-value" style="color:var(--green);font-size:20px;">{{ $altasProductoMes }}</div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Catálogo activo</div>
                <div class="pp-negocio-value" style="font-size:20px;">{{ $productosActivos }}</div>
            </div>
            <div class="pp-negocio-sub">Sube Excel para dar de alta productos</div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>

        <a href="{{ route('proveedores.facturas') }}">
        <div class="pp-card">
            <h4>Facturas</h4>
            <div class="pp-kpi-fact-body">
                <div class="pp-kpi-fact-stats">
                    <div class="pp-negocio-sub">{{ $facturasPendientes }} {{ $facturasPendientes === 1 ? 'pendiente' : 'pendientes' }}</div>
                    <div class="pp-negocio-sub">${{ number_format($montoPorCobrar, 0) }} por cobrar</div>
                    <div class="pp-negocio-sub" style="margin-top:4px;">
                        <span style="color:var(--green);">●</span> {{ $facturasPagadas }} pagadas &nbsp;
                        <span style="color:var(--amber);">●</span> {{ $facturasPendientes }} pendientes &nbsp;
                        <span style="color:var(--red);">●</span> {{ $facturasCanceladas }} canceladas
                    </div>
                </div>
                <div class="pp-chart-wrap-xs"><canvas id="chartFacturacion"></canvas></div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>

        <a href="{{ route('proveedores.mis-productos') }}">
        <div class="pp-card">
            <h4>Mis productos</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Activos</div>
                <div class="pp-negocio-value" style="font-size:20px;color:var(--green);">{{ $productosActivos }}</div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Inactivos</div>
                <div class="pp-negocio-value" style="font-size:20px;color:var(--red);">{{ $productosInactivos }}</div>
            </div>
            <div class="pp-negocio-sub">Total: {{ $productosActivos + $productosInactivos }}</div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>

        <a href="{{ route('proveedores.fiscal') }}">
        <div class="pp-card">
            <h4>Alta de facturas</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Altas del mes</div>
                <div class="pp-negocio-value" style="color:var(--green);font-size:20px;">{{ $altasFacturaMes }}</div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Total registradas</div>
                <div class="pp-negocio-value" style="font-size:20px;">{{ $facturasTotal }}</div>
            </div>
            <div class="pp-negocio-sub">Sube PDF y XML de la factura</div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>

        <a href="{{ route('proveedores.otif') }}">
        <div class="pp-card">
            <h4>OTIF</h4>
            <div class="pp-kpi-gauges">
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="gaugeOT" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">{{ number_format($otPercent, 1) }}%</div></div></div><div class="pp-otif-label">OT</div></div>
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="gaugeIF" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">{{ number_format($ifPercent, 1) }}%</div></div></div><div class="pp-otif-label">IF</div></div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div>
        </a>
    </div>

    <div class="pp-section-title">Accesos directos</div>
    <div class="pp-quick-grid">
        <a href="{{ route('proveedores.oc') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b3fa0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
            <div><div class="pp-quick-title">OC</div><div class="pp-quick-sub">Órdenes de compra</div></div>
        </a>
        <a href="{{ route('proveedores.forecast') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b3fa0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
            </div>
            <div><div class="pp-quick-title">Forecast</div><div class="pp-quick-sub">Tendencias de compra</div></div>
        </a>
        <a href="{{ route('proveedores.payment-history') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b3fa0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div><div class="pp-quick-title">Pagos</div><div class="pp-quick-sub">Historial y pendientes</div></div>
        </a>
        <a href="{{ route('proveedores.alta-producto') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b3fa0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div><div class="pp-quick-title">Alta de producto</div><div class="pp-quick-sub">Nuevo producto</div></div>
        </a>
        <a href="{{ route('proveedores.mis-productos') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6b3fa0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </div>
            <div><div class="pp-quick-title">Mis productos</div><div class="pp-quick-sub">Catálogo del proveedor</div></div>
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function drawDonut(canvasId, percent) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const size = canvas.width;
        const center = size / 2;
        const radius = size * 0.38;
        const lineWidth = size * 0.12;
        const startAngle = -Math.PI / 2;
        const endAngle = startAngle + (2 * Math.PI * percent / 100);

        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#e8e8ed';
        ctx.lineWidth = lineWidth;
        ctx.stroke();
        if (percent > 0) {
            ctx.beginPath();
            ctx.arc(center, center, radius, startAngle, endAngle);
            ctx.strokeStyle = '#34c759';
            ctx.lineWidth = lineWidth;
            ctx.lineCap = 'round';
            ctx.stroke();
        }
        if (percent < 100) {
            ctx.beginPath();
            ctx.arc(center, center, radius, endAngle + 0.02, startAngle + 2 * Math.PI - 0.02);
            ctx.strokeStyle = percent > 95 ? '#ff9500' : '#ff3b30';
            ctx.lineWidth = lineWidth;
            ctx.lineCap = 'butt';
            ctx.stroke();
        }

    }

    drawDonut('gaugeOT', {{ $otPercent }});
    drawDonut('gaugeIF', {{ $ifPercent }});
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
const SC = SALCOM_COLORS;
const factPagadas = {{ $facturasPagadas }};
const factPendientes = {{ $facturasPendientes }};
const factCanceladas = {{ $facturasCanceladas }};
const factTotal = factPagadas + factPendientes + factCanceladas;
salcomChart.doughnut(
    document.getElementById('chartFacturacion'),
    factTotal === 0 ? ['Sin facturas'] : ['Pagadas', 'Pendientes', 'Canceladas'],
    factTotal === 0 ? [1] : [factPagadas, factPendientes, factCanceladas],
    factTotal === 0 ? ['#e8e8ed'] : [SC.green, SC.amber, SC.red],
    { legend: false, cutout: '62%' }
);
</script>
@endpush
