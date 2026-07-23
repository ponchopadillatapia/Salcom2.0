@extends('layouts.admin')

@section('title', 'Resultado de validación')

@section('hero')
<div class="hero-band">
    <h1>Resultado de validación</h1>
    <p>{{ $proveedor->nombre ?? $proveedor->usuario }} — solo documentos correctos (aprobados)</p>
</div>
@endsection

@push('styles')
<style>
    .ver-wrap { max-width: 780px; margin: 0 auto; }
    .ver-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--purple); text-decoration: none; margin-bottom: 16px; }
    .ver-back:hover { text-decoration: underline; }

    .resultado-card {
        border-radius: 14px; padding: 1.5rem; border: 1.5px solid #bbf7d0;
        background: #f0fdf4; margin-bottom: 1rem;
    }
    .resultado-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0; }
    .resultado-empresa { font-size: 1.05rem; font-weight: 700; color: var(--gray-text); }
    .resultado-rfc { font-size: 0.82rem; color: var(--gray-muted); font-weight: 500; margin-top: 2px; }
    .resultado-divider { border: none; border-top: 1px solid var(--border-light); margin: 1rem 0; }

    .seccion-doc {
        display: block; text-decoration: none; color: inherit;
        border-radius: 10px; padding: 1rem 1.1rem; margin-bottom: 0.65rem;
        border: 1px solid var(--border-light); background: var(--white);
        border-left: 4px solid var(--green); transition: box-shadow .15s, transform .15s;
        cursor: pointer;
    }
    .seccion-doc:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); transform: translateY(-1px); }
    .seccion-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.55rem; flex-wrap: wrap; }
    .seccion-titulo { font-weight: 700; font-size: 0.9rem; color: var(--gray-text); flex: 1; }
    .status-pill { font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; background: #ecfdf5; color: #059669; }
    .detalle-item { font-size: 0.82rem; padding: 4px 0; display: flex; align-items: flex-start; gap: 0.5rem; color: #047857; line-height: 1.4; }
    .detalle-item svg { flex-shrink: 0; margin-top: 2px; }
    .doc-dl-hint { margin-top: 10px; font-size: 12px; font-weight: 600; color: var(--purple); display: inline-flex; align-items: center; gap: 6px; }
    .ver-empty { text-align: center; padding: 40px 20px; background: var(--white); border-radius: 14px; border: 1px solid var(--border-light); color: var(--gray-muted); font-size: 14px; }
    .ver-hint { font-size: 13px; color: var(--gray-muted); margin-bottom: 12px; line-height: 1.45; }
</style>
@endpush

@section('content')
<div class="ver-wrap">
    <a href="{{ route('admin.solicitudes-alta') }}" class="ver-back">← Volver a solicitudes</a>

    @php $datosIdent = $datosIdent ?? []; @endphp
    <div class="resultado-card" style="border-color:#ddd6fe;background:#f5f3ff;margin-bottom:1rem;">
        <div class="resultado-header">
            <div>
                <div class="resultado-empresa" style="color:var(--purple);">DATOS DEL FORMULARIO</div>
                <div class="resultado-rfc">{{ $proveedor->nombre ?? $proveedor->usuario }} · {{ $proveedor->tipo_persona ?? '—' }}</div>
            </div>
        </div>
        <hr class="resultado-divider">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;font-size:13px;">
            <div><strong>Correo:</strong> {{ $datosIdent['correo'] ?? $proveedor->correo ?? '—' }}</div>
            <div><strong>Teléfono:</strong> {{ $datosIdent['telefono'] ?? $proveedor->telefono ?? '—' }}</div>
            <div><strong>Banco:</strong> {{ $datosIdent['banco'] ?? '—' }}</div>
            <div><strong>CLABE:</strong> {{ $datosIdent['clabe'] ?? '—' }}</div>
            <div><strong>Cuenta:</strong> {{ $datosIdent['cuenta'] ?? '—' }}</div>
            <div><strong>CP:</strong> {{ $datosIdent['cp'] ?? '—' }}</div>
            <div style="grid-column:1/-1;"><strong>Dirección:</strong>
                {{ trim(implode(', ', array_filter([
                    $datosIdent['calle'] ?? null,
                    $datosIdent['num_exterior'] ?? null,
                    $datosIdent['colonia'] ?? null,
                    $datosIdent['municipio'] ?? null,
                    $datosIdent['estado'] ?? null,
                ]))) ?: '—' }}
            </div>
            <div style="grid-column:1/-1;"><strong>Contactos:</strong> {{ $proveedor->contactos->count() }} registrados</div>
        </div>
    </div>

    @if($docsAprobados->isEmpty())
        <div class="ver-empty">
            <p style="font-weight:600;color:var(--gray-text);margin-bottom:6px;">Sin documentos correctos aún</p>
            <p style="margin:0;">Cuando el proveedor complete la validación fiscal con éxito, aquí verás el detalle de cada documento aprobado. Puedes descargar el PDF haciendo clic en cada tarjeta.</p>
        </div>
    @else
        <div class="resultado-card">
            <div class="resultado-header">
                <span style="display:flex;align-items:center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <div>
                    <div class="resultado-empresa">DOCUMENTOS CORRECTOS</div>
                    <div class="resultado-rfc">
                        {{ $proveedor->nombre ?? $proveedor->usuario }}
                        · {{ $proveedor->tipo_persona ?? '—' }}
                        · {{ $docsAprobados->count() }} documento(s) aprobado(s)
                    </div>
                </div>
            </div>
            <hr class="resultado-divider">
            <p class="ver-hint">Haz clic en un documento para descargarlo y revisarlo manualmente. Solo se listan los que pasaron la validación.</p>

            @foreach($docsAprobados as $doc)
                @php
                    $resultado = is_array($doc->resultado_validacion) ? $doc->resultado_validacion : [];
                    $hallazgos = $resultado['hallazgos'] ?? [];
                    if ($hallazgos === [] && isset($resultado['checklist']) && is_array($resultado['checklist'])) {
                        $hallazgos = array_values(array_filter($resultado['checklist'], fn ($x) => is_string($x)));
                    }
                    if ($hallazgos === [] && ! empty($doc->notas_revision)) {
                        $hallazgos = [$doc->notas_revision];
                    }
                    $href = $doc->archivo
                        ? route('admin.expediente-fiscal.descargar', $doc)
                        : '#';
                @endphp
                <a href="{{ $href }}" class="seccion-doc" title="Descargar PDF">
                    <div class="seccion-header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span class="seccion-titulo">{{ $tiposLabel[$doc->tipo] ?? ucfirst($doc->tipo) }}</span>
                        <span class="status-pill">Aprobado</span>
                    </div>
                    @forelse($hallazgos as $h)
                        <div class="detalle-item">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ is_array($h) ? json_encode($h, JSON_UNESCAPED_UNICODE) : $h }}
                        </div>
                    @empty
                        <div class="detalle-item">Validación automática aprobada</div>
                    @endforelse
                    <div class="doc-dl-hint">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Clic para descargar PDF
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
