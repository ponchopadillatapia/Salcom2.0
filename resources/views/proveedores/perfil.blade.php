@extends('layouts.proveedor')

@section('title', 'Mi Perfil')

@section('hero')
<div class="hero-band">
    <h1>Mi Perfil</h1>
    <p>Consulta y actualiza tu información de proveedor</p>
</div>
@endsection

@push('styles')
<style>
    .perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .perfil-card { background: var(--white); border: 1px solid var(--border); border-radius: 14px; padding: 28px; }
    .perfil-card h3 { font-size: 16px; font-weight: 700; color: var(--purple-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .perfil-card h3 svg { flex-shrink: 0; }

    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 13px; color: #888; font-weight: 500; }
    .info-value { font-size: 13px; color: var(--gray-text); font-weight: 600; text-align: right; }

    .perfil-header { background: var(--white); border: 1px solid var(--border); border-radius: 14px; padding: 28px; margin-bottom: 24px; display: flex; align-items: center; gap: 20px; }
    .perfil-avatar { width: 64px; height: 64px; border-radius: 50%; background: var(--purple); display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; color: var(--white); flex-shrink: 0; }
    .perfil-name { font-size: 20px; font-weight: 700; color: var(--purple-dark); }
    .perfil-code { font-size: 13px; color: #888; margin-top: 2px; }
    .perfil-actions { margin-left: auto; }
    .btn-edit { padding: 8px 20px; border: 1.5px solid var(--purple); border-radius: 8px; background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all .15s; }
    .btn-edit:hover { background: var(--purple); color: var(--white); }

    .status-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 999px; }
    .status-active { background: var(--green-bg); color: var(--green); }
    .status-pending { background: var(--amber-bg); color: var(--amber); }

    @media (max-width: 768px) { .perfil-grid { grid-template-columns: 1fr; } .perfil-header { flex-wrap: wrap; } }
</style>
@endpush

@section('content')
    <div class="perfil-header">
        <div class="perfil-avatar">{{ strtoupper(substr(session('proveedor_nombre', 'P'), 0, 1)) }}</div>
        <div>
            <div class="perfil-name">{{ session('proveedor_nombre', 'Proveedor') }}</div>
            <div class="perfil-code">Código: {{ session('proveedor_codigo', '—') }} · {{ session('proveedor_correo', '—') }}</div>
        </div>
        <div class="perfil-actions">
            <a href="{{ route('proveedores.actualizacion') }}" class="btn-edit">Editar datos</a>
        </div>
    </div>

    <div class="perfil-grid">
        <div class="perfil-card">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Información General
            </h3>
            <div class="info-row"><span class="info-label">Nombre</span><span class="info-value">{{ session('proveedor_nombre', '—') }}</span></div>
            <div class="info-row"><span class="info-label">Correo</span><span class="info-value">{{ session('proveedor_correo', '—') }}</span></div>
            <div class="info-row"><span class="info-label">Código de compras</span><span class="info-value">{{ session('proveedor_codigo', '—') }}</span></div>
            <div class="info-row"><span class="info-label">Fuente de login</span><span class="info-value">{{ session('proveedor_login_source', 'local') }}</span></div>
        </div>

        <div class="perfil-card">
            <h3>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Estado de Cuenta
            </h3>
            <div class="info-row"><span class="info-label">Estado</span><span class="info-value"><span class="status-badge status-active">● Activo</span></span></div>
            <div class="info-row"><span class="info-label">Onboarding</span><span class="info-value"><span class="status-badge status-pending">● En progreso</span></span></div>
            <div class="info-row"><span class="info-label">Documentos fiscales</span><span class="info-value"><span class="status-badge status-active">● Verificados</span></span></div>
            <div class="info-row"><span class="info-label">Miembro desde</span><span class="info-value">Marzo 2026</span></div>
        </div>
    </div>
@endsection
