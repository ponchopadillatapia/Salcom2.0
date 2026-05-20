@extends('layouts.cliente')
@section('title', 'Inicio')
@section('hero')
<div class="hero-band">
    <h1>Hola, {{ session('cliente_nombre', 'Cliente') }}</h1>
    <p>Bienvenido al Portal de Clientes de Industrias Salcom</p>
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
    .pp-variation{font-size:14px;font-weight:700}
    .pp-variation-up{color:var(--green)}.pp-variation-down{color:var(--red)}
    .pp-detail-link{font-size:13px;color:var(--blue);font-weight:600;text-decoration:none;display:inline-block;margin-top:8px}
    .pp-detail-link:hover{text-decoration:underline}
    .pp-list-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--border-light);font-size:13px}
    .pp-list-item:last-child{border-bottom:none}
    .pp-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .pp-dot-green{background:var(--green)}.pp-dot-amber{background:var(--amber)}.pp-dot-red{background:var(--red)}
    .pp-list-text{flex:1;color:var(--gray-text);font-weight:500}
    .pp-list-status{font-size:11px;color:var(--gray-muted);font-weight:500}
    .pp-quick-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}
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

    {{-- ROW 1: Compras + Catálogo + OTIF + Fiscal --}}
    <div class="pp-grid-2" style="grid-template-columns:repeat(4,minmax(0,1fr));align-items:stretch;">
        <a href="{{ route('clientes.estado-cuenta') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Compras</h4>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Total (YTD)</div><div class="pp-negocio-value" style="color:var(--green);font-size:20px;">$1,842,300 <span class="pp-variation pp-variation-up">↑ +8%</span></div></div>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Volumen</div><div class="pp-negocio-value" style="font-size:20px;">142,180 kg</div></div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('clientes.catalogo') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Catálogo</h4>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Referencias activas</div><div class="pp-negocio-value" style="font-size:20px;">312</div></div>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Novedades (30 días)</div><div class="pp-negocio-value" style="color:var(--amber);font-size:20px;">6</div></div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('clientes.otif') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>OTIF</h4>
            <div style="display:flex;gap:14px;align-items:center;justify-content:center;flex:1;">
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="donutOT" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">98%</div></div></div><div class="pp-otif-label">OT</div></div>
                <div style="text-align:center;"><div class="pp-otif-canvas-wrap"><canvas id="donutIF" width="80" height="80"></canvas><div class="pp-otif-center"><div class="pp-otif-percent">95%</div></div></div><div class="pp-otif-label">IF</div></div>
            </div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>

        <a href="{{ route('clientes.fiscal') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="height:100%;display:flex;flex-direction:column;">
            <h4>Fiscal</h4>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Documentos</div><div class="pp-negocio-value" style="color:var(--green);font-size:20px;">6/6</div></div>
            <div class="pp-negocio-row"><div class="pp-negocio-label">Estatus</div><div class="pp-negocio-value" style="font-size:14px;color:var(--green);">Al día</div></div>
            <span class="pp-detail-link" style="margin-top:auto;">Ver detalle →</span>
        </div></a>
    </div>

    {{-- ROW 2: Actividad + Pendientes + Onboarding --}}
    <div class="pp-grid-2" style="grid-template-columns:1fr 1fr 1fr;align-items:stretch;">
        <div class="pp-card" style="padding:16px;">
            <h4 style="font-size:13px;margin-bottom:10px;">Actividad reciente</h4>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-green"></div><div class="pp-list-text" style="font-size:11px;">Pedido en producción</div><div class="pp-list-status" style="font-size:9px;">En curso</div></div>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-amber"></div><div class="pp-list-text" style="font-size:11px;">Factura en revisión</div><div class="pp-list-status" style="font-size:9px;">Pendiente</div></div>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-green"></div><div class="pp-list-text" style="font-size:11px;">Envío en ruta</div><div class="pp-list-status" style="font-size:9px;">En tránsito</div></div>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-green"></div><div class="pp-list-text" style="font-size:11px;">Pedido autorizado</div><div class="pp-list-status" style="font-size:9px;">Listo</div></div>
        </div>

        <div class="pp-card" style="padding:16px;">
            <h4 style="font-size:13px;margin-bottom:10px;">Pendientes</h4>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-red"></div><div class="pp-list-text" style="font-size:11px;">Factura por pagar</div><div class="pp-list-status" style="font-size:9px;">Vencida</div></div>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-amber"></div><div class="pp-list-text" style="font-size:11px;">Completar onboarding</div><div class="pp-list-status" style="font-size:9px;">33%</div></div>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-amber"></div><div class="pp-list-text" style="font-size:11px;">Documentación fiscal</div><div class="pp-list-status" style="font-size:9px;">Revisar</div></div>
            <div class="pp-list-item" style="padding:6px 0;"><div class="pp-dot pp-dot-green"></div><div class="pp-list-text" style="font-size:11px;">Confirmar recepción</div><div class="pp-list-status" style="font-size:9px;">Acción</div></div>
        </div>

        <a href="{{ route('clientes.onboarding') }}" style="text-decoration:none;color:inherit;">
        <div class="pp-card" style="cursor:pointer;padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;"><h4 style="font-size:13px;margin:0;">Onboarding</h4><span style="font-size:16px;font-weight:700;color:var(--red);">33%</span></div>
            <div style="height:6px;background:var(--border-light);border-radius:3px;margin-bottom:10px;overflow:hidden;"><div style="height:100%;width:33%;background:var(--red);border-radius:3px;"></div></div>
            <div class="pp-list-item" style="padding:4px 0;"><div class="pp-dot pp-dot-green"></div><div class="pp-list-text" style="font-size:11px;">Alta en el portal</div></div>
            <div class="pp-list-item" style="padding:4px 0;"><div class="pp-dot pp-dot-green"></div><div class="pp-list-text" style="font-size:11px;">Documentación fiscal</div></div>
            <div class="pp-list-item" style="padding:4px 0;"><div class="pp-dot pp-dot-amber"></div><div class="pp-list-text" style="font-size:11px;">Contactos</div></div>
            <div class="pp-list-item" style="padding:4px 0;"><div class="pp-dot pp-dot-amber"></div><div class="pp-list-text" style="font-size:11px;">Validación Salcom</div></div>
            <div class="pp-list-item" style="padding:4px 0;"><div class="pp-dot pp-dot-amber"></div><div class="pp-list-text" style="font-size:11px;">Condiciones comerciales</div></div>
        </div></a>
    </div>

    {{-- ROW 3: Quick access --}}
    <div class="pp-quick-grid">
        <a href="{{ route('clientes.ia') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M12 2a4 4 0 0 1 4 4v1a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V6a4 4 0 0 1 4-4z"/><path d="M16 11v1a4 4 0 0 1-8 0v-1"/></svg></div><div><div class="pp-quick-title">Dashboard IA</div><div class="pp-quick-sub">Análisis</div></div></a>
        <a href="{{ route('clientes.pedidos') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="pp-quick-title">Mis pedidos</div><div class="pp-quick-sub">Seguimiento</div></div></a>
        <a href="{{ route('clientes.estado-cuenta') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="pp-quick-title">Estado de cuenta</div><div class="pp-quick-sub">Facturas</div></div></a>
        <a href="{{ route('clientes.catalogo') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div><div><div class="pp-quick-title">Catálogo</div><div class="pp-quick-sub">Productos</div></div></a>
        <a href="{{ route('clientes.forecast') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div><div><div class="pp-quick-title">Forecast</div><div class="pp-quick-sub">Tendencias</div></div></a>
        <a href="{{ route('clientes.tracking') }}" class="pp-card pp-quick-card"><div class="pp-quick-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="pp-quick-title">Tracking</div><div class="pp-quick-sub">Envíos</div></div></a>
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
    drawDonut('donutOT', 98);
    drawDonut('donutIF', 95);
});
</script>
@endpush
