<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Datos — Industrias Salcom</title>
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
    <a href="{{ route('proveedores.portal') }}" class="ios-back-link">← Volver al portal</a>

    <div class="ios-brand">
        @include('partials.logo-salcom', ['size' => 'lg', 'color' => 'light'])
        <p>PORTAL DE PROVEEDORES</p>
    </div>

    <div class="ios-login-card">
        <div class="card-title">Actualizar Datos</div>
        <div class="card-sub">Modifica la información de tu cuenta</div>

        @if ($errors->any())
            <div class="ios-alert ios-alert-error"><ul style="padding-left:16px;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if(session('mensaje'))
            <div class="ios-alert ios-alert-success">{{ session('mensaje') }}</div>
        @endif

        <form method="POST" action="{{ route('proveedores.actualizacion.guardar') }}">
            @csrf @method('PUT')
            <div class="ios-field"><label>Nombre completo <span class="req">*</span></label><input type="text" name="nombre" placeholder="Tu nombre completo" value="{{ old('nombre') }}" required>@error('nombre')<span class="error-msg">{{ $message }}</span>@enderror</div>
            <div class="ios-field"><label>Tipo de persona <span class="req">*</span></label>
                <select name="tipo_persona" required>
                    <option value="" disabled {{ old('tipo_persona') ? '' : 'selected' }}>Selecciona una opción</option>
                    <option value="Persona Física" {{ old('tipo_persona')=='Persona Física'?'selected':'' }}>Persona Física</option>
                    <option value="Persona Moral" {{ old('tipo_persona')=='Persona Moral'?'selected':'' }}>Persona Moral</option>
                </select>
                @error('tipo_persona')<span class="error-msg">{{ $message }}</span>@enderror
            </div>
            <div class="form-row">
                <div class="ios-field"><label>Teléfono <span class="req">*</span></label><input type="tel" name="telefono" placeholder="33 1234 5678" value="{{ old('telefono') }}" required>@error('telefono')<span class="error-msg">{{ $message }}</span>@enderror</div>
                <div class="ios-field"><label>Correo electrónico <span class="req">*</span></label><input type="email" name="correo" placeholder="tu@correo.com" value="{{ old('correo') }}" required>@error('correo')<span class="error-msg">{{ $message }}</span>@enderror</div>
            </div>
            <div class="divider">Cambiar contraseña (opcional)</div>
            <div class="form-row">
                <div class="ios-field"><label>Nueva contraseña</label><input type="password" name="password" placeholder="Dejar vacío para no cambiar">@error('password')<span class="error-msg">{{ $message }}</span>@enderror</div>
                <div class="ios-field"><label>Confirmar contraseña</label><input type="password" name="password_confirmation" placeholder="Repite la nueva contraseña"></div>
            </div>
            <button type="submit" class="ios-btn-primary">Guardar cambios</button>
        </form>
    </div>

    <div class="ios-footer-text">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>
</body>
</html>
