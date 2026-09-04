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

@if(session('exito'))
    <div class="id-card" style="border-color:#059669;background:#ecfdf5;margin-bottom:16px;">
        <p style="color:#059669;font-size:14px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            {{ session('exito') }}
        </p>
    </div>
@endif

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

@if(!empty($tieneDocsAprobados))
    <div class="id-card" style="border-color:#F59E0B;background:#FFFBEB;margin-bottom:16px;">
        <p style="color:#92400E;font-size:13px;font-weight:600;margin:0;">Aviso importante</p>
        <p style="color:#78350F;font-size:12px;margin:8px 0 0;line-height:1.5;">
            Ya validaste documentos fiscales. Si cambias <strong>banco, CLABE, cuenta, nombre/razón social, C.P. o tipo de persona</strong>,
            esos documentos se invalidan y tendrás que volver a validarlos para que coincidan con los nuevos datos.
        </p>
    </div>
@endif

<form method="POST" action="{{ route('proveedores.identificacion.guardar') }}" id="formIdentificacion" novalidate>
    @csrf
    @php
        $d = $identificacion ?? [];

        // Precargar campos con lo que el proveedor puso en el REGISTRO (si aún no llenó este formato).
        if (isset($proveedor) && $proveedor) {
            $diReg2 = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];
            $prefill = [
                'rfc' => $proveedor->rfc ?? ($diReg2['rfc'] ?? $diReg2['RFC'] ?? null),
                'correo' => $proveedor->correo ?? null,
                'telefono' => $proveedor->telefono ?? null,
                'nombres' => $diReg2['nombres'] ?? null,
                'apellido_paterno' => $diReg2['apellido_paterno'] ?? null,
                'apellido_materno' => $diReg2['apellido_materno'] ?? null,
                'razon_social' => $diReg2['razon_social'] ?? null,
            ];
            foreach ($prefill as $campo => $valor) {
                // Solo rellenar si el formato aún no tiene ese dato guardado.
                if ((! isset($d[$campo]) || $d[$campo] === '' || $d[$campo] === null) && ! empty($valor)) {
                    $d[$campo] = $valor;
                }
            }
        }
    @endphp

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
                @php
                    $tpRegistro = isset($proveedor) ? ($proveedor->tipo_persona ?? '') : '';
                    $tipoBloqueado = isset($proveedor) && $proveedor && $proveedor->tipoPersonaBloqueado();
                    $tp = old('tipo_persona', $d['tipo_persona'] ?? $tpRegistro);
                    if ($tipoBloqueado) {
                        $tp = $proveedor->tipoPersonaNormalizado();
                    }
                    if ($tp && ! in_array($tp, ['Persona Física', 'Persona Moral'], true)) {
                        $tpLower = mb_strtolower($tp);
                        if (str_contains($tpLower, 'moral')) {
                            $tp = 'Persona Moral';
                        } elseif (str_contains($tpLower, 'fís') || str_contains($tpLower, 'fis')) {
                            $tp = 'Persona Física';
                        }
                    }
                @endphp
                @if($tipoBloqueado)
                    <input type="hidden" name="tipo_persona" id="tipo_persona" value="{{ $tp }}">
                    <select disabled aria-disabled="true">
                        <option value="Persona Física" {{ $tp == 'Persona Física' ? 'selected' : '' }}>Persona Física</option>
                        <option value="Persona Moral" {{ $tp == 'Persona Moral' ? 'selected' : '' }}>Persona Moral</option>
                    </select>
                    <span style="font-size:11px;color:var(--gray-muted);margin-top:4px;">Precargado desde tu registro. No se puede cambiar (como en el SAT). Si hay error, contacta a Compras.</span>
                @else
                    <select id="tipo_persona" name="tipo_persona" required>
                        <option value="" disabled {{ $tp ? '' : 'selected' }}>Selecciona una opción</option>
                        <option value="Persona Física" {{ $tp == 'Persona Física' ? 'selected' : '' }}>Persona Física</option>
                        <option value="Persona Moral" {{ $tp == 'Persona Moral' ? 'selected' : '' }}>Persona Moral</option>
                    </select>
                @endif
                @error('tipo_persona')<span class="error-msg" style="color:#DC2626;font-size:12px;">{{ $message }}</span>@enderror
            </div>
        </div>

        <div id="campos-fisica" style="display:none;">
            <div class="section-label">Persona Física</div>
            <div class="form-row cols-3">
                <div class="form-group">
                    <label for="apellido_paterno">Apellido paterno <span style="color:#DC2626">*</span></label>
                    <input type="text" id="apellido_paterno" name="apellido_paterno" value="{{ old('apellido_paterno', $d['apellido_paterno'] ?? '') }}" placeholder="Apellido paterno" class="no-emoji" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="apellido_materno">Apellido materno <span style="color:#DC2626">*</span></label>
                    <input type="text" id="apellido_materno" name="apellido_materno" value="{{ old('apellido_materno', $d['apellido_materno'] ?? '') }}" placeholder="Apellido materno" class="no-emoji" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="nombres">Nombre(s) <span style="color:#DC2626">*</span></label>
                    <input type="text" id="nombres" name="nombres" value="{{ old('nombres', $d['nombres'] ?? '') }}" placeholder="Nombre(s)" class="no-emoji" maxlength="150">
                </div>
            </div>
        </div>

        <div id="campos-moral" style="display:none;">
            <div class="section-label">Persona Moral</div>
            <div class="form-row cols-1">
                <div class="form-group">
                    <label for="razon_social">Denominación o Razón Social <span style="color:#DC2626">*</span></label>
                    <input type="text" id="razon_social" name="razon_social" value="{{ old('razon_social', $d['razon_social'] ?? '') }}" placeholder="Nombre completo de la empresa" class="no-emoji" maxlength="255">
                </div>
            </div>
        </div>

        {{-- RFC y correo: precargados del registro (RFC bloqueado para que coincida) --}}
        <div class="form-row cols-2">
            <div class="form-group">
                <label for="rfc">RFC <span style="color:#DC2626">*</span></label>
                @php
                    // RFC del registro (fuente oficial). Se precarga y se bloquea para que coincida.
                    $rfcRegistro = isset($proveedor) && $proveedor ? ($proveedor->rfc ?? '') : '';
                    if ($rfcRegistro === '') {
                        $diReg = (isset($proveedor) && is_array($proveedor->datos_identificacion)) ? $proveedor->datos_identificacion : [];
                        $rfcRegistro = $diReg['rfc'] ?? $diReg['RFC'] ?? '';
                    }
                    $rfcValor = $rfcRegistro !== '' ? $rfcRegistro : old('rfc', $d['rfc'] ?? '');
                    $rfcBloqueado = $rfcRegistro !== '';
                @endphp
                <input type="text" id="rfc" name="rfc" value="{{ strtoupper($rfcValor) }}" placeholder="Ej: VPA211201F67" maxlength="13" required class="no-emoji"
                    style="text-transform:uppercase;{{ $rfcBloqueado ? 'background:#f9fafb;' : '' }}"
                    {{ $rfcBloqueado ? 'readonly' : '' }}
                    oninput="this.value=this.value.toUpperCase().replace(/[^A-ZÑ&0-9]/g,'')">
                @if($rfcBloqueado)
                    <small style="color:var(--gray-muted);font-size:10px;">Precargado desde tu registro. Debe coincidir con el RFC registrado.</small>
                @endif
            </div>
            <div class="form-group">
                <label for="correo">Correo electrónico <span style="color:#DC2626">*</span></label>
                <input type="email" id="correo" name="correo" value="{{ old('correo', $d['correo'] ?? session('proveedor_correo')) }}" placeholder="correo@empresa.com" required>
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
                <label for="calle">Calle, avenida o vía <span style="color:#DC2626">*</span></label>
                <input type="text" id="calle" name="calle" value="{{ old('calle', $d['calle'] ?? '') }}" placeholder="Calle / avenida" required class="no-emoji" maxlength="255">
            </div>
            <div class="form-group">
                <label for="num_exterior">Número exterior <span style="color:#DC2626">*</span></label>
                <input type="text" id="num_exterior" name="num_exterior" value="{{ old('num_exterior', $d['num_exterior'] ?? '') }}" placeholder="Ext." required class="no-emoji" maxlength="50">
            </div>
            <div class="form-group">
                <label for="num_interior">Número interior</label>
                <input type="text" id="num_interior" name="num_interior" value="{{ old('num_interior', $d['num_interior'] ?? '') }}" placeholder="Int. (opcional)" class="no-emoji" maxlength="50">
            </div>
        </div>

        <div class="form-row cols-3">
            <div class="form-group">
                <label for="colonia">Colonia o fraccionamiento <span style="color:#DC2626">*</span></label>
                <select id="colonia" name="colonia" required style="padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white);">
                    <option value="{{ old('colonia', $d['colonia'] ?? '') }}">{{ old('colonia', $d['colonia'] ?? 'Se llena con C.P.') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label for="municipio">Municipio <span style="color:#DC2626">*</span></label>
                <input type="text" id="municipio" name="municipio" value="{{ old('municipio', $d['municipio'] ?? '') }}" placeholder="Municipio (editable si el C.P. no coincide)" required class="no-emoji">
            </div>
            <div class="form-group">
                <label for="estado">Entidad Federativa / Estado <span style="color:#DC2626">*</span></label>
                <input type="text" id="estado" name="estado" value="{{ old('estado', $d['estado'] ?? '') }}" placeholder="Se llena con C.P." required readonly style="background:#f9fafb;" class="no-emoji">
            </div>
        </div>

        <div class="form-row cols-4">
            <div class="form-group">
                <label for="ciudad">Ciudad o población <span style="color:#DC2626">*</span></label>
                <input type="text" id="ciudad" name="ciudad" value="{{ old('ciudad', $d['ciudad'] ?? '') }}" placeholder="Ciudad" required class="no-emoji">
            </div>
            <div class="form-group">
                <label for="pais">País <span style="color:#DC2626">*</span></label>
                <input type="text" id="pais" name="pais" value="{{ old('pais', $d['pais'] ?? 'México') }}" placeholder="País" required class="no-emoji">
            </div>
            <div class="form-group">
                <label for="cp">C.P. <span style="color:#DC2626">*</span></label>
                <input type="text" id="cp" name="cp" value="{{ old('cp', $d['cp'] ?? '') }}" placeholder="00000" maxlength="5" inputmode="numeric" pattern="[0-9]{5}" required oninput="this.value=this.value.replace(/\D/g,'').slice(0,5);buscarCP(this.value)">
                <small id="cp-loading" style="display:none;color:var(--purple);font-size:10px;">Buscando...</small>
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono <span style="color:#DC2626">*</span></label>
                <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $d['telefono'] ?? '') }}" placeholder="10 dígitos" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" required class="solo-digitos">
            </div>
        </div>

        <div class="form-row cols-3">
            <div class="form-group">
                <label for="celular">Celular <span style="color:#DC2626">*</span></label>
                <input type="tel" id="celular" name="celular" value="{{ old('celular', $d['celular'] ?? '') }}" placeholder="10 dígitos" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" required class="solo-digitos">
            </div>
            <div class="form-group">
                <label for="telefono2">Teléfono 2 (opcional)</label>
                <input type="tel" id="telefono2" name="telefono2" value="{{ old('telefono2', $d['telefono2'] ?? '') }}" placeholder="10 dígitos" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" class="solo-digitos">
            </div>
            <div class="form-group">
                <label for="extension">Extensión</label>
                <input type="text" id="extension" name="extension" value="{{ old('extension', $d['extension'] ?? '') }}" placeholder="Ext." maxlength="6" inputmode="numeric" class="solo-digitos">
            </div>
        </div>

    </div>

    {{-- Datos bancarios MXN --}}
    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Datos bancarios
        </h3>
        <p class="card-desc">Cuenta para pagos y transferencias. Todos los campos son obligatorios.</p>

        <div class="form-row cols-1">
            <div class="form-group">
                <label for="clabe">Cuenta CLABE <span style="color:#DC2626">*</span></label>
                <input type="text" id="clabe" name="clabe" value="{{ old('clabe', $d['clabe'] ?? '') }}" placeholder="18 dígitos" maxlength="18" inputmode="numeric" pattern="[0-9]{18}" required class="solo-digitos">
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label for="cuenta">Cuenta <span style="color:#DC2626">*</span></label>
                <input type="text" id="cuenta" name="cuenta" value="{{ old('cuenta', $d['cuenta'] ?? '') }}" placeholder="Número de cuenta" maxlength="20" inputmode="numeric" pattern="[0-9]{5,20}" required class="solo-digitos">
            </div>
            <div class="form-group">
                <label for="banco">Nombre de la Institución Financiera <span style="color:#DC2626">*</span></label>
                <select id="banco" name="banco" required style="padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white);">
                    <option value="" disabled {{ old('banco', $d['banco'] ?? '') ? '' : 'selected' }}>Selecciona tu banco</option>
                    @php
                        $bancos = [
                            'BBVA', 'Banamex', 'Banorte', 'Santander', 'HSBC',
                            'Scotiabank', 'Inbursa', 'Banco Azteca', 'BanCoppel',
                            'Nu México (Nu)', 'BanBajío', 'Banregio', 'Afirme',
                            'Hey Banco', 'Albo', 'Stori', 'Openbank',
                            'Multiva', 'Mifel', 'Bansi', 'CI Banco', 'Intercam Banco',
                            'Monex', 'Ve por Más', 'Actinver', 'Compartamos Banco',
                            'Banco del Bienestar', 'Banca Mifel',
                            'Bank of America', 'JP Morgan', 'Barclays', 'Deutsche Bank',
                            'ING Bank', 'Banco Sabadell', 'Banco Base',
                            'Banco Autofin', 'Banco Inmobiliario Mexicano',
                            'Banco S3 (México)', 'ABC Capital', 'Consubanco', 'Investa Bank',
                        ];
                        $bancoActual = old('banco', $d['banco'] ?? '');
                        if ($bancoActual === 'Citibanamex') {
                            $bancoActual = 'Banamex';
                        }
                    @endphp
                    @foreach($bancos as $b)
                        <option value="{{ $b }}" {{ $bancoActual === $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Datos bancarios USD (solo si proveedor tiene moneda DOLLAR) --}}
    @if($proveedor && $proveedor->esMonedaDollar())
        {{-- Los campos USD van ocultos dentro del mismo card de datos bancarios arriba, no como sección separada --}}
        <input type="hidden" id="clabe_usd" name="clabe_usd" value="{{ old('clabe_usd', $d['clabe_usd'] ?? '') }}">
        <input type="hidden" id="cuenta_usd" name="cuenta_usd" value="{{ old('cuenta_usd', $d['cuenta_usd'] ?? '') }}">
        <input type="hidden" id="banco_usd" name="banco_usd" value="{{ old('banco_usd', $d['banco_usd'] ?? '') }}">
    @endif

    {{-- Documentos agregados --}}
    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Documentos agregados
        </h3>
        @php
            $esRepse = false;
            if (isset($proveedor) && $proveedor) {
                $esRepse = (bool) ($proveedor->es_repse ?? false);
                if (! $esRepse) {
                    $di = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];
                    $esRepse = (bool) ($di['es_repse'] ?? false);
                }
            }
        @endphp

        <p class="card-desc">Marca todos los documentos que estás anexando a este formato. <span style="color:#DC2626">Todos son obligatorios</span>.</p>

        @unless($esRepse)
        <div class="docs-grid">
            <label class="doc-check" id="doc-acta-constitutiva">
                <input type="checkbox" name="docs[]" value="acta_constitutiva" {{ in_array('acta_constitutiva', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Acta Constitutiva <span style="color:#DC2626">*</span>
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="id_rep_legal" {{ in_array('id_rep_legal', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Identificación oficial del representante legal <span style="color:#DC2626">*</span>
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="id_contribuyente" {{ in_array('id_contribuyente', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Identificación oficial del contribuyente <span style="color:#DC2626">*</span>
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="constancia_fiscal" {{ in_array('constancia_fiscal', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Constancia de Situación Fiscal <span style="color:#DC2626">*</span>
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="opinion_cumplimiento" {{ in_array('opinion_cumplimiento', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Opinión de Cumplimiento <span style="color:#DC2626">*</span>
            </label>
            <label class="doc-check">
                <input type="checkbox" name="docs[]" value="caratula_banco" {{ in_array('caratula_banco', old('docs', $d['docs'] ?? [])) ? 'checked' : '' }}>
                Carátula de banco <span style="color:#DC2626">*</span>
            </label>
        </div>
        @error('docs')
            <p style="color:#DC2626;font-size:12px;font-weight:600;margin-top:10px;">{{ $message }}</p>
        @enderror
        @endunless

        @php
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
        @endphp

        @if($esRepse)
        <div style="margin-top:6px">
            <h3 style="font-size:14px;color:var(--purple);margin:0 0 4px;display:flex;align-items:center;gap:8px">
                Documentos REPSE
            </h3>
            <p class="card-desc" style="margin-bottom:12px">Como proveedor REPSE, debes anexar estos documentos. <span style="color:#DC2626">Todos son obligatorios</span>.</p>
            <div class="docs-grid">
                @foreach($docsRepse as $val => $label)
                    <label class="doc-check">
                        <input type="checkbox" name="docs_repse[]" value="{{ $val }}" {{ in_array($val, old('docs_repse', $d['docs_repse'] ?? [])) ? 'checked' : '' }}>
                        {{ $label }} <span style="color:#DC2626">*</span>
                    </label>
                @endforeach
            </div>
            @error('docs_repse')
                <p style="color:#DC2626;font-size:12px;font-weight:600;margin-top:10px;">{{ $message }}</p>
            @enderror
        </div>
        @endif
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
                <label for="nombre_firma">Nombre del representante legal <span style="color:#DC2626">*</span></label>
                <input type="text" id="nombre_firma" name="nombre_firma" value="{{ old('nombre_firma', $d['nombre_firma'] ?? '') }}" placeholder="Nombre completo quien firma" required class="no-emoji" maxlength="255">
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
            <button type="submit" class="btn-submit" id="btnEnviarIdentificacion">
                {{ !empty($d['banco']) || !empty($d['clabe']) ? 'Guardar cambios' : 'Enviar identificación' }}
            </button>
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
    var form = document.getElementById('formIdentificacion');
    var emojiRe = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{FE00}-\u{FE0F}\u{200D}]/gu;

    function toggleCampos() {
        var tipo = select ? select.value : '';
        var esFisica = tipo === 'Persona Física';
        var esMoral = tipo === 'Persona Moral';

        if (!fisica || !moral) return;

        fisica.style.display = esFisica ? 'block' : 'none';
        moral.style.display = esMoral ? 'block' : 'none';

        fisica.querySelectorAll('input').forEach(function (el) {
            el.required = esFisica;
            // Si el tipo está bloqueado (input hidden), no vaciar al cargar.
            if (!esFisica && select && select.tagName === 'SELECT' && !select.disabled) el.value = '';
        });
        moral.querySelectorAll('input').forEach(function (el) {
            el.required = esMoral;
            if (!esMoral && select && select.tagName === 'SELECT' && !select.disabled) el.value = '';
        });

        if (docActa) {
            docActa.style.display = esMoral ? 'flex' : 'none';
            if (!esMoral && select && select.tagName === 'SELECT' && !select.disabled) {
                var cb = docActa.querySelector('input');
                if (cb) cb.checked = false;
            }
        }
    }

    if (select && select.tagName === 'SELECT') {
        select.addEventListener('change', toggleCampos);
    }
    toggleCampos();

    // Solo dígitos
    document.querySelectorAll('.solo-digitos').forEach(function (el) {
        el.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });
        el.addEventListener('paste', function (e) {
            e.preventDefault();
            var t = (e.clipboardData || window.clipboardData).getData('text') || '';
            this.value = (this.value + t.replace(/\D/g, '')).slice(0, this.maxLength || 20);
        });
    });

    // Sin emojis
    document.querySelectorAll('.no-emoji').forEach(function (el) {
        el.addEventListener('input', function () {
            this.value = this.value.replace(emojiRe, '');
        });
    });

    function marcarError(el, msg) {
        el.style.borderColor = '#DC2626';
        el.setAttribute('title', msg || 'Campo obligatorio');
        if (!el._errHint) {
            var hint = document.createElement('small');
            hint.className = 'campo-error-hint';
            hint.style.cssText = 'color:#DC2626;font-size:11px;margin-top:4px;display:block;';
            el.parentNode.appendChild(hint);
            el._errHint = hint;
        }
        el._errHint.textContent = msg || 'Campo obligatorio';
    }

    function limpiarError(el) {
        el.style.borderColor = '';
        el.removeAttribute('title');
        if (el._errHint) {
            el._errHint.remove();
            el._errHint = null;
        }
    }

    form.addEventListener('submit', function (e) {
        var ok = true;
        var primero = null;

        form.querySelectorAll('[required]').forEach(function (el) {
            if (el.offsetParent === null && el.closest('#campos-fisica, #campos-moral')) {
                // oculto por tipo de persona
                if (!el.required) return;
            }
            var visible = el.offsetParent !== null || el.type === 'hidden';
            if (!visible && (el.closest('#campos-fisica') || el.closest('#campos-moral'))) {
                return;
            }
            limpiarError(el);
            var val = (el.value || '').trim();
            if (!val) {
                ok = false;
                marcarError(el, 'Este campo es obligatorio');
                if (!primero) primero = el;
            }
        });

        ['telefono', 'celular'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (el.value && !/^[0-9]{10}$/.test(el.value)) {
                ok = false;
                marcarError(el, 'Debe tener exactamente 10 dígitos');
                if (!primero) primero = el;
            }
        });

        var tel2 = document.getElementById('telefono2');
        if (tel2 && tel2.value && !/^[0-9]{10}$/.test(tel2.value)) {
            ok = false;
            marcarError(tel2, 'Debe tener exactamente 10 dígitos');
            if (!primero) primero = tel2;
        }

        var clabe = document.getElementById('clabe');
        if (clabe && !/^[0-9]{18}$/.test(clabe.value || '')) {
            ok = false;
            marcarError(clabe, 'La CLABE debe tener 18 dígitos');
            if (!primero) primero = clabe;
        }

        var cuenta = document.getElementById('cuenta');
        if (cuenta && !/^[0-9]{5,20}$/.test(cuenta.value || '')) {
            ok = false;
            marcarError(cuenta, 'Solo dígitos (5 a 20)');
            if (!primero) primero = cuenta;
        }

        var cp = document.getElementById('cp');
        if (cp && !/^[0-9]{5}$/.test(cp.value || '')) {
            ok = false;
            marcarError(cp, 'C.P. de 5 dígitos');
            if (!primero) primero = cp;
        }

        form.querySelectorAll('.no-emoji').forEach(function (el) {
            if (/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/u.test(el.value || '')) {
                ok = false;
                marcarError(el, 'No se permiten emojis');
                if (!primero) primero = el;
            }
        });

        if (!ok) {
            e.preventDefault();
            if (primero) {
                primero.focus();
                primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            alert('Completa todos los campos obligatorios correctamente antes de enviar.');
            return;
        }
    });
})();

// ── Autocompletado de CP con API SEPOMEX México ──
var cpTimeout = null;
function buscarCP(cp) {
    cp = cp.replace(/\D/g, '');
    if (cp.length !== 5) return;

    var loading = document.getElementById('cp-loading');
    if (loading) loading.style.display = 'inline';

    clearTimeout(cpTimeout);
    cpTimeout = setTimeout(function() {
        fetch('/api/codigo-postal/' + cp)
            .then(function(res) { return res.ok ? res.json() : null; })
            .then(function(data) {
                if (loading) loading.style.display = 'none';
                if (!data || data.error) {
                    liberarCamposManuales();
                    return;
                }

                var estadoEl = document.getElementById('estado');
                var municipioEl = document.getElementById('municipio');
                var ciudadEl = document.getElementById('ciudad');

                if (data.estado && estadoEl) estadoEl.value = data.estado;
                if (data.municipio && municipioEl) municipioEl.value = data.municipio;
                if (data.ciudad && ciudadEl) ciudadEl.value = data.ciudad;

                // Si no hay municipio, liberar campo para que lo escriba manualmente
                if (!data.municipio && municipioEl) {
                    municipioEl.removeAttribute('readonly');
                    municipioEl.style.background = '';
                }

                if (data.colonias && data.colonias.length) {
                    var coloniaSelect = document.getElementById('colonia');
                    if (coloniaSelect) {
                        // Si era input, convertir a select
                        if (coloniaSelect.tagName === 'INPUT') {
                            var newSel = document.createElement('select');
                            newSel.id = 'colonia';
                            newSel.name = 'colonia';
                            newSel.required = true;
                            newSel.style.cssText = 'padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;color:var(--gray-text);background:var(--white);width:100%;';
                            coloniaSelect.parentNode.replaceChild(newSel, coloniaSelect);
                            coloniaSelect = newSel;
                        }
                        coloniaSelect.innerHTML = '';
                        data.colonias.forEach(function(c) {
                            var opt = document.createElement('option');
                            opt.value = c;
                            opt.textContent = c;
                            coloniaSelect.appendChild(opt);
                        });
                        // Opción para capturar la colonia a mano si el C.P. no la trae bien.
                        var optOtra = document.createElement('option');
                        optOtra.value = '__otra__';
                        optOtra.textContent = 'Otra (escribir a mano)';
                        coloniaSelect.appendChild(optOtra);
                        // Al elegir "Otra", convertir el select en input de texto editable.
                        coloniaSelect.onchange = function() {
                            if (this.value === '__otra__') {
                                var inp = document.createElement('input');
                                inp.type = 'text';
                                inp.id = 'colonia';
                                inp.name = 'colonia';
                                inp.required = true;
                                inp.className = 'no-emoji';
                                inp.placeholder = 'Escribe tu colonia o fraccionamiento';
                                inp.style.cssText = 'padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;width:100%;';
                                this.parentNode.replaceChild(inp, this);
                                inp.focus();
                            }
                        };
                    }
                } else {
                    liberarCamposManuales();
                }
            })
            .catch(function() {
                if (loading) loading.style.display = 'none';
                liberarCamposManuales();
            });
    }, 300);
}

function liberarCamposManuales() {
    var coloniaSelect = document.getElementById('colonia');
    if (coloniaSelect && coloniaSelect.tagName === 'SELECT') {
        coloniaSelect.outerHTML = '<input type="text" id="colonia" name="colonia" required class="no-emoji" placeholder="Escribe la colonia" style="padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;">';
    }
    var mun = document.getElementById('municipio');
    if (mun) { mun.removeAttribute('readonly'); mun.style.background = ''; }
    var est = document.getElementById('estado');
    if (est) { est.removeAttribute('readonly'); est.style.background = ''; }
}

// ── Autollenado del banco a partir de la CLABE (3 primeros dígitos = código del banco) ──
(function() {
    // Código de banco (3 dígitos de la CLABE) -> nombre en la lista del select.
    var CLABE_BANCOS = {
        '002': 'Banamex',
        '012': 'BBVA',
        '014': 'Santander',
        '019': 'Banco del Bienestar',
        '021': 'HSBC',
        '030': 'Banco Bajío',
        '036': 'Inbursa',
        '042': 'Mifel',
        '044': 'Scotiabank',
        '058': 'Banregio',
        '059': 'Invex',
        '062': 'Afirme',
        '072': 'Banorte',
        '106': 'Bank of America',
        '108': 'Monex',
        '110': 'Ve por Más',
        '112': 'BanCoppel',
        '113': 'Ve por Más',
        '116': 'ING Bank',
        '124': 'Deutsche Bank',
        '127': 'Azteca',
        '128': 'Autofin',
        '130': 'CI Banco',
        '132': 'Banco Multiva',
        '133': 'Actinver',
        '136': 'Intercam Banco',
        '137': 'BanCoppel',
        '138': 'ABC Capital',
        '140': 'Banco Sabadell',
        '143': 'Consubanco',
        '145': 'Banco Base',
        '147': 'Banco Azteca',
        '148': 'Banco Azteca',
        '150': 'Bansi',
        '155': 'Banco S3 (México)',
        '166': 'Banco del Bienestar',
        '600': 'Compartamos Banco',
        '638': 'Nu México (Nu)',
        '646': 'STP',
        '723': 'Stori',
    };

    var bancoAliases = {
        'Banco Bajío': 'BanBajío',
        'Banco Multiva': 'Multiva',
        'Azteca': 'Banco Azteca',
    };

    var clabeInput = document.getElementById('clabe');
    if (!clabeInput) return;

    function bloquearBanco(select) {
        select.disabled = true;
        select.style.background = '#f3f4f6';
        select.style.color = '#6b7280';
        select.style.cursor = 'not-allowed';
        // El proveedor no puede cambiarlo, pero el valor debe enviarse: usar hidden espejo.
        var hid = document.getElementById('banco_hidden');
        if (!hid) {
            hid = document.createElement('input');
            hid.type = 'hidden';
            hid.id = 'banco_hidden';
            hid.name = 'banco';
            select.parentNode.appendChild(hid);
            select.removeAttribute('name'); // el name lo lleva el hidden
        }
        hid.value = select.value;
    }

    function desbloquearBanco(select) {
        select.disabled = false;
        select.style.background = 'var(--white)';
        select.style.color = 'var(--gray-text)';
        select.style.cursor = '';
        var hid = document.getElementById('banco_hidden');
        if (hid) { hid.remove(); select.setAttribute('name', 'banco'); }
    }

    function autollenarBanco() {
        var select = document.getElementById('banco');
        if (!select) return;
        var clabe = (clabeInput.value || '').replace(/\D/g, '');

        if (clabe.length < 3) {
            desbloquearBanco(select);
            return;
        }
        var codigo = clabe.substring(0, 3);
        var nombre = CLABE_BANCOS[codigo];
        if (nombre && bancoAliases[nombre]) nombre = bancoAliases[nombre];

        if (nombre) {
            // Buscar la opción que coincida.
            var encontrado = false;
            for (var i = 0; i < select.options.length; i++) {
                if (select.options[i].value === nombre) {
                    select.selectedIndex = i;
                    encontrado = true;
                    break;
                }
            }
            if (encontrado) {
                bloquearBanco(select);
            } else {
                // Banco no está en la lista: dejar editable para que lo elijan.
                desbloquearBanco(select);
            }
        } else {
            // Código no reconocido: dejar editable.
            desbloquearBanco(select);
        }
    }

    clabeInput.addEventListener('input', autollenarBanco);
    clabeInput.addEventListener('blur', autollenarBanco);
    // Si ya venía con CLABE precargada, autollenar al cargar.
    if ((clabeInput.value || '').replace(/\D/g, '').length >= 3) {
        autollenarBanco();
    }
})();
</script>
@endpush
