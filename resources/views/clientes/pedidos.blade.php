@extends('layouts.cliente')
@section('title', 'Pedidos — Carrito')
@section('hero')
<div class="hero-band">
    <h1>Pedidos</h1>
    <p>Revisa tu carrito, elige líneas y confirma la compra. Los pedidos encargados aparecen en <a class="cli-hero-link" href="{{ route('clientes.tracking') }}">Tracking</a> y el historial de facturas en <a class="cli-hero-link" href="{{ route('clientes.estado-cuenta') }}">Estado de cuenta</a>.</p>
</div>
@endsection

@push('styles')
<style>
    .cli-hero-link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .cli-hero-link:hover { text-decoration: underline; }

    .cli-notice { font-size: 12px; font-weight: 600; color: var(--amber); background: var(--amber-bg); border: 1px solid var(--amber); padding: 10px 14px; border-radius: var(--radius-lg); margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px; }

    .cart-banner { display:none; padding:12px 16px; border-radius: var(--radius-lg); margin-bottom:18px; font-size:13px; font-weight:500; }
    .cart-banner.ok { background: var(--green-bg); border:1px solid var(--green); color: var(--green); }
    .cart-banner.err { background: var(--red-bg); border:1px solid var(--red); color: var(--red); }

    .cart-toolbar { display:flex; align-items:center; gap:14px; margin-bottom:18px; flex-wrap:wrap; padding:16px 18px; background:var(--white); border:1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
    .cart-toolbar label { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--gray-text); cursor:pointer; user-select:none; font-weight:500; }
    .cart-toolbar input[type="checkbox"] { width:17px; height:17px; accent-color: var(--purple); }
    .cart-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
    .btn-ghost { padding:9px 16px; border:1px solid var(--border-light); border-radius:10px; background:var(--white); font-size:12px; font-family:inherit; font-weight:600; color:var(--gray-text); cursor:pointer; transition: var(--transition); }
    .btn-ghost:hover { border-color: var(--purple-mid); color: var(--purple); background: var(--purple-subtle); }
    .btn-buy { padding:10px 22px; background: var(--purple); color:#fff; border:none; border-radius:10px; font-size:13px; font-family:inherit; font-weight:700; cursor:pointer; transition: var(--transition); }
    .btn-buy:hover:not(:disabled) { background: var(--purple-dark); box-shadow: var(--shadow-md); }
    .btn-buy:disabled { background:#d1d5db; cursor:not-allowed; opacity: 0.85; }
    .cart-summary { margin-left:auto; font-size:13px; color:var(--gray-muted); }
    .cart-summary strong { color:var(--gray-text); font-size:15px; font-weight: 700; }

    .cart-card { background:var(--white); border:1px solid var(--border-light); border-radius: var(--radius-lg); overflow:hidden; box-shadow: var(--shadow-sm); transition: var(--transition); }
    .cart-card:hover { box-shadow: var(--shadow-md); }
    .cli-table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .tabla { width:100%; min-width: 640px; border-collapse:collapse; }
    .tabla th { font-size:11px; font-weight:700; color:var(--gray-muted); padding:14px 18px; text-align:left; border-bottom:1px solid var(--border-light); text-transform:uppercase; letter-spacing:.5px; background: var(--gray-soft); }
    .tabla td { padding:14px 18px; font-size:13px; color:var(--gray-text); border-bottom:1px solid var(--border-light); vertical-align:middle; }
    .tabla tr:last-child td { border-bottom:none; }
    .tabla tbody tr:hover td { background: var(--purple-subtle); }
    .tabla .codigo { font-size:11px; color:var(--gray-muted); }
    .tabla .nombre { font-weight:600; max-width:320px; line-height: 1.35; }
    .qty-wrap { display:flex; align-items:center; gap:6px; }
    .qty-wrap button { width:34px; height:34px; border:1px solid var(--border-light); border-radius:8px; background:var(--white); font-size:16px; line-height:1; cursor:pointer; color:var(--gray-text); transition: var(--transition); }
    .qty-wrap button:hover { background: var(--purple-subtle); border-color: var(--purple-mid); color: var(--purple); }
    .qty-wrap input { width:56px; text-align:center; border:1px solid var(--border-light); border-radius:8px; padding:7px; font-size:13px; font-family:inherit; }
    .btn-remove { padding:7px 14px; border:1px solid var(--red); background: var(--red-bg); color: var(--red); border-radius:8px; font-size:12px; font-family:inherit; font-weight:600; cursor:pointer; transition: var(--transition); }
    .btn-remove:hover { filter: brightness(0.97); }
    .empty-cart { text-align:center; padding:56px 28px; color:var(--gray-muted); border-top: 1px solid var(--border-light); }
    .empty-cart svg { margin-bottom: 12px; opacity: 0.35; }
    .empty-cart p { font-size: 15px; font-weight: 600; color: var(--gray-text); margin-bottom: 8px; }
    .empty-cart a { color: var(--purple); font-weight:700; text-decoration: none; }
    .empty-cart a:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="cli-notice" role="note">Carrito guardado en este navegador · Pendiente sincronizar con API</div>
<div id="cartBanner" class="cart-banner" role="status" aria-live="polite"></div>

<div class="cart-toolbar" id="cartToolbar" style="display:none">
    <label><input type="checkbox" id="chkMaster" title="Seleccionar o quitar todos"> Seleccionar todos</label>
    <div class="cart-actions">
        <button type="button" class="btn-ghost" id="btnDeselect">Quitar selección</button>
        <button type="button" class="btn-buy" id="btnComprar">Comprar seleccionados</button>
    </div>
    <div class="cart-summary">Selección: <strong id="selTotal">$0.00</strong></div>
</div>

<div class="cart-card" id="cartCard">
    <div class="cli-table-scroll">
    <table class="tabla">
        <thead>
            <tr>
                <th style="width:44px"></th>
                <th>Producto</th>
                <th>P. unitario</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
                <th style="width:100px"></th>
            </tr>
        </thead>
        <tbody id="cartBody"></tbody>
    </table>
    </div>
    <div id="cartEmpty" class="empty-cart" style="display:none">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <p>Tu carrito está vacío</p>
        <span>Explora el catálogo y agrega productos.</span><br><br>
        <a href="{{ route('clientes.catalogo') }}">Ir al catálogo</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CART_KEY = window.SALCOM_CART_STORAGE_KEY || 'salcom_cliente_carrito_v1';
const HISTORIAL_KEY = window.SALCOM_PEDIDOS_HISTORIAL_KEY || 'salcom_cliente_pedidos_v1';
const HISTORIAL_SEED = @json(config('cliente_portal.historial_pedidos.seed'));

function loadCart() {
    try {
        const raw = localStorage.getItem(CART_KEY);
        if (raw) {
            const data = JSON.parse(raw);
            if (Array.isArray(data)) return data;
        }
    } catch (e) {}
    return [];
}

function saveCart(arr) {
    localStorage.setItem(CART_KEY, JSON.stringify(arr));
}

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

function saveHistorial(arr) {
    localStorage.setItem(HISTORIAL_KEY, JSON.stringify(arr));
}

function nextPedidoFolio(list) {
    let max = 0;
    for (const p of list) {
        const m = /^PED-2026-(\d+)$/.exec(p.folio);
        if (m) max = Math.max(max, parseInt(m[1], 10));
    }
    return 'PED-2026-' + String(max + 1).padStart(3, '0');
}

let cart = loadCart();
const selected = new Set();

function showBanner(msg, isErr) {
    const el = document.getElementById('cartBanner');
    el.textContent = msg;
    el.style.display = 'block';
    el.classList.remove('ok', 'err');
    el.classList.add(isErr ? 'err' : 'ok');
    if (!isErr) setTimeout(() => { el.style.display = 'none'; }, 6500);
}

function syncMasterCheckbox() {
    const master = document.getElementById('chkMaster');
    const rows = cart.length;
    if (!rows) { master.checked = false; master.indeterminate = false; return; }
    let sel = 0;
    cart.forEach((_, i) => { if (selected.has(i)) sel++; });
    master.checked = sel === rows;
    master.indeterminate = sel > 0 && sel < rows;
}

function updateSelectionTotal() {
    let t = 0;
    cart.forEach((line, i) => {
        if (selected.has(i)) t += (line.precioUnit || 0) * (line.cantidad || 0);
    });
    document.getElementById('selTotal').textContent = '$' + t.toLocaleString('es-MX', { minimumFractionDigits: 2 });
    document.getElementById('btnComprar').disabled = selected.size === 0;
}

function normalizeSelectionAfterRender() {
    const next = new Set();
    cart.forEach((_, i) => { if (selected.has(i)) next.add(i); });
    selected.clear();
    next.forEach(i => { if (i < cart.length) selected.add(i); });
}

function render() {
    cart = loadCart();
    normalizeSelectionAfterRender();

    const body = document.getElementById('cartBody');
    const empty = document.getElementById('cartEmpty');
    const toolbar = document.getElementById('cartToolbar');
    const table = body.closest('table');

    if (typeof window.salcomSyncPedidosNavBadge === 'function') window.salcomSyncPedidosNavBadge();

    if (!cart.length) {
        body.innerHTML = '';
        empty.style.display = 'block';
        table.style.display = 'none';
        toolbar.style.display = 'none';
        return;
    }

    empty.style.display = 'none';
    table.style.display = 'table';
    toolbar.style.display = 'flex';

    body.innerHTML = cart.map((line, i) => {
        const sub = (line.precioUnit || 0) * (line.cantidad || 0);
        const chk = selected.has(i) ? 'checked' : '';
        return `<tr data-idx="${i}">
            <td><input type="checkbox" class="row-chk" data-idx="${i}" ${chk}></td>
            <td><div class="nombre">${escapeHtml(line.nombre)}</div><div class="codigo">${escapeHtml(line.codigo)}</div></td>
            <td>$${Number(line.precioUnit).toLocaleString('es-MX', { minimumFractionDigits: 2 })}</td>
            <td>
                <div class="qty-wrap">
                    <button type="button" data-act="minus" data-idx="${i}" aria-label="Menos">−</button>
                    <input type="number" min="1" value="${line.cantidad}" data-qty="${i}">
                    <button type="button" data-act="plus" data-idx="${i}" aria-label="Más">+</button>
                </div>
            </td>
            <td>$${sub.toLocaleString('es-MX', { minimumFractionDigits: 2 })}</td>
            <td><button type="button" class="btn-remove" data-remove="${i}">Quitar</button></td>
        </tr>`;
    }).join('');

    body.querySelectorAll('.row-chk').forEach(chk => {
        chk.addEventListener('change', function () {
            const i = parseInt(this.dataset.idx, 10);
            if (this.checked) selected.add(i); else selected.delete(i);
            syncMasterCheckbox();
            updateSelectionTotal();
        });
    });

    body.querySelectorAll('[data-act="minus"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const i = parseInt(btn.dataset.idx, 10);
            if (cart[i].cantidad > 1) cart[i].cantidad--;
            saveCart(cart);
            render();
        });
    });
    body.querySelectorAll('[data-act="plus"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const i = parseInt(btn.dataset.idx, 10);
            cart[i].cantidad++;
            saveCart(cart);
            render();
        });
    });
    body.querySelectorAll('[data-qty]').forEach(inp => {
        inp.addEventListener('change', () => {
            const i = parseInt(inp.dataset.qty, 10);
            let v = parseInt(inp.value, 10);
            if (!Number.isFinite(v) || v < 1) v = 1;
            cart[i].cantidad = v;
            saveCart(cart);
            render();
        });
    });
    body.querySelectorAll('[data-remove]').forEach(btn => {
        btn.addEventListener('click', () => {
            const i = parseInt(btn.dataset.remove, 10);
            cart.splice(i, 1);
            saveCart(cart);
            selected.clear();
            render();
        });
    });

    syncMasterCheckbox();
    updateSelectionTotal();
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

