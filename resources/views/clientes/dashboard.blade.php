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

    /** Categorías demo: volumen relativo (bar), tendencia vs periodo anterior, fecha de corte del indicador */
    $categoriasVenta = [
        ['nombre' => 'Detergentes y limpieza', 'img' => $catImg('Naedc 28 a 43.jpg'), 'ud' => 2840, 'bar' => 100, 'sube' => true, 'pct' => 12.4, 'fecha' => '01/05/2026'],
        ['nombre' => 'Químicos industriales (HO)', 'img' => $catImg('Naeho 57 a 10.jpg'), 'ud' => 2210, 'bar' => 78, 'sube' => true, 'pct' => 5.2, 'fecha' => '01/05/2026'],
        ['nombre' => 'Solventes y dieléctricos', 'img' => $catImg('Ndiel 00 a 03.jpg'), 'ud' => 1680, 'bar' => 59, 'sube' => false, 'pct' => 3.1, 'fecha' => '28/04/2026'],
        ['nombre' => 'Lubricantes', 'img' => $catImg('Nlilg 48 a 53.jpg'), 'ud' => 1420, 'bar' => 50, 'sube' => false, 'pct' => 8.7, 'fecha' => '28/04/2026'],
        ['nombre' => 'Línea automotriz', 'img' => $catImg('Narau09 a 12.jpg'), 'ud' => 960, 'bar' => 34, 'sube' => true, 'pct' => 2.0, 'fecha' => '30/04/2026'],
        ['nombre' => 'Aditivos y especialidades', 'img' => $catImg('Naeho52 a 78.jpg'), 'ud' => 520, 'bar' => 18, 'sube' => false, 'pct' => 11.0, 'fecha' => '25/04/2026'],
    ];
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
        grid-template-columns: 2fr 1fr;
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
        margin: 0 0 18px;
        line-height: 1.45;
    }
    .cat-sales-caption strong { color: var(--gray-text); }
    .cat-sales-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 14px;
        padding: 12px 0;
        border-bottom: 1px solid var(--border-light);
        font-size: 13px;
    }
    .cat-sales-row:last-child { border-bottom: none; }
    .cat-sales-lead {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1 1 200px;
        min-width: 0;
    }
    .cat-sales-thumb {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        object-fit: cover;
        background: var(--gray-soft);
        border: 1px solid var(--border-light);
        flex-shrink: 0;
    }
    .cat-sales-name {
        font-weight: 700;
        color: var(--gray-text);
        line-height: 1.25;
    }
    .cat-sales-ud {
        font-size: 11px;
        color: var(--gray-muted);
        margin-top: 2px;
        font-weight: 600;
    }
    .cat-sales-meta {
        flex: 1;
        min-width: 0;
    }
    .cat-sales-bar-track {
        flex: 1 1 160px;
        min-width: 100px;
        height: 10px;
        border-radius: 999px;
        background: var(--gray-soft);
        overflow: hidden;
    }
    .cat-sales-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, var(--purple), #9C6DD0);
        max-width: 100%;
    }
    .cat-sales-trend {
        text-align: right;
        flex-shrink: 0;
        min-width: 120px;
    }
    .cat-trend-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 700;
        padding: 4px 10px;
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
        margin-top: 4px;
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
        .mid-grid { grid-template-columns: 1fr; }
        .credit-dash-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .cat-sales-row { flex-direction: column; align-items: stretch; }
        .cat-sales-lead { flex: none; }
        .cat-sales-thumb { width: 44px; height: 44px; }
        .cat-sales-bar-track { flex: none !important; width: 100%; min-width: 0; }
        .cat-sales-trend { text-align: left; width: 100%; min-width: 0; }
    }
</style>
@endpush

@section('content')
<span class="badge-api">⚠ Datos de prueba (categorías, tendencias y saldo demo)</span>

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
                Gráfica por volumen de unidades (demo). La tendencia <strong>sube</strong> o <strong>baja</strong> vs. el mes anterior; cada renglón muestra la <strong>fecha de corte</strong> del dato.
            </p>
            @foreach($categoriasVenta as $cat)
                <div class="cat-sales-row">
                    <div class="cat-sales-lead">
                        <img class="cat-sales-thumb" src="{{ $cat['img'] }}" width="48" height="48" alt="" loading="lazy" decoding="async">
                        <div class="cat-sales-meta">
                            <div class="cat-sales-name">{{ $cat['nombre'] }}</div>
                            <div class="cat-sales-ud">{{ number_format($cat['ud']) }} ud. · {{ $cat['bar'] }}% del top</div>
                        </div>
                    </div>
                    <div class="cat-sales-bar-track" role="presentation" aria-hidden="true">
                        <div class="cat-sales-bar-fill" style="width: {{ $cat['bar'] }}%"></div>
                    </div>
                    <div class="cat-sales-trend">
                        @if($cat['sube'])
                            <span class="cat-trend-pill cat-trend-pill--up" title="Tendencia al alza">↑ Sube {{ number_format($cat['pct'], 1) }}%</span>
                        @else
                            <span class="cat-trend-pill cat-trend-pill--down" title="Tendencia a la baja">↓ Baja {{ number_format($cat['pct'], 1) }}%</span>
                        @endif
                        <span class="cat-trend-date">Corte {{ $cat['fecha'] }}</span>
                    </div>
                </div>
            @endforeach
            <p class="dash-rotacion-foot">Imágenes representativas por categoría desde <code>public/Catalogo</code>. Sustituir por agregados reales cuando exista historial en API.</p>
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
