@extends('layouts.admin')
@section('title', 'Pagos al proveedor')
@section('hero')
<div class="hero-band">
    <h1>Pagos al proveedor</h1>
    <p>Proveedores con facturas pendientes de pago</p>
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
    .filter-actions{display:flex;gap:8px;align-items:center;padding-bottom:1px}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-primary:hover{background:var(--purple-dark)}
    .btn-outline{padding:9px 16px;background:var(--white);color:var(--gray-text);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;text-decoration:none}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:14px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tbody tr.prov-row{cursor:pointer}
    .admin-table tbody tr.prov-row:hover td{background:var(--purple-subtle)}
    .admin-table tbody tr.row-nuevo td{background:#f5f9ff}
    /* Patrón "visto": punto azul (nivel padre con items nuevos) y rojo (item sin ver) */
    .dot-azul{display:inline-block;width:9px;height:9px;border-radius:50%;background:#2563eb;margin-right:7px;vertical-align:middle;animation:dotBlink 1.3s ease-in-out infinite}
    .dot-rojo{display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc2626;margin-right:7px;vertical-align:middle;animation:dotBlink 1.2s ease-in-out infinite}
    @keyframes dotBlink{0%,100%{opacity:1}50%{opacity:.3}}
    .tbl-wrap{overflow-x:auto}
    .code-link{font-weight:700;color:var(--purple);text-decoration:none}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-block}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.pendiente{background:#f3f4f6;color:#6b7280}
    .pill.programada{background:#fef2f2;color:#dc2626}
    .pill.pagada{background:#fefce8;color:#ca8a04}
    .pill.liquidada{background:#ecfdf5;color:#16a34a}
    .pill.cancelada,.pill.rechazada{background:#fef2f2;color:#7f1d1d}
    .bubble-roja{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;margin-left:8px;border-radius:999px;background:var(--red);color:#fff;font-size:10px;font-weight:700;vertical-align:middle}
    .hora-bubble{display:inline-flex;align-items:center;justify-content:center;padding:3px 8px;border-radius:999px;background:var(--red);color:#fff;font-size:11px;font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums}
    .hora-bubble.leida{background:var(--gray-muted);opacity:.85}
    .dias-count{font-weight:700;font-variant-numeric:tabular-nums;line-height:1.2;white-space:nowrap}
    .dias-count.warn{color:var(--amber)}
    .dias-count.late{color:var(--red)}
    .dias-sub{font-size:10px;color:var(--gray-muted);margin-top:2px;white-space:nowrap}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500;margin:0}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .active-filters{font-size:12px;color:var(--gray-muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:12px}
    .active-tag{background:var(--purple-subtle);color:var(--purple);padding:3px 10px;border-radius:999px;font-weight:600;font-size:11px}

    @media(max-width:768px){
        .inv-metrics{grid-template-columns:1fr 1fr}
        .filter-field{min-width:100%}
        .filter-form{flex-direction:column;align-items:stretch}
    }
</style>
@endpush
@section('content')
@php
    // El filtrado, los KPIs y la paginación ahora vienen del controlador
    // (AdminPagosController::index). Aquí solo se pinta.
    $filtrosBusqueda = $q !== '' || $codigo !== '';
    $filtrosActivos = $q !== '' || $codigo !== '' || $expediente !== '';

    // $proveedoresPendientes es un paginador (LengthAwarePaginator). Se pinta plano,
    // respetando el orden del controlador (los que deben más, arriba).
    $total = $proveedoresPendientes->total();

    $chipBase = array_filter([
        'q' => $q ?: null,
        'codigo' => $codigo ?: null,
    ]);
@endphp

@if(session('mensaje'))
    <div class="pag-alert ok anim">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
    <div class="pag-alert err anim">{{ session('error') }}</div>
@endif

<div class="toolbar anim" style="animation-delay:.04s">
    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.pagos') }}" class="filter-form">
            <div class="filter-field search-field" style="flex:1;">
                <label>Buscar proveedor</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Escribe código, nombre o RFC del proveedor…" autofocus>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Buscar</button>
                @if($filtrosBusqueda)
                    <a href="{{ route('admin.pagos') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosBusqueda)
        <div class="active-filters">
            <span>Buscando:</span>
            <span class="active-tag">«{{ $q ?: $codigo }}»</span>
        </div>
        @endif
    </div>
</div>

@if(! $filtrosBusqueda)
    {{-- Pantalla de bienvenida: aún no se ha buscado nada. --}}
    <div class="adm-section anim" style="animation-delay:.08s">
        <div class="empty-state" style="padding:48px 20px;text-align:center;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.5" style="margin-bottom:12px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <h3 style="margin:0 0 6px;color:#374151;">Busca un proveedor para pagarle</h3>
            <p style="color:#6b7280;max-width:460px;margin:0 auto;">
                Escribe el <strong>código</strong>, <strong>nombre</strong> o <strong>RFC</strong> del proveedor en el buscador de arriba.
                Al abrirlo verás sus facturas pendientes reales de Wiese, en tiempo real.
            </p>
        </div>
    </div>
@else
<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Resultados</h4>
            <div class="adm-section-meta">
                {{ number_format($total) }} proveedor{{ $total !== 1 ? 'es' : '' }} que coinciden con «{{ $q ?: $codigo }}»
                @if($proveedoresPendientes->lastPage() > 1) · página {{ $proveedoresPendientes->currentPage() }} de {{ $proveedoresPendientes->lastPage() }} @endif
            </div>
        </div>
    </div>

    @if($proveedoresPendientes->isEmpty())
        <div class="empty-state">
            <p>No se encontró ningún proveedor con «{{ $q ?: $codigo }}». Revisa el código, nombre o RFC.</p>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Proveedor</th>
                        <th style="text-align:center;">Moneda</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proveedoresPendientes as $row)
                        @php $esUsd = ($row->moneda ?? 'MXN') === 'USD'; @endphp
                        <tr class="prov-row" onclick="window.location='{{ route('admin.pagos.proveedor', $row->codigo) }}'"
                            style="cursor:pointer;@if($esUsd)background:linear-gradient(90deg,#eff6ff 0%,#f8fbff 60%,#ffffff 100%);@endif">
                            <td>
                                <a class="code-link" href="{{ route('admin.pagos.proveedor', $row->codigo) }}" onclick="event.stopPropagation()" style="{{ $esUsd ? 'color:#1d4ed8;' : '' }}">{{ $row->codigo }}</a>
                            </td>
                            <td style="font-weight:600;">{{ $row->nombre }}</td>
                            <td style="text-align:center;">
                                @if($esUsd)
                                    <span class="mon-badge mon-usd" style="background:#dbeafe;color:#1d4ed8;">USD</span>
                                @else
                                    <span class="mon-badge mon-mxn">MXN</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación: 50 por página. appends conserva la búsqueda al cambiar de página. --}}
        @if($proveedoresPendientes->hasPages())
            <div class="pagination-wrap">{{ $proveedoresPendientes->appends(request()->query())->links() }}</div>
        @endif
    @endif
</div>
@endif
@endsection
