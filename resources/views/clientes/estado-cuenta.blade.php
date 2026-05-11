@extends('layouts.cliente')
@section('title', 'Estado de Cuenta')
@section('hero')
<div class="hero-band">
    <h1>Estado de cuenta</h1>
    <p>Resumen financiero, saldos y <strong>historial de facturas</strong> CFDI. Pagos relacionados con tus <a class="cli-hero-link" href="{{ route('clientes.tracking') }}">pedidos</a>.</p>
</div>
@endsection

@push('styles')
<style>
    .cli-hero-link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .cli-hero-link:hover { text-decoration: underline; }
    .cli-notice { font-size: 12px; font-weight: 600; color: var(--amber); background: var(--amber-bg); border: 1px solid var(--amber); padding: 10px 14px; border-radius: var(--radius-lg); margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px; }

    .summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px;margin-bottom:22px}
    .sum-card{background:var(--white);border:1px solid var(--border-light);border-radius: var(--radius-lg);padding:20px 22px;box-shadow: var(--shadow-sm);transition: var(--transition)}
    .sum-card:hover{box-shadow: var(--shadow-md);border-color: rgba(107, 63, 160, 0.15)}
    .sum-label{font-size:11px;font-weight:700;color:var(--gray-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px}
    .sum-val{font-size:22px;font-weight:700;color:var(--gray-text);letter-spacing:-0.03em}
    .sum-sub{font-size:12px;color:var(--gray-muted);margin-top:8px;line-height:1.45}

    .info-banner{background: var(--purple-subtle);border:1px solid #d4c5e8;border-radius: var(--radius-lg);padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;font-size:13px;color:#4A2070}
    .info-banner svg{flex-shrink:0}
    .info-banner strong{font-weight:700}

    .contado-banner{background: var(--amber-bg);border:1px solid var(--amber);border-radius: var(--radius-lg);padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;font-size:13px;color:#92400e}

    .status-row{display:flex;align-items:flex-start;gap:14px;margin-bottom:22px;flex-wrap:wrap;padding:16px 18px;background:var(--white);border:1px solid var(--border-light);border-radius: var(--radius-lg);box-shadow: var(--shadow-sm)}
    .status-item{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray-text)}
    .status-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0}
    .dot-ok{background:var(--green)}.dot-review{background:var(--amber)}.dot-blocked{background:var(--red)}

    .card{background:var(--white);border:1px solid var(--border-light);border-radius: var(--radius-lg);overflow:hidden;box-shadow: var(--shadow-sm);transition: var(--transition)}
    .card:hover{box-shadow: var(--shadow-md)}
    .card-head{padding:16px 20px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:var(--gray-soft)}
    .card-head h3{font-size:15px;font-weight:700;color:var(--gray-text);margin:0;letter-spacing:-0.02em}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:12px;font-weight:600;color:var(--green);background: var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;font-family:inherit;transition: var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}
    .cli-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}
    .tabla{width:100%;min-width:640px;border-collapse:collapse}
    .tabla th{font-size:11px;font-weight:700;color:var(--gray-muted);padding:12px 18px;text-align:left;border-bottom:1px solid var(--border-light);text-transform:uppercase;letter-spacing:.5px}
    .tabla td{padding:14px 18px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light);vertical-align:top}
    .tabla tr:last-child td{border-bottom:none}
    .tabla tbody tr:hover td{background: var(--purple-subtle)}
    .tabla .folio{font-weight:600;color: var(--purple)}
    .tabla .num{text-align:right;font-variant-numeric:tabular-nums}

    .badge{font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;display:inline-block}
    .badge-pagada{background: var(--green-bg);color: var(--green)}
    .badge-pendiente{background: var(--amber-bg);color: var(--amber)}
    .badge-vencida{background: var(--red-bg);color: var(--red)}

    .filter-row{display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid var(--border-light);flex-wrap:wrap}
    .filter-select{border:1px solid var(--border-light);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white);min-width:160px}
    .filter-select:focus{border-color: var(--purple-mid)}

    @media(max-width:768px){.summary-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="cli-notice" role="note">Datos de demostración · Integración API pendiente</div>

@php
    $tipo = session('cliente_tipo', 'minorista');
    $creditoAutorizado = false;
@endphp

@if(!$creditoAutorizado)
<div class="contado-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span><strong>Cliente nuevo — Pagos de contado.</strong> Tu cuenta aún no tiene crédito autorizado. Los pedidos se procesan contra pago.</span>
</div>
@endif

<div class="info-banner">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4A2070" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    <span><strong>{{ session('cliente_nombre', 'Cliente') }}</strong> · Código {{ session('cliente_codigo', '—') }} · {{ ucfirst($tipo) }}</span>
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
        <div class="sum-val">$17,719.00</div>
        <div class="sum-sub">3 facturas pendientes · Sin vencidas</div>
    </div>
</div>

<div class="status-row">
    <div class="status-item"><div class="status-dot dot-review"></div><strong>Por liquidar</strong></div>
    <div class="status-item" style="color:var(--gray-muted);max-width:720px;line-height:1.5">CFDI-A-001236, 001237 y 001238 · Sin facturas vencidas</div>
</div>

<div class="card">
    <div class="card-head">
        <h3>Historial de facturas CFDI</h3>
        <button type="button" class="btn-export" onclick="exportFacturasCsv()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exportar CSV
        </button>
    </div>
    <div class="filter-row">
        <select class="filter-select" id="facturaFilter" onchange="filtrarFacturas()" aria-label="Filtrar facturas">
            <option value="">Todas</option>
            <option value="pagada">Pagadas</option>
            <option value="pendiente">Pendientes</option>
            <option value="vencida">Vencidas</option>
        </select>
        <span style="font-size:12px;font-weight:600;color:var(--gray-muted)" id="factCount"></span>
    </div>
    <div class="cli-table-scroll">
    <table class="tabla" id="tablaFacturas">
        <thead><tr><th>Folio CFDI</th><th>Fecha</th><th>Concepto</th><th class="num">Monto</th><th>Estatus</th></tr></thead>
        <tbody id="facturasBody"></tbody>
    </table>
    </div>
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
    if (!list.length) {
        body.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--gray-muted);padding:40px 20px">No hay facturas con este filtro</td></tr>';
    } else {
        body.innerHTML = list.map(f => `<tr><td class="folio">${f.folio}</td><td>${f.fecha}</td><td>${f.concepto}</td><td class="num">$${f.monto.toLocaleString('es-MX',{minimumFractionDigits:2})}</td><td>${badgeMap[f.estatus]}</td></tr>`).join('');
    }
    document.getElementById('factCount').textContent = list.length + ' factura' + (list.length === 1 ? '' : 's');
}

function filtrarFacturas() {
    const s = document.getElementById('facturaFilter').value;
    renderFacturas(s ? facturas.filter(f => f.estatus === s) : facturas);
}

function exportFacturasCsv() {
    const table = document.getElementById('tablaFacturas');
    if (!table) return;
    const rows = table.querySelectorAll('tr');
    const csv = [];
    rows.forEach(row => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];
        cols.forEach(col => {
            const t = col.innerText.replace(/"/g, '""').trim();
            const clean = t.replace(/\s+/g, ' ');
            rowData.push('"' + clean + '"');
        });
        csv.push(rowData.join(','));
    });
    const blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'Facturas_CFDI_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
}

renderFacturas(facturas);
</script>
@endpush
