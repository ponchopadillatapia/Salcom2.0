@extends('layouts.cliente')
@section('title', 'Estado de Cuenta')
@section('hero')
<div class="hero-band"><h1>Estado de Cuenta</h1><p>Resumen financiero y facturas CFDI</p></div>
@endsection

@push('styles')
<style>
    .summary-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
    .sum-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:20px}
    .sum-label{font-size:12px;color:var(--gray-muted);margin-bottom:6px;font-weight:500}
    .sum-val{font-size:22px;font-weight:700;color:var(--gray-text)}
    .sum-sub{font-size:11px;color:#9ca3af;margin-top:4px}

    .info-banner{background:#F3EEFA;border:1px solid #d4c5e8;border-radius:10px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;font-size:13px;color:#4A2070}
    .info-banner svg{flex-shrink:0}
    .info-banner strong{font-weight:700}

    .contado-banner{background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;font-size:13px;color:#92400e}

    .status-row{display:flex;align-items:center;gap:16px;margin-bottom:24px;flex-wrap:wrap}
    .status-item{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray-text)}
    .status-dot{width:10px;height:10px;border-radius:50%}
    .dot-ok{background:#059669}.dot-review{background:#d97706}.dot-blocked{background:#dc2626}

    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .card-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .card-head h3{font-size:14px;font-weight:600;color:var(--gray-text)}
    .tabla{width:100%;border-collapse:collapse}
    .tabla th{font-size:12px;font-weight:600;color:var(--gray-muted);padding:12px 20px;text-align:left;border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:.5px}
    .tabla td{padding:12px 20px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .tabla tr:last-child td{border-bottom:none}
    .tabla tr:hover td{background:#f9fafb}
    .tabla .folio{font-weight:600;color:#6B3FA0}

    .badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px}
    .badge-pagada{background:#ecfdf5;color:#059669}
    .badge-pendiente{background:#fffbeb;color:#d97706}
    .badge-vencida{background:#fef2f2;color:#dc2626}

    .badge-api{font-size:11px;color:#d97706;font-weight:600;background:#fffbeb;padding:3px 10px;border-radius:999px;display:inline-block;margin-bottom:16px}
    .filter-row{display:flex;align-items:center;gap:12px;padding:0 20px 14px}
    .filter-select{border:1.5px solid var(--border);border-radius:6px;padding:6px 12px;font-size:12px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}

    @media(max-width:768px){.summary-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<span class="badge-api">⚠ Datos de prueba — Pendiente de API</span>

@php
    $tipo = session('cliente_tipo', 'minorista');
    $creditoAutorizado = false; // viene de BD, por ahora false (contado)
@endphp

@if(!$creditoAutorizado)
<div class="contado-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span><strong>Cliente nuevo — Pagos de contado.</strong> Tu cuenta aún no tiene crédito autorizado. Los pedidos se procesan contra pago.</span>
</div>
@endif

<div class="info-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4A2070" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span><strong>{{ session('cliente_nombre', 'Cliente') }}</strong> · Código: {{ session('cliente_codigo', '—') }} · Tipo: {{ ucfirst($tipo) }}</span>
</div>

<div class="summary-grid">
    <div class="sum-card">
        <div class="sum-label">Tipo de cliente</div>
        <div class="sum-val">{{ ucfirst($tipo) }}</div>
        <div class="sum-sub">Clasificación comercial</div>
    </div>
    <div class="sum-card">
        <div class="sum-label">Límite de crédito</div>
        <div class="sum-val">{{ $creditoAutorizado ? '$50,000.00' : 'N/A' }}</div>
        <div class="sum-sub">{{ $creditoAutorizado ? 'Crédito autorizado' : 'Sin crédito — Contado' }}</div>
    </div>
    <div class="sum-card">
        <div class="sum-label">Saldo pendiente</div>
        <div class="sum-val">$0.00</div>
        <div class="sum-sub">Al corriente</div>
    </div>
</div>

<div class="status-row">
    <div class="status-item"><div class="status-dot dot-ok"></div><strong>Cuenta al corriente</strong></div>
    <div class="status-item" style="color:var(--gray-muted)">Sin adeudos · Sin facturas vencidas</div>
</div>

<div class="card">
    <div class="card-head">
        <h3>Historial de Facturas CFDI</h3>
    </div>
    <div class="filter-row" style="padding-top:14px">
        <select class="filter-select" id="facturaFilter" onchange="filtrarFacturas()">
            <option value="">Todas</option>
            <option value="pagada">Pagadas</option>
            <option value="pendiente">Pendientes</option>
            <option value="vencida">Vencidas</option>
        </select>
        <span style="font-size:12px;color:#9ca3af" id="factCount"></span>
    </div>
    <table class="tabla">
        <thead><tr><th>Folio CFDI</th><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Estatus</th></tr></thead>
        <tbody id="facturasBody"></tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
const facturas = [
    {folio:'CFDI-A-001234',fecha:'01/04/2026',concepto:'PED-2026-001 — Detergente, Desengrasante',monto:9802.00,estatus:'pagada'},
    {folio:'CFDI-A-001235',fecha:'03/04/2026',concepto:'PED-2026-002 — Aceite Lubricante',monto:3097.20,estatus:'pagada'},
    {folio:'CFDI-A-001236',fecha:'05/04/2026',concepto:'PED-2026-003 — Cinta, Stretch Film',monto:5481.00,estatus:'pendiente'},
    {folio:'CFDI-A-001237',fecha:'07/04/2026',concepto:'PED-2026-004 — Sanitizante',monto:6786.00,estatus:'pendiente'},
    {folio:'CFDI-A-001238',fecha:'09/04/2026',concepto:'PED-2026-005 — Solvente, Refrigerante',monto:5452.00,estatus:'pendiente'},
];

const badgeMap = {pagada:'<span class="badge badge-pagada">Pagada</span>',pendiente:'<span class="badge badge-pendiente">Pendiente</span>',vencida:'<span class="badge badge-vencida">Vencida</span>'};

function renderFacturas(list) {
    const body = document.getElementById('facturasBody');
    if (!list.length) { body.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:32px">No hay facturas con este filtro</td></tr>'; }
    else { body.innerHTML = list.map(f => `<tr><td class="folio">${f.folio}</td><td>${f.fecha}</td><td>${f.concepto}</td><td>$${f.monto.toLocaleString('es-MX',{minimumFractionDigits:2})}</td><td>${badgeMap[f.estatus]}</td></tr>`).join(''); }
    document.getElementById('factCount').textContent = list.length + ' facturas';
}

function filtrarFacturas() {
    const s = document.getElementById('facturaFilter').value;
    renderFacturas(s ? facturas.filter(f => f.estatus === s) : facturas);
}

renderFacturas(facturas);
</script>
@endpush
