@extends('layouts.cliente')
@section('title', 'Tracking de Pedido')
@section('hero')
<div class="hero-band"><h1>Seguimiento de Pedido</h1><p>Consulta el estatus de tu pedido en tiempo real</p></div>
@endsection

@push('styles')
<style>
    .search-bar{display:flex;gap:12px;margin-bottom:28px;max-width:500px}
    .search-input{flex:1;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:14px;font-family:inherit;color:var(--gray-text);outline:none}
    .search-input:focus{border-color:#6B3FA0;box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-search{padding:10px 24px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:all .15s}.btn-search:hover{background:#4A2070}

    .tracking-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:28px;display:none}
    .tracking-card.active{display:block}
    .track-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px}
    .track-folio{font-size:20px;font-weight:700;color:var(--gray-text)}.track-folio span{color:#6B3FA0}
    .track-badge{font-size:12px;font-weight:600;padding:4px 14px;border-radius:999px}
    .track-badge.active-step{background:#F3EEFA;color:#6B3FA0}

    /* Timeline */
    .timeline{position:relative;padding-left:32px}
    .timeline::before{content:'';position:absolute;left:11px;top:8px;bottom:8px;width:2px;background:var(--border)}
    .tl-step{position:relative;padding-bottom:28px}
    .tl-step:last-child{padding-bottom:0}
    .tl-dot{position:absolute;left:-32px;top:2px;width:22px;height:22px;border-radius:50%;border:2px solid var(--border);background:var(--white);display:flex;align-items:center;justify-content:center;z-index:1}
    .tl-dot svg{display:none}
    .tl-step.done .tl-dot{background:#059669;border-color:#059669}.tl-step.done .tl-dot svg{display:block}
    .tl-step.current .tl-dot{background:#6B3FA0;border-color:#6B3FA0;box-shadow:0 0 0 4px rgba(107,63,160,.2)}.tl-step.current .tl-dot svg{display:block}
    .tl-step.pending .tl-dot{background:#f3f4f6;border-color:#d1d5db}
    .tl-title{font-size:14px;font-weight:700;color:var(--gray-text)}.tl-step.pending .tl-title{color:#9ca3af}
    .tl-step.current .tl-title{color:#6B3FA0}
    .tl-meta{font-size:12px;color:var(--gray-muted);margin-top:2px}
    .tl-desc{font-size:13px;color:var(--gray-muted);margin-top:4px;line-height:1.5}

    .external-msg{background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px 20px;margin-top:20px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;display:none}
    .external-msg.visible{display:flex}
    .external-msg svg{flex-shrink:0;margin-top:1px}

    .no-result{text-align:center;color:#9ca3af;padding:40px 0;font-size:14px;display:none}
    .no-result.visible{display:block}

    .badge-api{font-size:11px;color:#d97706;font-weight:600;background:#fffbeb;padding:3px 10px;border-radius:999px;display:inline-block;margin-bottom:16px}
</style>
@endpush

@section('content')
<span class="badge-api">⚠ Datos de prueba — Pendiente de API</span>

<div class="search-bar">
    <input type="text" class="search-input" id="folioInput" placeholder="Ingresa el folio del pedido (ej: PED-2026-001)" value="">
    <button class="btn-search" onclick="buscarPedido()">Buscar</button>
</div>

<div class="no-result" id="noResult">No se encontró un pedido con ese folio. Verifica e intenta de nuevo.</div>

<div class="tracking-card" id="trackingCard">
    <div class="track-header">
        <div class="track-folio" id="trackFolio"></div>
        <span class="track-badge active-step" id="trackStatus"></span>
    </div>
    <div class="timeline" id="timeline"></div>
    <div class="external-msg" id="externalMsg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>El pedido ya salió de nuestras instalaciones. Para información de entrega, contacta a tu ejecutivo de cuenta en Industrias Salcom.</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
const pasos = [
    {key:'recibido',label:'Pedido recibido',desc:'Tu pedido fue registrado en el sistema'},
    {key:'validacion',label:'En validación',desc:'Verificando datos del pedido y disponibilidad'},
    {key:'autorizado',label:'Autorizado',desc:'Pedido aprobado por el área comercial'},
    {key:'produccion',label:'En producción',desc:'Tu pedido está siendo preparado en planta'},
    {key:'listo',label:'Listo para envío',desc:'Pedido empacado y listo para salir'},
    {key:'salida',label:'Salida de planta',desc:'El pedido salió de nuestras instalaciones'},
    {key:'entregado',label:'Entregado',desc:'Pedido entregado al cliente'},
];

const pedidosMock = {
    'PED-2026-001':{folio:'PED-2026-001',currentStep:'entregado',steps:{recibido:{fecha:'01/04/2026 09:15',desc:'Pedido registrado por CLI001'},validacion:{fecha:'01/04/2026 09:30',desc:'Validación de pago de contado'},autorizado:{fecha:'01/04/2026 10:00',desc:'Aprobado por área comercial'},produccion:{fecha:'02/04/2026 08:00',desc:'Ingresó a línea de producción'},listo:{fecha:'03/04/2026 14:30',desc:'Empacado y etiquetado'},salida:{fecha:'03/04/2026 16:00',desc:'Salió en transporte local'},entregado:{fecha:'04/04/2026 10:00',desc:'Recibido por el cliente'}}},
    'PED-2026-002':{folio:'PED-2026-002',currentStep:'salida',steps:{recibido:{fecha:'03/04/2026 11:00',desc:'Pedido registrado'},validacion:{fecha:'03/04/2026 11:15',desc:'Pago verificado'},autorizado:{fecha:'03/04/2026 12:00',desc:'Aprobado'},produccion:{fecha:'04/04/2026 07:00',desc:'En línea de producción'},listo:{fecha:'05/04/2026 15:00',desc:'Empacado'},salida:{fecha:'06/04/2026 08:30',desc:'Salió de planta Guadalajara'}}},
    'PED-2026-003':{folio:'PED-2026-003',currentStep:'produccion',steps:{recibido:{fecha:'05/04/2026 10:00',desc:'Pedido registrado'},validacion:{fecha:'05/04/2026 10:20',desc:'Pago verificado'},autorizado:{fecha:'05/04/2026 11:00',desc:'Aprobado'},produccion:{fecha:'06/04/2026 08:00',desc:'En producción — estimado 2 días'}}},
    'PED-2026-004':{folio:'PED-2026-004',currentStep:'autorizado',steps:{recibido:{fecha:'07/04/2026 09:00',desc:'Pedido registrado'},validacion:{fecha:'07/04/2026 09:30',desc:'Pago verificado'},autorizado:{fecha:'07/04/2026 14:00',desc:'Aprobado — en espera de producción'}}},
    'PED-2026-005':{folio:'PED-2026-005',currentStep:'validacion',steps:{recibido:{fecha:'09/04/2026 08:45',desc:'Pedido registrado'},validacion:{fecha:'09/04/2026 09:00',desc:'En revisión de disponibilidad'}}},
};

function buscarPedido() {
    const folio = document.getElementById('folioInput').value.trim().toUpperCase();
    const card = document.getElementById('trackingCard');
    const noResult = document.getElementById('noResult');
    const extMsg = document.getElementById('externalMsg');

    if (!folio || !pedidosMock[folio]) {
        card.classList.remove('active');
        noResult.classList.add('visible');
        return;
    }

    noResult.classList.remove('visible');
    card.classList.add('active');

    const ped = pedidosMock[folio];
    document.getElementById('trackFolio').innerHTML = 'Pedido <span>' + ped.folio + '</span>';

    const currentIdx = pasos.findIndex(p => p.key === ped.currentStep);
    const currentLabel = pasos[currentIdx]?.label || '';
    document.getElementById('trackStatus').textContent = currentLabel;

    let html = '';
    pasos.forEach((paso, i) => {
        const stepData = ped.steps[paso.key];
        let cls = 'pending';
        if (i < currentIdx) cls = 'done';
        else if (i === currentIdx) cls = 'current';

        const checkSvg = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

        html += '<div class="tl-step ' + cls + '">';
        html += '<div class="tl-dot">' + checkSvg + '</div>';
        html += '<div class="tl-title">' + paso.label + '</div>';
        if (stepData) {
            html += '<div class="tl-meta">' + stepData.fecha + '</div>';
            html += '<div class="tl-desc">' + stepData.desc + '</div>';
        } else if (cls === 'pending') {
            html += '<div class="tl-meta">Pendiente</div>';
        }
        html += '</div>';
    });

    document.getElementById('timeline').innerHTML = html;

    // Show external message if past salida de planta
    const salidaIdx = pasos.findIndex(p => p.key === 'salida');
    extMsg.classList.toggle('visible', currentIdx >= salidaIdx && ped.currentStep !== 'entregado');
}

document.getElementById('folioInput').addEventListener('keyup', e => { if (e.key === 'Enter') buscarPedido(); });
</script>
@endpush
