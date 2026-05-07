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
    .ob-header { background: var(--white); border-radius: var(--radius-lg); padding: 24px 28px; margin-bottom: 24px; box-shadow: var(--shadow-sm); }
    .ob-header h2 { font-size: 20px; color: var(--gray-text); font-weight: 700; margin-bottom: 4px; letter-spacing: -0.3px; }
    .ob-header p { font-size: 13px; color: var(--gray-muted); margin-bottom: 20px; }

    .progress-wrap { margin-bottom: 8px; }
    .progress-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--gray-text); margin-bottom: 6px; font-weight: 600; }
    .progress-bar { height: 8px; background: var(--border-light); border-radius: 999px; overflow: hidden; }
    .progress-fill { height: 100%; background: linear-gradient(90deg, var(--purple) 0%, var(--purple-mid) 100%); border-radius: 999px; }

    .pasos-grid { display: flex; flex-direction: column; gap: 16px; }
    .paso-card { background: var(--white); border-radius: var(--radius-lg); padding: 20px 24px; display: flex; align-items: center; gap: 20px; transition: var(--transition); box-shadow: var(--shadow-sm); }
    .paso-card:hover { box-shadow: var(--shadow-md); }
    .paso-card.completado { border-left: 4px solid var(--green); }
    .paso-card.pendiente  { border-left: 4px solid var(--amber); }
    .paso-card.bloqueado  { border-left: 4px solid var(--border-light); opacity: 0.6; }

    .paso-icono { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .paso-icono.verde { background: var(--green-bg); }
    .paso-icono.ambar { background: var(--amber-bg); }
    .paso-icono.gris  { background: var(--gray-soft); }

    .paso-info { flex: 1; min-width: 0; }
    .paso-titulo { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 3px; }
    .paso-desc { font-size: 13px; color: var(--gray-muted); line-height: 1.5; }

    .paso-badge { font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; white-space: nowrap; flex-shrink: 0; }
    .badge-completado { background: var(--green-bg); color: var(--green); }
    .badge-pendiente  { background: var(--amber-bg); color: var(--amber); }
    .badge-bloqueado  { background: var(--gray-soft); color: var(--gray-muted); }

    .btn-ver { padding: 7px 18px; border: 1.5px solid var(--purple); border-radius: var(--radius-pill); background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: var(--transition); text-decoration: none; display: inline-block; }
    .btn-ver:hover { background: var(--purple); color: white; transform: scale(1.03); }
    .btn-ver:active { transform: scale(0.97); }
    .btn-ver.disabled { border-color: var(--border-light); color: var(--gray-muted); cursor: not-allowed; pointer-events: none; }

    @media (max-width: 768px) { .paso-card { flex-wrap: wrap; } }
</style>
@endpush

@section('content')

    <div class="ob-header">
        <h2>Hola, {{ session('proveedor_nombre', 'Proveedor') }}</h2>
        <p>Aquí puedes ver tu progreso como proveedor de Industrias Salcom. Completa cada paso para activar tu cuenta completamente.</p>

        <div class="progress-wrap">
            <div class="progress-label">
                <span>Progreso de onboarding</span>
                <span>2 de 6 pasos completados</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: 33%"></div>
            </div>
        </div>
    </div>

    <div class="pasos-grid">

        {{-- PASO 1: Cuestionario --}}
        <div class="paso-card completado">
            <div class="paso-icono verde"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Cuestionario de registro</div>
                <div class="paso-desc">Completaste el cuestionario con tus datos generales: nombre, correo, teléfono, tipo de persona y código de compras.</div>
            </div>
            <span class="paso-badge badge-completado">Completado</span>
            <a href="{{ route('proveedores.actualizacion') }}" class="btn-ver">Ver</a>
        </div>

        {{-- PASO 2: Documentos fiscales (listado compartido) --}}
        <div class="paso-card completado">
            <div class="paso-icono verde"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 15l2 2 4-4"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Listado de documentos fiscales</div>
                <div class="paso-desc">Subiste tu CIF, Opinión de Cumplimiento del SAT, Acta Constitutiva, INE y Carátula bancaria. Documentos compartidos con Salcom.</div>
            </div>
            <span class="paso-badge badge-completado">Completado</span>
            <a href="/validacion-fiscal" class="btn-ver">Ver</a>
        </div>

        {{-- PASO 3: Contactos --}}
        <div class="paso-card pendiente">
            <div class="paso-icono ambar"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Registro de contactos</div>
                <div class="paso-desc">Registra los contactos de tu empresa por área:</div>
                <ul style="margin:6px 0 0 16px;font-size:12px;color:var(--gray-muted);line-height:1.8;list-style:disc;">
                    <li><strong>Business</strong> (área comercial)</li>
                    <li><strong>Finanzas</strong></li>
                    <li><strong>Compras</strong> (CA)</li>
                    <li><strong>Almacén</strong></li>
                    <li><strong>ITE</strong> (IT/Sistemas)</li>
                    <li><strong>Producción</strong></li>
                    <li><strong>OTIF / Calidad</strong></li>
                </ul>
            </div>
            <span class="paso-badge badge-pendiente">Pendiente</span>
            <a href="{{ route('proveedores.perfil') }}" class="btn-ver">Registrar</a>
        </div>

        {{-- PASO 4: Validación por Salcom --}}
        <div class="paso-card pendiente">
            <div class="paso-icono ambar"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Validación por Industrias Salcom</div>
                <div class="paso-desc">Nuestro equipo está revisando tu información, documentos y contactos. Te notificaremos cuando esté lista la aprobación.</div>
            </div>
            <span class="paso-badge badge-pendiente">En revisión</span>
            <button class="btn-ver disabled">Ver</button>
        </div>

        {{-- PASO 5: Validación de estándar / formato NAE y EXDAT --}}
        <div class="paso-card bloqueado">
            <div class="paso-icono gris"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#AAA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Validación de estándar</div>
                <div class="paso-desc">El proveedor sube el formato que aplique según su tipo:</div>
                <ul style="margin:6px 0 0 16px;font-size:12px;color:var(--gray-muted);line-height:1.8;list-style:disc;">
                    <li><strong>Formato NAE</strong> — Proveedor Nacional</li>
                    <li><strong>Formato EXDAT</strong> — Proveedor Extranjero</li>
                </ul>
                <div style="font-size:11px;color:var(--gray-muted);margin-top:6px;font-style:italic;">Se verifica que toda la documentación cumpla con los estándares de Salcom.</div>
            </div>
            <span class="paso-badge badge-bloqueado">Pendiente</span>
            <button class="btn-ver disabled">Ver</button>
        </div>

        {{-- PASO 6: Proveedor activo --}}
        <div class="paso-card bloqueado">
            <div class="paso-icono gris"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#AAA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div class="paso-info">
                <div class="paso-titulo">Proveedor activo</div>
                <div class="paso-desc">Una vez aprobado, podrás operar de forma completa: recibir OC, subir facturas y dar seguimiento a tus pagos.</div>
            </div>
            <span class="paso-badge badge-bloqueado">Pendiente</span>
            <button class="btn-ver disabled">Ver</button>
        </div>

    </div>

@endsection
