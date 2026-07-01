@extends('layouts.admin')
@section('title', 'Alta Producto Mantenimiento')
@section('hero')
<div class="hero-band">
    <h1>Alta Producto Mantenimiento</h1>
    <p>Sube tu Excel para dar de alta productos de mantenimiento: Consumibles (CM), Baleros (BL), Cilindros Neumáticos (CIL) y Conexiones Neumáticas (CN).</p>
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
    .prefijos-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:var(--purple-subtle);border:1px solid var(--purple-mid);border-radius:6px;font-size:11px;font-weight:600;color:var(--purple)}
    .prefijos-grid{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
    @media(max-width:768px){.alta-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
<div class="alert-success">{!! nl2br(e(session('mensaje'))) !!}</div>
@endif
@if(session('error'))
<div class="alert-error" style="white-space:pre-line;">{!! session('error') !!}</div>
@endif
@if($errors->any())
<div class="alert-error">
    @foreach($errors->all() as $error)
        {{ $error }}<br>
    @endforeach
</div>
@endif

<div class="alta-grid">
    <div class="alta-card">
        <h3>Mantenimiento</h3>
        <div class="alta-steps">
            <div class="alta-step"><div class="alta-step-num">1</div><div class="alta-step-text"><strong>Descarga el template</strong>Excel para productos de Mantenimiento.</div></div>
            <div class="alta-step"><div class="alta-step-num">2</div><div class="alta-step-text"><strong>Llena tus productos</strong>Solo CODIGO, NOMBRE_TIPO, NOMBRE_MODELO y NOMBRE_MEDIDA son obligatorios.</div></div>
            <div class="alta-step"><div class="alta-step-num">3</div><div class="alta-step-text"><strong>Sube el Excel</strong>Se valida y da de alta automático.</div></div>
        </div>
        <div style="margin-top:20px;">
            <a href="{{ route('admin.alta-producto-mto.template') }}" class="btn-download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar Template Mantenimiento
            </a>
        </div>
    </div>
    <div class="alta-card">
        <h3>Subir Excel Mantenimiento</h3>
        <form method="POST" action="{{ route('admin.alta-producto-mto.subir') }}" enctype="multipart/form-data">
            @csrf
            <div class="upload-zone" onclick="document.getElementById('fileMto').click()">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <div style="font-size:14px;font-weight:600;color:var(--gray-text);margin-top:8px;">Arrastra tu Excel aquí o haz clic</div>
                <div style="font-size:12px;color:var(--gray-muted);margin-top:4px;">.xlsx, .xls, .csv · Max 5MB</div>
                <div id="fileNameMto" style="margin-top:8px;font-size:12px;color:var(--purple);font-weight:600;display:none;"></div>
            </div>
            <input type="file" name="excel" id="fileMto" accept=".xlsx,.xls,.csv" style="display:none;" onchange="showName(this,'fileNameMto','btnMto')">
            <button type="submit" class="btn-upload" id="btnMto" disabled>Subir y validar</button>
        </form>
        <h3 style="margin-top:24px;">Formato Mantenimiento</h3>
        <div style="background:#f8f5ff;border:1px solid #d4c4e8;border-radius:10px;padding:16px;font-size:12px;">
            <table class="format-table" style="margin-top:0;">
                <thead><tr><th>Columna</th><th>Ejemplo</th><th>Req.</th></tr></thead>
                <tbody>
                    <tr><td style="color:var(--purple);font-weight:700;">CODIGO</td><td>CM0001 / BL0045 / CIL0012 / CN0003</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_TIPO</td><td>BALERO RIGIDO</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">NOMBRE_MARCA</td><td style="color:var(--gray-muted);">SKF</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MODELO</td><td>6205-2RS</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MEDIDA</td><td>25X52X15MM</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">NOMBRE_ESPECIFICACION</td><td style="color:var(--gray-muted);">SELLADO</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">FAMILIA</td><td style="color:var(--gray-muted);">MANTENIMIENTO</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">TIPO_PRODUCTO</td><td style="color:var(--gray-muted);">CM / BL / CIL / CN</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">UNIDAD_MEDIDA</td><td style="color:var(--gray-muted);">PZA</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">PRECIO</td><td style="color:var(--gray-muted);">$150.00</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">LOTE</td><td style="color:var(--gray-muted);">SI / NO</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">PEDIMENTO</td><td style="color:var(--gray-muted);">SI / NO</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">VOLTAJE</td><td style="color:var(--gray-muted);">—</td><td class="opt">—</td></tr>
                </tbody>
            </table>
            <p style="margin-top:12px;font-size:11px;color:var(--gray-muted);">
                <strong>Nota:</strong> El prefijo del CODIGO determina el tipo de producto (CM, BL, CIL, CN). Las columnas opcionales aparecen en color lila en el template.
            </p>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script>
function showName(input, labelId, btnId) {
    const name = input.files[0]?.name;
    if (name) {
        document.getElementById(labelId).textContent = name;
        document.getElementById(labelId).style.display = 'block';
        document.getElementById(btnId).disabled = false;
    }
}
</script>
@endpush
