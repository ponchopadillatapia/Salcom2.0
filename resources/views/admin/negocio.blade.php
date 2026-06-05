@extends('layouts.admin')
@section('title', 'Negocio')
@section('hero')
<div class="hero-band">
    <h1>Negocio</h1>
    <p>Ventas y facturación por proveedor</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px}
    .kpi{background:var(--white);border:2px solid var(--border-light);border-radius:14px;padding:22px;position:relative;overflow:hidden;cursor:pointer;text-decoration:none;color:inherit;transition:all .15s;display:block;width:100%;text-align:left;font-family:inherit}
    .kpi:hover{border-color:var(--purple);box-shadow:0 4px 16px rgba(107,63,160,.12)}
    .kpi.active{border-color:var(--purple);background:var(--purple-subtle);box-shadow:0 4px 16px rgba(107,63,160,.15)}
    .kpi .bar{position:absolute;bottom:0;left:0;right:0;height:4px}
    .kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
    .kpi-val{font-size:28px;font-weight:800;color:var(--gray-text);line-height:1;font-variant-numeric:tabular-nums}
    .kpi-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .kpi-count{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:rgba(0,0,0,.08);margin-left:6px;vertical-align:middle}
    .kpi.active .kpi-count{background:rgba(107,63,160,.15);color:var(--purple)}

    .neg-detail{display:none;margin-bottom:20px}
    .neg-detail.active{display:block}
    .section-meta{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:12px 18px;margin-bottom:14px;font-size:13px;color:var(--gray-muted)}
    .section-meta strong{color:var(--gray-text);font-weight:600}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table th.num{text-align:right}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table td.num{text-align:right;font-variant-numeric:tabular-nums}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .admin-table tfoot td{font-weight:700;background:var(--gray-soft);border-top:2px solid var(--border)}
    .code-col{font-weight:700;color:var(--purple)}
    .name-col{font-weight:600}
    .empty-state{text-align:center;padding:40px 20px;color:var(--gray-muted);font-size:14px;background:var(--white);border:1px solid var(--border);border-radius:12px}

    .chart-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:22px 24px;margin-top:8px}
    .chart-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px;flex-wrap:wrap}
    .chart-head h3{font-size:15px;font-weight:700;color:var(--gray-text);margin:0 0 4px}
    .chart-head p{font-size:12px;color:var(--gray-muted);margin:0}
    .chart-stats{display:flex;gap:20px;flex-wrap:wrap}
    .chart-stat{text-align:right}
    .chart-stat-label{font-size:10px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px}
    .chart-stat-val{font-size:16px;font-weight:700;color:var(--gray-text);font-variant-numeric:tabular-nums}
    .chart-wrap{position:relative;width:100%;height:280px}

    @media(max-width:900px){.kpi-grid{grid-template-columns:1fr}.admin-table-wrap{overflow-x:auto}.chart-stats{width:100%;justify-content:space-between}.chart-stat{text-align:left}}
</style>
@endpush
@section('content')

<div class="kpi-grid anim">
    <button type="button" class="kpi neg-tab-btn" data-tab="ventas" onclick="switchNegocio('ventas', this)">
        <div class="bar" style="background:var(--green)"></div>
        <div class="kpi-label">Ventas totales <span class="kpi-count">{{ $ventasCount }}</span></div>
        <div class="kpi-val">${{ number_format($ventasTotales, 0) }}</div>
        <div class="kpi-sub">{{ count($proveedoresVentas) }} proveedor{{ count($proveedoresVentas) !== 1 ? 'es' : '' }}</div>
    </button>
    <button type="button" class="kpi neg-tab-btn" data-tab="deudas" onclick="switchNegocio('deudas', this)">
        <div class="bar" style="background:var(--blue)"></div>
        <div class="kpi-label">Deudas <span class="kpi-count">{{ $deudasCount }}</span></div>
        <div class="kpi-val">${{ number_format($deudasTotal, 0) }}</div>
        <div class="kpi-sub">{{ count($proveedoresDeudas) }} proveedor{{ count($proveedoresDeudas) !== 1 ? 'es' : '' }} con adeudo</div>
    </button>
    <button type="button" class="kpi neg-tab-btn" data-tab="cobrado" onclick="switchNegocio('cobrado', this)">
        <div class="bar" style="background:var(--purple)"></div>
        <div class="kpi-label">Cobrado <span class="kpi-count">{{ $cobradoCount }}</span></div>
        <div class="kpi-val">${{ number_format($cobradoTotal, 0) }}</div>
        <div class="kpi-sub">{{ count($proveedoresCobrado) }} proveedor{{ count($proveedoresCobrado) !== 1 ? 'es' : '' }} pagados</div>
    </button>
</div>

@php
    $paneles = [
        'ventas' => ['label' => 'Ventas totales', 'lista' => $proveedoresVentas, 'desc' => 'Ventas por proveedor (facturas sin cancelar)'],
        'deudas' => ['label' => 'Deudas', 'lista' => $proveedoresDeudas, 'desc' => 'Facturas pendientes de pago a proveedores'],
        'cobrado' => ['label' => 'Cobrado', 'lista' => $proveedoresCobrado, 'desc' => 'Facturas pagadas a proveedores'],
    ];
@endphp

