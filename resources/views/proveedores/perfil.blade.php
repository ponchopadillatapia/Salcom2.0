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
    .perfil-header { background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
    .perfil-avatar { width: 56px; height: 56px; border-radius: 50%; background: var(--purple); display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: var(--white); flex-shrink: 0; }
    .perfil-name { font-size: 18px; font-weight: 700; color: var(--gray-text); }
    .perfil-meta { font-size: 13px; color: var(--gray-muted); margin-top: 2px; }
    .perfil-actions { margin-left: auto; }
    .btn-edit { padding: 8px 20px; border: 1px solid var(--purple); border-radius: 8px; background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all .15s; }
    .btn-edit:hover { background: var(--purple); color: var(--white); }

    .perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .perfil-card { background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
    .perfil-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border); }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 13px; color: var(--gray-muted); }
    .info-value { font-size: 13px; color: var(--gray-text); font-weight: 600; text-align: right; }

    .status-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
    .status-active { background: var(--green-bg); color: var(--green); }
    .status-pending { background: var(--amber-bg); color: var(--amber); }
    .status-inactive { background: var(--red-bg); color: var(--red); }

    @media (max-width: 768px) { .perfil-grid { grid-template-columns: 1fr; } .perfil-header { flex-wrap: wrap; } }
</style>
@endpush

@section('content')
    <div class="perfil-header">
        <div class="perfil-avatar">{{ strtoupper(substr($proveedor->nombre ?? session('proveedor_nombre', 'P'), 0, 1)) }}</div>
        <div>
            <div class="perfil-name">{{ $proveedor->nombre ?? session('proveedor_nombre', '—') }}</div>
            <div class="perfil-meta">
                Código: {{ $proveedor->codigo_compras ?? session('proveedor_codigo', '—') }}
                · {{ $proveedor->correo ?? session('proveedor_correo', '—') }}
            </div>
        </div>
        <div class="perfil-actions">
            <a href="{{ route('proveedores.actualizacion') }}" class="btn-edit">Editar datos</a>
        </div>
    </div>

    <div class="perfil-grid">
        <div class="perfil-card">
            <h3>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Información General
            </h3>
            <div class="info-row">
                <span class="info-label">Nombre</span>
                <span class="info-value">{{ $proveedor->nombre ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Usuario</span>
                <span class="info-value">{{ $proveedor->usuario ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Correo</span>
                <span class="info-value">{{ $proveedor->correo ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Teléfono</span>
                <span class="info-value">{{ $proveedor->telefono ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tipo de persona</span>
                <span class="info-value">{{ $proveedor->tipo_persona ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Código de compras</span>
                <span class="info-value">{{ $proveedor->codigo_compras ?? '—' }}</span>
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
                    @if($proveedor && $proveedor->activo)
                        <span class="status-badge status-active">● Activo</span>
                    @else
                        <span class="status-badge status-inactive">● Inactivo</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Fuente de login</span>
                <span class="info-value">{{ session('proveedor_login_source', 'local') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Token API</span>
                <span class="info-value">{{ session('proveedor_token') ? 'Activo' : 'No disponible' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Miembro desde</span>
                <span class="info-value">{{ $proveedor && $proveedor->created_at ? $proveedor->created_at->format('d/m/Y') : '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Última actualización</span>
                <span class="info-value">{{ $proveedor && $proveedor->updated_at ? $proveedor->updated_at->format('d/m/Y H:i') : '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">ID interno</span>
                <span class="info-value">#{{ $proveedor->id ?? session('proveedor_id', '—') }}</span>
            </div>
        </div>
    </div>
@endsection
