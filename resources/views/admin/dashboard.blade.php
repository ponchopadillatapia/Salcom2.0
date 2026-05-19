@extends('layouts.admin')
@section('title', 'Dashboard')
@section('hero')
<div class="hero-band">
    <h1>Dashboard — Panel Administrativo</h1>
    <p>Resumen general de Industrias Salcom · {{ now()->format('d/m/Y') }}</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    .fade{animation:fadeUp .4s ease both}

    .row-3{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:24px}
    .row-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:24px}

    .card-metric{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:24px;position:relative;overflow:hidden;text-decoration:none;color:inherit;display:block;transition:all .2s ease}
    .card-metric:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,.06);border-color:var(--purple-mid)}
    .card-metric .bar{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:16px 0 0 16px}
    .card-metric .icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
    .card-metric .label{font-size:12px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.3px;margin-bottom:6px}
    .card-metric .value{font-size:30px;font-weight:800;color:var(--gray-text);line-height:1;font-variant-numeric:tabular-nums}
    .card-metric .sub{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .card-metric .details{margin-top:14px;padding-top:12px;border-top:1px solid var(--border-light)}
    .card-metric .detail{display:flex;justify-content:space-between;font-size:12px;padding:3px 0}
    .card-metric .detail span:first-child{color:var(--gray-muted)}
    .card-metric .detail span:last-child{font-weight:700;color:var(--gray-text)}

    .otif-box{display:flex;align-items:center;justify-content:center;gap:28px;margin-top:14px}
    .otif-item{text-align:center}
    .otif-canvas-wrap{position:relative;width:100px;height:100px;margin:0 auto}
    .otif-canvas-wrap canvas{position:absolute;top:0;left:0}
    .otif-center-mini{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none}
    .otif-pct{font-weight:700;line-height:1;font-size:17px}
    .otif-item .lbl{font-size:11px;color:var(--gray-muted);font-weight:600;margin-top:6px}
    .otif-legend{display:flex;gap:10px;justify-content:center;margin-top:12px;padding-top:10px;border-top:1px solid var(--border-light)}
    .otif-legend span{display:flex;align-items:center;gap:4px;font-size:10px;font-weight:600;color:var(--gray-text)}
    .otif-legend .dot{width:8px;height:8px;border-radius:50%}

    .section-label{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:14px}
    .chart-box{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:22px}
    .chart-box .title{font-size:13px;font-weight:700;color:var(--gray-text);margin-bottom:16px}
    .chart-box .wrap{position:relative;width:100%;height:220px}

    .table-box{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden}
    .table-box .head{padding:14px 20px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:10px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-procesando{background:var(--blue-bg);color:var(--blue)}
    .badge-enviado{background:#ede9fe;color:#7c3aed}
    .badge-entregado{background:var(--green-bg);color:var(--green)}
    .badge-cancelado{background:var(--red-bg);color:var(--red)}
    .score-bar{width:56px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px}
    .score-fill{height:100%;border-radius:3px}
    .empty{text-align:center;padding:30px;color:var(--gray-muted);font-size:13px}
    .link-detail{font-size:12px;color:var(--blue);font-weight:600;margin-top:8px;display:block}

    @media(max-width:900px){.row-3,.row-2{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

{{-- ═══ FILA: Negocio, Inventario, OTIF, Opinión Positiva ═══ --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <a href="{{ route('admin.negocio') }}" class="card-metric fade" style="animation-delay:.05s">
        <div class="bar" style="background:var(--blue)"></div>
        <div class="label">Negocio</div>
        <div class="value">${{ number_format($montoPedidos, 0) }}</div>
        <div class="sub">Ventas totales</div>
    </a>
    <a href="{{ route('admin.inventario') }}" class="card-metric fade" style="animation-delay:.1s">
        <div class="bar" style="background:var(--amber)"></div>
        <div class="label">Inventario</div>
        <div class="value">{{ $totalProductos }}</div>
        <div class="sub">{{ $sinStock }} agotados · {{ $totalProductos - $sinStock }} disponibles</div>
    </a>
    <a href="{{ route('admin.otif') }}" class="card-metric fade" style="animation-delay:.15s">
        <div class="bar" style="background:var(--green)"></div>
        <div class="label">OTIF</div>
        <div class="otif-box">
            <div class="otif-item">
                <div class="otif-canvas-wrap">
                    <canvas id="gaugeOT" width="80" height="80"></canvas>
                    <div class="otif-center-mini"><div class="otif-pct" id="dashOtPct"></div></div>
                </div>
                <div class="lbl">OT</div>
            </div>
            <div class="otif-item">
                <div class="otif-canvas-wrap">
                    <canvas id="gaugeIF" width="80" height="80"></canvas>
                    <div class="otif-center-mini"><div class="otif-pct" id="dashIfPct"></div></div>
                </div>
                <div class="lbl">IF</div>
            </div>
        </div>
    </a>
    <a href="{{ route('admin.opinion-positiva') }}" class="card-metric fade" style="animation-delay:.2s">
        <div class="bar" style="background:var(--green)"></div>
        <div class="label">Opinión Positiva</div>
        @php
            $opActualizados = \App\Models\DocumentoProveedor::where('tipo','opinion')->where('estatus','aprobado')->count();
            $opTotal = \App\Models\ProveedorUser::where('activo',true)->count();
            $opNoActualizados = $opTotal - $opActualizados;
            $opPctAct = $opTotal > 0 ? round(($opActualizados / $opTotal) * 100, 1) : 0;
            $opPctNo = $opTotal > 0 ? round(($opNoActualizados / $opTotal) * 100, 1) : 0;
        @endphp
        <div class="otif-box">
            <div class="otif-item">
                <div class="otif-canvas-wrap">
                    <canvas id="gaugeOpAct" width="80" height="80"></canvas>
                    <div class="otif-center-mini"><div class="otif-pct" id="dashOpActPct"></div></div>
                </div>
                <div class="lbl">Actualizados</div>
            </div>
            <div class="otif-item">
                <div class="otif-canvas-wrap">
                    <canvas id="gaugeOpNo" width="80" height="80"></canvas>
                    <div class="otif-center-mini"><div class="otif-pct" id="dashOpNoPct"></div></div>
                </div>
                <div class="lbl">No actualizados</div>
            </div>
        </div>
    </a>
</div>

{{-- ═══ FILA: Proveedores, Docs, Fiscal ═══ --}}
<div class="row-3">
    <a href="{{ route('admin.proveedores') }}" class="card-metric fade" style="animation-delay:.25s">
        <div class="bar" style="background:var(--green)"></div>
        <div class="label">Proveedores</div>
        <div class="value">{{ $totalProveedores }}</div>
        <div class="sub">{{ $proveedoresActivos }} activos · Score {{ $scorePromedio }}%</div>
    </a>
    <a href="{{ route('admin.documentos', ['estatus'=>'pendiente']) }}" class="card-metric fade" style="animation-delay:.3s">
        <div class="bar" style="background:var(--red)"></div>
        <div class="label">Docs. pendientes</div>
        <div class="value">{{ $docsPendientes }}</div>
        <div class="sub">Documentos por revisar</div>
    </a>
    <a href="{{ route('admin.fiscal') }}" class="card-metric fade" style="animation-delay:.35s">
        <div class="bar" style="background:var(--purple)"></div>
        <div class="label">Fiscal</div>
        <div class="value">Validación</div>
        <div class="sub">Estado fiscal de proveedores</div>
    </a>
</div>

{{-- ═══ GRÁFICAS ═══ --}}
<div class="section-label">Análisis</div>
<div class="row-2">
    <div class="chart-box">
        <div class="title">Compras a proveedores por mes</div>
        <div class="wrap"><canvas id="chartPedidos"></canvas></div>
    </div>
    <div class="chart-box">
        <div class="title">Facturación</div>
        <div class="wrap"><canvas id="chartFacturas"></canvas></div>
    </div>
</div>

{{-- ═══ TABLAS ═══ --}}
<div class="section-label">Actividad reciente</div>
<div class="row-2">
    <div class="table-box">
        <div class="head">Órdenes de compra recientes</div>
        @if($ultimosPedidos->count())
        <table class="tbl">
            <thead><tr><th>Folio</th><th>Proveedor</th><th>Total</th><th>Estatus</th></tr></thead>
            <tbody>
            @foreach($ultimosPedidos as $p)
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $p->folio }}</td>
                <td>{{ Str::limit($p->nombre_cliente, 22) }}</td>
                <td>${{ number_format($p->total, 0) }}</td>
                <td><span class="badge badge-{{ $p->estatus }}">{{ ucfirst($p->estatus) }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="empty">No hay órdenes</div>
        @endif
    </div>
    <div class="table-box">
        <div class="head">Top proveedores</div>
        @if($topProveedores->count())
        <table class="tbl">
            <thead><tr><th>Proveedor</th><th>Score</th><th>Entrega</th></tr></thead>
            <tbody>
            @foreach($topProveedores as $pv)
            @php $sc = $pv->score_total; $c = $sc >= 70 ? 'var(--green)' : ($sc >= 40 ? 'var(--amber)' : 'var(--red)'); @endphp
            <tr>
                <td style="font-weight:600">{{ Str::limit($pv->nombre ?? $pv->usuario, 20) }}</td>
                <td><div class="score-bar"><div class="score-fill" style="width:{{ $sc }}%;background:{{ $c }}"></div></div><strong>{{ number_format($sc,0) }}%</strong></td>
                <td>{{ number_format($pv->score_entrega, 0) }}%</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="empty">Sin proveedores con score</div>
        @endif
    </div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script src="/js/otif-donut.js"></script>
<script>
const SC = SALCOM_COLORS;

@php
    $facturasProvDash = \App\Models\Factura::whereNotNull('codigo_proveedor')->get();
    $totalFP = $facturasProvDash->count();
    $pagadasFP = $facturasProvDash->where('estatus', 'pagada')->count();
    $canceladasFP = $facturasProvDash->where('estatus', 'cancelada')->count();
    $ot = $totalFP > 0 ? round(($pagadasFP / $totalFP) * 100, 1) : 0;
    $if = $totalFP > 0 ? round((($totalFP - $canceladasFP) / $totalFP) * 100, 1) : 0;
@endphp
salcomDrawOtifDonut('gaugeOT', {{ $ot }}, 'dashOtPct', 100);
salcomDrawOtifDonut('gaugeIF', {{ $if }}, 'dashIfPct', 100);

salcomDrawOtifDonut('gaugeOpAct', {{ $opPctAct }}, 'dashOpActPct', 100, { fill: '#059669', gap: '#fecaca', text: '#059669' });
salcomDrawOtifDonut('gaugeOpNo', {{ $opPctNo }}, 'dashOpNoPct', 100, { fill: '#dc2626', gap: '#bbf7d0', text: '#dc2626' });

// ── Compras por mes ──
salcomChart.bar(document.getElementById('chartPedidos'),
    {!! json_encode($pedidosPorMes->pluck('mes')) !!},
    {!! json_encode($pedidosPorMes->pluck('monto')) !!},
    {color:SC.purple, yFormat:v=>'$'+Math.round(v/1000)+'K'}
);

// ── Facturación ──
salcomChart.doughnut(document.getElementById('chartFacturas'),
    ['Pagadas','Pendientes','Canceladas'],
    [{{$facturasPorEstatus->get('pagada')->total??0}},{{$facturasPorEstatus->get('pendiente')->total??0}},{{$facturasPorEstatus->get('cancelada')->total??0}}],
    [SC.green,SC.amber,SC.red],
    {legendPos:'right'}
);
</script>
@endpush
