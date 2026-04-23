@extends('layouts.admin')
@section('title', 'Dashboard')
@section('hero')
<div class="hero-band">
    <h1>📊 Dashboard — Panel Administrativo</h1>
    <p>Resumen general de Industrias Salcom · {{ now()->format('d/m/Y') }}</p>
</div>
@endsection
@push('styles')
<style>
    .metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .metric{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:18px 20px;position:relative;overflow:hidden}
    .metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:12px 0 0 12px}
    .metric-label{font-size:12px;color:var(--gray-muted);font-weight:500;margin-bottom:6px;padding-left:8px}
    .metric-val{font-size:26px;font-weight:700;color:var(--purple-dark);padding-left:8px;line-height:1}
    .metric-sub{font-size:11px;color:var(--gray-muted);padding-left:8px;margin-top:4px}

    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px}
    .dash-card{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .dash-card-head{padding:14px 20px;border-bottom:1px solid var(--border);font-size:14px;font-weight:700;color:var(--purple-dark);display:flex;align-items:center;gap:8px}
    .dash-card-body{padding:20px}

    .dash-table{width:100%;border-collapse:collapse}
    .dash-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:8px 12px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .dash-table td{padding:10px 12px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .dash-table tr:last-child td{border-bottom:none}
    .dash-table tr:hover td{background:var(--purple-subtle)}

    .badge-estatus{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-estatus.validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.procesando{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.enviado{background:#ede9fe;color:#7c3aed}
    .badge-estatus.entregado{background:var(--green-bg);color:var(--green)}
    .badge-estatus.cancelado{background:var(--red-bg);color:var(--red)}

    .score-bar{width:60px;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px}
    .score-fill{height:100%;border-radius:4px}

    .dept-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
    .dept-tab{padding:10px 20px;font-size:13px;font-weight:600;color:var(--gray-muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;background:none;border-top:none;border-left:none;border-right:none;font-family:inherit}
    .dept-tab:hover{color:var(--purple)}
    .dept-tab.active{color:var(--purple);border-bottom-color:var(--purple)}
    .dept-panel{display:none}.dept-panel.active{display:block}

    .empty-msg{text-align:center;padding:24px;color:var(--gray-muted);font-size:13px}

    @media(max-width:900px){.metrics{grid-template-columns:1fr 1fr}.grid-2,.grid-3{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

{{-- MÉTRICAS GENERALES --}}
<div class="metrics">
    <div class="metric"><div class="accent" style="background:var(--purple)"></div><div class="metric-label">Clientes</div><div class="metric-val">{{ $totalClientes }}</div><div class="metric-sub">{{ $clientesActivos }} activos</div></div>
    <div class="metric"><div class="accent" style="background:var(--green)"></div><div class="metric-label">Proveedores</div><div class="metric-val">{{ $totalProveedores }}</div><div class="metric-sub">{{ $proveedoresActivos }} activos · Score prom: {{ $scorePromedio }}%</div></div>
    <div class="metric"><div class="accent" style="background:var(--blue)"></div><div class="metric-label">Pedidos</div><div class="metric-val">{{ $totalPedidos }}</div><div class="metric-sub">{{ $pedidosPendientes }} pendientes · ${{ number_format($montoPedidos, 0) }}</div></div>
    <div class="metric"><div class="accent" style="background:var(--amber)"></div><div class="metric-label">Productos</div><div class="metric-val">{{ $totalProductos }}</div><div class="metric-sub">{{ $sinStock }} sin stock</div></div>
</div>

<div class="metrics">
    <div class="metric"><div class="accent" style="background:var(--red)"></div><div class="metric-label">Facturas pendientes</div><div class="metric-val">{{ $facturasPendientes }}</div><div class="metric-sub">${{ number_format($montoFacturas, 0) }} por cobrar</div></div>
    <div class="metric"><div class="accent" style="background:#7c3aed"></div><div class="metric-label">Encuestas</div><div class="metric-val">{{ $totalEncuestas }}</div><div class="metric-sub">Calificación prom: {{ $calificacionProm ?: '—' }}/5</div></div>
    <div class="metric"><div class="accent" style="background:var(--amber)"></div><div class="metric-label">Muestras activas</div><div class="metric-val">{{ $muestrasActivas }}</div><div class="metric-sub">En proceso de validación</div></div>
    <div class="metric"><div class="accent" style="background:var(--red)"></div><div class="metric-label">Docs. pendientes</div><div class="metric-val">{{ $docsPendientes }}</div><div class="metric-sub">Documentos por revisar</div></div>
</div>

{{-- TABS POR DEPARTAMENTO --}}
<div class="dept-tabs">
    <button class="dept-tab active" onclick="switchDept('general')">General</button>
    <button class="dept-tab" onclick="switchDept('clientes')">Clientes</button>
    <button class="dept-tab" onclick="switchDept('proveedores')">Proveedores</button>
</div>

{{-- GENERAL --}}
<div class="dept-panel active" id="panel-general">
    <div class="grid-2">
        <div class="dash-card">
            <div class="dash-card-head">📦 Últimos pedidos</div>
            @if($ultimosPedidos->count())
            <table class="dash-table">
                <thead><tr><th>Folio</th><th>Cliente</th><th>Total</th><th>Estatus</th><th>Fecha</th></tr></thead>
                <tbody>
                @foreach($ultimosPedidos as $p)
                <tr>
                    <td style="font-weight:600;color:var(--purple)">{{ $p->folio }}</td>
                    <td>{{ $p->nombre_cliente }}</td>
                    <td>${{ number_format($p->total, 2) }}</td>
                    <td><span class="badge-estatus {{ $p->estatus }}">{{ ucfirst($p->estatus) }}</span></td>
                    <td>{{ $p->created_at?->format('d/m/Y') }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-msg">No hay pedidos registrados</div>
            @endif
        </div>

        <div class="dash-card">
            <div class="dash-card-head">🏭 Top proveedores por score</div>
            @if($topProveedores->count())
            <table class="dash-table">
                <thead><tr><th>Proveedor</th><th>Score</th><th>Entrega</th><th>Puntualidad</th></tr></thead>
                <tbody>
                @foreach($topProveedores as $pv)
                @php $sc = $pv->score_total; $cls = $sc >= 70 ? 'var(--green)' : ($sc >= 40 ? 'var(--amber)' : 'var(--red)'); @endphp
                <tr>
                    <td style="font-weight:600">{{ $pv->nombre ?? $pv->usuario }}</td>
                    <td><div class="score-bar"><div class="score-fill" style="width:{{ $sc }}%;background:{{ $cls }}"></div></div><strong>{{ number_format($sc, 0) }}%</strong></td>
                    <td>{{ number_format($pv->score_entrega, 0) }}%</td>
                    <td>{{ number_format($pv->score_puntualidad, 0) }}%</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-msg">No hay proveedores con score aún</div>
            @endif
        </div>
    </div>
</div>

{{-- CLIENTES --}}
<div class="dept-panel" id="panel-clientes">
    <div class="metrics">
        <div class="metric"><div class="accent" style="background:var(--purple)"></div><div class="metric-label">Total clientes</div><div class="metric-val">{{ $totalClientes }}</div><div class="metric-sub">{{ $clientesActivos }} activos</div></div>
        <div class="metric"><div class="accent" style="background:var(--blue)"></div><div class="metric-label">Pedidos totales</div><div class="metric-val">{{ $totalPedidos }}</div><div class="metric-sub">{{ $pedidosEntregados }} entregados</div></div>
        <div class="metric"><div class="accent" style="background:var(--green)"></div><div class="metric-label">Monto total</div><div class="metric-val">${{ number_format($montoPedidos, 0) }}</div><div class="metric-sub">Todos los pedidos</div></div>
        <div class="metric"><div class="accent" style="background:#7c3aed"></div><div class="metric-label">Satisfacción</div><div class="metric-val">{{ $calificacionProm ?: '—' }}/5</div><div class="metric-sub">{{ $totalEncuestas }} encuestas</div></div>
    </div>
    <div class="dash-card">
        <div class="dash-card-head">👥 Acciones rápidas</div>
        <div class="dash-card-body" style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="{{ route('admin.cliente.alta') }}" style="padding:10px 20px;background:var(--purple);color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none">+ Alta de cliente</a>
            <a href="{{ route('admin.clientes') }}" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:var(--gray-text)">Ver lista de clientes</a>
            <a href="{{ route('admin.encuestas') }}" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:var(--gray-text)">Ver encuestas</a>
            <a href="{{ route('admin.pedidos') }}" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:var(--gray-text)">Ver pedidos</a>
        </div>
    </div>
</div>

{{-- PROVEEDORES --}}
<div class="dept-panel" id="panel-proveedores">
    <div class="metrics">
        <div class="metric"><div class="accent" style="background:var(--green)"></div><div class="metric-label">Total proveedores</div><div class="metric-val">{{ $totalProveedores }}</div><div class="metric-sub">{{ $proveedoresActivos }} activos</div></div>
        <div class="metric"><div class="accent" style="background:var(--purple)"></div><div class="metric-label">Score promedio</div><div class="metric-val">{{ $scorePromedio }}%</div><div class="metric-sub">50% entrega + 50% puntualidad</div></div>
        <div class="metric"><div class="accent" style="background:var(--amber)"></div><div class="metric-label">Muestras activas</div><div class="metric-val">{{ $muestrasActivas }}</div><div class="metric-sub">En validación</div></div>
        <div class="metric"><div class="accent" style="background:var(--red)"></div><div class="metric-label">Docs. pendientes</div><div class="metric-val">{{ $docsPendientes }}</div><div class="metric-sub">Por revisar</div></div>
    </div>
    <div class="dash-card">
        <div class="dash-card-head">🏭 Acciones rápidas</div>
        <div class="dash-card-body" style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="{{ route('admin.proveedores') }}" style="padding:10px 20px;background:var(--purple);color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none">Ver proveedores / Score</a>
            <a href="{{ route('muestras.admin') }}" style="padding:10px 20px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;color:var(--gray-text)">Muestras</a>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function switchDept(dept) {
    document.querySelectorAll('.dept-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.dept-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + dept).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
@endpush
