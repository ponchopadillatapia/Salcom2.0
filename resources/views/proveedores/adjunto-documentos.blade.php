@extends('layouts.proveedor')
@section('title', 'Adjunto de Documentos Fiscales')
@section('hero')
<div class="hero-band">
    <h1>Adjunto de Documentos Fiscales</h1>
    <p>Sube tus documentos en PDF para que el equipo de Salcom los valide</p>
</div>
@endsection
@push('styles')
<style>
    .adj-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px;margin-bottom:20px;box-shadow:var(--shadow-sm)}
    .adj-card h4{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:6px}
    .adj-card p{font-size:13px;color:var(--gray-muted);margin-bottom:20px}
    .adj-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .adj-item{background:var(--gray-soft);border:1.5px dashed var(--border);border-radius:12px;padding:16px;transition:var(--transition)}
    .adj-item:hover{border-color:var(--purple-mid);background:var(--purple-subtle)}
    .adj-item.has-file{border-style:solid;border-color:var(--green);background:#ecfdf5}
    .adj-item label.adj-label{font-size:13px;font-weight:700;color:var(--gray-text);display:block;margin-bottom:4px}
    .adj-item .adj-hint{font-size:11px;color:var(--gray-muted);margin-bottom:10px}
    .adj-item input[type="file"]{display:none}
    .adj-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid var(--border);border-radius:8px;background:var(--white);font-size:12px;font-weight:600;color:var(--purple);cursor:pointer;transition:var(--transition)}
    .adj-btn:hover{border-color:var(--purple);background:var(--purple-light)}
    .adj-file-name{font-size:11px;color:var(--green);font-weight:600;margin-top:6px}
    .adj-file-name.empty{color:var(--gray-muted);font-weight:400}
    .adj-remove-btn{display:inline-block;margin-left:8px;padding:2px 8px;border:none;border-radius:4px;background:#fee2e2;color:#dc2626;font-size:10px;font-weight:700;cursor:pointer;vertical-align:middle}
    .adj-remove-btn:hover{background:#fecaca}
    .btn-enviar{padding:12px 32px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:var(--transition);display:block;width:100%;margin-top:20px}
    .btn-enviar:hover{background:var(--purple-dark);transform:translateY(-1px)}
    .adj-success{background:#ecfdf5;border:1px solid var(--green);border-radius:10px;padding:14px;font-size:13px;color:var(--green);font-weight:600;margin-bottom:16px}
    .adj-docs-list{margin-top:16px}
    .adj-doc-row{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--border-light);font-size:12px}
    .adj-doc-row:last-child{border-bottom:none}
    .adj-doc-tipo{font-weight:600;color:var(--gray-text)}
    .adj-doc-fecha{color:var(--gray-muted)}
    .adj-doc-badge{font-size:10px;font-weight:700;padding:3px 8px;border-radius:999px}
    .adj-doc-badge.pendiente{background:var(--amber-bg);color:var(--amber)}
    .adj-doc-badge.aprobado{background:var(--green-bg);color:var(--green)}
    @media(max-width:768px){.adj-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

@if(session('exito'))
    <div class="adj-success">✓ {{ session('exito') }}</div>
@endif

@if($errors->any())
    <div style="background:#fef2f2;border:1px solid #dc2626;border-radius:10px;padding:14px;font-size:13px;color:#dc2626;margin-bottom:16px;">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="adj-card">
    <h4>Subir documentos fiscales</h4>
    <p>Selecciona los documentos que deseas enviar. Solo se aceptan archivos PDF (máx. 10 MB cada uno).</p>

    <form method="POST" action="{{ route('proveedores.adjunto-documentos.subir') }}" enctype="multipart/form-data" id="formAdjuntos">
        @csrf
        <div class="adj-grid">
            <div class="adj-item" id="row_cif">
                <label class="adj-label">Constancia de Situación Fiscal (CIF)</label>
                <div class="adj-hint">Debe corresponder al mes en curso · PDF</div>
                <input type="file" id="file_cif" name="cif" accept=".pdf" onchange="adjVerArchivo('cif')">
                <label for="file_cif" class="adj-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Seleccionar PDF</label>
                <div id="name_cif" class="adj-file-name empty">Sin archivo</div>
            </div>

            <div class="adj-item" id="row_opinion">
                <label class="adj-label">Opinión de Cumplimiento SAT</label>
                <div class="adj-hint">Debe ser Positiva y del mes en curso · PDF</div>
                <input type="file" id="file_opinion" name="opinion" accept=".pdf" onchange="adjVerArchivo('opinion')">
                <label for="file_opinion" class="adj-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Seleccionar PDF</label>
                <div id="name_opinion" class="adj-file-name empty">Sin archivo</div>
            </div>

            <div class="adj-item" id="row_acta">
                <label class="adj-label">Acta Constitutiva</label>
                <div class="adj-hint">Solo Persona Moral · PDF</div>
                <input type="file" id="file_acta" name="acta" accept=".pdf" onchange="adjVerArchivo('acta')">
                <label for="file_acta" class="adj-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Seleccionar PDF</label>
                <div id="name_acta" class="adj-file-name empty">Sin archivo</div>
            </div>

            <div class="adj-item" id="row_rep_legal">
                <label class="adj-label">INE Representante Legal</label>
                <div class="adj-hint">INE/IFE vigente · PDF</div>
                <input type="file" id="file_rep_legal" name="rep_legal" accept=".pdf" onchange="adjVerArchivo('rep_legal')">
                <label for="file_rep_legal" class="adj-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Seleccionar PDF</label>
                <div id="name_rep_legal" class="adj-file-name empty">Sin archivo</div>
            </div>

            <div class="adj-item" id="row_contribuyente">
                <label class="adj-label">INE Contribuyente</label>
                <div class="adj-hint">INE/IFE vigente · PDF</div>
                <input type="file" id="file_contribuyente" name="contribuyente" accept=".pdf" onchange="adjVerArchivo('contribuyente')">
                <label for="file_contribuyente" class="adj-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Seleccionar PDF</label>
                <div id="name_contribuyente" class="adj-file-name empty">Sin archivo</div>
            </div>

            <div class="adj-item" id="row_caratula">
                <label class="adj-label">Carátula Bancaria</label>
                <div class="adj-hint">Estado de cuenta con CLABE · PDF</div>
                <input type="file" id="file_caratula" name="caratula_banco" accept=".pdf" onchange="adjVerArchivo('caratula')">
                <label for="file_caratula" class="adj-btn"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Seleccionar PDF</label>
                <div id="name_caratula" class="adj-file-name empty">Sin archivo</div>
            </div>
        </div>

        <button type="submit" class="btn-enviar">Enviar documentos para revisión</button>
    </form>
</div>

{{-- Documentos ya subidos --}}
@if($documentos->count())
<div class="adj-card">
    <h4>Documentos enviados</h4>
    <p>Historial de documentos que has subido.</p>
    <div class="adj-docs-list">
        @foreach($documentos as $doc)
            <div class="adj-doc-row">
                <span class="adj-doc-tipo">{{ $tiposLabel[$doc->tipo] ?? ucfirst($doc->tipo) }}</span>
                <span class="adj-doc-fecha">{{ $doc->created_at->format('d/m/Y H:i') }}</span>
                <span class="adj-doc-badge {{ $doc->estatus }}">{{ ucfirst($doc->estatus) }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection
@push('scripts')
<script>
function adjVerArchivo(campo) {
    var input = document.getElementById('file_' + campo);
    var label = document.getElementById('name_' + campo);
    var row = document.getElementById('row_' + campo);
    if (!input.files[0]) {
        label.innerHTML = 'Sin archivo';
        label.className = 'adj-file-name empty';
        row.classList.remove('has-file');
        return;
    }
    var archivo = input.files[0];
    if (archivo.type !== 'application/pdf' && !archivo.name.toLowerCase().endsWith('.pdf')) {
        label.innerHTML = '⚠ Solo se aceptan archivos PDF';
        label.className = 'adj-file-name';
        label.style.color = '#dc2626';
        row.classList.remove('has-file');
        input.value = '';
        return;
    }
    label.innerHTML = '✓ ' + archivo.name + ' <button type="button" class="adj-remove-btn" onclick="adjQuitarArchivo(\'' + campo + '\')">✕ Quitar</button>';
    label.className = 'adj-file-name';
    label.style.color = '';
    row.classList.add('has-file');
}

function adjQuitarArchivo(campo) {
    var input = document.getElementById('file_' + campo);
    var label = document.getElementById('name_' + campo);
    var row = document.getElementById('row_' + campo);
    input.value = '';
    label.innerHTML = 'Sin archivo';
    label.className = 'adj-file-name empty';
    row.classList.remove('has-file');
}
</script>
@endpush
