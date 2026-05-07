@extends('layouts.proveedor')

@section('title', 'OTIF — Entregas a tiempo y completas')

@section('hero')
<div class="hero-band">
    <h1>OTIF</h1>
    <p>On Time In Full — Métricas de entrega</p>
</div>
@endsection

@push('styles')
<style>
    .otif-wrap {
        max-width: 1140px;
        margin: 0 auto;
    }

    /* ── Donut charts section ── */
    .otif-charts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    .otif-chart-card {
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        padding: 32px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .otif-chart-card:hover {
        border-color: var(--purple-mid);
        box-shadow: var(--shadow-md);
    }
    .otif-canvas-wrap {
        position: relative;
        width: 180px;
        height: 180px;
        margin-bottom: 16px;
    }
    .otif-canvas-wrap canvas {
        position: absolute;
        top: 0;
        left: 0;
    }
    .otif-center {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    .otif-percent {
        font-size: 32px;
        font-weight: 700;
        color: var(--green);
        line-height: 1;
    }
    .otif-chart-label {
        font-size: 14px;
        color: var(--gray-muted);
        font-weight: 600;
        margin-top: 8px;
    }

    /* ── Fail items section ── */
    .otif-fail-card {
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        padding: 22px;
        margin-bottom: 24px;
        box-shadow: var(--shadow-sm);
    }
    .otif-fail-card h4 {
        font-size: 14px;
        font-weight: 700;
        color: var(--gray-text);
        margin-bottom: 16px;
    }
    .otif-fail-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 0;
        border-bottom: 1px solid var(--border-light);
    }
    .otif-fail-item:last-child {
        border-bottom: none;
    }
    .otif-fail-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--red);
        flex-shrink: 0;
    }
    .otif-fail-name {
        flex: 1;
        font-size: 13px;
        font-weight: 600;
        color: var(--gray-text);
    }
    .otif-fail-reason {
        font-size: 12px;
        color: var(--red);
        font-weight: 700;
    }

    /* ── Note ── */
    .otif-note {
        text-align: center;
        font-size: 11px;
        color: var(--gray-muted);
        margin-top: 24px;
        padding: 12px;
        background: var(--gray-soft);
        border-radius: var(--radius-lg);
    }

    @media (max-width: 768px) {
        .otif-charts {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="otif-wrap">

    {{-- ═══ Donut Charts ═══ --}}
    <div class="otif-charts">
        <div class="otif-chart-card">
            <div class="otif-canvas-wrap">
                <canvas id="otifDonutOT" width="180" height="180"></canvas>
                <div class="otif-center">
                    <div class="otif-percent">98.5%</div>
                </div>
            </div>
            <span class="otif-chart-label">OT (On Time)</span>
        </div>
        <div class="otif-chart-card">
            <div class="otif-canvas-wrap">
                <canvas id="otifDonutIF" width="180" height="180"></canvas>
                <div class="otif-center">
                    <div class="otif-percent">95%</div>
                </div>
            </div>
            <span class="otif-chart-label">IF (In Full)</span>
        </div>
    </div>

    {{-- ═══ Productos que no entrega a tiempo y completos ═══ --}}
    <div class="otif-fail-card">
        <h4>Productos que no entrega a tiempo y completos</h4>
        <div class="otif-fail-item">
            <div class="otif-fail-dot"></div>
            <div class="otif-fail-name">SOLVENTE</div>
            <div class="otif-fail-reason">Retraso 3 días</div>
        </div>
        <div class="otif-fail-item">
            <div class="otif-fail-dot"></div>
            <div class="otif-fail-name">CLORO</div>
            <div class="otif-fail-reason">Entrega parcial 60%</div>
        </div>
        <div class="otif-fail-item">
            <div class="otif-fail-dot"></div>
            <div class="otif-fail-name">PIGMENTO</div>
            <div class="otif-fail-reason">Retraso 1 día</div>
        </div>
    </div>

    {{-- ═══ Note ═══ --}}
    <div class="otif-note">
        Datos de prueba — Pendiente de API
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function drawDonut(canvasId, percent, color) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const size = canvas.width;
        const center = size / 2;
        const radius = 70;
        const lineWidth = 16;
        const startAngle = -Math.PI / 2;
        const endAngle = startAngle + (2 * Math.PI * percent / 100);

        // Background ring
        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#e8e8ed';
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'round';
        ctx.stroke();

        // Value ring
        ctx.beginPath();
        ctx.arc(center, center, radius, startAngle, endAngle);
        ctx.strokeStyle = color;
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'round';
        ctx.stroke();
    }

    drawDonut('otifDonutOT', 98.5, '#34c759');
    drawDonut('otifDonutIF', 95, '#34c759');
});
</script>
@endpush