@foreach($paneles as $key => $panel)
<div class="neg-detail anim" id="panel-{{ $key }}">
    <div class="section-meta">
        <strong>{{ $panel['label'] }}</strong> — {{ $panel['desc'] }}
    </div>
    @if(count($panel['lista']))
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proveedor</th>
                    <th class="num">Score</th>
                    <th class="num">Facturas</th>
                    <th class="num">Monto</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($panel['lista'] as $prov)
                <tr>
                    <td class="code-col">{{ $prov['codigo'] ?? '—' }}</td>
                    <td class="name-col">{{ $prov['nombre'] }}</td>
                    <td class="num">{{ $prov['score'] > 0 ? number_format($prov['score'], 0).'%' : '—' }}</td>
                    <td class="num">{{ $prov['facturas'] }}</td>
                    <td class="num">${{ number_format($prov['monto'], 2) }}</td>
                    <td>
                        @if($prov['codigo'])
                        <a href="{{ route('admin.proveedor-facturas', $prov['codigo']) }}" style="font-size:12px;font-weight:600;color:var(--purple);text-decoration:none;">Ver facturas →</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total ({{ count($panel['lista']) }} proveedores)</td>
                    <td class="num">{{ collect($panel['lista'])->sum('facturas') }}</td>
                    <td class="num">${{ number_format(collect($panel['lista'])->sum('monto'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @else
    <div class="empty-state">No hay facturas de proveedores en esta categoría.</div>
    @endif
</div>
@endforeach

<div class="chart-card anim">
    <div class="chart-head">
        <div>
            <h3>Ventas por proveedor — últimos 6 meses</h3>
            <p>Desglose cobrado vs pendiente · línea = número de facturas</p>
        </div>
        <div class="chart-stats">
            <div class="chart-stat">
                <div class="chart-stat-label">Total 6 meses</div>
                <div class="chart-stat-val">${{ number_format($chartTotal6m, 0) }}</div>
            </div>
            <div class="chart-stat">
                <div class="chart-stat-label">Promedio mensual</div>
                <div class="chart-stat-val">${{ number_format($chartPromedio6m, 0) }}</div>
            </div>
            @if($chartMesPico && $chartMesPico['monto'] > 0)
            <div class="chart-stat">
                <div class="chart-stat-label">Mes más alto</div>
                <div class="chart-stat-val">{{ $chartMesPico['mes_corto'] }} · ${{ number_format($chartMesPico['monto'], 0) }}</div>
            </div>
            @endif
        </div>
    </div>
    <div class="chart-wrap"><canvas id="chartVentasProveedor"></canvas></div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
(function() {
    const SC = SALCOM_COLORS;
    const fmtMoney = function(v) {
        if (v >= 1000000) return '$' + (v / 1000000).toFixed(1) + 'M';
        if (v >= 1000) return '$' + Math.round(v / 1000) + 'k';
        return '$' + v.toLocaleString('es-MX');
    };

    new Chart(document.getElementById('chartVentasProveedor'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($ventasPorMes->pluck('mes_corto')) !!},
            datasets: [
                {
                    label: 'Cobrado',
                    data: {!! json_encode($ventasPorMes->pluck('monto_pagado')) !!},
                    backgroundColor: SC.greenLight,
                    borderColor: SC.green,
                    borderWidth: 2,
                    borderRadius: { topLeft: 0, topRight: 0, bottomLeft: 8, bottomRight: 8 },
                    borderSkipped: false,
                    stack: 'montos',
                    order: 2,
                },
                {
                    label: 'Pendiente',
                    data: {!! json_encode($ventasPorMes->pluck('monto_pendiente')) !!},
                    backgroundColor: SC.amberLight,
                    borderColor: SC.amber,
                    borderWidth: 2,
                    borderRadius: { topLeft: 8, topRight: 8, bottomLeft: 0, bottomRight: 0 },
                    borderSkipped: false,
                    stack: 'montos',
                    order: 3,
                },
                {
                    type: 'line',
                    label: 'Facturas',
                    data: {!! json_encode($ventasPorMes->pluck('facturas')) !!},
                    borderColor: SC.purple,
                    backgroundColor: SC.purpleLight,
                    borderWidth: 2.5,
                    pointRadius: 5,
                    pointBackgroundColor: SC.purple,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7,
                    tension: 0.35,
                    yAxisID: 'y1',
                    order: 1,
                },
            ],
        },
        options: {
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 18 },
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if (ctx.dataset.yAxisID === 'y1' || ctx.dataset.type === 'line') {
                                return ' Facturas: ' + ctx.parsed.y;
                            }
                            return ' ' + ctx.dataset.label + ': $' + ctx.parsed.y.toLocaleString('es-MX', { minimumFractionDigits: 0 });
                        },
                        footer: function(items) {
                            const i = items[0]?.dataIndex;
                            if (i === undefined) return '';
                            const total = {!! json_encode($ventasPorMes->pluck('monto')) !!}[i] || 0;
                            return 'Total mes: $' + total.toLocaleString('es-MX');
                        },
                    },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { callback: fmtMoney },
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: { drawOnChartArea: false },
                    ticks: {
                        stepSize: 1,
                        callback: function(v) { return Number.isInteger(v) ? v : ''; },
                    },
                },
            },
        },
    });
})();

function switchNegocio(tab, btn) {
    var panel = document.getElementById('panel-' + tab);
    var wasActive = btn.classList.contains('active');

    document.querySelectorAll('.neg-tab-btn').forEach(function(b) { b.classList.remove('active'); });
    document.querySelectorAll('.neg-detail').forEach(function(p) { p.classList.remove('active'); });

    if (wasActive) {
        return;
    }

    btn.classList.add('active');
    if (panel) {
        panel.classList.add('active');
    }
}
</script>
@endpush
