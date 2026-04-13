@extends('layouts.cliente')
@section('title', 'Mis Pedidos')
@section('hero')
<div class="hero-band"><h1>Mis Pedidos</h1><p>Consulta el estatus de tus pedidos y crea nuevos</p></div>
@endsection

@push('styles')
<style>
    .ped-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
    .ped-filter{border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white);cursor:pointer;outline:none}
    .ped-count{font-size:13px;color:var(--gray-muted)}
    .btn-new{padding:9px 20px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s;margin-left:auto;display:flex;align-items:center;gap:6px}
    .btn-new:hover{background:#4A2070}

    .card{background:var(--white);border:1px solid var(--border);border-radius:10px;overflow:hidden}
    .tabla{width:100%;border-collapse:collapse}
    .tabla th{font-size:12px;font-weight:600;color:var(--gray-muted);padding:14px 20px;text-align:left;border-bottom:1px solid var(--border);text-transform:uppercase;letter-spacing:.5px}
    .tabla td{padding:14px 20px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .tabla tr:last-child td{border-bottom:none}
    .tabla tr:hover td{background:#f9fafb}
    .tabla .folio{font-weight:700;color:#6B3FA0}
    .tabla .prods{font-size:12px;color:var(--gray-muted);max-width:200px}

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

    /* Modal */
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:400;align-items:center;justify-content:center}
    .modal-overlay.active{display:flex}
    .modal{background:var(--white);border-radius:14px;padding:28px;width:100%;max-width:560px;max-height:85vh;overflow-y:auto;animation:fadeUp .25s ease}
    @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    .modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
    .modal-head h3{font-size:18px;font-weight:700;color:var(--gray-text)}
    .btn-close{width:32px;height:32px;border-radius:50%;border:none;background:#f3f4f6;cursor:pointer;font-size:16px;color:var(--gray-muted);display:flex;align-items:center;justify-content:center}.btn-close:hover{background:#e5e7eb}

    .prod-select{margin-bottom:16px}
    .prod-select label{display:block;font-size:12px;font-weight:600;color:var(--gray-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
    .prod-select select,.prod-select input{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .prod-select select:focus,.prod-select input:focus{border-color:#6B3FA0;box-shadow:0 0 0 3px rgba(107,63,160,.1)}

    .cart-items{margin-bottom:16px}
    .cart-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px}
    .cart-item:last-child{border-bottom:none}
    .cart-item .name{flex:1;font-weight:500}.cart-item .qty{color:var(--gray-muted);font-size:12px}.cart-item .price{font-weight:600}
    .cart-item .remove{background:none;border:none;color:#dc2626;cursor:pointer;font-size:14px;padding:2px 6px}

    .cart-total{display:flex;justify-content:space-between;padding:12px 0;border-top:2px solid var(--border);font-size:15px;font-weight:700;color:var(--gray-text)}
    .btn-confirm{width:100%;padding:12px;background:#6B3FA0;color:#fff;border:none;border-radius:10px;font-size:14px;font-family:inherit;font-weight:600;cursor:pointer;margin-top:12px;transition:all .15s}
    .btn-confirm:hover{background:#4A2070}
    .btn-confirm:disabled{background:#d1d5db;cursor:not-allowed}
    .btn-add-item{padding:8px 16px;background:var(--white);border:1.5px solid #6B3FA0;border-radius:8px;color:#6B3FA0;font-size:12px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s;margin-top:8px}
    .btn-add-item:hover{background:#F3EEFA}

    .success-msg{background:#ecfdf5;border:1px solid #059669;border-radius:10px;padding:16px;text-align:center;margin-top:16px}
    .success-msg h4{color:#059669;font-size:15px;margin-bottom:4px}.success-msg p{font-size:13px;color:#6b7280}
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
    <button class="btn-new" onclick="abrirModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nuevo pedido
    </button>
</div>

<div class="card">
    <table class="tabla">
        <thead><tr><th>Folio</th><th>Fecha</th><th>Productos</th><th>Total</th><th>Pago</th><th>Estatus</th></tr></thead>
        <tbody id="pedidosBody"></tbody>
    </table>
</div>

<!-- Modal nuevo pedido -->
<div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this)cerrarModal()">
    <div class="modal">
        <div class="modal-head"><h3>Nuevo Pedido</h3><button class="btn-close" onclick="cerrarModal()">✕</button></div>
        <div id="modalContent">
            <div class="prod-select">
                <label>Producto</label>
                <select id="prodSelect">
                    <option value="">Selecciona un producto</option>
                </select>
            </div>
            <div class="prod-select">
                <label>Cantidad</label>
                <input type="number" id="prodQty" min="1" value="1" placeholder="Cantidad">
            </div>
            <button class="btn-add-item" onclick="agregarItem()">+ Agregar al pedido</button>
            <div class="cart-items" id="cartItems"></div>
            <div class="cart-total" id="cartTotal" style="display:none"><span>Total</span><span id="totalAmount">$0.00</span></div>
            <button class="btn-confirm" id="btnConfirm" onclick="confirmarPedido()" disabled>Confirmar pedido</button>
        </div>
        <div id="successMsg" style="display:none"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const pedidos = [
    {folio:'PED-2026-001',fecha:'01/04/2026',productos:'Detergente Industrial x10, Desengrasante HD x5',total:8450.00,pago:'contado',estatus:'entregado',key:'entregado'},
    {folio:'PED-2026-002',fecha:'03/04/2026',productos:'Aceite Lubricante SAE 40 x3',total:2670.00,pago:'contado',estatus:'enviado',key:'enviado'},
    {folio:'PED-2026-003',fecha:'05/04/2026',productos:'Cinta Empaque x50, Stretch Film x20',total:4725.00,pago:'contado',estatus:'produccion',key:'produccion'},
    {folio:'PED-2026-004',fecha:'07/04/2026',productos:'Sanitizante Multiusos x30',total:5850.00,pago:'contado',estatus:'autorizado',key:'autorizado'},
    {folio:'PED-2026-005',fecha:'09/04/2026',productos:'Solvente Dieléctrico x8, Refrigerante x2',total:4700.00,pago:'contado',estatus:'validacion',key:'validacion'},
];

const catalogoSimple = [
    {codigo:'SAL-001',nombre:'Detergente Industrial 20L',precio:485},
    {codigo:'SAL-002',nombre:'Desengrasante HD',precio:320},
    {codigo:'SAL-003',nombre:'Sanitizante Multiusos',precio:195},
    {codigo:'SAL-004',nombre:'Aceite Lubricante SAE 40',precio:890},
    {codigo:'SAL-005',nombre:'Grasa Multiusos EP2',precio:650},
    {codigo:'SAL-006',nombre:'Cinta de Empaque 48mm',precio:28.50},
    {codigo:'SAL-007',nombre:'Stretch Film 18"',precio:165},
    {codigo:'SAL-008',nombre:'Solvente Dieléctrico',precio:275},
    {codigo:'SAL-010',nombre:'Jabón Líquido Manos',precio:145},
    {codigo:'SAL-011',nombre:'Bolsa Negra 90x120',precio:12.50},
    {codigo:'SAL-012',nombre:'Refrigerante Industrial',precio:1250},
];

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
    if (!list.length) { body.innerHTML = '<tr class="empty-row"><td colspan="6">No hay pedidos con este filtro</td></tr>'; }
    else { body.innerHTML = list.map(p => `<tr><td class="folio">${p.folio}</td><td>${p.fecha}</td><td class="prods">${p.productos}</td><td>$${p.total.toLocaleString('es-MX',{minimumFractionDigits:2})}</td><td>${pagoMap[p.pago]||p.pago}</td><td>${badgeMap[p.key]||p.estatus}</td></tr>`).join(''); }
    document.getElementById('pedCount').textContent = list.length + ' pedidos';
}

function filtrarPedidos() {
    const s = document.getElementById('statusFilter').value;
    renderPedidos(s ? pedidos.filter(p => p.key === s) : pedidos);
}

// Cart
let cart = [];

function abrirModal() {
    cart = [];
    renderCart();
    document.getElementById('modalContent').style.display = '';
    document.getElementById('successMsg').style.display = 'none';
    const sel = document.getElementById('prodSelect');
    sel.innerHTML = '<option value="">Selecciona un producto</option>' + catalogoSimple.map(p => `<option value="${p.codigo}">${p.nombre} — $${p.precio.toFixed(2)}</option>`).join('');
    document.getElementById('prodQty').value = 1;
    document.getElementById('modalOverlay').classList.add('active');
}

function cerrarModal() { document.getElementById('modalOverlay').classList.remove('active'); }

function agregarItem() {
    const sel = document.getElementById('prodSelect');
    const qty = parseInt(document.getElementById('prodQty').value) || 1;
    if (!sel.value) return;
    const prod = catalogoSimple.find(p => p.codigo === sel.value);
    if (!prod) return;
    const existing = cart.find(c => c.codigo === prod.codigo);
    if (existing) { existing.qty += qty; } else { cart.push({...prod, qty}); }
    sel.value = '';
    document.getElementById('prodQty').value = 1;
    renderCart();
}

function removeItem(codigo) { cart = cart.filter(c => c.codigo !== codigo); renderCart(); }

function renderCart() {
    const el = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotal');
    const btn = document.getElementById('btnConfirm');
    if (!cart.length) { el.innerHTML = '<div style="font-size:13px;color:#9ca3af;padding:12px 0;">Agrega productos al pedido</div>'; totalEl.style.display = 'none'; btn.disabled = true; return; }
    el.innerHTML = cart.map(c => `<div class="cart-item"><span class="name">${c.nombre}</span><span class="qty">x${c.qty}</span><span class="price">$${(c.precio*c.qty).toLocaleString('es-MX',{minimumFractionDigits:2})}</span><button class="remove" onclick="removeItem('${c.codigo}')">✕</button></div>`).join('');
    const total = cart.reduce((s,c) => s + c.precio * c.qty, 0);
    document.getElementById('totalAmount').textContent = '$' + total.toLocaleString('es-MX',{minimumFractionDigits:2});
    totalEl.style.display = 'flex';
    btn.disabled = false;
}

function confirmarPedido() {
    const folio = 'PED-2026-' + String(pedidos.length + 1).padStart(3,'0');
    const total = cart.reduce((s,c) => s + c.precio * c.qty, 0);
    const prods = cart.map(c => c.nombre + ' x' + c.qty).join(', ');
    pedidos.unshift({folio, fecha: new Date().toLocaleDateString('es-MX'), productos: prods, total, pago:'contado', estatus:'En validación', key:'validacion'});
    filtrarPedidos();
    document.getElementById('modalContent').style.display = 'none';
    document.getElementById('successMsg').innerHTML = `<div class="success-msg"><h4>Pedido creado</h4><p>Folio: <strong>${folio}</strong><br>Total: $${total.toLocaleString('es-MX',{minimumFractionDigits:2})}<br>Estatus: En validación</p></div>`;
    document.getElementById('successMsg').style.display = '';
}

renderPedidos(pedidos);
</script>
@endpush
