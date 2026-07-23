@extends('layouts.admin')

@section('title', 'Expediente — '.$proveedor->nombre)

@section('hero')
<div class="hero-band">
    <h1>Expediente documental</h1>
    <p>{{ $proveedor->nombre ?? $proveedor->usuario }} — documentos mes por mes</p>
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

    .mes-head {
        font-size: 13px; font-weight: 700; color: var(--purple);
        margin: 18px 0 10px; padding-bottom: 6px;
        border-bottom: 1px solid var(--border-light);
    }

    .seccion-doc {
        display: block; text-decoration: none; color: inherit;
        border-radius: 10px; padding: 1rem 1.1rem; margin-bottom: 0.65rem;
        border: 1px solid var(--border-light); background: var(--white);
        border-left: 4px solid #059669; transition: box-shadow .15s, transform .15s;
        cursor: pointer;
    }
    .seccion-doc.pendiente { border-left-color: #d97706; }
    .seccion-doc.rechazado { border-left-color: #dc2626; }
    .seccion-doc:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); transform: translateY(-1px); }
    .seccion-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.55rem; flex-wrap: wrap; }
    .seccion-titulo { font-weight: 700; font-size: 0.9rem; color: var(--gray-text); flex: 1; }
    .status-pill { font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; background: #ecfdf5; color: #059669; }
    .status-pill.pendiente { background: #fffbeb; color: #d97706; }
    .status-pill.rechazado { background: #fef2f2; color: #dc2626; }
    .detalle-item { font-size: 0.82rem; padding: 4px 0; display: flex; align-items: flex-start; gap: 0.5rem; color: #047857; line-height: 1.4; }
    .seccion-doc.pendiente .detalle-item { color: #92400e; }
    .seccion-doc.rechazado .detalle-item { color: #991b1b; }
    .detalle-item svg { flex-shrink: 0; margin-top: 2px; }
    .doc-dl-hint { margin-top: 10px; font-size: 12px; font-weight: 600; color: var(--purple); display: inline-flex; align-items: center; gap: 6px; }
    .ver-empty { text-align: center; padding: 40px 20px; background: var(--white); border-radius: 14px; border: 1px solid var(--border-light); color: var(--gray-muted); font-size: 14px; }
    .ver-hint { font-size: 13px; color: var(--gray-muted); margin-bottom: 12px; line-height: 1.45; }
</style>
@endpush

@section('content')
<div class="ver-wrap">
    <a href="{{ route('admin.expediente-fiscal', $filtrosQuery ?? []) }}" class="ver-back">← Volver al expediente</a>

    @php $datosIdent = $datosIdent ?? []; @endphp
    <div class="resultado-card" style="border-color:#ddd6fe;background:#f5f3ff;margin-bottom:1rem;">
        <div class="resultado-header">
            <div>
                <div class="resultado-empresa" style="color:var(--purple);">{{ $proveedor->nombre ?? $proveedor->usuario }}</div>
                <div class="resultado-rfc">
                    Código: {{ $proveedor->id_proveedor ?? '—' }}
                    · {{ $proveedor->tipo_persona ?? '—' }}
                    · {{ $docs->count() }} documento(s)
                </div>
            </div>
        </div>
        <hr class="resultado-divider">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;font-size:13px;">
            <div><strong>Correo:</strong> {{ $datosIdent['correo'] ?? $proveedor->correo ?? '—' }}</div>
            <div><strong>Teléfono:</strong> {{ $datosIdent['telefono'] ?? $proveedor->telefono ?? '—' }}</div>
            <div><strong>Banco:</strong> {{ $datosIdent['banco'] ?? '—' }}</div>
            <div><strong>CLABE:</strong> {{ $datosIdent['clabe'] ?? '—' }}</div>
            <div style="grid-column:1/-1;"><strong>Contactos:</strong> {{ $proveedor->contactos->count() }} registrados</div>
        </div>
    </div>

    @if($docsPorMes->isEmpty())
        <div class="ver-empty">
            <p style="font-weight:600;color:var(--gray-text);margin-bottom:6px;">Sin documentos con estos filtros</p>
            <p style="margin:0;">Vuelve a la lista o limpia el filtro de mes/tipo.</p>
        </div>
    @else
        <div class="resultado-card">
            <div class="resultado-header">
                <span style="display:flex;align-items:center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <div>
                    <div class="resultado-empresa">DOCUMENTOS DEL EXPEDIENTE</div>
                    <div class="resultado-rfc">Agrupados por mes · clic para descargar PDF</div>
                </div>
            </div>
            <hr class="resultado-divider">

            @foreach($docsPorMes as $mesKey => $docsMes)
                @php
                    try {
                        $tituloMes = ucfirst(\Carbon\Carbon::createFromFormat('Y-m', $mesKey)->locale('es')->translatedFormat('F Y'));
                    } catch (\Throwable $e) {
                        $tituloMes = $mesKey;
                    }
                @endphp
                <div class="mes-head">{{ $tituloMes }} · {{ $docsMes->count() }} documento(s)</div>

                @foreach($docsMes as $doc)
                    @php
                        $estatus = $doc->estatus ?? 'pendiente';
                        $resultado = is_array($doc->resultado_validacion) ? $doc->resultado_validacion : [];
                        $hallazgos = $resultado['hallazgos'] ?? [];
                        if ($hallazgos === [] && isset($resultado['checklist']) && is_array($resultado['checklist'])) {
                            $hallazgos = array_values(array_filter($resultado['checklist'], fn ($x) => is_string($x)));
                        }
                        $errores = $resultado['errores'] ?? [];
                        if ($hallazgos === [] && ! empty($doc->notas_revision)) {
                            $hallazgos = [$doc->notas_revision];
                        }
                        $datos = $resultado['datos'] ?? [];
                        $extra = [];
                        foreach (['nombre','curp','rfc','banco','clabe','cuenta','tipo_documento','fecha_nacimiento','clave_elector'] as $k) {
                            if (! empty($datos[$k])) {
                                $extra[] = strtoupper(str_replace('_', ' ', $k)).': '.$datos[$k];
                            }
                        }
                        $href = $doc->archivo
                            ? route('admin.expediente-fiscal.descargar', $doc)
                            : '#';
                        $pillClass = $estatus === 'aprobado' ? '' : $estatus;
                        $stroke = $estatus === 'aprobado' ? '#059669' : ($estatus === 'rechazado' ? '#dc2626' : '#d97706');
                    @endphp
                    <a href="{{ $href }}" class="seccion-doc {{ $estatus }}" title="Descargar PDF">
                        <div class="seccion-header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            <span class="seccion-titulo">{{ $tiposLabel[$doc->tipo] ?? ucfirst($doc->tipo) }}</span>
                            <span class="status-pill {{ $pillClass }}">{{ strtoupper($estatus) }}</span>
                        </div>
                        @foreach($extra as $line)
                            <div class="detalle-item">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $line }}
                            </div>
                        @endforeach
                        @foreach($hallazgos as $h)
                            <div class="detalle-item">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ is_array($h) ? json_encode($h, JSON_UNESCAPED_UNICODE) : $h }}
                            </div>
                        @endforeach
                        @foreach($errores as $err)
                            <div class="detalle-item">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                {{ is_array($err) ? json_encode($err, JSON_UNESCAPED_UNICODE) : $err }}
                            </div>
                        @endforeach
                        @if($extra === [] && $hallazgos === [] && $errores === [])
                            <div class="detalle-item">Sin detalle de validación registrado</div>
                        @endif
                        <div class="doc-dl-hint">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Clic para descargar PDF
                            @if($doc->created_at)
                                · {{ $doc->created_at->format('d/m/Y') }}
                            @endif
                        </div>
                    </a>
                @endforeach
            @endforeach
        </div>
    @endif
</div>
@endsection
