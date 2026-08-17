@extends('layouts.proveedor')

@section('title', 'Alta Facturas')

@section('hero')
<div class="hero-band">
    <h1>Alta Facturas</h1>
    <p>PDF + XML · primero valida, luego sube · OC opcional</p>
</div>
@endsection

@push('styles')
<style>
    .id-card {
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
    }
    .id-card h3 {
        font-size: 15px;
        font-weight: 700;
        color: var(--gray-text);
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .id-card .card-desc {
        font-size: 13px;
        color: var(--gray-muted);
        margin-bottom: 20px;
    }

    .section-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--purple);
        text-transform: uppercase;
        letter-spacing: .4px;
        margin: 4px 0 14px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--border-light);
    }

    .form-row { display: grid; gap: 14px; margin-bottom: 16px; }
    .form-row.cols-2 { grid-template-columns: 1fr 1fr; }
    .form-row.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
    }
    .form-group label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
    .form-group label .req { color: var(--red); }
    .form-group input[type="text"] {
        border: 1.5px solid var(--border);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        font-family: inherit;
        color: var(--gray-text);
        outline: none;
        background: var(--white);
        transition: var(--transition);
        width: 100%;
        box-sizing: border-box;
    }
    .form-group input[type="text"]:focus {
        border-color: var(--purple);
        box-shadow: 0 0 0 3px rgba(107, 63, 160, .1);
    }

    /* Dropzones */
    .dropzone {
        position: relative;
        border: 1.5px dashed var(--border);
        border-radius: 12px;
        background: var(--gray-soft);
        padding: 18px 12px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        min-height: 118px;
        height: 100%;
        width: 100%;
        box-sizing: border-box;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    .dropzone:hover,
    .dropzone.dragover {
        border-color: var(--purple);
        background: var(--purple-subtle);
    }
    .dropzone.has-file {
        border-style: solid;
        border-color: var(--purple-mid);
        background: var(--purple-light);
    }
    .dropzone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .dz-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: var(--white);
        display: flex; align-items: center; justify-content: center;
        box-shadow: var(--shadow-sm);
        margin-bottom: 2px;
        flex-shrink: 0;
    }
    .dz-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--gray-text);
        flex-shrink: 0;
    }
    .dz-sub {
        font-size: 11px;
        color: var(--gray-muted);
        flex-shrink: 0;
    }
    .dz-file {
        font-size: 11px;
        font-weight: 600;
        color: var(--purple);
        margin-top: 2px;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding: 0 4px;
        box-sizing: border-box;
    }

    /* Toggle fletera */
    .toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 16px;
        background: var(--gray-soft);
        border-radius: 12px;
        margin-bottom: 18px;
    }
    .toggle-info { min-width: 0; }
    .toggle-info .ti-title { font-size: 13px; font-weight: 700; color: var(--gray-text); }
    .toggle-info .ti-sub { font-size: 11px; color: var(--gray-muted); margin-top: 2px; }
    .seg {
        display: inline-flex;
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: 10px;
        padding: 3px;
        flex-shrink: 0;
    }
    .seg label {
        position: relative;
        cursor: pointer;
        margin: 0;
    }
    .seg input { position: absolute; opacity: 0; pointer-events: none; }
    .seg span {
        display: block;
        padding: 8px 18px;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-muted);
        border-radius: 8px;
        transition: var(--transition);
        user-select: none;
    }
    .seg label:has(input:checked) span {
        background: var(--purple);
        color: #fff;
        box-shadow: var(--shadow-sm);
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 4px;
        padding-top: 16px;
        border-top: 1px solid var(--border-light);
    }
    .btn-submit {
        padding: 10px 24px;
        background: var(--purple);
        color: #fff;
        border: none;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: var(--transition);
    }
    .btn-submit:hover { background: var(--purple-dark); transform: translateY(-1px); }
    .btn-submit:disabled { opacity: .45; cursor: not-allowed; transform: none; }
    .btn-outline {
        padding: 10px 24px;
        background: var(--white);
        color: var(--purple);
        border: 1.5px solid var(--purple);
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: var(--transition);
    }
    .btn-outline:hover { background: var(--purple-light); }
    .btn-outline:disabled { opacity: .45; cursor: not-allowed; }
    .oc-wrap { display: none; }
    .oc-wrap.is-visible { display: flex; flex-direction: column; gap: 6px; }
    .step-hint {
        font-size: 12px;
        color: var(--gray-muted);
        margin-right: auto;
        align-self: center;
    }
    .wizard-steps {
        display: flex;
        gap: 8px;
        margin-bottom: 18px;
    }
    .wizard-pill {
        flex: 1;
        text-align: center;
        padding: 8px 10px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-muted);
        background: var(--gray-soft);
        border: 1px solid var(--border-light);
    }
    .wizard-pill.active {
        color: var(--purple);
        background: var(--purple-light);
        border-color: var(--purple-mid);
    }
    .wizard-pill.done {
        color: #059669;
        background: var(--green-bg);
        border-color: #bbf7d0;
    }
    .wizard-panel { display: none; }
    .wizard-panel.active { display: block; }
    .plazo-box {
        margin: 4px 0 18px;
        padding: 16px;
        border: 1.5px solid var(--purple-mid);
        border-radius: 12px;
        background: var(--purple-light);
    }
    .plazo-box .section-label {
        border-bottom-color: rgba(107, 63, 160, .2);
        margin-bottom: 8px;
    }
    .plazo-box .card-desc { margin-bottom: 12px; }
    .choice-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    .choice-grid label {
        position: relative;
        cursor: pointer;
        margin: 0;
    }
    .choice-grid input { position: absolute; opacity: 0; pointer-events: none; }
    .choice-card {
        display: block;
        padding: 14px 12px;
        border: 1.5px solid var(--border);
        border-radius: 12px;
        background: var(--white);
        text-align: center;
        transition: var(--transition);
    }
    .choice-card .cc-title { font-size: 13px; font-weight: 700; color: var(--gray-text); }
    .choice-card .cc-sub { font-size: 11px; color: var(--gray-muted); margin-top: 4px; }
    .choice-grid label:has(input:checked) .choice-card {
        border-color: var(--purple);
        background: var(--purple-light);
        box-shadow: 0 0 0 3px rgba(107, 63, 160, .1);
    }
    .summary-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }
    .summary-chip {
        font-size: 11px;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 999px;
        background: var(--purple-light);
        color: var(--purple);
    }
    @media (max-width: 900px) {
        .choice-grid { grid-template-columns: 1fr; }
    }

    .result-box {
        margin-top: 16px;
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid transparent;
    }
    .result-box.ok { background: var(--green-bg); border-color: #bbf7d0; }
    .result-box.warn { background: #fffbeb; border-color: #fde68a; }
    .result-box.fail { background: var(--red-bg); border-color: #fecaca; }
    .result-title { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
    .result-box.ok .result-title { color: #059669; }
    .result-box.warn .result-title { color: #b45309; }
    .result-box.fail .result-title { color: #dc2626; }
    .result-msg { font-size: 12px; color: var(--gray-text); margin-bottom: 10px; }

    .checklist {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }
    .check-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--gray-text);
        background: rgba(255,255,255,.7);
        border-radius: 8px;
        padding: 7px 10px;
    }
    .check-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .check-dot.ok { background: #34c759; }
    .check-dot.fail { background: #ff3b30; }

    .error-list, .warn-list {
        margin: 8px 0 0;
        padding-left: 18px;
        font-size: 12px;
    }
    .error-list { color: #991b1b; }
    .warn-list { color: #92400e; }
    .error-list li, .warn-list li { margin-bottom: 3px; }

    .datos-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-top: 10px;
    }
    .dato-chip {
        background: rgba(255,255,255,.75);
        border-radius: 8px;
        padding: 8px 10px;
    }
    .dato-chip .dl {
        font-size: 10px;
        color: var(--gray-muted);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .dato-chip .dv {
        font-size: 12px;
        font-weight: 600;
        color: var(--gray-text);
        margin-top: 2px;
        word-break: break-all;
    }

    .card {
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 16px;
        box-shadow: var(--shadow-sm);
    }
    .card-head {
        padding: 14px 20px;
        border-bottom: 1px solid var(--border-light);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .card-head h3 { font-size: 14px; font-weight: 700; color: var(--gray-text); }

    .tabla { width: 100%; border-collapse: collapse; }
    .tabla th {
        font-size: 11px; font-weight: 700; color: var(--gray-muted);
        text-transform: uppercase; letter-spacing: .4px;
        padding: 10px 16px; text-align: left;
        background: var(--gray-soft);
        border-bottom: 1px solid var(--border-light);
    }
    .tabla td {
        padding: 12px 16px; font-size: 13px; color: var(--gray-text);
        border-bottom: 1px solid var(--border-light);
    }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: var(--gray-soft); }

    .badge {
        display: inline-block;
        font-size: 11px; font-weight: 600;
        padding: 3px 10px; border-radius: 999px;
    }
    .badge-amber { background: var(--amber-bg); color: var(--amber); }
    .badge-green { background: var(--green-bg); color: var(--green); }
    .badge-red { background: var(--red-bg); color: var(--red); }
    .badge-blue { background: var(--blue-bg); color: var(--blue); }
    .badge-purple { background: var(--purple-light); color: var(--purple); }
    .dias-count { font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.2; }
    .dias-count.warn { color: var(--amber); }
    .dias-count.late { color: var(--red); }
    .dias-sub { font-size: 10px; color: var(--gray-muted); margin-top: 2px; }

    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: var(--gray-muted);
        font-size: 13px;
    }

    .periodo-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        margin-bottom: 16px;
        border-radius: 12px;
        border: 1px solid #fde68a;
        background: #fffbeb;
    }
    .periodo-banner .pb-title {
        font-size: 13px;
        font-weight: 700;
        color: #b45309;
        margin-bottom: 2px;
    }
    .periodo-banner .pb-msg {
        font-size: 12px;
        color: #92400e;
        line-height: 1.45;
    }

    @media (max-width: 900px) {
        .form-row.cols-2, .form-row.cols-3, .checklist, .datos-grid { grid-template-columns: 1fr; }
        .toggle-row { flex-direction: column; align-items: stretch; }
        .seg { width: 100%; }
        .seg label { flex: 1; }
        .seg span { text-align: center; }
    }
</style>
@endpush

@section('content')

@php $res = session('fiscal_resultado'); @endphp

@if($errors->any())
<div class="id-card" id="fiscalFeedback" style="border-color:#fecaca;background:#fef2f2;padding:16px 20px;">
    <p style="color:#dc2626;font-size:13px;font-weight:700;margin:0 0 6px;">Corrige lo siguiente</p>
    <ul class="error-list" style="margin:0;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@php
    $estatusRes = $res['estatus'] ?? null;
    if (!empty($res['registrada'])) {
        $resTitulo = match ($estatusRes) {
            'aprobada_con_observaciones' => 'Factura registrada (con observaciones)',
            'rechazada' => 'Factura rechazada',
            default => 'Factura registrada',
        };
    } elseif (!empty($res['aprobado'])) {
        $resTitulo = match ($estatusRes) {
            'aprobada_con_observaciones' => 'Aprobada con observaciones',
            'aprobada' => 'Aprobada',
            default => 'Validación correcta',
        };
    } else {
        $resTitulo = 'Rechazada';
    }
@endphp

@if($res)
@php
    $boxClass = !empty($res['aprobado'])
        ? (($res['estatus'] ?? '') === 'aprobada_con_observaciones' ? 'warn' : 'ok')
        : 'fail';
@endphp
<div class="result-box {{ $boxClass }}" id="fiscalFeedback" style="margin-bottom:20px;margin-top:0;">
    <div class="result-title">{{ $resTitulo }}</div>
    <div class="result-msg">{{ $res['mensaje'] ?? '' }}</div>

    @if(!empty($res['checklist']))
        <div class="checklist">
            @foreach($res['checklist'] as $item)
                <div class="check-item">
                    <span class="check-dot {{ !empty($item['ok']) ? 'ok' : 'fail' }}"></span>
                    <span>{{ $item['label'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if(!empty($res['datos']['regimen_fiscal']) || !empty($res['datos']['uuid']))
        <div class="datos-grid">
            @if(!empty($res['datos']['regimen_fiscal']))
                <div class="dato-chip">
                    <div class="dl">Régimen</div>
                    <div class="dv">{{ $res['datos']['regimen_fiscal'] }}@if(!empty($res['datos']['regimen_nombre'])) — {{ $res['datos']['regimen_nombre'] }}@endif</div>
                </div>
            @endif
            @if(!empty($res['datos']['uuid']))
                <div class="dato-chip">
                    <div class="dl">UUID</div>
                    <div class="dv">{{ $res['datos']['uuid'] }}</div>
                </div>
            @endif
            <div class="dato-chip">
                <div class="dl">Total</div>
                <div class="dv">${{ number_format((float)($res['datos']['total'] ?? 0), 2) }}</div>
            </div>
            <div class="dato-chip">
                <div class="dl">Ret. IVA / ISR</div>
                <div class="dv">${{ number_format((float)($res['datos']['retencion_iva'] ?? 0), 2) }} / ${{ number_format((float)($res['datos']['retencion_isr'] ?? 0), 2) }}</div>
            </div>
            <div class="dato-chip">
                <div class="dl">Fletera</div>
                <div class="dv">{{ !empty($res['datos']['es_fletera']) ? 'Sí' : 'No' }}</div>
            </div>
            @if(!empty($res['datos']['forma_pago']))
                <div class="dato-chip">
                    <div class="dl">Forma de pago</div>
                    <div class="dv">{{ $res['datos']['forma_pago'] }}{{ !empty(config('facturas.formas_pago')[$res['datos']['forma_pago']]) ? ' — '.str_replace($res['datos']['forma_pago'].' — ', '', config('facturas.formas_pago')[$res['datos']['forma_pago']]) : '' }}</div>
                </div>
            @endif
            @if(!empty($res['datos']['metodo_pago']))
                <div class="dato-chip">
                    <div class="dl">Método de pago</div>
                    <div class="dv">{{ $res['datos']['metodo_pago'] }}</div>
                </div>
            @endif
            @if(!empty($res['datos']['uso_cfdi']))
                <div class="dato-chip">
                    <div class="dl">Uso CFDI</div>
                    <div class="dv">{{ $res['datos']['uso_cfdi'] }}</div>
                </div>
            @endif
            @if(!empty($res['datos']['producto']))
                <div class="dato-chip">
                    <div class="dl">Concepto</div>
                    <div class="dv">{{ $res['datos']['producto'] }}</div>
                </div>
            @endif
            @if(!empty($res['datos']['dias_plazo']))
                <div class="dato-chip">
                    <div class="dl">Plazo</div>
                    <div class="dv">{{ $res['datos']['dias_plazo'] }} días</div>
                </div>
            @endif
            @if(!empty($res['datos']['rfc_emisor']))
                <div class="dato-chip">
                    <div class="dl">RFC emisor</div>
                    <div class="dv">{{ $res['datos']['rfc_emisor'] }}</div>
                </div>
            @endif
        </div>
    @endif

    @if(!empty($res['errores']))
        <ul class="error-list">
            @foreach($res['errores'] as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    @endif
    @if(!empty($res['advertencias']))
        <ul class="warn-list">
            @foreach($res['advertencias'] as $w)<li>{{ $w }}</li>@endforeach
        </ul>
    @endif
</div>
@endif

<form method="POST" action="{{ route('proveedores.fiscal.validar') }}" enctype="multipart/form-data" id="formFiscal">
    @csrf
    <input type="hidden" name="es_fletera" value="0">

    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            Alta de factura
        </h3>
        <p class="card-desc">Adjunta PDF + XML (OC opcional). Primero valida; si queda aprobada, elige el plazo y pulsa Subir.</p>

        <div class="periodo-banner">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>
                <div class="pb-title">Solo facturas de {{ $mesEnCurso ?? now()->locale('es')->translatedFormat('F Y') }}</div>
                <div class="pb-msg">La fecha de emisión del CFDI debe ser del mes en curso. Si el archivo es de otro mes o ese periodo ya cerró, no se podrá validar ni subir.</div>
            </div>
        </div>

        <div class="section-label">Archivos</div>

        <div class="form-row cols-3" id="archivosRow">
            <div class="form-group">
                <label>Factura PDF <span class="req">*</span></label>
                <div class="dropzone {{ !empty($tieneArchivosPendientes) ? 'has-file' : '' }}" data-dz="archivo">
                    <input type="file" name="archivo" id="archivo" accept=".pdf,application/pdf" {{ !empty($tieneArchivosPendientes) ? '' : 'required' }}>
                    <div class="dz-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div class="dz-title">PDF</div>
                    <div class="dz-sub">{{ !empty($tieneArchivosPendientes) ? 'En servidor — opcional reemplazar' : 'Arrastra o haz clic' }}</div>
                    <div class="dz-file" {{ !empty($tieneArchivosPendientes) ? '' : 'hidden' }}>{{ $pendiente['nombre_pdf'] ?? '' }}</div>
                </div>
            </div>
            <div class="form-group">
                <label>XML CFDI <span class="req">*</span></label>
                <div class="dropzone {{ !empty($tieneArchivosPendientes) ? 'has-file' : '' }}" data-dz="archivo_xml">
                    <input type="file" name="archivo_xml" id="archivo_xml" accept=".xml,text/xml,application/xml" {{ !empty($tieneArchivosPendientes) ? '' : 'required' }}>
                    <div class="dz-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    </div>
                    <div class="dz-title">XML</div>
                    <div class="dz-sub">{{ !empty($tieneArchivosPendientes) ? 'En servidor — opcional reemplazar' : 'Arrastra o haz clic' }}</div>
                    <div class="dz-file" {{ !empty($tieneArchivosPendientes) ? '' : 'hidden' }}>{{ $pendiente['nombre_xml'] ?? '' }}</div>
                </div>
            </div>
            <div class="form-group">
                <label>Orden de compra <span style="color:var(--gray-muted);font-weight:500;">(opcional)</span></label>
                <div class="dropzone {{ !empty($pendiente['nombre_oc'] ?? null) ? 'has-file' : '' }}" data-dz="archivo_oc">
                    <input type="file" name="archivo_oc" id="archivo_oc" accept=".pdf,application/pdf">
                    <div class="dz-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gray-muted)" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <div class="dz-title">OC</div>
                    <div class="dz-sub">{{ !empty($pendiente['nombre_oc'] ?? null) ? 'En servidor — opcional reemplazar' : 'Opcional' }}</div>
                    <div class="dz-file" {{ !empty($pendiente['nombre_oc'] ?? null) ? '' : 'hidden' }}>{{ $pendiente['nombre_oc'] ?? '' }}</div>
                </div>
            </div>
        </div>

        @if(!empty($puedeSubir))
        @php $plazosDias = config('facturas.plazos_dias', [60, 120, 320]); @endphp
        <div class="plazo-box" id="plazoBox">
            <div class="section-label">Plazo de pago</div>
            <p class="card-desc">La factura quedó aprobada. Elige los días de crédito antes de subir.</p>
            <div class="choice-grid">
                @foreach($plazosDias as $dias)
                <label>
                    <input type="radio" name="dias_plazo" value="{{ $dias }}" {{ (string) old('dias_plazo') === (string) $dias ? 'checked' : '' }}>
                    <span class="choice-card">
                        <span class="cc-title">{{ $dias }} días</span>
                        <span class="cc-sub">Vence {{ now()->addDays($dias)->format('d/m/Y') }}</span>
                    </span>
                </label>
                @endforeach
            </div>
        </div>
        @endif

        <div class="form-actions">
            <span class="step-hint" id="stepHint">
                @if(!empty($puedeSubir))
                    Validación OK — elige 60, 120 o 320 días y pulsa Subir.
                @elseif(!empty($tieneArchivosPendientes))
                    Archivos listos — pulsa Validar de nuevo o reemplázalos.
                @else
                    1) Validar · 2) Subir si todo está correcto
                @endif
            </span>
            <button type="submit" class="btn-outline" id="btnValidar" formaction="{{ route('proveedores.fiscal.validar') }}">Validar</button>
            <button type="submit" class="btn-submit" id="btnSubir" formaction="{{ route('proveedores.fiscal.subir') }}" formnovalidate {{ !empty($puedeSubir) ? '' : 'disabled' }}>Subir</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-head">
        <h3>Facturas recientes</h3>
        <span style="font-size:12px;color:var(--gray-muted);">{{ $facturas->count() }} subidas</span>
    </div>
    @if($facturas->isEmpty())
        <div class="empty-state">Aún no has subido facturas.</div>
    @else
        <div style="overflow-x:auto;">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Folio / UUID</th>
                        <th>Régimen</th>
                        <th>Fletera</th>
                        <th>Retenciones</th>
                        <th>Total</th>
                        <th>Plazo</th>
                        <th>Estatus</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($facturas as $f)
                        <tr>
                            <td>
                                <div style="font-weight:600;">{{ $f->folio_cfdi }}</div>
                                @if($f->uuid_cfdi)
                                    <div style="font-size:11px;color:var(--gray-muted);">{{ \Illuminate\Support\Str::limit($f->uuid_cfdi, 22) }}</div>
                                @endif
                            </td>
                            <td>{{ $f->regimen_fiscal ?: '—' }}</td>
                            <td>
                                @if($f->es_fletera)
                                    <span class="badge badge-blue">Sí</span>
                                @else
                                    <span class="badge badge-purple">No</span>
                                @endif
                            </td>
                            <td style="font-size:12px;">
                                IVA ${{ number_format((float) $f->retencion_iva, 2) }} ·
                                ISR ${{ number_format((float) $f->retencion_isr, 2) }}
                            </td>
                            <td style="font-weight:600;">${{ number_format((float) $f->total, 2) }}</td>
                            <td>
                                @php $restantes = $f->diasRestantes(); @endphp
                                @if($restantes === null)
                                    —
                                @else
                                    <div class="dias-count {{ $restantes < 0 ? 'late' : ($restantes <= 15 ? 'warn' : '') }}">
                                        @if($restantes > 0)
                                            {{ $restantes }} días
                                        @elseif($restantes === 0)
                                            Vence hoy
                                        @else
                                            Vencida ({{ abs($restantes) }})
                                        @endif
                                    </div>
                                    @if($f->dias_plazo)
                                        <div class="dias-sub">de {{ $f->dias_plazo }}</div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @php
                                    $badge = match ($f->estatus) {
                                        'pendiente' => 'badge-amber',
                                        'pagada', 'aprobada' => 'badge-green',
                                        'rechazada', 'cancelada' => 'badge-red',
                                        default => 'badge-purple',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ ucfirst($f->estatus) }}</span>
                            </td>
                            <td style="font-size:12px;color:var(--gray-muted);">{{ $f->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('formFiscal');
    var btnValidar = document.getElementById('btnValidar');
    var btnSubir = document.getElementById('btnSubir');
    var stepHint = document.getElementById('stepHint');
    var puedeSubir = {{ !empty($puedeSubir) ? 'true' : 'false' }};
    var tieneArchivosPendientes = {{ !empty($tieneArchivosPendientes) ? 'true' : 'false' }};

    function plazoSeleccionado() {
        return !!(form && form.querySelector('input[name="dias_plazo"]:checked'));
    }

    function syncSubir() {
        if (!btnSubir) return;
        btnSubir.disabled = !(puedeSubir && plazoSeleccionado());
    }

    function invalidatePending() {
        if (!puedeSubir || !btnSubir) return;
        puedeSubir = false;
        btnSubir.disabled = true;
        var plazoBox = document.getElementById('plazoBox');
        if (plazoBox) plazoBox.style.display = 'none';
        if (stepHint) stepHint.textContent = 'Cambiaste archivos — vuelve a validar antes de subir.';
    }

    if (form) {
        form.querySelectorAll('input[name="dias_plazo"]').forEach(function (radio) {
            radio.addEventListener('change', syncSubir);
        });
    }
    syncSubir();

    function assignFiles(input, file) {
        if (!input || !file) return false;
        try {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            return input.files && input.files.length > 0;
        } catch (err) {
            try {
                input.files = eFilesFallback(file);
                return !!(input.files && input.files.length);
            } catch (err2) {
                return false;
            }
        }
    }

    function eFilesFallback(file) {
        var dt = new DataTransfer();
        dt.items.add(file);
        return dt.files;
    }

    document.querySelectorAll('.dropzone').forEach(function (dz) {
        var input = dz.querySelector('input[type="file"]');
        var nameEl = dz.querySelector('.dz-file');
        var subEl = dz.querySelector('.dz-sub');

        function showFile(file) {
            if (!file) return;
            dz.classList.add('has-file');
            nameEl.hidden = false;
            nameEl.textContent = file.name;
            nameEl.title = file.name;
            if (subEl) {
                if (file.size < 1) {
                    subEl.textContent = 'Vacío (0 B) — no válido';
                    subEl.style.color = '#dc2626';
                } else {
                    subEl.style.color = '';
                    var kb = file.size / 1024;
                    subEl.textContent = kb < 1
                        ? Math.max(file.size, 0) + ' B'
                        : (kb < 10 ? kb.toFixed(1) : kb.toFixed(0)) + ' KB';
                }
            }
            invalidatePending();
        }

        input.addEventListener('change', function () {
            if (input.files && input.files[0]) showFile(input.files[0]);
        });

        ['dragenter', 'dragover'].forEach(function (ev) {
            dz.addEventListener(ev, function (e) {
                e.preventDefault();
                dz.classList.add('dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            dz.addEventListener(ev, function (e) {
                e.preventDefault();
                dz.classList.remove('dragover');
            });
        });
        dz.addEventListener('drop', function (e) {
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                var file = e.dataTransfer.files[0];
                if (!assignFiles(input, file)) {
                    alert('No se pudo adjuntar el archivo arrastrado. Usa clic para seleccionarlo.');
                    return;
                }
                showFile(file);
            }
        });
    });

    if (form && btnValidar) {
        form.addEventListener('submit', function (e) {
            var submitter = e.submitter || document.activeElement;
            if (submitter === btnSubir) {
                if (!puedeSubir || !plazoSeleccionado()) {
                    e.preventDefault();
                    if (puedeSubir && !plazoSeleccionado()) {
                        alert('Selecciona el plazo de días (60, 120 o 320) antes de subir.');
                    }
                    return;
                }
                btnSubir.textContent = 'Subiendo…';
                setTimeout(function () { btnSubir.disabled = true; }, 0);
                return;
            }
            var pdf = document.getElementById('archivo');
            var xml = document.getElementById('archivo_xml');
            if (!tieneArchivosPendientes) {
                if (pdf && !pdf.files.length) {
                    e.preventDefault();
                    alert('Adjunta el PDF de la factura.');
                    return;
                }
                if (xml && !xml.files.length) {
                    e.preventDefault();
                    alert('Adjunta el XML CFDI.');
                    return;
                }
            }
            btnValidar.textContent = 'Validando…';
            setTimeout(function () {
                btnValidar.disabled = true;
                if (btnSubir) btnSubir.disabled = true;
            }, 0);
        });
    }

    var feedback = document.getElementById('fiscalFeedback');
    var plazoBox = document.getElementById('plazoBox');
    if (plazoBox) {
        plazoBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (feedback) {
        feedback.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();
</script>
@endpush
