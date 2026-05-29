@extends('layouts.admin')
@section('title', 'Mi Perfil')
@section('hero')
<div class="hero-band">
    <h1>Mi Perfil</h1>
    <p>Consulta y actualiza tu información de administrador</p>
</div>
@endsection

@push('styles')
<style>
    .perfil-header { background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; padding: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
    .perfil-avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--purple); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: var(--white); flex-shrink: 0; }
    .perfil-name { font-size: 18px; font-weight: 700; color: var(--gray-text); }
    .perfil-meta { font-size: 13px; color: var(--gray-muted); margin-top: 2px; }
    .perfil-actions { margin-left: auto; }
    .btn-edit { padding: 8px 20px; border: 1px solid var(--purple); border-radius: 8px; background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all .15s; }
    .btn-edit:hover { background: var(--purple); color: var(--white); }

    .perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .perfil-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; padding: 24px; }
    .perfil-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-light); gap: 12px; }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 13px; color: var(--gray-muted); flex-shrink: 0; }
    .info-value { font-size: 13px; color: var(--gray-text); font-weight: 600; text-align: right; }

    .status-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
    .status-active { background: var(--green-bg, #ecfdf5); color: var(--green, #059669); }
    .status-inactive { background: var(--red-bg, #fef2f2); color: var(--red, #dc2626); }
    .rol-badge { font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px; background: var(--purple-light); color: var(--purple); }

    .perfil-forms { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .perfil-form-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; padding: 24px; }
    .perfil-form-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 16px; }
    .fg { margin-bottom: 14px; }
    .fg label { display: block; font-size: 11px; font-weight: 600; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
    .fg input { width: 100%; border: 1.5px solid var(--border-light); border-radius: 8px; padding: 10px 12px; font-size: 13px; font-family: inherit; color: var(--gray-text); outline: none; }
    .fg input:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(107,63,160,.1); }
    .fg input:disabled { background: var(--gray-soft); color: var(--gray-muted); cursor: not-allowed; }
    .fg-hint { font-size: 11px; color: var(--gray-muted); margin-top: 4px; }
    .btn-save { padding: 10px 20px; background: var(--purple); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; }
    .btn-save:hover { background: var(--purple-dark, #5a3490); }
    .alert { border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; }
    .alert-error { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }
    .field-error { font-size: 12px; color: #dc2626; margin-top: 4px; }

    @media (max-width: 768px) {
        .perfil-grid, .perfil-forms { grid-template-columns: 1fr; }
        .perfil-header { flex-wrap: wrap; }
        .perfil-actions { margin-left: 0; width: 100%; }
    }
</style>
@endpush

@section('content')
    @if(session('mensaje'))
        <div class="alert alert-success">{{ session('mensaje') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="perfil-header">
        <div class="perfil-avatar">{{ strtoupper(substr($admin->nombre ?? session('admin_nombre', 'A'), 0, 1)) }}</div>
        <div>
            <div class="perfil-name">{{ $admin->nombre ?? session('admin_nombre', '—') }}</div>
            <div class="perfil-meta">
                Usuario: {{ $admin->usuario ?? session('admin_usuario', '—') }}
                · {{ $admin->correo ?? session('admin_correo', '—') }}
            </div>
        </div>
        @if(session('admin_rol') === 'admin')
        <div class="perfil-actions">
            <a href="{{ route('admin.administradores') }}" class="btn-edit">Agregar administradores</a>
        </div>
        @endif
    </div>

    <div class="perfil-grid">
        <div class="perfil-card">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Información General
            </h3>
            <div class="info-row">
                <span class="info-label">Nombre</span>
                <span class="info-value">{{ $admin->nombre ?? session('admin_nombre', '—') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Usuario</span>
                <span class="info-value">{{ $admin->usuario ?? session('admin_usuario', '—') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Correo</span>
                <span class="info-value">{{ $admin->correo ?? session('admin_correo', '—') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Rol</span>
                <span class="info-value"><span class="rol-badge">{{ $rolEtiqueta }}</span></span>
            </div>
        </div>

        <div class="perfil-card">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Estado de Cuenta
            </h3>
            <div class="info-row">
                <span class="info-label">Estado</span>
                <span class="info-value">
                    @if($admin && $admin->activo)
                        <span class="status-badge status-active">● Activo</span>
                    @else
                        <span class="status-badge status-inactive">● Inactivo</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Miembro desde</span>
                <span class="info-value">{{ $admin && $admin->created_at ? $admin->created_at->format('d/m/Y') : '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Última actualización</span>
                <span class="info-value">{{ $admin && $admin->updated_at ? $admin->updated_at->format('d/m/Y H:i') : '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">ID interno</span>
                <span class="info-value">#{{ $admin->id ?? session('admin_id', '—') }}</span>
            </div>
        </div>
    </div>

    <div class="perfil-forms">
        <div class="perfil-form-card" id="editar-datos">
            <h3>Actualizar datos</h3>
            <form method="POST" action="{{ route('admin.perfil.actualizar') }}">
                @csrf
                <div class="fg">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $admin->nombre ?? session('admin_nombre')) }}" required>
                    @error('nombre')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" value="{{ old('correo', $admin->correo ?? session('admin_correo')) }}" required>
                    @error('correo')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" value="{{ $admin->usuario ?? session('admin_usuario', '—') }}" disabled>
                    <p class="fg-hint">El usuario de acceso solo puede modificarse por un administrador del sistema.</p>
                </div>
                <button type="submit" class="btn-save">Guardar cambios</button>
            </form>
        </div>

        <div class="perfil-form-card" id="cambiar-password">
            <h3>Cambiar contraseña</h3>
            @if(session('error_password'))
                <div class="alert alert-error">{{ session('error_password') }}</div>
            @endif
            <form method="POST" action="{{ route('admin.perfil.password') }}">
                @csrf
                <div class="fg">
                    <label for="password_actual">Contraseña actual</label>
                    <input type="password" id="password_actual" name="password_actual" required autocomplete="current-password">
                </div>
                <div class="fg">
                    <label for="password">Nueva contraseña</label>
                    <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                    @error('password')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="fg">
                    <label for="password_confirmation">Confirmar nueva contraseña</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn-save">Actualizar contraseña</button>
            </form>
        </div>
    </div>
@endsection
