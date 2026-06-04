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
    .filter-actions{display:flex;gap:8px;align-items:center;padding-bottom:1px}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-primary:hover{background:var(--purple-dark)}
    .btn-outline{padding:9px 16px;background:var(--white);color:var(--gray-text);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;text-decoration:none}
    .btn-outline:hover{border-color:var(--purple);color:var(--purple)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}
    .active-filters{font-size:12px;color:var(--gray-muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center}
    .active-tag{background:var(--purple-subtle);color:var(--purple);padding:3px 10px;border-radius:999px;font-weight:600;font-size:11px}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}
    .badge-stock{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-stock.ok{background:var(--green-bg);color:var(--green)}
    .badge-stock.low{background:var(--amber-bg);color:var(--amber)}
    .badge-stock.out{background:var(--red-bg);color:var(--red)}
    .badge-activo{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-activo.on{background:var(--green-bg);color:var(--green)}
    .badge-activo.off{background:var(--gray-soft);color:var(--gray-muted)}
    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}
    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green)}
    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.filter-field{min-width:100%}.filter-form{flex-direction:column;align-items:stretch}}
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
@endphp

<div class="toolbar">
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
            <a href="{{ route('admin.productos', array_merge($baseQuery, ['activo' => '0'])) }}" class="filter-btn {{ $filtros['activo'] === '0' && !$filtros['stock'] && !$filtros['grupo'] ? 'active' : '' }}">
                Inactivos <span class="filter-count">{{ $conteoInactivos }}</span>
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
                <input type="text" name="busqueda" value="{{ $filtros['busqueda'] }}" placeholder="Código, nombre o categoría…">
            </div>
            <div class="filter-field">
                <label>Nivel de stock</label>
                <select name="stock">
                    <option value="">Todos los niveles</option>
                    @foreach($stockOpciones as $key => $label)
                        <option value="{{ $key }}" {{ $filtros['stock'] === $key ? 'selected' : '' }}>
                            {{ $label }} ({{ $key === 'agotado' ? $conteoAgotado : ($key === 'bajo' ? $conteoBajo : $conteoOk) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Categoría</label>
                <select name="categoria">
                    <option value="">Todas</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat }}" {{ $filtros['categoria'] === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Estado</label>
                <select name="activo">
                    <option value="">Activos e inactivos</option>
                    <option value="1" {{ $filtros['activo'] === '1' ? 'selected' : '' }}>Solo activos</option>
                    <option value="0" {{ $filtros['activo'] === '0' ? 'selected' : '' }}>Solo inactivos ({{ $conteoInactivos }})</option>
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
            @if($filtros['activo'] === '1')<span class="active-tag">Solo activos</span>@endif
            @if($filtros['activo'] === '0')<span class="active-tag">Solo inactivos</span>@endif
            @if($filtros['fecha_desde'] ?? false)<span class="active-tag">Desde: {{ $filtros['fecha_desde'] }}</span>@endif
            @if($filtros['fecha_hasta'] ?? false)<span class="active-tag">Hasta: {{ $filtros['fecha_hasta'] }}</span>@endif
        </div>
        @endif
    </div>
</div>

<div class="admin-table-wrap">
@if($productos->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Unidad</th>
                <th>Stock</th>
                <th>Tendencia</th>
                <th>Nivel</th>
                <th>Proveedor</th>
                <th>Hora alta</th>
                <th>Catálogo</th>
            </tr>
        </thead>
        <tbody>
        @php $lastDate = null; @endphp
        @foreach($productos as $p)
            @php
                $currentDate = $p->created_at ? $p->created_at->format('Y-m-d') : null;
                $stockClass = $p->stock <= 0 ? 'out' : ($p->stock < 50 ? 'low' : 'ok');
                $stockLabel = $p->stock <= 0 ? 'Agotado' : ($p->stock < 50 ? 'Bajo' : 'OK');
                $trendVal = rand(-15, 12);
            @endphp
            @if($currentDate !== $lastDate)
                <tr>
                    <td colspan="11" style="background:var(--purple-subtle);font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple);">
                        {{ $p->created_at ? $p->created_at->locale('es')->isoFormat('DD [de] MMMM YYYY') : 'Sin fecha' }}
                    </td>
                </tr>
                @php $lastDate = $currentDate; @endphp
            @endif
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td style="color:var(--gray-muted)">{{ $p->categoria ?: '—' }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($p->precio, 2) }}</td>
                <td>{{ $p->unidad_venta }}</td>
                <td style="font-weight:600;font-variant-numeric:tabular-nums">{{ number_format($p->stock) }}</td>
                <td>@include('partials.trend-arrow', ['value' => $trendVal])</td>
                <td><span class="badge-stock {{ $stockClass }}">{{ $stockLabel }}</span></td>
                <td style="font-size:11px;">
                    @if($p->proveedor_tipo === 'admin')
                        <span style="color:var(--purple);font-weight:600;">{{ $p->proveedor_nombre }}</span>
                    @elseif($p->proveedor_nombre)
                        <span style="color:var(--gray-muted);">{{ $p->proveedor_nombre }}</span>
                    @else
                        —
                    @endif
                </td>
                <td style="font-size:11px;color:var(--gray-muted);white-space:nowrap;">
                    {{ $p->created_at ? $p->created_at->format('h:i a') : '—' }}
                </td>
                <td>
                    <span class="badge-activo {{ $p->activo ? 'on' : 'off' }}">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
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
@endsection
