@extends('layouts.proveedor')

@section('title', 'Mis Productos')

@section('hero')
<div class="hero-band">
    <h1>Mis Productos</h1>
    <p>Consulta el catálogo de productos que has dado de alta o que tienes vinculados</p>
</div>
@endsection

@push('styles')
<style>
    .mp-info{background:var(--purple-light);border-radius:10px;padding:12px 20px;display:flex;align-items:center;gap:16px;margin-bottom:24px;font-size:13px;color:var(--gray-text);flex-wrap:wrap}
    .mp-info strong{color:var(--purple-dark)}
    .mp-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .mp-metric{background:var(--white);border:1px solid var(--border-light,var(--border));border-radius:14px;padding:20px;position:relative;overflow:hidden;text-decoration:none;color:inherit;display:block;transition:box-shadow .15s,border-color .15s}
    .mp-metric:hover{border-color:var(--purple-mid,#c4b5e0)}
    .mp-metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .mp-metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px}
    .mp-metric-val{font-size:28px;font-weight:700;color:var(--gray-text);line-height:1}
    .mp-metric-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .mp-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
    .mp-search{flex:1;min-width:200px;border:1.5px solid var(--border);border-radius:8px;padding:8px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none}
    .mp-search:focus{border-color:var(--purple-mid)}
    .mp-select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white);outline:none}
    .btn-filter{padding:8px 16px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
    .btn-clear{padding:8px 14px;background:var(--gray-soft);color:var(--gray-text);border:1px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;text-decoration:none}
    .btn-alta{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;margin-left:auto}
    .btn-alta:hover{background:#15803d}
    .card{background:var(--white);border-radius:14px;border:1px solid var(--border);overflow:hidden}
    .tabla{width:100%;border-collapse:collapse}
    .tabla th{font-size:12px;font-weight:600;color:var(--gray-text);padding:14px 20px;text-align:left;border-bottom:1px solid var(--border);background:var(--white)}
    .tabla td{padding:14px 20px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .tabla tr:last-child td{border-bottom:none}
    .tabla tr:hover td{background:var(--purple-light)}
    .tabla .codigo{color:var(--purple);font-weight:700}
    .monto{font-weight:600;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-block}
    .pill.on{background:var(--green-bg);color:var(--green)}
    .pill.off{background:var(--gray-soft);color:var(--gray-muted)}
    .empty{text-align:center;padding:48px 20px;color:var(--gray-muted);font-size:14px}
    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    @media(max-width:768px){.mp-metrics{grid-template-columns:1fr 1fr}.btn-alta{margin-left:0;width:100%;justify-content:center}}
</style>
@endpush

@section('content')
@php
    $nombre = $proveedor->nombre ?? session('proveedor_nombre', 'Proveedor');
    $codigoShow = $proveedor->id_proveedor ?? session('proveedor_codigo', '—');
    $filtrosActivos = ($filtros['q'] ?? '') !== '' || ($filtros['tipo'] ?? '') !== '' || ($filtros['activo'] ?? '') !== '';
@endphp

<div class="mp-info">
    <span>Proveedor: <strong>{{ $codigoShow }}</strong> · {{ $nombre }}</span>
    <span>Total en catálogo: <strong>{{ $kpis['totales'] }}</strong></span>
</div>

<div class="mp-metrics">
    <a class="mp-metric" href="{{ route('proveedores.mis-productos') }}">
        <div class="accent" style="background:var(--purple)"></div>
        <div class="mp-metric-label">Totales</div>
        <div class="mp-metric-val">{{ $kpis['totales'] }}</div>
        <div class="mp-metric-sub">Todos tus productos</div>
    </a>
    <a class="mp-metric" href="{{ route('proveedores.mis-productos', ['activo' => '1']) }}">
        <div class="accent" style="background:var(--green)"></div>
        <div class="mp-metric-label">Activos</div>
        <div class="mp-metric-val">{{ $kpis['activos'] }}</div>
        <div class="mp-metric-sub">Disponibles en catálogo</div>
    </a>
    <a class="mp-metric" href="{{ route('proveedores.mis-productos', ['activo' => '0']) }}">
        <div class="accent" style="background:var(--gray-muted)"></div>
        <div class="mp-metric-label">Inactivos</div>
        <div class="mp-metric-val">{{ $kpis['inactivos'] }}</div>
        <div class="mp-metric-sub">Fuera de catálogo</div>
    </a>
    <div class="mp-metric" style="cursor:default">
        <div class="accent" style="background:var(--amber)"></div>
        <div class="mp-metric-label">Sin precio</div>
        <div class="mp-metric-val">{{ $kpis['sin_precio'] }}</div>
        <div class="mp-metric-sub">Revisar alta</div>
    </div>
</div>

<form method="GET" action="{{ route('proveedores.mis-productos') }}" class="mp-toolbar">
    <input type="search" name="q" value="{{ $filtros['q'] }}" class="mp-search" placeholder="Buscar por código, nombre, familia…">
    <select name="tipo" class="mp-select">
        <option value="">Todos los tipos</option>
        @foreach($tipos as $tipo)
            <option value="{{ $tipo }}" {{ ($filtros['tipo'] ?? '') === $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
        @endforeach
    </select>
    <select name="activo" class="mp-select">
        <option value="">Todos los estatus</option>
        <option value="1" {{ ($filtros['activo'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
        <option value="0" {{ ($filtros['activo'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
    </select>
    <button type="submit" class="btn-filter">Filtrar</button>
    @if($filtrosActivos)
        <a href="{{ route('proveedores.mis-productos') }}" class="btn-clear">Limpiar</a>
    @endif
    <a href="{{ route('proveedores.alta-producto') }}" class="btn-alta">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Alta de producto
    </a>
</form>

<div class="card">
    @if($productos->count())
    <div style="overflow-x:auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Familia</th>
                    <th>Precio</th>
                    <th>Unidad</th>
                    <th>Estatus</th>
                    <th>Alta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos as $p)
                    @php
                        $precioVinculo = $p->preciosProveedor->first()?->precio;
                        $precioMostrar = $precioVinculo !== null ? (float) $precioVinculo : (float) $p->precio;
                    @endphp
                    <tr>
                        <td class="codigo">{{ $p->codigo }}</td>
                        <td style="font-weight:600">{{ $p->nombre }}</td>
                        <td style="color:var(--gray-muted)">{{ $p->tipo_producto ?: ($p->categoria ?: '—') }}</td>
                        <td style="color:var(--gray-muted)">{{ $p->familia ?: '—' }}</td>
                        <td class="monto">${{ number_format($precioMostrar, 2) }}</td>
                        <td>{{ $p->unidad_venta ?: '—' }}</td>
                        <td>
                            <span class="pill {{ $p->activo ? 'on' : 'off' }}">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td style="font-size:12px;color:var(--gray-muted);white-space:nowrap">
                            {{ $p->created_at ? $p->created_at->format('d/m/Y') : '—' }}
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
    <div class="empty">
        <p>No tienes productos registrados{{ $filtrosActivos ? ' con estos filtros' : '' }}.</p>
        @if($filtrosActivos)
            <p style="margin-top:10px"><a href="{{ route('proveedores.mis-productos') }}" style="color:var(--purple);font-weight:600">Quitar filtros</a></p>
        @else
            <p style="margin-top:10px"><a href="{{ route('proveedores.alta-producto') }}" style="color:var(--purple);font-weight:600">Dar de alta tu primer producto</a></p>
        @endif
    </div>
    @endif
</div>
@endsection
