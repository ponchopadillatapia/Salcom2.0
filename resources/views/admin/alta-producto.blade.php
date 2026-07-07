@extends('layouts.admin')
@section('title', 'Alta Producto Compras')
@section('hero')
<div class="hero-band">
    <h1>Alta Producto Compras</h1>
    <p>Sube tu Excel para dar de alta productos. Selecciona Nacional (ME/MP) o Internacional (MPI).</p>
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
    .tabs-bar{display:flex;gap:0;margin-bottom:24px;border-bottom:2px solid var(--border-light)}
    .tab-btn{padding:12px 24px;font-size:14px;font-weight:600;color:var(--gray-muted);background:none;border:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;font-family:inherit}
    .tab-btn.active{color:var(--purple);border-bottom-color:var(--purple)}
    .tab-btn:hover{color:var(--purple)}
    .tab-content{display:none}
    .tab-content.active{display:block}
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

{{-- TABS --}}
<div class="tabs-bar">
    <button class="tab-btn {{ session('tab', 'nacional') === 'nacional' ? 'active' : '' }}" onclick="switchTab('nacional')">Compras Nacional (ME/MP)</button>
    <button class="tab-btn {{ session('tab') === 'internacional' ? 'active' : '' }}" onclick="switchTab('internacional')">Compras Internacional (MPI)</button>
</div>

{{-- ═══ TAB: NACIONAL (ME/MP) ═══ --}}
<div class="tab-content {{ session('tab', 'nacional') === 'nacional' ? 'active' : '' }}" id="tab-nacional">
<div class="alta-grid">
    <div class="alta-card">
        <h3>Nacional — ME / MP</h3>
        <div class="alta-steps">
            <div class="alta-step"><div class="alta-step-num">1</div><div class="alta-step-text"><strong>Descarga el template Nacional</strong>Excel para Material de Empaque (ME) y Materia Prima (MP).</div></div>
            <div class="alta-step"><div class="alta-step-num">2</div><div class="alta-step-text"><strong>Llena tus productos</strong>Elige PREFIJO, luego CONSECUTIVO del dropdown (solo disponibles). Filtro en celda B1.</div></div>
            <div class="alta-step"><div class="alta-step-num">3</div><div class="alta-step-text"><strong>Sube el Excel</strong>Se valida y da de alta automático.</div></div>
        </div>
        <div style="margin-top:20px;">
            <a href="{{ route('admin.alta-producto.template') }}" class="btn-download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar Template Nacional
            </a>
        </div>
    </div>
    <div class="alta-card">
        <h3>Subir Excel Nacional</h3>
        <form method="POST" action="{{ route('admin.alta-producto.subir') }}" enctype="multipart/form-data">
            @csrf
            <div class="upload-zone" onclick="document.getElementById('fileNac').click()">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <div style="font-size:14px;font-weight:600;color:var(--gray-text);margin-top:8px;">Arrastra tu Excel aquí o haz clic</div>
                <div style="font-size:12px;color:var(--gray-muted);margin-top:4px;">.xlsx, .xls, .csv · Max 5MB</div>
                <div id="fileNameNac" style="margin-top:8px;font-size:12px;color:var(--purple);font-weight:600;display:none;"></div>
            </div>
            <input type="file" name="excel" id="fileNac" accept=".xlsx,.xls,.csv" style="display:none;" onchange="showName(this,'fileNameNac','btnNac')">
            <button type="submit" class="btn-upload" id="btnNac" disabled>Subir y validar</button>
        </form>
        <h3 style="margin-top:24px;">Formato Nacional</h3>
        <div style="background:#f8f5ff;border:1px solid #d4c4e8;border-radius:10px;padding:16px;font-size:12px;">
            <table class="format-table" style="margin-top:0;">
                <thead><tr><th>Columna</th><th>Ejemplo</th><th>Req.</th></tr></thead>
                <tbody>
                    <tr><td style="color:var(--purple);font-weight:700;">PREFIJO</td><td>ME / MP (dropdown)</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">CONSECUTIVO</td><td>0305 (dropdown disponibles, filtro B1)</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_TIPO</td><td>CAJA CORRUGADA</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">NOMBRE_MARCA</td><td style="color:var(--gray-muted);">—</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">NOMBRE_MODELO</td><td style="color:var(--gray-muted);">—</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_MEDIDA</td><td>40X30X25CM</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">NOMBRE_ESPECIFICACION</td><td style="color:var(--gray-muted);">—</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">FAMILIA</td><td style="color:var(--gray-muted);">EMPAQUE</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">TIPO_PRODUCTO</td><td>ME / MP</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">PRECIO</td><td>$150.50</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">MOQ</td><td>100 (mínimo de compra)</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">UNIDAD_MEDIDA — VOLTAJE</td><td style="color:var(--gray-muted);">opcionales</td><td class="opt">—</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

