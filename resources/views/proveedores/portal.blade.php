@extends('layouts.proveedor')

@section('title', 'Portal')

@section('hero')
<div class="hero-band">
    <h1>Bienvenido, {{ session('proveedor_nombre', 'Proveedor') }}</h1>
    <p>Selecciona una opción para continuar — {{ now()->format('d/m/Y') }}</p>
</div>
@endsection

@push('styles')
<style>
    .main-content { padding: 48px 32px 64px; display: flex; flex-direction: column; align-items: center; }

    .main-title { font-family: 'Playfair Display', serif; font-size: 24px; color: var(--purple-dark); font-weight: 600; text-align: center; margin-bottom: 8px; }
    .main-sub { font-size: 14px; color: #999; text-align: center; margin-bottom: 48px; }

    .opciones-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; width: 100%; max-width: 640px; }

    .opcion-card { background: var(--white); border: 0.5px solid var(--border); border-radius: 20px; padding: 40px 28px; text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 16px; transition: all .2s; position: relative; overflow: hidden; }
    .opcion-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--purple); transform: scaleX(0); transition: transform .2s; border-radius: 20px 20px 0 0; }
    .opcion-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(107,63,160,0.15); border-color: var(--purple-mid); }
    .opcion-card:hover::before { transform: scaleX(1); }
    .opcion-card.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

    .opcion-icon { width: 64px; height: 64px; border-radius: 18px; background: var(--purple-light); display: flex; align-items: center; justify-content: center; transition: background .2s; }
    .opcion-card:hover .opcion-icon { background: var(--purple); }
    .opcion-card:hover .opcion-icon svg { stroke: var(--white); }

    .opcion-title { font-family: 'Playfair Display', serif; font-size: 18px; color: var(--purple-dark); font-weight: 600; }
    .opcion-desc { font-size: 13px; color: #999; line-height: 1.6; }
    .opcion-badge { font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 999px; background: var(--purple-light); color: var(--purple); }
    .opcion-badge.pronto { background: #FEF3C7; color: #D97706; }

    @media (max-width: 900px) {
        .opciones-grid { grid-template-columns: 1fr; max-width: 400px; }
        .main-content { padding: 32px 20px 48px; }
    }
</style>
@endpush

@section('content')
    <div class="main-title">¿Qué deseas hacer hoy?</div>
    <div class="main-sub">Selecciona una de las siguientes opciones</div>

    <div class="opciones-grid">

        <a href="{{ route('proveedores.oc') }}" class="opcion-card">
            <div class="opcion-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div class="opcion-title">Consultar OC</div>
            <div class="opcion-desc">Revisa tus órdenes de compra, cantidades, precios y condiciones</div>
            <span class="opcion-badge">Disponible</span>
        </a>

        <a href="{{ route('proveedores.dashboard') }}" class="opcion-card">
            <div class="opcion-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
            </div>
            <div class="opcion-title">Dashboard</div>
            <div class="opcion-desc">Consulta tus facturas, pagos y estatus en tiempo real</div>
            <span class="opcion-badge">Disponible</span>
        </a>

    </div>
@endsection