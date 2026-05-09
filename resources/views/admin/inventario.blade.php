@extends('layouts.admin')
@section('title', 'Inventario')
@section('hero')
<div class="hero-band">
    <h1>Inventario</h1>
    <p>Control de stock y disponibilidad de productos</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .inv-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
    .inv-kpi{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;text-align:center;position:relative;overflow:hidden}
    .inv-kpi .bar{position:absolute;top:0;left:0;right:0;height:4px}
    .inv-kpi-val{font-size:30px;font-weight:800;line-height:1;margin-bottom:6px}
    .inv-kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px}

    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}
    .card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:24px;transition:box-shadow .2s}
    .card:hover{box-shadow:0 6px 20px rgba(0,0,0,.04)}
    .card-title{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:18px}
    .chart-wrap{position:relative;width:100%;height:280px}

    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden}
    .table-head{padding:16px 22px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .badge-stock{font-size:11px;font-weight:600;padding:4px 12px;border-radius:999px;display:inline-block}
    .badge-stock.ok{background:var(--green-bg);color:var(--green)}
    .badge-stock.low{background:var(--amber-bg);color:var(--amber)}
    .badge-stock.out{background:var(--red-bg);color:var(--red)}
    .stock-bar{width:80px;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .stock-fill{height:100%;border-radius:3px}

    @media(max-width:900px){.inv-kpis{grid-template-columns:1fr 1fr}.grid-2{grid-template-columns:1fr}}
    @media(max-width:768px){.table-card{overflow-x:auto}}
</style>
@endpush
@section('content')

<div class="inv-kpis anim">
    <div class="inv-kpi"><div class="bar" style="background:var(--purple)"></div><div class="inv-kpi-val" style="color:var(--purple)">{{ $productos->count() }}</div><div class="inv-kpi-label">Productos</div></div>
    <div class="inv-kpi"><div class="bar" style="background:var(--green)"></div><div class="inv-kpi-val" style="color:var(--green)">{{ $stockOk }}</div><div class="inv-kpi-label">Stock OK</div></div>
    <div class="inv-kpi"><div class="bar" style="background:var(--amber)"></div><div class="inv-kpi-val" style="color:var(--amber)">{{ $stockBajo }}</div><div class="inv-kpi-label">Stock bajo</div></div>
    <div class="inv-kpi"><div class="bar" style="background:var(--red)"></div><div class="inv-kpi-val" style="color:var(--red)">{{ $sinStock }}</div><div class="inv-kpi-label">Agotados</div></div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Stock por producto</div>
        <div class="chart-wrap"><canvas id="chartStock"></canvas></div>
    </div>
    <div class="card">
        <div class="card-title">Distribución de inventario</div>
        <div class="chart-wrap"><canvas id="chartDist"></canvas></div>
    </div>
</div>

<div class="table-card">
    <div class="table-head">Detalle de productos</div>
    <table class="tbl">
        <thead><tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Nivel</th><th>Estado</th></tr></thead>
        <tbody>
        @php $maxStock = $productos->max('stock') ?: 1; @endphp
        @foreach($productos as $p)
            @php
                $cls = $p->stock <= 0 ? 'out' : ($p->stock < 50 ? 'low' : 'ok');
                $lbl = $p->stock <= 0 ? 'Agotado' : ($p->stock < 50 ? 'Bajo' : 'OK');
                $pct = round(($p->stock / $maxStock) * 100);
                $barColor = $p->stock <= 0 ? '#ff3b30' : ($p->stock < 50 ? '#ff9f0a' : '#34c759');
            @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td style="color:var(--gray-muted)">{{ $p->categoria }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($p->precio, 2) }}</td>
                <td style="font-weight:700">{{ number_format($p->stock) }}</td>
                <td><div class="stock-bar"><div class="stock-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div></div></td>
                <td><span class="badge-stock {{ $cls }}">{{ $lbl }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
const SC = SALCOM_COLORS;
const stockData = {!! json_encode($productos->map(fn($p) => ['codigo' => $p->codigo, 'stock' => $p->stock])->values()) !!};

salcomChart.bar(
    document.getElementById('chartStock'),
    stockData.map(d => d.codigo),
    stockData.map(d => d.stock),
    {
        bgColors: stockData.map(d => d.stock <= 0 ? SC.redLight : (d.stock < 50 ? SC.amberLight : SC.greenLight)),
        borderColors: stockData.map(d => d.stock <= 0 ? SC.red : (d.stock < 50 ? SC.amber : SC.green)),
    }
);

salcomChart.doughnut(
    document.getElementById('chartDist'),
    ['Stock OK (≥50)', 'Stock bajo (<50)', 'Agotado (0)'],
    [{{ $stockOk }}, {{ $stockBajo }}, {{ $sinStock }}],
    [SC.green, SC.amber, SC.red],
    { legendPos: 'bottom' }
);
</script>
@endpush
