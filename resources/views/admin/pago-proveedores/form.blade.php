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
    .cq-table input[type=number]{width:110px;border:1px solid #d1d5db;border-radius:3px;padding:4px 6px;font-size:12px}
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

        <div class="cq-toolbar">
            <a class="cq-tool" href="{{ route('admin.pago-proveedores') }}" title="Nuevo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Nuevo
            </a>
            <button type="submit" class="cq-tool primary" onclick="document.getElementById('accion').value='guardar'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Guardar
            </button>
            <button type="submit" class="cq-tool" onclick="document.getElementById('accion').value='borrador'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                Borrador
            </button>
            <button type="button" class="cq-tool" disabled title="Próximamente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                Preliminar
            </button>
            <button type="button" class="cq-tool" disabled title="Próximamente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                Enviar
            </button>
            <button type="button" class="cq-tool" disabled title="Próximamente">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                Relacionar CFDIs
            </button>
            <a class="cq-tool danger" href="{{ route('admin.pago-proveedores') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                Cerrar
            </a>
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
                <label>Cuenta bancaria empresa</label>
                <input type="text" name="cuenta_bancaria" value="{{ old('cuenta_bancaria') }}" placeholder="(Ninguno) / Base Dollar / …">
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
                    <input type="number" step="0.000001" min="0" name="tipo_cambio" id="tipo_cambio"
                       value="{{ old('tipo_cambio', $poliza['tipo_cambio_default'] ?? '') }}"
                       placeholder="{{ $poliza['moneda'] === 'USD' ? '17.9042' : '1' }}"
                       @if($poliza['moneda'] === 'MXN') required @endif>
                </div>
            </div>
        </div>

        {{-- Selector proveedor (se oculta una vez elegido) --}}
        <div style="padding:10px 14px;background:#fff;border-bottom:1px solid #e5e7eb" id="prov-select-wrap">
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
                                <th></th>
                                <th>Fecha</th>
                                <th>Serie</th>
                                <th>Folio</th>
                                <th>Concepto</th>
                                <th>Referencia</th>
                                <th>Pago</th>
                                <th>Sistema origen</th>
                            </tr>
                        </thead>
                        <tbody id="docs-body">
                            <tr class="empty-row"><td colspan="8">Selecciona un proveedor para cargar facturas pendientes</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="cq-foot">
            <div style="font-size:12px;color:#6b7280">Documentos seleccionados: <strong id="sel-count">0</strong></div>
            <div class="cq-total">Pago: $<span id="sel-total">0.00</span> {{ $poliza['moneda'] }}</div>
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

    function recalc() {
        let total = 0, count = 0;
        body.querySelectorAll('tr[data-id]').forEach(tr => {
            const chk = tr.querySelector('.chk-doc');
            const inp = tr.querySelector('.imp-doc');
            if (chk && chk.checked) {
                count++;
                total += Number(inp.value || 0);
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
            body.innerHTML = '<tr class="empty-row"><td colspan="8">Este proveedor no tiene facturas pendientes en Salcom</td></tr>';
            recalc();
            return;
        }
        body.innerHTML = items.map(it => `
            <tr data-id="${it.id}" data-serie="${it.serie}" data-folio="${it.folio}" data-concepto="${it.concepto}" data-total="${it.total}" data-moneda="${it.moneda}">
                <td><input type="checkbox" class="chk-doc" name="factura_ids[]" value="${it.id}" checked></td>
                <td>${it.fecha_fmt}</td>
                <td>${it.serie}</td>
                <td>${it.folio}</td>
                <td>${it.concepto}</td>
                <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${it.referencia || ''}">${it.referencia || '—'}</td>
                <td><input type="number" step="0.01" min="0" class="imp-doc" name="importes[${it.id}]" value="${Number(it.total).toFixed(2)}"></td>
                <td>${it.sistema_origen}</td>
            </tr>
        `).join('');

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
        });
        recalc();
    }

    async function loadFacturas(proveedorId) {
        body.innerHTML = '<tr class="empty-row"><td colspan="8">Cargando…</td></tr>';
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

            // Detalle lateral removido
            loadFacturas(select.value);
        } else {
            strip.classList.add('hidden');
            selectWrap.style.display = '';
            body.innerHTML = '<tr class="empty-row"><td colspan="8">Selecciona un proveedor para cargar facturas pendientes</td></tr>';
            recalc();
        }
    });

    // Clic en la franja para cambiar proveedor
    document.getElementById('prov-strip').addEventListener('dblclick', () => {
        document.getElementById('prov-strip').classList.add('hidden');
        document.getElementById('prov-select-wrap').style.display = '';
        select.focus();
    });

    document.getElementById('form-abono').addEventListener('submit', (e) => {
        const n = body.querySelectorAll('.chk-doc:checked').length;
        if (!n) {
            e.preventDefault();
            alert('Selecciona al menos una factura / compra a pagar.');
        }
    });

    if (select.value) {
        select.dispatchEvent(new Event('change'));
    }
})();
</script>
@endpush
