@extends('layouts.admin')
@section('title', 'Lista de Clientes')
@section('hero')
<div class="hero-band">
    <h1>Lista de Clientes</h1>
    <p>Gestión y consulta de clientes registrados en el sistema</p>
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
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}

    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.ok{background:var(--green-bg);color:var(--green)}
    .badge-est.err{background:var(--red-bg);color:var(--red)}
    .tipo-badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px;background:var(--purple-light);color:var(--purple);text-transform:capitalize}

    .btn-sm{padding:6px 14px;font-size:12px;font-weight:600;border-radius:8px;border:1.5px solid var(--border);cursor:pointer;font-family:inherit;transition:all .15s;background:var(--white);color:var(--gray-text)}
    .btn-sm:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .btn-sm.desactivar{border-color:var(--amber);color:var(--amber)}.btn-sm.desactivar:hover{background:var(--amber-bg)}
    .btn-sm.eliminar{border-color:var(--red);color:var(--red)}.btn-sm.eliminar:hover{background:var(--red);color:#fff;border-color:var(--red)}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green);font-weight:500}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}
    .actions-cell{display:flex;gap:6px;align-items:center;flex-wrap:wrap}

    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.filter-field{min-width:100%}.filter-form{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif

<div class="toolbar">
    <div class="toolbar-top">
        <span class="badge-count">{{ $clientes->total() }} resultado{{ $clientes->total() !== 1 ? 's' : '' }}</span>
    </div>

    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.clientes') }}" class="filter-form">
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="busqueda" value="{{ $filtros['busqueda'] }}" placeholder="Nombre, correo, código o usuario…">
            </div>
            <div class="filter-field">
                <label>Estado</label>
                <select name="activo">
                    <option value="">Activos e inactivos</option>
                    <option value="1" {{ $filtros['activo'] === '1' ? 'selected' : '' }}>Activos ({{ $conteoActivos }})</option>
                    <option value="0" {{ $filtros['activo'] === '0' ? 'selected' : '' }}>Inactivos ({{ $conteoInactivos }})</option>
                </select>
            </div>
            <div class="filter-field">
                <label>Tipo de cliente</label>
                <select name="tipo_cliente">
                    <option value="">Todos los tipos</option>
                    @foreach($tipoOpciones as $key => $label)
                        <option value="{{ $key }}" {{ $filtros['tipo_cliente'] === $key ? 'selected' : '' }}>
                            {{ $label }} ({{ $conteosTipo[$key] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Registro desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}">
            </div>
            <div class="filter-field">
                <label>Registro hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosActivos)
                    <a href="{{ route('admin.clientes') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosActivos)
        <div class="active-filters" style="margin-top:12px;">
            <span>Filtros activos:</span>
            @if($filtros['busqueda'])<span class="active-tag">«{{ $filtros['busqueda'] }}»</span>@endif
            @if($filtros['activo'] === '1')<span class="active-tag">Activos</span>@endif
            @if($filtros['activo'] === '0')<span class="active-tag">Inactivos</span>@endif
            @if($filtros['tipo_cliente'])<span class="active-tag">{{ $tipoOpciones[$filtros['tipo_cliente']] ?? ucfirst($filtros['tipo_cliente']) }}</span>@endif
            @if($filtros['fecha_desde'])<span class="active-tag">Desde {{ $filtros['fecha_desde'] }}</span>@endif
            @if($filtros['fecha_hasta'])<span class="active-tag">Hasta {{ $filtros['fecha_hasta'] }}</span>@endif
        </div>
        @endif
    </div>
</div>

<div class="admin-table-wrap">
@if($clientes->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        @foreach($clientes as $c)
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $c->codigo_cliente ?? '—' }}</td>
                <td style="font-weight:600">{{ $c->nombre }}</td>
                <td>{{ $c->correo }}</td>
                <td>
                    @if($c->tipo_cliente)
                        <span class="tipo-badge">{{ $tipoOpciones[$c->tipo_cliente] ?? ucfirst($c->tipo_cliente) }}</span>
                    @else
                        —
                    @endif
                </td>
                <td>
                    <span class="badge-est {{ $c->activo ? 'ok' : 'err' }}">{{ $c->activo ? 'Activo' : 'Inactivo' }}</span>
                </td>
                <td style="color:var(--gray-muted)">{{ $c->created_at?->format('d/m/Y') ?? '—' }}</td>
                <td>
                    <div class="actions-cell">
                        <form method="POST" action="{{ route('admin.clientes.toggle', $c) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-sm {{ $c->activo ? 'desactivar' : '' }}">
                                {{ $c->activo ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.clientes.eliminar', $c) }}" onsubmit="return confirm('¿Estás seguro de eliminar a {{ addslashes($c->nombre) }}? Esta acción no se puede deshacer fácilmente.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-sm eliminar">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($clientes->hasPages())
        <div class="pagination-wrap">{{ $clientes->links() }}</div>
    @endif
@else
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <p>No se encontraron clientes con los filtros seleccionados.</p>
        @if($filtrosActivos)
            <p style="margin-top:8px;"><a href="{{ route('admin.clientes') }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>
        @endif
    </div>
@endif
</div>

@endsection
