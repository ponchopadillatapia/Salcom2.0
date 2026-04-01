<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Validación de Documentos — Salcom 2.0</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#EDE7F6;
            --purple-mid:  #9C6DD0;
            --gray-text:   #4A4A6A;
            --gray-soft:   #F7F6FB;
            --border:      #D8CFE8;
            --white:       #FFFFFF;
            --green:       #059669;
            --green-bg:    #D1FAE5;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', sans-serif;
            background: var(--gray-soft);
            color: var(--gray-text);
            min-height: 100vh;
        }

        .navbar-salcom {
            background: linear-gradient(135deg, var(--purple-dark) 0%, var(--purple) 100%);
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 12px rgba(74,32,112,0.18);
        }
        .navbar-salcom .brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--white);
            letter-spacing: 0.5px;
        }
        .navbar-salcom .brand span { color: #C9A8FF; }
        .navbar-salcom .nav-badge {
            background: rgba(255,255,255,0.15);
            color: var(--white);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.25);
        }

        .page-wrapper {
            max-width: 780px;
            margin: 2.5rem auto;
            padding: 0 1rem;
        }

        .section-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .section-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            color: var(--purple-dark);
            margin-bottom: 0.4rem;
        }
        .section-header p {
            font-size: 0.92rem;
            color: var(--gray-text);
            opacity: 0.8;
        }

        /* ── Separador de grupo ── */
        .group-title {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            color: var(--purple-dark);
            font-weight: 700;
            margin: 1.5rem 0 0.75rem;
            padding-bottom: 0.4rem;
            border-bottom: 2px solid var(--purple-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-salcom {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 24px rgba(107,63,160,0.08);
        }

        .doc-row {
            background: var(--gray-soft);
            border: 1.5px dashed var(--border);
            border-radius: 12px;
            padding: 1.1rem 1.25rem;
            margin-bottom: 1rem;
            transition: border-color 0.2s, background 0.2s;
        }
        .doc-row:hover        { border-color: var(--purple-mid); background: var(--purple-light); }
        .doc-row.has-file     { border-style: solid; border-color: var(--purple); background: var(--purple-light); }
        .doc-row.error-file   { border-style: solid; border-color: #DC2626; background: #FEE2E2; }

        .doc-row label.doc-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--purple-dark);
            margin-bottom: 0.4rem;
        }
        .doc-row label.doc-label i { font-size: 1rem; color: var(--purple-mid); }
        .doc-hint {
            font-size: 0.75rem;
            color: var(--gray-text);
            opacity: 0.7;
            margin-bottom: 0.6rem;
        }
        .doc-row input[type="file"] { display: none; }

        .file-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-family: 'Nunito', sans-serif;
            font-weight: 600;
            color: var(--purple);
            cursor: pointer;
            transition: all 0.2s;
        }
        .file-btn:hover { background: var(--purple-light); border-color: var(--purple); }

        .file-name {
            margin-left: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--green);
        }
        .file-name.empty   { color: var(--gray-text); opacity: 0.55; font-weight: 400; }
        .file-name.pdf-err { color: #DC2626; }

        .btn-salcom {
            width: 100%;
            padding: 0.85rem;
            background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
            color: var(--white);
            font-family: 'Nunito', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 1.25rem;
            transition: opacity 0.2s, transform 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-salcom:hover:not(:disabled) { opacity: 0.92; transform: translateY(-1px); }
        .btn-salcom:disabled { opacity: 0.6; cursor: not-allowed; }

        .spinner {
            width: 18px; height: 18px;
            border: 3px solid rgba(255,255,255,0.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        #resultado { margin-top: 1.5rem; }

        .resultado-card {
            border-radius: 12px;
            padding: 1.25rem 1.4rem;
            border: 1.5px solid;
            animation: fadeIn 0.35s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

        .resultado-card.verde     { background: var(--green-bg); border-color: var(--green);  }
        .resultado-card.amarillo  { background: #FEF9C3;         border-color: #CA8A04;       }
        .resultado-card.rojo      { background: #FEE2E2;         border-color: #DC2626;       }
        .resultado-card.procesando{ background: var(--purple-light); border-color: var(--purple-mid); }

        .resultado-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.8rem;
        }
        .semaforo           { font-size: 1.5rem; }
        .resultado-empresa  { font-size: 1rem; font-weight: 700; color: var(--purple-dark); }
        .resultado-rfc      { font-size: 0.8rem; color: var(--gray-text); font-weight: 500; }

        .resultado-divider  { border: none; border-top: 1px solid var(--border); margin: 0.75rem 0; }

        .resultado-group-title {
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--purple-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0.75rem 0 0.4rem;
        }

        .doc-check-row {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .doc-check-row .check-icon   { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
        .doc-check-row .check-label  { font-weight: 700; color: var(--gray-text); }
        .doc-check-row .check-errors { font-size: 0.78rem; color: #B45309; margin-top: 1px; }

        .btn-portal {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, var(--green) 0%, #047857 100%);
            color: var(--white);
            font-family: 'Nunito', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 0.25rem;
            transition: opacity 0.2s, transform 0.15s;
            animation: fadeIn 0.4s ease;
        }
        .btn-portal:hover { opacity: 0.9; transform: translateY(-1px); color: var(--white); }

        .page-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.78rem;
            color: var(--gray-text);
            opacity: 0.5;
        }
    </style>
</head>

<body>

<nav class="navbar-salcom">
    <span class="brand">Salcom <span>2.0</span></span>
    <span class="nav-badge"><i class="bi bi-shield-check"></i> Validación Fiscal</span>
</nav>

<div class="page-wrapper">

    <div class="section-header">
        <h1>Validación de Documentos</h1>
        <p>Sube los seis documentos requeridos para continuar con tu registro como proveedor.</p>
    </div>

    <div class="card-salcom">

        {{-- ── GRUPO 1: Documentos fiscales ── --}}
        <p class="group-title"><i class="bi bi-file-earmark-ruled"></i> Documentos Fiscales</p>

        <div class="doc-row" id="row_cif">
            <label class="doc-label" for="cif"><i class="bi bi-file-earmark-text"></i> Constancia de Situación Fiscal (CIF)</label>
            <p class="doc-hint">Debe corresponder al mes en curso · PDF</p>
            <input type="file" id="cif" accept=".pdf" onchange="verArchivo('cif')">
            <label for="cif" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="cif_nombre" class="file-name empty">Sin archivo</span>
        </div>

        <div class="doc-row" id="row_opinion">
            <label class="doc-label" for="opinion"><i class="bi bi-patch-check"></i> Opinión de Cumplimiento SAT (Positiva)</label>
            <p class="doc-hint">Debe ser Positiva y del mes en curso · PDF</p>
            <input type="file" id="opinion" accept=".pdf" onchange="verArchivo('opinion')">
            <label for="opinion" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="opinion_nombre" class="file-name empty">Sin archivo</span>
        </div>

        <div class="doc-row" id="row_acta">
            <label class="doc-label" for="acta"><i class="bi bi-building"></i> Acta Constitutiva</label>
            <p class="doc-hint">Documento de constitución de la empresa · PDF</p>
            <input type="file" id="acta" accept=".pdf" onchange="verArchivo('acta')">
            <label for="acta" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="acta_nombre" class="file-name empty">Sin archivo</span>
        </div>

        {{-- ── GRUPO 2: Identificaciones ── --}}
        <p class="group-title"><i class="bi bi-person-vcard"></i> Identificaciones Oficiales</p>

        <div class="doc-row" id="row_rep_legal">
            <label class="doc-label" for="rep_legal"><i class="bi bi-person-badge"></i> ID Oficial del Representante Legal</label>
            <p class="doc-hint">INE/IFE vigente del representante legal · PDF</p>
            <input type="file" id="rep_legal" accept=".pdf" onchange="verArchivo('rep_legal')">
            <label for="rep_legal" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="rep_legal_nombre" class="file-name empty">Sin archivo</span>
        </div>

        <div class="doc-row" id="row_contribuyente">
            <label class="doc-label" for="contribuyente"><i class="bi bi-person-check"></i> ID Oficial del Contribuyente</label>
            <p class="doc-hint">INE/IFE vigente del contribuyente · PDF</p>
            <input type="file" id="contribuyente" accept=".pdf" onchange="verArchivo('contribuyente')">
            <label for="contribuyente" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="contribuyente_nombre" class="file-name empty">Sin archivo</span>
        </div>

        {{-- ── GRUPO 3: Datos bancarios ── --}}
        <p class="group-title"><i class="bi bi-bank"></i> Datos Bancarios</p>

        <div class="doc-row" id="row_caratula_banco">
            <label class="doc-label" for="caratula_banco"><i class="bi bi-credit-card"></i> Carátula de Estado de Cuenta Bancario</label>
            <p class="doc-hint">Debe mostrar CLABE interbancaria y nombre del titular · PDF</p>
            <input type="file" id="caratula_banco" accept=".pdf" onchange="verArchivo('caratula_banco')">
            <label for="caratula_banco" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="caratula_banco_nombre" class="file-name empty">Sin archivo</span>
        </div>

        {{-- Botón validar --}}
        <button id="btn_validar" class="btn-salcom" onclick="enviar()">
            <span class="spinner" id="spinner"></span>
            <i class="bi bi-shield-check" id="btn_icon"></i>
            <span id="btn_texto">Validar Documentos</span>
        </button>

        <div id="resultado"></div>

    </div>

    <p class="page-footer">Salcom 2.0 · Wiese / Salcom Industries · Sistema de validación fiscal</p>
</div>


<script>
// ── Campos y sus nombres para el FormData ──
const campos = {
    cif:            'cif_pdf',
    opinion:        'opinion_pdf',
    acta:           'acta_pdf',
    rep_legal:      'rep_legal_pdf',
    contribuyente:  'contribuyente_pdf',
    caratula_banco: 'caratula_banco_pdf',
};

// ── Valida que el archivo sea PDF antes de marcarlo ──
function verArchivo(campo) {
    const input   = document.getElementById(campo);
    const label   = document.getElementById(campo + '_nombre');
    const row     = document.getElementById('row_' + campo);
    const archivo = input.files[0];

    if (!archivo) {
        label.textContent = 'Sin archivo';
        label.className   = 'file-name empty';
        row.classList.remove('has-file', 'error-file');
        return;
    }

    // ── Validación de tipo PDF en el cliente ──
    if (archivo.type !== 'application/pdf' && !archivo.name.toLowerCase().endsWith('.pdf')) {
        label.textContent = '⚠ Ese archivo no es un PDF. Selecciona un PDF válido.';
        label.className   = 'file-name pdf-err';
        row.classList.remove('has-file');
        row.classList.add('error-file');
        input.value = ''; // limpiar el input
        return;
    }

    label.textContent = '✓ ' + archivo.name;
    label.className   = 'file-name';
    row.classList.remove('error-file');
    row.classList.add('has-file');
}

const semaforos = {
    verde:    { emoji: '🟢', texto: 'Todos los documentos válidos',  clase: 'verde'    },
    amarillo: { emoji: '🟡', texto: 'Válido con observaciones',      clase: 'amarillo' },
    rojo:     { emoji: '🔴', texto: 'Documentos con errores',        clase: 'rojo'     },
};

function enviar() {
    // Verificar que todos los campos tengan archivo válido
    for (const campo of Object.keys(campos)) {
        const input = document.getElementById(campo);
        if (!input.files[0]) {
            mostrarError('Debes subir los seis documentos antes de continuar.');
            return;
        }
        const archivo = input.files[0];
        if (archivo.type !== 'application/pdf' && !archivo.name.toLowerCase().endsWith('.pdf')) {
            mostrarError(`El archivo "${archivo.name}" no es un PDF válido.`);
            return;
        }
    }

    const btn   = document.getElementById('btn_validar');
    const spin  = document.getElementById('spinner');
    const icon  = document.getElementById('btn_icon');
    const texto = document.getElementById('btn_texto');

    btn.disabled       = true;
    spin.style.display = 'block';
    icon.style.display = 'none';
    texto.textContent  = 'Validando…';

    document.getElementById('resultado').innerHTML = `
        <div class="resultado-card procesando">
            <div class="resultado-header">
                <span class="semaforo">⏳</span>
                <span class="resultado-empresa">Procesando documentos…</span>
            </div>
            <small>Esto puede tardar unos segundos.</small>
        </div>`;

    const formData = new FormData();
    for (const [campo, nombreCampo] of Object.entries(campos)) {
        formData.append(nombreCampo, document.getElementById(campo).files[0]);
    }

    fetch('/api/empresa', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled       = false;
        spin.style.display = 'none';
        icon.style.display = 'inline';
        texto.textContent  = 'Validar Documentos';

        if (data.mensaje) { mostrarError(data.mensaje); return; }
        renderResultado(data);
    })
    .catch(() => {
        btn.disabled       = false;
        spin.style.display = 'none';
        icon.style.display = 'inline';
        texto.textContent  = 'Validar Documentos';
        mostrarError('Error de conexión. Intenta de nuevo.');
    });
}

function renderResultado(data) {
    const e      = data.empresa;
    const estado = e.estado;
    const sem    = semaforos[estado] || semaforos.rojo;

    const docs = [
        { label: 'CIF',                        ok: e.cif_valido === 'SI',          errores: [] },
        { label: 'Opinión SAT',                ok: data.opinion.valida,            errores: data.opinion.errores },
        { label: 'Acta Constitutiva',          ok: data.acta.valida,               errores: data.acta.errores },
        { label: 'ID Representante Legal',     ok: data.rep_legal.valida,          errores: data.rep_legal.errores },
        { label: 'ID Contribuyente',           ok: data.contribuyente.valida,      errores: data.contribuyente.errores },
        { label: 'Carátula de Banco',          ok: data.caratula_banco.valida,     errores: data.caratula_banco.errores },
    ];

    const filas = docs.map(d => `
        <div class="doc-check-row">
            <span class="check-icon">${d.ok ? '✅' : '❌'}</span>
            <div>
                <span class="check-label">${d.label}</span>
                <div class="check-errors">${d.errores.length ? d.errores.join(' · ') : 'Sin observaciones'}</div>
            </div>
        </div>`).join('');

    const todoVerde = estado === 'verde';
    const btnPortal = todoVerde ? `
        <hr class="resultado-divider">
        <a href="http://localhost:8000/portal-proveedor" class="btn-portal">
            <i class="bi bi-box-arrow-in-right"></i> Ir al Portal del Proveedor
        </a>` : '';

    document.getElementById('resultado').innerHTML = `
        <div class="resultado-card ${sem.clase}">
            <div class="resultado-header">
                <span class="semaforo">${sem.emoji}</span>
                <div>
                    <div class="resultado-empresa">${e.nombre}</div>
                    <div class="resultado-rfc">RFC: ${e.rfc} &nbsp;·&nbsp; ${sem.texto}</div>
                </div>
            </div>
            <hr class="resultado-divider">
            ${filas}
            ${btnPortal}
        </div>`;
}

function mostrarError(msg) {
    document.getElementById('resultado').innerHTML = `
        <div class="resultado-card rojo">
            <div class="resultado-header">
                <span class="semaforo">🔴</span>
                <span class="resultado-empresa">${msg}</span>
            </div>
        </div>`;
}
</script>

</body>
</html>