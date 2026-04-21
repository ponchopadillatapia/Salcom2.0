@extends('layouts.proveedor')

@section('title', 'Alta de Producto')

@section('hero')
<div class="hero-band">
    <h1>Alta de Producto</h1>
    <p>Completa los datos de tu producto y descarga la plantilla para enviarnos</p>
</div>
@endsection

@push('styles')
<style>
    .alta-wrap { max-width: 860px; }

    .info-banner {
        background: var(--purple-light);
        border: 1px solid var(--purple-mid);
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 28px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
    .info-banner-icon { font-size: 20px; flex-shrink: 0; }
    .info-banner-text { font-size: 13px; color: var(--purple-dark); line-height: 1.6; }
    .info-banner-text strong { font-weight: 700; }

    .form-card { background: var(--white); border-radius: 14px; border: 0.5px solid var(--border); padding: 28px; margin-bottom: 20px; }
    .form-section-title { font-family: 'Playfair Display', serif; font-size: 16px; color: var(--purple-dark); font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1.5px solid var(--border); display: flex; align-items: center; gap: 8px; }
    .form-section-title .num { width: 26px; height: 26px; border-radius: 50%; background: var(--purple); color: white; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; font-family: 'Nunito', sans-serif; flex-shrink: 0; }

    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-grid.tres { grid-template-columns: 1fr 1fr 1fr; }
    .form-grid.uno { grid-template-columns: 1fr; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full { grid-column: 1 / -1; }

    label { font-size: 12px; font-weight: 700; color: var(--gray-text); text-transform: uppercase; letter-spacing: 0.5px; }
    .req { color: var(--red); margin-left: 2px; }

    input[type="text"],
    input[type="number"],
    textarea,
    select {
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        font-family: 'Nunito', sans-serif;
        color: var(--gray-text);
        background: var(--white);
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        width: 100%;
    }
    input:focus, textarea:focus, select:focus {
        border-color: var(--purple-mid);
        box-shadow: 0 0 0 3px rgba(156,109,208,0.12);
    }
    textarea { resize: vertical; min-height: 80px; }
    .input-with-unit { display: flex; gap: 8px; }
    .input-with-unit input { flex: 1; }
    .input-with-unit select { width: 100px; flex-shrink: 0; }

    .form-hint { font-size: 11px; color: #AAA; margin-top: 2px; }

    .atributos-wrap { display: flex; flex-direction: column; gap: 10px; }
    .atributo-row { display: flex; gap: 10px; align-items: center; }
    .atributo-row input { flex: 1; }
    .btn-remove-attr { width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: none; cursor: pointer; color: var(--red); font-size: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background .15s; }
    .btn-remove-attr:hover { background: var(--red-bg); }
    .btn-add-attr { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1.5px dashed var(--purple-mid); border-radius: 8px; background: none; color: var(--purple); font-size: 13px; font-family: inherit; font-weight: 600; cursor: pointer; margin-top: 4px; transition: background .15s; }
    .btn-add-attr:hover { background: var(--purple-light); }

    .actions-bar { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-top: 8px; }
    .btn-primary { padding: 12px 28px; background: var(--purple); color: white; border: none; border-radius: 10px; font-size: 14px; font-family: inherit; font-weight: 700; cursor: pointer; transition: background .2s; }
    .btn-primary:hover { background: var(--purple-dark); }
    .btn-excel { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; background: #16a34a; color: white; border: none; border-radius: 10px; font-size: 14px; font-family: inherit; font-weight: 700; cursor: pointer; transition: background .2s; }
    .btn-excel:hover { background: #15803d; }
    .btn-secondary { padding: 12px 20px; background: none; border: 1.5px solid var(--border); border-radius: 10px; font-size: 14px; font-family: inherit; color: var(--gray-text); cursor: pointer; transition: all .15s; }
    .btn-secondary:hover { border-color: var(--purple-mid); color: var(--purple); background: var(--purple-light); }

    .success-banner { display: none; background: var(--green-bg); border: 1px solid var(--green); border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; color: var(--green); font-weight: 600; font-size: 14px; align-items: center; gap: 10px; }
    .success-banner.show { display: flex; }

    @media (max-width: 768px) {
        .form-grid, .form-grid.tres { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="alta-wrap">

    <div class="info-banner">
        <div class="info-banner-icon">ℹ️</div>
        <div class="info-banner-text">
            <strong>¿Cómo funciona?</strong> Responde las preguntas generales, llena los datos de tu producto y agrega sus atributos. Al terminar, descarga la plantilla Excel generada automáticamente y envíala a tu ejecutivo de cuenta en Industrias Salcom para completar el registro.
        </div>
    </div>

    <div class="success-banner" id="successBanner">
        ✅ ¡Plantilla generada correctamente! Revisa tu carpeta de descargas.
    </div>

    {{-- SECCIÓN 0: PREGUNTAS GENERALES --}}
    <div class="form-card">
        <div class="form-section-title">
            <div class="num">0</div>
            Preguntas generales
        </div>
        <div class="form-grid">
            <div class="form-group full">
                <label>¿Cuál es el uso principal de este producto? <span class="req">*</span></label>
                <textarea id="usoPrincipal" placeholder="Describe para qué se usa el producto, en qué industria o proceso se aplica"></textarea>
            </div>
            <div class="form-group">
                <label>¿Tiene ficha técnica disponible? <span class="req">*</span></label>
                <select id="fichaTecnica">
                    <option value="">— Selecciona —</option>
                    <option value="si">Sí, la tengo disponible</option>
                    <option value="en_proceso">Está en proceso</option>
                    <option value="no">No tengo ficha técnica</option>
                </select>
            </div>
            <div class="form-group">
                <label>¿Requiere condiciones especiales de almacenamiento?</label>
                <select id="condicionesAlmacen">
                    <option value="">— Selecciona —</option>
                    <option value="no">No, almacenamiento normal</option>
                    <option value="refrigerado">Sí, requiere refrigeración</option>
                    <option value="seco">Sí, ambiente seco</option>
                    <option value="otro">Otro (especificar en descripción)</option>
                </select>
            </div>
            <div class="form-group full">
                <label>¿Tiene certificaciones o normativas aplicables?</label>
                <input type="text" id="certificaciones" placeholder="Ej: ISO 9001, NOM-051, FDA, libre de BPA, etc.">
                <span class="form-hint">Separa con comas si son varias</span>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 1: IDENTIFICACIÓN --}}
    <div class="form-card">
        <div class="form-section-title">
            <div class="num">1</div>
            Identificación del producto
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Número de parte <span class="req">*</span></label>
                <input type="text" id="numeroParte" placeholder="Ej. PROD-001, MP-2024-A" maxlength="50">
                <span class="form-hint">Código único que identifica tu producto en tu sistema</span>
            </div>
            <div class="form-group">
                <label>Código de barras / EAN</label>
                <input type="text" id="codigoBarras" placeholder="Ej. 7501234567890" maxlength="13">
                <span class="form-hint">Si aplica</span>
            </div>
            <div class="form-group full">
                <label>Nombre del producto <span class="req">*</span></label>
                <input type="text" id="nombreProducto" placeholder="Ej. Tornillo hexagonal acero inoxidable 1/4">
            </div>
            <div class="form-group full">
                <label>Descripción del producto <span class="req">*</span></label>
                <textarea id="descripcion" placeholder="Describe detalladamente el producto: material, uso, características principales..."></textarea>
            </div>
            <div class="form-group">
                <label>Categoría</label>
                <select id="categoria">
                    <option value="">— Selecciona —</option>
                    <option>Materia prima</option>
                    <option>Producto terminado</option>
                    <option>Consumible</option>
                    <option>Herramienta</option>
                    <option>Empaque</option>
                    <option>Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label>Marca / Fabricante</label>
                <input type="text" id="marca" placeholder="Ej. 3M, Genérico, Marca propia">
            </div>
        </div>
    </div>

    {{-- SECCIÓN 2: UNIDAD DE VENTA --}}
    <div class="form-card">
        <div class="form-section-title">
            <div class="num">2</div>
            Unidad de venta
        </div>
        <div class="form-grid tres">
            <div class="form-group">
                <label>Unidad de venta <span class="req">*</span></label>
                <select id="unidadVenta">
                    <option value="">— Selecciona —</option>
                    <option>Pieza</option>
                    <option>Caja</option>
                    <option>Paquete</option>
                    <option>Kilo</option>
                    <option>Gramo</option>
                    <option>Litro</option>
                    <option>Mililitro</option>
                    <option>Metro</option>
                    <option>Rollo</option>
                    <option>Par</option>
                    <option>Docena</option>
                    <option>Otro</option>
                </select>
                <span class="form-hint">¿Cómo lo vendes?</span>
            </div>
            <div class="form-group">
                <label>Cantidad por unidad <span class="req">*</span></label>
                <input type="number" id="cantidadUnidad" placeholder="Ej. 12" min="1">
                <span class="form-hint">Piezas por caja, gramos por bolsa, etc.</span>
            </div>
            <div class="form-group">
                <label>Precio unitario (MXN)</label>
                <input type="number" id="precioUnitario" placeholder="0.00" min="0" step="0.01">
                <span class="form-hint">Precio por unidad de venta</span>
            </div>
        </div>
    </div>

    {{-- SECCIÓN 3: PESO Y DIMENSIONES --}}
    <div class="form-card">
        <div class="form-section-title">
            <div class="num">3</div>
            Peso y dimensiones
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Peso <span class="req">*</span></label>
                <div class="input-with-unit">
                    <input type="number" id="peso" placeholder="0.00" min="0" step="0.01">
                    <select id="unidadPeso">
                        <option>kg</option>
                        <option>g</option>
                        <option>lb</option>
                        <option>oz</option>
                        <option>ton</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Unidad de dimensiones</label>
                <select id="unidadDim">
                    <option>cm</option>
                    <option>mm</option>
                    <option>m</option>
                    <option>in</option>
                </select>
            </div>
            <div class="form-group">
                <label>Largo</label>
                <input type="number" id="largo" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="form-group">
                <label>Ancho</label>
                <input type="number" id="ancho" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="form-group">
                <label>Alto</label>
                <input type="number" id="alto" placeholder="0.00" min="0" step="0.01">
            </div>
            <div class="form-group">
                <label>Volumen (calculado)</label>
                <input type="text" id="volumen" placeholder="—" readonly style="background:var(--gray-soft);color:#AAA;">
            </div>
        </div>
    </div>

    {{-- SECCIÓN 4: ATRIBUTOS / CUALIDADES --}}
    <div class="form-card">
        <div class="form-section-title">
            <div class="num">4</div>
            Atributos y cualidades
        </div>
        <p style="font-size:13px;color:#999;margin-bottom:16px;">Agrega las características específicas de tu producto (material, color, resistencia, certificaciones, etc.)</p>
        <div class="atributos-wrap" id="atributosWrap">
            <div class="atributo-row">
                <input type="text" placeholder="Atributo (ej. Color)" style="width:180px;flex:none;">
                <input type="text" placeholder="Valor (ej. Rojo)">
                <button class="btn-remove-attr" onclick="eliminarAtributo(this)" title="Eliminar">×</button>
            </div>
        </div>
        <button class="btn-add-attr" onclick="agregarAtributo()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Agregar atributo
        </button>
    </div>

    {{-- ACCIONES --}}
    <div class="actions-bar">
        <button class="btn-excel" onclick="generarPlantilla()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Generar y descargar plantilla
        </button>
        <button class="btn-secondary" onclick="limpiarFormulario()">Limpiar formulario</button>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Calcular volumen automático
['largo','ancho','alto'].forEach(id => {
    document.getElementById(id).addEventListener('input', calcularVolumen);
});

function calcularVolumen() {
    const l = parseFloat(document.getElementById('largo').value) || 0;
    const a = parseFloat(document.getElementById('ancho').value) || 0;
    const h = parseFloat(document.getElementById('alto').value) || 0;
    const u = document.getElementById('unidadDim').value;
    document.getElementById('volumen').value = (l && a && h) ? `${(l * a * h).toFixed(2)} ${u}³` : '—';
}
document.getElementById('unidadDim').addEventListener('change', calcularVolumen);

// Atributos
function agregarAtributo() {
    const wrap = document.getElementById('atributosWrap');
    const row = document.createElement('div');
    row.className = 'atributo-row';
    row.innerHTML = `
        <input type="text" placeholder="Atributo (ej. Material)" style="width:180px;flex:none;">
        <input type="text" placeholder="Valor (ej. Acero inoxidable)">
        <button class="btn-remove-attr" onclick="eliminarAtributo(this)" title="Eliminar">×</button>
    `;
    wrap.appendChild(row);
}

function eliminarAtributo(btn) {
    const rows = document.querySelectorAll('.atributo-row');
    if (rows.length > 1) btn.parentElement.remove();
}

// Validar campos requeridos
function validar() {
    const requeridos = ['numeroParte','nombreProducto','descripcion','unidadVenta','cantidadUnidad','peso'];
    let ok = true;
    requeridos.forEach(id => {
        const el = document.getElementById(id);
        if (!el.value.trim()) {
            el.style.borderColor = 'var(--red)';
            ok = false;
        } else {
            el.style.borderColor = '';
        }
    });
    return ok;
}

// Generar plantilla CSV
function generarPlantilla() {
    if (!validar()) {
        alert('Por favor completa los campos obligatorios marcados con *');
        return;
    }

    // Recolectar atributos
    const atributos = [];
    document.querySelectorAll('.atributo-row').forEach(row => {
        const inputs = row.querySelectorAll('input');
        const clave = inputs[0].value.trim();
        const valor = inputs[1].value.trim();
        if (clave && valor) atributos.push(`${clave}: ${valor}`);
    });

    const datos = {
        'Uso principal':         document.getElementById('usoPrincipal').value || '—',
        'Ficha técnica':         document.getElementById('fichaTecnica').value || '—',
        'Condiciones almacén':   document.getElementById('condicionesAlmacen').value || '—',
        'Certificaciones':       document.getElementById('certificaciones').value || '—',
        'Número de parte':       document.getElementById('numeroParte').value,
        'Código de barras/EAN':  document.getElementById('codigoBarras').value || '—',
        'Nombre del producto':   document.getElementById('nombreProducto').value,
        'Descripción':           document.getElementById('descripcion').value,
        'Categoría':             document.getElementById('categoria').value || '—',
        'Marca/Fabricante':      document.getElementById('marca').value || '—',
        'Unidad de venta':       document.getElementById('unidadVenta').value,
        'Cantidad por unidad':   document.getElementById('cantidadUnidad').value,
        'Precio unitario (MXN)': document.getElementById('precioUnitario').value || '—',
        'Peso':                  `${document.getElementById('peso').value} ${document.getElementById('unidadPeso').value}`,
        'Largo':                 document.getElementById('largo').value ? `${document.getElementById('largo').value} ${document.getElementById('unidadDim').value}` : '—',
        'Ancho':                 document.getElementById('ancho').value ? `${document.getElementById('ancho').value} ${document.getElementById('unidadDim').value}` : '—',
        'Alto':                  document.getElementById('alto').value ? `${document.getElementById('alto').value} ${document.getElementById('unidadDim').value}` : '—',
        'Volumen':               document.getElementById('volumen').value,
        'Atributos':             atributos.join(' | ') || '—',
        'Proveedor':             '{{ session("proveedor_nombre", "Proveedor") }}',
        'Código proveedor':      '{{ session("proveedor_codigo", "—") }}',
        'Fecha de alta':         new Date().toLocaleDateString('es-MX'),
    };

    // Generar CSV
    let csv = '\uFEFF'; // BOM UTF-8
    csv += 'CAMPO,VALOR\n';
    Object.entries(datos).forEach(([campo, valor]) => {
        csv += `"${campo}","${String(valor).replace(/"/g, '""')}"\n`;
    });

    // Descargar
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `alta-producto-${document.getElementById('numeroParte').value}-${new Date().toISOString().slice(0,10)}.csv`;
    a.click();

    // Mostrar banner de éxito
    const banner = document.getElementById('successBanner');
    banner.classList.add('show');
    setTimeout(() => banner.classList.remove('show'), 5000);
}

function limpiarFormulario() {
    if (!confirm('¿Seguro que quieres limpiar todos los datos?')) return;
    document.querySelectorAll('input[type="text"], input[type="number"], textarea, select').forEach(el => {
        el.value = '';
        el.style.borderColor = '';
    });
    // Dejar solo un atributo
    const wrap = document.getElementById('atributosWrap');
    wrap.innerHTML = `
        <div class="atributo-row">
            <input type="text" placeholder="Atributo (ej. Color)" style="width:180px;flex:none;">
            <input type="text" placeholder="Valor (ej. Rojo)">
            <button class="btn-remove-attr" onclick="eliminarAtributo(this)" title="Eliminar">×</button>
        </div>
    `;
    document.getElementById('volumen').value = '—';
}
</script>
@endpush