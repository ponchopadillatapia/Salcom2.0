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
    .pp-card h4{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:16px}
    .pp-negocio-row{margin-bottom:12px}
    .pp-negocio-label{font-size:12px;color:var(--gray-muted);font-weight:500;margin-bottom:2px}
    .pp-negocio-value{font-size:24px;font-weight:700;color:var(--gray-text);display:flex;align-items:baseline;gap:10px}
    .pp-detail-link{font-size:13px;color:var(--blue);font-weight:600;text-decoration:none;display:inline-block;margin-top:8px}
    .pp-detail-link:hover{text-decoration:underline}
    .pp-list-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-light);font-size:13px}
    .pp-list-item:last-child{border-bottom:none}
    .pp-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .pp-dot-green{background:var(--green)}.pp-dot-amber{background:var(--amber)}.pp-dot-red{background:var(--red)}
    .pp-list-text{flex:1;color:var(--gray-text);font-weight:500}
    .pp-list-status{font-size:11px;color:var(--gray-muted);font-weight:500}
    .pp-quick-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
    .pp-quick-card{text-decoration:none;display:flex;align-items:center;gap:10px;padding:12px !important}
    .pp-quick-icon{width:42px;height:42px;border-radius:12px;background:var(--purple-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:var(--transition)}
    .pp-quick-card:hover .pp-quick-icon{background:var(--purple);box-shadow:0 2px 8px rgba(107,63,160,0.25)}
    .pp-quick-card:hover .pp-quick-icon svg{stroke:white}
    .pp-quick-title{font-weight:600;color:var(--gray-text);font-size:13px}
    .pp-quick-sub{font-size:11px;color:var(--gray-muted);margin-top:2px}
    .pp-otif-canvas-wrap{position:relative;width:80px;height:80px}
    .pp-otif-canvas-wrap canvas{position:absolute;top:0;left:0}
    .pp-otif-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
    .pp-otif-percent{font-size:14px;font-weight:700;color:var(--green);line-height:1}
    .pp-otif-label{font-size:10px;color:var(--gray-muted);font-weight:600;margin-top:4px}
    @media(max-width:768px){.pp-grid-2{grid-template-columns:1fr !important}.pp-quick-grid{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')
<div class="pp-wrap">

    {{-- ROW 1: Negocio + Proveedores + OTIF + Inventario --}}
    <div class="pp-grid-2" style="grid-template-columns:repeat(4,minmax(0,1fr));align-items:stretch;">
        <a href="{{ route('admin.negocio') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Negocio</h4>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Ventas totales</div><div class="pp-negocio-value" style="color:var(--green);font-size:20px;">${{ number_format($montoPedidos, 0) }}</div></div>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Pedidos</div><div class="pp-negocio-value" style="font-size:20px;">{{ $totalPedidos }}</div></div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.proveedores') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Proveedores</h4>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Activos</div><div class="pp-negocio-value" style="font-size:20px;">{{ $proveedoresActivos }}</div></div>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Score promedio</div><div class="pp-negocio-value" style="color:var(--green);font-size:20px;">{{ $scorePromedio }}%</div></div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.otif') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>OTIF</h4>
            @php $ot = $scorePromedio > 0 ? round($scorePromedio * 0.55) : 50; $if = $scorePromedio > 0 ? round($scorePromedio * 1.1) : 100; @endphp
            <div style="display:flex;gap:14px;align-items:center;justify-content:center;flex:1;">
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="gaugeOT" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">{{ $ot }}%</div></div></div><div class="pp-otif-label">OT</div></div>
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="gaugeIF" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">{{ $if }}%</div></div></div><div class="pp-otif-label">IF</div></div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('admin.productos') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Inventario</h4>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Productos</div><div class="pp-negocio-value" style="font-size:20px;">{{ $totalProductos }}</div></div>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Sin stock</div><div class="pp-negocio-value" style="color:var(--red);font-size:20px;">{{ $sinStock }}</div></div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>
    </div>

    {{-- ROW 2: Pedidos + Docs pendientes + Fiscal --}}
    <div class="pp-grid-2" style="grid-template-columns:1fr 1fr 1fr;align-items:stretch;">
        <div class="pp-card" style="padding:16px;">
            <h4 style="font-size:13px;margin-bottom:10px;">Pedidos recientes</h4>
            @forelse($ultimosPedidos->take(4) as $p)
            <div class="pp-list-item" style="padding:6px 0;">
                <div class="pp-dot {{ $p->estatus === 'entregado' ? 'pp-dot-green' : ($p->estatus === 'cancelado' ? 'pp-dot-red' : 'pp-dot-amber') }}"></div>
                <div class="pp-list-text" style="font-size:11px;">{{ $p->folio }}</div>
                <div class="pp-list-status" style="font-size:9px;">${{ number_format($p->total, 0) }}</div>
            </div>
            @empty
            <div style="padding:12px 0;text-align:center;font-size:11px;color:var(--gray-muted);">Sin pedidos</div>
            @endforelse
        </div>

        <div class="pp-card" style="padding:16px;">
            <h4 style="font-size:13px;margin-bottom:10px;">Documentos pendientes</h4>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Por revisar</div><div class="pp-negocio-value" style="color:var(--amber);font-size:20px;">{{ $docsPendientes }}</div></div>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-amber"></div><div class="pp-list-text" style="font-size:11px;">Documentos sin validar</div></div>
        </div>

        <a href="{{ route('admin.fiscal') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="padding:16px;height:100%;display:flex;flex-direction:column;">
            <h4 style="font-size:13px;margin-bottom:10px;">Fiscal</h4>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Estado fiscal</div><div class="pp-negocio-value" style="font-size:16px;">Validación</div></div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>
    </div>

    {{-- ROW 3: Quick access --}}
    <div class="pp-quick-grid">
        <a href="{{ route('admin.proveedores') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="pp-quick-title">Proveedores</div><div class="pp-quick-sub">Score y ranking</div></div></a>
        <a href="{{ route('admin.pedidos') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="pp-quick-title">Pedidos</div><div class="pp-quick-sub">Órdenes</div></div></a>
        <a href="{{ route('admin.productos') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg></div><div><div class="pp-quick-title">Productos</div><div class="pp-quick-sub">Catálogo</div></div></a>
        <a href="{{ route('admin.facturas') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="pp-quick-title">Facturas</div><div class="pp-quick-sub">Pagos</div></div></a>
        <a href="{{ route('admin.reporte-proveedores') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg></div><div><div class="pp-quick-title">Reportes</div><div class="pp-quick-sub">Análisis</div></div></a>
        <a href="{{ route('admin.alta-producto') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div><div><div class="pp-quick-title">Alta Producto</div><div class="pp-quick-sub">Nuevo</div></div></a>
        <a href="{{ route('admin.documentos') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg></div><div><div class="pp-quick-title">Documentos</div><div class="pp-quick-sub">Fiscal</div></div></a>
        <a href="{{ route('admin.gestion-compras') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M9 5H2v7l6.29 6.29c.94.94 2.48.94 3.42 0l3.58-3.58c.94-.94.94-2.48 0-3.42L9 5z"/><path d="M6 9.01V9"/></svg></div><div><div class="pp-quick-title">Gestión Compras</div><div class="pp-quick-sub">Logística</div></div></a>
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
});
</script>
@endpush
