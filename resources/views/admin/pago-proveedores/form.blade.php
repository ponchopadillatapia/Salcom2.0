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
    .cq-head{display:grid;grid-template-columns:repeat(4,1fr);gap:10px 14px;padding:14px;background:#fff;border-bottom:1px solid #e5e7eb}
    .cq-field{display:flex;flex-direction:column;gap:3px}
    .cq-field label{font-size:11px;font-weight:700;color:#6b7280}
    .cq-field input,.cq-field select{border:1px solid #9ca3af;border-radius:3px;padding:6px 8px;font-size:13px;font-family:inherit;background:#fff}
    .cq-field input:focus,.cq-field select:focus{outline:2px solid #a78bfa;border-color:#7c3aed}
    .cq-field.span2{grid-column:span 2}
    .cq-body{display:grid;grid-template-columns:1.6fr .9fr;min-height:420px;background:#fff}
    .cq-main{border-right:1px solid #e5e7eb;display:flex;flex-direction:column}
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
    .cq-side{padding:12px;background:#fafafa}
    .cq-side h4{margin:0 0 10px;font-size:13px;font-weight:800;color:#374151}
    .cq-side .kv{display:grid;grid-template-columns:110px 1fr;gap:4px 8px;font-size:12px;margin-bottom:12px}
    .cq-side .kv span:first-child{color:#6b7280;font-weight:600}
    .cq-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 14px;background:#f9fafb;border-top:1px solid #e5e7eb}
    .cq-total{font-size:18px;font-weight:800;color:#166534;font-variant-numeric:tabular-nums}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .back{display:inline-flex;margin-bottom:12px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    .empty-row td{text-align:center;color:#9ca3af;padding:28px!important}
    @media(max-width:960px){
        .cq-head{grid-template-columns:1fr 1fr}
        .cq-body{grid-template-columns:1fr}
        .cq-main{border-right:none;border-bottom:1px solid #e5e7eb}
    }
</style>
@endpush
@section('content')
<a class="back" href="{{ route('admin.pago-proveedores.nuevo') }}">← Cambiar póliza</a>

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
            <a class="cq-tool" href="{{ route('admin.pago-proveedores.nuevo') }}" title="Nuevo">
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
            <div class="cq-field span2">
                <label>Concepto</label>
                <input type="text" value="{{ $poliza['concepto'] }}" readonly>
            </div>
            <div class="cq-field">
                <label>Fecha</label>
                <input type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" required>
            </div>
            <div class="cq-field">
                <label>Serie / Folio</label>
                <input type="text" value="{{ $poliza['serie'] }} — {{ $folioSiguiente }}" readonly>
            </div>
            <div class="cq-field span2">
                <label>Proveedor</label>
                <select name="proveedor_id" id="proveedor_id" required>
                    <option value="">— Seleccionar —</option>
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
                            @selected((string) old('proveedor_id') === (string) $p->id)>
                            {{ $cod }} — {{ $p->nombre }} [{{ $mon }}]
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="cq-field">
                <label>Moneda</label>
                <input type="text" value="{{ $poliza['moneda_label'] }} ({{ $poliza['moneda'] }})" readonly>
            </div>
            <div class="cq-field">
                <label>Tipo de cambio</label>
                <input type="number" step="0.000001" min="0" name="tipo_cambio" id="tipo_cambio"
                       value="{{ old('tipo_cambio', $poliza['tipo_cambio_default'] ?? '') }}"
                       placeholder="{{ $poliza['moneda'] === 'USD' ? 'Ej. 17.9042' : '1' }}"
                       @if($poliza['moneda'] === 'MXN') required @endif>
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
            <aside class="cq-side">
                <h4>Detalle documento</h4>
                <div class="kv" id="detalle-doc">
                    <span>Proveedor</span><span id="d-prov">—</span>
                    <span>Banco</span><span id="d-banco">—</span>
                    <span>CLABE</span><span id="d-clabe">—</span>
                    <span>Serie/Folio</span><span id="d-folio">—</span>
                    <span>Concepto</span><span id="d-concepto">—</span>
                    <span>Moneda</span><span id="d-moneda">{{ $poliza['moneda_label'] }}</span>
                    <span>Total doc.</span><span id="d-total">—</span>
                </div>
                <p style="font-size:11px;color:#6b7280;margin:0;line-height:1.4">
                    Marca las facturas a saldar. El importe de pago se puede ajustar (igual que en Contpaqi).
                </p>
            </aside>
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
        if (!tr) return;
        document.getElementById('d-folio').textContent = (tr.dataset.serie || '') + ' / ' + (tr.dataset.folio || '');
        document.getElementById('d-concepto').textContent = tr.dataset.concepto || 'Compra';
        document.getElementById('d-total').textContent = '$' + fmt(tr.dataset.total) + ' ' + (tr.dataset.moneda || '');
    }

    function renderItems(items) {
        if (!items.length) {
            body.innerHTML = '<tr class="empty-row"><td colspan="8">Este proveedor no tiene facturas pendientes en Salcom</td></tr>';
            recalc();
            return;
        }
        body.innerHTML = items.map(it => `
            <tr data-id="${it.id}" data-serie="${it.serie}" data-folio="${it.folio}" data-concepto="${it.concepto}" data-total="${it.total}" data-moneda="${it.moneda}">
                <td><input type="checkbox" class="chk-doc" name="factura_ids[]" value="${it.id}"></td>
                <td>${it.fecha_fmt}</td>
                <td>${it.serie}</td>
                <td>${it.folio}</td>
                <td>${it.concepto}</td>
                <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${it.referencia || ''}">${it.referencia || '—'}</td>
                <td><input type="number" step="0.01" min="0" class="imp-doc" name="importes[${it.id}]" value="${Number(it.total).toFixed(2)}" disabled></td>
                <td>${it.sistema_origen}</td>
            </tr>
        `).join('');

        body.querySelectorAll('tr[data-id]').forEach(tr => {
            const chk = tr.querySelector('.chk-doc');
            const inp = tr.querySelector('.imp-doc');
            chk.addEventListener('change', () => {
                inp.disabled = !chk.checked;
                if (chk.checked) showDetalle(tr);
                recalc();
            });
            inp.addEventListener('input', recalc);
            tr.addEventListener('click', (e) => {
                if (e.target.matches('input')) return;
                showDetalle(tr);
            });
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
        document.getElementById('d-prov').textContent = (opt.dataset.nombre || '—') + (opt.dataset.moneda ? ' [' + opt.dataset.moneda + ']' : '');
        document.getElementById('d-banco').textContent = opt.dataset.banco || '—';
        document.getElementById('d-clabe').textContent = opt.dataset.clabe || '—';
        if (select.value) loadFacturas(select.value);
        else {
            body.innerHTML = '<tr class="empty-row"><td colspan="8">Selecciona un proveedor para cargar facturas pendientes</td></tr>';
            recalc();
        }
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
