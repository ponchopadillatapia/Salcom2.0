@extends('layouts.admin')

@section('title', 'Identificación del Proveedor')

@section('hero')
<div class="hero-band">
    <h1>Identificación del Proveedor</h1>
    <p>Formato — {{ $proveedor->nombre ?? $proveedor->usuario }} (solo lectura)</p>
</div>
@endsection

@push('styles')
<style>
    .ver-back { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--purple); text-decoration: none; margin-bottom: 16px; }
    .ver-back:hover { text-decoration: underline; }

    .id-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 24px; margin-bottom: 20px; box-shadow: var(--shadow-sm); }
    .id-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .id-card .card-desc { font-size: 13px; color: var(--gray-muted); margin-bottom: 20px; }

    .section-label { font-size: 12px; font-weight: 700; color: var(--purple); text-transform: uppercase; letter-spacing: .4px; margin: 4px 0 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border-light); }

    .form-row { display: grid; gap: 16px; margin-bottom: 16px; }
    .form-row.cols-1 { grid-template-columns: 1fr; }
    .form-row.cols-2 { grid-template-columns: 1fr 1fr; }
    .form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
    .form-row.cols-4 { grid-template-columns: 1.5fr 1fr 1fr 1.2fr; }
    .form-row.domicilio-cp { grid-template-columns: 180px 1fr; align-items: start; }
    .form-row.nums { grid-template-columns: minmax(0, 1fr) 140px 140px; }
    .form-row.contacto { grid-template-columns: 1fr 160px 160px 120px; }

    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
    .form-group input,
    .form-group select {
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        font-family: inherit;
        color: var(--gray-text);
        outline: none;
        background: #f9fafb;
        width: 100%;
        box-sizing: border-box;
    }
    .form-group.corto input { max-width: 180px; }
    .form-hint { font-size: 11px; color: var(--gray-muted); line-height: 1.4; }

    .docs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .doc-check {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        background: var(--gray-soft);
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-text);
        border: 1.5px solid transparent;
    }
    .doc-check.marcado { border-color: var(--purple-mid, #c4b5fd); background: var(--purple-subtle, #f5f3ff); }
    .doc-check input { width: 16px; height: 16px; accent-color: var(--purple); flex-shrink: 0; }

    .decl-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 8px; }
    .decl-box { background: var(--gray-soft); border-radius: 10px; padding: 16px; font-size: 13px; color: var(--gray-text); line-height: 1.55; }

    .aviso-box { background: var(--gray-soft); border-radius: 10px; padding: 16px 18px; font-size: 11px; color: var(--gray-muted); line-height: 1.6; }
    .aviso-box strong { color: var(--gray-text); display: block; margin-bottom: 6px; font-size: 12px; }

    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px; flex-wrap: wrap; align-items: center; }
    .btn-aprobar { padding: 10px 24px; background: var(--green, #16a34a); color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-rechazar { padding: 10px 24px; background: var(--red, #dc2626); color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-aprobar:hover, .btn-rechazar:hover { opacity: .92; }
    .btn-aprobar:disabled { opacity: .45; cursor: not-allowed; }
    .btn-revisado { padding: 8px 16px; background: #d97706; color: #fff; border: none; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-revisado:hover { background: #b45309; }
    .doc-revisar-form { margin-top: 12px; }
    .ver-flash { border-radius: 10px; padding: 12px 16px; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
    .ver-flash.ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #059669; }
    .ver-flash.err { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
    .aprobar-hint { font-size: 12px; color: #92400e; margin: 0; flex: 1; min-width: 220px; }

    .seccion-doc {
        display: block; text-decoration: none; color: inherit;
        border-radius: 10px; padding: 1rem 1.1rem; margin-bottom: 0.65rem;
        border: 1px solid var(--border-light); background: var(--white);
        border-left: 4px solid var(--green); transition: box-shadow .15s, transform .15s;
    }
    .seccion-doc:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); transform: translateY(-1px); }
    .seccion-doc.pendiente { border-left-color: #d97706; background: #fffbeb; }
    .seccion-doc-body { display: block; text-decoration: none; color: inherit; }
    .seccion-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.55rem; flex-wrap: wrap; }
    .seccion-titulo { font-weight: 700; font-size: 0.9rem; color: var(--gray-text); flex: 1; }
    .status-pill { font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; background: #ecfdf5; color: #059669; }
    .status-pill.pendiente { background: #fffbeb; color: #d97706; }
    .detalle-item { font-size: 0.82rem; padding: 4px 0; display: flex; align-items: flex-start; gap: 0.5rem; color: #047857; line-height: 1.4; }
    .seccion-doc.pendiente .detalle-item { color: #92400e; }
    .detalle-item svg { flex-shrink: 0; margin-top: 2px; }
    .doc-dl-hint { margin-top: 10px; font-size: 12px; font-weight: 600; color: var(--purple); display: inline-flex; align-items: center; gap: 6px; }
    .ver-empty { text-align: center; padding: 28px 20px; color: var(--gray-muted); font-size: 14px; }
    .ver-hint { font-size: 13px; color: var(--gray-muted); margin-bottom: 12px; line-height: 1.45; }

    @media (max-width: 900px) {
        .form-row.cols-2, .form-row.cols-3, .form-row.cols-4,
        .form-row.domicilio-cp, .form-row.nums, .form-row.contacto,
        .docs-grid, .decl-grid { grid-template-columns: 1fr; }
        .form-group.corto input { max-width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $d = $datosIdent ?? [];
    $campo = function (string $key, $fallback = '') use ($d, $proveedor) {
        $v = $d[$key] ?? null;
        if ($v === null || $v === '') {
            if (in_array($key, ['correo', 'telefono', 'rfc'], true)) {
                $v = $proveedor->{$key} ?? $fallback;
            } else {
                $v = $fallback;
            }
        }
        return ($v === null || $v === '') ? '' : (string) $v;
    };

    $tp = $d['tipo_persona'] ?? ($proveedor->tipo_persona ?? '');
    if ($tp && ! in_array($tp, ['Persona Física', 'Persona Moral'], true)) {
        $tpLower = mb_strtolower($tp);
        if (str_contains($tpLower, 'moral')) {
            $tp = 'Persona Moral';
        } elseif (str_contains($tpLower, 'fís') || str_contains($tpLower, 'fis')) {
            $tp = 'Persona Física';
        }
    }
    $esMoral = $tp === 'Persona Moral';
    $esFisica = $tp === 'Persona Física';

    $esRepse = (bool) ($proveedor->es_repse ?? false);
    if (! $esRepse) {
        $esRepse = (bool) ($d['es_repse'] ?? false);
    }

    $docsMarcados = $d['docs'] ?? [];
    $docsRepseMarcados = $d['docs_repse'] ?? [];
    $docsRepse = [
        'repse_registro' => '1. Registro REPSE vigente (copia de la aceptación del registro)',
        'repse_isr_retenido' => '2. Declaración de ISR retenido a trabajadores + pago bancario ISR',
        'repse_iva' => '3. Declaración de IVA + acuse IVA',
        'repse_opinion_sat' => '4. Opinión de cumplimiento SAT',
        'repse_opinion_infonavit' => '5. Opinión de cumplimiento INFONAVIT',
        'repse_opinion_imss' => '6. Opinión de cumplimiento IMSS',
        'repse_pago_imss_infonavit' => '7. Pago bancario IMSS e INFONAVIT',
        'repse_cedula_imss' => '8. Cédula de determinación de cuotas IMSS',
        'repse_cedula_obrero_patronal' => '9. Cédula de cuotas obrero patronales, aportaciones y amortizaciones',
        'repse_sipare' => '10. SIPARE',
        'repse_sua' => '11. SUA',
        'repse_cfdi_nomina' => '12. CFDI de nóminas (XML y PDF) del personal que da el servicio',
    ];
    $docsBase = [
        'acta_constitutiva' => 'Acta Constitutiva',
        'id_rep_legal' => 'Identificación oficial del representante legal',
        'id_contribuyente' => 'Identificación oficial del contribuyente',
        'constancia_fiscal' => 'Constancia de Situación Fiscal (CIF)',
        'opinion_cumplimiento' => 'Opinión de Cumplimiento SAT',
        'caratula_banco' => 'Carátula de banco',
    ];

    $fechaVal = $campo('fecha');
    if ($fechaVal !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $fechaVal)) {
        $fechaVal = substr($fechaVal, 0, 10);
    } elseif ($fechaVal === '' && $proveedor->created_at) {
        $fechaVal = $proveedor->created_at->format('Y-m-d');
    }

    $puedeActuar = ! $proveedor->activo;
    $tienePendientes = $docsAprobados->where('estatus', 'pendiente')->isNotEmpty();
@endphp

<a href="{{ route('admin.solicitudes-alta') }}" class="ver-back">← Volver a solicitudes</a>

@if(session('mensaje'))
    <div class="ver-flash ok">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
    <div class="ver-flash err">{{ session('error') }}</div>
@endif

{{-- A. Identificación del proveedor --}}
<div class="id-card">
    <h3>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Identificación del proveedor
    </h3>
    <p class="card-desc">Datos enviados por el proveedor en el formato de identificación.</p>

    <div class="form-row cols-2">
        <div class="form-group">
            <label>Fecha</label>
            <input type="date" value="{{ $fechaVal }}" readonly tabindex="-1">
        </div>
        <div class="form-group">
            <label>Tipo de persona</label>
            <input type="text" value="{{ $tp !== '' ? $tp : '—' }}" readonly tabindex="-1">
        </div>
    </div>

    @if($esFisica)
        <div class="section-label">Persona Física</div>
        <div class="form-row cols-3">
            <div class="form-group">
                <label>Apellido paterno</label>
                <input type="text" value="{{ $campo('apellido_paterno') }}" readonly tabindex="-1">
            </div>
            <div class="form-group">
                <label>Apellido materno</label>
                <input type="text" value="{{ $campo('apellido_materno') }}" readonly tabindex="-1">
            </div>
            <div class="form-group">
                <label>Nombre(s)</label>
                <input type="text" value="{{ $campo('nombres') }}" readonly tabindex="-1">
            </div>
        </div>
    @endif

    @if($esMoral)
        <div class="section-label">Persona Moral</div>
        <div class="form-row cols-1">
            <div class="form-group">
                <label>Denominación o Razón Social</label>
                <input type="text" value="{{ $campo('razon_social', $proveedor->nombre ?? '') }}" readonly tabindex="-1">
            </div>
        </div>
    @endif

    <div class="form-row cols-2">
        <div class="form-group">
            <label>RFC</label>
            <input type="text" value="{{ strtoupper($campo('rfc')) }}" readonly tabindex="-1" style="text-transform:uppercase;">
        </div>
        <div class="form-group">
            <label>Correo electrónico</label>
            <input type="text" value="{{ $campo('correo') }}" readonly tabindex="-1">
        </div>
    </div>

    <div class="form-row contacto">
        <div class="form-group">
            <label>Teléfono</label>
            <input type="text" value="{{ $campo('telefono') }}" readonly tabindex="-1">
        </div>
        <div class="form-group">
            <label>Celular</label>
            <input type="text" value="{{ $campo('celular') }}" readonly tabindex="-1">
        </div>
        <div class="form-group">
            <label>Teléfono 2</label>
            <input type="text" value="{{ $campo('telefono2') }}" readonly tabindex="-1">
        </div>
        <div class="form-group corto">
            <label>Extensión</label>
            <input type="text" value="{{ $campo('extension') }}" readonly tabindex="-1">
        </div>
    </div>
</div>

{{-- B. Domicilio fiscal --}}
<div class="id-card">
    <h3>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        Domicilio fiscal
    </h3>
    <p class="card-desc">Domicilio capturado en el formato de identificación.</p>

    <div class="form-row domicilio-cp">
        <div class="form-group corto">
            <label>C.P.</label>
            <input type="text" value="{{ $campo('cp') }}" readonly tabindex="-1">
        </div>
    </div>

    <div class="form-row cols-3">
        <div class="form-group">
            <label>Entidad Federativa / Estado</label>
            <input type="text" value="{{ $campo('estado') }}" readonly tabindex="-1">
        </div>
        <div class="form-group">
            <label>Municipio</label>
            <input type="text" value="{{ $campo('municipio') }}" readonly tabindex="-1">
        </div>
        <div class="form-group">
            <label>Ciudad o población</label>
            <input type="text" value="{{ $campo('ciudad') }}" readonly tabindex="-1">
        </div>
    </div>

    <div class="form-row cols-2">
        <div class="form-group">
            <label>Colonia o fraccionamiento</label>
            <input type="text" value="{{ $campo('colonia') }}" readonly tabindex="-1">
        </div>
        <div class="form-group">
            <label>País</label>
            <input type="text" value="{{ $campo('pais', 'México') }}" readonly tabindex="-1">
        </div>
    </div>

    <div class="form-row nums">
        <div class="form-group">
            <label>Calle, avenida o vía</label>
            <input type="text" value="{{ $campo('calle') }}" readonly tabindex="-1">
        </div>
        <div class="form-group corto">
            <label>Núm. exterior</label>
            <input type="text" value="{{ $campo('num_exterior') }}" readonly tabindex="-1">
        </div>
        <div class="form-group corto">
            <label>Núm. interior</label>
            <input type="text" value="{{ $campo('num_interior') }}" readonly tabindex="-1">
        </div>
    </div>
</div>

{{-- C. Datos bancarios --}}
<div class="id-card">
    <h3>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Datos bancarios
    </h3>
    <p class="card-desc">Cuenta para pagos y transferencias.</p>

    <div class="form-row cols-1">
        <div class="form-group">
            <label>Cuenta CLABE</label>
            <input type="text" value="{{ $campo('clabe') }}" readonly tabindex="-1">
        </div>
    </div>
    <div class="form-row cols-2">
        <div class="form-group">
            <label>Nombre de la Institución Financiera</label>
            <input type="text" value="{{ $campo('banco') }}" readonly tabindex="-1">
        </div>
        <div class="form-group">
            <label>Número de cuenta</label>
            <input type="text" value="{{ $campo('cuenta') }}" readonly tabindex="-1">
        </div>
    </div>

    @if($proveedor->esMonedaDollar() || $campo('clabe_usd') !== '' || $campo('cuenta_usd') !== '' || $campo('banco_usd') !== '')
        <div class="section-label">Cuenta USD</div>
        <div class="form-row cols-1">
            <div class="form-group">
                <label>CLABE USD</label>
                <input type="text" value="{{ $campo('clabe_usd') }}" readonly tabindex="-1">
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label>Banco USD</label>
                <input type="text" value="{{ $campo('banco_usd') }}" readonly tabindex="-1">
            </div>
            <div class="form-group">
                <label>Número de cuenta USD</label>
                <input type="text" value="{{ $campo('cuenta_usd') }}" readonly tabindex="-1">
            </div>
        </div>
    @endif
</div>

{{-- D. Representante legal --}}
@if($esMoral)
<div class="id-card">
    <h3>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Representante legal
    </h3>
    <p class="card-desc">Nombre de quien firma este formato en representación de la empresa.</p>
    <div class="form-row cols-1">
        <div class="form-group">
            <label>Nombre completo del representante legal</label>
            <input type="text" value="{{ $campo('nombre_firma') }}" readonly tabindex="-1">
        </div>
    </div>
</div>
@endif

{{-- Contactos --}}
<div class="id-card">
    <h3>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Contactos
    </h3>
    <p class="card-desc">{{ $proveedor->contactos->count() }} registrados (mínimo 2 para aprobar).</p>
    @forelse($proveedor->contactos as $c)
        <div class="form-row cols-4">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" value="{{ $c->nombre }}" readonly tabindex="-1">
            </div>
            <div class="form-group">
                <label>Rol</label>
                <input type="text" value="{{ $c->rol }}" readonly tabindex="-1">
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" value="{{ $c->telefono }}" readonly tabindex="-1">
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="text" value="{{ $c->correo }}" readonly tabindex="-1">
            </div>
        </div>
    @empty
        <p class="card-desc" style="margin-bottom:0;color:#991b1b;">Sin contactos registrados.</p>
    @endforelse
</div>

{{-- E. Documentación declarada --}}
<div class="id-card">
    <h3>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Documentación declarada
    </h3>
    <p class="card-desc">Documentos que el proveedor marcó al enviar el formato.</p>

    @unless($esRepse)
    <div class="docs-grid">
        @foreach($docsBase as $val => $label)
            @php $marcado = in_array($val, $docsMarcados, true); @endphp
            <label class="doc-check {{ $marcado ? 'marcado' : '' }}">
                <input type="checkbox" {{ $marcado ? 'checked' : '' }} disabled>
                {{ $label }}
            </label>
        @endforeach
    </div>
    @endunless

    @if($esRepse)
        <h3 style="font-size:14px;color:var(--purple);margin:0 0 12px;">Documentos REPSE</h3>
        <div class="docs-grid">
            @foreach($docsRepse as $val => $label)
                @php $marcado = in_array($val, $docsRepseMarcados, true); @endphp
                <label class="doc-check {{ $marcado ? 'marcado' : '' }}">
                    <input type="checkbox" {{ $marcado ? 'checked' : '' }} disabled>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    @endif
</div>

{{-- F. Declaración --}}
<div class="id-card">
    <h3>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Declaración
    </h3>
    <div class="decl-grid">
        <div class="decl-box">Declaro bajo protesta de decir verdad que todos y cada uno de los datos proporcionados son verdaderos.</div>
        <div class="decl-box">La información será validada por personal de Industrias Salcom S.A. de C.V.</div>
    </div>
</div>

{{-- Documentos del expediente --}}
<div class="id-card">
    <h3>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        DOCUMENTOS DEL EXPEDIENTE
    </h3>
    @if($docsAprobados->isEmpty())
        <div class="ver-empty">
            <p style="font-weight:600;color:var(--gray-text);margin-bottom:6px;">Sin documentos correctos aún</p>
            <p style="margin:0;">Cuando el proveedor complete la validación fiscal, aquí verás el detalle de cada documento.</p>
        </div>
    @else
        <p class="ver-hint">
            {{ $proveedor->tipo_persona ?? '—' }}
            · {{ $docsAprobados->where('estatus', 'aprobado')->count() }} aprobado(s){{ $docsAprobados->where('estatus', 'pendiente')->count() > 0 ? ' · '.$docsAprobados->where('estatus', 'pendiente')->count().' en revisión manual' : '' }}.
            Haz clic en un documento para descargarlo. Los marcados en <strong style="color:#d97706;">naranja</strong> requieren revisión manual: márcalos como revisados para poder aprobar.
        </p>

        @foreach($docsAprobados as $doc)
            @php
                $estatus = $doc->estatus ?? 'aprobado';
                $esPendiente = $estatus === 'pendiente';
                $stroke = $esPendiente ? '#d97706' : '#059669';
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
            <div class="seccion-doc {{ $esPendiente ? 'pendiente' : '' }}">
                <a href="{{ $href }}" class="seccion-doc-body" title="Descargar PDF">
                    <div class="seccion-header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span class="seccion-titulo">{{ $tiposLabel[$doc->tipo] ?? ucfirst(str_replace('_', ' ', (string) $doc->tipo)) }}</span>
                        <span class="status-pill {{ $esPendiente ? 'pendiente' : '' }}">{{ $esPendiente ? 'Revisión manual' : 'Aprobado' }}</span>
                    </div>
                    @forelse($hallazgos as $h)
                        <div class="detalle-item">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="{{ $stroke }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ is_array($h) ? json_encode($h, JSON_UNESCAPED_UNICODE) : $h }}
                        </div>
                    @empty
                        <div class="detalle-item">{{ $esPendiente ? 'Requiere revisión manual del admin' : 'Validación automática aprobada' }}</div>
                    @endforelse
                    <div class="doc-dl-hint">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Clic para descargar PDF
                    </div>
                </a>
                @if($esPendiente && $puedeActuar)
                    <form method="POST" action="{{ route('admin.solicitudes-alta.documento.revisar', $doc) }}" class="doc-revisar-form" onsubmit="return confirm('¿Confirmas que revisaste este documento y es válido?\n\nQuedará aprobado para el alta del proveedor.');">
                        @csrf
                        <button type="submit" class="btn-revisado">✓ Marcar como revisado</button>
                    </form>
                @endif
            </div>
        @endforeach
    @endif
</div>

<div class="id-card">
    <div class="aviso-box">
        <strong>Aviso de Privacidad</strong>
        Industrias Salcom, S.A. de C.V., con domicilio en Calle 2 No. 10540, Col. Parque Industrial El Salto, El Salto, Jalisco, México, C.P. 45680, trata sus datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares, con la finalidad de identificarlo como proveedor, gestionar la relación comercial y realizar pagos.
    </div>

    @if($puedeActuar)
    <div class="form-actions">
        @if($tienePendientes)
            <p class="aprobar-hint">Hay documentos en revisión manual. Márcalos como revisados para habilitar la aprobación.</p>
        @endif
        <form method="POST" action="{{ route('admin.solicitudes-alta.rechazar') }}" onsubmit="return confirm('¿Rechazar la solicitud de {{ addslashes($proveedor->nombre ?? $proveedor->usuario) }}?\n\nNo se elimina la cuenta: el proveedor sigue registrado e inactivo y deberá volver a llenar datos bancarios y documentos.');">
            @csrf
            <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
            <button type="submit" class="btn-rechazar">✕ Rechazar</button>
        </form>
        @if($tienePendientes)
            <button type="button" class="btn-aprobar" disabled title="Marca los documentos en revisión manual como revisados">✓ Aprobar</button>
        @else
            <form method="POST" action="{{ route('admin.solicitudes-alta.aprobar') }}" onsubmit="return confirm('¿Aprobar y activar a {{ addslashes($proveedor->nombre ?? $proveedor->usuario) }}?');">
                @csrf
                <input type="hidden" name="proveedor_id" value="{{ $proveedor->id }}">
                <button type="submit" class="btn-aprobar">✓ Aprobar</button>
            </form>
        @endif
    </div>
    @endif
</div>
@endsection
