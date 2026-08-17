@extends('layouts.admin')
@section('title', 'Abono Prov. · '.$poliza['serie'])
@section('hero')
<div class="hero-band">
    <h1>Abono a proveedor</h1>
    <p>{{ $poliza['titulo'] }} · Serie {{ $poliza['serie'] }} · Folio {{ $folioSiguiente }}</p>
</div>
@endsection
@push('styles')
<style>
    .cq-wrap{background:#f3f4f6;border:1px solid #d1d5db;border-radius:8px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .cq-titlebar{background:linear-gradient(180deg,#eef2ff,#e0e7ff);border-bottom:1px solid #c7d2fe;padding:8px 14px;font-size:13px;font-weight:700;color:#312e81}
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
    .dias-count.warn{color:#d97706}
    .dias-count.late{color:#dc2626}
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
@if(session('error'))
    <div class="pag-alert err">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="pag-alert err">{{ $errors->first() }}</div>
@endif

<form method="post" action="{{ route('admin.pago-proveedores.store') }}" id="form-abono">
    @csrf
    <input type="hidden" name="poliza_key" value="{{ $poliza['key'] }}">
    <input type="hidden" name="accion" id="accion" value="guardar">

    <div class="cq-wrap">
        <div class="cq-titlebar">Abono Prov. · {{ $poliza['concepto'] }} · Serie {{ $poliza['serie'] }}</div>

        <div class="cq-toolbar" style="justify-content:flex-end;padding:12px 14px">
            <button type="submit" class="cq-tool primary" style="min-width:140px;padding:10px 24px;font-size:13px" onclick="document.getElementById('accion').value='guardar'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Guardar abono
            </button>
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

        {{-- Selector proveedor ELIMINADO — solo se muestra la franja del proveedor ya seleccionado --}}
        <div style="display:none" id="prov-select-wrap">
            <div class="cq-field">
                <label>Proveedor</label>
                <select name="proveedor_id" id="proveedor_id" required style="max-width:500px">
                    <option value="">— Seleccionar proveedor —</option>
                    @foreach($proveedores as $p)
                        @php
                            $cod = $p->id_proveedor ?: $p->codigo;
                            $mon = $p->etiquetaMoneda();
                        @endphp
                        <option value="{{ $p->id }}"
                            data-codigo="{{ $cod }}"
                            data-nombre="{{ $p->nombre }}"
                            data-moneda="{{ $mon }}"
                            data-banco="{{ $p->datos_identificacion['banco'] ?? '' }}"
                            data-clabe="{{ $p->datos_identificacion['clabe'] ?? '' }}"
                            @selected((string) old('proveedor_id', $proveedorIdPref ?? '') === (string) $p->id)>
                            {{ $cod }} — {{ $p->nombre }} [{{ $mon }}]
                        </option>
                    @endforeach
                </select>
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
            <div style="display:flex;align-items:center;gap:16px">
                <div class="cq-total">Pago: $<span id="sel-total">0.00</span> {{ $poliza['moneda'] }}</div>
                <button type="submit" style="padding:10px 28px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit" onclick="document.getElementById('accion').value='guardar'">
                    Guardar abono
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
            const late = it.dias_restantes != null && it.dias_restantes < 0;
            const warn = it.dias_restantes != null && it.dias_restantes <= 15;
            const cls = late ? 'late' : (warn ? 'warn' : '');
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

    select.addEventListener('change', () => {
        const opt = select.options[select.selectedIndex];
        const strip = document.getElementById('prov-strip');
        const selectWrap = document.getElementById('prov-select-wrap');

        if (select.value) {
            // Mostrar franja proveedor estilo Contpaqi
            document.getElementById('strip-code').textContent = opt.dataset.codigo || '—';
            document.getElementById('strip-name').textContent = opt.dataset.nombre || '—';
            document.getElementById('strip-moneda').textContent = opt.dataset.moneda || '{{ $poliza["moneda_label"] }}';
            strip.classList.remove('hidden');
            selectWrap.style.display = 'none';

            // Precargar cuenta bancaria del proveedor
            var banco = opt.dataset.banco || '';
            var clabe = opt.dataset.clabe || '';
            var cuentaField = document.getElementById('cuenta_bancaria');
            if (banco || clabe) {
                cuentaField.value = (banco ? banco : '') + (banco && clabe ? ' · CLABE: ' : '') + (clabe ? clabe : '');
            } else {
                cuentaField.value = 'Sin datos bancarios registrados';
            }

            // Detalle lateral removido
            loadFacturas(select.value);
        } else {
            strip.classList.add('hidden');
            selectWrap.style.display = '';
            body.innerHTML = '<tr class="empty-row"><td colspan="11">Selecciona un proveedor para cargar facturas pendientes</td></tr>';
            recalc();
        }
    });

    // Franja proveedor: solo visual, no se puede cambiar
    document.getElementById('prov-strip').addEventListener('dblclick', () => {
        // Deshabilitado: para cambiar proveedor, regresa al listado
    });

    document.getElementById('form-abono').addEventListener('submit', (e) => {
        const n = body.querySelectorAll('.chk-doc:checked').length;
        if (!n) {
            e.preventDefault();
            alert('Selecciona al menos una factura / compra a pagar.');
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
        select.dispatchEvent(new Event('change'));
    }
})();
</script>
@endpush
