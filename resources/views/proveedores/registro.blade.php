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
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
        .divider { display: flex; align-items: center; gap: 12px; margin: 4px 0 16px; color: rgba(255,255,255,0.3); font-size: 12px; }
        .divider::before, .divider::after { content: ''; flex: 1; border-top: 1px solid rgba(255,255,255,0.1); }
        .ios-field select { width: 100%; border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius); padding: 13px 16px; font-size: 15px; font-family: inherit; color: #fff; background: rgba(255,255,255,0.05); transition: var(--transition); outline: none; cursor: pointer; }
        .ios-field select:focus { border-color: rgba(139,92,246,0.5); background: rgba(255,255,255,0.08); box-shadow: 0 0 0 4px rgba(107,63,160,0.15); }
        .ios-field select option { background: #2d1b4e; color: #fff; }
        .req { color: #c4b5fd; }
        .hint { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 4px; display: block; }
        .error-msg { font-size: 11px; color: #fca5a5; margin-top: 3px; }
        .usuario-preview { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: 0.02em; }
        @media (max-width: 500px) {
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="ios-login-bg">
<div class="orb-accent"></div>

<div class="ios-login-container">
    <a href="/login-proveedor" class="ios-back-link">← Volver al login</a>

    <div class="ios-brand">
        @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        <p>PORTAL DE PROVEEDORES</p>
    </div>

    <div class="ios-login-card">
        <div class="card-title">Registro de Proveedor</div>
        <div class="card-sub">Completa tus datos para crear tu cuenta</div>

        @if ($errors->any())
            <div class="ios-alert ios-alert-error"><ul style="padding-left:16px;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('proveedores.registro.guardar') }}" id="form-registro">
            @csrf
            <div class="ios-field"><label>Tipo de persona <span class="req">*</span></label>
                <select name="tipo_persona" id="reg_tipo_persona" required>
                    <option value="" disabled {{ old('tipo_persona') ? '' : 'selected' }}>Selecciona una opción</option>
                    <option value="Persona Física" {{ old('tipo_persona')=='Persona Física'?'selected':'' }}>Persona Física</option>
                    <option value="Persona Moral" {{ old('tipo_persona')=='Persona Moral'?'selected':'' }}>Persona Moral</option>
                </select>
                @error('tipo_persona')<span class="error-msg">{{ $message }}</span>@enderror
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

            <div class="ios-field">
                <label>Usuario de acceso</label>
                <input type="text" id="reg_usuario_preview" class="usuario-preview" value="{{ old('usuario_sugerido', '') }}" readonly tabindex="-1" aria-live="polite">
                <input type="hidden" name="usuario_sugerido" id="reg_usuario_sugerido" value="{{ old('usuario_sugerido', '') }}">
                <span class="hint" id="reg_usuario_hint">Se genera solo: primer nombre + apellido paterno (ej. juan.garcia). También podrás entrar con tu correo.</span>
            </div>

            <div class="form-row">
                <div class="ios-field">
                    <label>Teléfono <span class="req">*</span></label>
                    <input type="tel" name="telefono" id="reg_telefono" placeholder="10 dígitos" value="{{ old('telefono') }}" required maxlength="10" inputmode="numeric" pattern="[0-9]{10}">
                    @error('telefono')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="ios-field"><label>Correo electrónico <span class="req">*</span></label><input type="email" name="correo" id="reg_correo" placeholder="tu@correo.com" value="{{ old('correo') }}" required>@error('correo')<span class="error-msg">{{ $message }}</span>@enderror</div>
            </div>
            <div class="form-row">
                <div class="ios-field"><label>Contraseña <span class="req">*</span></label><input type="password" name="password" placeholder="Mínimo 8 caracteres" required>@error('password')<span class="error-msg">{{ $message }}</span>@enderror</div>
                <div class="ios-field"><label>Confirmar contraseña <span class="req">*</span></label><input type="password" name="password_confirmation" placeholder="Repite tu contraseña" required></div>
            </div>
            <div class="divider">Verificación de seguridad</div>
            <div style="margin-bottom:16px;"><div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>@error('g-recaptcha-response')<span class="error-msg" style="display:block;margin-top:6px;">Por favor completa el captcha</span>@enderror</div>
            <button type="submit" class="ios-btn-primary">Crear mi cuenta</button>
        </form>
        <p class="ios-register-link">¿Ya tienes cuenta? <a href="{{ route('proveedores.login') }}">Inicia sesión aquí</a></p>
    </div>

    <div class="ios-footer-text">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>
<script>
(function () {
    var tel = document.getElementById('reg_telefono');
    if (tel) {
        tel.addEventListener('input', function () {
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

    function slugRazon(str) {
        if (!str) return '';
        return str
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '.')
            .replace(/^\.+|\.+$/g, '')
            .replace(/\.{2,}/g, '.')
            .slice(0, 40);
    }

    function actualizarUsuario() {
        if (!preview || !hiddenUsuario) return;
        var valor = '';
        if (tipo && tipo.value === 'Persona Moral') {
            valor = slugRazon(razon ? razon.value : '');
            if (hint) hint.textContent = 'Se genera a partir de la razón social. También podrás entrar con tu correo.';
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

    function toggleTipo() {
        var esMoral = tipo && tipo.value === 'Persona Moral';
        if (fisica) fisica.style.display = esMoral ? 'none' : '';
        if (moral) moral.style.display = esMoral ? '' : 'none';
        if (nombres) nombres.required = !esMoral;
        if (apPaterno) apPaterno.required = !esMoral;
        if (razon) razon.required = esMoral;
        actualizarUsuario();
    }

    [nombres, apPaterno, apMaterno, razon].forEach(stripEmoji);
    if (tipo) tipo.addEventListener('change', toggleTipo);
    toggleTipo();
})();
</script>
</body>
</html>
