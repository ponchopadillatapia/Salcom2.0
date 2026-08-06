@extends('layouts.admin')
@section('title', 'Pedidos')
@section('hero')
<div class="hero-band">
    <h1>Pedidos</h1>
    <p>Consulta y seguimiento de todos los pedidos del sistema</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .inv-metrics{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px}
    .inv-metric{background:var(--white);border:1px solid var(--border-light, var(--border));border-radius:14px;padding:18px;position:relative;overflow:hidden;cursor:pointer;transition:box-shadow .15s,border-color .15s;text-decoration:none;color:inherit;display:block}
    .inv-metric:hover{border-color:var(--purple-mid,#c4b5e0);box-shadow:var(--shadow-sm)}
    .inv-metric.is-active{border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.12)}
    .inv-metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .inv-metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px}
    .inv-metric-val{font-size:26px;font-weight:700;color:var(--gray-text);line-height:1}
    .inv-metric-sub{font-size:11px;color:var(--gray-muted);margin-top:6px}

    .toolbar{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
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
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;text-decoration:none;transition:var(--transition)}
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
    .admin-table tbody tr.pedido-row{cursor:pointer}
    .tbl-wrap{overflow-x:auto}

    .badge-estatus{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-estatus.validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.procesando{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.enviado{background:#ede9fe;color:#7c3aed}
    .badge-estatus.entregado{background:var(--green-bg);color:var(--green)}
    .badge-estatus.cancelado{background:var(--red-bg);color:var(--red)}
    .badge-pago{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--gray-soft);color:var(--gray-muted)}
    .badge-pago.credito{background:var(--blue-bg);color:var(--blue)}
    .badge-pago.contado{background:var(--green-bg);color:var(--green)}
    .badge-items{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--purple-subtle);color:var(--purple)}

    .pedido-detail-row td{background:var(--gray-soft)!important;padding:0!important}
    .pedido-detail-inner{padding:16px 20px}
    .pedido-products{width:100%;border-collapse:collapse;background:var(--white);border-radius:8px;overflow:hidden;border:1px solid var(--border-light)}
    .pedido-products th{padding:8px 12px;font-size:10px;font-weight:700;color:var(--purple);text-transform:uppercase;text-align:left;background:var(--purple-light)}
    .pedido-products td{padding:8px 12px;font-size:12px;border-bottom:1px solid var(--border-light)}
    .pedido-products tr:last-child td{border-bottom:none}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}

    @media(max-width:768px){
        .inv-metrics{grid-template-columns:1fr 1fr}
        .filter-field{min-width:100%}
        .filter-form{flex-direction:column;align-items:stretch}
    }
</style>
@endpush
@section('content')
@php
    $baseQuery = array_filter([
        'busqueda' => $filtros['busqueda'] ?: null,
        'tipo_pago' => $filtros['tipo_pago'] ?: null,
        'fecha_desde' => $filtros['fecha_desde'] ?: null,
        'fecha_hasta' => $filtros['fecha_hasta'] ?: null,
    ]);
    $chipActive = fn ($est = null, $grp = null) => (!$filtros['estatus'] && !$filtros['grupo'] && !$est && !$grp)
        || ($est && $filtros['estatus'] === $est)
        || ($grp && $filtros['grupo'] === $grp);
    $conteoProcesando = (int) ($conteosEstatus['procesando'] ?? 0);
    $conteoEnviado = (int) ($conteosEstatus['enviado'] ?? 0);
    $conteoEntregado = (int) ($conteosEstatus['entregado'] ?? 0);
@endphp

