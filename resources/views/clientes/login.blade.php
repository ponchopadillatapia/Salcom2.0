<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Portal de Clientes</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',-apple-system,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2d1b4e 0%,#4A2070 30%,#6B3FA0 60%,#9C6DD0 100%);-webkit-font-smoothing:antialiased;position:relative;overflow:hidden}
        body::before{content:'';position:absolute;width:600px;height:600px;border-radius:50%;background:rgba(107,63,160,0.15);top:-200px;right:-150px;filter:blur(80px)}
        body::after{content:'';position:absolute;width:400px;height:400px;border-radius:50%;background:rgba(139,92,246,0.12);bottom:-100px;left:-100px;filter:blur(60px)}
        .container{position:relative;z-index:1;width:100%;max-width:420px;display:flex;flex-direction:column;align-items:center;gap:24px;padding:32px 24px}
        .brand{text-align:center}.brand h1{font-size:28px;font-weight:700;color:#fff}.brand p{font-size:12px;font-weight:500;letter-spacing:3px;color:rgba(255,255,255,0.5);text-transform:uppercase;margin-top:4px}
        .card{width:100%;background:rgba(255,255,255,0.08);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.12);border-radius:16px;padding:36px 32px}
        .card-title{font-size:20px;font-weight:700;color:#fff;margin-bottom:4px}.card-sub{font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:24px}
        .alert{border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:18px}
        .alert-error{background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#fca5a5}
        .alert-success{background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);color:#6ee7b7}
        .field{margin-bottom:18px}.field label{display:block;font-size:11px;font-weight:600;color:rgba(255,255,255,0.6);margin-bottom:6px;letter-spacing:0.5px;text-transform:uppercase}
        .field input{width:100%;border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:12px 14px;font-size:14px;font-family:inherit;color:#fff;background:rgba(255,255,255,0.06);outline:none;transition:all .2s}
        .field input::placeholder{color:rgba(255,255,255,0.3)}
        .field input:focus{border-color:rgba(107,63,160,0.6);background:rgba(255,255,255,0.1);box-shadow:0 0 0 3px rgba(107,63,160,0.15)}
        .btn-login{width:100%;padding:13px;background:#6B3FA0;color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:15px;font-weight:600;cursor:pointer;transition:all .2s;box-shadow:0 4px 16px rgba(107,63,160,0.3)}
        .btn-login:hover{background:#4A2070;transform:translateY(-1px)}
        .no-register{text-align:center;margin-top:18px;font-size:12px;color:rgba(255,255,255,0.4)}
        .footer-text{font-size:11px;color:rgba(255,255,255,0.25);text-align:center}
    </style>
</head>
<body>
<div class="container">
    <div class="brand">@include('partials.logo-salcom', ['height' => 44, 'color' => 'light'])<p>Portal de Clientes</p></div>
    <div class="card">
        <div class="card-title">Iniciar sesión</div>
        <div class="card-sub">Ingresa tus credenciales para continuar</div>
        @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
        @if(session('mensaje'))<div class="alert alert-success">{{ session('mensaje') }}</div>@endif
        <form method="POST" action="/login-cliente">
            @csrf
            <div class="field"><label>Usuario</label><input type="text" name="usuario" placeholder="Tu usuario asignado" value="{{ old('usuario') }}" required autofocus></div>
            <div class="field"><label>Contraseña</label><input type="password" name="password" placeholder="Tu contraseña" required></div>
            <button type="submit" class="btn-login">Ingresar al portal</button>
        </form>
        <p class="no-register">¿No tienes cuenta? Contacta a Industrias Salcom para ser dado de alta.</p>
    </div>
    <div class="footer-text">&copy; {{ date('Y') }} Industrias Salcom. Todos los derechos reservados.</div>
</div>
</body>
</html>
