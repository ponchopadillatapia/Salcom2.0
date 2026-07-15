@extends('layouts.admin')

@section('title', 'Revisar solicitud')

@section('hero')
<div class="hero-band">
    <h1>{{ $proveedor->nombre ?? $proveedor->usuario }}</h1>
    <p>Revisión manual — Contabilidad / Dirección</p>
</div>
@endsection

@push('styles')
<style>
    .sd-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--purple); text-decoration: none; margin-bottom: 16px; }
    .sd-card { background: var(--white); border: 1px solid var(--border); border-radius: 12px; padding: 20px 22px; margin-bottom: 16px; }
    .sd-card h3 { font-size: 14px; font-weight: 700; color: var(--gray-text); margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border-light); }
    .sd-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px 18px; }
    .sd-field label { display: block; font-size: 11px; font-weight: 600; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .3px; margin-bottom: 3px; }
    .sd-field div { font-size: 13px; color: var(--gray-text); word-break: break-word; }
    .sd-empty { font-size: 13px; color: var(--gray-muted); font-style: italic; }
    .sd-table { width: 100%; border-collapse: collapse; }
    .sd-table th { font-size: 11px; text-align: left; color: var(--gray-muted); padding: 8px 10px; background: var(--gray-soft); }
    .sd-table td { font-size: 13px; padding: 10px; border-bottom: 1px solid var(--border-light); }
    .sd-badge { font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 999px; }
    .sd-badge.aprobado { background: var(--green-bg); color: var(--green); }
    .sd-badge.pendiente { background: var(--amber-bg); color: var(--amber); }
    .sd-badge.rechazado { background: #fef2f2; color: #b91c1c; }
    .sd-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 8px; }
    .sd-btn {
        padding: 10px 18px; background: var(--green); color: #fff; border: none; border-radius: 8px;
        font-size: 13px; font-weight: 700; font-family: inherit; cursor: pointer;
    }
    .sd-btn:hover { filter: brightness(.95); }
    .sd-aviso { background: #fff7ed; border: 1px solid #fcd34d; border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #92400e; margin-bottom: 16px; }
    .sd-flash { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; }
    .sd-flash.err { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    a.doc-link { color: var(--purple); font-weight: 600; text-decoration: none; font-size: 12px; }
</style>
@endpush

@section('content')

<a href="{{ route('admin.solicitudes-alta') }}" class="sd-back">← Volver a solicitudes</a>

@if(session('error'))
<div class="sd-flash err">{{ session('error') }}</div>
@endif

<div class="sd-aviso">
    Revisa el formulario, datos bancarios, documentos y contactos. Cuando Contabilidad/Dirección validen a mano, aprueba la solicitud.
</div>

@php
    $i = $identificacion ?? [];
    $v = fn ($k, $fallback = '—') => filled($i[$k] ?? null) ? $i[$k] : $fallback;
@endphp

<div class="sd-card">
    <h3>Datos generales</h3>
    <div class="sd-grid">
        <div class="sd-field"><label>ID interno</label><div>#{{ $proveedor->id }}</div></div>
        <div class="sd-field"><label>Correo</label><div>{{ $proveedor->correo }}</div></div>
        <div class="sd-field"><label>Teléfono registro</label><div>{{ $proveedor->telefono ?: '—' }}</div></div>
        <div class="sd-field"><label>Tipo persona (cuenta)</label><div>{{ $proveedor->tipo_persona }}</div></div>
        <div class="sd-field"><label>Registro</label><div>{{ optional($proveedor->created_at)->format('d/m/Y H:i') }}</div></div>
    </div>
</div>

<div class="sd-card">
    <h3>Formulario de identificación</h3>
    @if(empty($i))
        <p class="sd-empty">Aún no ha guardado el formulario. Pídele que lo complete desde Onboarding → Formulario datos bancarios.</p>
    @else
        <div class="sd-grid">
            <div class="sd-field"><label>Fecha</label><div>{{ $v('fecha') }}</div></div>
            <div class="sd-field"><label>Tipo persona</label><div>{{ $v('tipo_persona') }}</div></div>
            <div class="sd-field"><label>Razón social</label><div>{{ $v('razon_social') }}</div></div>
            <div class="sd-field"><label>Apellido paterno</label><div>{{ $v('apellido_paterno') }}</div></div>
            <div class="sd-field"><label>Apellido materno</label><div>{{ $v('apellido_materno') }}</div></div>
            <div class="sd-field"><label>Nombre(s)</label><div>{{ $v('nombres') }}</div></div>
            <div class="sd-field"><label>Calle</label><div>{{ $v('calle') }}</div></div>
            <div class="sd-field"><label>Núm. exterior</label><div>{{ $v('num_exterior') }}</div></div>
            <div class="sd-field"><label>Núm. interior</label><div>{{ $v('num_interior') }}</div></div>
            <div class="sd-field"><label>Colonia</label><div>{{ $v('colonia') }}</div></div>
            <div class="sd-field"><label>Municipio</label><div>{{ $v('municipio') }}</div></div>
            <div class="sd-field"><label>Estado</label><div>{{ $v('estado') }}</div></div>
            <div class="sd-field"><label>Ciudad</label><div>{{ $v('ciudad') }}</div></div>
            <div class="sd-field"><label>País</label><div>{{ $v('pais') }}</div></div>
            <div class="sd-field"><label>C.P.</label><div>{{ $v('cp') }}</div></div>
            <div class="sd-field"><label>Teléfono</label><div>{{ $v('telefono') }}</div></div>
            <div class="sd-field"><label>Celular</label><div>{{ $v('celular') }}</div></div>
            <div class="sd-field"><label>Teléfono 2</label><div>{{ $v('telefono2') }}</div></div>
            <div class="sd-field"><label>Extensión</label><div>{{ $v('extension') }}</div></div>
            <div class="sd-field"><label>Correo (formulario)</label><div>{{ $v('correo') }}</div></div>
            <div class="sd-field"><label>Firma</label><div>{{ $v('nombre_firma') }}</div></div>
        </div>
        @if(!empty($i['docs']) && is_array($i['docs']))
            <p style="margin-top:14px;font-size:12px;color:var(--gray-muted);"><strong>Docs marcados en el formato:</strong> {{ implode(', ', $i['docs']) }}</p>
        @endif
    @endif
</div>

<div class="sd-card">
    <h3>Datos bancarios</h3>
    @if(empty($i['banco']) && empty($i['clabe']) && empty($i['cuenta']))
        <p class="sd-empty">Sin datos bancarios guardados en el formulario.</p>
    @else
        <div class="sd-grid">
            <div class="sd-field"><label>CLABE</label><div>{{ $v('clabe') }}</div></div>
            <div class="sd-field"><label>Cuenta</label><div>{{ $v('cuenta') }}</div></div>
            <div class="sd-field"><label>Institución</label><div>{{ $v('banco') }}</div></div>
        </div>
    @endif
</div>

<div class="sd-card">
    <h3>Documentos subidos</h3>
    @if($proveedor->documentos->isEmpty())
        <p class="sd-empty">No ha subido documentos fiscales aún.</p>
    @else
        <table class="sd-table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Estatus</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($proveedor->documentos as $doc)
                <tr>
                    <td>{{ $tiposLabel[$doc->tipo] ?? $doc->tipo }}</td>
                    <td><span class="sd-badge {{ $doc->estatus }}">{{ ucfirst($doc->estatus) }}</span></td>
                    <td>{{ optional($doc->created_at)->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($doc->archivo)
                            <a class="doc-link" href="{{ asset('storage/'.$doc->archivo) }}" target="_blank" rel="noopener">Ver archivo</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="sd-card">
    <h3>Contactos ({{ $proveedor->contactos->count() }})</h3>
    @if($proveedor->contactos->isEmpty())
        <p class="sd-empty">Sin contactos registrados.</p>
    @else
        <table class="sd-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proveedor->contactos as $c)
                <tr>
                    <td>{{ $c->nombre }}</td>
                    <td>{{ $c->rol }}</td>
                    <td>{{ $c->telefono }}</td>
                    <td>{{ $c->correo }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="sd-card">
    <h3>Aprobación de Dirección</h3>
    <p style="font-size:13px;color:var(--gray-muted);margin:0 0 14px;">Al aprobar, la cuenta queda activa y el proveedor puede usar todo el portal.</p>
    <div class="sd-actions">
        <form method="POST" action="{{ route('admin.solicitudes-alta.aprobar') }}" onsubmit="return confirm('¿Confirmar aprobación de {{ addslashes($proveedor->nombre ?? $proveedor->usuario) }}?');">
            @csrf
            <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
            <button type="submit" class="sd-btn">Aprobar y activar</button>
        </form>
    </div>
</div>

@endsection
