@extends('layouts.cliente')
@section('title', 'Catálogo')
@section('hero')
<div class="hero-band">
    <h1>Catálogo de productos</h1>
    <p>Precios según tu perfil (<strong>{{ ucfirst(session('cliente_tipo', '—')) }}</strong>). Agrega al carrito y confirma en <a class="cli-hero-link" href="{{ route('clientes.pedidos') }}">Pedidos</a> · <a class="cli-hero-link" href="{{ route('clientes.tracking') }}">Tracking</a></p>
</div>
@endsection

@push('styles')
<style>
    .cli-hero-link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .cli-hero-link:hover { text-decoration: underline; }

    .cli-notice { font-size: 12px; font-weight: 600; color: var(--amber); background: var(--amber-bg); border: 1px solid var(--amber); padding: 10px 14px; border-radius: var(--radius-lg); margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px; }
    .cli-surface { background: var(--white); border: 1px solid var(--border-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: var(--transition); }
    .cli-surface--toolbar { padding: 16px 18px; margin-bottom: 22px; }
    .cli-surface--toolbar:hover { box-shadow: var(--shadow-md); }

    .cat-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
    .cat-search { flex:1; min-width:200px; border:1px solid var(--border-light); border-radius:10px; padding:10px 14px; font-size:13px; font-family:inherit; color:var(--gray-text); outline:none; background:var(--gray-soft); }
    .cat-search:focus { border-color: var(--purple-mid); background: var(--white); box-shadow:0 0 0 3px var(--purple-subtle); }
    .cat-filter { border:1px solid var(--border-light); border-radius:10px; padding:10px 14px; font-size:13px; font-family:inherit; color:var(--gray-text); background:var(--white); cursor:pointer; outline:none; }
    .cat-filter:focus { border-color: var(--purple-mid); }
    .cat-count { font-size: 12px; font-weight: 600; color: var(--gray-muted); margin-left: auto; letter-spacing: -0.02em; }

    .products-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-bottom:24px; }
    .prod-card { background:var(--white); border:1px solid var(--border-light); border-radius: var(--radius-lg); overflow:hidden; transition: var(--transition); display:flex; flex-direction:column; box-shadow: var(--shadow-sm); }
    .prod-card:hover { border-color: var(--purple-mid); box-shadow: var(--shadow-md); }
    .prod-img { height:148px; background: linear-gradient(165deg, var(--gray-soft) 0%, #ececf0 100%); display:flex; align-items:center; justify-content:center; border-bottom:1px solid var(--border-light); }
    .prod-img svg { opacity:.22; }
    .prod-body { padding:16px; flex:1; display:flex; flex-direction:column; }
    .prod-cat { font-size:11px; font-weight:600; color: var(--purple); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
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
    .btn-add { padding:8px 16px; background:var(--purple); color:#fff; border:none; border-radius:10px; font-size:12px; font-family:inherit; font-weight:600; cursor:pointer; transition: var(--transition); white-space:nowrap; }
    .btn-add:hover { background: var(--purple-dark); }
    .btn-add:disabled { background:#d1d5db; cursor:not-allowed; }

    .modal-overlay-cat{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:400;align-items:center;justify-content:center;-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px)}
    .modal-overlay-cat.active{display:flex}
    .modal-cat{background:var(--white);border-radius: var(--radius-lg);border:1px solid var(--border-light);padding:24px;width:100%;max-width:420px;box-shadow:var(--shadow-lg)}
    .modal-cat h3{font-size:17px;font-weight:700;color:var(--gray-text);margin:0 0 8px}
    .modal-cat .modal-sub{font-size:12px;color:var(--gray-muted);margin-bottom:16px;line-height:1.45}
    .modal-cat label{display:block;font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
    .modal-cat input[type="number"]{width:100%;border:1px solid var(--border-light);border-radius:10px;padding:10px 14px;font-size:14px;font-family:inherit;outline:none}
    .modal-cat input:focus{border-color: var(--purple-mid);box-shadow:0 0 0 3px var(--purple-subtle)}
    .modal-cat-actions{display:flex;gap:10px;margin-top:20px}
    .modal-cat-actions button{flex:1;padding:11px;border-radius:10px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;border:none}
    .btn-modal-cancel{background:#f3f4f6;color:var(--gray-text)}
    .btn-modal-cancel:hover{background:#e5e7eb}
    .btn-modal-ok{background: var(--purple);color:#fff}
    .btn-modal-ok:hover{background: var(--purple-dark)}

    .pagination-mock { display:flex; align-items:center; justify-content:center; gap:6px; padding: 14px; }
    .page-btn { width:36px; height:36px; border:1px solid var(--border-light); border-radius:10px; background:var(--white); font-size:13px; font-family:inherit; color:var(--gray-text); cursor:pointer; display:flex; align-items:center; justify-content:center; transition: var(--transition); }
    .page-btn:hover { background: var(--purple-subtle); color: var(--purple); border-color: var(--purple-mid); }
    .page-btn.active { background: var(--purple); color:#fff; border-color: var(--purple); }

    .cli-pager-wrap { margin-top: 8px; }

    @media(max-width:900px) { .products-grid { grid-template-columns:1fr 1fr; } }
    @media(max-width:600px) { .products-grid { grid-template-columns:1fr; } }

    .catalog-fly-item {
        position: fixed;
        z-index: 600;
        border-radius: 12px;
        pointer-events: none;
        overflow: hidden;
        box-shadow: 0 10px 28px rgba(107,63,160,0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(145deg, #6B3FA0, #9C6DD0);
    }
    .catalog-fly-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>
@endpush

@section('content')
<div class="cli-notice" role="note">Datos de demostración · Integración API pendiente</div>

<div class="cli-surface cli-surface--toolbar">
    <div class="cat-toolbar">
        <input type="search" class="cat-search" id="searchInput" placeholder="Buscar por nombre o código…" autocomplete="off" oninput="filtrar()" aria-label="Buscar en catálogo">
        <select class="cat-filter" id="catFilter" onchange="filtrar()" aria-label="Filtrar por categoría">
            <option value="">Todas las categorías</option>
        </select>
        <span class="cat-count" id="prodCount">—</span>
    </div>
</div>

<div class="products-grid" id="productsGrid"></div>

<div class="cli-surface cli-pager-wrap">
    <div class="pagination-mock" id="pagination"></div>
</div>

<div class="modal-overlay-cat" id="modalQtyOverlay" onclick="if(event.target===this)cerrarModalCantidad()">
    <div class="modal-cat" onclick="event.stopPropagation()">
        <h3 id="modalQtyTitle">Agregar al carrito</h3>
        <p class="modal-sub" id="modalQtyProduct"></p>
        <div>
            <label for="modalQtyInput">Cantidad</label>
            <input type="number" id="modalQtyInput" min="1" value="1">
        </div>
        <div class="modal-cat-actions">
            <button type="button" class="btn-modal-cancel" onclick="cerrarModalCantidad()">Cancelar</button>
            <button type="button" class="btn-modal-ok" onclick="confirmarCantidadPedido()">Confirmar</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    use Illuminate\Support\Facades\File;
    $catalogoImages = [];
    try {
        $catalogoImages = collect(File::files(public_path('Catalogo')))
            ->map(fn ($f) => $f->getFilename())
            ->values()
            ->all();
    } catch (\Throwable $e) {
        $catalogoImages = [];
    }
@endphp
<script>
const CART_STORAGE_KEY = window.SALCOM_CART_STORAGE_KEY || 'salcom_cliente_carrito_v1';

function loadCart() {
    try {
        const raw = localStorage.getItem(CART_STORAGE_KEY);
        if (raw) {
            const data = JSON.parse(raw);
            if (Array.isArray(data)) return data;
        }
    } catch (e) {}
    return [];
}

function saveCart(arr) {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(arr));
}

const productos = [
    // Fuente: PDF "2025-LP MX Wiese Institucional 19sep"
    // Regla aplicada: si hay rangos, usar "1 a 49"; si el precio viene por bloque, aplicar a todos los códigos del bloque.
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO57', nombre:'Aerosol HO WIESE Thaití 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Thaití 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO01', nombre:'Aerosol HO WIESE Hawaian Ginger 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Hawaian Ginger 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO02', nombre:'Aerosol HO WIESE Manzana Canela 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Manzana Canela 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO56', nombre:'Aerosol HO WIESE Frutas Rojas 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Frutas Rojas 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO10', nombre:'Aerosol HO WIESE Lavanda 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Lavanda 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO18', nombre:'Aerosol HO WIESE Amour 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Amour 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO20', nombre:'Aerosol HO WIESE Brisa de los Alpes 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Brisa de los Alpes 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO21', nombre:'Aerosol HO WIESE Paraíso Floral 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Paraíso Floral 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO23', nombre:'Aerosol HO WIESE Sensación Campestre 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Sensación Campestre 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO24', nombre:'Aerosol HO WIESE Cítrico 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Cítrico 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO25', nombre:'Aerosol HO WIESE Aqua 365g/400ml/12.87oz C/12 pzas', desc:'Aerosol HO WIESE Aqua 365g/400ml/12.87oz C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:287.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO52', nombre:'Desinfectante WIESE Cítrico 333g/400ml C/12 pzas', desc:'Desinfectante WIESE Cítrico 333g/400ml C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:511.50, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO65', nombre:'Desinfectante WIESE Fresh Linen 333g/400ml C/12 pzas', desc:'Desinfectante WIESE Fresh Linen 333g/400ml C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:511.50, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO78', nombre:'Eliminador de Olores WIESE Floral 333g/400ml C/12 pzas', desc:'Eliminador de Olores WIESE Floral 333g/400ml C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:511.50, unidad:'', stock:999 },

    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO09', nombre:'Abrillantador de Muebles WIESE Naranja 333g/400ml C/12 pzas', desc:'Abrillantador de Muebles WIESE Naranja 333g/400ml C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:474.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL 400ML', codigo:'NAEHO30', nombre:'Abrillantador de Muebles WIESE Limón 333g/400ml C/12 pzas', desc:'Abrillantador de Muebles WIESE Limón 333g/400ml C/12 pzas', categoria:'aromatizantes-en-aerosol-400ml', precio:474.00, unidad:'', stock:999 },

    { seccion:'AEROSOL 8OZ', codigo:'NAEHO53', nombre:'Abrillantador de Muebles WIESE Naranja 226g/8oz C/12 pzas', desc:'Abrillantador de Muebles WIESE Naranja 226g/8oz C/12 pzas', categoria:'aerosol-8oz', precio:378.00, unidad:'', stock:999 },
    { seccion:'AEROSOL 8OZ', codigo:'NAEHO74', nombre:'Desinfectante WIESE Fresh Linen 226g/8oz C/12 pzas', desc:'Desinfectante WIESE Fresh Linen 226g/8oz C/12 pzas', categoria:'aerosol-8oz', precio:385.00, unidad:'', stock:999 },
    { seccion:'AEROSOL 8OZ', codigo:'NAEHO77', nombre:'Desinfectante WIESE S/A Fresh Linen 226g/8oz C/12 pzas', desc:'Desinfectante WIESE S/A Fresh Linen 226g/8oz C/12 pzas', categoria:'aerosol-8oz', precio:277.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE EN AEROSOL 8OZ', codigo:'NAEHO34', nombre:'Aerosol HO WIESE Lavanda 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Lavanda 226g/8oz C/12 pzas', categoria:'aromatizante-en-aerosol-8oz', precio:239.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL 8OZ', codigo:'NAEHO35', nombre:'Aerosol HO WIESE Paraíso Floral 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Paraíso Floral 226g/8oz C/12 pzas', categoria:'aromatizante-en-aerosol-8oz', precio:239.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL 8OZ', codigo:'NAEHO36', nombre:'Aerosol HO WIESE Manzana Canela 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Manzana Canela 226g/8oz C/12 pzas', categoria:'aromatizante-en-aerosol-8oz', precio:239.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL 8OZ', codigo:'NAEHO41', nombre:'Aerosol HO WIESE Thaití 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Thaití 226g/8oz C/12 pzas', categoria:'aromatizante-en-aerosol-8oz', precio:239.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL 8OZ', codigo:'NAEHO42', nombre:'Aerosol HO WIESE Hawaian Ginger 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Hawaian Ginger 226g/8oz C/12 pzas', categoria:'aromatizante-en-aerosol-8oz', precio:239.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL 8OZ', codigo:'NAEHO43', nombre:'Aerosol HO WIESE Frutas Rojas 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Frutas Rojas 226g/8oz C/12 pzas', categoria:'aromatizante-en-aerosol-8oz', precio:239.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL 8OZ', codigo:'NAEHO55', nombre:'Aerosol HO WIESE Lavanda-Vainilla 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Lavanda-Vainilla 226g/8oz C/12 pzas', categoria:'aromatizante-en-aerosol-8oz', precio:239.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL 8OZ', codigo:'NAEHO59', nombre:'Aerosol HO WIESE Pino Canadiense 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Pino Canadiense 226g/8oz C/12 pzas', categoria:'aromatizante-en-aerosol-8oz', precio:239.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC28', nombre:'Clean Air en Aerosol DC WIESE Lavanda 180g/256ml C/12 pzas', desc:'Clean Air en Aerosol DC WIESE Lavanda 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:551.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC43', nombre:'Desinfectante DC WIESE Fresh Linen 180g/256ml C/12 pzas', desc:'Desinfectante DC WIESE Fresh Linen 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:551.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEMC03', nombre:'Desinfectante MC WIESE Fresh Linen 100ml/80g/2.82oz C/12 pza', desc:'Desinfectante MC WIESE Fresh Linen 100ml/80g/2.82oz C/12 pza', categoria:'aromatizante-en-aerosol', precio:244.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC00', nombre:'Aerosol DC WIESE Eliminador de Olores 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Eliminador de Olores 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC01', nombre:'Aerosol DC WIESE Naranja 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Naranja 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC02', nombre:'Aerosol DC WIESE Ocean Mist 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Ocean Mist 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC03', nombre:'Aerosol DC WIESE Mango 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Mango 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC04', nombre:'Aerosol DC WIESE Vainilla 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Vainilla 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC05', nombre:'Aerosol DC WIESE Piña Colada 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Piña Colada 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC06', nombre:'Aerosol DC WIESE Limón 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Limón 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC07', nombre:'Aerosol DC WIESE Hawaian Ginger 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Hawaian Ginger 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC09', nombre:'Aerosol DC WIESE Cereza 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Cereza 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC10', nombre:'Aerosol DC WIESE Canela 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Canela 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC11', nombre:'Aerosol DC WIESE Frutas Rojas 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Frutas Rojas 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC13', nombre:'Aerosol DC WIESE Bosque 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Bosque 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC25', nombre:'Aerosol DC WIESE Manzana Verde 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Manzana Verde 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC15', nombre:'Aerosol DC WIESE Tahití 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Tahití 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC17', nombre:'Aerosol DC WIESE Lavanda 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Lavanda 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC18', nombre:'Aerosol DC WIESE Manzana Canela 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Manzana Canela 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC20', nombre:'Aerosol DC WIESE Fresa 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Fresa 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC21', nombre:'Aerosol DC WIESE Melón 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Melón 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC26', nombre:'Aerosol DC WIESE Menta 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Menta 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC27', nombre:'Aerosol DC WIESE Brissé 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Brissé 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC41', nombre:'Aerosol DC WIESE (variante) 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE (variante) 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC40', nombre:'Aerosol DC WIESE Red Clover 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Red Clover 180g/256ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN AEROSOL', codigo:'NAEDC12', nombre:'Aerosol HO WIESE Baby Powder 323g/400ml C/12 pzas', desc:'Aerosol HO WIESE Baby Powder 323g/400ml C/12 pzas', categoria:'aromatizante-en-aerosol', precio:451.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'EBRDR05', nombre:'Breeze Matic WIESE Thaití 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit', desc:'Breeze Matic WIESE Thaití 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit', categoria:'aromatizantes-en-aerosol', precio:1993.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'EBRDR06', nombre:'Breeze Matic WIESE Hawaian Ginger 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit', desc:'Breeze Matic WIESE Hawaian Ginger 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit', categoria:'aromatizantes-en-aerosol', precio:1993.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'EBRDR07', nombre:'Breeze Matic WIESE Frutas Rojas 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit', desc:'Breeze Matic WIESE Frutas Rojas 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit', categoria:'aromatizantes-en-aerosol', precio:1993.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'EBRDR08', nombre:'Breeze Matic WIESE Manzana Canela 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit', desc:'Breeze Matic WIESE Manzana Canela 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit', categoria:'aromatizantes-en-aerosol', precio:1993.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'NDIDC03', nombre:'Dispensador Aromatizante Programable WIESE 1pza.', desc:'Dispensador Aromatizante Programable WIESE 1pza.', categoria:'aromatizantes-en-aerosol', precio:254.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'NAEMS00', nombre:'Aerosol MS WIESE Thaití 1 Repuesto/9g C/12 Blister', desc:'Aerosol MS WIESE Thaití 1 Repuesto/9g C/12 Blister', categoria:'aromatizantes-en-aerosol', precio:300.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'NAEMS01', nombre:'Aerosol MS WIESE Hawaian Ginger 1 Repuesto/9g C/12 Blister', desc:'Aerosol MS WIESE Hawaian Ginger 1 Repuesto/9g C/12 Blister', categoria:'aromatizantes-en-aerosol', precio:300.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'NAEMS11', nombre:'Aerosol MS WIESE Hipo Chicle 1 Repuesto/9g C/12 Blister', desc:'Aerosol MS WIESE Hipo Chicle 1 Repuesto/9g C/12 Blister', categoria:'aromatizantes-en-aerosol', precio:300.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'NAEMS03', nombre:'Aerosol MS WIESE Thaití 2 Repuestos/9g c/u C/12 Blister', desc:'Aerosol MS WIESE Thaití 2 Repuestos/9g c/u C/12 Blister', categoria:'aromatizantes-en-aerosol', precio:492.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'NAEMS05', nombre:'Aerosol MS WIESE Hawaian Ginger 2 Repuestos/9g c/u C/12 Blister', desc:'Aerosol MS WIESE Hawaian Ginger 2 Repuestos/9g c/u C/12 Blister', categoria:'aromatizantes-en-aerosol', precio:492.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTES EN AEROSOL', codigo:'NAEMS07', nombre:'Aerosol MS WIESE Hipo Chicle 2 Repuestos/9g c/u C/12 Blister', desc:'Aerosol MS WIESE Hipo Chicle 2 Repuestos/9g c/u C/12 Blister', categoria:'aromatizantes-en-aerosol', precio:492.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NARAU09', nombre:'Aromatizante Auto WIESE Amour 7ml C/6 pzas', desc:'Aromatizante Auto WIESE Amour 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:291.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NARAU10', nombre:'Aromatizante Auto WIESE Brisse 7ml C/6 pzas', desc:'Aromatizante Auto WIESE Brisse 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:291.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NARAU11', nombre:'Aromatizante Auto WIESE Atraktion 7ml C/6 pzas', desc:'Aromatizante Auto WIESE Atraktion 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:291.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NARAU13', nombre:'Aromatizante Auto WIESE Chicle León 7ml C/6 pzas', desc:'Aromatizante Auto WIESE Chicle León 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:291.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NARAU12', nombre:'Aromatizante Auto WIESE Auto Nuevo 7ml C/6 pzas', desc:'Aromatizante Auto WIESE Auto Nuevo 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:291.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NREAU14', nombre:'Repuesto Auto WIESE Amour 7ml C/6 pzas', desc:'Repuesto Auto WIESE Amour 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:220.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NREAU12', nombre:'Repuesto Auto WIESE Brisse 7ml C/6 pzas', desc:'Repuesto Auto WIESE Brisse 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:220.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NREAU13', nombre:'Repuesto Auto WIESE Atraktion 7ml C/6 pzas', desc:'Repuesto Auto WIESE Atraktion 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:220.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NREAU15', nombre:'Repuesto Auto WIESE Chicle León 7ml C/6 pzas', desc:'Repuesto Auto WIESE Chicle León 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:220.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA AUTO', codigo:'NREAU16', nombre:'Repuesto Auto WIESE Auto Nuevo 7ml C/6 pzas', desc:'Repuesto Auto WIESE Auto Nuevo 7ml C/6 pzas', categoria:'aromatizante-para-auto', precio:220.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE EN GEL', codigo:'NARGE06', nombre:'Gel Aromatizante WIESE Cítrico 70g C/24 pzas', desc:'Gel Aromatizante WIESE Cítrico 70g C/24 pzas', categoria:'aromatizante-en-gel', precio:246.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN GEL', codigo:'NARGE07', nombre:'Gel Aromatizante WIESE Floral 70g C/24 pzas', desc:'Gel Aromatizante WIESE Floral 70g C/24 pzas', categoria:'aromatizante-en-gel', precio:246.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN GEL', codigo:'NARGE08', nombre:'Gel Aromatizante WIESE Jazmín 70g C/24 pzas', desc:'Gel Aromatizante WIESE Jazmín 70g C/24 pzas', categoria:'aromatizante-en-gel', precio:246.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN GEL', codigo:'NARGE10', nombre:'Gel Aromatizante WIESE Lavanda 70g C/24 pzas', desc:'Gel Aromatizante WIESE Lavanda 70g C/24 pzas', categoria:'aromatizante-en-gel', precio:246.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN GEL', codigo:'NARGE11', nombre:'Gel Aromatizante WIESE Atraktion 70g C/24 pzas', desc:'Gel Aromatizante WIESE Atraktion 70g C/24 pzas', categoria:'aromatizante-en-gel', precio:246.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE EN GEL', codigo:'NARGE16', nombre:'Gel Aromatizante WIESE Auto Nuevo 70g C/24 pzas', desc:'Gel Aromatizante WIESE Auto Nuevo 70g C/24 pzas', categoria:'aromatizante-en-gel', precio:246.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE GEL CONO', codigo:'NARCG00', nombre:'Cono de Gel Aromatizante WIESE Cítrico 170g / 12 pzas', desc:'Cono de Gel Aromatizante WIESE Cítrico 170g / 12 pzas', categoria:'aromatizante-gel-cono', precio:266.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE GEL CONO', codigo:'NARCG07', nombre:'Cono de Gel Aromatizante WIESE Manzana Canela 170g / 12 pzas', desc:'Cono de Gel Aromatizante WIESE Manzana Canela 170g / 12 pzas', categoria:'aromatizante-gel-cono', precio:266.00, unidad:'', stock:999 },

    { seccion:'DIFUSOR ELÉCTRICO', codigo:'NDIEL00', nombre:'Difusor Eléctrico WIESE Brissé 21ml C/12 pzas', desc:'Difusor Eléctrico WIESE Brissé 21ml C/12 pzas', categoria:'difusor-electrico', precio:664.00, unidad:'', stock:999 },
    { seccion:'DIFUSOR ELÉCTRICO', codigo:'NDIEL02', nombre:'Difusor Eléctrico WIESE Lavanda Manzanilla 21ml C/12 pzas', desc:'Difusor Eléctrico WIESE Lavanda Manzanilla 21ml C/12 pzas', categoria:'difusor-electrico', precio:664.00, unidad:'', stock:999 },
    { seccion:'DIFUSOR ELÉCTRICO', codigo:'NDIEL03', nombre:'Difusor Eléctrico WIESE Manzana Canela 21ml C/12 pzas', desc:'Difusor Eléctrico WIESE Manzana Canela 21ml C/12 pzas', categoria:'difusor-electrico', precio:664.00, unidad:'', stock:999 },

    { seccion:'REPUESTO DIFUSOR ELÉCTRICO', codigo:'NDIER07', nombre:'Repuesto WIESE Brissé C/1 repuesto/21ml C/12 Blister', desc:'Repuesto WIESE Brissé C/1 repuesto/21ml C/12 Blister', categoria:'repuesto-difusor-electrico', precio:450.00, unidad:'', stock:999 },
    { seccion:'REPUESTO DIFUSOR ELÉCTRICO', codigo:'NDIER08', nombre:'Repuesto WIESE Lavanda Manzanilla C/1 repuesto/21ml C/12 Blister', desc:'Repuesto WIESE Lavanda Manzanilla C/1 repuesto/21ml C/12 Blister', categoria:'repuesto-difusor-electrico', precio:450.00, unidad:'', stock:999 },
    { seccion:'REPUESTO DIFUSOR ELÉCTRICO', codigo:'NDIER09', nombre:'Repuesto WIESE Manzana Canela C/1 repuesto/21ml C/12 Blister', desc:'Repuesto WIESE Manzana Canela C/1 repuesto/21ml C/12 Blister', categoria:'repuesto-difusor-electrico', precio:450.00, unidad:'', stock:999 },
    { seccion:'REPUESTO DIFUSOR ELÉCTRICO', codigo:'NDIER10', nombre:'Repuesto WIESE Brissé C/2 repuestos/21ml C/12 Blister', desc:'Repuesto WIESE Brissé C/2 repuestos/21ml C/12 Blister', categoria:'repuesto-difusor-electrico', precio:694.00, unidad:'', stock:999 },
    { seccion:'REPUESTO DIFUSOR ELÉCTRICO', codigo:'NDIER11', nombre:'Repuesto WIESE Lavanda Manzanilla C/2 repuestos/21ml C/12 Blister', desc:'Repuesto WIESE Lavanda Manzanilla C/2 repuestos/21ml C/12 Blister', categoria:'repuesto-difusor-electrico', precio:694.00, unidad:'', stock:999 },
    { seccion:'REPUESTO DIFUSOR ELÉCTRICO', codigo:'NDIER12', nombre:'Repuesto WIESE Manzana Canela C/2 repuestos/21ml C/12 Blister', desc:'Repuesto WIESE Manzana Canela C/2 repuestos/21ml C/12 Blister', categoria:'repuesto-difusor-electrico', precio:694.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NDILG04', nombre:'Dispensador Líquido Goteador 1pza', desc:'Dispensador Líquido Goteador 1pza', categoria:'aromatizante-liquido', precio:363.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG10', nombre:'Líquido Goteador WIESE Lavanda 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Lavanda 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG11', nombre:'Líquido Goteador WIESE Manzana Menta 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Manzana Menta 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG12', nombre:'Líquido Goteador WIESE Brissé 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Brissé 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG13', nombre:'Líquido Goteador WIESE Mango 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Mango 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG18', nombre:'Líquido Goteador WIESE Cítrus 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Cítrus 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG19', nombre:'Líquido Goteador WIESE Red Clover 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Red Clover 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NMANG02', nombre:'Manguera para Dispensador Líquido Gotero 1 mt.', desc:'Manguera para Dispensador Líquido Gotero 1 mt.', categoria:'aromatizante-liquido', precio:40.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG48', nombre:'Líquido Goteador WIESE Lavanda 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Lavanda 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG49', nombre:'Líquido Goteador WIESE Manzana Menta 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Manzana Menta 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG50', nombre:'Líquido Goteador WIESE Brissé 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Brissé 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG52', nombre:'Líquido Goteador WIESE Mango 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Mango 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG51', nombre:'Líquido Goteador WIESE Cítrus 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Cítrus 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE LÍQUIDO', codigo:'NLILG53', nombre:'Líquido Goteador WIESE Red Clover 270g/9.52oz C/6 pzas', desc:'Líquido Goteador WIESE Red Clover 270g/9.52oz C/6 pzas', categoria:'aromatizante-liquido', precio:538.00, unidad:'', stock:999 },

    { seccion:'PRODUCTOS NON-PARA', codigo:'NPCCL06', nombre:'Pastilla Cloro WIESE 35g C/12 pzas', desc:'Pastilla Cloro WIESE 35g C/12 pzas', categoria:'productos-non-para', precio:223.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOCA06', nombre:'Canastilla WIESE Lavanda 35g C/12 pzas', desc:'Canastilla WIESE Lavanda 35g C/12 pzas', categoria:'productos-non-para', precio:136.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOCA07', nombre:'Canastilla WIESE Naranja 35g C/12 pzas', desc:'Canastilla WIESE Naranja 35g C/12 pzas', categoria:'productos-non-para', precio:136.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOCA12', nombre:'Canastilla WIESE Azul Activo de Pino 35g C/12 pzas', desc:'Canastilla WIESE Azul Activo de Pino 35g C/12 pzas', categoria:'productos-non-para', precio:136.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOCG07', nombre:'Canastilla Gel WIESE Naranja 35g / 12 Blisters', desc:'Canastilla Gel WIESE Naranja 35g / 12 Blisters', categoria:'productos-non-para', precio:198.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOCG08', nombre:'Canastilla Gel WIESE Bosque 35g / 12 Blisters', desc:'Canastilla Gel WIESE Bosque 35g / 12 Blisters', categoria:'productos-non-para', precio:198.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOCG09', nombre:'Canastilla Gel WIESE Azul Naranja 35g / 12 Blisters', desc:'Canastilla Gel WIESE Azul Naranja 35g / 12 Blisters', categoria:'productos-non-para', precio:198.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NTACP02', nombre:'Tapete con Pastilla NO PDCB WIESE Azul 4oz / 12 pzas', desc:'Tapete con Pastilla NO PDCB WIESE Azul 4oz / 12 pzas', categoria:'productos-non-para', precio:201.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NTACP03', nombre:'Tapete con Pastilla NO PDCB WIESE Cherry Rosa 4oz / 12 pzas', desc:'Tapete con Pastilla NO PDCB WIESE Cherry Rosa 4oz / 12 pzas', categoria:'productos-non-para', precio:201.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOCR01', nombre:'Cristales NON-PDCB WIESE Lavanda Morado 750gr/1.65lb/4 Bote', desc:'Cristales NON-PDCB WIESE Lavanda Morado 750gr/1.65lb/4 Bote', categoria:'productos-non-para', precio:651.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOCR02', nombre:'Cristales NON-PDCB WIESE Menta 750gr/1.65lb/4 Bote', desc:'Cristales NON-PDCB WIESE Menta 750gr/1.65lb/4 Bote', categoria:'productos-non-para', precio:774.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOMI00', nombre:'Pastilla Mingitorio NO PDCB WIESE Azul 85gr C/50 pzas', desc:'Pastilla Mingitorio NO PDCB WIESE Azul 85gr C/50 pzas', categoria:'productos-non-para', precio:347.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOMI01', nombre:'Pastilla Mingitorio NO PDCB WIESE Rosa 85gr C/50 pzas', desc:'Pastilla Mingitorio NO PDCB WIESE Rosa 85gr C/50 pzas', categoria:'productos-non-para', precio:347.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOPA17', nombre:'Pastilla Azul WIESE Pino 48g C/12 Blister Burbuja', desc:'Pastilla Azul WIESE Pino 48g C/12 Blister Burbuja', categoria:'productos-non-para', precio:138.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOPA18', nombre:'Pastilla Azul WIESE Pino 96g C/12 Blister Burbuja', desc:'Pastilla Azul WIESE Pino 96g C/12 Blister Burbuja', categoria:'productos-non-para', precio:220.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOPA19', nombre:'Pastilla Azul WIESE Pino 144g C/12 Blister Burbuja', desc:'Pastilla Azul WIESE Pino 144g C/12 Blister Burbuja', categoria:'productos-non-para', precio:312.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOPA00', nombre:'Pastilla Azul WIESE 48g C/12 pzas', desc:'Pastilla Azul WIESE 48g C/12 pzas', categoria:'productos-non-para', precio:101.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS NON-PARA', codigo:'NNOPA02', nombre:'Pastilla Azul Low-Cost WIESE 144g C/12 pzas', desc:'Pastilla Azul Low-Cost WIESE 144g C/12 pzas', categoria:'productos-non-para', precio:110.00, unidad:'', stock:999 },

    { seccion:'PRODUCTOS PARA', codigo:'EPARE08', nombre:'Pastilla Flor WIESE Lavanda 40g C/50 pzas', desc:'Pastilla Flor WIESE Lavanda 40g C/50 pzas', categoria:'productos-para', precio:271.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'EPARE09', nombre:'Pastilla Flor WIESE Floral 40g C/50 pzas', desc:'Pastilla Flor WIESE Floral 40g C/50 pzas', categoria:'productos-para', precio:271.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPARE00', nombre:'Pastilla Redonda WIESE Surtido 60g C/50 pzas', desc:'Pastilla Redonda WIESE Surtido 60g C/50 pzas', categoria:'productos-para', precio:359.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPARE10', nombre:'Pastilla Redonda WIESE Surtido 70g C/50 pzas', desc:'Pastilla Redonda WIESE Surtido 70g C/50 pzas', categoria:'productos-para', precio:403.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPARE20', nombre:'Pastilla Redonda WIESE Surtido 80g C/50 pzas', desc:'Pastilla Redonda WIESE Surtido 80g C/50 pzas', categoria:'productos-para', precio:432.00, unidad:'', stock:999 },

    { seccion:'PRODUCTOS PARA', codigo:'NPAAL00', nombre:'Pastilla Alambre WIESE Surtido 60g C/50 pzas', desc:'Pastilla Alambre WIESE Surtido 60g C/50 pzas', categoria:'productos-para', precio:359.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPAAL10', nombre:'Pastilla Alambre WIESE Surtido 70g C/50 pzas', desc:'Pastilla Alambre WIESE Surtido 70g C/50 pzas', categoria:'productos-para', precio:403.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPAAL20', nombre:'Pastilla Alambre WIESE Surtido 80g C/50 pzas', desc:'Pastilla Alambre WIESE Surtido 80g C/50 pzas', categoria:'productos-para', precio:432.00, unidad:'', stock:999 },

    { seccion:'PRODUCTOS PARA', codigo:'NPABA11', nombre:'Pastilla Barra WIESE Cereza 300g C/12 pzas', desc:'Pastilla Barra WIESE Cereza 300g C/12 pzas', categoria:'productos-para', precio:435.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPABA12', nombre:'Pastilla Barra WIESE Lavanda 300g C/12 pzas', desc:'Pastilla Barra WIESE Lavanda 300g C/12 pzas', categoria:'productos-para', precio:435.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPABA13', nombre:'Pastilla Barra WIESE Manzana Verde 300g C/12 pzas', desc:'Pastilla Barra WIESE Manzana Verde 300g C/12 pzas', categoria:'productos-para', precio:435.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPABA30', nombre:'Pastilla Barra WIESE Lavanda 450g C/9 pzas', desc:'Pastilla Barra WIESE Lavanda 450g C/9 pzas', categoria:'productos-para', precio:475.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPABA32', nombre:'Pastilla Barra WIESE Cereza 450g C/9 pzas', desc:'Pastilla Barra WIESE Cereza 450g C/9 pzas', categoria:'productos-para', precio:475.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPABA34', nombre:'Pastilla Barra WIESE Manzana Verde 450g C/9 pzas', desc:'Pastilla Barra WIESE Manzana Verde 450g C/9 pzas', categoria:'productos-para', precio:475.00, unidad:'', stock:999 },

    { seccion:'PRODUCTOS PARA', codigo:'NPACR04', nombre:'Moth Balls WIESE Surtido 250g C/30 Bolsas', desc:'Moth Balls WIESE Surtido 250g C/30 Bolsas', categoria:'productos-para', precio:949.00, unidad:'', stock:999 },

    { seccion:'BICARBONATO', codigo:'NNOPA31', nombre:'Pastilla Bicarbonato WIESE 90g Caja con 12 Blisters', desc:'Pastilla Bicarbonato WIESE 90g Caja con 12 Blisters', categoria:'bicarbonato', precio:231.00, unidad:'', stock:999 },
    { seccion:'BICARBONATO', codigo:'NTACP06', nombre:'Tapete con pastilla Bicarbonato WIESE 4oz Caja con 12 Piezas', desc:'Tapete con pastilla Bicarbonato WIESE 4oz Caja con 12 Piezas', categoria:'bicarbonato', precio:270.00, unidad:'', stock:999 },

    { seccion:'PRODUCTOS PARA', codigo:'NTACP00', nombre:'Tapete con pastilla PDCB WIESE Lavanda Azul 85g C/12 pzas', desc:'Tapete con pastilla PDCB WIESE Lavanda Azul 85g C/12 pzas', categoria:'productos-para', precio:202.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NTACP01', nombre:'Tapete con pastilla PDCB WIESE Cherry Rosa 85g C/12 pzas', desc:'Tapete con pastilla PDCB WIESE Cherry Rosa 85g C/12 pzas', categoria:'productos-para', precio:202.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPAMI01', nombre:'Pastilla Mingitorio PDCB WIESE Cereza 60g C/50 pzas', desc:'Pastilla Mingitorio PDCB WIESE Cereza 60g C/50 pzas', categoria:'productos-para', precio:366.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPAMI04', nombre:'Pastilla Mingitorio PDCB WIESE Lavanda 85g C/50 pzas', desc:'Pastilla Mingitorio PDCB WIESE Lavanda 85g C/50 pzas', categoria:'productos-para', precio:487.00, unidad:'', stock:999 },
    { seccion:'PRODUCTOS PARA', codigo:'NPAMI05', nombre:'Pastilla Mingitorio PDCB WIESE Cereza 85g C/50 pzas', desc:'Pastilla Mingitorio PDCB WIESE Cereza 85g C/50 pzas', categoria:'productos-para', precio:487.00, unidad:'', stock:999 },

    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS25', nombre:'Tapete Anti-Salpicaduras MT WIESE Menta-Azul C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Menta-Azul C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS26', nombre:'Tapete Anti-Salpicaduras MT WIESE Manzana-Canela C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Manzana-Canela C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS27', nombre:'Tapete Anti-Salpicaduras MT WIESE Cítrus Caja C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Cítrus Caja C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS28', nombre:'Tapete Anti-Salpicaduras MT WIESE Brissé Caja C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Brissé Caja C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS29', nombre:'Tapete Anti-Salpicaduras MT WIESE Pepino-Melón C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Pepino-Melón C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS30', nombre:'Tapete Anti-Salpicaduras MT WIESE Mango-Naranja C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Mango-Naranja C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS31', nombre:'Tapete Anti-Salpicaduras MT WIESE Lavanda-Lila C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Lavanda-Lila C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS32', nombre:'Tapete Anti-Salpicaduras MT WIESE Menta-Verde C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Menta-Verde C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS33', nombre:'Tapete Anti-Salpicaduras MT WIESE Menta-Transparente C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Menta-Transparente C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAAS34', nombre:'Tapete Anti-Salpicaduras MT WIESE Red Clover-Rojo C/10 pzas', desc:'Tapete Anti-Salpicaduras MT WIESE Red Clover-Rojo C/10 pzas', categoria:'tapetes-urinal-screens', precio:451.00, unidad:'', stock:999 },

    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTALI00', nombre:'Tapete Liso WIESE Chicle-Azul C/12 pzas', desc:'Tapete Liso WIESE Chicle-Azul C/12 pzas', categoria:'tapetes-urinal-screens', precio:168.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTALI01', nombre:'Tapete Liso WIESE Cherry-Rojo C/12 pzas', desc:'Tapete Liso WIESE Cherry-Rojo C/12 pzas', categoria:'tapetes-urinal-screens', precio:168.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTALI02', nombre:'Tapete Liso WIESE Menta-Blanco C/12 pzas', desc:'Tapete Liso WIESE Menta-Blanco C/12 pzas', categoria:'tapetes-urinal-screens', precio:168.00, unidad:'', stock:999 },

    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAST02', nombre:'Tapete Storm WIESE Mango-Naranja C/12 pzas', desc:'Tapete Storm WIESE Mango-Naranja C/12 pzas', categoria:'tapetes-urinal-screens', precio:235.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAST19', nombre:'Tapete Storm WIESE Pepino/Melon-Amarillo C/12 pzas', desc:'Tapete Storm WIESE Pepino/Melon-Amarillo C/12 pzas', categoria:'tapetes-urinal-screens', precio:235.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAST03', nombre:'Tapete Storm WIESE Lavanda-Lila C/12 pzas', desc:'Tapete Storm WIESE Lavanda-Lila C/12 pzas', categoria:'tapetes-urinal-screens', precio:235.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAST12', nombre:'Tapete Storm WIESE Menta-Azul C/12 pzas', desc:'Tapete Storm WIESE Menta-Azul C/12 pzas', categoria:'tapetes-urinal-screens', precio:235.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAST13', nombre:'Tapete Storm WIESE Manzana/Canela-Verde C/12 pzas', desc:'Tapete Storm WIESE Manzana/Canela-Verde C/12 pzas', categoria:'tapetes-urinal-screens', precio:235.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAST14', nombre:'Tapete Storm WIESE Brissé-Turquesa C/12 pzas', desc:'Tapete Storm WIESE Brissé-Turquesa C/12 pzas', categoria:'tapetes-urinal-screens', precio:235.00, unidad:'', stock:999 },
    { seccion:'TAPETES / URINAL SCREENS', codigo:'NTAST15', nombre:'Tapete Storm WIESE Cítrus-Verde C/12 pzas', desc:'Tapete Storm WIESE Cítrus-Verde C/12 pzas', categoria:'tapetes-urinal-screens', precio:235.00, unidad:'', stock:999 },

    { seccion:'LO NUEVO', codigo:'NTALI03', nombre:'Tapete Liso C/Portería WIESE Chicle Verde C/12 Pz.', desc:'Tapete Liso C/Portería WIESE Chicle Verde C/12 Pz.', categoria:'lo-nuevo', precio:318.00, unidad:'', stock:999 },
    { seccion:'LO NUEVO', codigo:'NTAAS14', nombre:'Tapete Anti-Salpicaduras C/Portería WIESE Citrus Verde C/8 Pz.', desc:'Tapete Anti-Salpicaduras C/Portería WIESE Citrus Verde C/8 Pz.', categoria:'lo-nuevo', precio:312.00, unidad:'', stock:999 },

    { seccion:'CLIP ON', codigo:'NARCO02', nombre:'Aromatizante Clip On WIESE Mango-Naranja C/12 pzas', desc:'Aromatizante Clip On WIESE Mango-Naranja C/12 pzas', categoria:'clip-on', precio:236.00, unidad:'', stock:999 },
    { seccion:'CLIP ON', codigo:'NARCO03', nombre:'Aromatizante Clip On WIESE Lavanda-Lila C/12 pzas', desc:'Aromatizante Clip On WIESE Lavanda-Lila C/12 pzas', categoria:'clip-on', precio:236.00, unidad:'', stock:999 },
    { seccion:'CLIP ON', codigo:'NARCO04', nombre:'Aromatizante Clip On WIESE Pepino/Melón-Amarillo C/12 pzas', desc:'Aromatizante Clip On WIESE Pepino/Melón-Amarillo C/12 pzas', categoria:'clip-on', precio:236.00, unidad:'', stock:999 },
    { seccion:'CLIP ON', codigo:'NARCO05', nombre:'Aromatizante Clip On WIESE Menta-Azul C/12 pzas', desc:'Aromatizante Clip On WIESE Menta-Azul C/12 pzas', categoria:'clip-on', precio:236.00, unidad:'', stock:999 },
    { seccion:'CLIP ON', codigo:'NARCO06', nombre:'Aromatizante Clip On WIESE Manzana/Canela-Verde C/12 pzas', desc:'Aromatizante Clip On WIESE Manzana/Canela-Verde C/12 pzas', categoria:'clip-on', precio:236.00, unidad:'', stock:999 },
    { seccion:'CLIP ON', codigo:'NARCO07', nombre:'Aromatizante Clip On WIESE Brissé-Turquesa C/12 pzas', desc:'Aromatizante Clip On WIESE Brissé-Turquesa C/12 pzas', categoria:'clip-on', precio:236.00, unidad:'', stock:999 },
    { seccion:'CLIP ON', codigo:'NARCO08', nombre:'Aromatizante Clip On WIESE Cítrus-Verde C/12 pzas', desc:'Aromatizante Clip On WIESE Cítrus-Verde C/12 pzas', categoria:'clip-on', precio:236.00, unidad:'', stock:999 },

    { seccion:'HANG AIR', codigo:'NARHA00', nombre:'Aromatizante Hang Air WIESE Menta-Azul C/12 pzas', desc:'Aromatizante Hang Air WIESE Menta-Azul C/12 pzas', categoria:'hang-air', precio:222.00, unidad:'', stock:999 },
    { seccion:'HANG AIR', codigo:'NARHA01', nombre:'Aromatizante Hang Air WIESE Manzana/Canela-Verde C/12 pzas', desc:'Aromatizante Hang Air WIESE Manzana/Canela-Verde C/12 pzas', categoria:'hang-air', precio:222.00, unidad:'', stock:999 },
    { seccion:'HANG AIR', codigo:'NARHA02', nombre:'Aromatizante Hang Air WIESE Mango-Naranja C/12 pzas', desc:'Aromatizante Hang Air WIESE Mango-Naranja C/12 pzas', categoria:'hang-air', precio:222.00, unidad:'', stock:999 },
    { seccion:'HANG AIR', codigo:'NARHA03', nombre:'Aromatizante Hang Air WIESE Lavanda-Lila C/12 pzas', desc:'Aromatizante Hang Air WIESE Lavanda-Lila C/12 pzas', categoria:'hang-air', precio:222.00, unidad:'', stock:999 },
    { seccion:'HANG AIR', codigo:'NARHA04', nombre:'Aromatizante Hang Air WIESE Brissé-Turquesa C/12 pzas', desc:'Aromatizante Hang Air WIESE Brissé-Turquesa C/12 pzas', categoria:'hang-air', precio:222.00, unidad:'', stock:999 },
    { seccion:'HANG AIR', codigo:'NARHA05', nombre:'Aromatizante Hang Air WIESE Cítrus-Verde Caja C/12 pzas', desc:'Aromatizante Hang Air WIESE Cítrus-Verde Caja C/12 pzas', categoria:'hang-air', precio:222.00, unidad:'', stock:999 },
    { seccion:'HANG AIR', codigo:'NARHA06', nombre:'Aromatizante Hang Air WIESE Pepino/Melón-Amarillo C/12 pzas', desc:'Aromatizante Hang Air WIESE Pepino/Melón-Amarillo C/12 pzas', categoria:'hang-air', precio:222.00, unidad:'', stock:999 },

    { seccion:'LINEA PREMIUM', codigo:'NAEDC49', nombre:'Aerosol DC WIESE Premium Lavanda 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Premium Lavanda 180g/256ml C/12 pzas', categoria:'linea-premium', precio:473.00, unidad:'', stock:999 },
    { seccion:'LINEA PREMIUM', codigo:'NAEDC45', nombre:'Aerosol DC WIESE Premium Agua de Coco y Lima 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Premium Agua de Coco y Lima 180g/256ml C/12 pzas', categoria:'linea-premium', precio:473.00, unidad:'', stock:999 },
    { seccion:'LINEA PREMIUM', codigo:'NAEDC44', nombre:'Aerosol DC WIESE Premium Mandarina y Sándalo 180g/256ml C/12 pzas', desc:'Aerosol DC WIESE Premium Mandarina y Sándalo 180g/256ml C/12 pzas', categoria:'linea-premium', precio:473.00, unidad:'', stock:999 },

    { seccion:'LINEA PREMIUM', codigo:'NAEHO89', nombre:'Aerosol HO WIESE Premium Manzana Canela 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Premium Manzana Canela 226g/8oz C/12 pzas', categoria:'linea-premium', precio:299.00, unidad:'', stock:999 },
    { seccion:'LINEA PREMIUM', codigo:'NAEHO90', nombre:'Aerosol HO WIESE Premium Lavanda 226g/8oz C/12 pzas', desc:'Aerosol HO WIESE Premium Lavanda 226g/8oz C/12 pzas', categoria:'linea-premium', precio:299.00, unidad:'', stock:999 },
    { seccion:'LINEA PREMIUM', codigo:'NAEHO88', nombre:'Aerosol DC WIESE Redlicius 226g/8oz C/12 pzas', desc:'Aerosol DC WIESE Redlicius 226g/8oz C/12 pzas', categoria:'linea-premium', precio:299.00, unidad:'', stock:999 },
    { seccion:'LINEA PREMIUM', codigo:'NAEHO93', nombre:'Aerosol DC WIESE Mediterranean Essences 226g/8oz C/12 pzas', desc:'Aerosol DC WIESE Mediterranean Essences 226g/8oz C/12 pzas', categoria:'linea-premium', precio:299.00, unidad:'', stock:999 },
    { seccion:'LINEA PREMIUM', codigo:'NAEHO86', nombre:'Aerosol DC WIESE Premium Mandarina y Sándalo 226g/8oz C/12 pzas', desc:'Aerosol DC WIESE Premium Mandarina y Sándalo 226g/8oz C/12 pzas', categoria:'linea-premium', precio:299.00, unidad:'', stock:999 },
    { seccion:'LINEA PREMIUM', codigo:'NAEHO85', nombre:'Aerosol DC WIESE Agua de Coco y Lima 226g/8oz C/12 pzas', desc:'Aerosol DC WIESE Agua de Coco y Lima 226g/8oz C/12 pzas', categoria:'linea-premium', precio:299.00, unidad:'', stock:999 },

    { seccion:'LÍQUIDO LIMPIADOR PARA SANITARIOS', codigo:'NLILS02', nombre:'WIESE Limpiador Líquido para Sanitario Menta 750 ml C/12 pz.', desc:'WIESE Limpiador Líquido para Sanitario Menta 750 ml C/12 pz.', categoria:'liquido-limpiador-para-sanitarios', precio:330.00, unidad:'', stock:999 },

    { seccion:'AROMATIZANTE PARA CLOSET', codigo:'NPAVP00', nombre:'Vestido PDCB WIESE Lavanda (2) 85g/15 pzas', desc:'Vestido PDCB WIESE Lavanda (2) 85g/15 pzas', categoria:'aromatizante-para-closet', precio:478.00, unidad:'', stock:999 },
    { seccion:'AROMATIZANTE PARA CLOSET', codigo:'NPAPP00', nombre:'Camiseta PDCB WIESE Lavanda (2) 85g/16 pzas', desc:'Camiseta PDCB WIESE Lavanda (2) 85g/16 pzas', categoria:'aromatizante-para-closet', precio:353.00, unidad:'', stock:999 },
];

let pendingPedidoCodigo = null;

function agregarPedido(codigo) {
    const p = productos.find(x => x.codigo === codigo);
    if (!p || p.stock === 0) return;
    pendingPedidoCodigo = codigo;
    document.getElementById('modalQtyTitle').textContent = 'Agregar al carrito';
    document.getElementById('modalQtyProduct').textContent = p.nombre + ' · ' + p.codigo + ' — $' + p.precio.toLocaleString('es-MX', {minimumFractionDigits: 2}) + ' c/u';
    const inp = document.getElementById('modalQtyInput');
    inp.value = 1;
    inp.max = p.stock > 0 ? p.stock : '';
    document.getElementById('modalQtyOverlay').classList.add('active');
    setTimeout(() => inp.focus(), 50);
}

function cerrarModalCantidad() {
    pendingPedidoCodigo = null;
    document.getElementById('modalQtyOverlay').classList.remove('active');
}

function getPedidosFlyTargetEl() {
    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
        const q = document.getElementById('navPedidosQuick');
        if (q) return q;
    }
    return document.getElementById('sbLinkPedidos');
}

function flyProductToPedidos(fromRect, imgSrc) {
    const target = getPedidosFlyTargetEl();
    if (!target || !fromRect || fromRect.width < 1) {
        return Promise.resolve();
    }
    const to = target.getBoundingClientRect();
    const size = 52;
    const el = document.createElement('div');
    el.className = 'catalog-fly-item';
    el.setAttribute('aria-hidden', 'true');
    if (imgSrc) {
        const img = document.createElement('img');
        img.src = imgSrc;
        img.alt = '';
        el.appendChild(img);
    } else {
        el.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
    }
    document.body.appendChild(el);
    const x0 = fromRect.left + fromRect.width / 2 - size / 2;
    const y0 = fromRect.top + fromRect.height / 2 - size / 2;
    el.style.left = x0 + 'px';
    el.style.top = y0 + 'px';
    el.style.width = size + 'px';
    el.style.height = size + 'px';
    const cx0 = fromRect.left + fromRect.width / 2;
    const cy0 = fromRect.top + fromRect.height / 2;
    const dx = to.left + to.width / 2 - cx0;
    const dy = to.top + to.height / 2 - cy0;
    const keyframes = [
        { transform: 'translate(0,0) scale(1)', opacity: 1 },
        { transform: 'translate(' + (dx * 0.5) + 'px,' + (dy * 0.5 - 32) + 'px) scale(0.9)', opacity: 0.98, offset: 0.42 },
        { transform: 'translate(' + dx + 'px,' + dy + 'px) scale(0.32)', opacity: 0.12 },
    ];
    const opts = { duration: 750, easing: 'cubic-bezier(0.2, 0.85, 0.35, 1)' };
    if (el.animate) {
        return el.animate(keyframes, opts).finished.then(function () { el.remove(); }).catch(function () { try { el.remove(); } catch (e) {} });
    }
    el.remove();
    return Promise.resolve();
}

function confirmarCantidadPedido() {
    if (!pendingPedidoCodigo) return;
    const p = productos.find(x => x.codigo === pendingPedidoCodigo);
    if (!p) { cerrarModalCantidad(); return; }
    let qty = parseInt(document.getElementById('modalQtyInput').value, 10);
    if (!Number.isFinite(qty) || qty < 1) qty = 1;
    if (p.stock > 0 && qty > p.stock) qty = p.stock;

    const codigoRef = pendingPedidoCodigo;
    const card = document.querySelector('.prod-card[data-codigo="' + codigoRef + '"]');
    let fromRect = null;
    let imgSrc = null;
    if (card) {
        const wrap = card.querySelector('.prod-img');
        const img = wrap ? wrap.querySelector('img') : null;
        if (wrap) fromRect = wrap.getBoundingClientRect();
        if (img && img.src) imgSrc = img.src;
    }

    const cart = loadCart();
    const existing = cart.find(x => x.codigo === p.codigo);
    if (existing) {
        let nextQty = existing.cantidad + qty;
        if (p.stock > 0 && nextQty > p.stock) nextQty = p.stock;
        existing.cantidad = nextQty;
    } else {
        let c = qty;
        if (p.stock > 0 && c > p.stock) c = p.stock;
        cart.push({
            codigo: p.codigo,
            nombre: p.nombre,
            precioUnit: p.precio,
            cantidad: c,
        });
    }
    saveCart(cart);
    cerrarModalCantidad();

    const syncBadge = function () {
        if (typeof window.salcomSyncPedidosNavBadge === 'function') window.salcomSyncPedidosNavBadge();
    };

    if (fromRect && fromRect.width >= 1) {
        flyProductToPedidos(fromRect, imgSrc).then(syncBadge).catch(syncBadge);
    } else {
        syncBadge();
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('modalQtyOverlay').classList.contains('active')) cerrarModalCantidad();
});

const catalogoImages = @json($catalogoImages);

const categorias = Array.from(new Map(productos.map(p => [p.categoria, p.seccion])).entries())
    .map(([slug, label]) => ({ slug, label }))
    .sort((a, b) => a.label.localeCompare(b.label, 'es'));

(() => {
    const sel = document.getElementById('catFilter');
    sel.innerHTML = '<option value=\"\">Todas las categorías</option>' + categorias
        .map(c => `<option value=\"${c.slug}\">${c.label}</option>`)
        .join('');
})();

function stockBadge(s) {
    if (s === 0) return '<span class="stock-badge stock-out">Agotado</span>';
    if (s <= 20) return '<span class="stock-badge stock-low">Bajo stock ('+s+')</span>';
    return '<span class="stock-badge stock-ok">Disponible ('+s+')</span>';
}

function norm(s) {
    return String(s || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '');
}

// Mapeo explícito (según bloques del PDF) de CODIGO -> archivo dentro de public/Catalogo.
// Nota: en tu carpeta hay nombres como "Naeho52 a 78.jpg" que representan LISTAS (no rangos).
const imageByCode = (() => {
    const map = {};
    const add = (file, codes) => {
        for (const c of codes) map[String(c).toUpperCase()] = file;
    };

    // --- NAEHO (HO) ---
    add('Naeho 57 a 10.jpg', ['NAEHO57','NAEHO01','NAEHO02','NAEHO56','NAEHO10','NAEHO18','NAEHO20','NAEHO21','NAEHO23','NAEHO24','NAEHO25']);
    add('Naeho52 a 78.jpg', ['NAEHO52','NAEHO65','NAEHO78']);
    add('Naeho 09 a 30.jpg', ['NAEHO09','NAEHO30']);
    add('Naeho 53 a 77.jpg', ['NAEHO53','NAEHO74','NAEHO77']);
    add('Naeho 34 a 59.jpg', ['NAEHO34','NAEHO35','NAEHO36','NAEHO41','NAEHO42','NAEHO43','NAEHO55','NAEHO59']);
    add('Naeho 18 a 25.jpg', ['NAEHO18','NAEHO20','NAEHO21','NAEHO23','NAEHO24','NAEHO25']);

    // --- NAEDC / NAEMC (DC/MC) ---
    add('Naedc 28 a 43.jpg', ['NAEDC28','NAEDC43']);
    add('Naemc 03.jpg', ['NAEMC03']);
    add('Naedc 00 a 07.jpg', ['NAEDC00','NAEDC01','NAEDC02','NAEDC03','NAEDC04','NAEDC05','NAEDC06','NAEDC07']);
    add('Naedc 09 a 17.jpg', ['NAEDC09','NAEDC10','NAEDC11','NAEDC13','NAEDC25','NAEDC15','NAEDC17']);
    add('Naedc 18 a 40.jpg', ['NAEDC18','NAEDC20','NAEDC21','NAEDC26','NAEDC27','NAEDC40']);

    // --- Premium ---
    add('Naedc 49 a 44.jpg', ['NAEDC49','NAEDC45','NAEDC44']);
    add('Naeho 90 a 85.jpg', ['NAEHO90','NAEHO89','NAEHO88','NAEHO93','NAEHO86','NAEHO85']);

    // --- Kits / MS ---
    add('Ndidc03 a Ebrdr 05 a 08.jpg', ['NDIDC03','EBRDR05','EBRDR06','EBRDR07','EBRDR08']);
    add('Naems 00 a 11.jpg', ['NAEMS00','NAEMS01','NAEMS11']);
    add('Naems 03 a 07.jpg', ['NAEMS03','NAEMS05','NAEMS07']);

    // --- Auto ---
    add('Narau09 a 12.jpg', ['NARAU09','NARAU10','NARAU11','NARAU12','NARAU13']);
    add('Nreau 14 a 16.jpg', ['NREAU12','NREAU13','NREAU14','NREAU15','NREAU16']);

    // --- Gel ---
    add('Narcg 00 a 07.jpg', ['NARCG00','NARCG07']);
    // (el archivo "Narcg 06 a 16.jpg" parece corresponder a NARGE**, pero el catálogo usa NARGE** sin foto dedicada)

    // --- Eléctrico / Líquido ---
    add('Ndiel 00 a 03.jpg', ['NDIEL00','NDIEL02','NDIEL03']);
    add('Ndier 07 a 09.jpg', ['NDIER07','NDIER08','NDIER09']);
    add('Ndier 10 a 12.jpg', ['NDIER10','NDIER11','NDIER12']);
    add('Ndilg 04 a Nmang 02.jpg', ['NDILG04','NMANG02']);
    add('Nlilg 48 a 53.jpg', ['NLILG48','NLILG49','NLILG50','NLILG51','NLILG52','NLILG53']);
    // Para NLILG10..19 se reutiliza la misma foto del bloque 48..53 (si no hay otra)
    add('Nlilg 48 a 53.jpg', ['NLILG10','NLILG11','NLILG12','NLILG13','NLILG18','NLILG19']);

    // --- NON-PARA / PARA ---
    add('Nnopa 00 a 02.png', ['NNOPA00','NNOPA02']);
    add('Nnopa 17 a 19.jpg', ['NNOPA17','NNOPA18','NNOPA19']);
    add('Nnomi 00 a 01.jpg', ['NNOMI00','NNOMI01']);
    add('Nnocr 01 a 02.jpg', ['NNOCR01','NNOCR02']);
    add('Nnoca 06 a 12.jpg', ['NNOCA06','NNOCA07','NNOCA12']);
    add('Npccl06.jpg', ['NPCCL06']);
    add('Ntacp02 a 03.jpg', ['NTACP02','NTACP03']);
    add('Ntacp 00 a 01.jpg', ['NTACP00','NTACP01']);
    add('Ntacp06.jpg', ['NTACP06']);

    add('Epare 08 a 09.jpg', ['EPARE08','EPARE09']);
    add('Npare 00 a 20.jpg', ['NPARE00','NPARE10','NPARE20']);
    add('Npaal 00 a 20.jpg', ['NPAAL00','NPAAL10','NPAAL20']);
    add('Npaba 11 a 34.jpg', ['NPABA11','NPABA12','NPABA13','NPABA30','NPABA32','NPABA34']);
    add('Npacr 04.jpg', ['NPACR04']);
    add('Nnopa31.jpg', ['NNOPA31']);

    // --- Tapetes / nuevos / clip / hang ---
    add('Ntaas 25 a 34.jpg', ['NTAAS25','NTAAS26','NTAAS27','NTAAS28','NTAAS29','NTAAS30','NTAAS31','NTAAS32','NTAAS33','NTAAS34']);
    add('Ntali 00 a 02.jpg', ['NTALI00','NTALI01','NTALI02']);
    add('Ntast 02 a 19.jpg', ['NTAST02','NTAST03','NTAST12','NTAST13','NTAST14','NTAST15','NTAST19']);
    add('Ntali 03.jpg', ['NTALI03']);
    add('Ntaas 14.jpg', ['NTAAS14']);
    add('Ntaas 37.png', ['NTAAS37']);
    add('Narco 02 a 08.jpg', ['NARCO02','NARCO03','NARCO04','NARCO05','NARCO06','NARCO07','NARCO08']);
    add('Narha 00 a 06.jpg', ['NARHA00','NARHA01','NARHA02','NARHA03','NARHA04','NARHA05','NARHA06']);

    // --- Otros sueltos ---
    add('Npavp 00 a Npapp 00.jpg', ['NPAVP00','NPAPP00']);
    add('Nlils 02.jpg', ['NLILS02']);

    return map;
})();

function imageForCode(code) {
    const raw = String(code || '').toUpperCase().trim();
    if (!raw) return null;
    // variantes: con y sin ceros (ej. NAEHO01 -> NAEHO1)
    const m = raw.match(/^([A-Z]{3,})(0*)([0-9]+)$/);
    const variants = [raw];
    if (m) variants.push(m[1] + String(parseInt(m[3], 10)));

    for (const v of variants) {
        const file = imageByCode[v];
        if (file) return `{{ asset('Catalogo') }}/${encodeURIComponent(file)}`;
    }

    // fallback: contiene el código en el nombre del archivo (por si algún nombre es raro)
    const c = norm(raw);
    for (const file of catalogoImages) {
        const f = norm(file);
        if (f.includes(c)) return `{{ asset('Catalogo') }}/${encodeURIComponent(file)}`;
    }
    return null;
}

function renderProducts(list) {
    const grid = document.getElementById('productsGrid');
    const imageCache = {};
    grid.innerHTML = list.map(p => `
        <div class="prod-card" data-codigo="${p.codigo}" data-cat="${p.categoria}" data-name="${p.nombre.toLowerCase()}" data-code="${p.codigo.toLowerCase()}">
            <div class="prod-img">
                ${
                    (imageCache[p.codigo] ??= imageForCode(p.codigo))
                        ? `<img src="${imageCache[p.codigo]}" alt="${p.nombre.replace(/\"/g,'&quot;')}" style="width:100%;height:100%;object-fit:cover" onerror="this.remove();this.parentElement.innerHTML='<svg width=&quot;48&quot; height=&quot;48&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;#9ca3af&quot; stroke-width=&quot;1.5&quot;><rect x=&quot;3&quot; y=&quot;3&quot; width=&quot;18&quot; height=&quot;18&quot; rx=&quot;2&quot;/><line x1=&quot;12&quot; y1=&quot;8&quot; x2=&quot;12&quot; y2=&quot;16&quot;/><line x1=&quot;8&quot; y1=&quot;12&quot; x2=&quot;16&quot; y2=&quot;12&quot;/></svg>';">`
                        : `<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>`
                }
            </div>
            <div class="prod-body">
                <div class="prod-cat">${p.seccion || p.categoria}</div>
                <div class="prod-name">${p.nombre}</div>
                <div class="prod-code">${p.codigo}${p.unidad ? ' · ' + p.unidad : ''}</div>
                <div class="prod-desc">${p.desc}</div>
                <div class="prod-footer">
                    <div><div class="prod-price">$${p.precio.toLocaleString('es-MX',{minimumFractionDigits:2})}</div>${stockBadge(p.stock)}</div>
                    <button class="btn-add" ${p.stock===0?'disabled':''} onclick="agregarPedido('${p.codigo}')">${p.stock===0?'Agotado':'Agregar'}</button>
                </div>
            </div>
        </div>
    `).join('');
}

// --- Paginación (cliente) ---
const pageSize = 10;
let currentPage = 1;
let filteredProducts = productos.slice();

function pageCount() {
    return Math.max(1, Math.ceil(filteredProducts.length / pageSize));
}

function renderPagination() {
    const container = document.getElementById('pagination');
    const totalPages = pageCount();
    const page = Math.min(Math.max(1, currentPage), totalPages);
    currentPage = page;

    const mkBtn = (label, { disabled = false, active = false, onClick = null } = {}) => {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (active ? ' active' : '');
        btn.type = 'button';
        btn.textContent = label;
        btn.disabled = disabled;
        if (onClick) btn.addEventListener('click', onClick);
        return btn;
    };

    container.innerHTML = '';
    container.appendChild(mkBtn('◀', { disabled: page <= 1, onClick: () => goToPage(page - 1) }));

    // Ventana de páginas: 1 ... (page-1) page (page+1) ... last
    const pages = [];
    pages.push(1);
    for (let p = page - 1; p <= page + 1; p++) {
        if (p > 1 && p < totalPages) pages.push(p);
    }
    if (totalPages > 1) pages.push(totalPages);
    const uniquePages = Array.from(new Set(pages)).sort((a, b) => a - b);

    let last = 0;
    for (const p of uniquePages) {
        if (p - last > 1) {
            const dots = mkBtn('…', { disabled: true });
            container.appendChild(dots);
        }
        container.appendChild(mkBtn(String(p), { active: p === page, onClick: () => goToPage(p) }));
        last = p;
    }

    container.appendChild(mkBtn('▶', { disabled: page >= totalPages, onClick: () => goToPage(page + 1) }));
}

function renderCurrentPage() {
    const total = filteredProducts.length;
    const totalPages = pageCount();
    currentPage = Math.min(Math.max(1, currentPage), totalPages);

    const start = (currentPage - 1) * pageSize;
    const pageItems = filteredProducts.slice(start, start + pageSize);
    renderProducts(pageItems);

    const shownFrom = total === 0 ? 0 : start + 1;
    const shownTo = total === 0 ? 0 : Math.min(start + pageSize, total);
    document.getElementById('prodCount').textContent =
        `${total} productos · mostrando ${shownFrom}-${shownTo}`;

    renderPagination();
}

function goToPage(p) {
    currentPage = p;
    renderCurrentPage();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function filtrar() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    const cat = document.getElementById('catFilter').value;
    filteredProducts = productos.filter(p => {
        const matchSearch = !q || p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q);
        const matchCat = !cat || p.categoria === cat;
        return matchSearch && matchCat;
    });
    currentPage = 1;
    renderCurrentPage();
}

renderCurrentPage();
</script>
@endpush
