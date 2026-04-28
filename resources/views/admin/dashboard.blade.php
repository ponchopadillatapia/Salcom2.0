@extends('layouts.admin')
@section('title', 'Dashboard')
@section('hero')
<div class="hero-band">
    <h1><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:6px"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>Dashboard — Panel Administrativo</h1>
    <p>Resumen general de Industrias Salcom · {{ now()->format('d/m/Y') }}</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .45s cubic-bezier(.4,0,.2,1) both}
    .anim-d1{animation-delay:.05s}.anim-d2{animation-delay:.10s}.anim-d3{animation-delay:.15s}.anim-d4{animation-delay:.20s}
    .anim-d5{animation-delay:.25s}.anim-d6{animation-delay:.30s}.anim-d7{animation-delay:.35s}.anim-d8{animation-delay:.40s}

    .metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .metric{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px 22px;position:relative;overflow:hidden;transition:all .25s cubic-bezier(.4,0,.2,1);cursor:pointer;text-decoration:none;display:block;color:inherit}
    .metric:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.06);border-color:var(--purple-mid)}
    .metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .metric .metric-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
    .metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;letter-spacing:.3px;text-transform:uppercase;margin-bottom:6px}
    .metric-val{font-size:30px;font-weight:800;color:var(--gray-text);line-height:1;letter-spacing:-1px;font-variant-numeric:tabular-nums}
    .metric-sub{font-size:12px;color:var(--gray-muted);margin-top:6px;font-weight:500}

    .dept-tabs{display:flex;gap:4px;background:var(--gray-soft);border-radius:12px;padding:4px;margin-bottom:24px;width:fit-content}
    .dept-tab{padding:10px 24px;font-size:13px;font-weight:600;color:var(--gray-muted);cursor:pointer;border:none;background:none;border-radius:10px;font-family:inherit;transition:all .2s cubic-bezier(.4,0,.2,1)}
    .dept-tab:hover{color:var(--purple);background:rgba(107,63,160,.06)}
    .dept-tab.active{color:var(--purple);background:var(--white);box-shadow:0 1px 4px rgba(0,0,0,.06)}
    .dept-panel{display:none}.dept-panel.active{display:block;animation:fadeUp .35s ease both}

    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .dash-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden;transition:all .25s cubic-bezier(.4,0,.2,1)}
    .dash-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.05)}
    .dash-card-head{padding:16px 22px;border-bottom:1px solid var(--border-light);font-size:14px;font-weight:700;color:var(--gray-text);display:flex;align-items:center;gap:10px;background:var(--gray-soft)}
    .dash-card-head svg{flex-shrink:0}
    .dash-card-body{padding:22px}

    .dash-table{width:100%;border-collapse:collapse}
    .dash-table th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.8px;padding:10px 14px;text-align:left;border-bottom:1px solid var(--border-light)}
    .dash-table td{padding:12px 14px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .dash-table tr:last-child td{border-bottom:none}
    .dash-table tbody tr{transition:background .15s}
    .dash-table tbody tr:hover td{background:var(--purple-subtle)}

    .badge-estatus{font-size:11px;font-weight:600;padding:4px 12px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-estatus.validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.procesando{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.enviado{background:#ede9fe;color:#7c3aed}
    .badge-estatus.entregado{background:var(--green-bg);color:var(--green)}
    .badge-estatus.cancelado{background:var(--red-bg);color:var(--red)}

    .score-bar{width:64px;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .score-fill{height:100%;border-radius:4px;transition:width .6s cubic-bezier(.4,0,.2,1)}

    .empty-state-card{text-align:center;padding:40px 20px;color:var(--gray-muted)}
    .empty-state-card .empty-icon{width:56px;height:56px;border-radius:16px;background:var(--gray-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
    .empty-state-card p{font-size:13px;font-weight:500;margin:0}
    .empty-state-card .empty-hint{font-size:12px;color:var(--gray-muted);margin-top:4px;opacity:.7}

    .quick-actions{display:flex;gap:12px;flex-wrap:wrap}
    .qa-btn{padding:11px 22px;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s cubic-bezier(.4,0,.2,1);display:inline-flex;align-items:center;gap:8px}
    .qa-btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
    .qa-btn.primary{background:var(--purple);color:#fff}.qa-btn.primary:hover{background:var(--purple-dark)}
    .qa-btn.outline{border:1.5px solid var(--border);color:var(--gray-text);background:var(--white)}
    .qa-btn.outline:hover{border-color:var(--purple-mid);color:var(--purple);background:var(--purple-subtle)}

    .chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .chart-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px}
    .chart-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;transition:all .25s cubic-bezier(.4,0,.2,1)}
    .chart-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.05)}
    .chart-title{font-size:13px;font-weight:700;color:var(--gray-text);margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .chart-wrap{position:relative;width:100%;max-height:260px}

    @media(max-width:900px){.chart-grid,.chart-grid-3{grid-template-columns:1fr}}

    @media(max-width:900px){.metrics{grid-template-columns:1fr 1fr}.grid-2{grid-template-columns:1fr}}
    @media(max-width:500px){.metrics{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

{{-- ═══ MÉTRICAS FILA 1 ═══ --}}
<div class="metrics">
    <a href="{{ route('admin.clientes') }}" class="metric anim anim-d1">
        <div class="accent" style="background:var(--purple)"></div>
        <div class="metric-icon" style="background:var(--purple-light)">@include('partials.icons', ['name'=>'users','size'=>20,'color'=>'var(--purple)'])</div>
        <div class="metric-label">Clientes</div>
        <div class="metric-val">{{ $totalClientes }}</div>
        <div class="metric-sub">{{ $clientesActivos }} activos</div>
    </a>
    <a href="{{ route('admin.proveedores') }}" class="metric anim anim-d2">
        <div class="accent" style="background:var(--green)"></div>
        <div class="metric-icon" style="background:var(--green-bg)">@include('partials.icons', ['name'=>'factory','size'=>20,'color'=>'var(--green)'])</div>
        <div class="metric-label">Proveedores</div>
        <div class="metric-val">{{ $totalProveedores }}</div>
        <div class="metric-sub">{{ $proveedoresActivos }} activos · Score {{ $scorePromedio }}%</div>
    </a>
    <a href="{{ route('admin.pedidos') }}" class="metric anim anim-d3">
        <div class="accent" style="background:var(--blue)"></div>
        <div class="metric-icon" style="background:var(--blue-bg)">@include('partials.icons', ['name'=>'package','size'=>20,'color'=>'var(--blue)'])</div>
        <div class="metric-label">Pedidos</div>
        <div class="metric-val">{{ $totalPedidos }}</div>
        <div class="metric-sub">{{ $pedidosPendientes }} pendientes · ${{ number_format($montoPedidos, 0) }}</div>
    </a>
    <a href="{{ route('admin.productos') }}" class="metric anim anim-d4">
        <div class="accent" style="background:var(--amber)"></div>
        <div class="metric-icon" style="background:var(--amber-bg)">@include('partials.icons', ['name'=>'flask','size'=>20,'color'=>'var(--amber)'])</div>
        <div class="metric-label">Productos</div>
        <div class="metric-val">{{ $totalProductos }}</div>
        <div class="metric-sub">{{ $sinStock }} sin stock</div>
    </a>
</div>

{{-- ═══ MÉTRICAS FILA 2 ═══ --}}
<div class="metrics">
    <a href="{{ route('admin.facturas', ['estatus' => 'pendiente']) }}" class="metric anim anim-d5">
        <div class="accent" style="background:var(--red)"></div>
        <div class="metric-icon" style="background:var(--red-bg)">@include('partials.icons', ['name'=>'dollar','size'=>20,'color'=>'var(--red)'])</div>
        <div class="metric-label">Facturas pendientes</div>
        <div class="metric-val">{{ $facturasPendientes }}</div>
        <div class="metric-sub">${{ number_format($montoFacturas, 0) }} por cobrar</div>
    </a>
    <a href="{{ route('admin.encuestas') }}" class="metric anim anim-d6">
        <div class="accent" style="background:#7c3aed"></div>
        <div class="metric-icon" style="background:#ede9fe">@include('partials.icons', ['name'=>'star','size'=>20,'color'=>'#7c3aed'])</div>
        <div class="metric-label">Encuestas</div>
        <div class="metric-val">{{ $totalEncuestas }}</div>
        <div class="metric-sub">Calificación prom: {{ $calificacionProm ?: '—' }}/5</div>
    </a>
    <a href="{{ route('muestras.admin') }}" class="metric anim anim-d7">
        <div class="accent" style="background:var(--amber)"></div>
        <div class="metric-icon" style="background:var(--amber-bg)">@include('partials.icons', ['name'=>'microscope','size'=>20,'color'=>'var(--amber)'])</div>
        <div class="metric-label">Muestras activas</div>
        <div class="metric-val">{{ $muestrasActivas }}</div>
        <div class="metric-sub">En proceso de validación</div>
    </a>
    <a href="{{ route('admin.documentos', ['estatus' => 'pendiente']) }}" class="metric anim anim-d8">
        <div class="accent" style="background:var(--red)"></div>
        <div class="metric-icon" style="background:var(--red-bg)">@include('partials.icons', ['name'=>'file-text','size'=>20,'color'=>'var(--red)'])</div>
        <div class="metric-label">Docs. pendientes</div>
        <div class="metric-val">{{ $docsPendientes }}</div>
        <div class="metric-sub">Documentos por revisar</div>
    </a>
</div>

{{-- ═══ TABS ═══ --}}
<div class="dept-tabs anim" style="animation-delay:.42s">
    <button class="dept-tab active" onclick="switchDept('general')">General</button>
    <button class="dept-tab" onclick="switchDept('clientes')">Clientes</button>
    <button class="dept-tab" onclick="switchDept('proveedores')">Proveedores</button>
</div>

{{-- ═══ PANEL GENERAL ═══ --}}
<div class="dept-panel active" id="panel-general">

    {{-- Gráficas principales --}}
    <div class="chart-grid" style="margin-bottom:20px">
        <div class="chart-card">
            <div class="chart-title">@include('partials.icons', ['name'=>'bar-chart','size'=>16,'color'=>'var(--purple)']) Pedidos por mes (últimos 6 meses)</div>
            <div class="chart-wrap"><canvas id="chartPedidosMes"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title">@include('partials.icons', ['name'=>'package','size'=>16,'color'=>'var(--blue)']) Pedidos por estatus</div>
            <div class="chart-wrap"><canvas id="chartPedidosEstatus"></canvas></div>
        </div>
    </div>

    <div class="chart-grid" style="margin-bottom:20px">
        <div class="chart-card">
            <div class="chart-title">@include('partials.icons', ['name'=>'dollar','size'=>16,'color'=>'var(--green)']) Facturación por estatus</div>
            <div class="chart-wrap"><canvas id="chartFacturas"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title">@include('partials.icons', ['name'=>'microscope','size'=>16,'color'=>'var(--amber)']) Muestras por etapa</div>
            <div class="chart-wrap"><canvas id="chartMuestras"></canvas></div>
        </div>
    </div>

    <div class="grid-2">
        <div class="dash-card">
            <div class="dash-card-head">@include('partials.icons', ['name'=>'package','size'=>16,'color'=>'var(--purple)']) Últimos pedidos</div>
            @if($ultimosPedidos->count())
            <table class="dash-table">
                <thead><tr><th>Folio</th><th>Cliente</th><th>Total</th><th>Estatus</th><th>Fecha</th></tr></thead>
                <tbody>
                @foreach($ultimosPedidos as $p)
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $p->folio }}</td>
                    <td>{{ $p->nombre_cliente }}</td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($p->total, 2) }}</td>
                    <td><span class="badge-estatus {{ $p->estatus }}">{{ ucfirst($p->estatus) }}</span></td>
                    <td style="color:var(--gray-muted)">{{ $p->created_at?->format('d/m/Y') }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state-card">
                <div class="empty-icon">@include('partials.icons', ['name'=>'package','size'=>24,'color'=>'var(--gray-muted)'])</div>
                <p>No hay pedidos registrados</p>
                <div class="empty-hint">Los pedidos aparecerán aquí cuando se creen</div>
            </div>
            @endif
        </div>

        <div class="dash-card">
            <div class="dash-card-head">@include('partials.icons', ['name'=>'trophy','size'=>16,'color'=>'var(--purple)']) Top proveedores por score</div>
            @if($topProveedores->count())
            <table class="dash-table">
                <thead><tr><th>Proveedor</th><th>Score</th><th>Entrega</th><th>Puntualidad</th></tr></thead>
                <tbody>
                @foreach($topProveedores as $pv)
                @php $sc = $pv->score_total; $cls = $sc >= 70 ? 'var(--green)' : ($sc >= 40 ? 'var(--amber)' : 'var(--red)'); @endphp
                <tr>
                    <td style="font-weight:700">{{ $pv->nombre ?? $pv->usuario }}</td>
                    <td><div class="score-bar"><div class="score-fill" style="width:{{ $sc }}%;background:{{ $cls }}"></div></div><strong>{{ number_format($sc, 0) }}%</strong></td>
                    <td>{{ number_format($pv->score_entrega, 0) }}%</td>
                    <td>{{ number_format($pv->score_puntualidad, 0) }}%</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state-card">
                <div class="empty-icon">@include('partials.icons', ['name'=>'factory','size'=>24,'color'=>'var(--gray-muted)'])</div>
                <p>No hay proveedores con score aún</p>
                <div class="empty-hint">El score se calcula con entregas y puntualidad</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══ PANEL CLIENTES ═══ --}}
<div class="dept-panel" id="panel-clientes">
    <div class="metrics">
        <div class="metric">
            <div class="accent" style="background:var(--purple)"></div>
            <div class="metric-icon" style="background:var(--purple-light)">@include('partials.icons', ['name'=>'users','size'=>20,'color'=>'var(--purple)'])</div>
            <div class="metric-label">Total clientes</div>
            <div class="metric-val">{{ $totalClientes }}</div>
            <div class="metric-sub">{{ $clientesActivos }} activos</div>
        </div>
        <div class="metric">
            <div class="accent" style="background:var(--blue)"></div>
            <div class="metric-icon" style="background:var(--blue-bg)">@include('partials.icons', ['name'=>'package','size'=>20,'color'=>'var(--blue)'])</div>
            <div class="metric-label">Pedidos totales</div>
            <div class="metric-val">{{ $totalPedidos }}</div>
            <div class="metric-sub">{{ $pedidosEntregados }} entregados</div>
        </div>
        <div class="metric">
            <div class="accent" style="background:var(--green)"></div>
            <div class="metric-icon" style="background:var(--green-bg)">@include('partials.icons', ['name'=>'banknote','size'=>20,'color'=>'var(--green)'])</div>
            <div class="metric-label">Monto total</div>
            <div class="metric-val">${{ number_format($montoPedidos, 0) }}</div>
            <div class="metric-sub">Todos los pedidos</div>
        </div>
        <div class="metric">
            <div class="accent" style="background:#7c3aed"></div>
            <div class="metric-icon" style="background:#ede9fe">@include('partials.icons', ['name'=>'star','size'=>20,'color'=>'#7c3aed'])</div>
            <div class="metric-label">Satisfacción</div>
            <div class="metric-val">{{ $calificacionProm ?: '—' }}<span style="font-size:16px;font-weight:500;color:var(--gray-muted)">/5</span></div>
            <div class="metric-sub">{{ $totalEncuestas }} encuestas</div>
        </div>
    </div>

    <div class="chart-grid" style="margin-bottom:20px">
        <div class="chart-card">
            <div class="chart-title">@include('partials.icons', ['name'=>'users','size'=>16,'color'=>'var(--purple)']) Estado de clientes</div>
            <div class="chart-wrap"><canvas id="chartClientesEstado"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title">@include('partials.icons', ['name'=>'star','size'=>16,'color'=>'#7c3aed']) Satisfacción por cliente</div>
            <div class="chart-wrap"><canvas id="chartEncuestasCliente"></canvas></div>
        </div>
    </div>
    <div class="dash-card">
        <div class="dash-card-head">@include('partials.icons', ['name'=>'zap','size'=>16,'color'=>'var(--purple)']) Acciones rápidas</div>
        <div class="dash-card-body">
            <div class="quick-actions">
                <a href="{{ route('admin.cliente.alta') }}" class="qa-btn primary">+ Alta de cliente</a>
                <a href="{{ route('admin.clientes') }}" class="qa-btn outline">Ver lista de clientes</a>
                <a href="{{ route('admin.encuestas') }}" class="qa-btn outline">Ver encuestas</a>
                <a href="{{ route('admin.pedidos') }}" class="qa-btn outline">Ver pedidos</a>
            </div>
        </div>
    </div>
</div>

{{-- ═══ PANEL PROVEEDORES ═══ --}}
<div class="dept-panel" id="panel-proveedores">
    <div class="metrics">
        <div class="metric">
            <div class="accent" style="background:var(--green)"></div>
            <div class="metric-icon" style="background:var(--green-bg)">@include('partials.icons', ['name'=>'factory','size'=>20,'color'=>'var(--green)'])</div>
            <div class="metric-label">Total proveedores</div>
            <div class="metric-val">{{ $totalProveedores }}</div>
            <div class="metric-sub">{{ $proveedoresActivos }} activos</div>
        </div>
        <div class="metric">
            <div class="accent" style="background:var(--purple)"></div>
            <div class="metric-icon" style="background:var(--purple-light)">@include('partials.icons', ['name'=>'bar-chart','size'=>20,'color'=>'var(--purple)'])</div>
            <div class="metric-label">Score promedio</div>
            <div class="metric-val">{{ $scorePromedio }}<span style="font-size:16px;font-weight:500;color:var(--gray-muted)">%</span></div>
            <div class="metric-sub">50% entrega + 50% puntualidad</div>
        </div>
        <div class="metric">
            <div class="accent" style="background:var(--amber)"></div>
            <div class="metric-icon" style="background:var(--amber-bg)">@include('partials.icons', ['name'=>'microscope','size'=>20,'color'=>'var(--amber)'])</div>
            <div class="metric-label">Muestras activas</div>
            <div class="metric-val">{{ $muestrasActivas }}</div>
            <div class="metric-sub">En validación</div>
        </div>
        <div class="metric">
            <div class="accent" style="background:var(--red)"></div>
            <div class="metric-icon" style="background:var(--red-bg)">@include('partials.icons', ['name'=>'file-text','size'=>20,'color'=>'var(--red)'])</div>
            <div class="metric-label">Docs. pendientes</div>
            <div class="metric-val">{{ $docsPendientes }}</div>
            <div class="metric-sub">Por revisar</div>
        </div>
    </div>

    <div class="chart-grid" style="margin-bottom:20px">
        <div class="chart-card">
            <div class="chart-title">@include('partials.icons', ['name'=>'factory','size'=>16,'color'=>'var(--green)']) Estado de proveedores</div>
            <div class="chart-wrap"><canvas id="chartProvEstado"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="chart-title">@include('partials.icons', ['name'=>'trophy','size'=>16,'color'=>'var(--purple)']) Score por proveedor (Entrega vs Puntualidad)</div>
            <div class="chart-wrap"><canvas id="chartProvScore"></canvas></div>
        </div>
    </div>
    <div class="dash-card">
        <div class="dash-card-head">@include('partials.icons', ['name'=>'zap','size'=>16,'color'=>'var(--purple)']) Acciones rápidas</div>
        <div class="dash-card-body">
            <div class="quick-actions">
                <a href="{{ route('admin.proveedores') }}" class="qa-btn primary">Proveedores / Score</a>
                <a href="{{ route('muestras.admin') }}" class="qa-btn outline">Muestras</a>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
function switchDept(dept) {
    document.querySelectorAll('.dept-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.dept-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + dept).classList.add('active');
    event.currentTarget.classList.add('active');
}

const COLORS = {
    purple: '#6B3FA0', green: '#34c759', blue: '#007aff', amber: '#ff9f0a',
    red: '#ff3b30', violet: '#7c3aed', gray: '#86868b',
    purpleLight: 'rgba(107,63,160,.15)', greenLight: 'rgba(52,199,89,.15)',
    blueLight: 'rgba(0,122,255,.15)', amberLight: 'rgba(255,159,10,.15)',
    redLight: 'rgba(255,59,48,.15)'
};
const DEFAULTS = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { font: { family: 'Inter', size: 11 }, padding: 14 } } } };

