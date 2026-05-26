@extends('layouts.admin')
@section('title', 'Facturas')
@section('hero')
<div class="hero-band">
    <h1>Facturas de Proveedores</h1>
    <p>Control de facturación de proveedores</p>
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
    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-est.pagada{background:var(--green-bg);color:var(--green)}
    .badge-est.cancelada{background:var(--red-bg);color:var(--red)}
    .badge-vencida{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--red-bg);color:var(--red);margin-left:6px}
    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}
    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.filter-field{min-width:100%}.filter-form{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')
@php
    $baseQuery = array_filter([
        'busqueda' => $filtros['busqueda'] ?: null,
        'fecha_desde' => $filtros['fecha_desde'] ?: null,
        'fecha_hasta' => $filtros['fecha_hasta'] ?: null,
    ]);
    $chipActive = fn ($est = null, $venc = false) => (!$filtros['estatus'] && !$filtros['vencidas'] && !$est && !$venc)
        || ($est && $filtros['estatus'] === $est && !$filtros['vencidas'])
        || ($venc && $filtros['vencidas']);
@endphp

<div class="toolbar">
    <div class="toolbar-top">
        <div class="filter-group">
            <a href="{{ route('admin.facturas', $baseQuery) }}" class="filter-btn {{ $chipActive() ? 'active' : '' }}">
                Todas <span class="filter-count">{{ $totalGeneral }}</span>
            </a>
            <a href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'pendiente'])) }}" class="filter-btn warn {{ $chipActive('pendiente') ? 'active' : '' }}">
                Pendientes <span class="filter-count">{{ $conteosEstatus['pendiente'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'pagada'])) }}" class="filter-btn ok {{ $chipActive('pagada') ? 'active' : '' }}">
                Pagadas <span class="filter-count">{{ $conteosEstatus['pagada'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'cancelada'])) }}" class="filter-btn danger {{ $chipActive('cancelada') ? 'active' : '' }}">
                Canceladas <span class="filter-count">{{ $conteosEstatus['cancelada'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.facturas', array_merge($baseQuery, ['vencidas' => '1'])) }}" class="filter-btn danger {{ $chipActive(null, true) ? 'active' : '' }}">
                Vencidas <span class="filter-count">{{ $conteoVencidas }}</span>
            </a>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <span class="badge-count">{{ $facturas->total() }} resultado{{ $facturas->total() !== 1 ? 's' : '' }}</span>
            <a href="{{ route('admin.facturas.excel', request()->query()) }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:11px;font-weight:600;color:#fff;background:#059669;border-radius:6px;text-decoration:none">Exportar Excel</a>
        </div>
    </div>

    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.facturas') }}" class="filter-form">
            @if($filtros['vencidas'])
                <input type="hidden" name="vencidas" value="1">
            @endif
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="busqueda" value="{{ $filtros['busqueda'] }}" placeholder="Folio, código o proveedor…">
            </div>
            <div class="filter-field">
                <label>Estatus</label>
                <select name="estatus" {{ $filtros['vencidas'] ? 'disabled' : '' }}>
                    <option value="">Todos los estatus</option>
                    @foreach($estatusOpciones as $key => $label)
                        <option value="{{ $key }}" {{ $filtros['estatus'] === $key ? 'selected' : '' }}>
                            {{ $label }} ({{ $conteosEstatus[$key] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Vence desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}">
            </div>
            <div class="filter-field">
                <label>Vence hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosActivos)
                    <a href="{{ route('admin.facturas') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosActivos)
        <div class="active-filters" style="margin-top:12px;">
            <span>Filtros activos:</span>
            @if($filtros['busqueda'])<span class="active-tag">«{{ $filtros['busqueda'] }}»</span>@endif
            @if($filtros['vencidas'])<span class="active-tag">Vencidas</span>@endif
            @if($filtros['estatus'])<span class="active-tag">{{ $estatusOpciones[$filtros['estatus']] ?? ucfirst($filtros['estatus']) }}</span>@endif
            @if($filtros['fecha_desde'])<span class="active-tag">Vence desde {{ $filtros['fecha_desde'] }}</span>@endif
            @if($filtros['fecha_hasta'])<span class="active-tag">Vence hasta {{ $filtros['fecha_hasta'] }}</span>@endif
        </div>
        @endif
    </div>
</div>

<div class="admin-table-wrap">
@if($facturas->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Folio CFDI</th>
                <th>Proveedor</th>
                <th>Monto</th>
                <th>IVA</th>
                <th>Total</th>
                <th>Estatus</th>
                <th>Vencimiento</th>
            </tr>
        </thead>
        <tbody>
        @foreach($facturas as $f)
            @php $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast(); @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $f->folio_cfdi }}</td>
                <td>
                    <div>{{ $f->proveedor?->nombre ?? $f->codigo_proveedor ?? '—' }}</div>
                    @if($f->codigo_proveedor)<div style="font-size:11px;color:var(--gray-muted);">{{ $f->codigo_proveedor }}</div>@endif
                </td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto, 2) }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto_iva, 2) }}</td>
                <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                <td>
                    <span class="badge-est {{ $f->estatus }}">{{ $estatusOpciones[$f->estatus] ?? ucfirst($f->estatus) }}</span>
                    @if($vencida)<span class="badge-vencida">VENCIDA</span>@endif
                </td>
                <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">
                    {{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($facturas->hasPages())
        <div class="pagination-wrap">{{ $facturas->links() }}</div>
    @endif
@else
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p>No se encontraron facturas con los filtros seleccionados.</p>
        @if($filtrosActivos)
            <p style="margin-top:8px;"><a href="{{ route('admin.facturas') }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>
        @endif
    </div>
@endif
</div>
@endsection
