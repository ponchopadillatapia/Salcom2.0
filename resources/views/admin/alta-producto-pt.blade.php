@extends('layouts.admin')
@section('title', 'Alta Producto Comercial PT')
@section('hero')
<div class="hero-band">
    <h1>Alta Producto Comercial PT</h1>
    <p>Sube tu Excel exclusivo para Producto Terminado. Incluye las 6 clasificaciones (Departamento, Línea, Subfamilia, Canal, Vendedor, Módulo).</p>
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
    .pt-badge{display:inline-block;background:#6B3FA0;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;margin-left:6px}
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
        <h3>Cómo dar de alta Producto Terminado <span class="pt-badge">PT</span></h3>
        <div class="alta-steps">
            <div class="alta-step">
                <div class="alta-step-num">1</div>
                <div class="alta-step-text"><strong>Descarga el template PT</strong>Excel exclusivo para Producto Terminado con dropdowns de las 6 clasificaciones.</div>
            </div>
            <div class="alta-step">
                <div class="alta-step-num">2</div>
                <div class="alta-step-text"><strong>Llena tus productos</strong>Código PT (E/M/N + letras), nombres, clasificaciones de Departamento a Módulo.</div>
            </div>
            <div class="alta-step">
                <div class="alta-step-num">3</div>
                <div class="alta-step-text"><strong>Sube el Excel</strong>El sistema valida formato y da de alta automático.</div>
            </div>
        </div>

        <div style="margin-top:20px;">
            <a href="{{ route('admin.alta-producto-pt.template') }}" class="btn-download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar Template PT
            </a>
        </div>

        <div class="alta-rules">
            <h4>Códigos PT válidos</h4>
            <div style="background:var(--purple-subtle);border:1px solid #d4c4e8;border-radius:10px;padding:14px;font-size:13px;color:var(--gray-text);">
                <strong>Los códigos PT empiezan con E, M o N + letras:</strong><br>
                <span style="font-size:12px;margin-top:6px;display:block;color:var(--gray-muted);">Ejemplos: EAEHO001, MAEDC100, NAEHO050. La columna TIPO_PRODUCTO viene pre-llenada como PT.</span>
            </div>
        </div>
    </div>

    {{-- Upload --}}
    <div class="alta-card">
        <h3>Subir Excel PT</h3>
        <form method="POST" action="{{ route('admin.alta-producto-pt.subir') }}" enctype="multipart/form-data" id="formUpload">
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
                Subir y validar
            </button>
        </form>

        <h3 style="margin-top:24px;">Formato del Excel PT</h3>
        <div style="background:#f8f5ff;border:1px solid #d4c4e8;border-radius:10px;padding:16px;font-size:12px;">
            <table class="format-table" style="margin-top:0;">
                <thead><tr><th>Columna</th><th>Ejemplo</th><th>Req.</th></tr></thead>
                <tbody>
                    <tr><td style="color:var(--purple);font-weight:700;">CODIGO</td><td>EAEHO001</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_TIPO</td><td>AEROSOL AROMATIZANTE</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MARCA</td><td>WIESE</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MODELO</td><td>FRESH CLASSIC</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MEDIDA</td><td>400ML</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_ESPECIFICACION</td><td>LAVANDA</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">FAMILIA</td><td>AEROSOLES</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">TIPO_PRODUCTO</td><td>PT (pre-llenado)</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">UNIDAD_MEDIDA</td><td style="color:var(--gray-muted);">PZA / CAJA</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">PRECIO</td><td style="color:var(--gray-muted);">$85.00</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">CLAVE_SAT</td><td style="color:var(--gray-muted);">10191509</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">LOTE</td><td style="color:var(--gray-muted);">SI / NO</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">PEDIMENTO</td><td style="color:var(--gray-muted);">SI / NO</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">VOLTAJE</td><td style="color:var(--gray-muted);">N/A</td><td class="opt">—</td></tr>
                    <tr><td style="color:#9333ea;font-weight:600;">DEPARTAMENTO</td><td>PT</td><td class="opt">dropdown</td></tr>
                    <tr><td style="color:#9333ea;font-weight:600;">LINEA</td><td>Aerosoles</td><td class="opt">dropdown</td></tr>
                    <tr><td style="color:#9333ea;font-weight:600;">SUBFAMILIA</td><td>Aerosol 19oz</td><td class="opt">dropdown</td></tr>
                    <tr><td style="color:#9333ea;font-weight:600;">CANAL</td><td>Autoservicio</td><td class="opt">dropdown</td></tr>
                    <tr><td style="color:#9333ea;font-weight:600;">VENDEDOR</td><td>Jorge Ornelas</td><td class="opt">dropdown</td></tr>
                    <tr><td style="color:#9333ea;font-weight:600;">MODULO</td><td>AEROSOL</td><td class="opt">dropdown</td></tr>
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
