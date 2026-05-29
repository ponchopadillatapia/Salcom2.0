@extends('layouts.admin')
@section('title', 'Agregar administradores')
@section('hero')
<div class="hero-band">
    <h1>Agregar administradores</h1>
    <p>Alta de usuarios con acceso al panel administrativo</p>
</div>
@endsection

@push('styles')
<style>
    .admin-grid { display: grid; grid-template-columns: 380px 1fr; gap: 20px; align-items: start; }
    .admin-form-card, .admin-table-wrap { background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; padding: 24px; }
    .admin-form-card h3, .admin-table-wrap h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 16px; }
    .fg { margin-bottom: 14px; }
    .fg label { display: block; font-size: 11px; font-weight: 600; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
    .fg input, .fg select { width: 100%; border: 1.5px solid var(--border-light); border-radius: 8px; padding: 10px 12px; font-size: 13px; font-family: inherit; color: var(--gray-text); outline: none; background: var(--white); }
    .fg input:focus, .fg select:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(107,63,160,.1); }
    .btn-save { width: 100%; padding: 10px 20px; background: var(--purple); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; }
    .btn-save:hover { background: var(--purple-dark, #5a3490); }
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { font-size: 11px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .5px; padding: 10px 12px; text-align: left; background: var(--gray-soft); border-bottom: 1px solid var(--border-light); }
    .admin-table td { padding: 10px 12px; font-size: 13px; color: var(--gray-text); border-bottom: 1px solid var(--border-light); }
    .admin-table tr:last-child td { border-bottom: none; }
    .admin-table tbody tr:hover td { background: var(--purple-subtle); }
    .rol-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; background: var(--purple-light); color: var(--purple); }
    .badge-est { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
    .badge-est.ok { background: #ecfdf5; color: #059669; }
    .badge-est.err { background: #fef2f2; color: #dc2626; }
    .alert { border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; }
    .alert-error { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }
    .field-error { font-size: 12px; color: #dc2626; margin-top: 4px; }
    .back-perfil { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--purple); text-decoration: none; margin-bottom: 16px; }
    .back-perfil:hover { text-decoration: underline; }
    @media (max-width: 960px) { .admin-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    <a href="{{ route('admin.perfil') }}" class="back-perfil">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Volver a Mi Perfil
    </a>

    @if(session('mensaje'))
        <div class="alert alert-success">{{ session('mensaje') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="admin-grid">
        <div class="admin-form-card">
            <h3>Nuevo administrador</h3>
            <form method="POST" action="{{ route('admin.administradores.guardar') }}">
                @csrf
                <div class="fg">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                    @error('nombre')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" value="{{ old('usuario') }}" required>
                    @error('usuario')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" value="{{ old('correo') }}" required>
                    @error('correo')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" required>
                        <option value="">Selecciona un rol</option>
                        @foreach($rolesDisponibles as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('rol') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    @error('rol')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                    @error('password')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn-save">Dar de alta</button>
            </form>
        </div>

        <div class="admin-table-wrap">
            <h3>Administradores registrados ({{ $administradores->count() }})</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($administradores as $item)
                    <tr>
                        <td>{{ $item->nombre }}</td>
                        <td>{{ $item->usuario }}</td>
                        <td>{{ $item->correo }}</td>
                        <td><span class="rol-badge">{{ $rolesDisponibles[$item->rol] ?? ucfirst($item->rol) }}</span></td>
                        <td>
                            @if($item->activo)
                                <span class="badge-est ok">Activo</span>
                            @else
                                <span class="badge-est err">Inactivo</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;color:var(--gray-muted);padding:24px;">No hay administradores registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