<div class="inv-metrics anim">
    <a class="inv-metric {{ $chipActive(null, 'pendientes') ? 'is-active' : '' }}" href="{{ route('admin.pedidos', array_merge($baseQuery, ['grupo' => 'pendientes'])) }}">
        <div class="accent" style="background:var(--red,#dc2626)"></div>
        <div class="inv-metric-label">Pendientes</div>
        <div class="inv-metric-val">{{ $conteoPendientes }}</div>
        <div class="inv-metric-sub">Por atender</div>
    </a>
    <a class="inv-metric {{ $chipActive('procesando') ? 'is-active' : '' }}" href="{{ route('admin.pedidos', array_merge($baseQuery, ['estatus' => 'procesando'])) }}">
        <div class="accent" style="background:var(--amber,#d97706)"></div>
        <div class="inv-metric-label">En proceso</div>
        <div class="inv-metric-val">{{ $conteoProcesando }}</div>
        <div class="inv-metric-sub">En preparación</div>
    </a>
    <a class="inv-metric {{ $chipActive('enviado') ? 'is-active' : '' }}" href="{{ route('admin.pedidos', array_merge($baseQuery, ['estatus' => 'enviado'])) }}">
        <div class="accent" style="background:var(--blue,#2563eb)"></div>
        <div class="inv-metric-label">Enviados</div>
        <div class="inv-metric-val">{{ $conteoEnviado }}</div>
        <div class="inv-metric-sub">En camino</div>
    </a>
    <a class="inv-metric {{ $chipActive('entregado') ? 'is-active' : '' }}" href="{{ route('admin.pedidos', array_merge($baseQuery, ['estatus' => 'entregado'])) }}">
        <div class="accent" style="background:var(--green,#16a34a)"></div>
        <div class="inv-metric-label">Entregados</div>
        <div class="inv-metric-val">{{ $conteoEntregado }}</div>
        <div class="inv-metric-sub">Completados</div>
    </a>
    <a class="inv-metric {{ $chipActive() ? 'is-active' : '' }}" href="{{ route('admin.pedidos', $baseQuery) }}">
        <div class="accent" style="background:var(--purple,#6B3FA0)"></div>
        <div class="inv-metric-label">Todas</div>
        <div class="inv-metric-val">{{ $totalGeneral }}</div>
        <div class="inv-metric-sub">Pedidos totales</div>
    </a>
</div>

