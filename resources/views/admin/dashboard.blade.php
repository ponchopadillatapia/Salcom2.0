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
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}
    .anim-d1{animation-delay:.05s}.anim-d2{animation-delay:.1s}.anim-d3{animation-delay:.15s}
    .anim-d4{animation-delay:.2s}.anim-d5{animation-delay:.25s}

    .metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px}
    .metric{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:24px;position:relative;overflow:hidden;transition:all .25s cubic-bezier(.4,0,.2,1);text-decoration:none;display:block;color:inherit}
    .metric:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.07);border-color:var(--purple-mid)}
    .metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:16px 0 0 16px}
    .metric-top{display:flex;align-items:center;gap:14px;margin-bottom:16px}
    .metric-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .metric-label{font-size:13px;color:var(--gray-muted);font-weight:600;letter-spacing:.2px}
    .metric-val{font-size:32px;font-weight:800;color:var(--gray-text);line-height:1;letter-spacing:-1px;font-variant-numeric:tabular-nums;margin-bottom:4px}
    .metric-sub{font-size:12px;color:var(--gray-muted);font-weight:500}
    .metric-details{margin-top:14px;padding-top:12px;border-top:1px solid var(--border-light)}
    .metric-detail-row{display:flex;justify-content:space-between;align-items:center;font-size:12px;padding:4px 0}
    .metric-detail-row span:first-child{color:var(--gray-muted)}
    .metric-detail-row span:last-child{font-weight:700;color:var(--gray-text)}

    .metrics-row-2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:28px}

    .otif-gauges{display:flex;align-items:center;justify-content:center;gap:32px;margin-top:16px}
    .otif-gauge-item{display:flex;flex-direction:column;align-items:center}
    .otif-gauge-item canvas{display:block;width:110px!important;height:110px!important}
    .otif-gauge-label{font-size:11px;color:var(--gray-muted);font-weight:600;margin-top:8px}
    .otif-params{display:flex;gap:12px;margin-top:14px;padding-top:12px;border-top:1px solid var(--border-light);justify-content:center;flex-wrap:wrap}
    .otif-param{display:flex;align-items:center;gap:5px;font-size:10px;font-weight:600;color:var(--gray-text)}
    .otif-param-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

    .section-title{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}
    .chart-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;padding:24px;transition:box-shadow .25s}
    .chart-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.04)}
    .chart-title{font-size:13px;font-weight:700;color:var(--gray-text);margin-bottom:18px}
    .chart-wrap{position:relative;width:100%;height:240px}

    .dash-card{background:var(--white);border:1px solid var(--border-light);border-radius:16px;overflow:hidden;transition:box-shadow .25s}
    .dash-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.04)}
    .dash-card-head{padding:16px 22px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft)}
    .dash-table{width:100%;border-collapse:collapse}
    .dash-table th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.8px;padding:10px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .dash-table td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .dash-table tr:last-child td{border-bottom:none}
    .dash-table tbody tr:hover td{background:var(--purple-subtle)}
    .badge-estatus{font-size:11px;font-weight:600;padding:4px 12px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-estatus.validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.procesando{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.enviado{background:#ede9fe;color:#7c3aed}
    .badge-estatus.entregado{background:var(--green-bg);color:var(--green)}
    .badge-estatus.cancelado{background:var(--red-bg);color:var(--red)}
    .score-bar{width:60px;height:7px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px}
    .score-fill{height:100%;border-radius:4px}
    .empty-state{text-align:center;padding:36px 20px;color:var(--gray-muted);font-size:13px}

    @media(max-width:900px){.metrics,.metrics-row-2,.grid-2{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

{{-- ═══ FILA 1 ═══ --}}
<div class="metrics">
    <a href="{{ route('admin.proveedores') }}" class="metric anim anim-d1">
        <div class="accent" style="background:var(--green)"></div>
        <div class="metric-top">
            <div class="metric-icon" style="background:var(--green-bg)">@include('partials.icons', ['name'=>'factory','size'=>20,'color'=>'var(--green)'])</div>
            <div class="metric-label">Proveedores</div>
        </div>
        <div class="metric-val">{{ $totalProveedores }}</div>
        <div class="metric-sub">{{ $proveedoresActivos }} activos · Score {{ $scorePromedio }}%</div>
        <div class="metric-details">
            <div class="metric-detail-row"><span>Pedidos</span><span>{{ $totalPedidos }}</span></div>
            <div class="metric-detail-row"><span>Productos</span><span>{{ $totalProductos }}</span></div>
            <div class="metric-detail-row"><span>Facturas pendientes</span><span>{{ $facturasPendientes }}</span></div>
        </div>
    </a>

    <a href="{{ route('admin.negocio') }}" class="metric anim anim-d2">
        <div class="accent" style="background:var(--blue)"></div>
        <div class="metric-top">
            <div class="metric-icon" style="background:var(--blue-bg)">@include('partials.icons', ['name'=>'bar-chart','size'=>20,'color'=>'var(--blue)'])</div>
            <div class="metric-label">Negocio</div>
        </div>
        <div class="metric-val">${{ number_format($montoPedidos, 0) }}</div>
        <div class="metric-sub">Ventas totales</div>
        <div class="metric-details">
            <div class="metric-detail-row"><span>Entregados</span><span>{{ $pedidosEntregados }}</span></div>
            <div class="metric-detail-row"><span>Encuestas</span><span>{{ $totalEncuestas }}</span></div>
            <div class="metric-detail-row"><span>Calificación</span><span>{{ $calificacionProm ?: '—' }}/5</span></div>
        </div>
    </a>

    <a href="{{ route('admin.otif') }}" class="metric anim anim-d3">
        <div class="accent" style="background:var(--green)"></div>
        <div class="metric-top">
            <div class="metric-icon" style="background:var(--green-bg)">@include('partials.icons', ['name'=>'package','size'=>20,'color'=>'var(--green)'])</div>
            <div class="metric-label">OTIF</div>
        </div>
        <div class="otif-gauges">
            <div class="otif-gauge-item">
                <canvas id="gaugeOT" width="110" height="110"></canvas>
                <div class="otif-gauge-label">OT (On Time)</div>
            </div>
            <div class="otif-gauge-item">
                <canvas id="gaugeIF" width="110" height="110"></canvas>
                <div class="otif-gauge-label">IF (In Full)</div>
            </div>
        </div>
        <div class="otif-params">
            <div class="otif-param"><span class="otif-param-dot" style="background:#059669"></span>≥ 80% Óptimo</div>
            <div class="otif-param"><span class="otif-param-dot" style="background:#d97706"></span>50–79% Alerta</div>
            <div class="otif-param"><span class="otif-param-dot" style="background:#dc2626"></span>< 50% Crítico</div>
        </div>
        <div style="margin-top:10px;font-size:12px;color:var(--blue);font-weight:600">Ver detalle →</div>
    </a>
</div>

{{-- ═══ FILA 2 ═══ --}}
<div class="metrics-row-2">
    <a href="{{ route('admin.inventario') }}" class="metric anim anim-d4">
        <div class="accent" style="background:var(--amber)"></div>
        <div class="metric-top">
            <div class="metric-icon" style="background:var(--amber-bg)">@include('partials.icons', ['name'=>'flask','size'=>20,'color'=>'var(--amber)'])</div>
            <div class="metric-label">Inventario</div>
        </div>
        <div class="metric-val">{{ $totalProductos }}</div>
        <div class="metric-sub">{{ $sinStock }} agotados · {{ $totalProductos - $sinStock }} disponibles</div>
    </a>

    <a href="{{ route('admin.documentos', ['estatus' => 'pendiente']) }}" class="metric anim anim-d5">
        <div class="accent" style="background:var(--red)"></div>
        <div class="metric-top">
            <div class="metric-icon" style="background:var(--red-bg)">@include('partials.icons', ['name'=>'file-text','size'=>20,'color'=>'var(--red)'])</div>
            <div class="metric-label">Proveedor con docs. pendientes</div>
        </div>
        <div class="metric-val">{{ $docsPendientes }}</div>
        <div class="metric-sub">Documentos por revisar</div>
    </a>
</div>

{{-- ═══ GRÁFICAS ═══ --}}
<div class="section-title">Análisis</div>
<div class="grid-2">
    <div class="chart-card">
        <div class="chart-title">Pedidos por mes</div>
        <div class="chart-wrap"><canvas id="chartPedidosMes"></canvas></div>
    </div>
    <div class="chart-card">
        <div class="chart-title">Facturación</div>
        <div class="chart-wrap"><canvas id="chartFacturas"></canvas></div>
    </div>
</div>

{{-- ═══ TABLAS ═══ --}}
<div class="section-title">Actividad reciente</div>
<div class="grid-2">
    <div class="dash-card">
        <div class="dash-card-head">Últimos pedidos</div>
        @if($ultimosPedidos->count())
        <table class="dash-table">
            <thead><tr><th>Folio</th><th>Cliente</th><th>Total</th><th>Estatus</th></tr></thead>
            <tbody>
            @foreach($ultimosPedidos as $p)
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $p->folio }}</td>
                <td>{{ Str::limit($p->nombre_cliente, 25) }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($p->total, 0) }}</td>
                <td><span class="badge-estatus {{ $p->estatus }}">{{ ucfirst($p->estatus) }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">No hay pedidos</div>
        @endif
    </div>

    <div class="dash-card">
        <div class="dash-card-head">Top proveedores</div>
        @if($topProveedores->count())
        <table class="dash-table">
            <thead><tr><th>Proveedor</th><th>Score</th><th>Entrega</th></tr></thead>
            <tbody>
            @foreach($topProveedores as $pv)
            @php $sc = $pv->score_total; $cls = $sc >= 70 ? 'var(--green)' : ($sc >= 40 ? 'var(--amber)' : 'var(--red)'); @endphp
            <tr>
                <td style="font-weight:600">{{ Str::limit($pv->nombre ?? $pv->usuario, 22) }}</td>
                <td><div class="score-bar"><div class="score-fill" style="width:{{ $sc }}%;background:{{ $cls }}"></div></div><strong>{{ number_format($sc, 0) }}%</strong></td>
                <td>{{ number_format($pv->score_entrega, 0) }}%</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="empty-state">Sin proveedores con score</div>
        @endif
    </div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
const SC = SALCOM_COLORS;

// ── Gauge helper (dona con % al centro, fondo con colores de parámetros) ──
function createGauge(canvas, percent, color) {
    // El espacio vacío se divide en los colores de los niveles inferiores
    let segments, segColors;
    if (percent >= 80) {
        // Verde: el vacío es naranja + rojo
        const remaining = 100 - percent;
        segments = [percent, remaining * 0.6, remaining * 0.4];
        segColors = [color, '#d97706', '#dc2626'];
    } else if (percent >= 50) {
        // Naranja: el vacío es rojo
        segments = [percent, 100 - percent];
        segColors = [color, '#dc2626'];
    } else {
        // Rojo: todo es rojo, la parte llena es más oscura
        segments = [percent, 100 - percent];
        segColors = [color, '#fecaca'];
    }

    new Chart(canvas, {
        type: 'doughnut',
        data: { datasets: [{ data: segments, backgroundColor: segColors, borderWidth: 0, borderRadius: 14 }] },
        options: {
            responsive: false, cutout: '75%',
            plugins: { legend: { display: false }, tooltip: { enabled: false },
                centerText: { text: percent.toFixed(percent % 1 ? 1 : 0) + '%', color: color }
            },
            animation: { animateRotate: true, duration: 1000 }
        }
    });
}

// OT y IF gauges
@php
    $otPercent = $totalPedidos > 0 ? round(($pedidosEntregados / $totalPedidos) * 100, 1) : 0;
    $ifPercent = $totalPedidos > 0 ? round((($totalPedidos - \App\Models\Pedido::where('estatus','cancelado')->count()) / $totalPedidos) * 100, 1) : 0;
@endphp
createGauge(document.getElementById('gaugeOT'), {{ $otPercent }}, '#059669');
createGauge(document.getElementById('gaugeIF'), {{ $ifPercent }}, '#059669');

// Pedidos por mes (línea)
salcomChart.line(
    document.getElementById('chartPedidosMes'),
    {!! json_encode($pedidosPorMes->pluck('mes')) !!},
    {!! json_encode($pedidosPorMes->pluck('monto')) !!},
    { color: SC.purple, yFormat: v => '$' + Math.round(v/1000) + 'K' }
);

// Facturación (dona)
salcomChart.doughnut(
    document.getElementById('chartFacturas'),
    ['Pagadas', 'Pendientes', 'Canceladas'],
    [{{ $facturasPorEstatus->get('pagada')->total ?? 0 }}, {{ $facturasPorEstatus->get('pendiente')->total ?? 0 }}, {{ $facturasPorEstatus->get('cancelada')->total ?? 0 }}],
    [SC.green, SC.amber, SC.red],
    { legendPos: 'right' }
);
</script>
@endpush
