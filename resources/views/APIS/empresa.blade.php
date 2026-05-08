﻿<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Validación de Documentos — Industrias Salcom</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --purple:      #6B3FA0;
            --purple-dark: #4A2070;
            --purple-light:#F3EEFA;
            --purple-mid:  #9C6DD0;
            --gray-text:   #1a1a2e;
            --gray-muted:  #6b7280;
            --gray-soft:   #f9fafb;
            --border:      #e5e7eb;
            --white:       #FFFFFF;
            --green:       #059669;
            --green-bg:    #ecfdf5;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--gray-soft);
            color: var(--gray-text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .navbar-salcom {
            background: var(--white);
            padding: 0 2rem;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
        }
        .navbar-salcom .brand {
            font-size: 1rem;
            font-weight: 700;
            color: var(--purple);
        }
        .navbar-salcom .nav-badge {
            background: var(--purple-light);
            color: var(--purple);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .btn-back-nav {
            display: none;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-muted);
            text-decoration: none;
            padding: 6px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--white);
            cursor: pointer;
            transition: all .15s;
            font-family: inherit;
        }
        .btn-back-nav:hover { background: var(--purple-light); color: var(--purple); border-color: var(--purple-mid); }
        .btn-back-nav.visible { display: inline-flex; }

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
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-text);
            margin-bottom: 0.4rem;
        }
        .section-header p {
            font-size: 0.92rem;
            color: var(--gray-muted);
        }

        .group-title {
            font-size: 0.85rem;
            color: var(--gray-text);
            font-weight: 700;
            margin: 1.5rem 0 0.75rem;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-salcom {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
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
            background: var(--purple);
            color: var(--white);
            font-family: 'Inter', sans-serif;
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
            border-radius: 14px;
            padding: 1.5rem;
            border: 1.5px solid;
            animation: fadeIn 0.35s ease;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }

        .resultado-card.verde     { background: #f0fdf4; border-color: #bbf7d0; }
        .resultado-card.amarillo  { background: #fefce8; border-color: #fde68a; }
        .resultado-card.rojo      { background: #fef2f2; border-color: #fecaca; }
        .resultado-card.procesando{ background: var(--purple-light); border-color: var(--purple-mid); }

        .resultado-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0;
        }
        .semaforo           { display: flex; align-items: center; flex-shrink: 0; }
        .resultado-empresa  { font-size: 1.05rem; font-weight: 700; color: var(--gray-text); }
        .resultado-rfc      { font-size: 0.82rem; color: var(--gray-muted); font-weight: 500; margin-top: 2px; }

        .resultado-divider  { border: none; border-top: 1px solid var(--border); margin: 1rem 0; }

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

        /* ── Secciones detalladas del resultado ── */
        .seccion-doc {
            border-radius: 10px;
            padding: 1rem 1.1rem;
            margin-bottom: 0.65rem;
            border: 1px solid var(--border);
            background: var(--white);
            transition: box-shadow .2s;
        }
        .seccion-doc:hover { box-shadow: 0 2px 8px rgba(0,0,0,.04); }
        .seccion-doc.seccion-ok { border-left: 4px solid var(--green); }
        .seccion-doc.seccion-err { border-left: 4px solid #DC2626; }

        .seccion-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.6rem;
            flex-wrap: wrap;
        }
        .seccion-icon { display: flex; align-items: center; flex-shrink: 0; }
        .seccion-titulo {
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--gray-text);
            flex: 1;
        }

        .status-pill {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .status-pill.ok { background: #ecfdf5; color: #059669; }
        .status-pill.err { background: #fef2f2; color: #DC2626; }

        .chars-badge {
            margin-left: auto;
            font-size: 0.68rem;
            background: var(--purple-light);
            color: var(--purple);
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }

        .seccion-detalles { padding-left: 0.25rem; }

        .detalle-item {
            font-size: 0.82rem;
            padding: 4px 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            line-height: 1.4;
        }
        .detalle-item svg { flex-shrink: 0; }
        .detalle-item.ok { color: #047857; }
        .detalle-item.err { color: #B91C1C; }

        .page-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.78rem;
            color: var(--gray-text);
            opacity: 0.5;
        }

        .tipo-btn {
            flex: 1;
            padding: 16px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-text);
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .tipo-btn:hover { border-color: var(--purple-mid); background: var(--purple-light); color: var(--purple); }
        .tipo-btn.selected { border-color: var(--purple); background: var(--purple-light); color: var(--purple); box-shadow: 0 0 0 3px rgba(107,63,160,.15); }

        .optional-badge {
            font-size: 0.65rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
            background: var(--purple-light);
            color: var(--purple-mid);
            margin-left: 8px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
    </style>
</head>

<body>

<nav class="navbar-salcom">
    <div style="display:flex;align-items:center;gap:16px;">
        <button class="btn-back-nav" id="btnBack" onclick="history.back()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Regresar
        </button>
        <span class="brand">Industrias Salcom</span>
    </div>
    <span class="nav-badge"><i class="bi bi-shield-check"></i> Validación Fiscal</span>
</nav>

<div class="page-wrapper">

    <div class="section-header">
        <h1>Validación de Documentos</h1>
        <p>Sube los documentos requeridos para continuar con tu registro como proveedor.</p>
    </div>

    <div class="card-salcom">

        {{-- ── SELECTOR TIPO PERSONA ── --}}
        <p class="group-title"><i class="bi bi-person-lines-fill"></i> Tipo de Persona</p>
        <div style="display:flex;gap:12px;margin-bottom:1.5rem">
            <button type="button" class="tipo-btn" id="btn_moral" onclick="seleccionarTipo('moral')">
                <i class="bi bi-building"></i> Persona Moral
            </button>
            <button type="button" class="tipo-btn" id="btn_fisica" onclick="seleccionarTipo('fisica')">
                <i class="bi bi-person"></i> Persona Física
            </button>
        </div>

        <div id="formulario_docs" style="display:none">

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

        <div class="doc-row" id="row_acta" style="display:none">
            <label class="doc-label" for="acta"><i class="bi bi-building"></i> Acta Constitutiva</label>
            <p class="doc-hint">Documento de constitución de la empresa · PDF (solo Persona Moral)</p>
            <input type="file" id="acta" accept=".pdf" onchange="verArchivo('acta')">
            <label for="acta" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="acta_nombre" class="file-name empty">Sin archivo</span>
        </div>

        {{-- ── GRUPO 2: Identificaciones (opcionales) ── --}}
        <p class="group-title"><i class="bi bi-person-vcard"></i> Identificaciones Oficiales</p>

        <div class="doc-row" id="row_rep_legal">
            <label class="doc-label" for="rep_legal"><i class="bi bi-person-badge"></i> ID Oficial del Representante Legal</label>
            <p class="doc-hint">INE/IFE vigente del representante legal · PDF (opcional)</p>
            <input type="file" id="rep_legal" accept=".pdf" onchange="verArchivo('rep_legal')">
            <label for="rep_legal" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="rep_legal_nombre" class="file-name empty">Sin archivo</span>
        </div>

        <div class="doc-row" id="row_contribuyente">
            <label class="doc-label" for="contribuyente"><i class="bi bi-person-check"></i> ID Oficial del Contribuyente</label>
            <p class="doc-hint">INE/IFE vigente del contribuyente · PDF (opcional)</p>
            <input type="file" id="contribuyente" accept=".pdf" onchange="verArchivo('contribuyente')">
            <label for="contribuyente" class="file-btn"><i class="bi bi-upload"></i> Seleccionar PDF</label>
            <span id="contribuyente_nombre" class="file-name empty">Sin archivo</span>
        </div>

        {{-- ── GRUPO 3: Datos bancarios ── --}}
        <p class="group-title"><i class="bi bi-bank"></i> Datos Bancarios</p>

        <div class="doc-row" id="row_caratula_banco">
            <label class="doc-label" for="caratula_banco"><i class="bi bi-credit-card"></i> Carátula de Estado de Cuenta Bancario</label>
            <p class="doc-hint">Debe mostrar CLABE interbancaria (18 dígitos) o cuenta CLABE y nombre del titular · PDF</p>
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

        </div>{{-- fin formulario_docs --}}

    </div>

    <p class="page-footer">Industrias Salcom · Wiese / Salcom Industries · Sistema de validación fiscal</p>
</div>


<script>
// ── Estado del formulario ──
let tipoPersona = null; // 'moral' o 'fisica'

function seleccionarTipo(tipo) {
    tipoPersona = tipo;

    // UI de botones
    document.getElementById('btn_moral').classList.toggle('selected', tipo === 'moral');
    document.getElementById('btn_fisica').classList.toggle('selected', tipo === 'fisica');

    // Mostrar formulario
    document.getElementById('formulario_docs').style.display = 'block';

    // Acta constitutiva: solo para Persona Moral
    document.getElementById('row_acta').style.display = tipo === 'moral' ? 'block' : 'none';

    // Limpiar acta si cambia a física
    if (tipo === 'fisica') {
        document.getElementById('acta').value = '';
        document.getElementById('acta_nombre').textContent = 'Sin archivo';
        document.getElementById('acta_nombre').className = 'file-name empty';
        document.getElementById('row_acta').classList.remove('has-file');
    }
}

// ── Campos requeridos según tipo ──
function getCamposRequeridos() {
    const base = ['cif', 'opinion', 'caratula_banco'];
    if (tipoPersona === 'moral') base.push('acta');
    return base;
}

// ── Campos opcionales ──
function getCamposOpcionales() {
    return ['rep_legal', 'contribuyente'];
}

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

    if (archivo.type !== 'application/pdf' && !archivo.name.toLowerCase().endsWith('.pdf')) {
        label.textContent = '⚠ Ese archivo no es un PDF. Selecciona un PDF válido.';
        label.className   = 'file-name pdf-err';
        row.classList.remove('has-file');
        row.classList.add('error-file');
        input.value = '';
        return;
    }

    label.textContent = '✓ ' + archivo.name;
    label.className   = 'file-name';
    row.classList.remove('error-file');
    row.classList.add('has-file');
}

const semaforos = {
    verde:    { icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>', texto: 'Todos los documentos válidos',  clase: 'verde'    },
    amarillo: { icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#CA8A04" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>', texto: 'Válido con observaciones',      clase: 'amarillo' },
    rojo:     { icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>', texto: 'Documentos con errores',        clase: 'rojo'     },
};

function enviar() {
    if (!tipoPersona) {
        mostrarError('Selecciona primero si eres Persona Física o Persona Moral.');
        return;
    }

    // Verificar campos requeridos
    const requeridos = getCamposRequeridos();
    for (const campo of requeridos) {
        const input = document.getElementById(campo);
        if (!input.files[0]) {
            const nombres = { cif: 'CIF', opinion: 'Opinión de Cumplimiento', acta: 'Acta Constitutiva', caratula_banco: 'Carátula de Banco' };
            mostrarError(`Falta el documento: ${nombres[campo] || campo}`);
            return;
        }
        const archivo = input.files[0];
        if (archivo.type !== 'application/pdf' && !archivo.name.toLowerCase().endsWith('.pdf')) {
            const nombres = { cif: 'CIF', opinion: 'Opinión de Cumplimiento', acta: 'Acta Constitutiva', caratula_banco: 'Carátula de Banco' };
            mostrarError(`El archivo de "${nombres[campo] || campo}" no es un PDF válido.`);
            return;
        }
    }

    // Verificar opcionales que se hayan subido
    for (const campo of getCamposOpcionales()) {
        const input = document.getElementById(campo);
        if (input.files[0]) {
            const archivo = input.files[0];
            if (archivo.type !== 'application/pdf' && !archivo.name.toLowerCase().endsWith('.pdf')) {
                const nombres = { rep_legal: 'ID Representante Legal', contribuyente: 'ID Contribuyente' };
                mostrarError(`El archivo de "${nombres[campo] || campo}" no es un PDF válido.`);
                return;
            }
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
                <span class="semaforo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                <span class="resultado-empresa">Procesando documentos…</span>
            </div>
            <small style="color:var(--gray-muted)">Esto puede tardar unos segundos.</small>
        </div>`;

    const formData = new FormData();
    formData.append('tipo_persona', tipoPersona);

    // Agregar todos los campos que tengan archivo (requeridos + opcionales)
    for (const [campo, nombreCampo] of Object.entries(campos)) {
        const input = document.getElementById(campo);
        if (input.files[0]) {
            formData.append(nombreCampo, input.files[0]);
        }
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
    const estado = data.estado;
    const sem    = semaforos[estado] || semaforos.rojo;
    const cif    = data.cif;

    const nombre = cif.datos.nombre || 'NO DETECTADO';
    const rfc    = cif.datos.rfc || 'No detectado';
    const tipo   = cif.datos.tipo_persona || '—';

    const secciones = [
        { titulo: 'Constancia de Situación Fiscal (CIF)', icono: 'bi-file-earmark-text', doc: data.cif },
        { titulo: 'Opinión de Cumplimiento SAT',          icono: 'bi-patch-check',       doc: data.opinion },
    ];

    if (tipoPersona === 'moral') {
        secciones.push({ titulo: 'Acta Constitutiva', icono: 'bi-building', doc: data.acta });
    }

    if (data.rep_legal) {
        secciones.push({ titulo: 'ID Representante Legal', icono: 'bi-person-badge', doc: data.rep_legal });
    }
    if (data.contribuyente) {
        secciones.push({ titulo: 'ID Contribuyente', icono: 'bi-person-check', doc: data.contribuyente });
    }

    secciones.push({ titulo: 'Carátula de Banco', icono: 'bi-credit-card', doc: data.caratula_banco });

    const seccionesHtml = secciones.map(s => {
        if (!s.doc) return '';
        const ok = s.doc.valida;
        const hallazgos = (s.doc.hallazgos || []);
        const errores   = (s.doc.errores || []);

        const iconOk  = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
        const iconErr = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
        const statusIcon = ok ? iconOk : iconErr;
        const statusLabel = ok ? '<span class="status-pill ok">Aprobado</span>' : '<span class="status-pill err">Revisar</span>';

        const hallazgosHtml = hallazgos.map(h => `<div class="detalle-item ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> ${h}</div>`).join('');
        const erroresHtml = errores.map(e => `<div class="detalle-item err"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> ${e}</div>`).join('');

        return `
        <div class="seccion-doc ${ok ? 'seccion-ok' : 'seccion-err'}">
            <div class="seccion-header">
                <span class="seccion-icon">${statusIcon}</span>
                <span class="seccion-titulo">${s.titulo}</span>
                ${statusLabel}
            </div>
            <div class="seccion-detalles">
                ${hallazgosHtml}
                ${erroresHtml}
                ${!hallazgos.length && !errores.length ? '<div class="detalle-item ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Sin observaciones</div>' : ''}
            </div>
        </div>`;
    }).join('');

    const todoVerde = estado === 'verde';
    const btnPortal = todoVerde ? `
        <hr class="resultado-divider">
        <a href="/portal-proveedor" class="btn-portal">
            <i class="bi bi-box-arrow-in-right"></i> Ir al Portal del Proveedor
        </a>` : '';

    const tipoLabel = tipoPersona === 'moral' ? 'Persona Moral' : 'Persona Física';

    document.getElementById('resultado').innerHTML = `
        <div class="resultado-card ${sem.clase}">
            <div class="resultado-header">
                <span class="semaforo">${sem.icon}</span>
                <div>
                    <div class="resultado-empresa">${nombre}</div>
                    <div class="resultado-rfc">RFC: ${rfc} · ${tipoLabel} · ${sem.texto}</div>
                </div>
            </div>
            <hr class="resultado-divider">
            ${seccionesHtml}
            ${btnPortal}
        </div>`;
}

function mostrarError(msg) {
    document.getElementById('resultado').innerHTML = `
        <div class="resultado-card rojo">
            <div class="resultado-header">
                <span class="semaforo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></span>
                <span class="resultado-empresa">${msg}</span>
            </div>
        </div>`;
}

// ── Back button ──
(function() {
    const ref = document.referrer;
    const origin = window.location.origin;
    if (ref && ref.startsWith(origin) && !ref.includes('validacion-fiscal')) {
        document.getElementById('btnBack').classList.add('visible');
    }
})();
</script>

</body>
</html>