@extends('layouts.admin')
@section('title', 'Negocio')
@section('hero')
<div class="hero-band">
    <h1>Negocio</h1>
    <p>Análisis financiero y rendimiento de ventas</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
    .kpi{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;position:relative;overflow:hidden}
    .kpi .bar{position:absolute;bottom:0;left:0;right:0;height:4px}
    .kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
    .kpi-val{font-size:28px;font-weight:800;color:var(--gray-text);line-height:1;font-variant-numeric:tabular-nums}
    .kpi-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}

    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}
    .card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:24px;transition:box-shadow .2s}
    .card:hover{box-shadow:0 6px 20px rgba(0,0,0,.04)}
    .card-title{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:18px}
    .chart-wrap{position:relative;width:100%;height:260px}

    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden}
    .table-head{padding:16px 22px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}

    .rating{display:inline-flex;gap:2px}
    .rating-dot{width:8px;height:8px;border-radius:50%}

    @media(max-width:900px){.kpi-grid{grid-template-columns:1fr 1fr}.grid-2{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

<div class="kpi-grid anim">
    <div class="kpi"><div class="bar" style="background:var(--green)"></div><div class="kpi-label">Ventas totales</div><div class="kpi-val">${{ number_format($ventasTotales, 0) }}</div><div class="kpi-sub">Sin cancelados</div></div>
    <div class="kpi"><div class="bar" style="background:var(--blue)"></div><div class="kpi-label">Entregados</div><div class="kpi-val">{{ $pedidosEntregados }}<span style="font-size:14px;color:var(--gray-muted);font-weight:500"> / {{ $totalPedidos }}</span></div><div class="kpi-sub">Pedidos completados</div></div>
    <div class="kpi"><div class="bar" style="background:var(--purple)"></div><div class="kpi-label">Cobrado</div><div class="kpi-val">${{ number_format($facturasPagadas, 0) }}</div><div class="kpi-sub">${{ number_format($facturasPendientes, 0) }} por cobrar</div></div>
    <div class="kpi"><div class="bar" style="background:#7c3aed"></div><div class="kpi-label">Satisfacción</div><div class="kpi-val">{{ $calificacionProm ?: '—' }}<span style="font-size:14px;color:var(--gray-muted);font-weight:500">/5</span></div><div class="kpi-sub">{{ $totalEncuestas }} encuestas</div></div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Ingresos por mes</div>
        <div class="chart-wrap"><canvas id="chartIngresos"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title">Pedidos por mes</div>
        <div class="chart-wrap"><canvas id="chartPedidos"></canvas></div>
    </div>
</div>

<div class="table-card">
    <div class="table-head">Encuestas de satisfacción recientes</div>
    @if($encuestas->count())
    <table class="tbl">
        <thead><tr><th>Cliente</th><th>General</th><th>Entrega</th><th>Calidad</th><th>Comentario</th></tr></thead>
        <tbody>
        @foreach($encuestas as $e)
        <tr>
            <td style="font-weight:600">{{ $e->codigo_cliente }}</td>
            <td><strong>{{ $e->calificacion }}</strong>/5</td>
            <td>{{ $e->tiempo_entrega }}/5</td>
            <td>{{ $e->calidad_producto }}/5</td>
            <td style="color:var(--gray-muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $e->comentarios ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align:center;padding:24px;color:var(--gray-muted);font-size:13px">Sin encuestas registradas</p>
    @endif
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
const SC = SALCOM_COLORS;

salcomChart.line(
    document.getElementById('chartIngresos'),
    {!! json_encode($pedidosPorMes->pluck('mes')) !!},
    {!! json_encode($pedidosPorMes->pluck('monto')) !!},
    { color: SC.purple, yFormat: v => '$' + Math.round(v/1000) + 'K' }
);

salcomChart.bar(
    document.getElementById('chartPedidos'),
    {!! json_encode($pedidosPorMes->pluck('mes')) !!},
    {!! json_encode($pedidosPorMes->pluck('total')) !!},
    { color: SC.blue, stepSize: 1 }
);
</script>
@endpush
