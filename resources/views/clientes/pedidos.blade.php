@extends('layouts.cliente')
@section('title', 'Mis Pedidos')
@section('hero')
<div class="hero-band"><h1>Mis Pedidos</h1><p>Consulta el estatus de tus pedidos; los nuevos se crean desde el catálogo</p></div>
@endsection

@push('styles')
<style>
    .ped-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
    .ped-filter{border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white);cursor:pointer;outline:none}
    .ped-count{font-size:13px;color:var(--gray-muted);margin-left:auto}

    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .tabla{width:100%;border-collapse:collapse}
    .tabla th{font-size:12px;font-weight:600;color:var(--gray-muted);padding:14px 20px;text-align:left;border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:.5px}
    .tabla td{padding:14px 20px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .tabla tr:last-child td{border-bottom:none}
    .tabla tr:hover td{background:#f9fafb}
    .tabla .folio{font-weight:700;color:#6B3FA0}
    .tabla .prods{font-size:12px;color:var(--gray-muted);max-width:200px}
    .btn-del-ped{padding:6px 12px;border:1px solid #fecaca;background:#fef2f2;color:#b91c1c;border-radius:8px;font-size:12px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap}
    .btn-del-ped:hover{background:#fee2e2;border-color:#f87171}

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
        <thead><tr><th>Folio</th><th>Fecha</th><th>Productos</th><th>Total</th><th>Pago</th><th>Estatus</th><th></th></tr></thead>
        <tbody id="pedidosBody"></tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
const PEDIDOS_STORAGE_KEY = 'salcom_cliente_pedidos_v1';
const PEDIDOS_SEED = [
    {folio:'PED-2026-001',fecha:'01/04/2026',productos:'Detergente Industrial x10, Desengrasante HD x5',total:8450.00,pago:'contado',estatus:'entregado',key:'entregado'},
    {folio:'PED-2026-002',fecha:'03/04/2026',productos:'Aceite Lubricante SAE 40 x3',total:2670.00,pago:'contado',estatus:'enviado',key:'enviado'},
    {folio:'PED-2026-003',fecha:'05/04/2026',productos:'Cinta Empaque x50, Stretch Film x20',total:4725.00,pago:'contado',estatus:'produccion',key:'produccion'},
    {folio:'PED-2026-004',fecha:'07/04/2026',productos:'Sanitizante Multiusos x30',total:5850.00,pago:'contado',estatus:'autorizado',key:'autorizado'},
    {folio:'PED-2026-005',fecha:'09/04/2026',productos:'Solvente Dieléctrico x8, Refrigerante x2',total:4700.00,pago:'contado',estatus:'validacion',key:'validacion'},
];

function loadPedidosStorage() {
    try {
        const raw = localStorage.getItem(PEDIDOS_STORAGE_KEY);
        if (raw) {
            const data = JSON.parse(raw);
            if (Array.isArray(data) && data.length) return data;
        }
    } catch (e) {}
    return PEDIDOS_SEED.map(p => ({...p}));
}

function savePedidosStorage(arr) {
    localStorage.setItem(PEDIDOS_STORAGE_KEY, JSON.stringify(arr));
}

let pedidos = loadPedidosStorage();

try {
    localStorage.removeItem(window.SALCOM_PEDIDOS_NAV_BADGE_KEY || 'salcom_cliente_pedidos_nav_badge');
} catch (e) {}
if (typeof window.salcomSyncPedidosNavBadge === 'function') window.salcomSyncPedidosNavBadge();

const badgeMap = {
    validacion:'<span class="badge badge-validacion">En validación</span>',
    autorizado:'<span class="badge badge-autorizado">Autorizado</span>',
    produccion:'<span class="badge badge-produccion">En producción</span>',
    enviado:'<span class="badge badge-enviado">Enviado</span>',
    entregado:'<span class="badge badge-entregado">Entregado</span>',
};
const pagoMap = {contado:'<span class="badge badge-contado">Contado</span>',credito:'<span class="badge badge-credito">Crédito</span>'};

function renderPedidos(list) {
    const body = document.getElementById('pedidosBody');
    if (!list.length) { body.innerHTML = '<tr class="empty-row"><td colspan="7">No hay pedidos con este filtro</td></tr>'; }
    else {
        body.innerHTML = list.map(p => `<tr><td class="folio">${p.folio}</td><td>${p.fecha}</td><td class="prods">${p.productos}</td><td>$${p.total.toLocaleString('es-MX',{minimumFractionDigits:2})}</td><td>${pagoMap[p.pago]||p.pago}</td><td>${badgeMap[p.key]||p.estatus}</td><td><button type="button" class="btn-del-ped" onclick="eliminarPedido('${p.folio}')">Eliminar</button></td></tr>`).join('');
    }
    document.getElementById('pedCount').textContent = list.length + ' pedidos';
}

function filtrarPedidos() {
    const s = document.getElementById('statusFilter').value;
    renderPedidos(s ? pedidos.filter(p => p.key === s) : pedidos);
}

function eliminarPedido(folio) {
    if (!confirm('¿Eliminar el pedido ' + folio + '?')) return;
    pedidos = pedidos.filter(p => p.folio !== folio);
    savePedidosStorage(pedidos);
    filtrarPedidos();
}

filtrarPedidos();
</script>
@endpush
