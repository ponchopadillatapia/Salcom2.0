@extends('layouts.cliente')
@section('title', 'Tracking de pedidos')
@section('hero')
<div class="hero-band"><h1>Tracking</h1><p>Cada producto de tu pedido aparece en su propia fila (mismo folio = un solo pedido). Nuevo: <a href="{{ route('clientes.catalogo') }}" style="color:#6B3FA0;font-weight:600">Catálogo</a> → <a href="{{ route('clientes.pedidos') }}" style="color:#6B3FA0;font-weight:600">Pedidos</a>.</p></div>
@endsection

@push('styles')
<style>
    .ped-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
    .ped-filter{border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white);cursor:pointer;outline:none}
    .ped-count{font-size:13px;color:var(--gray-muted);margin-left:auto}

    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .tabla{width:100%;border-collapse:collapse}
    .tabla th{font-size:11px;font-weight:600;color:var(--gray-muted);padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:.5px}
    .tabla td{padding:12px 14px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border);vertical-align:top}
    .tabla tbody tr:last-child td{border-bottom:none}
    .tabla tbody tr:hover td{background:#f9fafb}
    .tabla tr.tr-pedido-sep td{border-top:2px solid #e5e7eb}
    .tabla .folio{font-weight:700;color:#6B3FA0;white-space:nowrap}
    .tabla .codigo{font-size:11px;color:var(--gray-muted)}
    .tabla .num{text-align:right;white-space:nowrap}

    .badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;white-space:nowrap}
    .badge-validacion{background:#F3EEFA;color:#6B3FA0}
    .badge-autorizado{background:#dbeafe;color:#2563eb}
    .badge-produccion{background:#fffbeb;color:#d97706}
    .badge-enviado{background:#ecfdf5;color:#059669}
    .badge-entregado{background:#f0fdf4;color:#166534}
    .badge-contado{background:#f3f4f6;color:#6b7280;font-size:10px}
    .badge-credito{background:#eff6ff;color:#2563eb;font-size:10px}

    .badge-api{font-size:11px;color:#d97706;font-weight:600;background:#fffbeb;padding:3px 10px;border-radius:999px;display:inline-block;margin-bottom:16px}
    .empty-row td{text-align:center;color:#9ca3af;padding:40px 20px}
</style>
@endpush

@section('content')
<span class="badge-api">⚠ Datos de prueba — Pendiente de API</span>

<div class="ped-toolbar">
    <select class="ped-filter" id="statusFilter" onchange="filtrarPedidos()">
        <option value="">Todos los estatus</option>
        <option value="validacion">En validación</option>
        <option value="autorizado">Autorizado</option>
        <option value="produccion">En producción</option>
        <option value="enviado">Enviado</option>
        <option value="entregado">Entregado</option>
    </select>
    <span class="ped-count" id="pedCount"></span>
</div>

<div class="card">
    <table class="tabla">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Fecha</th>
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
@endsection

@push('scripts')
<script>
const HISTORIAL_KEY = 'salcom_cliente_pedidos_v1';
const HISTORIAL_SEED = [
    {folio:'PED-2026-001',fecha:'01/04/2026',pago:'contado',estatus:'entregado',key:'entregado',total:8450.00,lineas:[
        {codigo:'DET-IND',nombre:'Detergente Industrial',cantidad:10,precioUnit:500},
        {codigo:'DES-HD',nombre:'Desengrasante HD',cantidad:5,precioUnit:690},
    ]},
    {folio:'PED-2026-002',fecha:'03/04/2026',pago:'contado',estatus:'enviado',key:'enviado',total:2670.00,lineas:[
        {codigo:'ACE-SAE40',nombre:'Aceite Lubricante SAE 40',cantidad:3,precioUnit:890},
    ]},
    {folio:'PED-2026-003',fecha:'05/04/2026',pago:'contado',estatus:'produccion',key:'produccion',total:4725.00,lineas:[
        {codigo:'CIN-EMP',nombre:'Cinta Empaque',cantidad:50,precioUnit:55},
        {codigo:'STR-FILM',nombre:'Stretch Film',cantidad:20,precioUnit:98.75},
    ]},
    {folio:'PED-2026-004',fecha:'07/04/2026',pago:'contado',estatus:'autorizado',key:'autorizado',total:5850.00,lineas:[
        {codigo:'SAN-MUL',nombre:'Sanitizante Multiusos',cantidad:30,precioUnit:195},
    ]},
    {folio:'PED-2026-005',fecha:'09/04/2026',pago:'contado',estatus:'validacion',key:'validacion',total:4700.00,lineas:[
        {codigo:'SOL-DIEL',nombre:'Solvente Dieléctrico',cantidad:8,precioUnit:400},
        {codigo:'REF-IND',nombre:'Refrigerante',cantidad:2,precioUnit:750},
    ]},
];

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
        lineas.forEach(l => {
            const sub = (Number(l.precioUnit) || 0) * (Number(l.cantidad) || 0);
            rows.push({
                folio: p.folio,
                fecha: p.fecha,
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
        body.innerHTML = '<tr class="empty-row"><td colspan="10">No hay líneas con este filtro</td></tr>';
        document.getElementById('pedCount').textContent = '0 pedidos';
        return;
    }

    let prevFolio = null;
    body.innerHTML = rows.map((r, idx) => {
        const sep = r.folio !== prevFolio && idx > 0 ? ' tr-pedido-sep' : '';
        prevFolio = r.folio;
        const pu = Number(r.precioUnit) || 0;
        const fmt = n => '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2 });
        return `<tr class="${sep.trim()}"><td class="folio">${escHtml(r.folio)}</td><td>${escHtml(r.fecha)}</td><td class="codigo">${r.codigo ? escHtml(r.codigo) : '—'}</td><td>${escHtml(r.nombre)}</td><td class="num">${escHtml(r.cantidad)}</td><td class="num">${fmt(pu)}</td><td class="num">${fmt(r.subtotal)}</td><td class="num">${fmt(r.totalPedido)}</td><td>${pagoMap[r.pago]||r.pago}</td><td>${badgeMap[r.key]||r.estatus}</td></tr>`;
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
