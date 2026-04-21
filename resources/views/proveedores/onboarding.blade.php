@extends('layouts.proveedor')

@section('title', 'Onboarding')

@section('hero')
<div class="hero-band">
    <h1>Onboarding</h1>
    <p>Sigue los pasos para convertirte en proveedor activo de Industrias Salcom</p>
</div>
@endsection

@push('styles')
<style>
    .ob-header { background: var(--white); border-radius: 14px; border: 0.5px solid var(--border); padding: 24px 28px; margin-bottom: 24px; }
    .ob-header h2 { font-family: 'Playfair Display', serif; font-size: 20px; color: var(--purple-dark); font-weight: 600; margin-bottom: 4px; }
    .ob-header p { font-size: 13px; color: #999; margin-bottom: 20px; }

    .progress-wrap { margin-bottom: 8px; }
    .progress-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--gray-text); margin-bottom: 6px; font-weight: 600; }
    .progress-bar { height: 8px; background: var(--border); border-radius: 999px; overflow: hidden; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, var(--purple) 0%, var(--purple-mid) 100%); border-radius: 999px; }

    .pasos-grid { display: flex; flex-direction: column; gap: 16px; }
    .paso-card { background: var(--white); border: 0.5px solid var(--border); border-radius: 14px; padding: 20px 24px; display: flex; align-items: center; gap: 20px; transition: box-shadow .2s; }
    .paso-card:hover { box-shadow: 0 4px 20px rgba(107,63,160,0.10); }
    .paso-card.completado { border-left: 4px solid var(--green); }
    .paso-card.pendiente  { border-left: 4px solid var(--amber); }
    .paso-card.bloqueado  { border-left: 4px solid var(--border); opacity: 0.6; }

    .paso-icono { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 24px; }
    .paso-icono.verde { background: var(--green-bg); }
    .paso-icono.ambar { background: var(--amber-bg); }
    .paso-icono.gris  { background: var(--gray-soft); }

    .paso-info { flex: 1; min-width: 0; }
    .paso-titulo { font-size: 15px; font-weight: 700; color: var(--purple-dark); margin-bottom: 3px; }
    .paso-desc { font-size: 13px; color: #999; line-height: 1.5; }

    .paso-badge { font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
    .badge-completado { background: var(--green-bg); color: var(--green); }
    .badge-pendiente  { background: var(--amber-bg); color: var(--amber); }
    .badge-bloqueado  { background: var(--gray-soft); color: #AAA; }

    .btn-ver { padding: 7px 18px; border: 1.5px solid var(--purple); border-radius: 8px; background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: all .15s; text-decoration: none; display: inline-block; }
    .btn-ver:hover { background: var(--purple); color: white; }
    .btn-ver.disabled { border-color: var(--border); color: #CCC; cursor: not-allowed; pointer-events: none; }

    @media (max-width: 768px) { .paso-card { flex-wrap: wrap; } }
</style>
@endpush

@section('content')

    <div class="ob-header">
        <h2>Hola, {{ session('proveedor_nombre', 'Proveedor') }} 👋</h2>
        <p>Aquí puedes ver tu progreso como proveedor de Industrias Salcom. Completa cada paso para activar tu cuenta completamente.</p>

        {{-- Score del proveedor --}}
        <div style="display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;background:var(--green-bg);border-radius:10px;padding:14px 18px;">
                <div style="font-size:11px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Score Total</div>
                <div style="font-size:28px;font-weight:700;color:var(--green);line-height:1">0%</div>
                <div style="font-size:11px;color:#999;margin-top:4px">50% entrega + 50% puntualidad</div>
            </div>
            <div style="flex:1;min-width:200px;background:var(--blue-bg);border-radius:10px;padding:14px 18px;">
                <div style="font-size:11px;font-weight:700;color:var(--blue);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Entrega a tiempo</div>
                <div style="font-size:28px;font-weight:700;color:var(--blue);line-height:1">0%</div>
                <div style="font-size:11px;color:#999;margin-top:4px">Se calcula con tus OC</div>
            </div>
            <div style="flex:1;min-width:200px;background:var(--purple-light);border-radius:10px;padding:14px 18px;">
                <div style="font-size:11px;font-weight:700;color:var(--purple);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Puntualidad</div>
                <div style="font-size:28px;font-weight:700;color:var(--purple);line-height:1">0%</div>
                <div style="font-size:11px;color:#999;margin-top:4px">Se calcula con tus entregas</div>
            </div>
        </div>

        <div class="progress-wrap">
            <div class="progress-label">
                <span>Progreso de onboarding</span>
                <span>2 de 5 pasos completados</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: 40%"></div>
            </div>
        </div>
    </div>

    <div class="pasos-grid">

        <div class="paso-card completado">
            <div class="paso-icono verde"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Registro de proveedor</div>
                <div class="paso-desc">Creaste tu cuenta y proporcionaste tus datos básicos: nombre, correo, teléfono y tipo de persona.</div>
            </div>
            <span class="paso-badge badge-completado">Completado</span>
            <a href="{{ route('proveedores.actualizacion') }}" class="btn-ver">Ver</a>
        </div>

        <div class="paso-card completado">
            <div class="paso-icono verde"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Documentos fiscales</div>
                <div class="paso-desc">Subiste tu CIF, Opinión de Cumplimiento del SAT y Acta Constitutiva. Documentos verificados correctamente.</div>
            </div>
            <span class="paso-badge badge-completado">Completado</span>
            <a href="/validacion-fiscal" class="btn-ver">Ver</a>
        </div>

        <div class="paso-card pendiente">
            <div class="paso-icono ambar"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Validación por Industrias Salcom</div>
                <div class="paso-desc">Nuestro equipo está revisando tu información y documentos. Te notificaremos cuando esté lista la aprobación.</div>
            </div>
            <span class="paso-badge badge-pendiente">En revisión</span>
            <button class="btn-ver disabled">Ver</button>
        </div>

        <div class="paso-card bloqueado">
            <div class="paso-icono gris"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#AAA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Primera Orden de Compra</div>
                <div class="paso-desc">Una vez validado, Industrias Salcom generará tu primera orden de compra. Podrás consultarla desde el módulo de OC.</div>
            </div>
            <span class="paso-badge badge-bloqueado">Pendiente</span>
            <button class="btn-ver disabled">Ver</button>
        </div>

        <div class="paso-card bloqueado">
            <div class="paso-icono gris"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#AAA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Proveedor activo</div>
                <div class="paso-desc">¡Bienvenido a la familia Salcom! Ya puedes operar de forma completa: consultar OC, subir facturas y dar seguimiento a tus pagos.</div>
            </div>
            <span class="paso-badge badge-bloqueado">Pendiente</span>
            <button class="btn-ver disabled">Ver</button>
        </div>

    </div>

@endsection