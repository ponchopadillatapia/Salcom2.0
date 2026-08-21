@extends('layouts.admin')
@section('title', 'Abono al proveedor')
@section('hero')
<div class="hero-band">
    <h1>Abono al proveedor</h1>
    <p>Registro interno — póliza contable de pago</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .ab-header-card{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
    .ab-header-band{background:linear-gradient(135deg,#4a2078,#6B3FA0);padding:14px 22px;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
    .ab-header-band .ab-concepto{color:#fff;font-weight:800;font-size:15px;flex:1;min-width:180px}
    .ab-header-band .ab-field{display:flex;flex-direction:column;gap:2px}
    .ab-header-band .ab-field label{font-size:10px;font-weight:700;color:rgba(255,255,255,.7);text-transform:uppercase}
    .ab-header-band .ab-field input,.ab-header-band .ab-field select{border:1px solid rgba(255,255,255,.3);border-radius:6px;padding:6px 10px;font-size:13px;font-family:inherit;background:rgba(255,255,255,.95);color:#111;min-width:100px}
    .ab-header-band .ab-field input:focus,.ab-header-band .ab-field select:focus{outline:none;border-color:#fff;box-shadow:0 0 0 2px rgba(255,255,255,.4)}

    .ab-body{padding:18px 22px;display:grid;grid-template-columns:1fr 1fr;gap:16px 28px}
    .ab-body .ab-group{display:flex;flex-direction:column;gap:4px}
    .ab-body .ab-group label{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase}
    .ab-body .ab-group input,.ab-body .ab-group select{border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;background:var(--white)}
    .ab-body .ab-group input:focus,.ab-body .ab-group select:focus{outline:none;border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .ab-body .ab-group input[readonly]{background:var(--gray-soft);color:var(--gray-muted)}

    .ab-actions{padding:14px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}
    .btn-abono{padding:10px 24px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s}
    .btn-abono:hover{background:#5a2d8c}
    .btn-abono:disabled{opacity:.5;cursor:not-allowed}
    .btn-secondary{padding:10px 20px;background:var(--white);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;color:var(--gray-text)}

    .ab-facturas{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .ab-facturas-head{padding:14px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
    .ab-facturas-head h4{font-size:14px;font-weight:700;margin:0}
    .ab-facturas-meta{font-size:12px;color:var(--gray-muted)}
    .ab-table{width:100%;border-collapse:collapse}
    .ab-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;padding:12px 16px;text-align:left;border-bottom:1px solid var(--border)}
    .ab-table td{padding:14px 16px;font-size:13px;border-bottom:1px solid var(--border)}
    .ab-table tr:hover td{background:var(--purple-subtle)}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .empty-state{text-align:center;padding:40px;color:var(--gray-muted);font-size:14px}
    .total-row td{font-weight:700;background:var(--gray-soft)!important;border-top:2px solid var(--border)}

    @media(max-width:768px){.ab-body{grid-template-columns:1fr}.ab-header-band{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')

@if(session('ok'))
    <div class="anim" style="background:var(--green-bg);color:var(--green);border:1px solid var(--green);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ session('ok') }}</div>
@endif
@if(session('error'))
    <div class="anim" style="background:var(--red-bg);color:var(--red);border:1px solid var(--red);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('admin.abono-proveedor.confirmar') }}" id="form-abono">
    @csrf

    {{-- Encabezado estilo Contpaqi --}}
    <div class="ab-header-card anim">
        <div class="ab-header-band">
            <div class="ab-concepto">{{ $cuentaConfig['concepto'] ?? 'Abono Prov' }}</div>
            <div class="ab-field">
                <label>Fecha <span style="color:#dc2626;font-size:14px" id="fecha-dot">●</span></label>
                <input type="date" name="fecha" id="ab-fecha" value="{{ old('fecha', date('Y-m-d')) }}" required style="border:2px solid var(--green,#16a34a)" oninput="checkFecha()">
            </div>
            <div class="ab-field">
                <label>Serie</label>
                <input type="text" name="serie" value="{{ old('serie', $cuentaConfig['serie'] ?? '8969') }}" style="width:70px" placeholder="8969">
            </div>
            <div class="ab-field">
                <label>Folio / Nº Póliza <span style="color:#dc2626;font-size:14px" id="poliza-dot">●</span></label>
                <input type="text" name="poliza" id="ab-poliza" value="{{ old('poliza') }}" required placeholder="Obligatorio" style="width:140px;border:2px solid #dc2626;background:#fff;font-weight:700;font-size:14px" oninput="checkPoliza()">
            </div>
            <div class="ab-field">
                <label>Concepto</label>
                <input type="text" name="cuenta" value="{{ $cuentaConfig['titulo'] ?? '' }}" readonly style="min-width:160px;background:rgba(255,255,255,.8)">
                <input type="hidden" name="cuenta_key" value="{{ $cuentaKey }}">
            </div>
        </div>

        <div class="ab-body">
            <div class="ab-group">
                <label>Proveedor <span style="color:#dc2626;font-size:14px" id="prov-dot">●</span></label>
                <select name="codigo_proveedor" id="ab-proveedor" required style="border:2px solid #dc2626" onchange="checkProveedor()">
                    <option value="">(Seleccionar proveedor)</option>
                    @foreach($proveedores as $p)
                        <option value="{{ $p->codigo }}" {{ old('codigo_proveedor', request('proveedor')) === $p->codigo ? 'selected' : '' }}>
                            {{ $p->codigo }} — {{ $p->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="ab-group">
                <label>Moneda</label>
                <div style="display:flex;gap:10px">
                    <input type="text" name="moneda" id="ab-moneda" value="{{ old('moneda', $cuentaConfig['moneda'] ?? 'MXN') }}" style="flex:1" readonly>
                    <div style="display:flex;flex-direction:column;gap:2px;flex:1">
                        <label style="font-size:10px">Tipo de cambio</label>
                        <input type="text" name="tipo_cambio" id="ab-tc" value="{{ old('tipo_cambio', $cuentaConfig['tipo_cambio_default'] ?? '1.0000') }}" style="width:100%" {{ ($cuentaConfig['moneda'] ?? 'MXN') === 'MXN' ? 'readonly' : '' }}>
                    </div>
                </div>
            </div>
            <div class="ab-group">
                <label>Razón social</label>
                <input type="text" id="ab-razon" readonly value="" placeholder="Se llena al seleccionar proveedor">
            </div>
            <div class="ab-group">
                <label>Notas</label>
                <input type="text" name="notas" value="{{ old('notas') }}" placeholder="Notas (opcional)">
            </div>
        </div>
    </div>

    {{-- Facturas del proveedor seleccionado --}}
    <div class="ab-facturas anim" style="animation-delay:.05s" id="seccion-facturas">
        <div class="ab-facturas-head">
            <h4>Facturas del proveedor</h4>
            <span class="ab-facturas-meta" id="facturas-meta">Selecciona un proveedor arriba</span>
        </div>
        <div id="facturas-contenido">
            <div class="empty-state">Selecciona un proveedor para ver sus facturas.</div>
        </div>
    </div>

    <div class="ab-header-card anim" style="animation-delay:.08s;margin-top:16px">
        <div style="padding:16px 22px;display:flex;align-items:center;justify-content:space-between">
            <div style="font-size:14px;font-weight:700">Total: <span id="ab-total-display">$0.00</span></div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('admin.abono-proveedor', ['cuenta' => $cuentaKey]) }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-abono" id="btn-guardar" disabled>Guardar abono</button>
            </div>
        </div>
    </div>
</form>

{{-- Listado histórico de pólizas registradas (solo se muestra después de guardar) --}}
@if(session('ok') && $abonosRegistrados->count() > 0)
<div class="ab-facturas anim" style="animation-delay:.1s;margin-top:24px">
    <div class="ab-facturas-head">
        <h4>Pólizas registradas</h4>
        <span class="ab-facturas-meta">{{ $abonosRegistrados->count() }} abono{{ $abonosRegistrados->count() !== 1 ? 's' : '' }} registrados</span>
    </div>
    <table class="ab-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Nº Póliza</th>
                <th>Proveedor</th>
                <th>Folio factura</th>
                <th>Monto pagado</th>
                <th>Cuenta</th>
                <th>Registrado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($abonosRegistrados as $ab)
                @php
                    $detalle = is_array($ab->validacion_detalle) ? ($ab->validacion_detalle['abono_interno'] ?? []) : [];
                    $polizaNum = $detalle['poliza'] ?? '';
                    $fechaAbono = $detalle['fecha'] ?? '';
                    $cuentaAbono = $detalle['cuenta'] ?? '';
                    $serieAbono = $detalle['serie'] ?? '';
                @endphp
                <tr>
                    <td>{{ $fechaAbono ? \Carbon\Carbon::parse($fechaAbono)->format('d/m/Y') : '—' }}</td>
                    <td style="font-weight:700">
                        @if($polizaNum)
                            {{ $polizaNum }}
                        @else
                            <span style="color:#dc2626;font-size:12px">● Sin póliza</span>
                        @endif
                    </td>
                    <td style="font-weight:600">{{ $ab->codigo_proveedor }}</td>
                    <td>{{ $ab->folio_cfdi ?: $ab->id }}</td>
                    <td class="monto">${{ number_format((float)$ab->monto_pagado, 2) }}</td>
                    <td style="font-size:12px">{{ $cuentaAbono ?: '—' }}</td>
                    <td style="font-size:12px;color:var(--gray-muted)">{{ $ab->updated_at?->format('d/m/Y h:i a') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
@push('scripts')
<script>
(function(){
    // Validación visual de póliza
    window.checkPoliza = function() {
        var input = document.getElementById('ab-poliza');
        var dot = document.getElementById('poliza-dot');
        if (input.value.trim() !== '') {
            input.style.borderColor = 'var(--green, #16a34a)';
            dot.style.display = 'none';
        } else {
            input.style.borderColor = '#dc2626';
            dot.style.display = 'inline';
        }
    };

    window.checkFecha = function() {
        var input = document.getElementById('ab-fecha');
        var dot = document.getElementById('fecha-dot');
        if (input.value) {
            input.style.borderColor = 'var(--green, #16a34a)';
            dot.style.display = 'none';
        } else {
            input.style.borderColor = '#dc2626';
            dot.style.display = 'inline';
        }
    };

    window.checkProveedor = function() {
        var input = document.getElementById('ab-proveedor');
        var dot = document.getElementById('prov-dot');
        if (input.value) {
            input.style.borderColor = 'var(--green, #16a34a)';
            dot.style.display = 'none';
        } else {
            input.style.borderColor = '#dc2626';
            dot.style.display = 'inline';
        }
    };

    // Check al cargar
    checkPoliza();
    checkFecha();
    checkProveedor();

    // Bloquear envío si no hay póliza, proveedor o fecha
    document.getElementById('form-abono').addEventListener('submit', function(e) {
        var poliza = document.getElementById('ab-poliza').value.trim();
        var proveedor = document.getElementById('ab-proveedor').value;
        var fecha = document.getElementById('ab-fecha').value;

        if (!poliza) {
            e.preventDefault();
            alert('El número de póliza es obligatorio.');
            document.getElementById('ab-poliza').focus();
            return false;
        }
        if (!proveedor) {
            e.preventDefault();
            alert('Selecciona un proveedor.');
            document.getElementById('ab-proveedor').focus();
            return false;
        }
        if (!fecha) {
            e.preventDefault();
            alert('La fecha es obligatoria.');
            document.getElementById('ab-fecha').focus();
            return false;
        }
    });
    var provSelect = document.getElementById('ab-proveedor');
    var facturasContenido = document.getElementById('facturas-contenido');
    var facturasMeta = document.getElementById('facturas-meta');
    var totalDisplay = document.getElementById('ab-total-display');
    var btnGuardar = document.getElementById('btn-guardar');
    var razonInput = document.getElementById('ab-razon');

    // Datos de proveedores para razón social
    var proveedoresData = @json($proveedores->mapWithKeys(fn($p) => [$p->codigo => ['nombre' => $p->nombre, 'moneda' => $p->moneda ?? 'MXN']]));

    provSelect.addEventListener('change', function(){
        var codigo = this.value;
        if (!codigo) {
            facturasContenido.innerHTML = '<div class="empty-state">Selecciona un proveedor para ver sus facturas.</div>';
            facturasMeta.textContent = 'Selecciona un proveedor arriba';
            totalDisplay.textContent = '$0.00';
            btnGuardar.disabled = true;
            razonInput.value = '';
            return;
        }

        var pData = proveedoresData[codigo] || {};
        razonInput.value = pData.nombre || '';
        document.getElementById('ab-moneda').value = pData.moneda || 'MXN';
        if ((pData.moneda || 'MXN') === 'MXN') {
            document.getElementById('ab-tc').value = '1.0000';
        }

        // Cargar facturas por AJAX
        facturasMeta.textContent = 'Cargando...';
        fetch('/admin/abono-proveedor/facturas?codigo=' + encodeURIComponent(codigo))
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (!data.facturas || data.facturas.length === 0) {
                    facturasContenido.innerHTML = '<div class="empty-state">Este proveedor no tiene facturas pagadas pendientes de abono.</div>';
                    facturasMeta.textContent = '0 facturas';
                    totalDisplay.textContent = '$0.00';
                    btnGuardar.disabled = true;
                    return;
                }
                var html = '<table class="ab-table"><thead><tr>';
                html += '<th><input type="checkbox" id="chk-all" checked></th>';
                html += '<th>Folio</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Fecha pago</th>';
                html += '</tr></thead><tbody>';
                var totalPago = 0;
                data.facturas.forEach(function(f){
                    var saldo = Math.max(0, f.total - f.monto_pagado).toFixed(2);
                    totalPago += parseFloat(f.monto_pagado);
                    html += '<tr>';
                    html += '<td><input type="checkbox" name="factura_ids[]" value="'+f.id+'" class="chk-f" checked></td>';
                    html += '<td>'+(f.folio_cfdi || f.id)+'</td>';
                    html += '<td>$'+parseFloat(f.total).toLocaleString('en',{minimumFractionDigits:2})+'</td>';
                    html += '<td class="monto">$'+parseFloat(f.monto_pagado).toLocaleString('en',{minimumFractionDigits:2})+'</td>';
                    html += '<td>$'+parseFloat(saldo).toLocaleString('en',{minimumFractionDigits:2})+'</td>';
                    html += '<td style="font-size:12px;color:var(--gray-muted)">'+(f.fecha_pago || '—')+'</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                facturasContenido.innerHTML = html;
                facturasMeta.textContent = data.facturas.length + ' factura' + (data.facturas.length !== 1 ? 's' : '') + ' pagadas';
                totalDisplay.textContent = '$' + totalPago.toLocaleString('en', {minimumFractionDigits:2});
                btnGuardar.disabled = false;

                // Checkbox master
                document.getElementById('chk-all').addEventListener('change', function(){
                    var checked = this.checked;
                    document.querySelectorAll('.chk-f').forEach(function(c){ c.checked = checked; });
                    recalcTotal();
                });
                document.querySelectorAll('.chk-f').forEach(function(c){
                    c.addEventListener('change', recalcTotal);
                });
            })
            .catch(function(){
                facturasContenido.innerHTML = '<div class="empty-state">Error al cargar facturas.</div>';
                facturasMeta.textContent = 'Error';
            });
    });

    function recalcTotal(){
        var total = 0;
        document.querySelectorAll('.chk-f:checked').forEach(function(c){
            var tr = c.closest('tr');
            var monto = tr.querySelectorAll('td')[3].textContent.replace(/[^0-9.-]/g,'');
            total += parseFloat(monto) || 0;
        });
        totalDisplay.textContent = '$' + total.toLocaleString('en', {minimumFractionDigits:2});
        btnGuardar.disabled = document.querySelectorAll('.chk-f:checked').length === 0;
    }

    // Si ya viene proveedor seleccionado (por query param)
    if (provSelect.value) {
        provSelect.dispatchEvent(new Event('change'));
    }
})();
</script>
@endpush
