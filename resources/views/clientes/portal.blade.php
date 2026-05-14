@extends('layouts.cliente')

@section('title', 'Inicio')

@section('hero')
<div class="hero-band">
    <h1>Hola, {{ session('cliente_nombre', 'Cliente') }}</h1>
    <p>Bienvenido al Portal de Clientes de Industrias Salcom</p>
</div>
@endsection

@push('styles')
<style>
    .pp-wrap {
        max-width: 1140px;
        margin: 0 auto;
    }

    .pp-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
    }

    /* Fila superior: 4 KPI iguales (evita columna Fiscal estrecha por min-content del OTIF) */
    .pp-kpi-row {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    .pp-kpi-link {
        display: flex;
        flex-direction: column;
        min-width: 0;
        color: inherit;
        text-decoration: none;
    }
    .pp-kpi-link .pp-card {
        flex: 1;
        width: 100%;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .pp-kpi-link .pp-detail-link {
        margin-top: auto;
    }

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

    .pp-otif-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: clamp(10px, 2.5vw, 22px);
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        max-width: 100%;
    }
    .pp-otif-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        flex: 0 1 auto;
        min-width: 0;
    }
    .pp-otif-canvas-wrap {
        position: relative;
        width: 88px;
        height: 88px;
        flex-shrink: 0;
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
        font-size: 16px;
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

    @media (max-width: 1024px) and (min-width: 769px) {
        .pp-kpi-row {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .pp-kpi-row {
            grid-template-columns: 1fr;
        }
        .pp-grid-2 {
            grid-template-columns: 1fr !important;
        }
        .pp-quick-grid {
            grid-template-columns: 1fr 1fr;
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

    <div class="pp-kpi-row">
        <a href="{{ route('clientes.estado-cuenta') }}" class="pp-kpi-link">
        <div class="pp-card">
            <h4>Compras</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Compras (YTD)</div>
                <div class="pp-negocio-value" style="color:var(--green);">
                    $1,842,300.00
                    <span class="pp-variation pp-variation-up">+8%</span>
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Volumen (kg)</div>
                <div class="pp-negocio-value" style="color:var(--gray-text);">
                    142,180
                    <span class="pp-variation pp-variation-up">+3%</span>
                </div>
            </div>
            <span class="pp-detail-link">Ver detalle →</span>
        </div>
        </a>

        <a href="{{ route('clientes.catalogo') }}" class="pp-kpi-link">
        <div class="pp-card">
            <h4>Catálogo</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Referencias activas</div>
                <div class="pp-negocio-value">312</div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Novedades (30 días)</div>
                <div class="pp-negocio-value" style="color:var(--amber);">
                    6
                </div>
            </div>
            <span class="pp-detail-link">Ver detalle →</span>
        </div>
        </a>

        <a href="{{ route('clientes.otif') }}" class="pp-kpi-link">
        <div class="pp-card">
            <h4>OTIF</h4>
            <div class="pp-otif-wrap">
                <div class="pp-otif-item">
                    <div class="pp-otif-canvas-wrap">
                        <canvas id="donutOTCliente" width="88" height="88"></canvas>
                        <div class="pp-otif-center">
                            <div class="pp-otif-percent" id="pctOTCliente">98.5%</div>
                        </div>
                    </div>
                    <span class="pp-otif-label">OT</span>
                </div>
                <div class="pp-otif-item">
                    <div class="pp-otif-canvas-wrap">
                        <canvas id="donutIFCliente" width="88" height="88"></canvas>
                        <div class="pp-otif-center">
                            <div class="pp-otif-percent" id="pctIFCliente">95%</div>
                        </div>
                    </div>
                    <span class="pp-otif-label">IF</span>
                </div>
            </div>
            <span class="pp-detail-link">Ver detalle →</span>
        </div>
        </a>

        <a href="{{ route('clientes.fiscal') }}" class="pp-kpi-link">
        <div class="pp-card">
            <h4>Fiscal</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Documentos</div>
                <div class="pp-negocio-value" style="color:var(--green);">
                    6/6
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Estatus</div>
                <div class="pp-negocio-value" style="font-size:14px;color:var(--green);">
                    Al día
                </div>
            </div>
            <span class="pp-detail-link">Ver detalle →</span>
        </div>
        </a>
    </div>

    <div class="pp-grid-2">
        <div class="pp-card">
            <h4>Actividad reciente</h4>
            <a href="{{ route('clientes.pedidos') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">PED-2026-005 en producción</div>
                <div class="pp-list-status">En curso</div>
            </a>
            <a href="{{ route('clientes.estado-cuenta') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Factura CFDI-A-001236 en revisión</div>
                <div class="pp-list-status">Pendiente</div>
            </a>
            <a href="{{ route('clientes.tracking') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">Envío PED-2026-002 en ruta</div>
                <div class="pp-list-status">En tránsito</div>
            </a>
            <a href="{{ route('clientes.pedidos') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">PED-2026-004 autorizado</div>
                <div class="pp-list-status">Listo</div>
            </a>
            <a href="{{ route('clientes.otif') }}" class="pp-list-item" style="text-decoration:none;color:inherit;">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Entrega PED-2026-001 — revisar OTIF</div>
                <div class="pp-list-status">Parcial</div>
            </a>
        </div>

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
                <div class="pp-list-text">Alta en el portal</div>
                <div class="pp-list-status">Completado</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-green"></div>
                <div class="pp-list-text">Documentación fiscal</div>
                <div class="pp-list-status">Completado</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Contactos y datos operativos</div>
                <div class="pp-list-status">Pendiente</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Validación Salcom</div>
                <div class="pp-list-status">Pendiente</div>
            </div>
            <div class="pp-list-item">
                <div class="pp-dot pp-dot-amber"></div>
                <div class="pp-list-text">Condiciones comerciales</div>
                <div class="pp-list-status">Pendiente</div>
            </div>
            <a href="{{ route('clientes.onboarding') }}" class="pp-detail-link">Ver →</a>
        </div>
    </div>

    <div class="pp-quick-grid">
        <a href="{{ route('clientes.ia') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/><line x1="12" y1="16" x2="12" y2="20"/><line x1="8" y1="20" x2="16" y2="20"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Dashboard IA</div>
                <div class="pp-quick-sub">Análisis inteligente</div>
            </div>
        </a>
        <a href="{{ route('clientes.pedidos') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Mis pedidos</div>
                <div class="pp-quick-sub">Estatus y seguimiento</div>
            </div>
        </a>
        <a href="{{ route('clientes.estado-cuenta') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Estado de cuenta</div>
                <div class="pp-quick-sub">Facturas y saldos</div>
            </div>
        </a>
        <a href="{{ route('clientes.catalogo') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Catálogo</div>
                <div class="pp-quick-sub">Productos y precios</div>
            </div>
        </a>
        <a href="{{ route('clientes.forecast') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Forecast</div>
                <div class="pp-quick-sub">Tendencias de compras</div>
            </div>
        </a>
        <a href="{{ route('clientes.tracking') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <div class="pp-quick-title">Tracking</div>
                <div class="pp-quick-sub">Seguimiento de envíos</div>
            </div>
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Dona: pista gris → arco faltante (rojo si ≤95%, naranja si >95%) → arco cumplido (verde).
     * Mismo grosor y lineCap butt en todos para que el rojo quede alineado sin manchas.
     */
    function drawDonut(canvasId, percent, percentElId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const css = 88;
        const dpr = window.devicePixelRatio || 1;
        canvas.width = css * dpr;
        canvas.height = css * dpr;
        canvas.style.width = css + 'px';
        canvas.style.height = css + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const center = css / 2;
        const radius = 34;
        const lineWidth = 9;
        const startAngle = -Math.PI / 2;
        const p = Math.min(100, Math.max(0, percent));
        const sweep = (2 * Math.PI * p) / 100;
        const endAngle = startAngle + sweep;
        const fullEnd = startAngle + 2 * Math.PI;
        const gapColor = p > 95 ? '#ff9500' : '#ff3b30';

        ctx.clearRect(0, 0, css, css);
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'butt';

        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#e8e8ed';
        ctx.stroke();

        if (p < 100) {
            ctx.beginPath();
            ctx.arc(center, center, radius, endAngle, fullEnd);
            ctx.strokeStyle = gapColor;
            ctx.stroke();
        }

        if (p > 0) {
            ctx.beginPath();
            ctx.arc(center, center, radius, startAngle, endAngle);
            ctx.strokeStyle = '#34c759';
            ctx.stroke();
        }

        const percentEl = document.getElementById(percentElId);
        if (percentEl) {
            if (p <= 95) {
                percentEl.style.color = '#ff3b30';
            } else if (p < 100) {
                percentEl.style.color = '#34c759';
            } else {
                percentEl.style.color = '#34c759';
            }
        }
    }

    drawDonut('donutOTCliente', 98.5, 'pctOTCliente');
    drawDonut('donutIFCliente', 95, 'pctIFCliente');
});
</script>
@endpush
