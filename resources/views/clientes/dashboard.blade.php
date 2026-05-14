@extends('layouts.cliente')
@section('title', 'Dashboard')
@section('hero')
<div class="hero-band">
    <h1>Bienvenido, {{ session('cliente_nombre', 'Cliente') }}</h1>
    <p>Código: {{ session('cliente_codigo', '—') }} — {{ now()->format('d/m/Y') }}</p>
</div>
@endsection

@php
    $c = $cliente ?? null;
    $limite = $c && $c->limite_credito !== null ? (float) $c->limite_credito : null;
    $diasCred = $c?->dias_credito;
    $credAut = (bool) ($c?->credito_autorizado);
    /** Saldo pendiente demo (alineado a la tarjeta de métricas; sustituir por API/facturas) */
    $saldoDemo = 17719.0;
    $pctUso = $limite && $limite > 0 ? min(100, round(($saldoDemo / $limite) * 100, 1)) : null;

    $catImg = fn (string $file) => asset('Catalogo/' . rawurlencode($file));

    $ap = config('cliente_portal.analitica_portal', []);
    $dashCats = $ap['dashboard_categorias_mas_vendidas'] ?? [];
@endphp

@push('styles')
<style>
    .metrics {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .metric {
        background: var(--white);
        border-radius: var(--radius-lg);
        padding: 20px 22px;
        border: none;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .metric:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    .metric-label {
        font-size: 12px;
        color: var(--gray-muted);
        font-weight: 600;
        margin-bottom: 8px;
        letter-spacing: -0.1px;
    }
    .metric-val {
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

    .credit-dash {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-sm);
        padding: 20px 22px;
        margin-bottom: 24px;
        transition: var(--transition);
    }
    .credit-dash:hover { box-shadow: var(--shadow-md); }
    .credit-dash-head {
        font-size: 15px;
        font-weight: 700;
        color: var(--gray-text);
        margin: 0 0 14px;
        letter-spacing: -0.2px;
    }
    .credit-dash-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        align-items: start;
    }
    .credit-dash-item dt {
        font-size: 11px;
        font-weight: 700;
        color: var(--gray-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 6px;
    }
    .credit-dash-item dd {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: var(--gray-text);
        letter-spacing: -0.3px;
    }
    .credit-dash-item dd.sm { font-size: 15px; font-weight: 600; }
    .badge-cred-si {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: var(--green-bg);
        color: var(--green);
    }
    .badge-cred-no {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: var(--gray-soft);
        color: var(--gray-muted);
    }
    .credit-usage {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid var(--border-light);
    }
    .credit-usage-label {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: var(--gray-muted);
        margin-bottom: 8px;
    }
    .credit-usage-label strong { color: var(--gray-text); }
    .credit-usage-bar {
        height: 10px;
        border-radius: 999px;
        background: var(--gray-soft);
        overflow: hidden;
    }
    .credit-usage-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--purple), #9C6DD0);
        max-width: 100%;
    }
    .credit-usage-note {
        font-size: 11px;
        color: var(--gray-muted);
        margin-top: 8px;
    }

    .mid-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }
    .card {
        background: var(--white);
        border-radius: var(--radius-lg);
        border: none;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .card:hover { box-shadow: var(--shadow-md); }
    .card-head {
        padding: 16px 22px;
        border-bottom: 1px solid var(--border-light);
        font-size: 15px;
        font-weight: 700;
        color: var(--gray-text);
        letter-spacing: -0.2px;
    }
    .card-body { padding: 20px; }

    .cat-sales-caption {
        font-size: 12px;
        color: var(--gray-muted);
        margin: 0 0 14px;
        line-height: 1.5;
    }
    .cat-sales-caption strong { color: var(--gray-text); }

    .cat-sales-board {
        border-radius: 14px;
        border: 1px solid var(--border-light);
        background: linear-gradient(180deg, #fbfbfd 0%, #f3f2f7 100%);
        overflow: hidden;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75);
    }
    .cat-sales-board-row {
        display: grid;
        grid-template-columns: minmax(200px, 1.35fr) minmax(140px, 2fr) minmax(96px, 112px);
        gap: 12px 16px;
        align-items: center;
        padding: 12px 16px;
    }
    .cat-sales-board-row--scale {
        background: rgba(255, 255, 255, 0.92);
        border-bottom: 1px solid var(--border-light);
        padding-top: 10px;
        padding-bottom: 8px;
        font-size: 10px;
        font-weight: 700;
        color: var(--gray-muted);
        letter-spacing: 0.02em;
    }
    .cat-sales-board-corner {
        align-self: end;
        padding-bottom: 2px;
        line-height: 1.3;
    }
    .cat-sales-scale-ticks {
        display: flex;
        justify-content: space-between;
        position: relative;
        min-width: 0;
        padding: 0 2px;
    }
    .cat-sales-scale-ticks::after {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        bottom: -6px;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(107, 63, 160, 0.2), transparent);
    }
    .cat-sales-board-row--data {
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        background: rgba(255, 255, 255, 0.35);
    }
    .cat-sales-board-row--data:last-child { border-bottom: none; }
    .cat-sales-board-row--data:hover {
        background: rgba(255, 255, 255, 0.92);
    }

    .cat-sales-lead {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        min-width: 0;
    }
    .cat-sales-rank {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 800;
        color: var(--purple);
        background: rgba(107, 63, 160, 0.12);
        border: 1px solid rgba(107, 63, 160, 0.18);
        flex-shrink: 0;
        margin-top: 2px;
    }
    .cat-sales-thumb {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        object-fit: cover;
        background: var(--gray-soft);
        border: 1px solid rgba(15, 23, 42, 0.08);
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    }
    .cat-sales-copy { min-width: 0; flex: 1; }
    .cat-sales-name {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: var(--gray-text);
        line-height: 1.25;
        letter-spacing: -0.2px;
    }
    .cat-sales-meta {
        margin: 6px 0 0;
        font-size: 11px;
        font-weight: 600;
        color: var(--gray-muted);
        line-height: 1.35;
    }
    .cat-sales-meta strong { color: var(--gray-text); }
    .cat-sales-slug {
        display: inline-block;
        max-width: 100%;
        margin-top: 4px;
        padding: 2px 7px;
        border-radius: 5px;
        font-family: ui-monospace, Menlo, Consolas, monospace;
        font-size: 10px;
        font-weight: 600;
        color: var(--gray-muted);
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid var(--border-light);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }

    .cat-sales-board-plot {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }
    .cat-sales-chart-track {
        position: relative;
        flex: 1;
        min-width: 0;
        height: 24px;
        border-radius: 10px;
        background: #e8e8ee;
        overflow: hidden;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.08);
    }
    .cat-sales-chart-track::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        background-image: linear-gradient(90deg, rgba(15, 23, 42, 0.07) 1px, transparent 1px);
        background-size: 25% 100%;
        pointer-events: none;
        z-index: 0;
    }
    .cat-sales-chart-fill {
        position: relative;
        z-index: 1;
        height: 100%;
        border-radius: 9px;
        max-width: 100%;
        min-width: 0;
        background: linear-gradient(90deg, #4a2a7a, var(--purple) 45%, #a889d9);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.35),
            0 1px 3px rgba(107, 63, 160, 0.25);
        transition: width 0.35s ease;
    }
    .cat-sales-board [role="list"] > .cat-sales-board-row--data:nth-child(4n + 2) .cat-sales-chart-fill {
        background: linear-gradient(90deg, #1d4ed8, #3b82f6 50%, #93c5fd);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.35),
            0 1px 3px rgba(37, 99, 235, 0.25);
    }
    .cat-sales-board [role="list"] > .cat-sales-board-row--data:nth-child(4n + 3) .cat-sales-chart-fill {
        background: linear-gradient(90deg, #047857, #10b981 55%, #6ee7b7);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.35),
            0 1px 3px rgba(16, 185, 129, 0.22);
    }
    .cat-sales-board [role="list"] > .cat-sales-board-row--data:nth-child(4n + 4) .cat-sales-chart-fill {
        background: linear-gradient(90deg, #b45309, #f59e0b 50%, #fcd34d);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.35),
            0 1px 3px rgba(245, 158, 11, 0.25);
    }
    .cat-sales-chart-readout {
        font-size: 13px;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: var(--gray-text);
        min-width: 2.75rem;
        text-align: right;
        letter-spacing: -0.3px;
    }

    .cat-sales-board-side {
        text-align: right;
        min-width: 0;
    }
    .cat-trend-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 700;
        padding: 5px 10px;
        border-radius: 999px;
    }
    .cat-trend-pill--up {
        background: var(--green-bg);
        color: var(--green);
    }
    .cat-trend-pill--down {
        background: #fef2f2;
        color: #b91c1c;
    }
    .cat-trend-date {
        display: block;
        font-size: 10px;
        font-weight: 600;
        color: var(--gray-muted);
        margin-top: 6px;
    }

    .recent-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
    }
    .recent-item:last-child { border-bottom: none; }
    .recent-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .dot-validacion { background: var(--purple); }
    .dot-produccion { background: #d97706; }
    .dot-b { background: var(--blue); }
    .recent-text { flex: 1; color: var(--gray-text); }
    .recent-meta { font-size: 11px; color: var(--gray-muted); }
    .badge-api {
        font-size: 11px;
        color: var(--amber);
        font-weight: 600;
        background: var(--amber-bg);
        padding: 3px 10px;
        border-radius: 999px;
        display: inline-block;
        margin-bottom: 16px;
    }
    .dash-rotacion-foot {
        font-size: 11px;
        color: var(--gray-muted);
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid var(--border-light);
    }

    @media (max-width: 900px) {
        .metrics { grid-template-columns: 1fr 1fr; }
        .credit-dash-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 720px) {
        .cat-sales-board-row {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .cat-sales-board-row--scale .cat-sales-board-corner:first-of-type { display: none; }
        .cat-sales-scale-ticks::after { bottom: -4px; }
        .cat-sales-board-side {
            text-align: left;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 12px;
            padding-top: 2px;
            border-top: 1px dashed var(--border-light);
        }
        .cat-trend-date { margin-top: 0; }
        .cat-sales-thumb { width: 48px; height: 48px; }
    }
</style>
@endpush

@section('content')
<span class="badge-api">⚠ Datos de prueba (categorías del catálogo y saldo demo)</span>

<div class="metrics">
    <div class="metric"><div class="metric-label">Pedidos este mes</div><div class="metric-val">5</div><div class="metric-sub">Abril 2026 · Mis pedidos</div></div>
    <div class="metric"><div class="metric-label">Total facturado</div><div class="metric-val">$30,618</div><div class="metric-sub">Suma CFDI · Estado de cuenta</div></div>
    <div class="metric"><div class="metric-label">Saldo pendiente</div><div class="metric-val">$17,719</div><div class="metric-sub">3 facturas pendientes</div></div>
    <div class="metric"><div class="metric-label">Último pedido</div><div class="metric-val">PED-2026-005</div><div class="metric-sub">09/04/2026 · En validación</div></div>
</div>

<section class="credit-dash" aria-labelledby="credit-dash-title">
    <h2 class="credit-dash-head" id="credit-dash-title">Límite y plazo de crédito</h2>
    <dl class="credit-dash-grid">
        <div class="credit-dash-item">
            <dt>Límite de crédito</dt>
            <dd>
                @if($limite !== null)
                    ${{ number_format($limite, 2, '.', ',') }}
                @else
                    <span class="sm">—</span>
                @endif
            </dd>
        </div>
        <div class="credit-dash-item">
            <dt>Días de crédito</dt>
            <dd>
                @if($diasCred !== null)
                    {{ $diasCred }} <span class="sm" style="font-weight:600;color:var(--gray-muted)">días</span>
                @else
                    <span class="sm">—</span>
                @endif
            </dd>
        </div>
        <div class="credit-dash-item">
            <dt>Estado</dt>
            <dd class="sm" style="padding-top:4px">
                @if($credAut)
                    <span class="badge-cred-si">Crédito autorizado</span>
                @else
                    <span class="badge-cred-no">Sin crédito autorizado</span>
                @endif
            </dd>
        </div>
    </dl>
    @if($limite !== null && $limite > 0 && $pctUso !== null)
        <div class="credit-usage">
            <div class="credit-usage-label">
                <span>Uso vs límite <strong>(demo)</strong></span>
                <span><strong>{{ $pctUso }}%</strong> · Saldo demo ${{ number_format($saldoDemo, 2, '.', ',') }}</span>
            </div>
            <div class="credit-usage-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) $pctUso }}">
                <div class="credit-usage-fill" style="width: {{ $pctUso }}%"></div>
            </div>
            <p class="credit-usage-note">El porcentaje usa el saldo pendiente de demostración de arriba; conéctalo a facturas reales cuando la API esté lista.</p>
        </div>
    @elseif($limite === null)
        <p class="credit-usage-note" style="margin-top:12px;margin-bottom:0">Tu límite y días de crédito aparecerán aquí cuando el administrador los registre en tu ficha.</p>
    @endif
</section>

<div class="mid-grid">
    <div class="card">
        <div class="card-head">Categorías más vendidas</div>
        <div class="card-body">
            <p class="cat-sales-caption">
                Mismas <strong>secciones que el catálogo</strong> (demo). Gráfico de barras horizontales: longitud = volumen frente al líder (100%). Incluye unidades, tendencia vs. mes anterior y <strong>fecha de corte</strong>.
            </p>
            @if(!empty($dashCats))
            <div class="cat-sales-board" aria-label="Gráfico comparativo de categorías vs. líder de ventas">
                <div class="cat-sales-board-row cat-sales-board-row--scale">
                    <span class="cat-sales-board-corner">Categoría · ud.</span>
                    <div class="cat-sales-scale-ticks" aria-hidden="true">
                        <span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span>
                    </div>
                    <span class="cat-sales-board-corner" style="text-align:right">Tendencia</span>
                </div>
                <div role="list">
                @foreach($dashCats as $cat)
                    <article class="cat-sales-board-row cat-sales-board-row--data" role="listitem" aria-labelledby="cat-sales-name-{{ $loop->iteration }}">
                        <div class="cat-sales-lead">
                            <span class="cat-sales-rank" title="Posición">{{ $loop->iteration }}</span>
                            <img class="cat-sales-thumb" src="{{ $catImg($cat['img'] ?? '') }}" width="52" height="52" alt="" loading="lazy" decoding="async">
                            <div class="cat-sales-copy">
                                <p class="cat-sales-name" id="cat-sales-name-{{ $loop->iteration }}">{{ $cat['seccion'] ?? '—' }}</p>
                                <p class="cat-sales-meta"><strong>{{ number_format((int)($cat['ud'] ?? 0)) }}</strong> ud. · vs. líder del ranking</p>
                                @if(!empty($cat['slug']))
                                    <code class="cat-sales-slug" title="{{ $cat['slug'] }}">{{ $cat['slug'] }}</code>
                                @endif
                            </div>
                        </div>
                        <div class="cat-sales-board-plot">
                            <div class="cat-sales-chart-track" role="img" aria-label="Barra al {{ (int)($cat['bar'] ?? 0) }} por ciento frente al líder">
                                <div class="cat-sales-chart-fill" style="width: {{ min(100, max(0, (int)($cat['bar'] ?? 0))) }}%"></div>
                            </div>
                            <span class="cat-sales-chart-readout">{{ (int)($cat['bar'] ?? 0) }}%</span>
                        </div>
                        <div class="cat-sales-board-side">
                            @if(!empty($cat['sube']))
                                <span class="cat-trend-pill cat-trend-pill--up" title="Tendencia al alza">↑ Sube {{ number_format((float)($cat['pct'] ?? 0), 1) }}%</span>
                            @else
                                <span class="cat-trend-pill cat-trend-pill--down" title="Tendencia a la baja">↓ Baja {{ number_format((float)($cat['pct'] ?? 0), 1) }}%</span>
                            @endif
                            <span class="cat-trend-date">Corte {{ $cat['fecha'] ?? '—' }}</span>
                        </div>
                    </article>
                @endforeach
                </div>
            </div>
            @else
                <p style="color:var(--gray-muted);font-size:13px">Sin datos de demostración.</p>
            @endif
            <p class="dash-rotacion-foot">Imágenes desde <code>public/Catalogo</code>. Categorías alineadas con <a href="{{ route('clientes.catalogo') }}" style="color:var(--purple);font-weight:600;">Catálogo</a> y <a href="{{ route('clientes.forecast') }}" style="color:var(--purple);font-weight:600;">Forecast</a>; sustituir por API.</p>
        </div>
    </div>
    <div class="card">
        <div class="card-head">Últimos pedidos</div>
        <div class="card-body" style="padding:12px 20px">
            <div class="recent-item"><div class="recent-dot dot-validacion"></div><div class="recent-text">PED-2026-005 — En validación</div><div class="recent-meta">09/04</div></div>
            <div class="recent-item"><div class="recent-dot dot-b"></div><div class="recent-text">PED-2026-004 — Autorizado</div><div class="recent-meta">07/04</div></div>
            <div class="recent-item"><div class="recent-dot dot-produccion"></div><div class="recent-text">PED-2026-003 — En producción</div><div class="recent-meta">05/04</div></div>
        </div>
    </div>
</div>
@endsection
