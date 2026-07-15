@extends('layouts.proveedor')

@section('title', 'Identificación del Proveedor')

@section('hero')
<div class="hero-band">
    <h1>Identificación del Proveedor</h1>
    <p>Formato — Persona física o moral</p>
</div>
@endsection

@push('styles')
<style>
    .id-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 24px; margin-bottom: 20px; box-shadow: var(--shadow-sm); }
    .id-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .id-card .card-desc { font-size: 13px; color: var(--gray-muted); margin-bottom: 20px; }

    .section-label { font-size: 12px; font-weight: 700; color: var(--purple); text-transform: uppercase; letter-spacing: .4px; margin: 4px 0 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border-light); }

    .form-row { display: grid; gap: 16px; margin-bottom: 16px; }
    .form-row.cols-1 { grid-template-columns: 1fr; }
    .form-row.cols-2 { grid-template-columns: 1fr 1fr; }
    .form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
    .form-row.cols-4 { grid-template-columns: 1.5fr 1fr 1fr 1.2fr; }

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
        background: var(--white);
        transition: var(--transition);
        width: 100%;
        box-sizing: border-box;
    }
    .form-group input:focus,
    .form-group select:focus {
        border-color: var(--purple);
        box-shadow: 0 0 0 3px rgba(107, 63, 160, .1);
    }

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
        cursor: pointer;
        transition: var(--transition);
        border: 1.5px solid transparent;
    }
    .doc-check:hover { border-color: var(--purple-mid); background: var(--purple-subtle); }
    .doc-check input { width: 16px; height: 16px; accent-color: var(--purple); flex-shrink: 0; cursor: pointer; }

    .decl-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
    .decl-box { background: var(--gray-soft); border-radius: 10px; padding: 16px; font-size: 13px; color: var(--gray-text); line-height: 1.55; }

    .aviso-box { background: var(--gray-soft); border-radius: 10px; padding: 16px 18px; font-size: 11px; color: var(--gray-muted); line-height: 1.6; }
    .aviso-box strong { color: var(--gray-text); display: block; margin-bottom: 6px; font-size: 12px; }
    .aviso-box a { color: var(--purple); font-weight: 600; text-decoration: none; }
    .aviso-box a:hover { text-decoration: underline; }

    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px; flex-wrap: wrap; }
    .btn-submit { padding: 10px 24px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; transition: var(--transition); }
    .btn-submit:hover { background: var(--purple-dark); transform: translateY(-1px); }

    @media (max-width: 900px) {
        .form-row.cols-2, .form-row.cols-3, .form-row.cols-4,
        .docs-grid, .decl-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

@if ($errors->any())
    <div class="id-card" style="border-color:#DC2626;background:#FEF2F2;margin-bottom:16px;">
        <p style="color:#DC2626;font-size:13px;font-weight:600;margin:0;">Corrige los siguientes campos:</p>
        <ul style="margin:8px 0 0 18px;color:#991B1B;font-size:12px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('proveedores.identificacion.guardar') }}" id="formIdentificacion">
    @csrf
    @php $d = $identificacion ?? []; @endphp

    {{-- Datos generales --}}
    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Datos de identificación
        </h3>
        <p class="card-desc">Completa los campos según tu tipo de persona. Sin abreviaturas.</p>

        <div class="form-row cols-2">
            <div class="form-group">
                <label for="fecha">Fecha</label>
                <input type="date" id="fecha" name="fecha" value="{{ old('fecha', $d['fecha'] ?? date('Y-m-d')) }}" required>
            </div>
            <div class="form-group">
                <label for="tipo_persona">Tipo de persona</label>
                <select id="tipo_persona" name="tipo_persona" required>
                    @php $tp = old('tipo_persona', $d['tipo_persona'] ?? ''); @endphp
                    <option value="" disabled {{ $tp ? '' : 'selected' }}>Selecciona una opción</option>
                    <option value="Persona Física" {{ $tp == 'Persona Física' ? 'selected' : '' }}>Persona Física</option>
                    <option value="Persona Moral" {{ $tp == 'Persona Moral' ? 'selected' : '' }}>Persona Moral</option>
                </select>
            </div>
        </div>

        <div id="campos-fisica" style="display:none;">
            <div class="section-label">Persona Física</div>
            <div class="form-row cols-3">
                <div class="form-group">
                    <label for="apellido_paterno">Apellido paterno</label>
                    <input type="text" id="apellido_paterno" name="apellido_paterno" value="{{ old('apellido_paterno', $d['apellido_paterno'] ?? '') }}" placeholder="Apellido paterno">
                </div>
                <div class="form-group">
                    <label for="apellido_materno">Apellido materno</label>
                    <input type="text" id="apellido_materno" name="apellido_materno" value="{{ old('apellido_materno', $d['apellido_materno'] ?? '') }}" placeholder="Apellido materno">
                </div>
                <div class="form-group">
                    <label for="nombres">Nombre(s)</label>
                    <input type="text" id="nombres" name="nombres" value="{{ old('nombres', $d['nombres'] ?? '') }}" placeholder="Nombre(s)">
                </div>
            </div>
        </div>

        <div id="campos-moral" style="display:none;">
            <div class="section-label">Persona Moral</div>
            <div class="form-row cols-1">
                <div class="form-group">
                    <label for="razon_social">Denominación o Razón Social</label>
                    <input type="text" id="razon_social" name="razon_social" value="{{ old('razon_social', $d['razon_social'] ?? '') }}" placeholder="Nombre completo de la empresa">
                </div>
            </div>
        </div>
    </div>

    {{-- Domicilio --}}
    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Domicilio
        </h3>
        <p class="card-desc">Domicilio fiscal o de identificación del proveedor.</p>

        <div class="form-row cols-3">
            <div class="form-group" style="grid-column: span 1;">
                <label for="calle">Calle, avenida o vía</label>
                <input type="text" id="calle" name="calle" value="{{ old('calle', $d['calle'] ?? '') }}" placeholder="Calle / avenida">
            </div>
            <div class="form-group">
                <label for="num_exterior">Número exterior</label>
                <input type="text" id="num_exterior" name="num_exterior" value="{{ old('num_exterior', $d['num_exterior'] ?? '') }}" placeholder="Ext.">
            </div>
            <div class="form-group">
                <label for="num_interior">Número interior</label>
                <input type="text" id="num_interior" name="num_interior" value="{{ old('num_interior', $d['num_interior'] ?? '') }}" placeholder="Int. (opcional)">
            </div>
        </div>

        <div class="form-row cols-3">
            <div class="form-group">
                <label for="colonia">Colonia o fraccionamiento</label>
                <input type="text" id="colonia" name="colonia" value="{{ old('colonia', $d['colonia'] ?? '') }}" placeholder="Colonia">
            </div>
            <div class="form-group">
                <label for="municipio">Delegación / Municipio</label>
                <input type="text" id="municipio" name="municipio" value="{{ old('municipio', $d['municipio'] ?? '') }}" placeholder="Municipio">
            </div>
            <div class="form-group">
                <label for="estado">Entidad Federativa / Estado</label>
                <input type="text" id="estado" name="estado" value="{{ old('estado', $d['estado'] ?? '') }}" placeholder="Estado">
            </div>
        </div>

        <div class="form-row cols-4">
            <div class="form-group">
                <label for="ciudad">Ciudad o población</label>
                <input type="text" id="ciudad" name="ciudad" value="{{ old('ciudad', $d['ciudad'] ?? '') }}" placeholder="Ciudad">
            </div>
            <div class="form-group">
                <label for="pais">País</label>
                <input type="text" id="pais" name="pais" value="{{ old('pais', $d['pais'] ?? 'México') }}" placeholder="País">
            </div>
            <div class="form-group">
                <label for="cp">C.P.</label>
                <input type="text" id="cp" name="cp" value="{{ old('cp', $d['cp'] ?? '') }}" placeholder="00000" maxlength="10">
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $d['telefono'] ?? '') }}" placeholder="33 1234 5678">
            </div>
        </div>

        <div class="form-row cols-3">
            <div class="form-group">
                <label for="celular">Celular</label>
                <input type="tel" id="celular" name="celular" value="{{ old('celular', $d['celular'] ?? '') }}" placeholder="33 1234 5678">
            </div>
            <div class="form-group">
                <label for="telefono2">Teléfono 2 (incluir clave lada)</label>
                <input type="tel" id="telefono2" name="telefono2" value="{{ old('telefono2', $d['telefono2'] ?? '') }}" placeholder="Lada + número">
            </div>
            <div class="form-group">
                <label for="extension">Extensión</label>
                <input type="text" id="extension" name="extension" value="{{ old('extension', $d['extension'] ?? '') }}" placeholder="Ext.">
            </div>
        </div>

        <div class="form-row cols-1">
            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="{{ old('correo', $d['correo'] ?? session('proveedor_correo')) }}" placeholder="correo@empresa.com">
            </div>
        </div>
    </div>

    {{-- Datos bancarios --}}
    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Datos bancarios
        </h3>
        <p class="card-desc">Cuenta para pagos y transferencias.</p>

        <div class="form-row cols-1">
            <div class="form-group">
                <label for="clabe">Cuenta CLABE</label>
                <input type="text" id="clabe" name="clabe" value="{{ old('clabe', $d['clabe'] ?? '') }}" placeholder="18 dígitos" maxlength="18">
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label for="cuenta">Cuenta</label>
                <input type="text" id="cuenta" name="cuenta" value="{{ old('cuenta', $d['cuenta'] ?? '') }}" placeholder="Número de cuenta">
            </div>
            <div class="form-group">
                <label for="banco">Nombre de la Institución Financiera</label>
                <input type="text" id="banco" name="banco" value="{{ old('banco', $d['banco'] ?? '') }}" placeholder="Ej. BBVA, Banorte, Santander">
            </div>
        </div>
    </div>

    {{-- Documentos agregados --}}
    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Documentos agregados
        </h3>
        <p class="card-desc">Marca los documentos que estás anexando a este formato.</p>

        <div class="docs-grid">
            <label class="doc-check" id="doc-acta-constitutiva">
                <input type="checkbox" name="docs[]" value="acta_constitutiva" {{ in_array('acta_constitutiva', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Acta Constitutiva
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="id_rep_legal" {{ in_array('id_rep_legal', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Identificación oficial del representante legal
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="id_contribuyente" {{ in_array('id_contribuyente', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Identificación oficial del contribuyente
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="constancia_fiscal" {{ in_array('constancia_fiscal', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Constancia de Situación Fiscal
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="opinion_cumplimiento" {{ in_array('opinion_cumplimiento', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Opinión de Cumplimiento
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="caratula_banco" {{ in_array('caratula_banco', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Carátula de banco
            </label>
        </div>
    </div>

    {{-- Declaración y firma --}}
    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Declaración
        </h3>

        <div class="decl-grid">
            <div class="decl-box">Declaro bajo protesta de decir verdad que todos y cada uno de los datos proporcionados son verdaderos.</div>
            <div class="decl-box">La información será validada por personal de Industrias Salcom S.A. de C.V.</div>
        </div>

        <div class="form-row cols-1">
            <div class="form-group">
                <label for="nombre_firma">Nombre del representante legal</label>
                <input type="text" id="nombre_firma" name="nombre_firma" value="{{ old('nombre_firma', $d['nombre_firma'] ?? '') }}" placeholder="Nombre completo quien firma">
            </div>
        </div>
    </div>

    {{-- Aviso de privacidad --}}
    <div class="id-card">
        <div class="aviso-box">
            <strong>Aviso de Privacidad</strong>
            Industrias Salcom, S.A. de C.V., con domicilio en Calle 2 No. 10540, Col. Parque Industrial El Salto, El Salto, Jalisco, México, C.P. 45680, trata sus datos personales conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares, con la finalidad de identificarlo como proveedor, gestionar la relación comercial y realizar pagos.
            Para conocer el aviso completo, visite
            <a href="{{ route('aviso.privacidad') }}" target="_blank">Aviso de Privacidad</a>.
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Enviar identificación</button>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    var select = document.getElementById('tipo_persona');
    var fisica = document.getElementById('campos-fisica');
    var moral = document.getElementById('campos-moral');
    var docActa = document.getElementById('doc-acta-constitutiva');

    function toggleCampos() {
        var tipo = select.value;
        var esFisica = tipo === 'Persona Física';
        var esMoral = tipo === 'Persona Moral';

        fisica.style.display = esFisica ? 'block' : 'none';
        moral.style.display = esMoral ? 'block' : 'none';

        fisica.querySelectorAll('input').forEach(function (el) {
            el.required = esFisica;
            if (!esFisica) el.value = '';
        });
        moral.querySelectorAll('input').forEach(function (el) {
            el.required = esMoral;
            if (!esMoral) el.value = '';
        });

        // Acta Constitutiva solo aplica a Persona Moral
        if (docActa) {
            docActa.style.display = esMoral ? 'flex' : 'none';
            if (!esMoral) {
                var cb = docActa.querySelector('input');
                if (cb) cb.checked = false;
            }
        }
    }

    select.addEventListener('change', toggleCampos);
    toggleCampos();
})();
</script>
@endpush
