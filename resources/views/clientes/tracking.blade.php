@extends('layouts.cliente')
@section('title', 'Tracking de pedidos')
@section('hero')
<div class="hero-band">
    <h1>Tracking de pedidos</h1>
    <p>Seguimiento <strong>exclusivo de tu cuenta</strong> en el portal de clientes: cada línea es un producto; el folio agrupa el pedido. Incluye día de envío y día de llegada. Flujo: <a class="cli-hero-link" href="{{ route('clientes.catalogo') }}">Catálogo</a> → <a class="cli-hero-link" href="{{ route('clientes.pedidos') }}">Pedidos</a> → aquí.</p>
</div>
@endsection

@push('styles')
<style>
    .cli-hero-link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .cli-hero-link:hover { text-decoration: underline; }
    .cli-notice { font-size: 12px; font-weight: 600; color: var(--amber); background: var(--amber-bg); border: 1px solid var(--amber); padding: 10px 14px; border-radius: var(--radius-lg); margin-bottom: 14px; display: inline-flex; align-items: center; gap: 8px; }

    .cli-portal-strip {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 12px 16px; margin-bottom: 20px;
        background: var(--purple-subtle); border: 1px solid #d4c5e8; border-radius: var(--radius-lg);
        font-size: 13px; color: #4A2070; line-height: 1.45;
    }
    .cli-portal-strip svg { flex-shrink: 0; margin-top: 1px; }
    .cli-portal-strip strong { font-weight: 700; }

    .cli-surface { background: var(--white); border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: var(--transition); }
    .cli-surface:hover { box-shadow: var(--shadow-md); }

    .track-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    .track-stat {
        background: var(--white); border: 1px solid var(--border-light); border-radius: var(--radius-lg);
        padding: 14px 16px; box-shadow: var(--shadow-sm);
    }
    .track-stat-label { font-size: 11px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; letter-spacing: 0.45px; margin-bottom: 6px; }
    .track-stat-val { font-size: 20px; font-weight: 700; color: var(--gray-text); letter-spacing: -0.03em; font-variant-numeric: tabular-nums; }
    .track-stat-sub { font-size: 11px; color: var(--gray-muted); margin-top: 4px; }

    .track-toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; padding: 16px 18px; margin-bottom: 18px; }
    .track-search {
        flex: 1; min-width: 200px; border: 1px solid var(--border-light); border-radius: 10px;
        padding: 10px 14px; font-size: 13px; font-family: inherit; color: var(--gray-text);
        outline: none; background: var(--gray-soft);
    }
    .track-search:focus { border-color: var(--purple-mid); background: var(--white); box-shadow: 0 0 0 3px var(--purple-subtle); }
    .track-filter {
        border: 1px solid var(--border-light); border-radius: 10px; padding: 10px 14px;
        font-size: 13px; font-family: inherit; color: var(--gray-text); background: var(--white);
        cursor: pointer; outline: none; min-width: 200px;
    }
    .track-filter:focus { border-color: var(--purple-mid); box-shadow: 0 0 0 3px var(--purple-subtle); }
    .track-count { font-size: 12px; font-weight: 600; color: var(--gray-muted); margin-left: auto; letter-spacing: -0.02em; text-align: right; }

    .ped-legend { display: flex; flex-wrap: wrap; gap: 10px 18px; margin-bottom: 16px; font-size: 11px; color: var(--gray-muted); padding: 0 2px; }
    .ped-legend span { display: inline-flex; align-items: center; gap: 6px; }
    .ped-legend i { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

    .card { background: var(--white); border: 1px solid var(--border-light); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); transition: var(--transition); }
    .card:hover { box-shadow: var(--shadow-md); }
    .card-head {
        padding: 14px 18px; border-bottom: 1px solid var(--border-light);
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
        background: var(--gray-soft);
    }
    .card-head h2 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin: 0; letter-spacing: -0.02em; }
    .card-head p { margin: 0; font-size: 12px; color: var(--gray-muted); max-width: 520px; line-height: 1.45; }

    .cli-table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 0 0 var(--radius-lg) var(--radius-lg); }
    .tabla { width: 100%; min-width: 1080px; border-collapse: collapse; }
    .tabla .dia-track { font-size: 12px; white-space: nowrap; }
    .tabla .dia-track.muted { color: var(--gray-muted); font-size: 11px; }
    .tabla th {
        font-size: 11px; font-weight: 700; color: var(--gray-muted); padding: 13px 16px; text-align: left;
        border-bottom: 1px solid var(--border-light); text-transform: uppercase; letter-spacing: 0.5px;
        background: var(--gray-soft); white-space: nowrap;
    }
    .tabla td { padding: 13px 16px; font-size: 13px; color: var(--gray-text); border-bottom: 1px solid var(--border-light); vertical-align: top; }
    .tabla tbody tr:last-child td { border-bottom: none; }
    .tabla tbody tr:hover td { background: var(--purple-subtle); }
    .tabla tr.tr-pedido-sep td { border-top: 2px solid var(--border-light); }
    .tabla .folio { font-weight: 700; color: var(--purple); white-space: nowrap; font-size: 13px; }
    .tabla .codigo { font-size: 11px; color: var(--gray-muted); }
    .tabla .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }

    .badge { font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 999px; white-space: nowrap; display: inline-block; }
    .badge-validacion { background: var(--purple-subtle); color: var(--purple); }
    .badge-autorizado { background: #dbeafe; color: #2563eb; }
    .badge-produccion { background: var(--amber-bg); color: var(--amber); }
    .badge-enviado { background: var(--green-bg); color: var(--green); }
    .badge-entregado { background: #dcfce7; color: #166534; }
    .badge-contado { background: var(--gray-soft); color: var(--gray-muted); font-size: 10px; }
    .badge-credito { background: #dbeafe; color: #2563eb; font-size: 10px; }

    .empty-row td { text-align: center; color: var(--gray-muted); padding: 48px 24px; font-size: 14px; }

    @media (max-width: 900px) {
        .track-stats { grid-template-columns: repeat(2, 1fr); }
        .track-count { width: 100%; margin-left: 0; text-align: left; }
    }
    @media (max-width: 480px) {
        .track-stats { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="cli-notice" role="note">Datos de demostración · Integración API pendiente</div>

<div class="cli-portal-strip" role="status">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    <span><strong>Solo portal de clientes.</strong> Lo que ves aquí corresponde a tu sesión iniciada; no es un panel interno de Salcom ni se mezcla con otros clientes.</span>
</div>

<div class="track-stats" id="trackStats" aria-live="polite">
    <div class="track-stat">
        <div class="track-stat-label">Pedidos</div>
        <div class="track-stat-val" id="statPedidos">—</div>
        <div class="track-stat-sub">Folios distintos</div>
    </div>
    <div class="track-stat">
        <div class="track-stat-label">Líneas</div>
        <div class="track-stat-val" id="statLineas">—</div>
        <div class="track-stat-sub">Productos listados</div>
    </div>
    <div class="track-stat">
        <div class="track-stat-label">En planta / trámite</div>
        <div class="track-stat-val" id="statPend">—</div>
        <div class="track-stat-sub">Validación, autorizado o producción</div>
    </div>
    <div class="track-stat">
        <div class="track-stat-label">En ruta o entregado</div>
        <div class="track-stat-val" id="statRuta">—</div>
        <div class="track-stat-sub">Enviado u entregado</div>
    </div>
</div>

<div class="cli-surface track-toolbar">
    <input type="search" class="track-search" id="trackSearch" placeholder="Buscar por folio, código o producto…" autocomplete="off" aria-label="Buscar en tracking" oninput="filtrarPedidos()">
    <select class="track-filter" id="statusFilter" onchange="filtrarPedidos()" aria-label="Filtrar por estatus">
        <option value="">Todos los estatus</option>
        <option value="validacion">En validación</option>
        <option value="autorizado">Autorizado</option>
        <option value="produccion">En producción</option>
        <option value="enviado">Enviado</option>
        <option value="entregado">Entregado</option>
    </select>
    <span class="track-count" id="pedCount"></span>
</div>

<div class="ped-legend" aria-hidden="true">
    <span><i style="background:var(--purple)"></i> Validación</span>
    <span><i style="background:#2563eb"></i> Autorizado</span>
    <span><i style="background:var(--amber)"></i> Producción</span>
    <span><i style="background:var(--green)"></i> Enviado / Entregado</span>
</div>

<div class="card">
    <div class="card-head">
        <h2>Detalle por línea</h2>
        <p>Misma información que en pedidos, ordenada para seguimiento logístico y facturación.</p>
    </div>
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

function pedidoMatchesSearch(p, q) {
    if (!q) return true;
    const qq = q.toLowerCase().trim();
    if (String(p.folio).toLowerCase().includes(qq)) return true;
    const lineas = lineasFromPedido(p);
    return lineas.some(l =>
        (l.codigo && String(l.codigo).toLowerCase().includes(qq)) ||
        (l.nombre && String(l.nombre).toLowerCase().includes(qq))
    );
}

function updateStats(list) {
    const folios = new Set(list.map(p => p.folio));
    let lineas = 0;
    list.forEach(p => { lineas += lineasFromPedido(p).length; });
    const pendKeys = ['validacion', 'autorizado', 'produccion'];
    const rutaKeys = ['enviado', 'entregado'];
    const nPend = list.filter(p => pendKeys.includes(p.key)).length;
    const nRuta = list.filter(p => rutaKeys.includes(p.key)).length;
    document.getElementById('statPedidos').textContent = String(folios.size);
    document.getElementById('statLineas').textContent = String(lineas);
    document.getElementById('statPend').textContent = String(nPend);
    document.getElementById('statRuta').textContent = String(nRuta);
}

function renderPedidos(filteredPedidos) {
    updateStats(filteredPedidos);

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
        body.innerHTML = '<tr class="empty-row"><td colspan="12">No hay resultados con este filtro o búsqueda</td></tr>';
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
    document.getElementById('pedCount').textContent = rows.length + ' línea' + (rows.length === 1 ? '' : 's') + ' · ' + nPed + ' pedido' + (nPed === 1 ? '' : 's');
}

function filtrarPedidos() {
    const s = document.getElementById('statusFilter').value;
    const q = document.getElementById('trackSearch').value;
    let list = s ? pedidos.filter(p => p.key === s) : pedidos.slice();
    list = list.filter(p => pedidoMatchesSearch(p, q));
    renderPedidos(list);
}

filtrarPedidos();
</script>
@endpush
