@extends('layouts.proveedor')

@section('title', 'Inicio')

@section('hero')
<div class="hero-band">
    <h1>Hola, {{ session('proveedor_nombre', 'Proveedor') }}</h1>
    <p>Bienvenido al Portal de Proveedores de Industrias Salcom</p>
</div>
@endsection

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
        gap: 20px;
        margin-bottom: 24px;
    }

    /* ── Card base ── */
    .pp-card {
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        padding: 22px;
        transition: var(--transition);
    }
    .pp-card:hover {
        border-color: var(--purple-mid);
        box-shadow: var(--shadow-md);
    }
    .pp-card h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--gray-text);
        margin-bottom: 16px;
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
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .pp-quick-card {
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 14px;
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
    @media (max-width: 480px) {
        .pp-quick-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="pp-wrap">

    {{-- ═══ SECTION 1: Negocio + Inventario + OTIF + Fiscal ═══ --}}
    <div class="pp-grid-2" style="grid-template-columns: 1fr 1fr 1fr 1fr;">
        {{-- Negocio --}}
        <a href="{{ route('proveedores.business') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card">
            <h4>Negocio</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Ventas</div>
                <div class="pp-negocio-value" style="color:var(--green);">
                    $2,500,000.25
                    <span class="pp-variation pp-variation-up">+15%</span>
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Unidades</div>
                <div class="pp-negocio-value" style="color:var(--red);">
                    275,343
                    <span class="pp-variation pp-variation-down">-2%</span>
                </div>
            </div>
            <span class="pp-detail-link">Ver detalle →</span>
        </div>
        </a>

        {{-- Inventario --}}
        <a href="{{ route('proveedores.inventario') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card">
            <h4>Inventario</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">SKUs activos</div>
                <div class="pp-negocio-value">48</div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Stock bajo</div>
                <div class="pp-negocio-value" style="color:var(--amber);">
                    3
                </div>
            </div>
            <span class="pp-detail-link">Ver detalle →</span>
        </div>
        </a>

        {{-- OTIF --}}
        <a href="{{ route('proveedores.otif') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card">
            <h4>OTIF (a Salcom)</h4>
            <div class="pp-otif-wrap">
                <div class="pp-otif-item">
                    <div class="pp-otif-canvas-wrap">
                        <canvas id="donutOT" width="100" height="100"></canvas>
                        <div class="pp-otif-center">
                            <div class="pp-otif-percent">92%</div>
                        </div>
                    </div>
                    <span class="pp-otif-label">OT — OC / planta</span>
                </div>
                <div class="pp-otif-item">
                    <div class="pp-otif-canvas-wrap">
                        <canvas id="donutIF" width="100" height="100"></canvas>
                        <div class="pp-otif-center">
                            <div class="pp-otif-percent">88%</div>
                        </div>
                    </div>
                    <span class="pp-otif-label">IF — cantidad vs. OC</span>
                </div>
            </div>
            <span class="pp-detail-link">Ver detalle →</span>
        </div>
        </a>

        {{-- Fiscal --}}
        <a href="{{ route('proveedores.fiscal') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card">
            <h4>Fiscal</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Documentos</div>
                <div class="pp-negocio-value" style="color:var(--green);">
                    5/6
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Estatus</div>
                <div class="pp-negocio-value" style="font-size:14px;color:var(--amber);">
                    Pendiente validación
                </div>
            </div>
            <span class="pp-detail-link">Ver detalle →</span>
        </div>
        </a>
    </div>

    {{-- ═══ SECTION 2: Actividad reciente + Onboarding ═══ --}}
    <div class="pp-grid-2">
        {{-- Actividad reciente --}}
        <div class="pp-card">
            <h4>Actividad reciente</h4>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">OC #10045 generada</div>
                <div class="pp-list-status">Pendiente</div>
            </div>
            <a href="{{ route('proveedores.business') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Factura en revisión</div>
                <div class="pp-list-status">Pendiente</div>
            </a>
            <a href="{{ route('proveedores.payment-history') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">Pago programado</div>
                <div class="pp-list-status">Pendiente</div>
            </a>
            <a href="{{ route('proveedores.oc') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">Estatus OC</div>
                <div class="pp-list-status">Pendiente</div>
            </a>
            <a href="{{ route('proveedores.business') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Facturas OC</div>
                <div class="pp-list-status">Pendiente</div>
            </a>
        </div>

        {{-- Onboarding --}}
        <div class="pp-card">
            <div class="pp-onboarding-header">
                <h4 style="margin-bottom:0;">Onboarding</h4>
                <span class="pp-onboarding-progress" style="color: var(--red);">33%</span>
            </div>
            <div style="background:var(--red-bg);border:1px solid var(--red);border-radius:10px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
                <span style="font-size:18px;">⚠</span>
                <span style="font-size:12px;font-weight:600;color:var(--red);">Tienes pasos pendientes por completar. Finaliza tu onboarding para operar al 100%.</span>
            </div>
            <div class="pp-onboarding-steps">2 de 6 pasos completados</div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">Alta de proveedor</div>
                <div class="pp-list-status">Completado</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">Documentos fiscales</div>
                <div class="pp-list-status">Completado</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Contactos</div>
                <div class="pp-list-status">Pendiente</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Validación estándar</div>
                <div class="pp-list-status">Pendiente</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Detalle de inventario</div>
                <div class="pp-list-status">Pendiente</div>
            </div>
            <a href="{{ route('proveedores.onboarding') }}" class="pp-detail-link">Ver →</a>
        </div>
    </div>

    {{-- ═══ SECTION 3: Quick access ═══ --}}
    <div class="pp-quick-grid">
        <a href="{{ route('proveedores.ia') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Módulo IA</div>
                <div class="pp-quick-sub">Análisis inteligente</div>
            </div>
        </a>
        <a href="{{ route('proveedores.oc') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Consultar OC</div>
                <div class="pp-quick-sub">Órdenes de compra</div>
            </div>
        </a>
        <a href="{{ route('proveedores.payment-history') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Estatus de pagos</div>
                <div class="pp-quick-sub">Historial y pendientes</div>
            </div>
        </a>
        <a href="{{ route('muestras.crear') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Envío de muestras</div>
                <div class="pp-quick-sub">Registro y seguimiento</div>
            </div>
        </a>
        <a href="{{ route('proveedores.forecast') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Forecast</div>
                <div class="pp-quick-sub">Tendencias de productos</div>
            </div>
        </a>
        <a href="{{ route('proveedores.alta-producto') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Alta de producto</div>
                <div class="pp-quick-sub">Nuevo producto</div>
            </div>
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
        const radius = 40;
        const lineWidth = 10;
        const startAngle = -Math.PI / 2;
        const endAngle = startAngle + (2 * Math.PI * percent / 100);

        const mainColor = '#34c759';
        const gapColor = percent > 95 ? '#ff9500' : '#ff3b30';

        // Fondo gris
        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#e8e8ed';
        ctx.lineWidth = lineWidth;
        ctx.stroke();

        // Parte completada (verde)
        ctx.beginPath();
        ctx.arc(center, center, radius, startAngle, endAngle);
        ctx.strokeStyle = mainColor;
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'round';
        ctx.stroke();

        // Gap — pedacito cuadrado (sin round) encima
        if (percent < 100) {
            ctx.beginPath();
            ctx.arc(center, center, radius, endAngle + 0.02, startAngle + 2 * Math.PI - 0.02);
            ctx.strokeStyle = gapColor;
            ctx.lineWidth = lineWidth;
            ctx.lineCap = 'butt';
            ctx.stroke();
        }

        // Color del texto
        const percentEl = canvas.parentElement.querySelector('.pp-otif-percent');
        if (percentEl) {
            percentEl.style.color = percent <= 95 ? '#ff3b30' : '#34c759';
        }
    }

    drawDonut('donutOT', 92);
    drawDonut('donutIF', 88);
});
</script>
@endpush
