@extends('layouts.proveedor')

@section('title', 'Alta Facturas')

@section('hero')
<div class="hero-band">
    <h1>Alta Facturas</h1>
    <p>PDF + XML · validación de régimen, retenciones y fletera</p>
</div>
@endsection

@push('styles')
<style>
    .metrics-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    .metric-card {
        background: var(--white);
        border: 1px solid var(--border-light);
        border-radius: 12px;
        padding: 14px 16px;
        box-shadow: var(--shadow-sm);
    }
    .metric-label { font-size: 11px; color: var(--gray-muted); font-weight: 600; margin-bottom: 4px; }
    .metric-value { font-size: 22px; font-weight: 700; color: var(--gray-text); letter-spacing: -0.4px; line-height: 1.1; }

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
    .form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }

    .form-group { display: flex; flex-direction: column; gap: 6px; }
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
        padding: 22px 16px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        min-height: 110px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
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
        margin-bottom: 4px;
    }
    .dz-title { font-size: 13px; font-weight: 700; color: var(--gray-text); }
    .dz-sub { font-size: 11px; color: var(--gray-muted); }
    .dz-file {
        font-size: 12px;
        font-weight: 600;
        color: var(--purple);
        margin-top: 2px;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding: 0 8px;
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
    .btn-submit:disabled { opacity: .55; cursor: not-allowed; transform: none; }

    .result-box {
        margin-top: 16px;
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid transparent;
    }
    .result-box.ok { background: var(--green-bg); border-color: #bbf7d0; }
    .result-box.fail { background: var(--red-bg); border-color: #fecaca; }
    .result-title { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
    .result-box.ok .result-title { color: #059669; }
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

    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: var(--gray-muted);
        font-size: 13px;
    }

    @media (max-width: 900px) {
        .metrics-row { grid-template-columns: 1fr 1fr; }
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

<div class="metrics-row">
    <div class="metric-card">
        <div class="metric-label">Recientes</div>
        <div class="metric-value">{{ $stats['total'] ?? 0 }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Pendientes</div>
        <div class="metric-value" style="color:var(--amber)">{{ $stats['pendientes'] ?? 0 }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Rechazadas</div>
        <div class="metric-value" style="color:var(--red)">{{ $stats['rechazadas'] ?? 0 }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Fleteras</div>
        <div class="metric-value" style="color:var(--blue)">{{ $stats['fleteras'] ?? 0 }}</div>
    </div>
</div>

@if($errors->any())
<div class="id-card" style="border-color:#fecaca;background:#fef2f2;padding:16px 20px;">
    <p style="color:#dc2626;font-size:13px;font-weight:700;margin:0 0 6px;">Corrige lo siguiente</p>
    <ul class="error-list" style="margin:0;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($res)
<div class="result-box {{ $res['aprobado'] ? 'ok' : 'fail' }}" style="margin-bottom:20px;margin-top:0;">
    <div class="result-title">{{ $res['aprobado'] ? 'Factura registrada' : 'Factura rechazada' }}</div>
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

<form method="POST" action="{{ route('proveedores.fiscal.subir') }}" enctype="multipart/form-data" id="formFiscal">
    @csrf

    <div class="id-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            Subir factura
        </h3>
        <p class="card-desc">Adjunta el XML y el PDF. El sistema valida régimen y retenciones antes de registrar.</p>

        <div class="section-label">Archivos</div>

        <div class="form-row cols-3">
            <div class="form-group">
                <label>Factura PDF <span class="req">*</span></label>
                <div class="dropzone" data-dz="archivo">
                    <input type="file" name="archivo" id="archivo" accept=".pdf,application/pdf" required>
                    <div class="dz-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div class="dz-title">PDF</div>
                    <div class="dz-sub">Arrastra o haz clic</div>
                    <div class="dz-file" hidden></div>
                </div>
            </div>
            <div class="form-group">
                <label>XML CFDI <span class="req">*</span></label>
                <div class="dropzone" data-dz="archivo_xml">
                    <input type="file" name="archivo_xml" id="archivo_xml" accept=".xml,text/xml,application/xml" required>
                    <div class="dz-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    </div>
                    <div class="dz-title">XML</div>
                    <div class="dz-sub">Arrastra o haz clic</div>
                    <div class="dz-file" hidden></div>
                </div>
            </div>
            <div class="form-group">
                <label>Orden de compra</label>
                <div class="dropzone" data-dz="archivo_oc">
                    <input type="file" name="archivo_oc" id="archivo_oc" accept=".pdf,application/pdf">
                    <div class="dz-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gray-muted)" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <div class="dz-title">OC (opcional)</div>
                    <div class="dz-sub">PDF</div>
                    <div class="dz-file" hidden></div>
                </div>
            </div>
        </div>

        <div class="section-label">Datos</div>

        <div class="toggle-row">
            <div class="toggle-info">
                <div class="ti-title">¿Es fletera?</div>
                <div class="ti-sub" id="fleteraHint">Retenciones según el régimen del XML</div>
            </div>
            <div class="seg" id="fleteraSeg">
                <label>
                    <input type="radio" name="es_fletera" value="0" {{ old('es_fletera', '0') === '0' ? 'checked' : '' }} required>
                    <span>No</span>
                </label>
                <label>
                    <input type="radio" name="es_fletera" value="1" {{ old('es_fletera') === '1' ? 'checked' : '' }}>
                    <span>Sí</span>
                </label>
            </div>
        </div>

        <div class="form-group" style="margin-bottom:0;">
            <label>Notas</label>
            <input type="text" name="notas" value="{{ old('notas') }}" placeholder="Ej. Factura OC #10045" maxlength="500">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit" id="btnSubmit">Subir y validar</button>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-head">
        <h3>Facturas recientes</h3>
        <span style="font-size:12px;color:var(--gray-muted);">{{ $facturas->count() }} registros</span>
    </div>
    @if($facturas->isEmpty())
        <div class="empty-state">Aún no has dado de alta facturas.</div>
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
    document.querySelectorAll('.dropzone').forEach(function (dz) {
        var input = dz.querySelector('input[type="file"]');
        var nameEl = dz.querySelector('.dz-file');
        var titleEl = dz.querySelector('.dz-title');
        var subEl = dz.querySelector('.dz-sub');

        function showFile(file) {
            if (!file) return;
            dz.classList.add('has-file');
            nameEl.hidden = false;
            nameEl.textContent = file.name;
            if (subEl) subEl.textContent = (file.size / 1024).toFixed(0) + ' KB';
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
                input.files = e.dataTransfer.files;
                showFile(e.dataTransfer.files[0]);
            }
        });
    });

    var hint = document.getElementById('fleteraHint');
    function updateHint() {
        var checked = document.querySelector('input[name="es_fletera"]:checked');
        if (!hint || !checked) return;
        hint.textContent = checked.value === '1'
            ? 'Se validan retenciones IVA 4% + ISR 1.25%'
            : 'Retenciones según el régimen del XML';
    }
    document.querySelectorAll('input[name="es_fletera"]').forEach(function (r) {
        r.addEventListener('change', updateHint);
    });
    updateHint();

    var form = document.getElementById('formFiscal');
    var btn = document.getElementById('btnSubmit');
    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.textContent = 'Validando…';
        });
    }
})();
</script>
@endpush
