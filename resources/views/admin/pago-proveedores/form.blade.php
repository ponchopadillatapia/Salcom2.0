@extends('layouts.admin')
@section('title', 'Pago a proveedor · '.$poliza['serie'])
@section('hero')
<div class="hero-band">
    <h1>Pago a proveedor</h1>
    <p>{{ $poliza['titulo'] }} · Serie {{ $poliza['serie'] }} · Folio {{ $folioSiguiente }}</p>
</div>
@endsection
@push('styles')
<style>
    .cq-wrap{background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .cq-titlebar{background:linear-gradient(180deg,#f3e8ff,#ede9fe);border-bottom:1px solid #c4b5fd;padding:8px 14px;font-size:13px;font-weight:700;color:#5b21b6}
    .cq-toolbar{display:flex;flex-wrap:wrap;gap:4px;padding:8px;background:#fff;border-bottom:1px solid #e5e7eb}
    .cq-tool{display:inline-flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;min-width:64px;padding:6px 8px;border:1px solid transparent;border-radius:6px;background:transparent;font-size:10px;font-weight:600;color:#374151;cursor:pointer;font-family:inherit}
    .cq-tool:hover{background:#f3f4f6;border-color:#e5e7eb}
    .cq-tool.primary{background:#6B3FA0;color:#fff}
    .cq-tool.primary:hover{background:#5a3490;border-color:#5a3490}
    .cq-tool.danger{color:#b91c1c}
    .cq-tool svg{width:18px;height:18px}
    .cq-tool:disabled{opacity:.35;cursor:not-allowed}
    .cq-head{display:grid;grid-template-columns:repeat(4,1fr);gap:10px 14px;padding:14px;background:#fff;border-bottom:none}
    .cq-prov-strip{display:grid;grid-template-columns:1fr 1fr;gap:0;align-items:stretch;margin:0;border-bottom:1px solid #e5e7eb}
    .cq-prov-strip .prov-cell{padding:10px 14px;background:#fff;border:1px solid #d1d5db}
    .cq-prov-strip .prov-cell:first-child{border-right:none}
    .cq-prov-strip .prov-cell-label{display:block;font-size:10px;font-weight:800;color:#374151;text-transform:uppercase;letter-spacing:.4px;margin-bottom:2px;display:inline-block;border-radius:2px}
    .cq-prov-strip .prov-code{font-size:13px;font-weight:800;color:#111827;margin-top:4px}
    .cq-prov-strip .prov-name{font-size:14px;font-weight:600;color:#374151}
    .cq-prov-strip .prov-moneda-line{font-size:13px;color:#374151;margin-top:4px}
    .cq-prov-strip .prov-moneda-line strong{color:#111827}
    .cq-prov-strip .prov-tc-line{font-size:13px;color:#374151;margin-top:4px}
    .cq-prov-strip .prov-tc-line input{border-bottom:1px solid #374151;border-top:none;border-left:none;border-right:none;background:transparent;font-size:13px;font-weight:700;color:#111827;width:80px;padding:2px 4px}
    .cq-prov-strip .prov-tc-line input:focus{outline:none;border-bottom-color:#7c3aed}
    .cq-prov-strip.hidden{display:none}
    .cq-field{display:flex;flex-direction:column;gap:3px}
    .cq-field label{font-size:11px;font-weight:700;color:#6b7280}
    .cq-field input,.cq-field select{border:1px solid #9ca3af;border-radius:3px;padding:6px 8px;font-size:13px;font-family:inherit;background:#fff}
    .cq-field input:focus,.cq-field select:focus{outline:2px solid #a78bfa;border-color:#7c3aed}
    .cq-field.span2{grid-column:span 2}
    .cq-body{display:flex;flex-direction:column;min-height:420px;background:#fff}
    .cq-main{display:flex;flex-direction:column;flex:1}
    .cq-tabs{display:flex;gap:0;border-bottom:1px solid #e5e7eb;background:#f9fafb}
    .cq-tab{padding:8px 14px;font-size:12px;font-weight:600;color:#6b7280;border-bottom:2px solid transparent}
    .cq-tab.active{color:#5b21b6;border-bottom-color:#6B3FA0;background:#fff}
    .cq-table-wrap{overflow:auto;flex:1}
    .cq-table{width:100%;border-collapse:collapse;font-size:12px}
    .cq-table th{position:sticky;top:0;background:#eef2ff;color:#3730a3;font-size:11px;text-transform:uppercase;letter-spacing:.3px;padding:8px;text-align:left;border-bottom:1px solid #c7d2fe}
    .cq-table td{padding:8px;border-bottom:1px solid #f3f4f6;vertical-align:middle}
    .cq-table tbody tr:hover{background:#f5f3ff}
    .cq-table tbody tr.is-on{background:#ede9fe}
    .cq-table input[type=number],.cq-table input[type=text].imp-doc{width:120px;border:1px solid #d1d5db;border-radius:6px;padding:6px 8px;font-size:13px;font-weight:600;text-align:right;-moz-appearance:textfield}
    .cq-table input[type=number]::-webkit-outer-spin-button,.cq-table input[type=number]::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
    .cq-table input[type=number]:focus,.cq-table input[type=text].imp-doc:focus{outline:2px solid #a78bfa;border-color:#7c3aed}
    .cq-table .saldo-col{font-size:12px;color:#6b7280;font-weight:600}
    .dias-count{font-weight:700;font-variant-numeric:tabular-nums;line-height:1.2;white-space:nowrap}
    .dias-count.ok{color:#16a34a}
    .dias-count.warn{color:#d97706}
    .dias-count.late{color:#dc2626}
    .dias-count.tinto{color:#7f1d1d;font-weight:800}
    .dias-sub{font-size:10px;color:#6b7280;margin-top:2px;white-space:nowrap}
    .cq-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 14px;background:#f9fafb;border-top:1px solid #e5e7eb}
    .cq-total{font-size:18px;font-weight:800;color:#166534;font-variant-numeric:tabular-nums}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .back{display:inline-flex;margin-bottom:12px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    .empty-row td{text-align:center;color:#9ca3af;padding:28px!important}
    @media(max-width:960px){
        .cq-head{grid-template-columns:1fr 1fr}
    }
</style>
@endpush
@section('content')
<a href="{{ route('admin.pago-proveedores') }}" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Volver a pagos
</a>
@if(session('error'))
    <div class="pag-alert err">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="pag-alert err">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.pago-proveedores.store') }}" id="form-abono" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="poliza_key" value="{{ $poliza['key'] }}">
    <input type="hidden" name="accion" id="accion" value="guardar">

    <div class="cq-wrap">
        <div class="cq-titlebar">Pago a proveedor · {{ $poliza['concepto'] }} · Serie {{ $poliza['serie'] }}</div>

        <div class="cq-toolbar" style="justify-content:flex-end;padding:12px 14px">
        </div>

        <div class="cq-head">
            <div class="cq-field">
                <label>Concepto</label>
                <input type="text" value="{{ $poliza['concepto'] }}" readonly>
            </div>
            <div class="cq-field">
                <label>Fecha</label>
                <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" required>
            </div>
            <div class="cq-field">
                <label>Serie</label>
                <input type="text" value="{{ $poliza['serie'] }}" readonly>
            </div>
            <div class="cq-field">
                <label>Folio</label>
                <input type="text" value="{{ $folioSiguiente }}" readonly>
            </div>
            <div class="cq-field span2">
                <label>Cuenta bancaria proveedor</label>
                <input type="text" name="cuenta_bancaria" id="cuenta_bancaria" value="{{ old('cuenta_bancaria') }}" placeholder="Se precarga al seleccionar proveedor" readonly style="background:#f9fafb">
            </div>
            <div class="cq-field span2">
                <label>Notas</label>
                <input type="text" name="notas" value="{{ old('notas') }}" placeholder="Opcional">
            </div>
        </div>

        {{-- Bloque Proveedor estilo Contpaqi --}}
        <div class="cq-prov-strip hidden" id="prov-strip">
            <div class="prov-cell">
                <span class="prov-cell-label">Proveedor</span>
                <div class="prov-code" id="strip-code">—</div>
                <div class="prov-name" id="strip-name">—</div>
            </div>
            <div class="prov-cell">
                <div class="prov-moneda-line">Moneda: &nbsp;<strong id="strip-moneda">{{ $poliza['moneda_label'] }}</strong></div>
                <div class="prov-tc-line">Tipo de cambio: &nbsp;
                    <input type="number" step="0.0001" min="0" name="tipo_cambio" id="tipo_cambio"
                       value="{{ old('tipo_cambio', $poliza['moneda'] === 'MXN' ? '1' : ($poliza['tipo_cambio_default'] ?? '')) }}"
                       placeholder="{{ $poliza['moneda'] === 'USD' ? '17.9042' : '1' }}"
                       @if($poliza['moneda'] === 'MXN') readonly @endif>
                </div>
            </div>
        </div>

        {{-- Proveedor: hidden input + botón modal --}}
        <input type="hidden" name="proveedor_id" id="proveedor_id" value="{{ old('proveedor_id', $proveedorIdPref ?? '') }}">
        <div id="prov-select-wrap" style="padding:0 14px 14px">
            <div class="cq-field">
                <label>Proveedor <span style="color:#dc2626">●</span></label>
                <button type="button" id="btn-abrir-prov" onclick="abrirModalProv()" style="text-align:left;padding:10px 14px;border:2px solid #dc2626;border-radius:8px;background:#faf5ff;font-size:13px;font-weight:600;color:#5b21b6;cursor:pointer;font-family:inherit;width:100%;max-width:500px">
                    Seleccionar proveedor...
                </button>
            </div>
        </div>

        {{-- Modal selector proveedor --}}
        <div id="modal-prov" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center;padding:20px">
            <div style="background:#fff;border-radius:12px;width:100%;max-width:720px;max-height:75vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;border:1px solid #e5e7eb">
                <div style="background:linear-gradient(135deg,#4a2078,#6B3FA0);padding:12px 20px;display:flex;align-items:center;gap:12px">
                    <button type="button" onclick="cerrarModalProv()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:6px;font-size:16px;cursor:pointer;font-weight:700;display:flex;align-items:center;justify-content:center">&times;</button>
                    <span style="font-size:15px;font-weight:700;color:#fff">Seleccionar proveedor</span>
                </div>
                <div style="padding:12px 20px;background:#faf5ff;border-bottom:1px solid #e9d5ff;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px;position:relative">
                        <input type="text" id="prov-buscar" placeholder="Buscar por nombre, código o RFC..." style="border:1.5px solid #c4b5fd;border-radius:8px;padding:9px 14px 9px 34px;font-size:13px;width:100%;font-family:inherit;background:#fff" oninput="filtrarProvs()">
                        <svg style="position:absolute;left:10px;top:10px;opacity:.5" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </div>
                    <label style="font-size:12px;display:flex;align-items:center;gap:5px;cursor:pointer;color:#5b21b6;font-weight:600">
                        <input type="checkbox" id="prov-activos" checked onchange="filtrarProvs()" style="accent-color:#6B3FA0;width:15px;height:15px"> Activos
                    </label>
                </div>
                <div style="overflow-y:auto;flex:1">
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr style="background:#f3e8ff;position:sticky;top:0">
                                <th style="padding:10px 16px;text-align:left;font-weight:700;color:#5b21b6;font-size:11px;text-transform:uppercase;border-bottom:2px solid #c4b5fd;width:130px">Código</th>
                                <th style="padding:10px 16px;text-align:left;font-weight:700;color:#5b21b6;font-size:11px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Nombre</th>
                                <th style="padding:10px 16px;text-align:left;font-weight:700;color:#5b21b6;font-size:11px;text-transform:uppercase;border-bottom:2px solid #c4b5fd;width:140px">R.F.C.</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-provs-body">
                            @foreach($proveedores as $p)
                                @php
                                    $cod = $p->id_proveedor ?: $p->codigo;
                                    $di = is_array($p->datos_identificacion) ? $p->datos_identificacion : [];
                                    $rfc = $di['rfc'] ?? '';
                                @endphp
                                <tr class="prov-row" data-id="{{ $p->id }}" data-codigo="{{ $cod }}" data-nombre="{{ $p->nombre }}" data-rfc="{{ $rfc }}" data-moneda="{{ $p->etiquetaMoneda() }}" data-banco="{{ $di['banco'] ?? '' }}" data-clabe="{{ $di['clabe'] ?? '' }}" onclick="seleccionarProv(this)" style="cursor:pointer;border-bottom:1px solid #f3f4f6">
                                    <td style="padding:10px 16px;font-weight:700;color:#6B3FA0">{{ $cod }}</td>
                                    <td style="padding:10px 16px;color:#111">{{ $p->nombre }}</td>
                                    <td style="padding:10px 16px;font-size:12px;color:#6b7280;font-family:monospace">{{ $rfc }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="cq-body">
            <div class="cq-main">
                <div class="cq-tabs">
                    <div class="cq-tab active">Generales</div>
                    <div class="cq-tab">Resumen de Pagos</div>
                </div>
                <div class="cq-table-wrap">
                    <table class="cq-table" id="tbl-docs">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="chk-all" checked title="Seleccionar/Deseleccionar todas"></th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Serie</th>
                                <th>Folio</th>
                                <th>Vencimiento</th>
                                <th>Concepto</th>
                                <th>Referencia</th>
                                <th>Saldo</th>
                                <th>Pago</th>
                                <th>Sistema origen</th>
                            </tr>
                        </thead>
                        <tbody id="docs-body">
                            <tr class="empty-row"><td colspan="11">Selecciona un proveedor para cargar facturas pendientes</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="cq-foot">
            <div style="font-size:12px;color:#6b7280">Documentos seleccionados: <strong id="sel-count">0</strong></div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:12px;padding:8px 14px;background:#faf5ff;border:1.5px solid #c4b5fd;border-radius:8px;color:#5b21b6;font-weight:600" id="formato-label">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.49"/></svg>
                    <span id="formato-file-name">Adjuntar formato de pago (PDF)</span>
                    <input type="file" name="formato_pago" accept=".pdf,application/pdf" style="display:none" id="formato-pago-input" onchange="updateFormatoLabel()">
                </label>
                <div class="cq-total">Pago: $<span id="sel-total">0.00</span> {{ $poliza['moneda'] }}</div>
                <a href="{{ route('admin.pago-proveedores') }}" style="padding:10px 20px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;text-decoration:none">
                    Cancelar
                </a>
                <button type="submit" style="padding:10px 28px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit" onclick="document.getElementById('accion').value='guardar'">
                    Guardar pago
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const select = document.getElementById('proveedor_id');
    const body = document.getElementById('docs-body');
    const urlBase = @json(route('admin.pago-proveedores.facturas-json'));
    const polizaMoneda = @json($poliza['moneda']);

    function fmt(n) {
        return (Number(n) || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function parseImporte(val) {
        // Acepta: 70,000.50 o 70000.50 o 70,000
        return Number(String(val).replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
    }

    function formatImporte(n) {
        return Number(n).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function recalc() {
        let total = 0, count = 0;
        body.querySelectorAll('tr[data-id]').forEach(tr => {
            const chk = tr.querySelector('.chk-doc');
            const inp = tr.querySelector('.imp-doc');
            if (chk && chk.checked) {
                count++;
                total += parseImporte(inp.value);
                tr.classList.add('is-on');
            } else {
                tr.classList.remove('is-on');
            }
        });
        document.getElementById('sel-count').textContent = count;
        document.getElementById('sel-total').textContent = fmt(total);
    }

    function showDetalle(tr) {
        // sin panel lateral
    }

    function renderItems(items) {
        if (!items.length) {
            body.innerHTML = '<tr class="empty-row"><td colspan="11">Este proveedor no tiene facturas pendientes en Salcom</td></tr>';
            recalc();
            return;
        }
        body.innerHTML = items.map(it => {
            let cls = '';
            if (it.dias_restantes != null) {
                if (it.dias_restantes <= 0) cls = 'tinto';
                else if (it.dias_restantes <= 10) cls = 'late';
                else if (it.dias_restantes <= 30) cls = 'warn';
                else cls = 'ok';
            }
            let sub = it.fecha_vencimiento_fmt || '';
            if (it.dias_plazo) sub += (sub ? ' · de ' : 'de ') + it.dias_plazo;
            const vencHtml = (it.dias_label && it.dias_label !== '—')
                ? `<div class="dias-count ${cls}">${it.dias_label}</div>` + (sub ? `<div class="dias-sub">${sub}</div>` : '')
                : (it.fecha_vencimiento_fmt || '—');
            return `
            <tr data-id="${it.id}" data-serie="${it.serie}" data-folio="${it.folio}" data-concepto="${it.concepto}" data-total="${it.total}" data-moneda="${it.moneda}">
                <td><input type="checkbox" class="chk-doc" name="factura_ids[]" value="${it.id}" checked></td>
                <td>${it.fecha_fmt}</td>
                <td style="font-size:11px;color:#6b7280">${it.hora || '—'}</td>
                <td>${it.serie}</td>
                <td>${it.folio}</td>
                <td>${vencHtml}</td>
                <td>${it.concepto}</td>
                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${it.referencia || ''}">${it.referencia || '—'}</td>
                <td class="saldo-col">$${Number(it.total_factura || it.total).toLocaleString('es-MX', {minimumFractionDigits:2})}</td>
                <td><input type="text" inputmode="decimal" class="imp-doc" name="importes[${it.id}]" value="${Number(it.total).toLocaleString('es-MX', {minimumFractionDigits:2})}" data-raw="${Number(it.total).toFixed(2)}"></td>
                <td>${it.sistema_origen}</td>
            </tr>
        `;
        }).join('');

        body.querySelectorAll('tr[data-id]').forEach(tr => {
            const chk = tr.querySelector('.chk-doc');
            const inp = tr.querySelector('.imp-doc');
            tr.classList.add('is-on');
            chk.addEventListener('change', () => {
                inp.disabled = !chk.checked;
                if (!chk.checked) tr.classList.remove('is-on');
                else tr.classList.add('is-on');
                recalc();
            });
            inp.addEventListener('input', recalc);
            inp.addEventListener('blur', () => {
                const val = parseImporte(inp.value);
                inp.value = formatImporte(val);
            });
            inp.addEventListener('focus', () => {
                const val = parseImporte(inp.value);
                inp.value = val > 0 ? val.toFixed(2) : '';
            });
        });
        recalc();
    }

    async function loadFacturas(proveedorId) {
        body.innerHTML = '<tr class="empty-row"><td colspan="11">Cargando…</td></tr>';
        const res = await fetch(urlBase + '?proveedor_id=' + encodeURIComponent(proveedorId), {
            headers: {'Accept': 'application/json'}
        });
        const data = await res.json();
        let items = data.items || [];
        if (polizaMoneda) {
            const want = polizaMoneda === 'USD' ? ['USD', 'DOLLAR'] : ['MXN', 'MXP'];
            const filtered = items.filter(i => want.includes(String(i.moneda || 'MXN').toUpperCase()));
            if (filtered.length) items = filtered;
        }
        renderItems(items);
    }

    // Selección de proveedor se maneja desde el modal (función seleccionarProv)

    // Franja proveedor: solo visual
    document.getElementById('prov-strip').addEventListener('dblclick', () => {});

    document.getElementById('form-abono').addEventListener('submit', (e) => {
        const n = body.querySelectorAll('.chk-doc:checked').length;
        if (!n) {
            e.preventDefault();
            alert('Selecciona al menos una factura / compra a pagar.');
            return;
        }
        // Validar que se adjuntó el formato de pago
        const formatoInput = document.getElementById('formato-pago-input');
        if (!formatoInput.files || formatoInput.files.length === 0) {
            e.preventDefault();
            alert('Debes adjuntar el formato de pago (PDF) antes de guardar.');
            document.getElementById('formato-label').style.borderColor = '#dc2626';
            return;
        }
        // Convertir valores con comas a números limpios para el backend
        body.querySelectorAll('.imp-doc').forEach(inp => {
            inp.value = parseImporte(inp.value).toFixed(2);
        });
    });

    // Checkbox master: seleccionar/deseleccionar todas
    document.getElementById('chk-all').addEventListener('change', function() {
        const checked = this.checked;
        body.querySelectorAll('.chk-doc').forEach(chk => {
            chk.checked = checked;
            const tr = chk.closest('tr');
            const inp = tr.querySelector('.imp-doc');
            if (inp) inp.disabled = !checked;
            if (checked) tr.classList.add('is-on');
            else tr.classList.remove('is-on');
        });
        recalc();
    });

    if (select.value) {
        // Si ya hay proveedor precargado, cargar facturas
        loadFacturas(select.value);
    }

    // Exponer loadFacturas globalmente para el modal
    window._loadFacturas = loadFacturas;
})();

// ═══════════════════════════════════════════
// Modal proveedor
// ═══════════════════════════════════════════
function abrirModalProv() {
    document.getElementById('modal-prov').style.display = 'flex';
    setTimeout(function() { document.getElementById('prov-buscar').focus(); }, 100);
}
function cerrarModalProv() {
    document.getElementById('modal-prov').style.display = 'none';
}
function filtrarProvs() {
    var q = document.getElementById('prov-buscar').value.toLowerCase().trim();
    document.querySelectorAll('.prov-row').forEach(function(tr) {
        var nombre = (tr.dataset.nombre || '').toLowerCase();
        var codigo = (tr.dataset.codigo || '').toLowerCase();
        var rfc = (tr.dataset.rfc || '').toLowerCase();
        tr.style.display = (nombre.includes(q) || codigo.includes(q) || rfc.includes(q)) ? '' : 'none';
    });
}
function seleccionarProv(tr) {
    var id = tr.dataset.id;
    var codigo = tr.dataset.codigo;
    var nombre = tr.dataset.nombre;
    var banco = tr.dataset.banco || '';
    var clabe = tr.dataset.clabe || '';
    var moneda = tr.dataset.moneda || '';

    // Setear hidden input
    document.getElementById('proveedor_id').value = id;

    // Actualizar botón
    var btn = document.getElementById('btn-abrir-prov');
    btn.textContent = codigo + ' — ' + nombre;
    btn.style.color = '#111827';
    btn.style.borderColor = '#16a34a';

    // Mostrar franja proveedor
    document.getElementById('strip-code').textContent = codigo;
    document.getElementById('strip-name').textContent = nombre;
    document.getElementById('strip-moneda').textContent = moneda;
    document.getElementById('prov-strip').classList.remove('hidden');
    document.getElementById('prov-select-wrap').style.display = 'none';

    // Precargar cuenta bancaria
    var cuentaField = document.getElementById('cuenta_bancaria');
    if (banco || clabe) {
        cuentaField.value = (banco ? banco : '') + (banco && clabe ? ' · CLABE: ' : '') + (clabe ? clabe : '');
    } else {
        cuentaField.value = 'Sin datos bancarios registrados';
    }

    // Cargar facturas
    if (window._loadFacturas) window._loadFacturas(id);

    // Cerrar modal
    cerrarModalProv();

    // Highlight
    document.querySelectorAll('.prov-row').forEach(function(r) { r.style.background = ''; });
    tr.style.background = '#dbeafe';
}
// Hover filas
document.querySelectorAll('.prov-row').forEach(function(tr) {
    tr.addEventListener('mouseenter', function() { this.style.background = '#f3e8ff'; });
    tr.addEventListener('mouseleave', function() { if (this.style.background !== 'rgb(219, 234, 254)') this.style.background = ''; });
});

function updateFormatoLabel() {
    var input = document.getElementById('formato-pago-input');
    var label = document.getElementById('formato-file-name');
    var wrapper = document.getElementById('formato-label');
    if (input.files && input.files.length > 0) {
        var name = input.files[0].name;
        label.textContent = name.length > 25 ? name.substring(0, 22) + '...' : name;
        wrapper.style.borderColor = '#059669';
        wrapper.style.color = '#059669';
    } else {
        label.textContent = 'Adjuntar formato de pago (PDF)';
        wrapper.style.borderColor = '#c4b5fd';
        wrapper.style.color = '#5b21b6';
    }
}
</script>
@endpush