{{-- ═══ TAB: INTERNACIONAL (MPI) ═══ --}}
<div class="tab-content {{ session('tab') === 'internacional' ? 'active' : '' }}" id="tab-internacional">
<div class="alta-grid">
    <div class="alta-card">
        <h3>Internacional — MPI</h3>
        <div class="alta-steps">
            <div class="alta-step"><div class="alta-step-num">1</div><div class="alta-step-text"><strong>Descarga el template MPI</strong>Excel exclusivo para Materia Prima de Importación.</div></div>
            <div class="alta-step"><div class="alta-step-num">2</div><div class="alta-step-text"><strong>Llena tus productos</strong>Código, nombre genérico, medida, familia, unidad, lote y pedimento obligatorios.</div></div>
            <div class="alta-step"><div class="alta-step-num">3</div><div class="alta-step-text"><strong>Sube el Excel</strong>Se valida y da de alta automático.</div></div>
        </div>
        <div style="margin-top:20px;">
            <a href="{{ route('admin.alta-producto.template-mpi') }}" class="btn-download">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Descargar Template MPI
            </a>
        </div>
    </div>
    <div class="alta-card">
        <h3>Subir Excel MPI</h3>
        <form method="POST" action="{{ route('admin.alta-producto.subir-mpi') }}" enctype="multipart/form-data">
            @csrf
            <div class="upload-zone" onclick="document.getElementById('fileInt').click()">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <div style="font-size:14px;font-weight:600;color:var(--gray-text);margin-top:8px;">Arrastra tu Excel aquí o haz clic</div>
                <div style="font-size:12px;color:var(--gray-muted);margin-top:4px;">.xlsx, .xls, .csv · Max 5MB</div>
                <div id="fileNameInt" style="margin-top:8px;font-size:12px;color:var(--purple);font-weight:600;display:none;"></div>
            </div>
            <input type="file" name="excel" id="fileInt" accept=".xlsx,.xls,.csv" style="display:none;" onchange="showName(this,'fileNameInt','btnInt')">
            <button type="submit" class="btn-upload" id="btnInt" disabled>Subir y validar</button>
        </form>
        <h3 style="margin-top:24px;">Formato MPI</h3>
        <div style="background:#f8f5ff;border:1px solid #d4c4e8;border-radius:10px;padding:16px;font-size:12px;">
            <table class="format-table" style="margin-top:0;">
                <thead><tr><th>Columna</th><th>Ejemplo</th><th>Req.</th></tr></thead>
                <tbody>
                    <tr><td style="color:var(--purple);font-weight:700;">PREFIJO</td><td>MPI / MPIVA / MPIDA (dropdown)</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">CONSECUTIVO</td><td>0601</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">NOMBRE_generico</td><td>FRAGANCIA LAVANDA</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">codigo_proveedor</td><td style="color:var(--gray-muted);">120205</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">NOMBRE_ESPECIFICACION_adicional</td><td style="color:var(--gray-muted);">tornillo para lampara</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">FAMILIA</td><td>MATERIA PRIMA</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">TIPO_PRODUCTO</td><td>MPI (pre-llenado)</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">UNIDAD_MEDIDA</td><td>KG</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--gray-muted);">PRECIO</td><td style="color:var(--gray-muted);">$18.00</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--gray-muted);">CLAVE_SAT</td><td style="color:var(--gray-muted);">—</td><td class="opt">—</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">LOTE</td><td>SI</td><td class="req">✓</td></tr>
                    <tr><td style="color:var(--purple);font-weight:700;">PEDIMENTO</td><td>SI</td><td class="req">✓</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

{{-- ═══ CARD: Migración Masiva ═══ --}}
<div style="background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px;margin-top:8px;display:flex;align-items:center;justify-content:space-between;gap:16px;box-shadow:0 2px 8px rgba(0,0,0,.04);">
    <div style="display:flex;align-items:center;gap:16px;">
        <div style="width:44px;height:44px;border-radius:12px;background:var(--purple-subtle);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 12 15 15"/></svg>
        </div>
        <div>
            <h3 style="font-size:15px;font-weight:700;color:var(--gray-text);margin:0 0 4px 0;">Migración</h3>
            <p style="font-size:13px;color:var(--gray-muted);margin:0;">Migra productos del sistema anterior.</p>
        </div>
    </div>
    <a href="{{ route('admin.migracion-masiva') }}" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--purple);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;transition:all .15s;">
        Ir a Migración
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
</div>

@endsection
@push('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
}
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
