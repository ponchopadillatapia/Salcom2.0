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

    .kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
    .kpi{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;position:relative;overflow:hidden}
    .kpi .bar{position:absolute;bottom:0;left:0;right:0;height:4px}
    .kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
    .kpi-val{font-size:28px;font-weight:800;color:var(--gray-text);line-height:1;font-variant-numeric:tabular-nums}
    .kpi-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}

    .chart-card{margin-bottom:28px}
    .card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:24px;transition:box-shadow .2s}
    .card:hover{box-shadow:0 6px 20px rgba(0,0,0,.04)}
    .card-title{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:18px}
    .chart-wrap{position:relative;width:100%;height:260px}

    @media(max-width:900px){.kpi-grid{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')

<div class="kpi-grid anim">
    <div class="kpi"><div class="bar" style="background:var(--green)"></div><div class="kpi-label">Ventas totales</div><div class="kpi-val">${{ number_format($ventasTotales, 0) }}</div><div class="kpi-sub">Sin cancelados</div></div>
    <div class="kpi"><div class="bar" style="background:var(--blue)"></div><div class="kpi-label">Deudas</div><div class="kpi-val">${{ number_format($deudasTotal, 0) }}</div><div class="kpi-sub">{{ $deudasCount }} {{ $deudasCount === 1 ? 'factura pendiente' : 'facturas pendientes' }}</div></div>
    <div class="kpi"><div class="bar" style="background:var(--purple)"></div><div class="kpi-label">Cobrado</div><div class="kpi-val">${{ number_format($facturasPagadas, 0) }}</div><div class="kpi-sub">Facturas pagadas</div></div>
</div>

<div class="card chart-card">
    <div class="card-title">Pedidos por mes</div>
    <div class="chart-wrap"><canvas id="chartPedidos"></canvas></div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
const SC = SALCOM_COLORS;

salcomChart.bar(
    document.getElementById('chartPedidos'),
    {!! json_encode($pedidosPorMes->pluck('mes')) !!},
    {!! json_encode($pedidosPorMes->pluck('total')) !!},
    { color: SC.blue, stepSize: 1 }
);
</script>
@endpush
