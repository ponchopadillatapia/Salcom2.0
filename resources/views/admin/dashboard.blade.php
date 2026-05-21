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
    .pp-section-title{font-size:15px;font-weight:700;color:var(--gray-text);margin:20px 0 12px}
    .pp-chart-card{padding:20px;height:100%}
    .pp-chart-wrap{position:relative;width:100%;height:220px}
    .pp-chart-wrap-sm{position:relative;width:100%;height:200px;max-width:260px;margin:0 auto}
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
    .pp-tbl{width:100%;border-collapse:collapse;font-size:12px}
    .pp-tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:8px 10px;text-align:left;border-bottom:1px solid var(--border-light);background:var(--gray-soft)}
    .pp-tbl td{padding:9px 10px;border-bottom:1px solid var(--border-light);color:var(--gray-text)}
    .pp-tbl tr:last-child td{border-bottom:none}
    .pp-tbl tr:hover td{background:var(--purple-subtle)}
    .pp-score-bar{height:6px;background:#e8e8ed;border-radius:4px;overflow:hidden;min-width:60px}
    .pp-score-fill{height:100%;background:var(--green);border-radius:4px}
    .pp-badge{font-size:10px;font-weight:600;padding:3px 8px;border-radius:999px;text-transform:capitalize}
    .pp-badge-validacion{background:#fefce8;color:#d97706}
    .pp-badge-entregado{background:#ecfdf5;color:#059669}
    .pp-badge-procesando,.pp-badge-enviado{background:#eff6ff;color:#2563eb}
    .pp-badge-cancelado{background:#fef2f2;color:#dc2626}
    @media(max-width:768px){
        .pp-grid-2{grid-template-columns:1fr !important}
        .pp-grid-3{grid-template-columns:1fr !important}
        .pp-kpi-section{grid-template-columns:1fr 1fr !important}
        .pp-kpi-section .pp-card{min-height:170px;max-height:none}
    }
    @media(max-width:480px){
        .pp-kpi-section{grid-template-columns:1fr !important}
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

        <a href="{{ route('admin.productos') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Inventario</h4>
            <div class="pp-negocio-value" style="font-size:26px;">{{ $totalProductos }}</div>
            <div class="pp-negocio-sub">{{ $sinStock }} agotados · {{ $conStock }} disponibles</div>
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

    {{-- Análisis: compras por mes --}}
    <h3 class="pp-section-title">Análisis</h3>
    <div class="pp-card pp-chart-card">
        <h4 style="margin-bottom:12px;">Compras a proveedores por mes</h4>
        <div class="pp-chart-wrap"><canvas id="chartComprasMes"></canvas></div>
    </div>

    {{-- Actividad reciente --}}
    <h3 class="pp-section-title">Actividad reciente</h3>
    <div class="pp-grid-2" style="align-items:stretch;">
        <div class="pp-card" style="padding:0;overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700;">Órdenes de compra recientes</div>
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
                        <td>{{ $p->nombre_cliente }}</td>
                        <td>${{ number_format($p->total, 0) }}</td>
                        <td><span class="pp-badge pp-badge-{{ $p->estatus }}">{{ ucfirst($p->estatus) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;color:var(--gray-muted);padding:20px;">Sin órdenes recientes</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pp-card" style="padding:0;overflow:hidden;">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border-light);font-size:13px;font-weight:700;">Top proveedores</div>
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
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="pp-score-bar"><div class="pp-score-fill" style="width:{{ min(100, $prov->score_total) }}%"></div></div>
                                <span style="font-weight:700;font-size:11px;">{{ number_format($prov->score_total, 0) }}%</span>
                            </div>
                        </td>
                        <td style="font-size:11px;color:var(--gray-muted);">{{ number_format($prov->score_entrega, 0) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;color:var(--gray-muted);padding:20px;">Sin proveedores con score</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
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
const mesesCompras = {!! json_encode($pedidosPorMes->pluck('mes')) !!};
const montosCompras = {!! json_encode($pedidosPorMes->pluck('monto')) !!};

salcomChart.bar(
    document.getElementById('chartComprasMes'),
    mesesCompras,
    montosCompras,
    { color: SC.purple, yFormat: v => '$' + Number(v).toLocaleString('es-MX', {maximumFractionDigits:0}) }
);

salcomChart.doughnut(
    document.getElementById('chartFacturacion'),
    ['Pagadas', 'Pendientes', 'Canceladas'],
    [{{ $facturasPagadas }}, {{ $facturasPendientesCount }}, {{ $facturasCanceladas }}],
    [SC.green, SC.amber, SC.red],
    { legend: false, cutout: '62%' }
);
</script>
@endpush
