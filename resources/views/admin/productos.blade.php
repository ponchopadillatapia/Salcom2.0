@extends('layouts.admin')
@section('title', 'Productos')
@section('hero')
<div class="hero-band">
    <h1>Catálogo de Productos</h1>
    <p>Inventario y gestión de productos registrados</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .adm-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:22px 26px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .adm-summary-main{text-align:center;min-width:100px}
    .adm-summary-pct{font-size:42px;font-weight:800;line-height:1;color:var(--purple)}
    .adm-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .adm-summary-metrics{flex:1;display:flex;gap:24px;flex-wrap:wrap}
    .adm-metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .adm-metric-val{font-size:22px;font-weight:700;display:flex;align-items:center;gap:8px}
    .adm-summary-badge{padding:10px 16px;border-radius:10px;font-size:12px;font-weight:600;line-height:1.4;text-decoration:none;transition:var(--transition)}
    .adm-summary-badge:hover{opacity:.85}

    .toolbar{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
    .toolbar-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .filter-group{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
    .filter-btn{padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);cursor:pointer;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .filter-btn:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .filter-btn.warn.active{background:var(--amber);border-color:var(--amber)}
    .filter-btn.ok.active{background:var(--green);border-color:var(--green)}
    .filter-btn.danger.active{background:var(--red);border-color:var(--red)}
    .filter-count{font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;background:rgba(0,0,0,.08);line-height:1.2}
    .filter-btn.active .filter-count{background:rgba(255,255,255,.25)}
    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:16px 18px}
    .filter-form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:140px;flex:1}
    .filter-field.search-field{flex:2;min-width:200px}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px}
    .filter-field input,.filter-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .filter-field input:focus,.filter-field select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .filter-actions{display:flex;gap:8px;align-items:center;padding-bottom:1px;flex-wrap:wrap}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-primary:hover{background:var(--purple-dark)}
    .btn-outline{padding:9px 16px;background:var(--white);color:var(--gray-text);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;text-decoration:none}
    .btn-outline:hover{border-color:var(--purple);color:var(--purple)}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;text-decoration:none;font-family:inherit;transition:var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}
    .active-filters{font-size:12px;color:var(--gray-muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center}
    .active-tag{background:var(--purple-subtle);color:var(--purple);padding:3px 10px;border-radius:999px;font-weight:600;font-size:11px}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}
    .adm-section-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .tbl-wrap{overflow-x:auto}
    .date-row td{background:var(--purple-subtle)!important;font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)}

    .badge-stock{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-stock.ok{background:var(--green-bg);color:var(--green)}
    .badge-stock.low{background:var(--amber-bg);color:var(--amber)}
    .badge-stock.out{background:var(--red-bg);color:var(--red)}
    .badge-activo{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-activo.on{background:var(--green-bg);color:var(--green)}
    .badge-activo.off{background:var(--gray-soft);color:var(--gray-muted)}
    .stock-bar{width:72px;height:7px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .stock-fill{height:100%;border-radius:4px}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}
    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green)}

    @media(max-width:768px){
        .adm-summary{flex-direction:column;align-items:flex-start}
        .filter-field{min-width:100%}
        .filter-form{flex-direction:column;align-items:stretch}
    }
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif

@php
    $baseQuery = array_filter([
        'busqueda' => $filtros['busqueda'] ?: null,
        'activo' => $filtros['activo'] !== '' ? $filtros['activo'] : null,
        'categoria' => $filtros['categoria'] ?: null,
    ]);
    $chipActive = fn ($stk = null, $grp = null) => (!$filtros['stock'] && !$filtros['grupo'] && !$stk && !$grp)
        || ($stk && $filtros['stock'] === $stk)
        || ($grp && $filtros['grupo'] === $grp);
    $saludColor = $saludPct >= 70 ? 'var(--green)' : ($saludPct >= 40 ? 'var(--amber)' : 'var(--red)');
    $saludBg = $saludPct >= 70 ? 'var(--green-bg)' : ($saludPct >= 40 ? 'var(--amber-bg)' : 'var(--red-bg)');
@endphp

<div class="adm-summary anim" style="padding:16px 26px;">
    <div class="adm-summary-main">
        <div class="adm-summary-pct">{{ $totalGeneral }}</div>
        <div class="adm-summary-label">Productos en catálogo</div>
    </div>
</div>

