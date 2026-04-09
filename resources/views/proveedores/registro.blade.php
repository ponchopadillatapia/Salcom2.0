<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <title>Registro — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #2d1b4e 0%, #4A2070 30%, #6B3FA0 60%, #9C6DD0 100%);
            -webkit-font-smoothing: antialiased; position: relative; overflow-y: auto; padding: 40px 24px;
        }
        body::before { content:''; position:fixed; width:600px; height:600px; border-radius:50%; background:rgba(107,63,160,0.15); top:-200px; right:-150px; filter:blur(80px); }
        body::after { content:''; position:fixed; width:400px; height:400px; border-radius:50%; background:rgba(139,92,246,0.12); bottom:-100px; left:-100px; filter:blur(60px); }

        .container { position:relative; z-index:1; width:100%; max-width:480px; display:flex; flex-direction:column; align-items:center; gap:20px; }
        .brand { text-align:center; }
        .brand h1 { font-size:28px; font-weight:700; color:#fff; }
        .brand p { font-size:12px; font-weight:500; letter-spacing:3px; color:rgba(255,255,255,0.5); text-transform:uppercase; margin-top:4px; }

        .card {
            width:100%; background:rgba(255,255,255,0.08); backdrop-filter:blur(20px);
            border:1px solid rgba(255,255,255,0.12); border-radius:16px; padding:32px 28px;
        }
        .card-title { font-size:20px; font-weight:700; color:#fff; margin-bottom:4px; }
        .card-sub { font-size:13px; color:rgba(255,255,255,0.5); margin-bottom:20px; }

        .alert-errors { background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3); color:#fca5a5; border-radius:8px; padding:10px 14px; font-size:13px; margin-bottom:16px; }
        .alert-errors ul { padding-left:16px; }

        .field { margin-bottom:16px; }
        .field label { display:block; font-size:11px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:5px; letter-spacing:0.5px; text-transform:uppercase; }
        .field label .req { color:#c4b5fd; }
        .field input, .field select {
            width:100%; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:11px 14px;
            font-size:14px; font-family:inherit; color:#fff; background:rgba(255,255,255,0.06); outline:none; transition:all .2s;
        }
        .field input::placeholder { color:rgba(255,255,255,0.3); }
        .field input:focus, .field select:focus { border-color:rgba(107,63,160,0.6); background:rgba(255,255,255,0.1); box-shadow:0 0 0 3px rgba(107,63,160,0.15); }
        .field select { cursor:pointer; }
        .field select option { background:#2d1b4e; color:#fff; }
        .error-msg { font-size:11px; color:#fca5a5; margin-top:3px; }

        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .divider { display:flex; align-items:center; gap:12px; margin:4px 0 16px; color:rgba(255,255,255,0.3); font-size:12px; }
        .divider::before, .divider::after { content:''; flex:1; border-top:1px solid rgba(255,255,255,0.1); }

        .btn-submit {
            width:100%; padding:13px; background:#6B3FA0; color:#fff; border:none; border-radius:10px;
            font-family:inherit; font-size:15px; font-weight:600; cursor:pointer; transition:all .2s;
            box-shadow:0 4px 16px rgba(107,63,160,0.3);
        }
        .btn-submit:hover { background:#4A2070; transform:translateY(-1px); }

        .login-link { text-align:center; margin-top:16px; font-size:13px; color:rgba(255,255,255,0.5); }
        .login-link a { color:#c4b5fd; text-decoration:none; font-weight:600; }
        .login-link a:hover { color:#ddd6fe; }
        .footer-text { font-size:11px; color:rgba(255,255,255,0.25); text-align:center; }

        @media (max-width:500px) { .form-row { grid-template-columns:1fr; } .card { padding:24px 20px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="brand"><h1>Industrias Salcom</h1><p>Portal de Proveedores</p></div>
    <div class="card">
        <div class="card-title">Registro de Proveedor</div>
        <div class="card-sub">Completa tus datos para crear tu cuenta</div>

        @if ($errors->any())
            <div class="alert-errors"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('proveedores.registro.guardar') }}">
            @csrf
            <div class="field"><label>Nombre completo <span class="req">*</span></label><input type="text" name="nombre" placeholder="Tu nombre completo" value="{{ old('nombre') }}" required>@error('nombre')<span class="error-msg">{{ $message }}</span>@enderror</div>
            <div class="field"><label>Tipo de persona <span class="req">*</span></label>
                <select name="tipo_persona" required>
                    <option value="" disabled {{ old('tipo_persona') ? '' : 'selected' }}>Selecciona una opción</option>
                    <option value="Persona Física" {{ old('tipo_persona')=='Persona Física'?'selected':'' }}>Persona Física</option>
                    <option value="Persona Moral" {{ old('tipo_persona')=='Persona Moral'?'selected':'' }}>Persona Moral</option>
                </select>
                @error('tipo_persona')<span class="error-msg">{{ $message }}</span>@enderror
            </div>
            <div class="form-row">
                <div class="field"><label>Teléfono <span class="req">*</span></label><input type="tel" name="telefono" placeholder="33 1234 5678" value="{{ old('telefono') }}" required>@error('telefono')<span class="error-msg">{{ $message }}</span>@enderror</div>
                <div class="field"><label>Correo electrónico <span class="req">*</span></label><input type="email" name="correo" placeholder="tu@correo.com" value="{{ old('correo') }}" required>@error('correo')<span class="error-msg">{{ $message }}</span>@enderror</div>
            </div>
            <div class="form-row">
                <div class="field"><label>Contraseña <span class="req">*</span></label><input type="password" name="password" placeholder="Mínimo 8 caracteres" required>@error('password')<span class="error-msg">{{ $message }}</span>@enderror</div>
                <div class="field"><label>Confirmar contraseña <span class="req">*</span></label><input type="password" name="password_confirmation" placeholder="Repite tu contraseña" required></div>
            </div>
            <div class="divider">Verificación de seguridad</div>
            <div style="margin-bottom:16px;"><div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>@error('g-recaptcha-response')<span class="error-msg" style="display:block;margin-top:6px;">Por favor completa el captcha</span>@enderror</div>
            <button type="submit" class="btn-submit">Crear mi cuenta</button>
        </form>
        <p class="login-link">¿Ya tienes cuenta? <a href="{{ route('proveedores.login') }}">Inicia sesión aquí</a></p>
    </div>
    <div class="footer-text">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>
</body>
</html>