<div class="toolbar anim" style="animation-delay:.04s">
    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.pedidos') }}" class="filter-form">
            @if($filtros['grupo'] && !$filtros['estatus'])
                <input type="hidden" name="grupo" value="{{ $filtros['grupo'] }}">
            @endif
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="busqueda" value="{{ $filtros['busqueda'] }}" placeholder="Folio, proveedor o código…">
            </div>
            <div class="filter-field">
                <label>Estatus</label>
                <select name="estatus">
                    <option value="">Todos los estatus</option>
                    @foreach($estatusOpciones as $key => $label)
                        <option value="{{ $key }}" {{ $filtros['estatus'] === $key ? 'selected' : '' }}>
                            {{ $label }} ({{ $conteosEstatus[$key] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Tipo de pago</label>
                <select name="tipo_pago">
                    <option value="">Todos</option>
                    <option value="credito" {{ $filtros['tipo_pago'] === 'credito' ? 'selected' : '' }}>Crédito</option>
                    <option value="contado" {{ $filtros['tipo_pago'] === 'contado' ? 'selected' : '' }}>Contado</option>
                </select>
            </div>
            <div class="filter-field">
                <label>Desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}">
            </div>
            <div class="filter-field">
                <label>Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosActivos)
                    <a href="{{ route('admin.pedidos') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosActivos)
        <div class="active-filters" style="margin-top:12px;">
            <span>Filtros activos:</span>
            @if($filtros['busqueda'])<span class="active-tag">«{{ $filtros['busqueda'] }}»</span>@endif
            @if($filtros['grupo'] === 'pendientes')<span class="active-tag">Pendientes</span>@endif
            @if($filtros['grupo'] === 'activos')<span class="active-tag">Activos</span>@endif
            @if($filtros['estatus'])<span class="active-tag">{{ $estatusOpciones[$filtros['estatus']] ?? ucfirst($filtros['estatus']) }}</span>@endif
            @if($filtros['tipo_pago'])<span class="active-tag">{{ $filtros['tipo_pago'] === 'credito' ? 'Crédito' : 'Contado' }}</span>@endif
            @if($filtros['fecha_desde'])<span class="active-tag">Desde {{ $filtros['fecha_desde'] }}</span>@endif
            @if($filtros['fecha_hasta'])<span class="active-tag">Hasta {{ $filtros['fecha_hasta'] }}</span>@endif
            @if($filtrosActivos)
                <span class="active-tag">Monto filtrado: ${{ number_format($montoFiltrado, 2) }}</span>
            @endif
        </div>
        @endif
    </div>
</div>

<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Listado de pedidos</h4>
            <div class="adm-section-meta">{{ $pedidos->total() }} resultado{{ $pedidos->total() !== 1 ? 's' : '' }} · clic en una fila para ver productos</div>
        </div>
        <div class="adm-section-toolbar">
            <a href="{{ route('admin.pedidos.excel', request()->query()) }}" class="btn-export">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
        </div>
    </div>
    @if($pedidos->count())
    <div class="tbl-wrap">
        <table class="admin-table" id="tablePedidos">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Cliente</th>
                    <th>Proveedor</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Estatus</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            @foreach($pedidos as $p)
                @php
                    $nombreProv = $p->proveedor?->nombre ?? $p->nombre_proveedor;
                    $codigoProv = $p->proveedor?->id_proveedor ?? $p->codigo_proveedor;
                    $productos = is_array($p->productos) ? $p->productos : [];
                    $numProductos = count($productos);
                @endphp
                <tr class="pedido-row" onclick="togglePedidoDetail('pedido-{{ $p->id }}')">
                    <td style="font-weight:700;color:var(--purple)">{{ $p->folio }}</td>
                    <td>
                        <div style="font-weight:600">{{ $p->nombre_cliente ?? '—' }}</div>
                        @if($p->codigo_cliente)
                            <div style="font-size:11px;color:var(--gray-muted)">{{ $p->codigo_cliente }}</div>
                        @endif
                    </td>
                    <td>
                        <div>{{ $nombreProv ?? '—' }}</div>
                        @if($codigoProv)
                            <div style="font-size:11px;color:var(--gray-muted)">{{ $codigoProv }}</div>
                        @endif
                    </td>
                    <td><span class="badge-items">{{ $numProductos }} ítem{{ $numProductos !== 1 ? 's' : '' }}</span></td>
                    <td style="font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)">${{ number_format($p->total, 2) }}</td>
                    <td><span class="badge-pago {{ $p->tipo_pago }}">{{ $p->tipo_pago === 'credito' ? 'Crédito' : 'Contado' }}</span></td>
                    <td><span class="badge-estatus {{ $p->estatus }}">{{ $estatusOpciones[$p->estatus] ?? ucfirst($p->estatus) }}</span></td>
                    <td style="white-space:nowrap">{{ $p->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
                <tr id="pedido-{{ $p->id }}" class="pedido-detail-row" style="display:none">
                    <td colspan="8">
                        <div class="pedido-detail-inner">
                            @if($numProductos)
                            <table class="pedido-products">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Código</th>
                                        <th style="text-align:right">Cantidad</th>
                                        <th style="text-align:right">Precio unit.</th>
                                        <th style="text-align:right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($productos as $prod)
                                    @php
                                        $cant = (float) ($prod['cantidad'] ?? 0);
                                        $precio = (float) ($prod['precio'] ?? $prod['precio_unitario'] ?? 0);
                                        $sub = $cant * $precio;
                                    @endphp
                                    <tr>
                                        <td style="font-weight:600">{{ $prod['nombre'] ?? '—' }}</td>
                                        <td style="color:var(--purple);font-weight:600">{{ $prod['sku'] ?? $prod['codigo'] ?? '—' }}</td>
                                        <td style="text-align:right;font-variant-numeric:tabular-nums">{{ number_format($cant) }} {{ $prod['unidad'] ?? '' }}</td>
                                        <td style="text-align:right;font-variant-numeric:tabular-nums">${{ number_format($precio, 2) }}</td>
                                        <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums">${{ number_format($sub, 2) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            @else
                            <div style="font-size:13px;color:var(--gray-muted)">Sin detalle de productos registrado.</div>
                            @endif
                            @if($p->notas)
                            <div style="margin-top:12px;font-size:12px;color:var(--gray-muted)"><strong>Notas:</strong> {{ $p->notas }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($pedidos->hasPages())
        <div class="pagination-wrap">{{ $pedidos->links() }}</div>
    @endif
    @else
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        <p>No se encontraron pedidos con los filtros seleccionados.</p>
        @if($filtrosActivos)
            <p style="margin-top:8px;"><a href="{{ route('admin.pedidos') }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>
        @endif
    </div>
    @endif
</div>

@endsection
@push('scripts')
<script>
function togglePedidoDetail(id) {
    var row = document.getElementById(id);
    if (!row) return;
    var open = row.style.display !== 'none';
    document.querySelectorAll('.pedido-detail-row').forEach(function (r) { r.style.display = 'none'; });
    row.style.display = open ? 'none' : 'table-row';
}
</script>
@endpush
