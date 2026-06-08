@extends('layouts.admin')
@section('title', 'Documentos Fiscales')
@section('hero')
<div class="hero-band">
    <h1>Documentos Fiscales de Proveedores</h1>
    <p>Revisión y validación de documentos subidos por proveedores</p>
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
    .badge-doc{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-doc.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-doc.aprobado{background:var(--green-bg);color:var(--green)}
    .badge-doc.rechazado{background:var(--red-bg);color:var(--red)}
    .tipo-badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px;background:var(--purple-light);color:var(--purple)}
    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}
    @media(max-width:768px){.adm-summary{flex-direction:column;align-items:flex-start}.filter-field{min-width:100%}.filter-form{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')
@php
    $baseQuery = array_filter([
        'busqueda' => $filtros['busqueda'] ?: null,
        'tipo' => $filtros['tipo'] ?: null,
        'fecha_desde' => $filtros['fecha_desde'] ?: null,
        'fecha_hasta' => $filtros['fecha_hasta'] ?: null,
    ]);
    $chipActive = fn ($est = null) => (!$filtros['estatus'] && !$est) || ($est && $filtros['estatus'] === $est);
    $labelTipo = fn ($t) => $tipoLabels[$t] ?? ucfirst(str_replace('_', ' ', $t));
    $pctColor = $pctAprobados >= 70 ? 'var(--green)' : ($pctAprobados >= 40 ? 'var(--amber)' : 'var(--red)');
    $pctBg = $pctAprobados >= 70 ? 'var(--green-bg)' : ($pctAprobados >= 40 ? 'var(--amber-bg)' : 'var(--red-bg)');
@endphp

<div class="adm-summary anim">
    <div class="adm-summary-main">
        <div class="adm-summary-pct">{{ $totalGeneral }}</div>
        <div class="adm-summary-label">Documentos totales</div>
    </div>
    <div class="adm-summary-metrics">
        <div>
            <div class="adm-metric-label">Pendientes</div>
            <div class="adm-metric-val" style="color:{{ $conteoPendientes > 0 ? 'var(--amber)' : 'var(--green)' }}">{{ $conteoPendientes }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Aprobados</div>
            <div class="adm-metric-val" style="color:var(--green)">{{ $conteoAprobados }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Rechazados</div>
            <div class="adm-metric-val" style="color:{{ $conteoRechazados > 0 ? 'var(--red)' : 'var(--gray-muted)' }}">{{ $conteoRechazados }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Tasa de aprobación</div>
            <div class="adm-metric-val" style="color:{{ $pctColor }}">{{ $pctAprobados }}%</div>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
        <div class="adm-summary-badge" style="background:{{ $pctBg }};color:{{ $pctColor }}">
            {{ $proveedoresConPendientes }} proveedor{{ $proveedoresConPendientes !== 1 ? 'es' : '' }} con documentos pendientes
        </div>
        <a href="{{ route('admin.fiscal') }}" class="adm-summary-badge" style="background:var(--purple-subtle);color:var(--purple)">Ver cumplimiento fiscal →</a>
    </div>
</div>

<div class="toolbar anim" style="animation-delay:.04s">
    <div class="toolbar-top">
        <div class="filter-group">
            <a href="{{ route('admin.documentos', $baseQuery) }}" class="filter-btn {{ $chipActive() ? 'active' : '' }}">
                Todos <span class="filter-count">{{ $totalGeneral }}</span>
            </a>
            <a href="{{ route('admin.documentos', array_merge($baseQuery, ['estatus' => 'pendiente'])) }}" class="filter-btn warn {{ $chipActive('pendiente') ? 'active' : '' }}">
                Pendientes <span class="filter-count">{{ $conteosEstatus['pendiente'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.documentos', array_merge($baseQuery, ['estatus' => 'aprobado'])) }}" class="filter-btn ok {{ $chipActive('aprobado') ? 'active' : '' }}">
                Aprobados <span class="filter-count">{{ $conteosEstatus['aprobado'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.documentos', array_merge($baseQuery, ['estatus' => 'rechazado'])) }}" class="filter-btn danger {{ $chipActive('rechazado') ? 'active' : '' }}">
                Rechazados <span class="filter-count">{{ $conteosEstatus['rechazado'] ?? 0 }}</span>
            </a>
        </div>
        <span class="badge-count">{{ $documentos->total() }} resultado{{ $documentos->total() !== 1 ? 's' : '' }}</span>
    </div>

    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.documentos') }}" class="filter-form">
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="busqueda" value="{{ $filtros['busqueda'] }}" placeholder="Proveedor, tipo o notas…">
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
                <label>Tipo de documento</label>
                <select name="tipo">
                    <option value="">Todos los tipos</option>
                    @foreach($tipos as $t)
                        <option value="{{ $t }}" {{ $filtros['tipo'] === $t ? 'selected' : '' }}>{{ $labelTipo($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Subido desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}">
            </div>
            <div class="filter-field">
                <label>Subido hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosActivos)
                    <a href="{{ route('admin.documentos') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosActivos)
        <div class="active-filters" style="margin-top:12px;">
            <span>Filtros activos:</span>
            @if($filtros['busqueda'])<span class="active-tag">«{{ $filtros['busqueda'] }}»</span>@endif
            @if($filtros['estatus'])<span class="active-tag">{{ $estatusOpciones[$filtros['estatus']] ?? ucfirst($filtros['estatus']) }}</span>@endif
            @if($filtros['tipo'])<span class="active-tag">{{ $labelTipo($filtros['tipo']) }}</span>@endif
            @if($filtros['fecha_desde'])<span class="active-tag">Desde {{ $filtros['fecha_desde'] }}</span>@endif
            @if($filtros['fecha_hasta'])<span class="active-tag">Hasta {{ $filtros['fecha_hasta'] }}</span>@endif
        </div>
        @endif
    </div>
</div>

<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Listado de documentos</h4>
            <div class="adm-section-meta">{{ $documentos->total() }} resultado{{ $documentos->total() !== 1 ? 's' : '' }} · ordenados por prioridad de revisión</div>
        </div>
        <div class="adm-section-toolbar">
            <a href="{{ route('admin.documentos.excel', request()->query()) }}" class="btn-export">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
        </div>
    </div>
    @if($documentos->count())
    <div class="tbl-wrap">
        <table class="admin-table" id="tableDocumentos">
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Tipo</th>
                    <th>Estatus</th>
                    <th>Notas</th>
                    <th>Fecha revisión</th>
                    <th>Subido</th>
                </tr>
            </thead>
            <tbody>
            @foreach($documentos as $d)
                <tr>
                    <td>
                        <div style="font-weight:600">{{ $d->proveedor?->nombre ?? $d->proveedor?->usuario ?? 'ID: '.$d->proveedor_id }}</div>
                        @if($d->proveedor?->codigo_compras)<div style="font-size:11px;color:var(--gray-muted)">{{ $d->proveedor->codigo_compras }}</div>@endif
                    </td>
                    <td><span class="tipo-badge">{{ $labelTipo($d->tipo) }}</span></td>
                    <td><span class="badge-doc {{ $d->estatus }}">{{ $estatusOpciones[$d->estatus] ?? ucfirst($d->estatus) }}</span></td>
                    <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--gray-muted)" title="{{ $d->notas_revision }}">{{ $d->notas_revision ?? '—' }}</td>
                    <td style="color:var(--gray-muted);white-space:nowrap">{{ $d->revisado_at?->format('d/m/Y') ?? '—' }}</td>
                    <td style="color:var(--gray-muted);white-space:nowrap">{{ $d->created_at?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($documentos->hasPages())
        <div class="pagination-wrap">{{ $documentos->links() }}</div>
    @endif
    @else
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p>No se encontraron documentos con los filtros seleccionados.</p>
        @if($filtrosActivos)
            <p style="margin-top:8px;"><a href="{{ route('admin.documentos') }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>
        @endif
    </div>
    @endif
</div>
@endsection
