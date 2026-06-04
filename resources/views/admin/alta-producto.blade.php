@extends('layouts.admin')
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
            <a href="{{ route('admin.alta-producto.template') }}" class="btn-download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar Template Excel
            </a>
        </div>

        <div class="alta-rules">
            <h4>Reglas para llenar el Excel</h4>
            <div style="background:var(--purple-subtle);border:1px solid var(--purple-mid);border-radius:8px;padding:14px;margin-bottom:12px;font-size:12px;">
                <strong style="font-size:13px;color:var(--purple);display:block;margin-bottom:10px;">Llena cada columna en este orden:</strong>
                <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 14px;">
                    <span style="font-weight:700;color:var(--purple);">CODIGO</span><span>Código único del producto (ej: MPI0538, ME0201)</span>
                    <span style="font-weight:700;color:var(--purple);">NOMBRE_TIPO</span><span>Qué es el producto (ej: PINTURA VINILICA, BOMBA AGUA, CAJA CARTON)</span>
                    <span style="font-weight:700;color:var(--purple);">NOMBRE_MARCA</span><span>Quién lo fabrica (ej: COMEX, TRUPER, PEMEX, BIOPAPPEL)</span>
                    <span style="font-weight:700;color:var(--purple);">NOMBRE_MODELO</span><span>Nombre o numero de modelo del producto (ej: IPHONE 15, GALAXY S24, COROLLA 2024, VIN-100, W22-3HP)</span>
                    <span style="font-weight:700;color:var(--purple);">NOMBRE_MEDIDA</span><span>Tamaño con números (ej: 19LT, 1HP, 48MMX150M)</span>
                    <span style="font-weight:700;color:var(--purple);">NOMBRE_ESPECIFICACION</span><span>Detalle adicional (ej: BLANCO MATE, CENTRIFUGA 127V)</span>
                    <span style="font-weight:700;color:var(--purple);">FAMILIA</span><span>Seleccionar del dropdown (ej: MATERIA PRIMA, MANTENIMIENTO)</span>
                    <span style="font-weight:700;color:var(--purple);">TIPO_PRODUCTO</span><span>MPI = Materia Prima, ME = Empaque, MN = Mantenimiento</span>
                    <span style="font-weight:700;color:var(--purple);">UNIDAD_MEDIDA</span><span>Solo KG, PZA o CAJA (dropdown)</span>
                    <span style="font-weight:700;color:var(--gray-muted);">PRECIO</span><span style="color:var(--gray-muted);">Opcional. Con $ y decimales (ej: $150.50)</span>
                    <span style="font-weight:700;color:var(--gray-muted);">CLAVE_SAT</span><span style="color:var(--gray-muted);">Opcional. Código SAT para facturación (ej: 10191509)</span>
                    <span style="font-weight:700;color:var(--amber);">LOTE</span><span>SI o NO. Obligatorio solo si TIPO_PRODUCTO = MPI</span>
                    <span style="font-weight:700;color:var(--amber);">PEDIMENTO</span><span>SI o NO. Obligatorio solo si TIPO_PRODUCTO = MPI</span>
                    <span style="font-weight:700;color:var(--gray-muted);">VOLTAJE</span><span style="color:var(--gray-muted);">Opcional. Seleccionar del dropdown (ej: 220V, 220/440V)</span>
                </div>
            </div>

            <div style="margin-top:14px;padding:10px;background:#fff;border-radius:6px;border:1px solid #e5e0ee;font-size:11px;line-height:2;">
                <strong style="color:var(--gray-text);display:block;margin-bottom:4px;">Colores de la tabla:</strong>
                <span style="color:var(--purple);font-weight:700;">● Morado</span> = Obligatorio (siempre llenar)<br>
                <span style="color:var(--gray-muted);font-weight:700;">● Gris</span> = Opcional (puedes dejarlo vacío)<br>
                <span style="color:var(--amber);font-weight:700;">● Amarillo</span> = Obligatorio SOLO si TIPO_PRODUCTO = MPI (Materia Prima)
            </div>

            <div style="margin-top:12px;padding:10px;background:#fff0f0;border:1px solid #fca5a5;border-radius:6px;font-size:11px;color:#991b1b;line-height:1.7;">
                <strong style="display:block;margin-bottom:4px;color:#7f1d1d;">REGLAS IMPORTANTES:</strong>
                • Todo en MAYUSCULAS, sin acentos ni caracteres especiales<br>
                • NOMBRE_TIPO debe tener MINIMO 2 palabras (PINTURA VINILICA, no solo PINTURA)<br>
                • NOMBRE_ESPECIFICACION no debe repetir datos de otros campos<br>
                • PRECIO debe llevar $ al inicio (ej: $150.50). Si no sabes, dejalo vacio<br>
                • No repetir productos que ya existen en el catalogo
            </div>

            <div style="margin-top:12px;padding:10px;background:#f0fdf4;border:1px solid #86efac;border-radius:6px;font-size:11px;color:#166534;">
                <strong>EJEMPLO CORRECTO:</strong><br>
                <code style="font-size:10px;background:#dcfce7;padding:2px 4px;border-radius:3px;">MPI0538 | PINTURA VINILICA | COMEX | VIN-100 | 19LT | BLANCO MATE INTERIOR | MATERIA PRIMA | MPI | KG</code>
            </div>

            <div style="margin-top:12px;padding:10px;background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;font-size:11px;color:#92400e;">
                <strong>⚠️ IMPORTANTE:</strong> La fila 2 del template es solo un EJEMPLO (en gris). Borra el contenido con SUPRIMIR (Delete). NO elimines la fila o pierdes los dropdowns. Revisa la hoja "Instrucciones" del Excel.
            </div>
        </div>
    </div>

    {{-- Upload --}}
    <div class="alta-card">
        <h3>Subir Excel</h3>
        <form method="POST" action="{{ route('admin.alta-producto.subir') }}" enctype="multipart/form-data" id="formUpload">
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
