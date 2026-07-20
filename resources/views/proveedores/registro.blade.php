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
        .divider { display: flex; align-items: center; gap: 12px; margin: 4px 0 16px; color: rgba(255,255,255,0.3); font-size: 12px; }
        .divider::before, .divider::after { content: ''; flex: 1; border-top: 1px solid rgba(255,255,255,0.1); }
        .ios-field select { width: 100%; border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius); padding: 13px 16px; font-size: 15px; font-family: inherit; color: #fff; background: rgba(255,255,255,0.05); transition: var(--transition); outline: none; cursor: pointer; }
        .ios-field select:focus { border-color: rgba(139,92,246,0.5); background: rgba(255,255,255,0.08); box-shadow: 0 0 0 4px rgba(107,63,160,0.15); }
        .ios-field select option { background: #2d1b4e; color: #fff; }
        .req { color: #c4b5fd; }
        .error-msg { font-size: 11px; color: #fca5a5; margin-top: 3px; }
        @media (max-width: 500px) { .form-row { grid-template-columns: 1fr; } }
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

        <form method="POST" action="{{ route('proveedores.registro.guardar') }}">
            @csrf
            <div class="ios-field">
                <label>Nombre completo <span class="req">*</span></label>
                <input type="text" name="nombre" id="reg_nombre" placeholder="Apellido paterno Apellido materno Nombre(s)" value="{{ old('nombre') }}" required maxlength="255">
                <span style="font-size:11px;color:rgba(255,255,255,0.45);margin-top:4px;display:block;">Orden: primero apellidos, luego nombre(s). Ej: García López Juan Carlos</span>
                @error('nombre')<span class="error-msg">{{ $message }}</span>@enderror
            </div>
            <div class="ios-field"><label>Tipo de persona <span class="req">*</span></label>
                <select name="tipo_persona" required>
                    <option value="" disabled {{ old('tipo_persona') ? '' : 'selected' }}>Selecciona una opción</option>
                    <option value="Persona Física" {{ old('tipo_persona')=='Persona Física'?'selected':'' }}>Persona Física</option>
                    <option value="Persona Moral" {{ old('tipo_persona')=='Persona Moral'?'selected':'' }}>Persona Moral</option>
                </select>
                @error('tipo_persona')<span class="error-msg">{{ $message }}</span>@enderror
            </div>
            <div class="form-row">
                <div class="ios-field">
                    <label>Teléfono <span class="req">*</span></label>
                    <input type="tel" name="telefono" id="reg_telefono" placeholder="10 dígitos" value="{{ old('telefono') }}" required maxlength="10" inputmode="numeric" pattern="[0-9]{10}">
                    @error('telefono')<span class="error-msg">{{ $message }}</span>@enderror
                </div>
                <div class="ios-field"><label>Correo electrónico <span class="req">*</span></label><input type="email" name="correo" placeholder="tu@correo.com" value="{{ old('correo') }}" required>@error('correo')<span class="error-msg">{{ $message }}</span>@enderror</div>
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
    var nom = document.getElementById('reg_nombre');
    if (nom) {
        nom.addEventListener('input', function () {
            this.value = this.value.replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu, '');
        });
    }
})();
</script>
</body>
</html>
