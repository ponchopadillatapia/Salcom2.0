<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envío de Muestras — Industrias Salcom</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --purple: #6B3FA0; --purple-dark: #4A2070; --purple-light: #EDE7F6;
            --purple-mid: #9C6DD0; --gray-text: #4A4A6A; --gray-soft: #F7F6FB;
            --border: #D8CFE8; --white: #FFFFFF; --green: #059669;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Nunito', sans-serif; background: var(--gray-soft); color: var(--gray-text); }
        .navbar-salcom {
            background: linear-gradient(135deg, var(--purple-dark), var(--purple));
            padding: 0 2rem; height: 64px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 12px rgba(74,32,112,0.18);
        }
        .navbar-salcom .brand { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: var(--white); }
        .navbar-salcom .brand span { color: #C9A8FF; }
        .navbar-salcom .nav-badge {
            background: rgba(255,255,255,0.15); color: var(--white); font-size: 0.75rem;
            font-weight: 600; padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.25);
        }
        .page-wrapper { max-width: 680px; margin: 2.5rem auto; padding: 0 1rem; }
        .section-header { text-align: center; margin-bottom: 2rem; }
        .section-header h1 { font-family: 'Playfair Display', serif; font-size: 1.75rem; color: var(--purple-dark); margin-bottom: 0.4rem; }
        .section-header p { font-size: 0.92rem; color: var(--gray-text); opacity: 0.8; }
        .card-salcom {
            background: var(--white); border: 1px solid var(--border); border-radius: 16px;
            padding: 2rem; box-shadow: 0 4px 24px rgba(107,63,160,0.08);
        }
        label.form-label { font-weight: 700; font-size: 0.85rem; color: var(--purple-dark); }
        .form-control:focus { border-color: var(--purple-mid); box-shadow: 0 0 0 3px rgba(107,63,160,0.12); }
        .btn-salcom {
            width: 100%; padding: 0.85rem;
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            color: var(--white); font-weight: 700; border: none; border-radius: 10px;
            font-size: 1rem; cursor: pointer; margin-top: 1rem;
        }
        .btn-salcom:hover { opacity: 0.92; color: var(--white); }
        .alert-exito {
            background: #D1FAE5; border: 1px solid var(--green); color: #065F46;
            border-radius: 10px; padding: 0.8rem 1rem; margin-bottom: 1rem; font-weight: 600;
        }
        .page-footer { text-align: center; margin-top: 2rem; font-size: 0.78rem; color: var(--gray-text); opacity: 0.5; }
        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    </style>
</head>
<body>

<nav class="navbar-salcom">
    <span class="brand">Industrias <span>Salcom</span></span>
    <span class="nav-badge"><i class="bi bi-box-seam"></i> Envío de Muestras</span>
</nav>

<div class="page-wrapper">
    <div class="section-header">
        <h1>Envío de Muestras</h1>
        <p>Registra un nuevo lote de muestras para iniciar el proceso de validación.</p>
    </div>

    @if(session('exito'))
        <div class="alert-exito"><i class="bi bi-check-circle"></i> {{ session('exito') }}</div>
    @endif

    <div class="card-salcom">
        <form method="POST" action="{{ route('muestras.guardar') }}">
            @csrf

            <div class="row-2">
                <div class="mb-3">
                    <label class="form-label">Número de Lote</label>
                    <input type="text" name="lote" class="form-control" placeholder="Ej: LOTE-2026-001" required value="{{ old('lote') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Producto</label>
                    <input type="text" name="producto" class="form-control" placeholder="Nombre del producto" required value="{{ old('producto') }}">
                </div>
            </div>

            <div class="row-2">
                <div class="mb-3">
                    <label class="form-label">Proveedor</label>
                    <input type="text" name="proveedor" class="form-control" placeholder="Nombre del proveedor" required value="{{ old('proveedor') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contacto (opcional)</label>
                    <input type="text" name="proveedor_contacto" class="form-control" placeholder="Email o teléfono" value="{{ old('proveedor_contacto') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles del material o muestra">{{ old('descripcion') }}</textarea>
            </div>

            <div class="row-2">
                <div class="mb-3">
                    <label class="form-label">Cantidad</label>
                    <input type="number" name="cantidad" class="form-control" min="1" value="{{ old('cantidad', 1) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unidad</label>
                    <select name="unidad" class="form-control">
                        <option value="piezas">Piezas</option>
                        <option value="cajas">Cajas</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Días de validación</label>
                <input type="text" class="form-control" value="15 a 20 días" disabled style="max-width:160px; background:#f0f0f0;">
                <input type="hidden" name="dias_validacion" value="15">
            </div>

            @if($errors->any())
                <div class="alert alert-danger mt-2" style="border-radius:10px; font-size:0.85rem;">
                    @foreach($errors->all() as $error) <div>{{ $error }}</div> @endforeach
                </div>
            @endif

            <button type="submit" class="btn-salcom"><i class="bi bi-send"></i> Registrar Muestra</button>
        </form>
    </div>

    <p class="page-footer">Industrias Salcom · Sistema de control de muestras</p>
</div>
</body>
</html>
