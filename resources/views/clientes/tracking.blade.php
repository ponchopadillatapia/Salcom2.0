@extends('layouts.cliente')
@section('title', 'Tracking de pedidos')
@section('hero')
<div class="hero-band">
    <h1>Tracking</h1>
    <p>Cada producto aparece en su fila; el mismo folio agrupa un pedido. Incluye <strong>día de envío</strong> y <strong>día de llegada</strong>. Flujo: <a class="cli-hero-link" href="{{ route('clientes.catalogo') }}">Catálogo</a> → <a class="cli-hero-link" href="{{ route('clientes.pedidos') }}">Pedidos</a> → aquí.</p>
</div>
@endsection

@push('styles')
<style>
    .cli-hero-link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .cli-hero-link:hover { text-decoration: underline; }
    .cli-notice { font-size: 12px; font-weight: 600; color: var(--amber); background: var(--amber-bg); border: 1px solid var(--amber); padding: 10px 14px; border-radius: var(--radius-lg); margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px; }

    .ped-toolbar{display:flex;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap;padding:16px 18px;background:var(--white);border:1px solid var(--border-light);border-radius: var(--radius-lg);box-shadow: var(--shadow-sm);transition: var(--transition)}
    .ped-toolbar:hover{box-shadow: var(--shadow-md)}
    .ped-filter{border:1px solid var(--border-light);border-radius:10px;padding:10px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white);cursor:pointer;outline:none;min-width:200px}
    .ped-filter:focus{border-color: var(--purple-mid); box-shadow: 0 0 0 3px var(--purple-subtle)}
    .ped-count{font-size:12px;font-weight:600;color:var(--gray-muted);margin-left:auto;letter-spacing:-0.02em}

    .ped-legend{display:flex;flex-wrap:wrap;gap:12px 20px;margin-bottom:16px;font-size:11px;color:var(--gray-muted)}
    .ped-legend span{display:inline-flex;align-items:center;gap:6px}
    .ped-legend i{width:8px;height:8px;border-radius:50%;flex-shrink:0}

    .card{background:var(--white);border:1px solid var(--border-light);border-radius: var(--radius-lg);overflow:hidden;box-shadow: var(--shadow-sm);transition: var(--transition)}
    .card:hover{box-shadow: var(--shadow-md)}
    .cli-table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;border-radius:0 0 var(--radius-lg) var(--radius-lg)}
    .tabla{width:100%;min-width:1080px;border-collapse:collapse}
    .tabla .dia-track{font-size:12px;white-space:nowrap}
    .tabla .dia-track.muted{color:var(--gray-muted);font-size:11px}
    .tabla th{font-size:11px;font-weight:700;color:var(--gray-muted);padding:13px 16px;text-align:left;border-bottom:1px solid var(--border-light);text-transform:uppercase;letter-spacing:.5px;background:var(--gray-soft);white-space:nowrap}
    .tabla td{padding:13px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light);vertical-align:top}
    .tabla tbody tr:last-child td{border-bottom:none}
    .tabla tbody tr:hover td{background: var(--purple-subtle)}
    .tabla tr.tr-pedido-sep td{border-top:2px solid var(--border-light)}
    .tabla .folio{font-weight:700;color: var(--purple);white-space:nowrap;font-size:13px}
    .tabla .codigo{font-size:11px;color:var(--gray-muted)}
    .tabla .num{text-align:right;white-space:nowrap;font-variant-numeric: tabular-nums}

    .badge{font-size:11px;font-weight:600;padding:4px 10px;border-radius:999px;white-space:nowrap;display:inline-block}
    .badge-validacion{background: var(--purple-subtle);color: var(--purple)}
    .badge-autorizado{background:#dbeafe;color:#2563eb}
    .badge-produccion{background: var(--amber-bg);color: var(--amber)}
    .badge-enviado{background: var(--green-bg);color: var(--green)}
    .badge-entregado{background:#dcfce7;color:#166534}
    .badge-contado{background:var(--gray-soft);color:var(--gray-muted);font-size:10px}
    .badge-credito{background:#dbeafe;color:#2563eb;font-size:10px}

    .empty-row td{text-align:center;color:var(--gray-muted);padding:48px 24px;font-size:14px}
</style>
@endpush

@section('content')
<div class="cli-notice" role="note">Datos de demostración · Integración API pendiente</div>

<div class="ped-toolbar">
    <select class="ped-filter" id="statusFilter" onchange="filtrarPedidos()" aria-label="Filtrar por estatus">
        <option value="">Todos los estatus</option>
        <option value="validacion">En validación</option>
        <option value="autorizado">Autorizado</option>
        <option value="produccion">En producción</option>
        <option value="enviado">Enviado</option>
        <option value="entregado">Entregado</option>
    </select>
    <span class="ped-count" id="pedCount"></span>
</div>

<div class="ped-legend" aria-hidden="true">
    <span><i style="background:var(--purple)"></i> Validación</span>
    <span><i style="background:#2563eb"></i> Autorizado</span>
    <span><i style="background:var(--amber)"></i> Producción</span>
    <span><i style="background:var(--green)"></i> Enviado / Entregado</span>
</div>

<div class="card">
    <div class="cli-table-scroll">
    <table class="tabla">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha pedido</th>
                <th>Día de envío</th>
                <th>Día de llegada</th>
                <th>Código</th>
                <th>Producto</th>
                <th class="num">Cant.</th>
                <th class="num">P. unit.</th>
                <th class="num">Subtotal</th>
                <th class="num">Total pedido</th>
                <th>Pago</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody id="pedidosBody"></tbody>
    </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const HISTORIAL_KEY = window.SALCOM_PEDIDOS_HISTORIAL_KEY || 'salcom_cliente_pedidos_v1';
const HISTORIAL_SEED = @json(config('cliente_portal.historial_pedidos.seed'));

function loadHistorial() {
    try {
        const raw = localStorage.getItem(HISTORIAL_KEY);
        if (raw) {
            const data = JSON.parse(raw);
            if (Array.isArray(data) && data.length) return data;
        }
    } catch (e) {}
    return HISTORIAL_SEED.map(p => ({...p, lineas: p.lineas.map(l => ({...l}))}));
}

function lineasFromPedido(p) {
    if (Array.isArray(p.lineas) && p.lineas.length) return p.lineas;
    const t = Number(p.total) || 0;
    return [{ codigo: '', nombre: p.productos || '—', cantidad: 1, precioUnit: t }];
}

function totalFromLineas(lineas) {
    return lineas.reduce((s, l) => s + (Number(l.precioUnit) || 0) * (Number(l.cantidad) || 0), 0);
}

/** Envío y llegada; compatible con datos sin campos o con esquema anterior (diaEntrega). */
function diasTrackingPedido(p) {
    let envio = p.diaEnviado != null ? String(p.diaEnviado).trim() : '';
    let llegada = p.diaLlegada != null ? String(p.diaLlegada).trim() : '';
    const legEnt = p.diaEntrega != null ? String(p.diaEntrega).trim() : '';
    if (!envio && legEnt) {
        const col1 = p.diaLlegada != null ? String(p.diaLlegada).trim() : '';
        envio = col1;
        llegada = legEnt;
    } else if (!envio && !legEnt && llegada && p.key !== 'entregado') {
        llegada = '';
    }
    if (!envio) {
        if (p.key === 'enviado' || p.key === 'entregado') envio = '—';
        else envio = '—';
    }
    if (!llegada) {
        if (p.key === 'entregado') llegada = p.fecha || '—';
        else llegada = 'Pendiente';
    }
    return { envio, llegada };
}

function escHtml(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

let pedidos = loadHistorial();

const badgeMap = {
    validacion:'<span class="badge badge-validacion">En validación</span>',
    autorizado:'<span class="badge badge-autorizado">Autorizado</span>',
    produccion:'<span class="badge badge-produccion">En producción</span>',
    enviado:'<span class="badge badge-enviado">Enviado</span>',
    entregado:'<span class="badge badge-entregado">Entregado</span>',
};
const pagoMap = {contado:'<span class="badge badge-contado">Contado</span>',credito:'<span class="badge badge-credito">Crédito</span>'};

function renderPedidos(filteredPedidos) {
    const body = document.getElementById('pedidosBody');
    const rows = [];
    filteredPedidos.forEach(p => {
        const lineas = lineasFromPedido(p);
        const totalPed = p.total != null && p.total !== '' ? Number(p.total) : totalFromLineas(lineas);
        const dias = diasTrackingPedido(p);
        lineas.forEach(l => {
            const sub = (Number(l.precioUnit) || 0) * (Number(l.cantidad) || 0);
            rows.push({
                folio: p.folio,
                fecha: p.fecha,
                diaEnviado: dias.envio,
                diaLlegada: dias.llegada,
                codigo: l.codigo || '',
                nombre: l.nombre || '—',
                cantidad: l.cantidad,
                precioUnit: l.precioUnit,
                subtotal: sub,
                totalPedido: totalPed,
                pago: p.pago,
                key: p.key,
                estatus: p.estatus,
            });
        });
    });

    if (!rows.length) {
        body.innerHTML = '<tr class="empty-row"><td colspan="12">No hay líneas con este filtro</td></tr>';
        document.getElementById('pedCount').textContent = '0 pedidos';
        return;
    }

    let prevFolio = null;
    body.innerHTML = rows.map((r, idx) => {
        const sep = r.folio !== prevFolio && idx > 0 ? ' tr-pedido-sep' : '';
        prevFolio = r.folio;
        const pu = Number(r.precioUnit) || 0;
        const fmt = n => '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2 });
        const clsEnv = (r.diaEnviado === '—' || r.diaEnviado === 'Pendiente') ? ' dia-track muted' : ' dia-track';
        const clsLleg = (r.diaLlegada === '—' || r.diaLlegada === 'Pendiente') ? ' dia-track muted' : ' dia-track';
        return `<tr class="${sep.trim()}"><td class="folio">${escHtml(r.folio)}</td><td>${escHtml(r.fecha)}</td><td class="${clsEnv.trim()}">${escHtml(r.diaEnviado)}</td><td class="${clsLleg.trim()}">${escHtml(r.diaLlegada)}</td><td class="codigo">${r.codigo ? escHtml(r.codigo) : '—'}</td><td>${escHtml(r.nombre)}</td><td class="num">${escHtml(r.cantidad)}</td><td class="num">${fmt(pu)}</td><td class="num">${fmt(r.subtotal)}</td><td class="num">${fmt(r.totalPedido)}</td><td>${pagoMap[r.pago]||r.pago}</td><td>${badgeMap[r.key]||r.estatus}</td></tr>`;
    }).join('');

    const nPed = new Set(filteredPedidos.map(p => p.folio)).size;
    document.getElementById('pedCount').textContent = rows.length + ' producto' + (rows.length === 1 ? '' : 's') + ' · ' + nPed + ' pedido' + (nPed === 1 ? '' : 's');
}

function filtrarPedidos() {
    const s = document.getElementById('statusFilter').value;
    const list = s ? pedidos.filter(p => p.key === s) : pedidos.slice();
    renderPedidos(list);
}

filtrarPedidos();
</script>
@endpush
