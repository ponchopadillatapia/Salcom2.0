@extends('layouts.admin')

@section('title', 'Reembolsos')

@push('styles')
<style>
    .reembolsos-wrap { max-width: 920px; }
    .rb-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 24px; margin-bottom: 20px; box-shadow: var(--shadow-sm); }
    .rb-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .rb-card .card-desc { font-size: 13px; color: var(--gray-muted); margin-bottom: 20px; line-height: 1.5; }

    .rb-politicas { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 24px; }
    .rb-politica-box { background: var(--gray-soft); border-radius: 10px; padding: 14px 16px; border-left: 3px solid var(--purple); }
    .rb-politica-box h4 { font-size: 11px; font-weight: 700; color: var(--purple); text-transform: uppercase; letter-spacing: .4px; margin: 0 0 8px; }
    .rb-politica-box ul { margin: 0; padding: 0 0 0 16px; font-size: 12px; color: var(--gray-text); line-height: 1.7; }
    .rb-politica-box li { margin-bottom: 2px; }
    .rb-politica-box.alerta { border-left-color: #dc2626; background: #fef2f2; }
    .rb-politica-box.alerta h4 { color: #dc2626; }
    .rb-politica-box.info { border-left-color: #2563eb; background: #eff6ff; }
    .rb-politica-box.info h4 { color: #2563eb; }

    .rb-corte-banner { background: #fffbeb; border: 1.5px solid #f59e0b; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .rb-corte-banner svg { flex-shrink: 0; }
    .rb-corte-banner p { font-size: 12px; color: #92400e; margin: 0; line-height: 1.5; }
    .rb-corte-banner strong { color: #78350f; }

    .rb-form-row { display: grid; gap: 16px; margin-bottom: 16px; }
    .rb-form-row.cols-2 { grid-template-columns: 1fr 1fr; }
    .rb-form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
    .rb-form-row.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .rb-form-group { display: flex; flex-direction: column; gap: 6px; }
    .rb-form-group label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
    .rb-form-group select,
    .rb-form-group input[type="text"],
    .rb-form-group input[type="number"],
    .rb-form-group input[type="date"],
    .rb-form-group textarea {
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
    .rb-form-group select:focus,
    .rb-form-group input:focus,
    .rb-form-group textarea:focus {
        border-color: var(--purple);
        box-shadow: 0 0 0 3px rgba(107, 63, 160, .1);
    }
    .rb-form-group .hint { font-size: 11px; color: var(--gray-muted); margin-top: 2px; }

    .rb-upload-zone {
        border: 2px dashed var(--border);
        border-radius: 12px;
        padding: 28px 20px;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        background: var(--gray-soft);
    }
    .rb-upload-zone:hover, .rb-upload-zone.dragover {
        border-color: var(--purple);
        background: var(--purple-subtle);
    }
    .rb-upload-zone svg { margin-bottom: 8px; }
    .rb-upload-zone p { font-size: 13px; color: var(--gray-muted); margin: 0; }
    .rb-upload-zone .upload-hint { font-size: 11px; color: var(--gray-muted); margin-top: 6px; }
    .rb-upload-zone .file-name { font-size: 12px; font-weight: 600; color: var(--purple); margin-top: 8px; display: none; }

    .rb-uploads-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px; }

    .btn-enviar-reembolso { padding: 12px 28px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; transition: all .2s; }
    .btn-enviar-reembolso:hover { background: var(--purple-dark); transform: translateY(-1px); }

    .rb-historial-empty { text-align: center; padding: 32px; color: var(--gray-muted); font-size: 13px; }

    @media (max-width: 768px) {
        .rb-form-row.cols-2, .rb-form-row.cols-3, .rb-form-row.cols-4, .rb-politicas, .rb-uploads-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="reembolsos-wrap">

    @if(session('mensaje'))
    <div class="rb-card" style="border-color:#059669;background:#ecfdf5;margin-bottom:16px;">
        <p style="color:#059669;font-size:14px;font-weight:700;margin:0;">{{ session('mensaje') }}</p>
    </div>
    @endif
    @if(session('error'))
    <div class="rb-card" style="border-color:#DC2626;background:#FEF2F2;margin-bottom:16px;">
        <p style="color:#DC2626;font-size:13px;font-weight:600;margin:0;">{{ session('error') }}</p>
    </div>
    @endif
    @if($errors->any())
    <div class="rb-card" style="border-color:#DC2626;background:#FEF2F2;margin-bottom:16px;">
        <ul style="margin:0;padding:0 0 0 18px;color:#991B1B;font-size:12px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    {{-- Políticas de aprobación --}}
    <div class="rb-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Políticas de Reembolso
        </h3>

        <div class="rb-corte-banner">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <p><strong>Cierre semanal:</strong> La fecha límite para recibir facturas es el <strong>lunes antes de la 1:00 PM</strong>. Después de esa hora se procesan la siguiente semana.</p>
        </div>

        <div class="rb-politicas">
            <div class="rb-politica-box alerta">
                <h4>Criterios obligatorios</h4>
                <ul>
                    <li>Facturas de México requieren autorización de <strong>Fernando Nuñez y Eduardo Arias</strong></li>
                    <li><strong>Materialidad obligatoria:</strong> adjuntar evidencia (correo o foto)</li>
                    <li>Sin materialidad = <strong>rechazado automáticamente</strong></li>
                </ul>
            </div>
            <div class="rb-politica-box">
                <h4>Datos fiscales requeridos</h4>
                <ul>
                    <li>Razón social: <strong>Salcom</strong> o <strong>Franfoods</strong></li>
                    <li>Uso CFDI: <strong>G03</strong> (general) o <strong>I04</strong> (cómputo)</li>
                    <li>Forma de pago: <strong>28</strong> — Tarjeta de débito</li>
                    <li>Método de pago: <strong>PUE</strong></li>
                </ul>
            </div>
            <div class="rb-politica-box info">
                <h4>Viáticos y viajes</h4>
                <ul>
                    <li>Vendedores/promotores estiman costo y solicitan permiso</li>
                    <li>Aprobación: Alexander Cominu, Fernando y Eduardo</li>
                    <li>Se reembolsa a tarjeta del colaborador</li>
                </ul>
            </div>
            <div class="rb-politica-box">
                <h4>Viajes internacionales</h4>
                <ul>
                    <li><strong>No</strong> se requiere factura fiscal mexicana</li>
                    <li>Se aceptan tickets/comprobantes en moneda local</li>
                    <li>Adjuntar evidencia fotográfica del ticket</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Formulario --}}
    <div class="rb-card">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Registrar Reembolso
        </h3>
        <p class="card-desc">Completa los datos y sube la factura PDF firmada con su voucher y materialidad.</p>

        <form method="POST" action="{{ route('admin.reembolsos.enviar') }}" enctype="multipart/form-data" id="formReembolso">
            @csrf

            <div class="rb-form-row cols-3">
                <div class="rb-form-group">
                    <label for="categoria">Categoría <span style="color:#DC2626">*</span></label>
                    <select id="categoria" name="categoria" required>
                        <option value="" disabled selected>Selecciona</option>
                        <option value="gasto_general">Gasto general</option>
                        <option value="gasolina" {{ empty($tieneBitacoraGasolina) ? 'disabled' : '' }}>Gasolina {{ empty($tieneBitacoraGasolina) ? '(llena la bitácora primero)' : '' }}</option>
                        <option value="computo">Equipo de cómputo</option>
                        <option value="viaticos_nacional">Viáticos nacionales</option>
                        <option value="viaticos_internacional">Viáticos internacionales</option>
                    </select>
                    @if(empty($tieneBitacoraGasolina))
                    <span class="hint" style="color:#dc2626;">Para reembolso de gasolina, primero registra en la <a href="{{ route('admin.bitacora-gasolina') }}" style="color:var(--purple);font-weight:600;">Bitácora de Gasolina</a>.</span>
                    @endif
                </div>
                <div class="rb-form-group">
                    <label for="razon_social">Razón social <span style="color:#DC2626">*</span></label>
                    <select id="razon_social" name="razon_social" required>
                        <option value="" disabled selected>Selecciona</option>
                        <option value="Industrias Salcom S.A. de C.V.">Industrias Salcom S.A. de C.V.</option>
                        <option value="Franfoods S.A. de C.V.">Franfoods S.A. de C.V.</option>
                    </select>
                </div>
                <div class="rb-form-group">
                    <label for="metodo_pago_empresa">Método de pago empresa <span style="color:#DC2626">*</span></label>
                    <select id="metodo_pago_empresa" name="metodo_pago_empresa" required>
                        <option value="" disabled selected>Selecciona</option>
                        <option value="bbva">BBVA (requiere factura a nombre de Salcom)</option>
                        <option value="inntec">Inntec (solo ticket, sin materialidad extra)</option>
                    </select>
                    <span class="hint" id="hintMetodo"></span>
                </div>
            </div>

            <div class="rb-form-row cols-4">
                <div class="rb-form-group">
                    <label>Uso de CFDI</label>
                    <input type="text" id="uso_cfdi_display" value="G03 — Gastos en General" readonly style="background:var(--gray-soft);">
                    <input type="hidden" name="uso_cfdi" id="uso_cfdi" value="G03">
                </div>
                <div class="rb-form-group">
                    <label>Forma de pago</label>
                    <input type="text" value="28 — Tarjeta de débito" readonly style="background:var(--gray-soft);">
                    <input type="hidden" name="forma_pago" value="28">
                </div>
                <div class="rb-form-group">
                    <label>Método de pago</label>
                    <input type="text" value="PUE — Pago en Una Exhibición" readonly style="background:var(--gray-soft);">
                    <input type="hidden" name="metodo_pago" value="PUE">
                </div>
                <div class="rb-form-group">
                    <label for="fecha_factura">Fecha de la factura <span style="color:#DC2626">*</span></label>
                    <input type="date" id="fecha_factura" name="fecha_factura" required value="{{ old('fecha_factura', date('Y-m-d')) }}">
                </div>
            </div>

            <div class="rb-form-row cols-3">
                <div class="rb-form-group">
                    <label for="monto">Monto total <span style="color:#DC2626">*</span></label>
                    <input type="text" id="monto" name="monto" placeholder="$0.00" required inputmode="decimal" value="{{ old('monto') }}">
                </div>
                <div class="rb-form-group">
                    <label for="concepto">Concepto <span style="color:#DC2626">*</span></label>
                    <input type="text" id="concepto" name="concepto" placeholder="Ej: Tóner impresora, gasolina..." required maxlength="255" value="{{ old('concepto') }}">
                </div>
                <div class="rb-form-group">
                    <label for="solicitante">Solicitante <span style="color:#DC2626">*</span></label>
                    <input type="text" id="solicitante" name="solicitante" placeholder="Nombre del colaborador" required maxlength="150" value="{{ old('solicitante') }}">
                </div>
            </div>

            <div class="rb-form-row cols-2">
                <div class="rb-form-group">
                    <label for="numero_cuenta">Número de cuenta / tarjeta <span style="color:#DC2626">*</span></label>
                    <input type="text" id="numero_cuenta" name="numero_cuenta" placeholder="Últimos 4 dígitos o cuenta completa" required maxlength="20" value="{{ old('numero_cuenta') }}">
                </div>
                <div class="rb-form-group">
                    <label for="titular_cuenta">Nombre del titular de la tarjeta <span style="color:#DC2626">*</span></label>
                    <input type="text" id="titular_cuenta" name="titular_cuenta" placeholder="Nombre como aparece en la tarjeta" required maxlength="150" value="{{ old('titular_cuenta') }}">
                </div>
            </div>

            {{-- Archivos --}}
            <div class="rb-uploads-grid" style="grid-template-columns:1fr 1fr 1fr;">
                <div class="rb-form-group">
                    <label>Factura PDF (firmada + voucher) <span style="color:#DC2626">*</span></label>
                    <div class="rb-upload-zone" id="uploadZone1" onclick="document.getElementById('archivo_factura').click();">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--gray-muted)" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <p>Factura PDF</p>
                        <span class="upload-hint">PDF, máx 10 MB</span>
                        <span class="file-name" id="fileName1"></span>
                    </div>
                    <input type="file" id="archivo_factura" name="archivo_factura" accept=".pdf" required style="display:none;">
                </div>
                <div class="rb-form-group">
                    <label>XML de la factura</label>
                    <div class="rb-upload-zone" id="uploadZone3" onclick="document.getElementById('archivo_xml').click();">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--gray-muted)" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <p>Archivo XML</p>
                        <span class="upload-hint">XML, máx 5 MB</span>
                        <span class="file-name" id="fileName3"></span>
                    </div>
                    <input type="file" id="archivo_xml" name="archivo_xml" accept=".xml" style="display:none;">
                </div>
                <div class="rb-form-group">
                    <label>Materialidad (correo o foto) <span style="color:#DC2626" id="materialidadReq">*</span></label>
                    <div class="rb-upload-zone" id="uploadZone2" onclick="document.getElementById('archivo_materialidad').click();">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--gray-muted)" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <p>Evidencia / Materialidad</p>
                        <span class="upload-hint">PDF, JPG, PNG — máx 10 MB</span>
                        <span class="file-name" id="fileName2"></span>
                    </div>
                    <input type="file" id="archivo_materialidad" name="archivo_materialidad" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
                    <span class="hint" id="hintMaterialidad">Obligatorio. Sin materialidad el reembolso se rechaza automáticamente.</span>
                </div>
            </div>

            <div class="rb-form-group" style="margin-bottom:24px;">
                <label for="notas">Notas adicionales</label>
                <textarea id="notas" name="notas" rows="2" placeholder="Observaciones, aprobaciones previas, etc." maxlength="500">{{ old('notas') }}</textarea>
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="btn-enviar-reembolso">Registrar reembolso</button>
            </div>
        </form>
    </div>

    {{-- Historial --}}
    <div class="rb-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
            <h3 style="margin:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                Historial de reembolsos
            </h3>
            @if(isset($reembolsos) && $reembolsos->count())
            <a href="{{ route('admin.reembolsos.excel') }}" style="padding:7px 14px;background:var(--green-bg,#dcfce7);border:1px solid #86efac;border-radius:8px;font-size:12px;font-weight:600;color:#166534;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">📊 Exportar Excel</a>
            @endif
        </div>

        @if(isset($reembolsos) && $reembolsos->count())
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="border-bottom:2px solid var(--border-light);">
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;">Fecha</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;">Solicitante</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;">Concepto</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;">Monto</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;">Nº Cuenta</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;">Titular</th>
                        <th style="padding:10px 12px;text-align:left;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;">Institución</th>
                        <th style="padding:10px 12px;text-align:center;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;">Autorización Sandra Gutiérrez</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reembolsos as $r)
                    @php $d = $r->datos ?? []; @endphp
                    <tr style="border-bottom:1px solid var(--border-light);">
                        <td style="padding:10px 12px;">{{ $r->created_at->format('d/m/Y') }}</td>
                        <td style="padding:10px 12px;font-weight:600;">{{ $d['solicitante'] ?? '—' }}</td>
                        <td style="padding:10px 12px;">{{ $d['concepto'] ?? $r->contenido ?? '—' }}</td>
                        <td style="padding:10px 12px;font-weight:600;">${{ $d['monto'] ?? '—' }}</td>
                        <td style="padding:10px 12px;">{{ $d['numero_cuenta'] ?? '—' }}</td>
                        <td style="padding:10px 12px;">{{ $d['titular_cuenta'] ?? '—' }}</td>
                        <td style="padding:10px 12px;">{{ strtoupper($d['metodo_pago_empresa'] ?? '—') }}</td>
                        <td style="padding:10px 12px;text-align:center;">
                            @if(!empty($d['autorizado_sandra']))
                                <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#dcfce7;color:#166534;">✓ Autorizado</span>
                            @else
                                <form method="POST" action="{{ route('admin.reembolsos.autorizar', $r->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" style="padding:5px 12px;border:1.5px solid #7c3aed;border-radius:8px;background:#f5f3ff;color:#7c3aed;font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;" onclick="return confirm('¿Autorizar este reembolso como Sandra Gutiérrez?');">Autorizar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="text-align:center;padding:32px;color:var(--gray-muted);font-size:13px;">
            <p>Aún no hay reembolsos registrados.</p>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var categoria = document.getElementById('categoria');
    var usoCfdiDisplay = document.getElementById('uso_cfdi_display');
    var usoCfdi = document.getElementById('uso_cfdi');
    var metodoEmpresa = document.getElementById('metodo_pago_empresa');
    var hintMetodo = document.getElementById('hintMetodo');
    var hintMaterialidad = document.getElementById('hintMaterialidad');
    var materialidadReq = document.getElementById('materialidadReq');
    var inputMaterialidad = document.getElementById('archivo_materialidad');
    var razonSocial = document.getElementById('razon_social');

    // Cambiar uso de CFDI según categoría
    categoria.addEventListener('change', function() {
        if (this.value === 'computo') {
            usoCfdiDisplay.value = 'I04 — Equipo de Cómputo';
            usoCfdi.value = 'I04';
        } else {
            usoCfdiDisplay.value = 'G03 — Gastos en General';
            usoCfdi.value = 'G03';
        }
        // Viajes internacionales: no requiere factura fiscal mexicana
        var facLabel = document.querySelector('[for="archivo_factura"]') || document.querySelector('label[for="archivo_factura"]');
        if (this.value === 'viaticos_internacional') {
            if (facLabel) facLabel.innerHTML = 'Ticket o comprobante (PDF/imagen)';
            document.getElementById('archivo_factura').removeAttribute('required');
            document.getElementById('archivo_factura').accept = '.pdf,.jpg,.jpeg,.png';
        } else {
            if (facLabel) facLabel.innerHTML = 'Factura PDF (firmada + voucher) <span style="color:#DC2626">*</span>';
            document.getElementById('archivo_factura').setAttribute('required', 'required');
            document.getElementById('archivo_factura').accept = '.pdf';
        }
    });

    // Método de pago empresa: Inntec no requiere materialidad
    metodoEmpresa.addEventListener('change', function() {
        var cfdiDisplay = document.getElementById('uso_cfdi_display');
        var formaPagoDisplay = document.querySelector('input[value="28 — Tarjeta de débito"]');
        var metodoPagoDisplay = document.querySelector('input[value="PUE — Pago en Una Exhibición"]');

        if (this.value === 'inntec') {
            hintMetodo.textContent = 'Inntec: solo ticket, no requiere materialidad extra.';
            hintMetodo.style.color = '#2563eb';
            hintMaterialidad.textContent = 'No requerido para Inntec (solo ticket).';
            materialidadReq.style.display = 'none';
            if (inputMaterialidad) inputMaterialidad.removeAttribute('required');
            // Bloquear y vaciar CFDI, forma de pago y método de pago
            if (cfdiDisplay) { cfdiDisplay.value = 'N/A — No aplica (Inntec)'; cfdiDisplay.style.opacity = '0.5'; }
            if (formaPagoDisplay) { formaPagoDisplay.value = 'N/A — No aplica'; formaPagoDisplay.style.opacity = '0.5'; }
            if (metodoPagoDisplay) { metodoPagoDisplay.value = 'N/A — No aplica'; metodoPagoDisplay.style.opacity = '0.5'; }
            document.getElementById('uso_cfdi').value = 'N/A';
        } else {
            hintMetodo.textContent = 'BBVA: la factura debe estar a nombre de Salcom.';
            hintMetodo.style.color = '#92400e';
            hintMaterialidad.textContent = 'Obligatorio. Sin materialidad el reembolso se rechaza automáticamente.';
            materialidadReq.style.display = 'inline';
            // Restaurar CFDI, forma de pago y método de pago
            var cat = document.getElementById('categoria');
            if (cfdiDisplay) {
                cfdiDisplay.value = (cat && cat.value === 'computo') ? 'I04 — Equipo de Cómputo' : 'G03 — Gastos en General';
                cfdiDisplay.style.opacity = '1';
            }
            document.getElementById('uso_cfdi').value = (cat && cat.value === 'computo') ? 'I04' : 'G03';
            if (formaPagoDisplay) { formaPagoDisplay.value = '28 — Tarjeta de débito'; formaPagoDisplay.style.opacity = '1'; }
            if (metodoPagoDisplay) { metodoPagoDisplay.value = 'PUE — Pago en Una Exhibición'; metodoPagoDisplay.style.opacity = '1'; }
        }
    });

    // Upload zones
    function setupUpload(zoneId, inputId, nameId) {
        var zone = document.getElementById(zoneId);
        var input = document.getElementById(inputId);
        var nameEl = document.getElementById(nameId);
        if (!zone || !input) return;

        ['dragenter', 'dragover'].forEach(function(ev) {
            zone.addEventListener(ev, function(e) { e.preventDefault(); zone.classList.add('dragover'); });
        });
        ['dragleave', 'drop'].forEach(function(ev) {
            zone.addEventListener(ev, function(e) { e.preventDefault(); zone.classList.remove('dragover'); });
        });
        zone.addEventListener('drop', function(e) {
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                nameEl.textContent = '📄 ' + e.dataTransfer.files[0].name;
                nameEl.style.display = 'block';
            }
        });
        input.addEventListener('change', function() {
            if (this.files.length) {
                nameEl.textContent = '📄 ' + this.files[0].name;
                nameEl.style.display = 'block';
            }
        });
    }
    setupUpload('uploadZone1', 'archivo_factura', 'fileName1');
    setupUpload('uploadZone2', 'archivo_materialidad', 'fileName2');
    setupUpload('uploadZone3', 'archivo_xml', 'fileName3');
})();
</script>
@endpush
