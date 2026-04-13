@extends('layouts.cliente')
@section('title', 'Catálogo')
@section('hero')
<div class="hero-band"><h1>Catálogo de Productos</h1><p>Consulta productos disponibles y precios según tu tipo de cliente: {{ session('cliente_tipo', '—') }}</p></div>
@endsection

@push('styles')
<style>
    .cat-toolbar { display:flex; align-items:center; gap:12px; margin-bottom:24px; flex-wrap:wrap; }
    .cat-search { flex:1; min-width:200px; border:1.5px solid var(--border); border-radius:8px; padding:9px 14px; font-size:13px; font-family:inherit; color:var(--gray-text); outline:none; background:var(--white); }
    .cat-search:focus { border-color:#6B3FA0; box-shadow:0 0 0 3px rgba(107,63,160,0.1); }
    .cat-filter { border:1.5px solid var(--border); border-radius:8px; padding:9px 14px; font-size:13px; font-family:inherit; color:var(--gray-text); background:var(--white); cursor:pointer; outline:none; }
    .cat-count { font-size:13px; color:var(--gray-muted); margin-left:auto; }

    .products-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-bottom:24px; }
    .prod-card { background:var(--white); border:1px solid var(--border); border-radius:12px; overflow:hidden; transition:all .15s; display:flex; flex-direction:column; }
    .prod-card:hover { border-color:#9C6DD0; box-shadow:0 4px 16px rgba(107,63,160,0.08); }
    .prod-img { height:140px; background:#f3f4f6; display:flex; align-items:center; justify-content:center; border-bottom:1px solid var(--border); }
    .prod-img svg { opacity:.25; }
    .prod-body { padding:16px; flex:1; display:flex; flex-direction:column; }
    .prod-cat { font-size:11px; font-weight:600; color:#6B3FA0; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
    .prod-name { font-size:15px; font-weight:700; color:var(--gray-text); margin-bottom:4px; }
    .prod-code { font-size:11px; color:var(--gray-muted); margin-bottom:8px; }
    .prod-desc { font-size:12px; color:var(--gray-muted); line-height:1.5; margin-bottom:12px; flex:1; }
    .prod-footer { display:flex; align-items:center; justify-content:space-between; gap:8px; }
    .prod-price { font-size:18px; font-weight:700; color:var(--gray-text); }
    .prod-unit { font-size:11px; color:var(--gray-muted); }
    .stock-badge { font-size:10px; font-weight:600; padding:3px 8px; border-radius:999px; }
    .stock-ok { background:#ecfdf5; color:#059669; }
    .stock-low { background:#fffbeb; color:#d97706; }
    .stock-out { background:#fef2f2; color:#dc2626; }
    .btn-add { padding:7px 14px; background:#6B3FA0; color:#fff; border:none; border-radius:8px; font-size:12px; font-family:inherit; font-weight:600; cursor:pointer; transition:all .15s; white-space:nowrap; }
    .btn-add:hover { background:#4A2070; }
    .btn-add:disabled { background:#d1d5db; cursor:not-allowed; }

    .pagination-mock { display:flex; align-items:center; justify-content:center; gap:4px; }
    .page-btn { width:32px; height:32px; border:1px solid var(--border); border-radius:6px; background:var(--white); font-size:13px; font-family:inherit; color:var(--gray-text); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .1s; }
    .page-btn:hover { background:#F3EEFA; color:#6B3FA0; border-color:#9C6DD0; }
    .page-btn.active { background:#6B3FA0; color:#fff; border-color:#6B3FA0; }

    .badge-api { font-size:11px; color:#d97706; font-weight:600; background:#fffbeb; padding:3px 10px; border-radius:999px; display:inline-block; margin-bottom:16px; }

    @media(max-width:900px) { .products-grid { grid-template-columns:1fr 1fr; } }
    @media(max-width:600px) { .products-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<span class="badge-api">⚠ Datos de prueba — Pendiente de API de Alan</span>

<div class="cat-toolbar">
    <input type="text" class="cat-search" id="searchInput" placeholder="Buscar por nombre o código..." oninput="filtrar()">
    <select class="cat-filter" id="catFilter" onchange="filtrar()">
        <option value="">Todas las categorías</option>
        <option value="limpieza">Limpieza</option>
        <option value="industrial">Industrial</option>
        <option value="empaque">Empaque</option>
        <option value="quimicos">Químicos</option>
    </select>
    <span class="cat-count" id="prodCount">12 productos</span>
</div>

<div class="products-grid" id="productsGrid"></div>

<div class="pagination-mock">
    <button class="page-btn">◀</button>
    <button class="page-btn active">1</button>
    <button class="page-btn">2</button>
    <button class="page-btn">3</button>
    <button class="page-btn">▶</button>
</div>
@endsection

@push('scripts')
<script>
const productos = [
    { codigo:'SAL-001', nombre:'Detergente Industrial 20L', desc:'Detergente concentrado para uso industrial, biodegradable', categoria:'limpieza', precio:485.00, unidad:'Cubeta 20L', stock:150 },
    { codigo:'SAL-002', nombre:'Desengrasante HD', desc:'Desengrasante de alta potencia para maquinaria pesada', categoria:'limpieza', precio:320.00, unidad:'Galón', stock:85 },
    { codigo:'SAL-003', nombre:'Sanitizante Multiusos', desc:'Sanitizante con registro EPA para superficies', categoria:'limpieza', precio:195.00, unidad:'Litro', stock:200 },
    { codigo:'SAL-004', nombre:'Aceite Lubricante SAE 40', desc:'Aceite mineral para maquinaria industrial', categoria:'industrial', precio:890.00, unidad:'Cubeta 19L', stock:45 },
    { codigo:'SAL-005', nombre:'Grasa Multiusos EP2', desc:'Grasa de litio para rodamientos y chumaceras', categoria:'industrial', precio:650.00, unidad:'Cubeta 16kg', stock:30 },
    { codigo:'SAL-006', nombre:'Cinta de Empaque 48mm', desc:'Cinta adhesiva transparente para sellado de cajas', categoria:'empaque', precio:28.50, unidad:'Rollo', stock:500 },
    { codigo:'SAL-007', nombre:'Stretch Film 18"', desc:'Película estirable para paletizado y protección', categoria:'empaque', precio:165.00, unidad:'Rollo', stock:120 },
    { codigo:'SAL-008', nombre:'Solvente Dieléctrico', desc:'Limpiador de contactos eléctricos de secado rápido', categoria:'quimicos', precio:275.00, unidad:'Aerosol 500ml', stock:8 },
    { codigo:'SAL-009', nombre:'Ácido Muriático', desc:'Ácido clorhídrico al 28% para limpieza industrial', categoria:'quimicos', precio:95.00, unidad:'Litro', stock:0 },
    { codigo:'SAL-010', nombre:'Jabón Líquido Manos', desc:'Jabón antibacterial para dispensador industrial', categoria:'limpieza', precio:145.00, unidad:'Galón', stock:180 },
    { codigo:'SAL-011', nombre:'Bolsa Negra 90x120', desc:'Bolsa para basura calibre 300, uso rudo', categoria:'empaque', precio:12.50, unidad:'Pieza', stock:2000 },
    { codigo:'SAL-012', nombre:'Refrigerante Industrial', desc:'Refrigerante para sistemas de corte CNC', categoria:'industrial', precio:1250.00, unidad:'Cubeta 20L', stock:15 },
];

function stockBadge(s) {
    if (s === 0) return '<span class="stock-badge stock-out">Agotado</span>';
    if (s <= 20) return '<span class="stock-badge stock-low">Bajo stock ('+s+')</span>';
    return '<span class="stock-badge stock-ok">Disponible ('+s+')</span>';
}

function renderProducts(list) {
    const grid = document.getElementById('productsGrid');
    grid.innerHTML = list.map(p => `
        <div class="prod-card" data-cat="${p.categoria}" data-name="${p.nombre.toLowerCase()}" data-code="${p.codigo.toLowerCase()}">
            <div class="prod-img"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg></div>
            <div class="prod-body">
                <div class="prod-cat">${p.categoria}</div>
                <div class="prod-name">${p.nombre}</div>
                <div class="prod-code">${p.codigo} · ${p.unidad}</div>
                <div class="prod-desc">${p.desc}</div>
                <div class="prod-footer">
                    <div><div class="prod-price">$${p.precio.toLocaleString('es-MX',{minimumFractionDigits:2})}</div>${stockBadge(p.stock)}</div>
                    <button class="btn-add" ${p.stock===0?'disabled':''} onclick="agregarPedido('${p.codigo}')">${p.stock===0?'Agotado':'Agregar'}</button>
                </div>
            </div>
        </div>
    `).join('');
    document.getElementById('prodCount').textContent = list.length + ' productos';
}

function filtrar() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const cat = document.getElementById('catFilter').value;
    const filtered = productos.filter(p => {
        const matchSearch = !q || p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q);
        const matchCat = !cat || p.categoria === cat;
        return matchSearch && matchCat;
    });
    renderProducts(filtered);
}

function agregarPedido(codigo) {
    alert('Producto ' + codigo + ' agregado al pedido (funcionalidad pendiente de API)');
}

renderProducts(productos);
</script>
@endpush