<div class="toolbar anim" style="animation-delay:.04s">
    <div class="toolbar-top">
        <div class="filter-group">
            <a href="{{ route('admin.productos', $baseQuery) }}" class="filter-btn {{ $chipActive() ? 'active' : '' }}">
                Todos <span class="filter-count">{{ $totalGeneral }}</span>
            </a>
            <a href="{{ route('admin.productos', array_merge($baseQuery, ['grupo' => 'criticos'])) }}" class="filter-btn warn {{ $chipActive(null, 'criticos') ? 'active' : '' }}">
                Críticos <span class="filter-count">{{ $conteoCriticos }}</span>
            </a>
            <a href="{{ route('admin.productos', array_merge($baseQuery, ['stock' => 'agotado'])) }}" class="filter-btn danger {{ $chipActive('agotado') ? 'active' : '' }}">
                Agotados <span class="filter-count">{{ $conteoAgotado }}</span>
            </a>
            <a href="{{ route('admin.productos', array_merge($baseQuery, ['stock' => 'bajo'])) }}" class="filter-btn warn {{ $chipActive('bajo') ? 'active' : '' }}">
                Stock bajo <span class="filter-count">{{ $conteoBajo }}</span>
            </a>
            <a href="{{ route('admin.productos', array_merge($baseQuery, ['stock' => 'ok'])) }}" class="filter-btn ok {{ $chipActive('ok') ? 'active' : '' }}">
                Stock OK <span class="filter-count">{{ $conteoOk }}</span>
            </a>
        </div>
        <span class="badge-count">{{ $productos->total() }} resultado{{ $productos->total() !== 1 ? 's' : '' }}</span>
    </div>

    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.productos') }}" class="filter-form">
            @if($filtros['grupo'] && !$filtros['stock'])
                <input type="hidden" name="grupo" value="{{ $filtros['grupo'] }}">
            @endif
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="busqueda" value="{{ $filtros['busqueda'] }}" placeholder="Nombre, categoría o proveedor…">
            </div>
            <div class="filter-field">
                <label>Código</label>
                <input type="text" name="codigo" value="{{ $filtros['codigo'] ?? '' }}" placeholder="Buscar por código…">
            </div>
            <div class="filter-field">
                <label>Tipo Producto</label>
                <select name="categoria">
                    <option value="">Todas</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat }}" {{ $filtros['categoria'] === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Proveedor</label>
                <select name="proveedor">
                    <option value="">Todos</option>
                    @foreach($proveedores->filter(fn($p) => \App\Models\Producto::where('proveedor_nombre', $p)->where('proveedor_tipo', 'proveedor')->exists()) as $prov)
                        <option value="{{ $prov }}" {{ ($filtros['proveedor'] ?? '') === $prov ? 'selected' : '' }}>{{ $prov }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Admin</label>
                <select name="admin">
                    <option value="">Todos</option>
                    @foreach($admins as $adm)
                        <option value="{{ $adm }}" {{ ($filtros['admin'] ?? '') === $adm ? 'selected' : '' }}>{{ $adm }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Unidad</label>
                <select name="unidad">
                    <option value="">Todas</option>
                    @foreach(\App\Models\Producto::whereNotNull('unidad_venta')->where('unidad_venta', '!=', '')->distinct()->pluck('unidad_venta')->sort() as $uni)
                        <option value="{{ $uni }}" {{ ($filtros['unidad'] ?? '') === $uni ? 'selected' : '' }}>{{ $uni }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Fecha desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}">
            </div>
            <div class="filter-field">
                <label>Fecha hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosActivos)
                    <a href="{{ route('admin.productos') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosActivos)
        <div class="active-filters" style="margin-top:12px;">
            <span>Filtros activos:</span>
            @if($filtros['busqueda'])<span class="active-tag">«{{ $filtros['busqueda'] }}»</span>@endif
            @if($filtros['grupo'] === 'criticos')<span class="active-tag">Críticos</span>@endif
            @if($filtros['stock'])<span class="active-tag">{{ $stockOpciones[$filtros['stock']] ?? ucfirst($filtros['stock']) }}</span>@endif
            @if($filtros['categoria'])<span class="active-tag">{{ $filtros['categoria'] }}</span>@endif
            @if($filtros['proveedor'] ?? false)<span class="active-tag">Prov: {{ $filtros['proveedor'] }}</span>@endif
            @if($filtros['admin'] ?? false)<span class="active-tag">Admin: {{ $filtros['admin'] }}</span>@endif
            @if($filtros['fecha_desde'] ?? false)<span class="active-tag">Desde: {{ $filtros['fecha_desde'] }}</span>@endif
            @if($filtros['fecha_hasta'] ?? false)<span class="active-tag">Hasta: {{ $filtros['fecha_hasta'] }}</span>@endif
        </div>
        @endif
    </div>
</div>

<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Catálogo de productos</h4>
            <div class="adm-section-meta">{{ $productos->total() }} resultado{{ $productos->total() !== 1 ? 's' : '' }} · agrupados por fecha de alta</div>
        </div>
        <div class="adm-section-toolbar">
            <a href="{{ route('admin.productos.excel', request()->query()) }}" class="btn-export">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
        </div>
    </div>
    @if($productos->count())
    <div class="tbl-wrap">
        <table class="admin-table" id="tableProductos">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tipo Producto</th>
                    <th>Precio</th>
                    <th>Unidad de Medida</th>
                    <th>Proveedor</th>
                    <th>Hora alta</th>
                    <th style="width:40px"></th>
                </tr>
            </thead>
            <tbody>
            @php $lastDate = null; $maxStock = max($productos->max('stock'), 1); @endphp
            @foreach($productos as $p)
                @php
                    $currentDate = $p->created_at ? $p->created_at->format('Y-m-d') : null;
                    $stockClass = $p->stock <= 0 ? 'out' : ($p->stock < 50 ? 'low' : 'ok');
                    $stockLabel = $p->stock <= 0 ? 'Agotado' : ($p->stock < 50 ? 'Bajo' : 'OK');
                    $stockPct = min(100, round(($p->stock / $maxStock) * 100));
                    $barColor = $p->stock <= 0 ? '#ff3b30' : ($p->stock < 50 ? '#ff9f0a' : '#34c759');
                @endphp
                @if($currentDate !== $lastDate)
                    <tr class="date-row">
                    <td colspan="7">{{ $p->created_at ? $p->created_at->locale('es')->isoFormat('DD [de] MMMM YYYY') : 'Sin fecha' }}</td>
                    </tr>
                    @php $lastDate = $currentDate; @endphp
                @endif
                <tr data-id="{{ $p->id }}" data-categoria="{{ $p->categoria }}" data-precio="{{ $p->precio }}" data-unidad="{{ $p->unidad_venta }}" data-stock="{{ $p->stock }}" style="cursor:pointer" onclick="abrirEditor(this)">
                    <td onclick="event.stopPropagation()"><a href="{{ route('admin.productos.detalle', $p->id) }}" style="font-weight:700;color:var(--purple);text-decoration:none;">{{ $p->codigo }}</a></td>
                    <td style="font-weight:600">{{ $p->nombre }}</td>
                    <td style="color:var(--gray-muted)">{{ $p->categoria ?: '—' }}</td>
                    <td style="font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)">${{ number_format($p->precio, 2) }}</td>
                    <td>{{ $p->unidad_venta }}</td>
                    <td style="font-size:12px;">
                        @if($p->proveedor_tipo === 'admin')
                            <span style="color:var(--purple);font-weight:600;">{{ $p->proveedor_nombre }}</span>
                        @elseif($p->proveedor_nombre)
                            <span style="color:var(--gray-muted);">{{ $p->proveedor_nombre }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td style="font-size:11px;color:var(--gray-muted);white-space:nowrap">{{ $p->created_at ? $p->created_at->format('h:i a') : '—' }}</td>
                    <td style="text-align:center" onclick="event.stopPropagation()">
                        <button onclick="borrarProducto({{ $p->id }}, '{{ $p->codigo }}')" style="background:none;border:none;cursor:pointer;color:var(--red);opacity:.6;transition:.15s" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.6">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($productos->hasPages())
        <div class="pagination-wrap">{{ $productos->links() }}</div>
    @endif
    @else
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="15" y2="15"/><line x1="15" y1="9" x2="9" y2="15"/></svg>
        <p>No se encontraron productos con los filtros seleccionados.</p>
        @if($filtrosActivos)
            <p style="margin-top:8px;"><a href="{{ route('admin.productos') }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>
        @endif
    </div>
    @endif
</div>

{{-- Modal de edición rápida --}}
<div class="edit-modal-overlay" id="editModal" onclick="if(event.target===this)cerrarEditor()">
    <div class="edit-modal">
        <h3>Editar producto</h3>
        <div class="edit-subtitle"><span id="editCodigo"></span> — <span id="editNombre"></span></div>
        <input type="hidden" id="editId">
        <div class="edit-field">
            <label>Tipo Producto</label>
            <select id="editCategoria">
                <option value="">Sin categoría</option>
                <option value="MPI">MPI</option>
                <option value="ME">ME</option>
                <option value="MN">MN</option>
                <option value="MP">MP</option>
                <option value="PT">PT</option>
                <option value="RP">RP</option>
                <option value="CONTABLE">CONTABLE</option>
                <option value="GASTOS">GASTOS</option>
                <option value="REFACCIONES">REFACCIONES</option>
                <option value="HERRAMIENTAS">HERRAMIENTAS</option>
                <option value="MAQUINARIA">MAQUINARIA</option>
                <option value="MUESTRAS">MUESTRAS</option>
                <option value="INSUMOS">INSUMOS</option>
                <option value="EQUIPO">EQUIPO</option>
                <option value="SEGURIDAD">SEGURIDAD</option>
                <option value="VEHICULOS">VEHICULOS</option>
                <option value="MOLDES">MOLDES</option>
                <option value="SERVICIOS">SERVICIOS</option>
            </select>
        </div>
        <div class="edit-field">
            <label>Precio</label>
            <input type="number" id="editPrecio" step="0.01" min="0" placeholder="0.00">
        </div>
        <div class="edit-field">
            <label>Unidad de medida</label>
            <select id="editUnidad">
                <option value="">—</option>
                <option value="PZA">PZA</option>
                <option value="CAJA">CAJA</option>
                <option value="SET">SET</option>
                <option value="KG">KG</option>
                <option value="TONELADA">TONELADA</option>
                <option value="METRO">METRO</option>
                <option value="LITRO">LITRO</option>
                <option value="PAR">PAR</option>
                <option value="PACK">PACK</option>
                <option value="CUBETA">CUBETA</option>
                <option value="TIRA">TIRA</option>
                <option value="NA">NA</option>
            </select>
        </div>
        <div class="edit-actions">
            <button class="btn-cancel" onclick="cerrarEditor()">Cancelar</button>
            <button class="btn-save" onclick="guardarProducto()">Guardar</button>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
    .edit-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(2px)}
    .edit-modal{background:#fff;border-radius:16px;padding:28px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.2)}
    .edit-modal h3{font-size:16px;font-weight:700;color:var(--gray-text);margin-bottom:4px}
    .edit-modal .edit-subtitle{font-size:12px;color:var(--gray-muted);margin-bottom:20px}
    .edit-field{margin-bottom:14px}
    .edit-field label{display:block;font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px}
    .edit-field input,.edit-field select{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:9px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none}
    .edit-field input:focus,.edit-field select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .edit-actions{display:flex;gap:10px;margin-top:20px}
    .edit-actions .btn-save{flex:1;padding:10px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
    .edit-actions .btn-save:hover{background:var(--purple-dark)}
    .edit-actions .btn-cancel{flex:1;padding:10px;background:var(--gray-soft);color:var(--gray-text);border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
</style>
@endpush
@push('scripts')
<script>
function abrirEditor(row) {
    const modal = document.getElementById('editModal');
    document.getElementById('editId').value = row.dataset.id;
    document.getElementById('editCategoria').value = row.dataset.categoria || '';
    document.getElementById('editPrecio').value = row.dataset.precio || '0';
    document.getElementById('editUnidad').value = row.dataset.unidad || '';
    document.getElementById('editCodigo').textContent = row.cells[0].textContent;
    document.getElementById('editNombre').textContent = row.cells[1].textContent;
    modal.style.display = 'flex';
}

function cerrarEditor() {
    document.getElementById('editModal').style.display = 'none';
}

function guardarProducto() {
    const id = document.getElementById('editId').value;
    const data = {
        categoria: document.getElementById('editCategoria').value,
        precio: document.getElementById('editPrecio').value,
        unidad_venta: document.getElementById('editUnidad').value,
    };

    fetch(`/admin/productos/${id}/actualizar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            cerrarEditor();
            location.reload();
        }
    })
    .catch(err => alert('Error al guardar'));
}

function borrarProducto(id, codigo) {
    if (!confirm('¿Eliminar producto ' + codigo + '? Esta acción no se puede deshacer.')) return;

    fetch(`/admin/productos/${id}/borrar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) location.reload();
        else alert(res.mensaje || 'Error al borrar');
    })
    .catch(err => alert('Error al borrar'));
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="busqueda"]');
    const form = searchInput.closest('form');

    // Solo buscar al presionar Enter en el campo de búsqueda
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            form.submit();
        }
    });

    // Los selects NO filtran automáticamente - solo al presionar "Filtrar"

    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarEditor(); });
});
</script>
@endpush
