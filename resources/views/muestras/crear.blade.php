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
    <a href="/" style="text-decoration:none"><span class="brand">Industrias <span>Salcom</span></span></a>
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="/" style="font-size:12px;color:rgba(255,255,255,0.6);text-decoration:none;">← Inicio</a>
        <span class="nav-badge"><i class="bi bi-box-seam"></i> Envío de Muestras</span>
    </div>
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
        <form method="POST" action="{{ route('muestras.guardar') }}" enctype="multipart/form-data">
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
                    <label class="form-label">Material / Composición</label>
                    <input type="text" name="material" class="form-control" placeholder="Ej: Polietileno, Acero inoxidable" value="{{ old('material') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Color</label>
                    <input type="text" name="color" class="form-control" placeholder="Ej: Transparente, Rojo" value="{{ old('color') }}">
                </div>
            </div>

            <div class="row-2">
                <div class="mb-3">
                    <label class="form-label">Dimensiones</label>
                    <input type="text" name="dimensiones" class="form-control" placeholder="Ej: 10x5x3 cm" value="{{ old('dimensiones') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Peso por unidad</label>
                    <input type="text" name="peso_unidad" class="form-control" placeholder="Ej: 250g, 1.5kg" value="{{ old('peso_unidad') }}">
                </div>
            </div>

            <div class="row-2">
                <div class="mb-3">
                    <label class="form-label">Certificaciones requeridas</label>
                    <input type="text" name="certificaciones" class="form-control" placeholder="Ej: ISO 9001, FDA, NOM" value="{{ old('certificaciones') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Temperatura de almacenamiento</label>
                    <input type="text" name="temperatura" class="form-control" placeholder="Ej: 15-25°C, Refrigerado" value="{{ old('temperatura') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Pruebas requeridas</label>
                <textarea name="pruebas_requeridas" class="form-control" rows="2" placeholder="Ej: Prueba de resistencia, análisis químico, prueba de estabilidad">{{ old('pruebas_requeridas') }}</textarea>
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

            <hr style="margin:1.5rem 0;border-color:var(--border);">

            <div class="mb-3">
                <label class="form-label">Documentos de envío</label>
                <input type="file" name="documentos_envio[]" class="form-control" multiple accept=".pdf,.jpg,.png">
                <small style="color:var(--gray-text);opacity:0.7">Adjunta los documentos que acompañan el envío (guía, certificado de análisis, hoja de seguridad, etc.)</small>
            </div>

            <div class="row-2">
                <div class="mb-3">
                    <label class="form-label">Remitente</label>
                    <input type="text" name="remitente" class="form-control" placeholder="Nombre de quien envía" value="{{ old('remitente') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Destinatario</label>
                    <input type="text" name="destinatario" class="form-control" placeholder="Nombre de quien recibe en Salcom" value="{{ old('destinatario') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Imagen de la muestra</label>
                <input type="file" name="imagen_muestra" class="form-control" accept="image/*">
                <small style="color:var(--gray-text);opacity:0.7">Foto del material o muestra que se envía</small>
            </div>

            <hr style="margin:1.5rem 0;border-color:var(--border);">

            <h6 style="font-family:'Playfair Display',serif;color:var(--purple-dark);margin-bottom:1rem;font-size:1rem;">Especificaciones de Empaque</h6>

            <p style="font-size:0.82rem;color:var(--gray-text);margin-bottom:1rem;opacity:0.8">Selecciona el tipo de producto para ver el diagrama de empaque recomendado.</p>

            <div class="mb-3">
                <label class="form-label">Tipo de producto</label>
                <select name="tipo_empaque" class="form-control" id="tipoEmpaque" onchange="cambiarDiagrama()">
                    <option value="">— Seleccionar —</option>
                    <option value="aerosoles">Aerosoles</option>
                    <option value="pastillas">Pastillas / Botes redondos</option>
                    <option value="botellas">Botellas</option>
                    <option value="bolsas">Bolsas / Sacos</option>
                    <option value="cajas_individuales">Cajas individuales</option>
                </select>
            </div>

            <div id="diagramaEmpaque" style="display:none;background:#f9fafb;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:1.2rem;text-align:center">
                <div id="svgDiagrama"></div>
                <p id="diagramaDesc" style="font-size:12px;color:var(--gray-text);margin-top:10px;font-weight:600"></p>
            </div>

            <label class="form-label">Caja Máster</label>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:1rem">
                <div>
                    <input type="text" name="caja_largo" class="form-control" placeholder="Largo (cm)" value="{{ old('caja_largo') }}">
                </div>
                <div>
                    <input type="text" name="caja_ancho" class="form-control" placeholder="Ancho (cm)" value="{{ old('caja_ancho') }}">
                </div>
                <div>
                    <input type="text" name="caja_alto" class="form-control" placeholder="Alto (cm)" value="{{ old('caja_alto') }}">
                </div>
            </div>

            <label class="form-label">Separador</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:1rem">
                <div>
                    <input type="text" name="separador_largo" class="form-control" placeholder="Largo (cm)" value="{{ old('separador_largo') }}">
                </div>
                <div>
                    <input type="text" name="separador_alto" class="form-control" placeholder="Alto (cm)" value="{{ old('separador_alto') }}">
                </div>
            </div>

            <div class="row-2">
                <div class="mb-3">
                    <label class="form-label">Piezas por caja</label>
                    <input type="number" name="piezas_por_caja" class="form-control" placeholder="Ej: 4, 6, 12" value="{{ old('piezas_por_caja') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Peso total caja (kg)</label>
                    <input type="text" name="peso_caja" class="form-control" placeholder="Ej: 5.2" value="{{ old('peso_caja') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Foto del empaque</label>
                <input type="file" name="imagen_empaque" class="form-control" accept="image/*">
                <small style="color:var(--gray-text);opacity:0.7">Foto de la caja máster con separadores y producto dentro</small>
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
<script>
function cambiarDiagrama() {
    const tipo = document.getElementById('tipoEmpaque').value;
    const container = document.getElementById('diagramaEmpaque');
    const svg = document.getElementById('svgDiagrama');
    const desc = document.getElementById('diagramaDesc');

    if (!tipo) { container.style.display = 'none'; return; }
    container.style.display = 'block';

    const diagramas = {
        aerosoles: {
            svg: `<svg width="240" height="200" viewBox="0 0 240 200">
                <!-- Caja -->
                <rect x="20" y="20" width="200" height="160" rx="4" fill="none" stroke="#8B6914" stroke-width="2" stroke-dasharray="4"/>
                <!-- Separadores verticales -->
                <line x1="80" y1="20" x2="80" y2="180" stroke="#8B6914" stroke-width="1.5" stroke-dasharray="3"/>
                <line x1="140" y1="20" x2="140" y2="180" stroke="#8B6914" stroke-width="1.5" stroke-dasharray="3"/>
                <!-- Separador horizontal -->
                <line x1="20" y1="100" x2="220" y2="100" stroke="#8B6914" stroke-width="1.5" stroke-dasharray="3"/>
                <!-- Aerosoles (cilindros vista superior) -->
                <circle cx="50" cy="60" r="18" fill="#e0e0e0" stroke="#999" stroke-width="1.5"/>
                <circle cx="50" cy="60" r="6" fill="#ccc" stroke="#999" stroke-width="1"/>
                <circle cx="110" cy="60" r="18" fill="#e0e0e0" stroke="#999" stroke-width="1.5"/>
                <circle cx="110" cy="60" r="6" fill="#ccc" stroke="#999" stroke-width="1"/>
                <circle cx="170" cy="60" r="18" fill="#e0e0e0" stroke="#999" stroke-width="1.5"/>
                <circle cx="170" cy="60" r="6" fill="#ccc" stroke="#999" stroke-width="1"/>
                <circle cx="50" cy="140" r="18" fill="#e0e0e0" stroke="#999" stroke-width="1.5"/>
                <circle cx="50" cy="140" r="6" fill="#ccc" stroke="#999" stroke-width="1"/>
                <circle cx="110" cy="140" r="18" fill="#e0e0e0" stroke="#999" stroke-width="1.5"/>
                <circle cx="110" cy="140" r="6" fill="#ccc" stroke="#999" stroke-width="1"/>
                <circle cx="170" cy="140" r="18" fill="#e0e0e0" stroke="#999" stroke-width="1.5"/>
                <circle cx="170" cy="140" r="6" fill="#ccc" stroke="#999" stroke-width="1"/>
            </svg>`,
            desc: 'Vista superior: 6 aerosoles con separadores verticales y horizontal (3x2)'
        },
        pastillas: {
            svg: `<svg width="240" height="200" viewBox="0 0 240 200">
                <!-- Caja -->
                <rect x="20" y="20" width="200" height="160" rx="4" fill="none" stroke="#8B6914" stroke-width="2" stroke-dasharray="4"/>
                <!-- Separadores -->
                <line x1="120" y1="20" x2="120" y2="180" stroke="#8B6914" stroke-width="1.5" stroke-dasharray="3"/>
                <line x1="20" y1="100" x2="220" y2="100" stroke="#8B6914" stroke-width="1.5" stroke-dasharray="3"/>
                <!-- Botes redondos (pastillas) -->
                <circle cx="70" cy="60" r="28" fill="#f0f0f0" stroke="#999" stroke-width="2"/>
                <circle cx="70" cy="60" r="24" fill="#fafafa" stroke="#ddd" stroke-width="1"/>
                <circle cx="170" cy="60" r="28" fill="#f0f0f0" stroke="#999" stroke-width="2"/>
                <circle cx="170" cy="60" r="24" fill="#fafafa" stroke="#ddd" stroke-width="1"/>
                <circle cx="70" cy="140" r="28" fill="#f0f0f0" stroke="#999" stroke-width="2"/>
                <circle cx="70" cy="140" r="24" fill="#fafafa" stroke="#ddd" stroke-width="1"/>
                <circle cx="170" cy="140" r="28" fill="#f0f0f0" stroke="#999" stroke-width="2"/>
                <circle cx="170" cy="140" r="24" fill="#fafafa" stroke="#ddd" stroke-width="1"/>
            </svg>`,
            desc: 'Vista superior: 4 botes de pastillas con separador central en cruz (2x2)'
        },
        botellas: {
            svg: `<svg width="240" height="200" viewBox="0 0 240 200">
                <!-- Caja -->
                <rect x="20" y="20" width="200" height="160" rx="4" fill="none" stroke="#8B6914" stroke-width="2" stroke-dasharray="4"/>
                <!-- Separadores -->
                <line x1="80" y1="20" x2="80" y2="180" stroke="#8B6914" stroke-width="1.5" stroke-dasharray="3"/>
                <line x1="140" y1="20" x2="140" y2="180" stroke="#8B6914" stroke-width="1.5" stroke-dasharray="3"/>
                <!-- Botellas (rectangulares vista frontal) -->
                <rect x="35" y="40" width="30" height="120" rx="4" fill="#e8f4fd" stroke="#2196F3" stroke-width="1.5"/>
                <rect x="40" y="35" width="20" height="12" rx="2" fill="#2196F3"/>
                <rect x="95" y="40" width="30" height="120" rx="4" fill="#e8f4fd" stroke="#2196F3" stroke-width="1.5"/>
                <rect x="100" y="35" width="20" height="12" rx="2" fill="#2196F3"/>
                <rect x="155" y="40" width="30" height="120" rx="4" fill="#e8f4fd" stroke="#2196F3" stroke-width="1.5"/>
                <rect x="160" y="35" width="20" height="12" rx="2" fill="#2196F3"/>
            </svg>`,
            desc: 'Vista frontal: 3 botellas con separadores individuales'
        },
        bolsas: {
            svg: `<svg width="240" height="200" viewBox="0 0 240 200">
                <!-- Caja -->
                <rect x="20" y="20" width="200" height="160" rx="4" fill="none" stroke="#8B6914" stroke-width="2" stroke-dasharray="4"/>
                <!-- Bolsas apiladas -->
                <rect x="40" y="130" width="160" height="30" rx="6" fill="#f5f0e0" stroke="#c9a84c" stroke-width="1.5"/>
                <rect x="40" y="95" width="160" height="30" rx="6" fill="#f5f0e0" stroke="#c9a84c" stroke-width="1.5"/>
                <rect x="40" y="60" width="160" height="30" rx="6" fill="#f5f0e0" stroke="#c9a84c" stroke-width="1.5"/>
                <rect x="40" y="25" width="160" height="30" rx="6" fill="#f5f0e0" stroke="#c9a84c" stroke-width="1.5"/>
                <!-- Líneas de costura -->
                <line x1="50" y1="40" x2="190" y2="40" stroke="#c9a84c" stroke-width="0.5" stroke-dasharray="2"/>
                <line x1="50" y1="75" x2="190" y2="75" stroke="#c9a84c" stroke-width="0.5" stroke-dasharray="2"/>
                <line x1="50" y1="110" x2="190" y2="110" stroke="#c9a84c" stroke-width="0.5" stroke-dasharray="2"/>
                <line x1="50" y1="145" x2="190" y2="145" stroke="#c9a84c" stroke-width="0.5" stroke-dasharray="2"/>
            </svg>`,
            desc: 'Vista frontal: 4 bolsas/sacos apilados horizontalmente'
        },
        cajas_individuales: {
            svg: `<svg width="240" height="200" viewBox="0 0 240 200">
                <!-- Caja máster -->
                <rect x="20" y="20" width="200" height="160" rx="4" fill="none" stroke="#8B6914" stroke-width="2" stroke-dasharray="4"/>
                <!-- Cajas individuales -->
                <rect x="30" y="30" width="85" height="65" rx="3" fill="#fff3e0" stroke="#e65100" stroke-width="1.5"/>
                <rect x="125" y="30" width="85" height="65" rx="3" fill="#fff3e0" stroke="#e65100" stroke-width="1.5"/>
                <rect x="30" y="105" width="85" height="65" rx="3" fill="#fff3e0" stroke="#e65100" stroke-width="1.5"/>
                <rect x="125" y="105" width="85" height="65" rx="3" fill="#fff3e0" stroke="#e65100" stroke-width="1.5"/>
                <!-- Líneas de cierre -->
                <line x1="30" y1="62" x2="115" y2="62" stroke="#e65100" stroke-width="0.8"/>
                <line x1="125" y1="62" x2="210" y2="62" stroke="#e65100" stroke-width="0.8"/>
                <line x1="30" y1="137" x2="115" y2="137" stroke="#e65100" stroke-width="0.8"/>
                <line x1="125" y1="137" x2="210" y2="137" stroke="#e65100" stroke-width="0.8"/>
            </svg>`,
            desc: 'Vista superior: 4 cajas individuales dentro de la caja máster (2x2)'
        }
    };

    const d = diagramas[tipo];
    if (d) {
        svg.innerHTML = d.svg;
        desc.textContent = d.desc;
    }
}
</script>
</body>
</html>
