@extends('layouts.proveedor')
@section('title', 'Alta de Producto')
@section('hero')
<div class="hero-band">
    <h1>Alta de Producto</h1>
    <p>Sube tu Excel estandarizado para dar de alta productos nuevos. La IA validará el formato automáticamente.</p>
</div>
@endsection
@push('styles')
<style>
    .alta-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px}
    .alta-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px}
    .alta-card h3{font-size:15px;font-weight:700;color:var(--gray-text);margin-bottom:16px}
    .alta-steps{display:flex;flex-direction:column;gap:12px}
    .alta-step{display:flex;align-items:flex-start;gap:12px;font-size:13px}
    .alta-step-num{width:28px;height:28px;border-radius:50%;background:var(--purple);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
    .alta-step-text{padding-top:4px;color:var(--gray-text)}
    .alta-step-text strong{display:block;margin-bottom:2px}
    .alta-rules{background:var(--gray-soft);border-radius:10px;padding:16px;margin-top:16px}
    .alta-rules h4{font-size:12px;font-weight:700;color:var(--gray-text);margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px}
    .alta-rules ul{list-style:none;padding:0;margin:0;font-size:12px;color:var(--gray-muted)}
    .alta-rules li{padding:4px 0;display:flex;align-items:center;gap:6px}
    .alta-rules li::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--purple);flex-shrink:0}
    .upload-zone{border:2px dashed var(--border);border-radius:14px;padding:40px;text-align:center;transition:var(--transition);cursor:pointer}
    .upload-zone:hover{border-color:var(--purple);background:var(--purple-subtle)}
    .upload-zone.dragover{border-color:var(--purple);background:var(--purple-light)}
    .btn-download{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--green);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none;transition:var(--transition)}
    .btn-download:hover{background:#15803d;transform:translateY(-1px)}
    .btn-upload{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:var(--transition);margin-top:16px}
    .btn-upload:hover{background:var(--purple-dark)}
    .btn-upload:disabled{opacity:.5;cursor:not-allowed}
    .format-table{width:100%;border-collapse:collapse;font-size:12px;margin-top:12px}
    .format-table th{text-align:left;padding:8px;background:var(--gray-soft);font-weight:600;color:var(--gray-muted);font-size:11px;text-transform:uppercase;border-bottom:1px solid var(--border-light)}
    .format-table td{padding:8px;border-bottom:1px solid var(--border-light);color:var(--gray-text)}
    .format-table .req{color:var(--red);font-weight:700}
    .format-table .opt{color:var(--gray-muted)}
    .alert-success{background:var(--green-bg);border:1px solid var(--green);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--green);margin-bottom:16px}
    .alert-error{background:var(--red-bg);border:1px solid var(--red);border-radius:8px;padding:12px 16px;font-size:13px;color:var(--red);margin-bottom:16px;white-space:pre-line}
    .correccion-tag{background:#2d0a4e;color:#fff;padding:2px 8px;border-radius:4px;font-weight:700;font-size:12px;display:inline-block;margin-left:4px}
    @media(max-width:768px){.alta-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
<div class="alert-success">{!! nl2br(e(session('mensaje'))) !!}</div>
@endif
@if(session('error'))
<div class="alert-error" style="white-space:pre-line;">
    {!! session('error') !!}
    @if(session('archivo_correcciones'))
    <div style="margin-top:12px;">
        <a href="{{ asset('storage/' . session('archivo_correcciones')) }}" download style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--red);color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Descargar Excel con correcciones
        </a>
    </div>
    @endif
</div>
@endif
@if($errors->any())
<div class="alert-error">
    @foreach($errors->all() as $error)
        {{ $error }}<br>
    @endforeach
</div>
@endif

<div class="alta-grid">
    {{-- Instrucciones --}}
    <div class="alta-card">
        <h3>Cómo dar de alta un producto</h3>
        <div class="alta-steps">
            <div class="alta-step">
                <div class="alta-step-num">1</div>
                <div class="alta-step-text"><strong>Descarga el template</strong>Baja el Excel con el formato estandarizado de Salcom.</div>
            </div>
            <div class="alta-step">
                <div class="alta-step-num">2</div>
                <div class="alta-step-text"><strong>Llena tus productos</strong>Código, nombre, familia, unidad, precio, datos de empaque y logística.</div>
            </div>
            <div class="alta-step">
                <div class="alta-step-num">3</div>
                <div class="alta-step-text"><strong>Sube el Excel</strong>La IA validará al instante. Si todo está bien, se da de alta automático.</div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <a href="{{ route('proveedores.alta-producto.template') }}" class="btn-download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar Template Excel
            </a>
        </div>

        <div class="alta-rules">
            <h4>Instrucciones</h4>
            <div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:14px;font-size:13px;color:#92400e;">
                <strong>📋 Lee la hoja "Instrucciones" dentro del Excel template.</strong><br>
                <span style="font-size:12px;margin-top:6px;display:block;">Ahí se explica cada columna, cómo llenarla correctamente y qué formatos son válidos. El template ya viene vacío listo para que llenes tus productos desde la fila 2.</span>
            </div>
        </div>
    </div>

    {{-- Upload --}}
    <div class="alta-card">
        <h3>Subir Excel</h3>
        <form method="POST" action="{{ route('proveedores.alta-producto.subir') }}" enctype="multipart/form-data" id="formUpload">
            @csrf
            <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                <div class="upload-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div style="font-size:14px;font-weight:600;color:var(--gray-text);margin-top:8px;">Arrastra tu Excel aquí o haz clic</div>
                <div style="font-size:12px;color:var(--gray-muted);margin-top:4px;">Formatos: .xlsx, .xls, .csv · Máximo 5MB</div>
                <div id="fileName" style="margin-top:8px;font-size:12px;color:var(--purple);font-weight:600;display:none;"></div>
            </div>
            <input type="file" name="excel" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none;" onchange="showFileName(this)">
            <button type="submit" class="btn-upload" id="btnUpload" disabled>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Subir y validar con IA
            </button>
        </form>

        <h3 style="margin-top:24px;">Formato del Excel</h3>
        <div style="background:#f8f5ff;border:1px solid #d4c4e8;border-radius:10px;padding:16px;font-size:12px;">
            <table class="format-table" style="margin-top:0;">
                <thead><tr><th>Columna</th><th>Ejemplo</th><th>Req.</th></tr></thead>
                <tbody>
                    <tr><td style="color:var(--purple);font-weight:700;">CODIGO</td><td>MPI0538</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_TIPO</td><td>RESINA EPOXICA</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MARCA</td><td>SKF</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MODELO</td><td>IPHONE 15 / VIN-100</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MEDIDA</td><td>500ML</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_ESPECIFICACION</td><td>TRANSPARENTE</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">FAMILIA</td><td>MATERIA PRIMA</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">TIPO_PRODUCTO</td><td>MPI / ME / MN</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">UNIDAD_MEDIDA</td><td style="color:var(--gray-muted);">KG / PZA / CAJA</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">PRECIO</td><td style="color:var(--gray-muted);">$150.50</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">CLAVE_SAT</td><td style="color:var(--gray-muted);">10191509</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--amber);font-weight:600;">LOTE</td><td>SI / NO</td><td style="color:var(--amber);font-weight:700;">si MPI</td></tr>
                    <tr><td style="color:var(--amber);font-weight:600;">PEDIMENTO</td><td>SI / NO</td><td style="color:var(--amber);font-weight:700;">si MPI</td></tr>
                    <tr><td style="color:var(--gray-muted);">VOLTAJE</td><td style="color:var(--gray-muted);">220/440V</td><td class="opt">—</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function showFileName(input) {
    const name = input.files[0]?.name;
    if (name) {
        document.getElementById('fileName').textContent = name;
        document.getElementById('fileName').style.display = 'block';
        document.getElementById('btnUpload').disabled = false;
    }
}

const zone = document.getElementById('uploadZone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.name.endsWith('.csv'))) {
        const input = document.getElementById('fileInput');
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showFileName(input);
    }
});
</script>
@endpush
