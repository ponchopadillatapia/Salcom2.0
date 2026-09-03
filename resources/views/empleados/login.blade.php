<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Empleados — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2e1065 0%, #6d28d9 60%, #a78bfa 100%);
            padding: 20px;
        }
        .card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 40px 32px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .logo { text-align: center; margin-bottom: 8px; }
        .logo .brand { font-size: 13px; font-weight: 700; letter-spacing: 3px; color: #ddd6fe; text-transform: uppercase; }
        h1 { color: #fff; font-size: 20px; font-weight: 700; text-align: center; margin: 20px 0 4px; }
        .sub { color: rgba(255,255,255,0.7); font-size: 13px; text-align: center; margin-bottom: 28px; }
        label { display: block; color: rgba(255,255,255,0.85); font-size: 12px; font-weight: 600; margin-bottom: 6px; }
        input {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid rgba(255,255,255,0.25);
            border-radius: 12px;
            background: rgba(255,255,255,0.1);
            color: #fff;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            margin-bottom: 18px;
        }
        input::placeholder { color: rgba(255,255,255,0.5); }
        input:focus { border-color: #fff; background: rgba(255,255,255,0.18); }
        .btn {
            width: 100%;
            padding: 13px;
            background: #fff;
            color: #6d28d9;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: transform .15s;
        }
        .btn:hover { transform: translateY(-1px); }
        .error { background: rgba(220,38,38,0.2); border: 1px solid rgba(248,113,113,0.5); border-radius: 10px; padding: 10px 14px; color: #fecaca; font-size: 13px; margin-bottom: 18px; }
        .back { display: block; text-align: center; margin-top: 20px; color: rgba(255,255,255,0.6); font-size: 12px; text-decoration: none; }
        .back:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo"><div class="brand">Industrias Salcom</div></div>
        <h1>Portal de Empleados</h1>
        <p class="sub">Ingresa con tu número de empleado</p>

        @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
        @endif
        @if(session('error'))
        <div class="error">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('empleados.login.procesar') }}">
            @csrf
            <label for="numero_empleado">Número de empleado</label>
            <input type="text" id="numero_empleado" name="numero_empleado" placeholder="Ej: EMP-001" required autofocus value="{{ old('numero_empleado') }}">
            <button type="submit" class="btn">Entrar</button>
        </form>

        <a href="/" class="back">← Volver al inicio</a>
    </div>
</body>
</html>
