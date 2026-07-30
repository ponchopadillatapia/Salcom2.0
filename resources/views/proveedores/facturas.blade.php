@extends('layouts.proveedor')

@section('title', 'Mis Facturas')

@section('hero')
<div class="hero-band">
    <h1>Mis Facturas</h1>
    <p>Consulta todas las facturas que has subido</p>
</div>
@endsection

@push('styles')
<style>
    .ph-info { background: var(--purple-light); border-radius: 10px; padding: 12px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 24px; font-size: 13px; color: var(--gray-text); flex-wrap: wrap; }
    .ph-info strong { color: var(--purple-dark); }
    .ph-info .change-link { color: var(--purple); font-weight: 600; text-decoration: none; margin-left: 4px; cursor: pointer; }

    .ph-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
    .ph-select { border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 13px; font-family: inherit; color: var(--gray-text); background: var(--white); outline: none; }
    .ph-search { flex: 1; min-width: 200px; border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 14px; font-size: 13px; font-family: inherit; color: var(--gray-text); outline: none; }
    .ph-search:focus { border-color: var(--purple-mid); }
    .btn-download { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1.5px solid var(--border); border-radius: 8px; background: var(--white); font-size: 13px; font-family: inherit; color: var(--gray-text); cursor: pointer; font-weight: 500; text-decoration: none; margin-left: auto; }
    .btn-download:hover { border-color: var(--purple-mid); color: var(--purple); }
    .btn-filter { padding: 8px 16px; background: var(--purple); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-clear { padding: 8px 14px; background: var(--gray-soft); color: var(--gray-text); border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; }

    .card { background: var(--white); border-radius: 14px; border: 1px solid var(--border); overflow: hidden; }
    .tabla { width: 100%; border-collapse: collapse; }
    .tabla th { font-size: 12px; font-weight: 600; color: var(--gray-text); padding: 14px 20px; text-align: left; border-bottom: 1px solid var(--border); background: var(--white); }
    .tabla th.sortable::after { content: ' ↓'; color: var(--purple-mid); }
    .tabla td { padding: 14px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 1px solid var(--border); }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: var(--purple-light); }
    .tabla .link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .monto { font-weight: 600; font-variant-numeric: tabular-nums; }
    .pill { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; display: inline-block; text-transform: capitalize; }
    .pill.pendiente { background: var(--amber-bg); color: var(--amber); }
    .pill.pagada, .pill.programada { background: var(--green-bg); color: var(--green); }
    .pill.aprobada, .pill.validada { background: var(--purple-light); color: var(--purple); }
    .empty { text-align: center; padding: 48px 20px; color: var(--gray-muted); font-size: 14px; }
    .pagination-wrap { padding: 16px; display: flex; justify-content: center; }
</style>
@endpush

@section('content')
@php
    $nombre = $proveedor->nombre ?? session('proveedor_nombre', 'Proveedor');
    $codigoShow = $codigo ?: session('proveedor_codigo', '—');
    $desdeLabel = $filtros['fecha_desde'] ? \Carbon\Carbon::parse($filtros['fecha_desde'])->format('d/m/Y') : 'Inicio';
    $hastaLabel = $filtros['fecha_hasta'] ? \Carbon\Carbon::parse($filtros['fecha_hasta'])->format('d/m/Y') : 'Hoy';
@endphp

<div class="ph-info">
    <span>Proveedor: <strong>{{ $codigoShow }}</strong> · {{ $nombre }}</span>
    <span>Período: <strong id="periodoLabel">{{ $desdeLabel }} - {{ $hastaLabel }}</strong></span>
    <a class="change-link" id="btnCambiarPeriodo" onclick="document.getElementById('periodoForm').style.display='flex'; this.style.display='none'; return false;">Cambiar</a>
    <form id="periodoForm" method="GET" action="{{ route('proveedores.facturas') }}" style="display:none;align-items:center;gap:8px;flex-wrap:wrap;">
        <input type="hidden" name="campo" value="{{ $filtros['campo'] }}">
        <input type="hidden" name="q" value="{{ $filtros['q'] }}">
        <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}" style="border:1.5px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px;font-family:inherit;">
        <span style="color:var(--gray-muted);">—</span>
        <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}" style="border:1.5px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px;font-family:inherit;">
        <button type="submit" class="btn-filter" style="padding:6px 14px;font-size:12px;">Aplicar</button>
        <a href="{{ route('proveedores.facturas', request()->except(['fecha_desde','fecha_hasta'])) }}" class="btn-clear" style="padding:6px 14px;font-size:12px;">Quitar fechas</a>
    </form>
</div>

<form method="GET" action="{{ route('proveedores.facturas') }}" class="ph-toolbar">
    @if($filtros['fecha_desde'])<input type="hidden" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}">@endif
    @if($filtros['fecha_hasta'])<input type="hidden" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}">@endif
    <select class="ph-select" name="campo">
        <option value="folio" {{ $filtros['campo'] === 'folio' ? 'selected' : '' }}>Folio / UUID</option>
        <option value="monto" {{ $filtros['campo'] === 'monto' ? 'selected' : '' }}>Monto</option>
        <option value="estatus" {{ $filtros['campo'] === 'estatus' ? 'selected' : '' }}>Estatus</option>
    </select>
    <input type="text" class="ph-search" name="q" value="{{ $filtros['q'] }}" placeholder="Buscar...">
    <button type="submit" class="btn-filter">Filtrar</button>
    @if($filtros['q'] !== '' || $filtros['fecha_desde'] || $filtros['fecha_hasta'])
        <a href="{{ route('proveedores.facturas') }}" class="btn-clear">Limpiar</a>
    @endif
    <a class="btn-download" href="{{ route('proveedores.facturas.excel', request()->query()) }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Descargar
    </a>
</form>

<div class="card">
    @if($facturas->isEmpty())
        <div class="empty">No hay facturas registradas todavía.<br><span style="font-size:12px;">Súbelas desde Alta Facturas.</span></div>
    @else
        <table class="tabla" id="tablaFacturas">
            <thead>
                <tr>
                    <th class="sortable">Fecha</th>
                    <th>Folio</th>
                    <th>Monto</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facturas as $f)
                    <tr>
                        <td>{{ $f->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="link">{{ $f->folio_cfdi ?: '—' }}</span>
                            @if($f->uuid_cfdi)
                                <div style="font-size:10px;color:var(--gray-muted);margin-top:2px;">{{ \Illuminate\Support\Str::limit($f->uuid_cfdi, 28) }}</div>
                            @endif
                        </td>
                        <td class="monto">${{ number_format((float) $f->total, 2) }}</td>
                        <td>
                            <span class="pill {{ $f->estatus }}">{{ $f->estatus }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($facturas->hasPages())
            <div class="pagination-wrap">{{ $facturas->links() }}</div>
        @endif
    @endif
</div>
@endsection
