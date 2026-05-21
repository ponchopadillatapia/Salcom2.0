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
    @media(max-width:768px){.alta-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
<div class="alert-success">{!! nl2br(e(session('mensaje'))) !!}</div>
@endif
@if(session('error'))
<div class="alert-error" style="white-space:pre-line;">
    {{ session('error') }}
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
            <h4>Reglas para llenar el Excel</h4>
            <div style="background:var(--purple-subtle);border:1px solid var(--purple-mid);border-radius:8px;padding:14px;margin-bottom:12px;">
                <strong style="font-size:13px;color:var(--purple);">ORDEN OBLIGATORIO DEL NOMBRE (mínimo 5 palabras):</strong>
                <div style="display:grid;grid-template-columns:auto 1fr;gap:4px 12px;margin-top:8px;font-size:12px;">
                    <span style="font-weight:700;color:var(--purple);">1. TIPO</span><span style="color:var(--gray-text);">¿Qué es? → MOTOR, RESINA, CAJA, PIGMENTO</span>
                    <span style="font-weight:700;color:var(--purple);">2. MARCA</span><span style="color:var(--gray-text);">¿Quién lo hace? → WEG, SKF, 3M, ALPHA</span>
                    <span style="font-weight:700;color:var(--purple);">3. MODELO</span><span style="color:var(--gray-text);">Referencia → W22, IND-500, ORG-R180</span>
                    <span style="font-weight:700;color:var(--purple);">4. MEDIDA</span><span style="color:var(--gray-text);">Tamaño → 3HP, 500ML, 40X30X25</span>
                    <span style="font-weight:700;color:var(--purple);">5. ESPECIFICACIÓN</span><span style="color:var(--gray-text);">Detalle → TRIFASICO, TRANSPARENTE</span>
                </div>
                <div style="font-size:11px;color:var(--green);margin-top:8px;font-weight:600;">
                    ✓ MOTOR ELECTRICO WEG W22 3HP 220/440V TRIFASICO
                </div>
                <div style="font-size:11px;color:var(--red);font-weight:600;">
                    ✗ WEG MOTOR 3HP (orden incorrecto — TIPO va primero)
                </div>
            </div>
            <ul>
                <li>Todo en MAYÚSCULAS, sin acentos ni símbolos raros</li>
                <li>PRODUCCION y TIPO_PRODUCTO: usar dropdown (MPI = Materia Prima Importación, ME = Material Empaque, MN = Mantenimiento)</li>
                <li>Unidades: solo KG, PZA o CAJA (seleccionar del dropdown)</li>
                <li>Precio con punto decimal (ej: 150.50) — <strong>opcional</strong></li>
                <li>OBSERVACIONES siempre obligatorio</li>
                <li><strong style="color:var(--amber);">Si es MPI:</strong> LOTE (SI/NO) y PEDIMENTO (SI/NO) son obligatorios</li>
                <li>Voltaje solo valores reales: 220V, 110/220V, 3HP</li>
                <li>No repetir productos que ya existen en el catálogo</li>
            </ul>
            <div style="background:#fff;border:1px solid var(--border-light);border-radius:6px;padding:10px;margin-top:10px;font-size:11px;">
                <strong style="color:var(--gray-text);">Colores del header en el Excel:</strong><br>
                <span style="display:inline-block;width:12px;height:12px;background:#6B3FA0;border-radius:3px;vertical-align:middle;margin-right:4px;"></span> <strong>Morado oscuro</strong> = Obligatorio<br>
                <span style="display:inline-block;width:12px;height:12px;background:#9B7BC7;border-radius:3px;vertical-align:middle;margin-right:4px;"></span> <strong>Morado claro</strong> = Opcional
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
        <p style="font-size:12px;color:var(--gray-muted);margin-bottom:8px;">El template tiene estas columnas. <strong style="color:var(--purple);">Morado</strong> = obligatorio, <span style="color:var(--gray-muted);">gris</span> = opcional:</p>
        <table class="format-table">
            <thead><tr><th>Columna</th><th>Ejemplo</th><th>Req.</th></tr></thead>
            <tbody>
                <tr><td>CODIGO</td><td>NAEIN-02</td><td class="req">✓</td></tr>
                <tr><td>NOMBRE</td><td>INSECTICIDA MT XTERM BIO 180G C/12</td><td class="req">✓</td></tr>
                <tr><td>PRODUCCION</td><td>MPI / ME / MN</td><td class="req">✓</td></tr>
                <tr><td>FAMILIA</td><td>AEROSOLES</td><td class="req">✓</td></tr>
                <tr><td>TIPO_PRODUCTO</td><td>MPI</td><td class="req">✓</td></tr>
                <tr><td>SUBFAMILIA</td><td>INSECTICIDA METERED</td><td class="opt">—</td></tr>
                <tr><td>UNIDAD_MEDIDA</td><td>KG / PZA / CAJA</td><td class="req">✓</td></tr>
                <tr><td>PRECIO</td><td>150.50</td><td class="opt">—</td></tr>
                <tr><td>CLAVE_SAT</td><td>10191509</td><td class="opt">—</td></tr>
                <tr><td>LOTE</td><td>SI / NO</td><td style="color:var(--amber);font-weight:700;">✓ si MPI</td></tr>
                <tr><td>PEDIMENTO</td><td>SI / NO</td><td style="color:var(--amber);font-weight:700;">✓ si MPI</td></tr>
                <tr><td>MARCA</td><td>WEG</td><td class="opt">—</td></tr>
                <tr><td>MODELO</td><td>W22</td><td class="opt">—</td></tr>
                <tr><td>MEDIDA</td><td>500ML</td><td class="opt">—</td></tr>
                <tr><td>MATERIAL</td><td>ACERO</td><td class="opt">—</td></tr>
                <tr><td>COLOR</td><td>ROJO</td><td class="opt">—</td></tr>
                <tr><td>VOLTAJE</td><td>220/440V</td><td class="opt">—</td></tr>
                <tr><td>ESPECIFICACIONES</td><td>TRIFASICO C40</td><td class="opt">—</td></tr>
                <tr><td>OBSERVACIONES</td><td>PEDIMENTO REQUERIDO</td><td class="req">✓</td></tr>
            </tbody>
        </table>
        <div style="margin-top:10px;font-size:11px;color:var(--gray-muted);line-height:1.6;">
            <strong style="color:var(--amber);">Nota:</strong> LOTE y PEDIMENTO son obligatorios solo si PRODUCCION = <strong>MPI</strong> (Materia Prima Importación). El Excel marca con ✓ las columnas requeridas.
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
