<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta de Cliente — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',-apple-system,sans-serif;background:#f9fafb;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 24px;-webkit-font-smoothing:antialiased}
        .container{width:100%;max-width:520px}
        .brand{text-align:center;margin-bottom:24px}.brand h1{font-size:22px;font-weight:700;color:#6B3FA0}.brand p{font-size:12px;color:#6b7280;margin-top:2px}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:32px 28px}
        .card-title{font-size:18px;font-weight:700;color:#1a1a2e;margin-bottom:4px}.card-sub{font-size:13px;color:#6b7280;margin-bottom:20px}
        .alert-success{background:#ecfdf5;border:1px solid #059669;border-radius:8px;padding:10px 14px;font-size:13px;color:#065f46;margin-bottom:16px}
        .alert-errors{background:#fef2f2;border:1px solid #dc2626;border-radius:8px;padding:10px 14px;font-size:13px;color:#991b1b;margin-bottom:16px}
        .alert-errors ul{padding-left:16px}
        .field{margin-bottom:16px}.field label{display:block;font-size:11px;font-weight:600;color:#6b7280;margin-bottom:5px;letter-spacing:.5px;text-transform:uppercase}
        .field input,.field select{width:100%;border:1.5px solid #e5e7eb;border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;color:#1a1a2e;outline:none;background:#fff}
        .field input:focus,.field select:focus{border-color:#6B3FA0;box-shadow:0 0 0 3px rgba(107,63,160,.1)}
        .field select{cursor:pointer}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .check-row{display:flex;align-items:center;gap:8px;margin-bottom:16px;font-size:13px;color:#1a1a2e}.check-row input{width:16px;height:16px;accent-color:#6B3FA0}
        .btn-submit{width:100%;padding:12px;background:#6B3FA0;color:#fff;border:none;border-radius:10px;font-size:14px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s}.btn-submit:hover{background:#4A2070}
        .error-msg{font-size:11px;color:#dc2626;margin-top:3px}
        @media(max-width:500px){.form-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="container">
    <div style="text-align:center;margin-bottom:16px;">
        <a href="/admin/clientes" style="font-size:13px;color:#6B3FA0;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:6px;">← Volver al panel de administración</a>
    </div>
    <div class="brand"><h1>Industrias Salcom</h1><p>Alta de Cliente — Uso interno</p></div>
    <div class="card">
        <div class="card-title">Dar de alta cliente</div>
        <div class="card-sub">Completa los datos del nuevo cliente</div>
        @if(session('mensaje'))<div class="alert-success">{{ session('mensaje') }}</div>@endif
        @if($errors->any())<div class="alert-errors"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.cliente.guardar') }}">
            @csrf
            <div class="field"><label>Nombre / Razón social *</label><input type="text" name="nombre" value="{{ old('nombre') }}" required></div>
            <div class="form-row">
                <div class="field"><label>Usuario *</label><input type="text" name="usuario" value="{{ old('usuario') }}" placeholder="CLI003" required></div>
                <div class="field"><label>Contraseña *</label><input type="password" name="password" placeholder="Mínimo 8 caracteres" required></div>
            </div>
            <div class="form-row">
                <div class="field"><label>Correo *</label><input type="email" name="correo" value="{{ old('correo') }}" required></div>
                <div class="field"><label>Teléfono</label><input type="tel" name="telefono" value="{{ old('telefono') }}"></div>
            </div>
            <div class="form-row">
                <div class="field"><label>RFC</label><input type="text" name="rfc" value="{{ old('rfc') }}" maxlength="13"></div>
                <div class="field"><label>Código cliente</label><input type="text" name="codigo_cliente" value="{{ old('codigo_cliente') }}" placeholder="CLI-2026-XXX"></div>
            </div>
            <div class="form-row">
                <div class="field"><label>Tipo de persona *</label><select name="tipo_persona" required><option value="">Selecciona</option><option value="Persona Física" {{ old('tipo_persona')=='Persona Física'?'selected':'' }}>Persona Física</option><option value="Persona Moral" {{ old('tipo_persona')=='Persona Moral'?'selected':'' }}>Persona Moral</option></select></div>
                <div class="field"><label>Tipo de cliente *</label><select name="tipo_cliente" required><option value="">Selecciona</option><option value="mayorista" {{ old('tipo_cliente')=='mayorista'?'selected':'' }}>Mayorista</option><option value="minorista" {{ old('tipo_cliente')=='minorista'?'selected':'' }}>Minorista</option><option value="distribuidor" {{ old('tipo_cliente')=='distribuidor'?'selected':'' }}>Distribuidor</option></select></div>
            </div>
            <div class="field"><label>Límite de crédito (MXN)</label><input type="number" name="limite_credito" value="{{ old('limite_credito') }}" step="0.01" min="0" placeholder="0.00"></div>
            <div class="check-row"><input type="checkbox" name="credito_autorizado" value="1" {{ old('credito_autorizado')?'checked':'' }}><label>Crédito autorizado (si no, pagos de contado)</label></div>
            <button type="submit" class="btn-submit">Dar de alta cliente</button>
        </form>
    </div>
</div>
</body>
</html>
