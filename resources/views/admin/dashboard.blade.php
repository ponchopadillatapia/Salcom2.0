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
    .pp-wrap{max-width:1140px;margin:0 auto}
    .pp-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
    .pp-card{background:var(--white);border:2px solid var(--purple);border-radius:var(--radius-lg);padding:22px;transition:var(--transition);box-shadow:var(--shadow-sm)}
    .pp-card:hover{border-color:var(--purple-dark);box-shadow:var(--shadow-md)}
    .pp-card h4{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:16px;text-transform:uppercase;letter-spacing:.3px}
    .pp-negocio-row{margin-bottom:12px}
    .pp-negocio-label{font-size:12px;color:var(--gray-muted);font-weight:500;margin-bottom:2px}
    .pp-negocio-value{font-size:24px;font-weight:700;color:var(--gray-text);display:flex;align-items:baseline;gap:10px}
    .pp-variation{font-size:14px;font-weight:700}
    .pp-variation-up{color:var(--green)}
    .pp-variation-down{color:var(--red)}
    .pp-negocio-sub{font-size:12px;color:var(--gray-muted);margin-top:4px}
    .pp-detail-link{font-size:13px;color:var(--blue);font-weight:600;text-decoration:none;display:inline-block;margin-top:8px}
    .pp-detail-link:hover{text-decoration:underline}
    .pp-otif-canvas-wrap{position:relative;width:80px;height:80px}
    .pp-otif-canvas-wrap canvas{position:absolute;top:0;left:0}
    .pp-otif-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
    .pp-otif-percent{font-size:14px;font-weight:700;color:var(--green);line-height:1}
    .pp-otif-label{font-size:10px;color:var(--gray-muted);font-weight:600;margin-top:4px}
    .pp-section-title{font-size:15px;font-weight:700;color:var(--gray-text);margin:16px 0 10px}
    .pp-quick-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
    .pp-quick-card{text-decoration:none;display:flex;align-items:center;gap:10px;padding:12px !important;min-height:auto;max-height:none}
    .pp-quick-icon{width:42px;height:42px;border-radius:12px;background:var(--purple-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:var(--transition)}
    .pp-quick-card:hover .pp-quick-icon{background:var(--purple);box-shadow:0 2px 8px rgba(107,63,160,0.25)}
    .pp-quick-card:hover .pp-quick-icon svg{stroke:white}
    .pp-quick-title{font-weight:600;color:var(--gray-text);font-size:13px}
    .pp-quick-sub{font-size:11px;color:var(--gray-muted);margin-top:2px}
    .pp-chart-wrap-xs{position:relative;width:72px;height:72px;flex-shrink:0}
    .pp-kpi-section{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:14px}
    .pp-kpi-section>a{display:flex;text-decoration:none;color:inherit;min-height:0}
    .pp-kpi-section .pp-card{flex:1;width:100%;min-height:188px;max-height:188px;padding:16px 18px;overflow:hidden;box-sizing:border-box;display:flex;flex-direction:column}
    .pp-kpi-section .pp-card h4{margin-bottom:10px;font-size:13px}
    .pp-kpi-section .pp-negocio-row{margin-bottom:8px}
    .pp-kpi-section .pp-negocio-row:last-of-type{margin-bottom:0}
    .pp-kpi-gauges{display:flex;gap:12px;align-items:center;justify-content:center;min-height:88px}
    .pp-kpi-fact-body{display:flex;align-items:center;justify-content:space-between;gap:8px;flex:1;min-height:0}
    .pp-kpi-fact-stats{flex:1;min-width:0}
    .pp-kpi-fact-stats .pp-negocio-sub{margin:0;font-size:11px;line-height:1.35}
    .pp-activity-card{padding:0 !important;overflow:hidden}
    .pp-activity-card .pp-activity-head{padding:12px 16px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700}
    .pp-activity-card .pp-tbl{font-size:11px}
    .pp-activity-card .pp-tbl th{font-size:9px;padding:6px 10px}
    .pp-activity-card .pp-tbl td{padding:7px 10px}
    .pp-tbl{width:100%;border-collapse:collapse;font-size:12px}
    .pp-tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:8px 10px;text-align:left;border-bottom:1px solid var(--border-light);background:var(--gray-soft)}
    .pp-tbl td{padding:9px 10px;border-bottom:1px solid var(--border-light);color:var(--gray-text)}
    .pp-tbl tr:last-child td{border-bottom:none}
    .pp-tbl tr:hover td{background:var(--purple-subtle)}
    .pp-score-bar{height:5px;background:#e8e8ed;border-radius:4px;overflow:hidden;min-width:50px}
    .pp-score-fill{height:100%;background:var(--green);border-radius:4px}
    .pp-badge{font-size:10px;font-weight:600;padding:2px 7px;border-radius:999px;text-transform:capitalize}
    .pp-badge-validacion{background:#fefce8;color:#d97706}
    .pp-badge-entregado{background:#ecfdf5;color:#059669}
    .pp-badge-procesando,.pp-badge-enviado{background:#eff6ff;color:#2563eb}
    .pp-badge-cancelado{background:#fef2f2;color:#dc2626}
    @media(max-width:768px){
        .pp-grid-2{grid-template-columns:1fr !important}
        .pp-grid-3{grid-template-columns:1fr !important}
        .pp-kpi-section{grid-template-columns:1fr 1fr !important}
        .pp-kpi-section .pp-card{min-height:170px;max-height:none}
        .pp-quick-grid{grid-template-columns:1fr 1fr !important}
    }
    @media(max-width:480px){
        .pp-kpi-section{grid-template-columns:1fr !important}
        .pp-quick-grid{grid-template-columns:1fr !important}
    }
</style>
@endpush
@section('content')
@php
    $ot = $scorePromedio > 0 ? round($scorePromedio * 0.55) : 50;
    $if = $scorePromedio > 0 ? min(100, round($scorePromedio * 1.1)) : 100;
    $mesAnteriorData = $pedidosPorMes->count() >= 2 ? $pedidosPorMes[$pedidosPorMes->count() - 2] : null;
    $penultimoMesData = $pedidosPorMes->count() >= 3 ? $pedidosPorMes[$pedidosPorMes->count() - 3] : null;
    $ventasVarPct = ($penultimoMesData && $penultimoMesData['monto'] > 0 && $mesAnteriorData)
        ? round((($mesAnteriorData['monto'] - $penultimoMesData['monto']) / $penultimoMesData['monto']) * 100)
        : 0;
    $pedidosVarPct = ($penultimoMesData && $penultimoMesData['total'] > 0 && $mesAnteriorData)
        ? round((($mesAnteriorData['total'] - $penultimoMesData['total']) / $penultimoMesData['total']) * 100)
        : 0;
    $facturasPagadas = $facturasPorEstatus->get('pagada')?->total ?? 0;
    $facturasPendientesCount = $facturasPorEstatus->get('pendiente')?->total ?? 0;
    $facturasCanceladas = $facturasPorEstatus->get('cancelada')?->total ?? 0;
@endphp
<div class="pp-wrap">

    {{-- KPIs: 2 filas × 4 columnas, misma altura --}}
    <div class="pp-kpi-section">
        <a href="{{ route('admin.negocio') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Negocio</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Ventas totales</div>
                <div class="pp-negocio-value" style="color:var(--green);font-size:20px;">
                    ${{ number_format($montoPedidos, 0) }}
                    @if($ventasVarPct != 0)<span class="pp-variation {{ $ventasVarPct > 0 ? 'pp-variation-up' : 'pp-variation-down' }}" style="font-size:14px;">{{ $ventasVarPct > 0 ? '↑' : '↓' }} {{ $ventasVarPct > 0 ? '+' : '' }}{{ $ventasVarPct }}%</span>@endif
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Pedidos</div>
                <div class="pp-negocio-value" style="font-size:20px;">
                    {{ $totalPedidos }}
                    @if($pedidosVarPct != 0)<span class="pp-variation {{ $pedidosVarPct > 0 ? 'pp-variation-up' : 'pp-variation-down' }}" style="font-size:14px;">{{ $pedidosVarPct > 0 ? '↑' : '↓' }} {{ $pedidosVarPct > 0 ? '+' : '' }}{{ $pedidosVarPct }}%</span>@endif
                </div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.inventario') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Inventario</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">SKUs activos</div>
                <div class="pp-negocio-value" style="font-size:20px;">
                    {{ $totalProductos }}
                    @if($skusVarPct != 0)<span class="pp-variation {{ $skusVarPct > 0 ? 'pp-variation-up' : 'pp-variation-down' }}" style="font-size:14px;">{{ $skusVarPct > 0 ? '↑' : '↓' }} {{ $skusVarPct > 0 ? '+' : '' }}{{ $skusVarPct }}%</span>@endif
                </div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Agotados</div>
                <div class="pp-negocio-value" style="color:{{ $sinStock > 0 ? 'var(--red)' : 'var(--green)' }};font-size:20px;">
                    {{ $sinStock }}
                    @if($agotadosVarPct != 0)
                        @php $agotadosMejora = $agotadosVarPct < 0; @endphp
                        <span class="pp-variation {{ $agotadosMejora ? 'pp-variation-up' : 'pp-variation-down' }}" style="font-size:14px;">{{ $agotadosMejora ? '↓' : '↑' }} {{ $agotadosVarPct > 0 ? '+' : '' }}{{ $agotadosVarPct }}%</span>
                    @endif
                </div>
            </div>
            <div class="pp-negocio-sub">
                <span style="color:var(--amber);">●</span> {{ $stockBajo }} bajo &nbsp;
                <span style="color:var(--green);">●</span> {{ $stockOk }} OK &nbsp;
                {{ number_format($saludPct, 0) }}% salud
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.otif') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>OTIF</h4>
            <div class="pp-kpi-gauges">
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="gaugeOT" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">{{ $ot }}%</div></div></div><div class="pp-otif-label">OT</div></div>
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="gaugeIF" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">{{ $if }}%</div></div></div><div class="pp-otif-label">IF</div></div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.opinion-positiva') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Opinión positiva</h4>
            <div class="pp-kpi-gauges">
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="gaugeOpOk" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">{{ $opinionPctActualizados }}%</div></div></div><div class="pp-otif-label">Actualizados</div></div>
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="gaugeOpNo" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent" style="color:var(--amber)">{{ $opinionPctNoActualizados }}%</div></div></div><div class="pp-otif-label">No actualizados</div></div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.proveedores') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Proveedores</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Activos</div>
                <div class="pp-negocio-value" style="font-size:20px;">{{ $proveedoresActivos }}</div>
            </div>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Score promedio</div>
                <div class="pp-negocio-value" style="color:var(--green);font-size:20px;">{{ $scorePromedio }}%</div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.documentos') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Docs. pendientes</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Documentos por revisar</div>
                <div class="pp-negocio-value" style="color:var(--amber);font-size:26px;">{{ $docsPendientes }}</div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.fiscal') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Fiscal</h4>
            <div class="pp-negocio-row">
                <div class="pp-negocio-label">Estado fiscal de proveedores</div>
                <div class="pp-negocio-value" style="font-size:20px;">Validación</div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.facturas') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Facturación</h4>
            <div class="pp-kpi-fact-body">
                <div class="pp-kpi-fact-stats">
                    <div class="pp-negocio-sub">{{ $facturasPendientesCount }} pendientes</div>
                    <div class="pp-negocio-sub">${{ number_format($montoFacturas, 0) }} por cobrar</div>
                    <div class="pp-negocio-sub" style="margin-top:4px;">
                        <span style="color:var(--green);">●</span> {{ $facturasPagadas }} &nbsp;
                        <span style="color:var(--amber);">●</span> {{ $facturasPendientesCount }} &nbsp;
                        <span style="color:var(--red);">●</span> {{ $facturasCanceladas }}
                    </div>
                </div>
                <div class="pp-chart-wrap-xs"><canvas id="chartFacturacion"></canvas></div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>
    </div>

    {{-- Actividad reciente --}}
    <h3 class="pp-section-title">Actividad reciente</h3>
    <div class="pp-grid-2" style="align-items:stretch;">
        <div class="pp-card pp-activity-card">
            <div class="pp-activity-head">Órdenes de compra recientes</div>
            <table class="pp-tbl">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Proveedor</th>
                        <th>Total</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($ultimosPedidos as $p)
                    <tr>
                        <td style="font-weight:600;">{{ $p->folio }}</td>
                        <td>
                            <div>{{ $p->proveedor?->nombre ?? $p->nombre_proveedor ?? '—' }}</div>
                            @if($p->codigo_proveedor)<div style="font-size:10px;color:var(--gray-muted)">{{ $p->codigo_proveedor }}</div>@endif
                        </td>
                        <td>${{ number_format($p->total, 0) }}</td>
                        <td><span class="pp-badge pp-badge-{{ $p->estatus }}">{{ ucfirst($p->estatus) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;color:var(--gray-muted);padding:16px;">Sin órdenes recientes</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pp-card pp-activity-card">
            <div class="pp-activity-head">Top proveedores</div>
            <table class="pp-tbl">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Score</th>
                        <th>Entrega</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($topProveedores as $prov)
                    <tr>
                        <td style="font-weight:600;">{{ $prov->nombre ?? $prov->usuario }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <div class="pp-score-bar"><div class="pp-score-fill" style="width:{{ min(100, $prov->score_total) }}%"></div></div>
                                <span style="font-weight:700;font-size:10px;">{{ number_format($prov->score_total, 0) }}%</span>
                            </div>
                        </td>
                        <td style="font-size:10px;color:var(--gray-muted);">{{ number_format($prov->score_entrega, 0) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--gray-muted);padding:16px;">Sin proveedores con score</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Accesos directos --}}
    <h3 class="pp-section-title">Accesos directos</h3>
    <div class="pp-quick-grid">
        <a href="{{ route('admin.pedidos') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
            <div><div class="pp-quick-title">Pedidos</div><div class="pp-quick-sub">Órdenes de compra</div></div>
        </a>
        <a href="{{ route('admin.reporte-proveedores') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg></div>
            <div><div class="pp-quick-title">Reportes</div><div class="pp-quick-sub">Análisis de proveedores</div></div>
        </a>
        <a href="{{ route('admin.otif') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><div class="pp-quick-title">OTIF</div><div class="pp-quick-sub">On Time In Full</div></div>
        </a>
        <a href="{{ route('admin.gestion-compras') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M9 5H2v7l6.29 6.29c.94.94 2.48.94 3.42 0l3.58-3.58c.94-.94.94-2.48 0-3.42L9 5z"/></svg></div>
            <div><div class="pp-quick-title">Gestión Compras</div><div class="pp-quick-sub">Logística</div></div>
        </a>
        <a href="{{ route('admin.clientes') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <div><div class="pp-quick-title">Clientes</div><div class="pp-quick-sub">Gestión de cuentas</div></div>
        </a>
        <a href="{{ route('admin.encuestas') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></div>
            <div><div class="pp-quick-title">Encuestas</div><div class="pp-quick-sub">Satisfacción</div></div>
        </a>
        @if(in_array(session('admin_rol'), ['gerente', 'materia_prima']))
        <a href="{{ route('admin.materia-prima') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M9 3h6v7l4 8H5l4-8V3z"/></svg></div>
            <div><div class="pp-quick-title">Materia Prima</div><div class="pp-quick-sub">Área MP</div></div>
        </a>
        @endif
        @if(in_array(session('admin_rol'), ['gerente', 'material_empaque']))
        <a href="{{ route('admin.material-empaque') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
            <div><div class="pp-quick-title">Material Empaque</div><div class="pp-quick-sub">Área ME</div></div>
        </a>
        @endif
        @if(in_array(session('admin_rol'), ['admin', 'compras_nacional', 'compras_importacion', 'mantenimiento']))
        <a href="{{ route('admin.alta-producto') }}" class="pp-card pp-quick-card">
            <div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
            <div><div class="pp-quick-title">Alta de Producto</div><div class="pp-quick-sub">Nuevo producto</div></div>
        </a>
        @endif
    </div>

</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function drawDonut(canvasId, percent) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const size = canvas.width, center = size/2, radius = size*0.38, lineWidth = size*0.12;
        const startAngle = -Math.PI/2, endAngle = startAngle + (2*Math.PI*percent/100);
        ctx.beginPath(); ctx.arc(center,center,radius,0,2*Math.PI); ctx.strokeStyle='#e8e8ed'; ctx.lineWidth=lineWidth; ctx.stroke();
        if(percent>0){ctx.beginPath();ctx.arc(center,center,radius,startAngle,endAngle);ctx.strokeStyle='#34c759';ctx.lineWidth=lineWidth;ctx.lineCap='round';ctx.stroke();}
        if(percent<100){ctx.beginPath();ctx.arc(center,center,radius,endAngle+0.02,startAngle+2*Math.PI-0.02);ctx.strokeStyle=percent>95?'#ff9500':'#ff3b30';ctx.lineWidth=lineWidth;ctx.lineCap='butt';ctx.stroke();}
    }
    drawDonut('gaugeOT', {{ $ot }});
    drawDonut('gaugeIF', {{ $if }});
    drawDonut('gaugeOpOk', {{ $opinionPctActualizados }});
    drawDonut('gaugeOpNo', {{ $opinionPctNoActualizados }});
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="/js/chart-config.js"></script>
<script>
const SC = SALCOM_COLORS;
salcomChart.doughnut(
    document.getElementById('chartFacturacion'),
    ['Pagadas', 'Pendientes', 'Canceladas'],
    [{{ $facturasPagadas }}, {{ $facturasPendientesCount }}, {{ $facturasCanceladas }}],
    [SC.green, SC.amber, SC.red],
    { legend: false, cutout: '62%' }
);
</script>
@endpush
