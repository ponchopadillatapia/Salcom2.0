@extends('layouts.admin')
@section('title', 'Mi Perfil')
@section('hero')
<div class="hero-band">
    <h1>Mi Perfil</h1>
    <p>Consulta tu información de administrador</p>
</div>
@endsection

@push('styles')
<style>
    .perfil-header { background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; padding: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
    .perfil-avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--purple); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: var(--white); flex-shrink: 0; position: relative; cursor: pointer; overflow: hidden; transition: all .15s; }
    .perfil-avatar:hover { opacity: .85; }
    .perfil-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .perfil-avatar .avatar-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .15s; border-radius: 50%; }
    .perfil-avatar:hover .avatar-overlay { opacity: 1; }
    .perfil-name { font-size: 18px; font-weight: 700; color: var(--gray-text); }
    .perfil-meta { font-size: 13px; color: var(--gray-muted); margin-top: 2px; }
    .perfil-actions { margin-left: auto; }
    .btn-edit { padding: 8px 20px; border: 1px solid var(--purple); border-radius: 8px; background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all .15s; }
    .btn-edit:hover { background: var(--purple); color: var(--white); }

    .perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
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

    .alert { border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; }
    .alert-error { background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; }

    @media (max-width: 768px) {
        .perfil-grid { grid-template-columns: 1fr; }
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
        <form id="fotoForm" method="POST" action="{{ route('admin.perfil.foto') }}" enctype="multipart/form-data" style="display:inline;">
            @csrf
            <div class="perfil-avatar" onclick="document.getElementById('fotoInput').click()" title="Cambiar foto">
                @if($admin && $admin->foto)
                    <img src="{{ asset('storage/' . $admin->foto) }}" alt="Foto">
                @else
                    {{ strtoupper(substr($admin->nombre ?? session('admin_nombre', 'A'), 0, 1)) }}
                @endif
                <div class="avatar-overlay">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </div>
            </div>
            <input type="file" id="fotoInput" name="foto" accept="image/*" style="display:none;" onchange="document.getElementById('fotoForm').submit()">
        </form>
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
@endsection
