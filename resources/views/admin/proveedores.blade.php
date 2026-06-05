@extends('layouts.admin')
@section('title', 'Proveedores')
@section('hero')
<div class="hero-band">
    <h1>Proveedores</h1>
    <p>Gestión de proveedores, órdenes de compra y facturas</p>
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
    .filter-btn.ok.active{background:var(--green);border-color:var(--green)}
    .filter-btn.warn.active{background:var(--amber);border-color:var(--amber)}
    .filter-btn.danger.active{background:var(--red);border-color:var(--red)}
    .filter-count{font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;background:rgba(0,0,0,.08);line-height:1.2}
    .filter-btn.active .filter-count{background:rgba(255,255,255,.25)}
    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin-bottom:14px}
    .filter-form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:140px;flex:1}
    .filter-field.search-field{flex:2;min-width:200px}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px}
    .filter-field input,.filter-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .filter-field input:focus,.filter-field select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .filter-actions{display:flex;gap:8px;align-items:center;padding-bottom:1px;flex-wrap:wrap}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-primary:hover{background:var(--purple-dark)}
    .btn-outline{padding:9px 16px;background:var(--white);color:var(--gray-text);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;text-decoration:none;display:inline-flex;align-items:center}
    .btn-outline:hover{border-color:var(--purple);color:var(--purple)}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:12px;font-weight:600;color:#fff;background:#059669;border-radius:8px;text-decoration:none}
    .btn-export:hover{background:#047857}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}
    .active-filters{font-size:12px;color:var(--gray-muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:12px}
    .active-tag{background:var(--purple-subtle);color:var(--purple);padding:3px 10px;border-radius:999px;font-weight:600;font-size:11px}

    .prov-panel{display:none}.prov-panel.active{display:block}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}

    .score-bar{width:80px;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .score-fill{height:100%;border-radius:4px}
    .score-high .score-fill{background:var(--green)}
    .score-mid .score-fill{background:var(--amber)}
    .score-low .score-fill{background:var(--red)}
    .pct-cell{display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap}
    .pct-val{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}

    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.ok,.badge-est.aprobada,.badge-est.pagada,.badge-est.completada,.badge-est.entregado{background:var(--green-bg);color:var(--green)}
    .badge-est.warn,.badge-est.pendiente,.badge-est.validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-est.err,.badge-est.rechazado,.badge-est.cancelado,.badge-est.cancelada{background:var(--red-bg);color:var(--red)}
    .badge-est.en_proceso,.badge-est.procesando,.badge-est.enviado{background:var(--blue-bg);color:var(--blue)}

    .badge-vencida{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--red-bg);color:var(--red);margin-left:6px}
    .btn-sm{padding:6px 14px;font-size:12px;font-weight:600;border-radius:8px;border:1.5px solid var(--red);cursor:pointer;font-family:inherit;background:var(--red);color:#fff}
    .btn-sm:hover{background:#b91c1c}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}
    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green);font-weight:500}

    .productos-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
    .producto-card{background:var(--white);border:1px solid var(--border-light);border-radius:12px;padding:14px;display:flex;gap:12px;align-items:center;transition:box-shadow .2s}
    .producto-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.06)}
    .producto-img{width:56px;height:56px;border-radius:10px;background:var(--purple-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden}
    .producto-img img{width:100%;height:100%;object-fit:cover}
    .producto-placeholder{font-size:16px;font-weight:800;color:var(--purple)}
    .producto-info{flex:1;min-width:0}
    .producto-nombre{font-size:13px;font-weight:700;color:var(--gray-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .producto-codigo{font-size:11px;color:var(--gray-muted);margin-top:2px}
    .producto-precio{font-size:14px;font-weight:800;color:var(--green);margin-top:4px}
    .producto-stock{font-size:11px;color:var(--gray-muted);margin-top:2px}

    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.toolbar{flex-direction:column;align-items:stretch}.filter-form{width:100%;flex-direction:column;align-items:stretch}.filter-field{min-width:100%}.filter-group{width:100%}.prov-tabs{width:100%;overflow-x:auto}.productos-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif

@php $tab = $tabActiva ?? 'proveedores'; @endphp

<div class="toolbar" style="margin-bottom:14px;">
    <div class="toolbar-top">
        <div class="filter-group">
            <button type="button" class="filter-btn prov-tab-btn {{ $tab === 'proveedores' ? 'active' : '' }}" data-tab="proveedores" onclick="switchProvTab('proveedores', this)">
                Proveedores <span class="filter-count">{{ $totalProveedores }}</span>
            </button>
            <button type="button" class="filter-btn prov-tab-btn {{ $tab === 'forecast' ? 'active' : '' }}" data-tab="forecast" onclick="switchProvTab('forecast', this)">
                Forecast <span class="filter-count">{{ $proveedores->total() }}</span>
            </button>
            <button type="button" class="filter-btn prov-tab-btn {{ $tab === 'ordenes' ? 'active' : '' }}" data-tab="ordenes" onclick="switchProvTab('ordenes', this)">
                Órdenes <span class="filter-count">{{ $totalOrdenes }}</span>
            </button>
            <button type="button" class="filter-btn prov-tab-btn {{ $tab === 'facturas' ? 'active' : '' }}" data-tab="facturas" onclick="switchProvTab('facturas', this)">
                Facturas <span class="filter-count">{{ $conteoFacturasPendientes }}</span>
            </button>
        </div>
        <span class="badge-count" id="prov-panel-count">
            @if($tab === 'ordenes'){{ $ordenes->count() }} resultados
            @elseif($tab === 'facturas'){{ $facturasPendientes->count() }} resultados
            @else{{ $proveedores->total() }} resultados
            @endif
        </span>
    </div>
</div>

{{-- ═══ TAB PROVEEDORES ═══ --}}
<div class="prov-panel {{ $tab === 'proveedores' ? 'active' : '' }}" id="panel-proveedores" data-count="{{ $proveedores->total() }} resultados">
    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.proveedores') }}" class="filter-form">
            <input type="hidden" name="tab" value="proveedores">
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveOc])
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveFact])
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="busqueda" value="{{ $filtrosProv['busqueda'] }}" placeholder="Nombre, código, correo o usuario…">
            </div>
            <div class="filter-field">
                <label>Estado</label>
                <select name="f_activo">
                    <option value="">Todos</option>
                    <option value="1" {{ ($filtrosProv['activo'] ?? '') === '1' ? 'selected' : '' }}>Activos ({{ $conteoActivos }})</option>
                    <option value="0" {{ ($filtrosProv['activo'] ?? '') === '0' ? 'selected' : '' }}>Inactivos ({{ $conteoInactivos }})</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosProvActivos)
                    <a href="{{ route('admin.proveedores', ['tab' => 'proveedores']) }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosProvActivos)
        <div class="active-filters">
            <span>Filtros activos:</span>
            @if($filtrosProv['busqueda'])<span class="active-tag">«{{ $filtrosProv['busqueda'] }}»</span>@endif
            @if(($filtrosProv['activo'] ?? '') === '1')<span class="active-tag">Activos</span>@endif
            @if(($filtrosProv['activo'] ?? '') === '0')<span class="active-tag">Inactivos</span>@endif
        </div>
        @endif
    </div>
    <div class="admin-table-wrap">
    @if($proveedores->count())
        <table class="admin-table">
            <thead><tr><th>Código</th><th>Nombre</th><th>Correo</th><th>OTIF</th><th>Entrega</th><th>Puntualidad</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>
            @foreach($proveedores as $p)
                @php $m = $metricasProveedores[$p->id] ?? []; @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $p->codigo_compras ?? '—' }}</td>
                    <td style="font-weight:600">{{ $p->nombre ?? '—' }}</td>
                    <td>{{ $p->correo ?? '—' }}</td>
                    <td>
                        <div class="pct-cell">
                            <div class="score-bar {{ $m['score_class'] ?? 'score-low' }}"><div class="score-fill" style="width:{{ $p->score_total }}%"></div></div>
                            <span class="pct-val"><strong>{{ number_format($p->score_total, 0) }}%</strong>@include('partials.trend-arrow', ['value' => $m['trend_otif'] ?? 0, 'size' => '11'])</span>
                        </div>
                    </td>
                    <td><span class="pct-val">{{ number_format($p->score_entrega, 0) }}%@include('partials.trend-arrow', ['value' => $m['trend_entrega'] ?? 0, 'size' => '11'])</span></td>
                    <td><span class="pct-val">{{ number_format($p->score_puntualidad, 0) }}%@include('partials.trend-arrow', ['value' => $m['trend_puntualidad'] ?? 0, 'size' => '11'])</span></td>
                    <td><span class="badge-est {{ $p->activo ? 'ok' : 'err' }}">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.proveedores.eliminar', $p) }}" onsubmit="return confirm('¿Eliminar a {{ addslashes($p->nombre ?? $p->usuario) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if($proveedores->hasPages())<div class="pagination-wrap">{{ $proveedores->links() }}</div>@endif
    @else
        <div class="empty-state">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <p>No se encontraron proveedores con los filtros seleccionados.</p>
            @if($filtrosProvActivos)<p style="margin-top:8px;"><a href="{{ route('admin.proveedores', ['tab' => 'proveedores']) }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>@endif
        </div>
    @endif
    </div>
</div>

{{-- ═══ TAB FORECAST ═══ --}}
<div class="prov-panel {{ $tab === 'forecast' ? 'active' : '' }}" id="panel-forecast" data-count="{{ $proveedores->total() }} resultados">
    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.proveedores') }}" class="filter-form">
            <input type="hidden" name="tab" value="forecast">
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveOc])
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveFact])
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="busqueda" value="{{ $filtrosProv['busqueda'] }}" placeholder="Nombre, código o correo…">
            </div>
            <div class="filter-field">
                <label>Estado</label>
                <select name="f_activo">
                    <option value="">Todos</option>
                    <option value="1" {{ ($filtrosProv['activo'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
                    <option value="0" {{ ($filtrosProv['activo'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosProvActivos)<a href="{{ route('admin.proveedores', ['tab' => 'forecast']) }}" class="btn-outline">Limpiar</a>@endif
            </div>
        </form>
    </div>
    <div class="admin-table-wrap">
    @if($proveedores->count())
        <table class="admin-table">
            <thead><tr><th>Código</th><th>Proveedor</th><th>OTIF</th><th>Forecast %</th><th>Compras último trimestre</th><th>Estimado próximo mes</th></tr></thead>
            <tbody>
            @foreach($proveedores as $p)
                @php
                    $m = $metricasProveedores[$p->id] ?? [];
                    $otifActual = $m['otif_actual'] ?? 0;
                    $forecast = $m['forecast'] ?? $otifActual;
                    $comprasTrim = $m['compras_trim'] ?? 0;
                    $estimado = $m['estimado'] ?? 0;
                @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $p->codigo_compras ?? '—' }}</td>
                    <td style="font-weight:600">{{ $p->nombre ?? $p->usuario }}</td>
                    <td><span class="pct-val">{{ number_format($otifActual, 0) }}%@include('partials.trend-arrow', ['value' => $m['trend_otif'] ?? 0, 'size' => '11'])</span></td>
                    <td>
                        <div class="pct-cell">
                            <div class="score-bar {{ $m['forecast_class'] ?? 'score-low' }}"><div class="score-fill" style="width:{{ $forecast }}%"></div></div>
                            <span class="pct-val"><strong>{{ number_format($forecast, 0) }}%</strong>@include('partials.trend-arrow', ['value' => $m['trend_forecast'] ?? 0, 'size' => '11'])</span>
                        </div>
                    </td>
                    <td style="font-variant-numeric:tabular-nums"><span class="pct-val">${{ number_format($comprasTrim, 2) }}@include('partials.trend-arrow', ['value' => $m['trend_compras'] ?? 0, 'size' => '11'])</span></td>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($estimado, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if($proveedores->hasPages())<div class="pagination-wrap">{{ $proveedores->links() }}</div>@endif
    @else
        <div class="empty-state"><p>No se encontraron proveedores con los filtros seleccionados.</p></div>
    @endif
    </div>
</div>

{{-- ═══ TAB ÓRDENES DE COMPRA ═══ --}}
<div class="prov-panel {{ $tab === 'ordenes' ? 'active' : '' }}" id="panel-ordenes" data-count="{{ $ordenes->count() }} resultados">
    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.proveedores') }}" class="filter-form">
            <input type="hidden" name="tab" value="ordenes">
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveProv])
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveFact])
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="f_oc_proveedor" value="{{ $filtrosOc['proveedor'] ?? '' }}" placeholder="Proveedor, código o producto…">
            </div>
            <div class="filter-field">
                <label>OC #</label>
                <input type="text" name="f_oc_numero" value="{{ $filtrosOc['numero'] ?? '' }}" placeholder="Ej. 12">
            </div>
            <div class="filter-field">
                <label>Producto</label>
                <input type="text" name="f_oc_producto" value="{{ $filtrosOc['producto'] ?? '' }}" placeholder="Nombre producto">
            </div>
            <div class="filter-field">
                <label>Estatus</label>
                <select name="f_oc_estatus">
                    <option value="">Todos los estatus</option>
                    @foreach($estatusOc as $est)
                        <option value="{{ $est }}" {{ ($filtrosOc['estatus'] ?? '') === $est ? 'selected' : '' }}>
                            {{ $estatusOcLabels[$est] ?? ucfirst($est) }} ({{ $conteosOcEstatus[$est] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Vencimiento</label>
                <select name="f_oc_vencida">
                    <option value="">Todas</option>
                    <option value="1" {{ ($filtrosOc['vencida'] ?? '') === '1' ? 'selected' : '' }}>Solo vencidas ({{ $conteoOcVencidas }})</option>
                </select>
            </div>
            <div class="filter-field">
                <label>Desde</label>
                <input type="date" name="f_oc_fecha_desde" value="{{ $filtrosOc['fecha_desde'] ?? '' }}">
            </div>
            <div class="filter-field">
                <label>Hasta</label>
                <input type="date" name="f_oc_fecha_hasta" value="{{ $filtrosOc['fecha_hasta'] ?? '' }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosOcActivos)<a href="{{ route('admin.proveedores', ['tab' => 'ordenes']) }}" class="btn-outline">Limpiar</a>@endif
            </div>
        </form>
        @if($filtrosOcActivos)
        <div class="active-filters">
            <span>Filtros activos:</span>
            @if($filtrosOc['proveedor'])<span class="active-tag">«{{ $filtrosOc['proveedor'] }}»</span>@endif
            @if($filtrosOc['vencida'])<span class="active-tag">Vencidas</span>@endif
            @if($filtrosOc['estatus'])<span class="active-tag">{{ $estatusOcLabels[$filtrosOc['estatus']] ?? $filtrosOc['estatus'] }}</span>@endif
        </div>
        @endif
    </div>
    <div class="admin-table-wrap">
    @if($ordenes->count())
        <table class="admin-table">
            <thead><tr><th>Proveedor</th><th>Orden</th><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>Estatus</th><th>Vencimiento</th></tr></thead>
            <tbody>
            @foreach($ordenes as $o)
                @php
                    $lineasOc = $o->productos ?? [];
                    $proveedorNombre = $o->proveedor?->nombre ?? $o->proveedor?->usuario ?? '—';
                    $proveedorCodigo = $o->proveedor?->codigo_compras ?? $o->codigo_proveedor ?? '';
                    $vencimiento = $o->created_at->copy()->addDays(30);
                    $vencida = $o->estatus !== 'completada' && $vencimiento->isPast();
                @endphp
                @forelse($lineasOc as $prod)
                <tr>
                    <td><div style="font-weight:600">{{ $proveedorNombre }}</div><div style="font-size:10px;color:var(--gray-muted)">{{ $proveedorCodigo }}</div></td>
                    <td style="font-weight:700;color:var(--purple)">#{{ $o->id }}</td>
                    <td>{{ $o->created_at->format('d/m/Y') }}</td>
                    <td>{{ $prod['nombre'] ?? '—' }}</td>
                    <td style="font-variant-numeric:tabular-nums">{{ number_format($prod['cantidad'] ?? 0) }} {{ $prod['unidad'] ?? '' }}</td>
                    <td><span class="badge-est {{ $o->estatus }}">{{ $estatusOcLabels[$o->estatus] ?? ucfirst(str_replace('_', ' ', $o->estatus)) }}</span>@if($vencida)<span class="badge-vencida">VENCIDA</span>@endif</td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">{{ $vencimiento->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td><div style="font-weight:600">{{ $proveedorNombre }}</div><div style="font-size:10px;color:var(--gray-muted)">{{ $proveedorCodigo }}</div></td>
                    <td style="font-weight:700;color:var(--purple)">#{{ $o->id }}</td>
                    <td>{{ $o->created_at->format('d/m/Y') }}</td>
                    <td colspan="2">—</td>
                    <td><span class="badge-est {{ $o->estatus }}">{{ $estatusOcLabels[$o->estatus] ?? ucfirst(str_replace('_', ' ', $o->estatus)) }}</span>@if($vencida)<span class="badge-vencida">VENCIDA</span>@endif</td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }}">{{ $vencimiento->format('d/m/Y') }}</td>
                </tr>
                @endforelse
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <p>No se encontraron órdenes con los filtros seleccionados.</p>
            @if($filtrosOcActivos)<p style="margin-top:8px;"><a href="{{ route('admin.proveedores', ['tab' => 'ordenes']) }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>@endif
        </div>
    @endif
    </div>
</div>

{{-- ═══ TAB FACTURAS PENDIENTES ═══ --}}
<div class="prov-panel {{ $tab === 'facturas' ? 'active' : '' }}" id="panel-facturas" data-count="{{ $facturasPendientes->count() }} resultados">
    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.proveedores') }}" class="filter-form">
            <input type="hidden" name="tab" value="facturas">
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveProv])
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveOc])
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="f_fact_folio" value="{{ $filtrosFact['folio'] ?? '' }}" placeholder="Folio o código proveedor…">
            </div>
            <div class="filter-field">
                <label>Proveedor</label>
                <input type="text" name="f_fact_proveedor" value="{{ $filtrosFact['proveedor'] ?? '' }}" placeholder="Código compras">
            </div>
            <div class="filter-field">
                <label>Vencimiento</label>
                <select name="f_fact_vencidas">
                    <option value="">Todas las pendientes</option>
                    <option value="1" {{ ($filtrosFact['vencidas'] ?? '') === '1' ? 'selected' : '' }}>Solo vencidas ({{ $conteoFacturasVencidas }})</option>
                </select>
            </div>
            <div class="filter-field">
                <label>Vence desde</label>
                <input type="date" name="f_fact_vence_desde" value="{{ $filtrosFact['vence_desde'] ?? '' }}">
            </div>
            <div class="filter-field">
                <label>Vence hasta</label>
                <input type="date" name="f_fact_vence_hasta" value="{{ $filtrosFact['vence_hasta'] ?? '' }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosFactActivos)<a href="{{ route('admin.proveedores', ['tab' => 'facturas']) }}" class="btn-outline">Limpiar</a>@endif
                <a href="{{ route('admin.facturas-pendientes.excel') }}" class="btn-export">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar Excel
                </a>
            </div>
        </form>
        @if($filtrosFactActivos)
        <div class="active-filters">
            <span>Filtros activos:</span>
            @if($filtrosFact['folio'])<span class="active-tag">Folio «{{ $filtrosFact['folio'] }}»</span>@endif
            @if($filtrosFact['proveedor'])<span class="active-tag">{{ $filtrosFact['proveedor'] }}</span>@endif
            @if($filtrosFact['vencidas'])<span class="active-tag">Vencidas</span>@endif
        </div>
        @endif
    </div>
    <div class="admin-table-wrap">
    @if($facturasPendientes->count())
        <table class="admin-table">
            <thead><tr><th>Proveedor</th><th>Código</th><th>Total</th><th>Vencimiento</th><th>Días vencido</th></tr></thead>
            <tbody>
            @foreach($facturasPendientes as $idx => $f)
                @php
                    $vencida = $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
                    $diasVencido = $vencida ? (int) $f->fecha_vencimiento->diffInDays(now()) : 0;
                    $provF = \App\Models\ProveedorUser::where('codigo_compras', $f->codigo_proveedor)->first();
                    $nombreProv = $provF ? $provF->nombre : $f->codigo_proveedor;
                    // Productos del proveedor
                    $productosP = \App\Models\Producto::where('activo', true)->limit(6)->get();
                @endphp
                <tr style="cursor:pointer" onclick="toggleProductos('prod-{{ $idx }}')">
                    <td style="font-weight:700;color:var(--purple)">{{ $nombreProv }}</td>
                    <td style="color:var(--gray-muted)">{{ $f->codigo_proveedor }}</td>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">{{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        @if($vencida)<span style="color:var(--red);font-weight:700">{{ $diasVencido }} días</span>
                        @else<span style="color:var(--gray-muted)">Vigente</span>@endif
                    </td>
                </tr>
                <tr id="prod-{{ $idx }}" class="productos-row" style="display:none">
                    <td colspan="5" style="padding:16px;background:var(--gray-soft)">
                        <div style="font-size:13px;font-weight:700;color:var(--gray-text);margin-bottom:12px">Productos de {{ $nombreProv }}</div>
                        <table style="width:100%;border-collapse:collapse;background:var(--white);border-radius:8px;overflow:hidden;border:1px solid var(--border-light)">
                            <thead>
                                <tr style="background:var(--purple-light)">
                                    <th style="padding:8px 12px;font-size:10px;font-weight:700;color:var(--purple);text-transform:uppercase;text-align:left">Código</th>
                                    <th style="padding:8px 12px;font-size:10px;font-weight:700;color:var(--purple);text-transform:uppercase;text-align:left">Producto</th>
                                    <th style="padding:8px 12px;font-size:10px;font-weight:700;color:var(--purple);text-transform:uppercase;text-align:right">Precio</th>
                                    <th style="padding:8px 12px;font-size:10px;font-weight:700;color:var(--purple);text-transform:uppercase;text-align:right">Stock</th>
                                    <th style="padding:8px 12px;font-size:10px;font-weight:700;color:var(--purple);text-transform:uppercase;text-align:left">Unidad</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($productosP as $prod)
                                <tr style="border-bottom:1px solid var(--border-light)">
                                    <td style="padding:8px 12px;font-size:12px;font-weight:700;color:var(--purple)">{{ $prod->codigo }}</td>
                                    <td style="padding:8px 12px;font-size:12px;font-weight:600">{{ $prod->nombre }}</td>
                                    <td style="padding:8px 12px;font-size:12px;font-weight:700;color:var(--green);text-align:right">${{ number_format($prod->precio, 2) }}</td>
                                    <td style="padding:8px 12px;font-size:12px;font-weight:600;text-align:right">{{ number_format($prod->stock) }}</td>
                                    <td style="padding:8px 12px;font-size:12px;color:var(--gray-muted)">{{ $prod->unidad_venta }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <a href="{{ route('admin.proveedor-facturas', $f->codigo_proveedor) }}" style="display:inline-block;margin-top:12px;font-size:12px;color:var(--purple);font-weight:600;text-decoration:none">Ver detalle completo →</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">
            <p>No se encontraron facturas con los filtros seleccionados.</p>
            @if($filtrosFactActivos)<p style="margin-top:8px;"><a href="{{ route('admin.proveedores', ['tab' => 'facturas']) }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>@endif
        </div>
    @endif
    </div>
</div>

@endsection
@push('scripts')
<script>
function switchProvTab(tab, btn) {
    document.querySelectorAll('.prov-tab-btn').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.prov-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById('panel-' + tab);
    if (panel) panel.classList.add('active');
    (btn || document.querySelector('.prov-tab-btn[data-tab="' + tab + '"]'))?.classList.add('active');
    const countEl = document.getElementById('prov-panel-count');
    if (panel && countEl && panel.dataset.count) countEl.textContent = panel.dataset.count;
}

function toggleProductos(id) {
    const row = document.getElementById(id);
    if (row.style.display === 'none') {
        // Cerrar todos los demás
        document.querySelectorAll('.productos-row').forEach(r => r.style.display = 'none');
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    const tab = @json($tabActiva ?? 'proveedores');
    if (tab && tab !== 'proveedores') switchProvTab(tab);
});
</script>
@endpush