// ═══ GENERAL: Pedidos por mes ═══
new Chart(document.getElementById('chartPedidosMes'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($pedidosPorMes->pluck('mes')) !!},
        datasets: [{
            label: 'Pedidos',
            data: {!! json_encode($pedidosPorMes->pluck('total')) !!},
            backgroundColor: COLORS.purpleLight,
            borderColor: COLORS.purple,
            borderWidth: 2, borderRadius: 8, barPercentage: 0.6
        }, {
            label: 'Monto ($K)',
            data: {!! json_encode($pedidosPorMes->pluck('monto')->map(fn($v) => round($v/1000, 1))) !!},
            type: 'line',
            borderColor: COLORS.blue,
            backgroundColor: COLORS.blueLight,
            tension: 0.4, pointRadius: 4, pointBackgroundColor: COLORS.blue,
            yAxisID: 'y1'
        }]
    },
    options: { ...DEFAULTS, scales: {
        y: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 } } },
        y1: { position: 'right', beginAtZero: true, grid: { display: false }, ticks: { font: { size: 10 }, callback: v => '$'+v+'K' } },
        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }}
});

// ═══ GENERAL: Pedidos por estatus (dona) ═══
new Chart(document.getElementById('chartPedidosEstatus'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($pedidosPorEstatus->keys()->map(fn($e) => ucfirst($e))) !!},
        datasets: [{
            data: {!! json_encode($pedidosPorEstatus->values()) !!},
            backgroundColor: [COLORS.amber, COLORS.blue, COLORS.violet, COLORS.green, COLORS.red, COLORS.gray],
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { ...DEFAULTS, cutout: '65%', plugins: { ...DEFAULTS.plugins, legend: { position: 'right', labels: { font: { family: 'Inter', size: 11 }, padding: 10, usePointStyle: true, pointStyle: 'circle' } } } }
});

// ═══ GENERAL: Facturas por estatus ═══
new Chart(document.getElementById('chartFacturas'), {
    type: 'doughnut',
    data: {
        labels: ['Pagadas (${{ number_format($facturasPorEstatus->get("pagada")->monto ?? 0, 0) }})', 'Pendientes (${{ number_format($facturasPorEstatus->get("pendiente")->monto ?? 0, 0) }})', 'Canceladas'],
        datasets: [{
            data: [{{ $facturasPorEstatus->get('pagada')->total ?? 0 }}, {{ $facturasPorEstatus->get('pendiente')->total ?? 0 }}, {{ $facturasPorEstatus->get('cancelada')->total ?? 0 }}],
            backgroundColor: [COLORS.green, COLORS.amber, COLORS.red],
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { ...DEFAULTS, cutout: '65%', plugins: { ...DEFAULTS.plugins, legend: { position: 'right', labels: { font: { family: 'Inter', size: 11 }, padding: 10, usePointStyle: true, pointStyle: 'circle' } } } }
});

// ═══ GENERAL: Muestras por etapa ═══
new Chart(document.getElementById('chartMuestras'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($muestrasPorEtapa->keys()->map(fn($e) => ucfirst($e))) !!},
        datasets: [{
            label: 'Muestras',
            data: {!! json_encode($muestrasPorEtapa->values()) !!},
            backgroundColor: [COLORS.amberLight, COLORS.blueLight, COLORS.purpleLight, COLORS.greenLight, COLORS.amberLight, COLORS.greenLight, COLORS.redLight],
            borderColor: [COLORS.amber, COLORS.blue, COLORS.purple, COLORS.green, COLORS.amber, COLORS.green, COLORS.red],
            borderWidth: 2, borderRadius: 8, barPercentage: 0.6
        }]
    },
    options: { ...DEFAULTS, indexAxis: 'y', plugins: { legend: { display: false } }, scales: {
        x: { beginAtZero: true, grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 }, stepSize: 1 } },
        y: { grid: { display: false }, ticks: { font: { size: 11 } } }
    }}
});