document.getElementById('chkMaster').addEventListener('change', function () {
    cart = loadCart();
    if (!cart.length) return;
    if (this.checked) {
        cart.forEach((_, i) => selected.add(i));
    } else {
        selected.clear();
    }
    render();
});

document.getElementById('btnDeselect').addEventListener('click', () => {
    selected.clear();
    render();
});

document.getElementById('btnComprar').addEventListener('click', () => {
    if (!selected.size) {
        showBanner('Selecciona al menos una línea para comprar.', true);
        return;
    }
    const indices = Array.from(selected).sort((a, b) => b - a);
    const lines = indices.map(i => cart[i]).filter(Boolean);
    const lineas = lines.map(l => ({
        codigo: l.codigo,
        nombre: l.nombre,
        cantidad: l.cantidad,
        precioUnit: l.precioUnit,
    }));
    const total = lineas.reduce((s, l) => s + (Number(l.precioUnit) || 0) * (Number(l.cantidad) || 0), 0);

    const historial = loadHistorial();
    const folio = nextPedidoFolio(historial);
    historial.unshift({
        folio,
        fecha: new Date().toLocaleDateString('es-MX'),
        diaEnviado: '',
        diaLlegada: '',
        lineas,
        total,
        pago: 'contado',
        estatus: 'En validación',
        key: 'validacion',
    });
    saveHistorial(historial);

    indices.forEach(i => cart.splice(i, 1));
    saveCart(cart);
    selected.clear();

    showBanner('Pedido ' + folio + ' encargado correctamente. Revisa Tracking.', false);
    render();
});

render();
</script>
@endpush
