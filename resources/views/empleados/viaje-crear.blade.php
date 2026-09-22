<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Reembolso de Viaje — Portal Empleados</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --purple:#6d28d9; --purple-dark:#5b21b6; --gray-soft:#f4f4f7; --gray-text:#1d1d1f; --gray-muted:#86868b; --border:#e5e5ea; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-soft); color: var(--gray-text); font-size: 14px; }
        .topbar { background: #fff; border-bottom: 1px solid var(--border); padding: 14px 28px; }
        .topbar .brand { font-size: 13px; font-weight: 700; letter-spacing: 2px; color: var(--purple); text-transform: uppercase; }
        .wrap { max-width: 820px; margin: 0 auto; padding: 28px 20px 60px; }
        .back { font-size: 13px; color: var(--purple); font-weight: 600; text-decoration: none; display: inline-block; margin-bottom: 16px; }
        .card { background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 22px; margin-bottom: 18px; }
        .card h3 { font-size: 15px; font-weight: 700; margin-bottom: 16px; }
        .row { display: grid; gap: 14px; margin-bottom: 14px; }
        .row.c2 { grid-template-columns: 1fr 1fr; }
        .row.c3 { grid-template-columns: 1fr 1fr 1fr; }
        .group { display: flex; flex-direction: column; gap: 6px; }
        .group label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
        .group input, .group select, .group textarea { border: 1.5px solid var(--border); border-radius: 8px; padding: 10px 12px; font-size: 13px; font-family: inherit; width: 100%; box-sizing: border-box; outline: none; }
        .group input:focus, .group select:focus { border-color: var(--purple); }
        .group .hint { font-size: 11px; color: var(--gray-muted); }
        .gtable { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .gtable th { font-size: 10px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; padding: 8px; text-align: left; border-bottom: 1px solid var(--border); }
        .gtable td { padding: 8px; border-bottom: 1px solid var(--border); }
        .gtable input, .gtable select { padding: 8px; font-size: 13px; border: 1.5px solid var(--border); border-radius: 8px; width: 100%; box-sizing: border-box; }
        .btn-quitar { background: none; border: none; color: #dc2626; cursor: pointer; font-size: 16px; font-weight: 700; }
        .btn-add { padding: 8px 16px; background: var(--gray-soft); border: 1.5px dashed var(--border); border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--purple); cursor: pointer; font-family: inherit; }
        .total-box { background: var(--gray-soft); border-radius: 10px; padding: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
        .total-item { text-align: center; }
        .total-item .val { font-size: 20px; font-weight: 700; }
        .total-item .lbl { font-size: 11px; color: var(--gray-muted); text-transform: uppercase; }
        .actions { display: flex; justify-content: flex-end; gap: 12px; }
        .btn-cancel { padding: 12px 20px; border: 1.5px solid var(--border); border-radius: 10px; background: #fff; font-size: 13px; font-weight: 600; color: var(--gray-muted); text-decoration: none; }
        .btn-save { padding: 12px 24px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; }
        .err-box { background:#fef2f2;border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:16px;color:#991b1b;font-size:12px; }
        @media(max-width:768px){ .row.c2,.row.c3 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="topbar"><div class="brand">Industrias Salcom · Portal Empleados</div></div>
    <div class="wrap">
        <a href="{{ route('empleados.portal') }}" class="back">← Volver al portal</a>

        @if($errors->any())
        <div class="err-box"><ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('empleados.viaje.guardar') }}" id="formViaje">
            @csrf

            <div class="card">
                <h3>Datos del viaje</h3>
                <div class="row c2">
                    <div class="group">
                        <label>Empleado</label>
                        <input type="text" value="{{ session('empleado_nombre') }} ({{ session('empleado_numero') }})" readonly style="background:var(--gray-soft);">
                    </div>
                    <div class="group">
                        <label>Departamento</label>
                        <input type="text" value="{{ session('empleado_departamento') ?: '—' }}" readonly style="background:var(--gray-soft);">
                    </div>
                </div>
                <div class="row c2">
                    <div class="group">
                        <label for="fecha_salida">Fecha de salida *</label>
                        <input type="date" id="fecha_salida" name="fecha_salida" required value="{{ old('fecha_salida') }}">
                    </div>
                    <div class="group">
                        <label for="fecha_regreso">Fecha de regreso *</label>
                        <input type="date" id="fecha_regreso" name="fecha_regreso" required value="{{ old('fecha_regreso') }}">
                        <span class="hint">Tienes 3 días desde el regreso para subir tus facturas.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>Destino y Moneda</h3>
                <div class="row c3">
                    <div class="group">
                        <label for="pais_destino">País / Región *</label>
                        <select id="pais_destino" name="pais_destino" required>
                            <option value="" disabled selected>Selecciona</option>
                            @foreach($paises as $pais => $info)
                                <option value="{{ $pais }}" data-moneda="{{ $info['moneda'] }}">{{ $pais }} ({{ $info['moneda'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="group">
                        <label for="moneda_destino">Moneda</label>
                        <input type="text" id="moneda_destino" name="moneda_destino" readonly style="background:var(--gray-soft);">
                    </div>
                    <div class="group">
                        <label for="tipo_cambio">Tipo de cambio a MXN *</label>
                        <input type="number" id="tipo_cambio" name="tipo_cambio" step="0.0001" min="0.0001" required placeholder="Ej: 17.50">
                        <span class="hint">Cuántos MXN vale 1 unidad de la moneda destino.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>Gastos del Viaje</h3>
                <table class="gtable">
                    <thead>
                        <tr>
                            <th style="width:35%;">Concepto</th>
                            <th style="width:25%;">Monto (<span id="lblMoneda">local</span>)</th>
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
                <button type="button" class="btn-add" onclick="agregarFila()">+ Agregar concepto</button>

                <div class="total-box">
                    <div class="total-item"><div class="val" id="totalLocal">0.00</div><div class="lbl">Total <span id="totalMonedaLbl">local</span></div></div>
                    <div class="total-item"><div class="val" id="totalMXN">$0.00</div><div class="lbl">Total MXN</div></div>
                </div>
            </div>

            <div class="card">
                <h3>Notas</h3>
                <div class="group">
                    <textarea name="notas" rows="3" placeholder="Detalles del viaje...">{{ old('notas') }}</textarea>
                </div>
            </div>

            <div class="actions">
                <a href="{{ route('empleados.portal') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">Guardar borrador</button>
            </div>
        </form>
    </div>

<script>
(function() {
    var paisSelect = document.getElementById('pais_destino');
    var monedaInput = document.getElementById('moneda_destino');
    var tipoCambioInput = document.getElementById('tipo_cambio');
    var lblMoneda = document.getElementById('lblMoneda');
    var totalMonedaLbl = document.getElementById('totalMonedaLbl');
    var filaIdx = 1;

    paisSelect.addEventListener('change', function() {
        var moneda = this.options[this.selectedIndex].dataset.moneda || '';
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
        if (tbody.children.length > 1) { btn.closest('tr').remove(); recalcular(); }
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
            var baseEl = el.closest('tr').querySelector('.monto-base');
            if (baseEl) baseEl.value = '$' + (val * tc).toLocaleString('en-US', {minimumFractionDigits:2});
        });
        document.getElementById('totalLocal').textContent = totalL.toLocaleString('en-US', {minimumFractionDigits:2});
        document.getElementById('totalMXN').textContent = '$' + (totalL * tc).toLocaleString('en-US', {minimumFractionDigits:2});
    }
    bindMontos();
})();
</script>
</body>
</html>