// ═══ CLIENTES: Estado activo/inactivo ═══
new Chart(document.getElementById('chartClientesEstado'), {
    type: 'doughnut',
    data: {
        labels: ['Activos', 'Inactivos'],
        datasets: [{
            data: [{{ $clientesActivos }}, {{ $clientesInactivos }}],
            backgroundColor: [COLORS.green, COLORS.red],
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { ...DEFAULTS, cutout: '65%', plugins: { ...DEFAULTS.plugins, legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 12 }, padding: 16, usePointStyle: true, pointStyle: 'circle' } } } }
});

// ═══ CLIENTES: Satisfacción por cliente ═══
new Chart(document.getElementById('chartEncuestasCliente'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($encuestasPorCliente->pluck('codigo_cliente')) !!},
        datasets: [{
            label: 'Calificación promedio',
            data: {!! json_encode($encuestasPorCliente->pluck('prom')->map(fn($v) => round($v, 1))) !!},
            backgroundColor: COLORS.purpleLight,
            borderColor: COLORS.purple,
            borderWidth: 2, borderRadius: 8, barPercentage: 0.5
        }]
    },
    options: { ...DEFAULTS, plugins: { legend: { display: false } }, scales: {
        y: { beginAtZero: true, max: 5, grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 }, stepSize: 1 } },
        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }}
});

