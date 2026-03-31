<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Validación de Documentos — Salcom 2.0</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* ── Variables del sistema visual de Said ── */
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

        /* ── Navbar ── */
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
        .navbar-salcom .brand span {
            color: #C9A8FF;
        }
        .navbar-salcom .nav-badge {
            background: rgba(255,255,255,0.15);
            color: var(--white);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.25);
        }

        /* ── Layout central ── */
        .page-wrapper {
            max-width: 680px;
            margin: 2.5rem auto;
            padding: 0 1rem;
        }

        /* ── Encabezado de sección ── */
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

        /* ── Card principal ── */
        .card-salcom {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 24px rgba(107,63,160,0.08);
        }

        /* ── Cada fila de documento ── */
        .doc-row {
            background: var(--gray-soft);
            border: 1.5px dashed var(--border);
            border-radius: 12px;
            padding: 1.1rem 1.25rem;
            margin-bottom: 1rem;
            transition: border-color 0.2s, background 0.2s;
        }
        .doc-row:hover {
            border-color: var(--purple-mid);
            background: var(--purple-light);
        }
        .doc-row.has-file {
            border-style: solid;
            border-color: var(--purple);
            background: var(--purple-light);
        }
        .doc-row label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--purple-dark);
            margin-bottom: 0.6rem;
            cursor: pointer;
        }
        .doc-row label i {
            font-size: 1rem;
            color: var(--purple-mid);
        }
        .doc-row .doc-hint {
            font-size: 0.75rem;
            color: var(--gray-text);
            opacity: 0.7;
            margin-bottom: 0.6rem;
        }
        .doc-row input[type="file"] {
            display: none;
        }
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
        .file-btn:hover {
            background: var(--purple-light);
            border-color: var(--purple);
        }
        .file-name {
            margin-left: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--green);
        }
        .file-name.empty {
            color: var(--gray-text);
            opacity: 0.55;
            font-weight: 400;
        }

        /* ── Botón principal ── */
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
            margin-top: 0.5rem;
            transition: opacity 0.2s, transform 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-salcom:hover:not(:disabled) {
            opacity: 0.92;
            transform: translateY(-1px);
        }
        .btn-salcom:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* ── Spinner ── */
        .spinner {
            width: 18px; height: 18px;
            border: 3px solid rgba(255,255,255,0.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Resultado ── */
        #resultado { margin-top: 1.5rem; }

        .resultado-card {
            border-radius: 12px;
            padding: 1.25rem 1.4rem;
            border: 1.5px solid;
            animation: fadeIn 0.35s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

        .resultado-card.verde {
            background: var(--green-bg);
            border-color: var(--green);
        }
        .resultado-card.amarillo {
            background: #FEF9C3;
            border-color: #CA8A04;
        }
        .resultado-card.rojo {
            background: #FEE2E2;
            border-color: #DC2626;
        }
        .resultado-card.procesando {
            background: var(--purple-light);
            border-color: var(--purple-mid);
        }

        .resultado-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.8rem;
        }
        .semaforo {
            font-size: 1.5rem;
        }
        .resultado-empresa {
            font-size: 1rem;
            font-weight: 700;
            color: var(--purple-dark);
        }
        .resultado-rfc {
            font-size: 0.8rem;
            color: var(--gray-text);
            font-weight: 500;
        }

        .resultado-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 0.75rem 0;
        }

        .doc-check-row {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .doc-check-row .check-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
        .doc-check-row .check-label { font-weight: 700; color: var(--gray-text); }
        .doc-check-row .check-errors {
            font-size: 0.78rem;
            color: #B45309;
            margin-top: 1px;
        }

        /* ── Footer ── */
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

{{-- ── Navbar ── --}}
<nav class="navbar-salcom">
    <span class="brand">Salcom <span>2.0</span></span>
    <span class="nav-badge"><i class="bi bi-shield-check"></i> Validación Fiscal</span>
</nav>

{{-- ── Contenido principal ── --}}
<div class="page-wrapper">

    <div class="section-header">
        <h1>Validación de Documentos</h1>
        <p>Sube los tres documentos fiscales requeridos para continuar con tu registro como proveedor.</p>
    </div>

    <div class="card-salcom">

        {{-- Documento 1: CIF --}}
        <div class="doc-row" id="row_cif">
            <label for="cif">
                <i class="bi bi-file-earmark-text"></i>
                Constancia de Situación Fiscal (CIF)
            </label>
            <p class="doc-hint">Debe corresponder al mes en curso · Formato PDF</p>
            <input type="file" id="cif" accept=".pdf" onchange="verArchivo('cif')">
            <label for="cif" class="file-btn">
                <i class="bi bi-upload"></i> Seleccionar PDF
            </label>
            <span id="cif_nombre" class="file-name empty">Sin archivo</span>
        </div>

        {{-- Documento 2: Opinión --}}
        <div class="doc-row" id="row_opinion">
            <label for="opinion">
                <i class="bi bi-patch-check"></i>
                Opinión de Cumplimiento del SAT (Positiva)
            </label>
            <p class="doc-hint">Debe ser Positiva y del mes en curso · Formato PDF</p>
            <input type="file" id="opinion" accept=".pdf" onchange="verArchivo('opinion')">
            <label for="opinion" class="file-btn">
                <i class="bi bi-upload"></i> Seleccionar PDF
            </label>
            <span id="opinion_nombre" class="file-name empty">Sin archivo</span>
        </div>

        {{-- Documento 3: Acta --}}
        <div class="doc-row" id="row_acta">
            <label for="acta">
                <i class="bi bi-building"></i>
                Acta Constitutiva
            </label>
            <p class="doc-hint">Documento de constitución de la empresa · Formato PDF</p>
            <input type="file" id="acta" accept=".pdf" onchange="verArchivo('acta')">
            <label for="acta" class="file-btn">
                <i class="bi bi-upload"></i> Seleccionar PDF
            </label>
            <span id="acta_nombre" class="file-name empty">Sin archivo</span>
        </div>

        {{-- Botón validar --}}
        <button id="btn_validar" class="btn-salcom" onclick="enviar()">
            <span class="spinner" id="spinner"></span>
            <i class="bi bi-shield-check" id="btn_icon"></i>
            <span id="btn_texto">Validar Documentos</span>
        </button>

        {{-- Resultado --}}
        <div id="resultado"></div>

    </div>

    <p class="page-footer">Salcom 2.0 · Wiese / Salcom Industries · Sistema de validación fiscal</p>
</div>


<script>
// ── Muestra nombre del archivo y activa estilo del row ──
function verArchivo(campo) {
    const input   = document.getElementById(campo);
    const label   = document.getElementById(campo + '_nombre');
    const row     = document.getElementById('row_' + campo);
    const archivo = input.files[0];

    if (archivo) {
        label.textContent = '✓ ' + archivo.name;
        label.classList.remove('empty');
        row.classList.add('has-file');
    } else {
        label.textContent = 'Sin archivo';
        label.classList.add('empty');
        row.classList.remove('has-file');
    }
}

// ── Íconos y textos según semáforo ──
const semaforos = {
    verde:    { emoji: '🟢', texto: 'Documentos válidos',      clase: 'verde'    },
    amarillo: { emoji: '🟡', texto: 'Válido con observaciones', clase: 'amarillo' },
    rojo:     { emoji: '🔴', texto: 'Documentos con errores',  clase: 'rojo'     },
};

// ── Envío al controlador ──
function enviar() {
    const cif     = document.getElementById('cif').files[0];
    const opinion = document.getElementById('opinion').files[0];
    const acta    = document.getElementById('acta').files[0];

    if (!cif || !opinion || !acta) {
        mostrarError('Debes subir los tres documentos antes de continuar.');
        return;
    }

    // UI: cargando
    const btn    = document.getElementById('btn_validar');
    const spin   = document.getElementById('spinner');
    const icon   = document.getElementById('btn_icon');
    const texto  = document.getElementById('btn_texto');

    btn.disabled      = true;
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
    formData.append('cif_pdf',     cif);
    formData.append('opinion_pdf', opinion);
    formData.append('acta_pdf',    acta);

    fetch('/api/empresa', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        // Restaurar botón
        btn.disabled       = false;
        spin.style.display = 'none';
        icon.style.display = 'inline';
        texto.textContent  = 'Validar Documentos';

        if (data.mensaje) {
            mostrarError(data.mensaje);
            return;
        }

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

// ── Renderiza el resultado completo ──
function renderResultado(data) {
    const e        = data.empresa;
    const estado   = e.estado; // verde | amarillo | rojo
    const sem      = semaforos[estado] || semaforos.rojo;

    const opinionOk = data.opinion.valida;
    const actaOk    = data.acta.valida;

    const opinionErrores = data.opinion.errores.length
        ? data.opinion.errores.join(' · ')
        : 'Sin observaciones';
    const actaErrores = data.acta.errores.length
        ? data.acta.errores.join(' · ')
        : 'Sin observaciones';

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

            <div class="doc-check-row">
                <span class="check-icon">${e.rfc_valido === 'válido' ? '✅' : '❌'}</span>
                <div>
                    <span class="check-label">RFC</span>
                    <div class="check-errors">${e.rfc_valido}</div>
                </div>
            </div>

            <div class="doc-check-row">
                <span class="check-icon">${opinionOk ? '✅' : '❌'}</span>
                <div>
                    <span class="check-label">Opinión de Cumplimiento SAT</span>
                    <div class="check-errors">${opinionErrores}</div>
                </div>
            </div>

            <div class="doc-check-row">
                <span class="check-icon">${actaOk ? '✅' : '❌'}</span>
                <div>
                    <span class="check-label">Acta Constitutiva</span>
                    <div class="check-errors">${actaErrores}</div>
                </div>
            </div>

        </div>`;
}

// ── Mensaje de error genérico ──
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