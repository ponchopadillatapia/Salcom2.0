@extends('layouts.admin')
@section('title', 'Nueva Solicitud de Reembolso de Viaje')

@push('styles')
<style>
    .rv-wrap { max-width: 880px; }
    .rv-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 24px; margin-bottom: 20px; }
    .rv-card h3 { font-size: 15px; font-weight: 700; color: var(--gray-text); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .rv-row { display: grid; gap: 16px; margin-bottom: 16px; }
    .rv-row.cols-2 { grid-template-columns: 1fr 1fr; }
    .rv-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
    .rv-row.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
    .rv-group { display: flex; flex-direction: column; gap: 6px; }
    .rv-group label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
    .rv-group input, .rv-group select, .rv-group textarea {
        border: 1.5px solid var(--border); border-radius: 8px; padding: 10px 14px;
        font-size: 13px; font-family: inherit; color: var(--gray-text); outline: none;
        background: var(--white); width: 100%; box-sizing: border-box;
    }
    .rv-group input:focus, .rv-group select:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(107,63,160,.1); }
    .rv-group .hint { font-size: 11px; color: var(--gray-muted); }

    .gastos-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .gastos-table th { font-size: 11px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; padding: 8px 10px; text-align: left; border-bottom: 1px solid var(--border-light); }
    .gastos-table td { padding: 8px 10px; border-bottom: 1px solid var(--border-light); }
    .gastos-table input, .gastos-table select { padding: 8px 10px; font-size: 13px; border: 1.5px solid var(--border); border-radius: 8px; width: 100%; box-sizing: border-box; }
    .gastos-table .btn-quitar { background: none; border: none; color: #dc2626; cursor: pointer; font-size: 16px; font-weight: 700; }

    .btn-agregar { padding: 8px 16px; background: var(--gray-soft); border: 1.5px dashed var(--border); border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--purple); cursor: pointer; font-family: inherit; }
    .btn-agregar:hover { background: var(--purple-subtle); border-color: var(--purple); }

    .rv-total-box { background: var(--gray-soft); border-radius: 10px; padding: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .rv-total-item { text-align: center; }
    .rv-total-item .val { font-size: 20px; font-weight: 700; color: var(--gray-text); }
    .rv-total-item .lbl { font-size: 11px; color: var(--gray-muted); text-transform: uppercase; }

    .btn-guardar { padding: 12px 28px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-guardar:hover { background: var(--purple-dark); }
    @media(max-width:768px) { .rv-row.cols-2,.rv-row.cols-3,.rv-row.cols-4 { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="rv-wrap">
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:16px;">
        <ul style="margin:0;padding:0 0 0 16px;color:#991b1b;font-size:12px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.reembolsos-viaje.guardar') }}" enctype="multipart/form-data" id="formViaje">
        @csrf

        {{-- Identificación del empleado --}}
        <div class="rv-card">
            <h3>Identificación del Empleado</h3>
            <div class="rv-row cols-3">
                <div class="rv-group">
                    <label for="codigo_empleado">Código de empleado *</label>
                    <input type="text" id="codigo_empleado" name="codigo_empleado" required placeholder="Ej: EMP-001" value="{{ old('codigo_empleado') }}">
                </div>
                <div class="rv-group">
                    <label for="nombre_empleado">Nombre completo *</label>
                    <input type="text" id="nombre_empleado" name="nombre_empleado" required placeholder="Se autocompleta o escribe" value="{{ old('nombre_empleado') }}">
                </div>
                <div class="rv-group">
                    <label for="departamento">Departamento / Rol</label>
                    <input type="text" id="departamento" name="departamento" placeholder="Ej: Ventas, Promotor" value="{{ old('departamento') }}">
                </div>
            </div>
        </div>

        {{-- Destino y moneda --}}
        <div class="rv-card">
            <h3>Destino y Moneda</h3>
            <div class="rv-row cols-3">
                <div class="rv-group">
                    <label for="pais_destino">País / Región de destino *</label>
                    <select id="pais_destino" name="pais_destino" required>
                        <option value="" disabled selected>Selecciona</option>
                        @foreach($paises as $pais => $info)
                            <option value="{{ $pais }}" data-moneda="{{ $info['moneda'] }}" data-simbolo="{{ $info['simbolo'] }}">{{ $pais }} ({{ $info['moneda'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="rv-group">
                    <label for="moneda_destino">Moneda del destino</label>
                    <input type="text" id="moneda_destino" name="moneda_destino" readonly style="background:var(--gray-soft);" value="{{ old('moneda_destino') }}">
                </div>
                <div class="rv-group">
                    <label for="tipo_cambio">Tipo de cambio a MXN *</label>
                    <input type="number" id="tipo_cambio" name="tipo_cambio" step="0.0001" min="0.0001" required placeholder="Ej: 0.0045" value="{{ old('tipo_cambio') }}">
                    <span class="hint">¿Cuántos MXN vale 1 unidad de la moneda destino?</span>
                </div>
            </div>
        </div>

        {{-- Gastos dinámicos --}}
        <div class="rv-card">
            <h3>Gastos del Viaje</h3>
            <table class="gastos-table" id="tablGastos">
                <thead>
                    <tr>
                        <th style="width:35%;">Concepto</th>
                        <th style="width:25%;">Monto (<span id="lblMoneda">moneda local</span>)</th>
                        <th style="width:25%;">Equivalente MXN</th>
                        <th style="width:15%;"></th>
                    </tr>
                </thead>
                <tbody id="gastosBody">
                    <tr>
                        <td>
                            <select name="gastos[0][concepto]" required>
                                <option value="" disabled selected>Concepto</option>
                                @foreach($conceptos as $k => $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach
                            </select>
                        </td>
                        <td><input type="number" name="gastos[0][monto_local]" step="0.01" min="0" required placeholder="0.00" class="monto-local"></td>
                        <td><input type="text" class="monto-base" readonly style="background:var(--gray-soft);" value="$0.00"></td>
                        <td><button type="button" class="btn-quitar" onclick="quitarFila(this)">×</button></td>
                    </tr>
                </tbody>
            </table>
            <button type="button" class="btn-agregar" onclick="agregarFila()">+ Agregar concepto</button>

            <div class="rv-total-box" style="margin-top:16px;">
                <div class="rv-total-item">
                    <div class="val" id="totalLocal">0.00</div>
                    <div class="lbl">Total <span id="totalMonedaLbl">moneda local</span></div>
                </div>
                <div class="rv-total-item">
                    <div class="val" id="totalMXN">$0.00</div>
                    <div class="lbl">Total MXN (reembolso)</div>
                </div>
            </div>
        </div>

        {{-- Comprobantes y Facturas --}}
        <div class="rv-card">
            <h3>Facturas y Comprobantes</h3>
            <div class="rv-row cols-3">
                <div class="rv-group">
                    <label for="factura_pdf">Factura PDF</label>
                    <input type="file" id="factura_pdf" name="factura_pdf" accept=".pdf" style="padding:8px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;">
                    <span class="hint">PDF de la factura firmada</span>
                </div>
                <div class="rv-group">
                    <label for="factura_xml">Factura XML</label>
                    <input type="file" id="factura_xml" name="factura_xml" accept=".xml" style="padding:8px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;">
                    <span class="hint">Archivo XML del CFDI</span>
                </div>
                <div class="rv-group">
                    <label for="archivo_comprobantes">Tickets / Comprobantes</label>
                    <input type="file" id="archivo_comprobantes" name="archivo_comprobantes" accept=".pdf,.jpg,.jpeg,.png,.zip" style="padding:8px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;">
                    <span class="hint">PDF, imagen o ZIP (máx 20 MB)</span>
                </div>
            </div>
            <div class="rv-row cols-2" style="margin-top:12px;">
                <div class="rv-group">
                    <label for="notas">Notas / Observaciones</label>
                    <textarea id="notas" name="notas" rows="3" placeholder="Detalles del viaje, aprobaciones previas...">{{ old('notas') }}</textarea>
                </div>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:12px;">
            <a href="{{ route('admin.reembolsos-viaje') }}" style="padding:12px 20px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;font-weight:600;color:var(--gray-text);text-decoration:none;">Cancelar</a>
            <button type="submit" class="btn-guardar">Guardar como borrador</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var paisSelect = document.getElementById('pais_destino');
    var monedaInput = document.getElementById('moneda_destino');
    var tipoCambioInput = document.getElementById('tipo_cambio');
    var lblMoneda = document.getElementById('lblMoneda');
    var totalMonedaLbl = document.getElementById('totalMonedaLbl');
    var filaIdx = 1;

    paisSelect.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var moneda = opt.dataset.moneda || '';
        monedaInput.value = moneda;
        lblMoneda.textContent = moneda;
        totalMonedaLbl.textContent = moneda;
    });

    tipoCambioInput.addEventListener('input', recalcular);

    window.agregarFila = function() {
        var conceptos = @json($conceptos);
        var opts = '<option value="" disabled selected>Concepto</option>';
        for (var k in conceptos) { opts += '<option value="' + conceptos[k] + '">' + conceptos[k] + '</option>'; }
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><select name="gastos[' + filaIdx + '][concepto]" required>' + opts + '</select></td>'
            + '<td><input type="number" name="gastos[' + filaIdx + '][monto_local]" step="0.01" min="0" required placeholder="0.00" class="monto-local"></td>'
            + '<td><input type="text" class="monto-base" readonly style="background:var(--gray-soft);" value="$0.00"></td>'
            + '<td><button type="button" class="btn-quitar" onclick="quitarFila(this)">×</button></td>';
        document.getElementById('gastosBody').appendChild(tr);
        filaIdx++;
        bindMontos();
    };

    window.quitarFila = function(btn) {
        var tbody = document.getElementById('gastosBody');
        if (tbody.children.length > 1) {
            btn.closest('tr').remove();
            recalcular();
        }
    };

    function bindMontos() {
        document.querySelectorAll('.monto-local').forEach(function(el) {
            el.removeEventListener('input', recalcular);
            el.addEventListener('input', recalcular);
        });
    }

    function recalcular() {
        var tc = parseFloat(tipoCambioInput.value) || 0;
        var totalL = 0;
        document.querySelectorAll('.monto-local').forEach(function(el) {
            var val = parseFloat(el.value) || 0;
            totalL += val;
            var base = (val * tc).toFixed(2);
            var baseEl = el.closest('tr').querySelector('.monto-base');
            if (baseEl) baseEl.value = '$' + Number(base).toLocaleString('en-US', {minimumFractionDigits:2});
        });
        document.getElementById('totalLocal').textContent = totalL.toLocaleString('en-US', {minimumFractionDigits:2});
        document.getElementById('totalMXN').textContent = '$' + (totalL * tc).toLocaleString('en-US', {minimumFractionDigits:2});
    }

    bindMontos();
})();
</script>
@endpush
