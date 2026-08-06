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
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .inv-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
    .inv-metric{background:var(--white);border:1px solid var(--border-light, var(--border));border-radius:14px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:box-shadow .15s,border-color .15s;text-decoration:none;color:inherit;display:block}
    .inv-metric:hover{border-color:var(--purple-mid,#c4b5e0);box-shadow:var(--shadow-sm)}
    .inv-metric.is-active{border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.12)}
    .inv-metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .inv-metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px}
    .inv-metric-val{font-size:28px;font-weight:700;color:var(--gray-text);line-height:1}
    .inv-metric-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}

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

    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-est.programada{background:var(--purple-subtle);color:var(--purple)}
    .badge-est.pagada{background:var(--green-bg);color:var(--green)}
    .badge-est.cancelada{background:var(--red-bg);color:var(--red)}
    .badge-vencida{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--red-bg);color:var(--red)}
    .fact-link{font-size:12px;font-weight:600;color:var(--purple);text-decoration:none}
    .fact-link:hover{text-decoration:underline}

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
        'fecha_desde' => $filtros['fecha_desde'] ?: null,
        'fecha_hasta' => $filtros['fecha_hasta'] ?: null,
    ]);
    $chipActive = fn ($est = null, $venc = false) => (!$filtros['estatus'] && !$filtros['vencidas'] && !$est && !$venc)
        || ($est && $filtros['estatus'] === $est && !$filtros['vencidas'])
        || ($venc && $filtros['vencidas']);
    $conteoProgramadas = (int) (($conteosEstatus['programada'] ?? 0)
        + ($conteosEstatus['aprobada'] ?? 0)
        + ($conteosEstatus['validada'] ?? 0));
@endphp

<div class="inv-metrics anim">
    <a class="inv-metric {{ $chipActive('pendiente') ? 'is-active' : '' }}" href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'pendiente'])) }}">
        <div class="accent" style="background:var(--red,#dc2626)"></div>
        <div class="inv-metric-label">Pendientes</div>
        <div class="inv-metric-val">{{ $conteoPendientes }}</div>
        <div class="inv-metric-sub">Por pagar</div>
    </a>
    <a class="inv-metric {{ $chipActive('programada') ? 'is-active' : '' }}" href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'programada'])) }}">
        <div class="accent" style="background:var(--amber,#d97706)"></div>
        <div class="inv-metric-label">Programadas</div>
        <div class="inv-metric-val">{{ $conteoProgramadas }}</div>
        <div class="inv-metric-sub">En proceso de pago</div>
    </a>
    <a class="inv-metric {{ $chipActive('pagada') ? 'is-active' : '' }}" href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'pagada'])) }}">
        <div class="accent" style="background:var(--green,#16a34a)"></div>
        <div class="inv-metric-label">Pagadas</div>
        <div class="inv-metric-val">{{ $conteoPagadas }}</div>
        <div class="inv-metric-sub">Ya liquidadas</div>
    </a>
    <a class="inv-metric {{ $chipActive() ? 'is-active' : '' }}" href="{{ route('admin.facturas', $baseQuery) }}">
        <div class="accent" style="background:var(--purple,#6B3FA0)"></div>
        <div class="inv-metric-label">Todas</div>
        <div class="inv-metric-val">{{ $totalGeneral }}</div>
        <div class="inv-metric-sub">Facturas totales</div>
    </a>
</div>

<div class="toolbar anim" style="animation-delay:.04s">
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

<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Listado de facturas</h4>
            <div class="adm-section-meta">
                {{ $facturas->total() }} resultado{{ $facturas->total() !== 1 ? 's' : '' }}
                @if($filtrosActivos)
                    · monto filtrado: <strong style="color:var(--green)">${{ number_format($montoFiltrado, 2) }}</strong>
                @endif
            </div>
        </div>
        <div class="adm-section-toolbar">
            <a href="{{ route('admin.facturas.excel', request()->query()) }}" class="btn-export">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
        </div>
    </div>
    @if($facturas->count())
    <div class="tbl-wrap">
        <table class="admin-table" id="tableFacturas">
            <thead>
                <tr>
                    <th>Folio CFDI</th>
                    <th>Proveedor</th>
                    <th>Monto</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th>Estatus</th>
                    <th>Vencimiento</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($facturas as $f)
                @php
                    $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
                    $diasVencido = $vencida ? (int) $f->fecha_vencimiento->diffInDays(now()) : 0;
                @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $f->folio_cfdi ?? '—' }}</td>
                    <td>
                        <div style="font-weight:600">{{ $f->proveedor?->nombre ?? $f->codigo_proveedor ?? '—' }}</div>
                        @if($f->codigo_proveedor)
                            <div style="font-size:11px;color:var(--gray-muted)">{{ $f->codigo_proveedor }}</div>
                        @endif
                    </td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto, 2) }}</td>
                    <td style="font-variant-numeric:tabular-nums;color:var(--gray-muted)">${{ number_format($f->monto_iva, 2) }}</td>
                    <td style="font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)">${{ number_format($f->total, 2) }}</td>
                    <td>
                        <span class="badge-est {{ $f->estatus }}">{{ $estatusOpciones[$f->estatus] ?? ucfirst($f->estatus) }}</span>
                        @if($vencida)
                            <span class="badge-vencida">{{ $diasVencido }} día{{ $diasVencido === 1 ? '' : 's' }}</span>
                        @endif
                    </td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }};white-space:nowrap">
                        {{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td>
                        @if($f->codigo_proveedor)
                            <a href="{{ route('admin.proveedor-facturas', $f->codigo_proveedor) }}" class="fact-link">Detalle →</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
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
