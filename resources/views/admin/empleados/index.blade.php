@extends('layouts.admin')
@section('title', 'Empleados')

@push('styles')
<style>
    .emp-wrap { max-width: 960px; }
    .emp-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 24px; margin-bottom: 20px; }
    .emp-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 16px; }
    .emp-row { display: grid; grid-template-columns: 1fr 1.5fr 1fr 1.5fr auto; gap: 12px; align-items: end; }
    .emp-group { display: flex; flex-direction: column; gap: 6px; }
    .emp-group label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
    .emp-group input {
        border: 1.5px solid var(--border); border-radius: 8px; padding: 10px 14px;
        font-size: 13px; font-family: inherit; color: var(--gray-text); outline: none; background: var(--white); width: 100%; box-sizing: border-box;
    }
    .emp-group input:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(107,63,160,.1); }
    .btn-alta { padding: 10px 20px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; white-space: nowrap; }
    .btn-alta:hover { background: var(--purple-dark); }
    .emp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .emp-table th { text-align: left; font-size: 10px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; padding: 10px 12px; border-bottom: 2px solid var(--border-light); }
    .emp-table td { padding: 12px; border-bottom: 1px solid var(--border-light); }
    .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .b-activo { background: #dcfce7; color: #166534; } .b-inactivo { background: #fee2e2; color: #991b1b; }
    .link-act { font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer; background: none; border: none; font-family: inherit; padding: 0; }
    .empty { text-align: center; padding: 32px; color: var(--gray-muted); font-size: 13px; }
    @media(max-width:768px){ .emp-row { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="emp-wrap">
    @if(session('mensaje'))
    <div style="background:#ecfdf5;border:1px solid #059669;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#059669;font-size:13px;font-weight:600;">{{ session('mensaje') }}</div>
    @endif
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
        <ul style="margin:0;padding:0 0 0 16px;color:#991b1b;font-size:12px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Alta --}}
    <div class="emp-card">
        <h3>Dar de alta empleado</h3>
        <form method="POST" action="{{ route('admin.empleados.guardar') }}">
            @csrf
            <div class="emp-row">
                <div class="emp-group">
                    <label for="numero_empleado">Número de empleado *</label>
                    <input type="text" id="numero_empleado" name="numero_empleado" required placeholder="Ej: 31542" value="{{ old('numero_empleado') }}">
                </div>
                <div class="emp-group">
                    <label for="nombre">Nombre completo *</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Nombre del empleado" value="{{ old('nombre') }}">
                </div>
                <div class="emp-group">
                    <label for="departamento">Departamento</label>
                    <input type="text" id="departamento" name="departamento" placeholder="Ej: Ventas" value="{{ old('departamento') }}">
                </div>
                <div class="emp-group">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" placeholder="correo@salcom.com" value="{{ old('correo') }}">
                </div>
                <div class="emp-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-alta">Dar de alta</button>
                </div>
            </div>
        </form>
    </div>

    {{-- Listado --}}
    <div class="emp-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
            <h3 style="margin:0;">Empleados registrados</h3>
            <form method="GET" action="{{ route('admin.empleados') }}" style="display:flex;gap:8px;">
                <input type="text" name="busqueda" value="{{ request('busqueda') }}" placeholder="Buscar..." style="padding:7px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;width:180px;">
                <button type="submit" style="padding:7px 14px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Buscar</button>
            </form>
        </div>

        @if($empleados->count())
        <table class="emp-table">
            <thead><tr><th>Nº Empleado</th><th>Nombre</th><th>Departamento</th><th>Correo</th><th>Estatus</th><th>Acciones</th></tr></thead>
            <tbody>
                @foreach($empleados as $emp)
                <tr>
                    <td><strong>{{ $emp->numero_empleado }}</strong></td>
                    <td>{{ $emp->nombre }}</td>
                    <td>{{ $emp->departamento ?: '—' }}</td>
                    <td>{{ $emp->correo ?: '—' }}</td>
                    <td><span class="badge {{ $emp->activo ? 'b-activo' : 'b-inactivo' }}">{{ $emp->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td style="display:flex;gap:12px;align-items:center;">
                        <form method="POST" action="{{ route('admin.empleados.toggle', $emp) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="link-act" style="color:{{ $emp->activo ? '#d97706' : '#059669' }};">{{ $emp->activo ? 'Desactivar' : 'Activar' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.empleados.eliminar', $emp) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar este empleado?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="link-act" style="color:#dc2626;">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:16px;">{{ $empleados->links() }}</div>
        @else
        <div class="empty">No hay empleados registrados.</div>
        @endif
    </div>
</div>
@endsection
