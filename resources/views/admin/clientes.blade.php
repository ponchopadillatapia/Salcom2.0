@extends('layouts.admin')
@section('title', 'Lista de Clientes')
@section('hero')
<div class="hero-band">
    <h1>👥 Lista de Clientes</h1>
    <p>Gestión y consulta de clientes registrados en el sistema</p>
</div>
@endsection
@push('styles')
<style>
    .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .search-box{display:flex;gap:8px;flex:1;max-width:420px}
    .search-box input{flex:1;border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .search-box input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .search-box button{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap}
    .search-box button:hover{background:var(--purple-dark)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}

    .badge-activo{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-activo.si{background:var(--green-bg);color:var(--green)}
    .badge-activo.no{background:var(--red-bg);color:var(--red)}

    .btn-toggle{padding:5px 14px;font-size:12px;font-weight:600;border:1px solid var(--border);border-radius:6px;background:var(--white);cursor:pointer;font-family:inherit;transition:all .15s}
    .btn-toggle:hover{background:var(--purple-light);color:var(--purple);border-color:var(--purple-mid)}
    .btn-toggle.desactivar{color:var(--red)}
    .btn-toggle.desactivar:hover{background:var(--red-bg);border-color:var(--red);color:var(--red)}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .pagination-wrap nav{display:flex;gap:4px}
    .pagination-wrap .page-link{padding:6px 12px;font-size:12px;border:1px solid var(--border);border-radius:6px;color:var(--gray-text);text-decoration:none;transition:all .15s}
    .pagination-wrap .page-link:hover{background:var(--purple-light);color:var(--purple)}
    .pagination-wrap .page-item.active .page-link{background:var(--purple);color:#fff;border-color:var(--purple)}
    .pagination-wrap .page-item.disabled .page-link{color:var(--gray-muted);opacity:.5}

    .alert{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px}
    .alert-success{background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green)}

    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}

    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.toolbar{flex-direction:column;align-items:stretch}.search-box{max-width:100%}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert alert-success">{{ session('mensaje') }}</div>
@endif

<div class="toolbar">
    <form method="GET" action="{{ route('admin.clientes') }}" class="search-box">
        <input type="text" name="busqueda" placeholder="Buscar por nombre o correo…" value="{{ $busqueda ?? '' }}">
        <button type="submit">Buscar</button>
    </form>
    <span class="badge-count">{{ $clientes->total() }} cliente{{ $clientes->total() !== 1 ? 's' : '' }}</span>
</div>

<div class="admin-table-wrap">
@if($clientes->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Tipo cliente</th>
                <th>Activo</th>
                <th>Fecha registro</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        @foreach($clientes as $c)
            <tr>
                <td style="font-weight:600;color:var(--purple)">{{ $c->codigo_cliente ?? '—' }}</td>
                <td>{{ $c->nombre }}</td>
                <td>{{ $c->correo }}</td>
                <td>{{ ucfirst($c->tipo_cliente ?? '—') }}</td>
                <td>
                    <span class="badge-activo {{ $c->activo ? 'si' : 'no' }}">
                        {{ $c->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td>{{ $c->created_at?->format('d/m/Y') ?? '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.clientes.toggle', $c) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-toggle {{ $c->activo ? 'desactivar' : '' }}">
                            {{ $c->activo ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
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
        <p>No se encontraron clientes{{ $busqueda ? ' con esa búsqueda' : '' }}</p>
    </div>
@endif
</div>

@endsection
