<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <title>Registro — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="/css/ios-theme.css" rel="stylesheet">
    <style>
        .ios-login-container.registro-wide {
            max-width: 860px;
        }
        .ios-login-card.registro-card {
            padding: 28px 32px 24px;
        }
        .registro-head { margin-bottom: 6px; }
        .registro-head .card-title {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            letter-spacing: -0.4px;
            line-height: 1.2;
        }
        .registro-head .card-sub {
            font-size: 14px;
            line-height: 1.45;
            color: rgba(255,255,255,0.5);
            margin: 4px 0 0;
        }
        .form-section-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.42);
            margin: 2px 0 0;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px 18px; }
        .form-row-foot {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        .form-row-foot .g-recaptcha { display: inline-block; }
        .divider { display: flex; align-items: center; gap: 12px; margin: 2px 0 0; color: rgba(255,255,255,0.3); font-size: 12px; }
        .divider::before, .divider::after { content: ''; flex: 1; border-top: 1px solid rgba(255,255,255,0.1); }
        .ios-field select { width: 100%; border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius); padding: 13px 16px; font-size: 15px; font-family: inherit; color: #fff; background: rgba(255,255,255,0.05); transition: var(--transition); outline: none; cursor: pointer; }
        .ios-field select:focus { border-color: rgba(139,92,246,0.5); background: rgba(255,255,255,0.08); box-shadow: 0 0 0 4px rgba(107,63,160,0.15); }
        .ios-field select option { background: #2d1b4e; color: #fff; }
        .req { color: #c4b5fd; }
        .hint { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 4px; display: block; }
        .error-msg { font-size: 11px; color: #fca5a5; margin-top: 3px; }
        .usuario-preview { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: 0.02em; }
        .ios-login-card.registro-card > form .ios-btn-primary {
            width: auto;
            min-width: 240px;
            margin: 0 auto;
        }
        @media (max-width: 760px) {
            .ios-login-container.registro-wide { max-width: 420px; }
            .ios-login-card.registro-card { padding: 22px 18px 20px; }
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
            .ios-login-card.registro-card > form .ios-btn-primary { width: 100%; }
        }
    </style>
</head>
<body class="ios-login-bg">
<div class="orb-accent"></div>

<div class="ios-login-container registro-wide">
    <a href="/login-proveedor" class="ios-back-link" id="txt_back_login">← Volver al login</a>

    <div class="ios-brand">
        @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        <p id="txt_portal">PORTAL DE PROVEEDORES</p>
    </div>

    <div class="ios-login-card registro-card">
        <div class="registro-head">
            <div class="card-title" id="txt_card_title">Registro de Proveedor</div>
            <div class="card-sub" id="txt_card_sub">Completa tus datos para crear tu cuenta</div>
        </div>

        @if ($errors->any())
            <div class="ios-alert ios-alert-error"><ul style="padding-left:16px;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('proveedores.registro.guardar') }}" id="form-registro">
            @csrf

            {{-- Selector de tipo de proveedor: nacional (flujo fiscal MX) vs extranjero (importación, C-TPAT en inglés). --}}
            <input type="hidden" name="es_extranjero" id="reg_es_extranjero" value="{{ old('es_extranjero', '0') }}">
            <div class="form-section-title">Supplier type / Tipo de proveedor</div>
            <div class="form-row" style="margin-bottom:6px;">
                <div class="ios-field" style="grid-column:1 / -1;">
                    <select id="reg_origen" onchange="setOrigen(this.value)">
                        <option value="nacional" {{ old('es_extranjero','0')==='1' ? '' : 'selected' }}>National supplier (México) · Proveedor nacional</option>
                        <option value="extranjero" {{ old('es_extranjero','0')==='1' ? 'selected' : '' }}>Foreign supplier (import) · Proveedor extranjero</option>
                    </select>
                </div>
            </div>

            {{-- ──────────── BLOQUE NACIONAL (español, fiscal MX) ──────────── --}}
            <div id="bloque-nacional">
            <div class="form-section-title">Identificación</div>
            <div class="form-row">
                <div class="ios-field">
                    <label>Tipo de persona <span class="req">*</span></label>
                    <select name="tipo_persona" id="reg_tipo_persona" required>
                        <option value="" disabled {{ old('tipo_persona') ? '' : 'selected' }}>Selecciona una opción</option>
                        <option value="Persona Física" {{ old('tipo_persona')=='Persona Física'?'selected':'' }}>Persona Física</option>
                        <option value="Persona Moral" {{ old('tipo_persona')=='Persona Moral'?'selected':'' }}>Persona Moral</option>
                    </select>
                    @error('tipo_persona')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="ios-field">
                    <label>¿Es proveedor REPSE? <span class="req">*</span></label>
                    <select name="es_repse" id="reg_es_repse" required>
                        <option value="" disabled {{ old('es_repse') !== null && old('es_repse') !== '' ? '' : 'selected' }}>Selecciona una opción</option>
                        <option value="1" {{ old('es_repse')==='1' ? 'selected' : '' }}>Sí, estoy registrado en el REPSE</option>
                        <option value="0" {{ old('es_repse')==='0' ? 'selected' : '' }}>No</option>
                    </select>
                    <span class="hint">Registro de Prestadoras de Servicios Especializados (STPS).</span>
                    @error('es_repse')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
            </div>

            <div id="campos-fisica" style="{{ old('tipo_persona', '') === 'Persona Moral' ? 'display:none' : '' }}">
                <div class="form-row-3">
                    <div class="ios-field">
                        <label>Nombre(s) <span class="req">*</span></label>
                        <input type="text" name="nombres" id="reg_nombres" placeholder="Juan Carlos" value="{{ old('nombres') }}" maxlength="150" autocomplete="given-name">
                        @error('nombres')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>
                    <div class="ios-field">
                        <label>Apellido paterno <span class="req">*</span></label>
                        <input type="text" name="apellido_paterno" id="reg_apellido_paterno" placeholder="García" value="{{ old('apellido_paterno') }}" maxlength="100" autocomplete="family-name">
                        @error('apellido_paterno')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>
                    <div class="ios-field">
                        <label>Apellido materno</label>
                        <input type="text" name="apellido_materno" id="reg_apellido_materno" placeholder="López" value="{{ old('apellido_materno') }}" maxlength="100">
                        @error('apellido_materno')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div id="campos-moral" style="{{ old('tipo_persona') === 'Persona Moral' ? '' : 'display:none' }}">
                <div class="ios-field">
                    <label>Razón social <span class="req">*</span></label>
                    <input type="text" name="razon_social" id="reg_razon_social" placeholder="Nombre de la empresa" value="{{ old('razon_social') }}" maxlength="255">
                    @error('razon_social')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="ios-field" id="campo-rfc">
                    <label>RFC <span class="req">*</span></label>
                    <input type="text" name="rfc" id="reg_rfc" placeholder="Ej. ABCD010203XY9" value="{{ old('rfc') }}" maxlength="13" autocomplete="off" style="text-transform: uppercase;" required>
                    <span class="hint" id="reg_rfc_hint">13 caracteres (persona física).</span>
                    @error('rfc')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="ios-field">
                    <label>Usuario de acceso</label>
                    <input type="text" id="reg_usuario_preview" class="usuario-preview" value="{{ old('usuario_sugerido', '') }}" readonly tabindex="-1" aria-live="polite">
                    <input type="hidden" name="usuario_sugerido" id="reg_usuario_sugerido" value="{{ old('usuario_sugerido', '') }}">
                    <span class="hint" id="reg_usuario_hint">Se genera automáticamente (ej. juan.garcia). También podrás entrar con tu correo.</span>
                </div>
            </div>
            </div>{{-- /#bloque-nacional --}}

            {{-- ──────────── BLOQUE EXTRANJERO (English, import / C-TPAT) ──────────── --}}
            <div id="bloque-extranjero" style="{{ old('es_extranjero','0')==='1' ? '' : 'display:none' }}">
                <div class="form-section-title">Company information</div>
                <div class="form-row">
                    <div class="ios-field">
                        <label>Company name <span class="req">*</span></label>
                        <input type="text" name="company_name" id="reg_company_name" placeholder="Company legal name" value="{{ old('company_name') }}" maxlength="255">
                        @error('company_name')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>
                    <div class="ios-field">
                        <label>Tax ID number <span class="req">*</span></label>
                        <input type="text" name="tax_id_number" id="reg_tax_id" placeholder="Tax ID / EIN" value="{{ old('tax_id_number') }}" maxlength="100">
                        @error('tax_id_number')<span class="error-msg">{{ $message }}</span>@enderror
                    </div>
                </div>
                <span class="hint">You will complete the full Business Profile and the C-TPAT security questionnaire after signing in. The required documents (address proof, tax ID proof, contact information and the bank letter) are uploaded there as well.</span>
            </div>

            <div class="form-section-title" id="sec-contacto">Contacto y acceso</div>
            <div class="form-row">
                <div class="ios-field">
                    <label id="lbl_telefono">Teléfono <span class="req">*</span></label>
                    <input type="tel" name="telefono" id="reg_telefono" placeholder="10 dígitos" value="{{ old('telefono') }}" required maxlength="40" inputmode="tel">
                    @error('telefono')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="ios-field">
                    <label id="lbl_correo">Correo electrónico <span class="req">*</span></label>
                    <input type="email" name="correo" id="reg_correo" placeholder="tu@correo.com" value="{{ old('correo') }}" required>
                    @error('correo')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="form-row">
                <div class="ios-field">
                    <label id="lbl_password">Contraseña <span class="req">*</span></label>
                    <input type="password" name="password" placeholder="Mínimo 8 caracteres" required>
                    @error('password')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="ios-field">
                    <label id="lbl_password_conf">Confirmar contraseña <span class="req">*</span></label>
                    <input type="password" name="password_confirmation" placeholder="Repite tu contraseña" required>
                </div>
            </div>

            <div class="divider" id="txt_divider">Verificación de seguridad</div>
            <div class="form-row-foot">
                <div>
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                    @error('g-recaptcha-response')<span class="error-msg" style="display:block;margin-top:6px;">Por favor completa el captcha</span>@enderror
                </div>
                <button type="submit" class="ios-btn-primary">Crear mi cuenta</button>
            </div>
        </form>
        <p class="ios-register-link" id="txt_have_account">¿Ya tienes cuenta? <a href="{{ route('proveedores.login') }}">Inicia sesión aquí</a></p>
    </div>

    <div class="ios-footer-text" id="txt_footer">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>
<script>
(function () {
    var tel = document.getElementById('reg_telefono');
    if (tel) {
        tel.addEventListener('input', function () {
            // Para nacional: solo 10 dígitos. Para extranjero: permitir + y formato internacional.
            var flag = document.getElementById('reg_es_extranjero');
            if (flag && flag.value === '1') { return; }
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
    }

    var tipo = document.getElementById('reg_tipo_persona');
    var fisica = document.getElementById('campos-fisica');
    var moral = document.getElementById('campos-moral');
    var nombres = document.getElementById('reg_nombres');
    var apPaterno = document.getElementById('reg_apellido_paterno');
    var apMaterno = document.getElementById('reg_apellido_materno');
    var razon = document.getElementById('reg_razon_social');
    var rfc = document.getElementById('reg_rfc');
    var rfcHint = document.getElementById('reg_rfc_hint');
    var preview = document.getElementById('reg_usuario_preview');
    var hiddenUsuario = document.getElementById('reg_usuario_sugerido');
    var hint = document.getElementById('reg_usuario_hint');

    function stripEmoji(el) {
        if (!el) return;
        el.addEventListener('input', function () {
            this.value = this.value.replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '');
            actualizarUsuario();
        });
    }

    function slugPart(str) {
        if (!str) return '';
        return str
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '')
            .slice(0, 40);
    }

    function primerNombre(str) {
        if (!str) return '';
        var parts = str.trim().split(/\s+/).filter(Boolean);
        return parts[0] || '';
    }

    var stopRazon = {
        de: 1, del: 1, la: 1, las: 1, los: 1, el: 1, y: 1, e: 1, en: 1, para: 1,
        sa: 1, sc: 1, srl: 1, spr: 1, cv: 1, ac: 1, sapi: 1, sofom: 1, enr: 1
    };

    function slugRazonCorta(str) {
        if (!str) return '';
        var limpio = str
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/\b(s\.?\s*a\.?\s*(de\s*)?c\.?\s*v\.?|s\.?\s*de\s*r\.?\s*l\.?(\s*de\s*c\.?\s*v\.?)?|s\.?\s*p\.?\s*r\.?\s*(de\s*)?r\.?\s*l\.?|s\.?\s*a\.?\s*p\.?\s*i\.?|a\.?\s*c\.?)\b/gi, ' ')
            .replace(/[^a-z0-9\s]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
        var palabras = limpio.split(' ').filter(function (p) {
            return p && p.length > 1 && !stopRazon[p];
        });
        var tomadas = palabras.slice(0, 2).map(function (p) { return p.slice(0, 16); });
        if (!tomadas.length && limpio) {
            tomadas = [limpio.replace(/\s+/g, '').slice(0, 20)];
        }
        return tomadas.join('.').slice(0, 24);
    }

    function actualizarUsuario() {
        if (!preview || !hiddenUsuario) return;
        var valor = '';
        if (tipo && tipo.value === 'Persona Moral') {
            valor = slugRazonCorta(razon ? razon.value : '');
            if (hint) hint.textContent = 'Se genera corto a partir de la razón social (ej. operadora.servicios). También podrás entrar con tu correo.';
        } else {
            var n = slugPart(primerNombre(nombres ? nombres.value : ''));
            var a = slugPart(apPaterno ? apPaterno.value : '');
            if (n && a) valor = n + '.' + a;
            else if (n) valor = n;
            else if (a) valor = a;
            if (hint) hint.textContent = 'Se genera solo: primer nombre + apellido paterno (ej. juan.garcia). También podrás entrar con tu correo.';
        }
        preview.value = valor || '—';
        hiddenUsuario.value = valor;
    }

    function actualizarRfcUi() {
        var esMoral = tipo && tipo.value === 'Persona Moral';
        var maxLen = esMoral ? 12 : 13;
        if (rfc) {
            rfc.required = true;
            rfc.maxLength = maxLen;
            rfc.placeholder = esMoral ? 'Ej. ABC010203XY9' : 'Ej. ABCD010203XY9';
            if (rfc.value.length > maxLen) {
                rfc.value = rfc.value.slice(0, maxLen);
            }
        }
        if (rfcHint) {
            rfcHint.textContent = esMoral
                ? '12 caracteres (persona moral).'
                : '13 caracteres (persona física).';
        }
    }

    function toggleTipo() {
        var esMoral = tipo && tipo.value === 'Persona Moral';
        if (fisica) fisica.style.display = esMoral ? 'none' : '';
        if (moral) moral.style.display = esMoral ? '' : 'none';
        if (nombres) nombres.required = !esMoral;
        if (apPaterno) apPaterno.required = !esMoral;
        if (razon) razon.required = esMoral;
        actualizarRfcUi();
        actualizarUsuario();
    }

    if (rfc) {
        rfc.addEventListener('input', function () {
            var esMoral = tipo && tipo.value === 'Persona Moral';
            var maxLen = esMoral ? 12 : 13;
            this.value = this.value
                .toUpperCase()
                .replace(/[^A-Z0-9Ñ&]/g, '')
                .slice(0, maxLen);
        });
    }

    [nombres, apPaterno, apMaterno, razon].forEach(stripEmoji);
    if (tipo) tipo.addEventListener('change', toggleTipo);
    toggleTipo();

    // ── Toggle Nacional / Extranjero ──
    // POR QUÉ: ambos usan el mismo form; según el tipo se muestra/exige un set de campos u otro
    // y se traducen las etiquetas de las secciones compartidas (contacto/acceso).
    window.setOrigen = function (valor) {
        var esExtranjero = valor === 'extranjero';
        var bloqueNac = document.getElementById('bloque-nacional');
        var bloqueExt = document.getElementById('bloque-extranjero');
        var flag = document.getElementById('reg_es_extranjero');
        var companyName = document.getElementById('reg_company_name');
        var taxId = document.getElementById('reg_tax_id');

        if (flag) flag.value = esExtranjero ? '1' : '0';
        if (bloqueNac) bloqueNac.style.display = esExtranjero ? 'none' : '';
        if (bloqueExt) bloqueExt.style.display = esExtranjero ? '' : 'none';

        // Campos nacionales: solo requeridos/activos si NO es extranjero.
        if (tipo) tipo.required = !esExtranjero;
        if (rfc) rfc.required = !esExtranjero;
        var repse = document.getElementById('reg_es_repse');
        if (repse) repse.required = !esExtranjero;
        if (!esExtranjero) { toggleTipo(); }
        else {
            // Al pasar a extranjero, quitar required de los campos MX de persona.
            if (nombres) nombres.required = false;
            if (apPaterno) apPaterno.required = false;
            if (razon) razon.required = false;
        }

        // Campos extranjeros: requeridos solo si ES extranjero.
        if (companyName) companyName.required = esExtranjero;
        if (taxId) taxId.required = esExtranjero;

        // ── Traducir TODOS los textos compartidos cuando se elige extranjero ──
        // POR QUÉ: el bloque extranjero ya está en inglés, pero el resto de la página (encabezado,
        // sección contacto, placeholders, footer, links) seguía en español. Aquí se traduce todo.
        function setText(id, en, es) { var el = document.getElementById(id); if (el) el.textContent = esExtranjero ? en : es; }
        function setHtml(id, en, es) { var el = document.getElementById(id); if (el) el.innerHTML = esExtranjero ? en : es; }
        function setPh(el, en, es) { if (el) el.placeholder = esExtranjero ? en : es; }

        setText('txt_portal', 'SUPPLIER PORTAL', 'PORTAL DE PROVEEDORES');
        setText('txt_card_title', 'Supplier Registration', 'Registro de Proveedor');
        setText('txt_card_sub', 'Fill in your details to create your account', 'Completa tus datos para crear tu cuenta');
        setText('txt_back_login', '← Back to login', '← Volver al login');
        setText('txt_divider', 'Security verification', 'Verificación de seguridad');
        setHtml('txt_have_account',
            esExtranjero
                ? 'Already have an account? <a href="{{ route('proveedores.login') }}">Sign in here</a>'
                : '¿Ya tienes cuenta? <a href="{{ route('proveedores.login') }}">Inicia sesión aquí</a>',
            '¿Ya tienes cuenta? <a href="{{ route('proveedores.login') }}">Inicia sesión aquí</a>');
        setText('txt_footer',
            '© {{ date('Y') }} Industrias Salcom. All rights reserved.',
            '© {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.');

        // Sección contacto / acceso + etiquetas.
        setText('sec-contacto', 'Contact & access', 'Contacto y acceso');
        setHtml('lbl_telefono', (esExtranjero ? 'Telephone' : 'Teléfono') + ' <span class="req">*</span>');
        setHtml('lbl_correo', (esExtranjero ? 'Email' : 'Correo electrónico') + ' <span class="req">*</span>');
        setHtml('lbl_password', (esExtranjero ? 'Password' : 'Contraseña') + ' <span class="req">*</span>');
        setHtml('lbl_password_conf', (esExtranjero ? 'Confirm password' : 'Confirmar contraseña') + ' <span class="req">*</span>');

        // Placeholders compartidos.
        setPh(document.getElementById('reg_telefono'), 'Phone number', '10 dígitos');
        setPh(document.getElementById('reg_correo'), 'you@email.com', 'tu@correo.com');
        setPh(document.querySelector('input[name=password]'), 'At least 8 characters', 'Mínimo 8 caracteres');
        setPh(document.querySelector('input[name=password_confirmation]'), 'Repeat your password', 'Repite tu contraseña');

        var btn = document.querySelector('#form-registro .ios-btn-primary');
        if (btn) btn.textContent = esExtranjero ? 'Create my account' : 'Crear mi cuenta';
    };

    // Estado inicial (respeta old() tras un error de validación).
    setOrigen(document.getElementById('reg_origen') ? document.getElementById('reg_origen').value : 'nacional');
})();
</script>
</body>
</html>
