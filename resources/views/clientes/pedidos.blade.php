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
    // Fuente: PDF "2025-LP MX Wiese Institucional 19sep"
    {codigo:'NAEHO57',nombre:'Aerosol HO WIESE Thaití 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO01',nombre:'Aerosol HO WIESE Hawaian Ginger 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO02',nombre:'Aerosol HO WIESE Manzana Canela 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO56',nombre:'Aerosol HO WIESE Frutas Rojas 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO10',nombre:'Aerosol HO WIESE Lavanda 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO18',nombre:'Aerosol HO WIESE Amour 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO20',nombre:'Aerosol HO WIESE Brisa de los Alpes 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO21',nombre:'Aerosol HO WIESE Paraíso Floral 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO23',nombre:'Aerosol HO WIESE Sensación Campestre 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO24',nombre:'Aerosol HO WIESE Cítrico 365g/400ml/12.87oz C/12 pzas',precio:287.00},
    {codigo:'NAEHO25',nombre:'Aerosol HO WIESE Aqua 365g/400ml/12.87oz C/12 pzas',precio:287.00},

    {codigo:'NAEHO52',nombre:'Desinfectante WIESE Cítrico 333g/400ml C/12 pzas',precio:511.50},
    {codigo:'NAEHO65',nombre:'Desinfectante WIESE Fresh Linen 333g/400ml C/12 pzas',precio:511.50},
    {codigo:'NAEHO78',nombre:'Eliminador de Olores WIESE Floral 333g/400ml C/12 pzas',precio:511.50},

    {codigo:'NAEHO09',nombre:'Abrillantador de Muebles WIESE Naranja 333g/400ml C/12 pzas',precio:474.00},
    {codigo:'NAEHO30',nombre:'Abrillantador de Muebles WIESE Limón 333g/400ml C/12 pzas',precio:474.00},

    {codigo:'NAEHO53',nombre:'Abrillantador de Muebles WIESE Naranja 226g/8oz C/12 pzas',precio:378.00},
    {codigo:'NAEHO74',nombre:'Desinfectante WIESE Fresh Linen 226g/8oz C/12 pzas',precio:385.00},
    {codigo:'NAEHO77',nombre:'Desinfectante WIESE S/A Fresh Linen 226g/8oz C/12 pzas',precio:277.00},

    {codigo:'NAEHO34',nombre:'Aerosol HO WIESE Lavanda 226g/8oz C/12 pzas',precio:239.00},
    {codigo:'NAEHO35',nombre:'Aerosol HO WIESE Paraíso Floral 226g/8oz C/12 pzas',precio:239.00},
    {codigo:'NAEHO36',nombre:'Aerosol HO WIESE Manzana Canela 226g/8oz C/12 pzas',precio:239.00},
    {codigo:'NAEHO41',nombre:'Aerosol HO WIESE Thaití 226g/8oz C/12 pzas',precio:239.00},
    {codigo:'NAEHO42',nombre:'Aerosol HO WIESE Hawaian Ginger 226g/8oz C/12 pzas',precio:239.00},
    {codigo:'NAEHO43',nombre:'Aerosol HO WIESE Frutas Rojas 226g/8oz C/12 pzas',precio:239.00},
    {codigo:'NAEHO55',nombre:'Aerosol HO WIESE Lavanda-Vainilla 226g/8oz C/12 pzas',precio:239.00},
    {codigo:'NAEHO59',nombre:'Aerosol HO WIESE Pino Canadiense 226g/8oz C/12 pzas',precio:239.00},

    {codigo:'NAEDC28',nombre:'Clean Air en Aerosol DC WIESE Lavanda 180g/256ml C/12 pzas',precio:551.00},
    {codigo:'NAEDC43',nombre:'Desinfectante DC WIESE Fresh Linen 180g/256ml C/12 pzas',precio:551.00},
    {codigo:'NAEMC03',nombre:'Desinfectante MC WIESE Fresh Linen 100ml/80g/2.82oz C/12 pza',precio:244.00},

    {codigo:'NAEDC00',nombre:'Aerosol DC WIESE Eliminador de Olores 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC01',nombre:'Aerosol DC WIESE Naranja 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC02',nombre:'Aerosol DC WIESE Ocean Mist 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC03',nombre:'Aerosol DC WIESE Mango 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC04',nombre:'Aerosol DC WIESE Vainilla 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC05',nombre:'Aerosol DC WIESE Piña Colada 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC06',nombre:'Aerosol DC WIESE Limón 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC07',nombre:'Aerosol DC WIESE Hawaian Ginger 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC09',nombre:'Aerosol DC WIESE Cereza 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC10',nombre:'Aerosol DC WIESE Canela 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC11',nombre:'Aerosol DC WIESE Frutas Rojas 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC13',nombre:'Aerosol DC WIESE Bosque 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC25',nombre:'Aerosol DC WIESE Manzana Verde 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC15',nombre:'Aerosol DC WIESE Tahití 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC17',nombre:'Aerosol DC WIESE Lavanda 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC18',nombre:'Aerosol DC WIESE Manzana Canela 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC20',nombre:'Aerosol DC WIESE Fresa 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC21',nombre:'Aerosol DC WIESE Melón 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC26',nombre:'Aerosol DC WIESE Menta 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC27',nombre:'Aerosol DC WIESE Brissé 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC40',nombre:'Aerosol DC WIESE Red Clover 180g/256ml C/12 pzas',precio:451.00},
    {codigo:'NAEDC12',nombre:'Aerosol HO WIESE Baby Powder 323g/400ml C/12 pzas',precio:451.00},

    {codigo:'EBRDR05',nombre:'Breeze Matic WIESE Thaití 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit',precio:1993.00},
    {codigo:'EBRDR06',nombre:'Breeze Matic WIESE Hawaian Ginger 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit',precio:1993.00},
    {codigo:'EBRDR07',nombre:'Breeze Matic WIESE Frutas Rojas 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit',precio:1993.00},
    {codigo:'EBRDR08',nombre:'Breeze Matic WIESE Manzana Canela 1 Dispensador/1 Repuesto/2 Pilas C/6 Kit',precio:1993.00},
    {codigo:'NDIDC03',nombre:'Dispensador Aromatizante Programable WIESE 1pza.',precio:254.00},

    {codigo:'NAEMS00',nombre:'Aerosol MS WIESE Thaití 1 Repuesto/9g C/12 Blister',precio:300.00},
    {codigo:'NAEMS01',nombre:'Aerosol MS WIESE Hawaian Ginger 1 Repuesto/9g C/12 Blister',precio:300.00},
    {codigo:'NAEMS11',nombre:'Aerosol MS WIESE Hipo Chicle 1 Repuesto/9g C/12 Blister',precio:300.00},
    {codigo:'NAEMS03',nombre:'Aerosol MS WIESE Thaití 2 Repuestos/9g c/u C/12 Blister',precio:492.00},
    {codigo:'NAEMS05',nombre:'Aerosol MS WIESE Hawaian Ginger 2 Repuestos/9g c/u C/12 Blister',precio:492.00},
    {codigo:'NAEMS07',nombre:'Aerosol MS WIESE Hipo Chicle 2 Repuestos/9g c/u C/12 Blister',precio:492.00},

    {codigo:'NARAU09',nombre:'Aromatizante Auto WIESE Amour 7ml C/6 pzas',precio:291.00},
    {codigo:'NARAU10',nombre:'Aromatizante Auto WIESE Brisse 7ml C/6 pzas',precio:291.00},
    {codigo:'NARAU11',nombre:'Aromatizante Auto WIESE Atraktion 7ml C/6 pzas',precio:291.00},
    {codigo:'NARAU13',nombre:'Aromatizante Auto WIESE Chicle León 7ml C/6 pzas',precio:291.00},
    {codigo:'NARAU12',nombre:'Aromatizante Auto WIESE Auto Nuevo 7ml C/6 pzas',precio:291.00},

    {codigo:'NREAU14',nombre:'Repuesto Auto WIESE Amour 7ml C/6 pzas',precio:220.00},
    {codigo:'NREAU12',nombre:'Repuesto Auto WIESE Brisse 7ml C/6 pzas',precio:220.00},
    {codigo:'NREAU13',nombre:'Repuesto Auto WIESE Atraktion 7ml C/6 pzas',precio:220.00},
    {codigo:'NREAU15',nombre:'Repuesto Auto WIESE Chicle León 7ml C/6 pzas',precio:220.00},
    {codigo:'NREAU16',nombre:'Repuesto Auto WIESE Auto Nuevo 7ml C/6 pzas',precio:220.00},

    {codigo:'NARGE06',nombre:'Gel Aromatizante WIESE Cítrico 70g C/24 pzas',precio:246.00},
    {codigo:'NARGE07',nombre:'Gel Aromatizante WIESE Floral 70g C/24 pzas',precio:246.00},
    {codigo:'NARGE08',nombre:'Gel Aromatizante WIESE Jazmín 70g C/24 pzas',precio:246.00},
    {codigo:'NARGE10',nombre:'Gel Aromatizante WIESE Lavanda 70g C/24 pzas',precio:246.00},
    {codigo:'NARGE11',nombre:'Gel Aromatizante WIESE Atraktion 70g C/24 pzas',precio:246.00},
    {codigo:'NARGE16',nombre:'Gel Aromatizante WIESE Auto Nuevo 70g C/24 pzas',precio:246.00},

    {codigo:'NARCG00',nombre:'Cono de Gel Aromatizante WIESE Cítrico 170g / 12 pzas',precio:266.00},
    {codigo:'NARCG07',nombre:'Cono de Gel Aromatizante WIESE Manzana Canela 170g / 12 pzas',precio:266.00},

    {codigo:'NDIEL00',nombre:'Difusor Eléctrico WIESE Brissé 21ml C/12 pzas',precio:664.00},
    {codigo:'NDIEL02',nombre:'Difusor Eléctrico WIESE Lavanda Manzanilla 21ml C/12 pzas',precio:664.00},
    {codigo:'NDIEL03',nombre:'Difusor Eléctrico WIESE Manzana Canela 21ml C/12 pzas',precio:664.00},

    {codigo:'NDIER07',nombre:'Repuesto WIESE Brissé C/1 repuesto/21ml C/12 Blister',precio:450.00},
    {codigo:'NDIER08',nombre:'Repuesto WIESE Lavanda Manzanilla C/1 repuesto/21ml C/12 Blister',precio:450.00},
    {codigo:'NDIER09',nombre:'Repuesto WIESE Manzana Canela C/1 repuesto/21ml C/12 Blister',precio:450.00},
    {codigo:'NDIER10',nombre:'Repuesto WIESE Brissé C/2 repuestos/21ml C/12 Blister',precio:694.00},
    {codigo:'NDIER11',nombre:'Repuesto WIESE Lavanda Manzanilla C/2 repuestos/21ml C/12 Blister',precio:694.00},
    {codigo:'NDIER12',nombre:'Repuesto WIESE Manzana Canela C/2 repuestos/21ml C/12 Blister',precio:694.00},

    {codigo:'NDILG04',nombre:'Dispensador Líquido Goteador 1pza',precio:363.00},
    {codigo:'NLILG10',nombre:'Líquido Goteador WIESE Lavanda 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG11',nombre:'Líquido Goteador WIESE Manzana Menta 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG12',nombre:'Líquido Goteador WIESE Brissé 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG13',nombre:'Líquido Goteador WIESE Mango 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG18',nombre:'Líquido Goteador WIESE Cítrus 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG19',nombre:'Líquido Goteador WIESE Red Clover 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NMANG02',nombre:'Manguera para Dispensador Líquido Gotero 1 mt.',precio:40.00},

    {codigo:'NLILG48',nombre:'Líquido Goteador WIESE Lavanda 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG49',nombre:'Líquido Goteador WIESE Manzana Menta 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG50',nombre:'Líquido Goteador WIESE Brissé 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG52',nombre:'Líquido Goteador WIESE Mango 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG51',nombre:'Líquido Goteador WIESE Cítrus 270g/9.52oz C/6 pzas',precio:538.00},
    {codigo:'NLILG53',nombre:'Líquido Goteador WIESE Red Clover 270g/9.52oz C/6 pzas',precio:538.00},

    {codigo:'NPCCL06',nombre:'Pastilla Cloro WIESE 35g C/12 pzas',precio:223.00},
    {codigo:'NNOCA06',nombre:'Canastilla WIESE Lavanda 35g C/12 pzas',precio:136.00},
    {codigo:'NNOCA07',nombre:'Canastilla WIESE Naranja 35g C/12 pzas',precio:136.00},
    {codigo:'NNOCA12',nombre:'Canastilla WIESE Azul Activo de Pino 35g C/12 pzas',precio:136.00},
    {codigo:'NNOCG07',nombre:'Canastilla Gel WIESE Naranja 35g / 12 Blisters',precio:198.00},
    {codigo:'NNOCG08',nombre:'Canastilla Gel WIESE Bosque 35g / 12 Blisters',precio:198.00},
    {codigo:'NNOCG09',nombre:'Canastilla Gel WIESE Azul Naranja 35g / 12 Blisters',precio:198.00},
    {codigo:'NTACP02',nombre:'Tapete con Pastilla NO PDCB WIESE Azul 4oz / 12 pzas',precio:201.00},
    {codigo:'NTACP03',nombre:'Tapete con Pastilla NO PDCB WIESE Cherry Rosa 4oz / 12 pzas',precio:201.00},
    {codigo:'NNOCR01',nombre:'Cristales NON-PDCB WIESE Lavanda Morado 750gr/1.65lb/4 Bote',precio:651.00},
    {codigo:'NNOCR02',nombre:'Cristales NON-PDCB WIESE Menta 750gr/1.65lb/4 Bote',precio:774.00},
    {codigo:'NNOMI00',nombre:'Pastilla Mingitorio NO PDCB WIESE Azul 85gr C/50 pzas',precio:347.00},
    {codigo:'NNOMI01',nombre:'Pastilla Mingitorio NO PDCB WIESE Rosa 85gr C/50 pzas',precio:347.00},
    {codigo:'NNOPA17',nombre:'Pastilla Azul WIESE Pino 48g C/12 Blister Burbuja',precio:138.00},
    {codigo:'NNOPA18',nombre:'Pastilla Azul WIESE Pino 96g C/12 Blister Burbuja',precio:220.00},
    {codigo:'NNOPA19',nombre:'Pastilla Azul WIESE Pino 144g C/12 Blister Burbuja',precio:312.00},
    {codigo:'NNOPA00',nombre:'Pastilla Azul WIESE 48g C/12 pzas',precio:101.00},
    {codigo:'NNOPA02',nombre:'Pastilla Azul Low-Cost WIESE 144g C/12 pzas',precio:110.00},

    {codigo:'EPARE08',nombre:'Pastilla Flor WIESE Lavanda 40g C/50 pzas',precio:271.00},
    {codigo:'EPARE09',nombre:'Pastilla Flor WIESE Floral 40g C/50 pzas',precio:271.00},
    {codigo:'NPARE00',nombre:'Pastilla Redonda WIESE Surtido 60g C/50 pzas',precio:359.00},
    {codigo:'NPARE10',nombre:'Pastilla Redonda WIESE Surtido 70g C/50 pzas',precio:403.00},
    {codigo:'NPARE20',nombre:'Pastilla Redonda WIESE Surtido 80g C/50 pzas',precio:432.00},
    {codigo:'NPAAL00',nombre:'Pastilla Alambre WIESE Surtido 60g C/50 pzas',precio:359.00},
    {codigo:'NPAAL10',nombre:'Pastilla Alambre WIESE Surtido 70g C/50 pzas',precio:403.00},
    {codigo:'NPAAL20',nombre:'Pastilla Alambre WIESE Surtido 80g C/50 pzas',precio:432.00},

    {codigo:'NPABA11',nombre:'Pastilla Barra WIESE Cereza 300g C/12 pzas',precio:435.00},
    {codigo:'NPABA12',nombre:'Pastilla Barra WIESE Lavanda 300g C/12 pzas',precio:435.00},
    {codigo:'NPABA13',nombre:'Pastilla Barra WIESE Manzana Verde 300g C/12 pzas',precio:435.00},
    {codigo:'NPABA30',nombre:'Pastilla Barra WIESE Lavanda 450g C/9 pzas',precio:475.00},
    {codigo:'NPABA32',nombre:'Pastilla Barra WIESE Cereza 450g C/9 pzas',precio:475.00},
    {codigo:'NPABA34',nombre:'Pastilla Barra WIESE Manzana Verde 450g C/9 pzas',precio:475.00},
    {codigo:'NPACR04',nombre:'Moth Balls WIESE Surtido 250g C/30 Bolsas',precio:949.00},

    {codigo:'NNOPA31',nombre:'Pastilla Bicarbonato WIESE 90g Caja con 12 Blisters',precio:231.00},
    {codigo:'NTACP06',nombre:'Tapete con pastilla Bicarbonato WIESE 4oz Caja con 12 Piezas',precio:270.00},

    {codigo:'NTACP00',nombre:'Tapete con pastilla PDCB WIESE Lavanda Azul 85g C/12 pzas',precio:202.00},
    {codigo:'NTACP01',nombre:'Tapete con pastilla PDCB WIESE Cherry Rosa 85g C/12 pzas',precio:202.00},
    {codigo:'NPAMI01',nombre:'Pastilla Mingitorio PDCB WIESE Cereza 60g C/50 pzas',precio:366.00},
    {codigo:'NPAMI04',nombre:'Pastilla Mingitorio PDCB WIESE Lavanda 85g C/50 pzas',precio:487.00},
    {codigo:'NPAMI05',nombre:'Pastilla Mingitorio PDCB WIESE Cereza 85g C/50 pzas',precio:487.00},

    {codigo:'NTAAS25',nombre:'Tapete Anti-Salpicaduras MT WIESE Menta-Azul C/10 pzas',precio:451.00},
    {codigo:'NTAAS26',nombre:'Tapete Anti-Salpicaduras MT WIESE Manzana-Canela C/10 pzas',precio:451.00},
    {codigo:'NTAAS27',nombre:'Tapete Anti-Salpicaduras MT WIESE Cítrus Caja C/10 pzas',precio:451.00},
    {codigo:'NTAAS28',nombre:'Tapete Anti-Salpicaduras MT WIESE Brissé Caja C/10 pzas',precio:451.00},
    {codigo:'NTAAS29',nombre:'Tapete Anti-Salpicaduras MT WIESE Pepino-Melón C/10 pzas',precio:451.00},
    {codigo:'NTAAS30',nombre:'Tapete Anti-Salpicaduras MT WIESE Mango-Naranja C/10 pzas',precio:451.00},
    {codigo:'NTAAS31',nombre:'Tapete Anti-Salpicaduras MT WIESE Lavanda-Lila C/10 pzas',precio:451.00},
    {codigo:'NTAAS32',nombre:'Tapete Anti-Salpicaduras MT WIESE Menta-Verde C/10 pzas',precio:451.00},
    {codigo:'NTAAS33',nombre:'Tapete Anti-Salpicaduras MT WIESE Menta-Transparente C/10 pzas',precio:451.00},
    {codigo:'NTAAS34',nombre:'Tapete Anti-Salpicaduras MT WIESE Red Clover-Rojo C/10 pzas',precio:451.00},

    {codigo:'NTALI00',nombre:'Tapete Liso WIESE Chicle-Azul C/12 pzas',precio:168.00},
    {codigo:'NTALI01',nombre:'Tapete Liso WIESE Cherry-Rojo C/12 pzas',precio:168.00},
    {codigo:'NTALI02',nombre:'Tapete Liso WIESE Menta-Blanco C/12 pzas',precio:168.00},

    {codigo:'NTAST02',nombre:'Tapete Storm WIESE Mango-Naranja C/12 pzas',precio:235.00},
    {codigo:'NTAST19',nombre:'Tapete Storm WIESE Pepino/Melon-Amarillo C/12 pzas',precio:235.00},
    {codigo:'NTAST03',nombre:'Tapete Storm WIESE Lavanda-Lila C/12 pzas',precio:235.00},
    {codigo:'NTAST12',nombre:'Tapete Storm WIESE Menta-Azul C/12 pzas',precio:235.00},
    {codigo:'NTAST13',nombre:'Tapete Storm WIESE Manzana/Canela-Verde C/12 pzas',precio:235.00},
    {codigo:'NTAST14',nombre:'Tapete Storm WIESE Brissé-Turquesa C/12 pzas',precio:235.00},
    {codigo:'NTAST15',nombre:'Tapete Storm WIESE Cítrus-Verde C/12 pzas',precio:235.00},

    {codigo:'NTALI03',nombre:'Tapete Liso C/Portería WIESE Chicle Verde C/12 Pz.',precio:318.00},
    {codigo:'NTAAS14',nombre:'Tapete Anti-Salpicaduras C/Portería WIESE Citrus Verde C/8 Pz.',precio:312.00},

    {codigo:'NARCO02',nombre:'Aromatizante Clip On WIESE Mango-Naranja C/12 pzas',precio:236.00},
    {codigo:'NARCO03',nombre:'Aromatizante Clip On WIESE Lavanda-Lila C/12 pzas',precio:236.00},
    {codigo:'NARCO04',nombre:'Aromatizante Clip On WIESE Pepino/Melón-Amarillo C/12 pzas',precio:236.00},
    {codigo:'NARCO05',nombre:'Aromatizante Clip On WIESE Menta-Azul C/12 pzas',precio:236.00},
    {codigo:'NARCO06',nombre:'Aromatizante Clip On WIESE Manzana/Canela-Verde C/12 pzas',precio:236.00},
    {codigo:'NARCO07',nombre:'Aromatizante Clip On WIESE Brissé-Turquesa C/12 pzas',precio:236.00},
    {codigo:'NARCO08',nombre:'Aromatizante Clip On WIESE Cítrus-Verde C/12 pzas',precio:236.00},

    {codigo:'NTAAS37',nombre:'Tapete Anti-Salpicadura 7.0 WIESE Mango Naranja C/12 Pz.',precio:144.00},

    {codigo:'NARHA00',nombre:'Aromatizante Hang Air WIESE Menta-Azul C/12 pzas',precio:222.00},
    {codigo:'NARHA01',nombre:'Aromatizante Hang Air WIESE Manzana/Canela-Verde C/12 pzas',precio:222.00},
    {codigo:'NARHA02',nombre:'Aromatizante Hang Air WIESE Mango-Naranja C/12 pzas',precio:222.00},
    {codigo:'NARHA03',nombre:'Aromatizante Hang Air WIESE Lavanda-Lila C/12 pzas',precio:222.00},
    {codigo:'NARHA04',nombre:'Aromatizante Hang Air WIESE Brissé-Turquesa C/12 pzas',precio:222.00},
    {codigo:'NARHA05',nombre:'Aromatizante Hang Air WIESE Cítrus-Verde Caja C/12 pzas',precio:222.00},
    {codigo:'NARHA06',nombre:'Aromatizante Hang Air WIESE Pepino/Melón-Amarillo C/12 pzas',precio:222.00},

    {codigo:'NAEDC49',nombre:'Aerosol DC WIESE Premium Lavanda 180g/256ml C/12 pzas',precio:473.00},
    {codigo:'NAEDC45',nombre:'Aerosol DC WIESE Premium Agua de Coco y Lima 180g/256ml C/12 pzas',precio:473.00},
    {codigo:'NAEDC44',nombre:'Aerosol DC WIESE Premium Mandarina y Sándalo 180g/256ml C/12 pzas',precio:473.00},

    {codigo:'NAEHO89',nombre:'Aerosol HO WIESE Premium Manzana Canela 226g/8oz C/12 pzas',precio:299.00},
    {codigo:'NAEHO90',nombre:'Aerosol HO WIESE Premium Lavanda 226g/8oz C/12 pzas',precio:299.00},
    {codigo:'NAEHO88',nombre:'Aerosol DC WIESE Redlicius 226g/8oz C/12 pzas',precio:299.00},
    {codigo:'NAEHO93',nombre:'Aerosol DC WIESE Mediterranean Essences 226g/8oz C/12 pzas',precio:299.00},
    {codigo:'NAEHO86',nombre:'Aerosol DC WIESE Premium Mandarina y Sándalo 226g/8oz C/12 pzas',precio:299.00},
    {codigo:'NAEHO85',nombre:'Aerosol DC WIESE Agua de Coco y Lima 226g/8oz C/12 pzas',precio:299.00},

    {codigo:'NLILS02',nombre:'WIESE Limpiador Líquido para Sanitario Menta 750 ml C/12 pz.',precio:330.00},
    {codigo:'NPAVP00',nombre:'Vestido PDCB WIESE Lavanda (2) 85g/15 pzas',precio:478.00},
    {codigo:'NPAPP00',nombre:'Camiseta PDCB WIESE Lavanda (2) 85g/16 pzas',precio:353.00},
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