// ═══ PROVEEDORES: Estado activo/inactivo ═══
new Chart(document.getElementById('chartProvEstado'), {
    type: 'doughnut',
    data: {
        labels: ['Activos', 'Inactivos'],
        datasets: [{
            data: [{{ $proveedoresActivos }}, {{ $proveedoresInactivos }}],
            backgroundColor: [COLORS.green, COLORS.red],
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: { ...DEFAULTS, cutout: '65%', plugins: { ...DEFAULTS.plugins, legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 12 }, padding: 16, usePointStyle: true, pointStyle: 'circle' } } } }
});

// ═══ PROVEEDORES: Score por proveedor ═══
new Chart(document.getElementById('chartProvScore'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($proveedoresScore->map(fn($p) => $p->nombre ? \Illuminate\Support\Str::limit($p->nombre, 20) : $p->usuario)) !!},
        datasets: [{
            label: 'Entrega',
            data: {!! json_encode($proveedoresScore->pluck('score_entrega')) !!},
            backgroundColor: COLORS.blueLight,
            borderColor: COLORS.blue,
            borderWidth: 2, borderRadius: 6, barPercentage: 0.4
        }, {
            label: 'Puntualidad',
            data: {!! json_encode($proveedoresScore->pluck('score_puntualidad')) !!},
            backgroundColor: COLORS.greenLight,
            borderColor: COLORS.green,
            borderWidth: 2, borderRadius: 6, barPercentage: 0.4
        }]
    },
    options: { ...DEFAULTS, scales: {
        y: { beginAtZero: true, max: 100, grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 }, callback: v => v+'%' } },
        x: { grid: { display: false }, ticks: { font: { size: 10 } } }
    }}
});
</script>
@endpush
