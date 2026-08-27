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

    .ab-header-card{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:0}
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

    .ab-tab{padding:10px 18px;font-size:13px;font-weight:600;color:var(--gray-muted);background:transparent;border:none;border-bottom:2px solid transparent;cursor:pointer;font-family:inherit}
    .ab-tab:hover{color:var(--purple)}
    .ab-tab.active{color:var(--purple);border-bottom-color:var(--purple);background:var(--white)}
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
    <div class="ab-header-card anim" style="border-radius:12px 12px 0 0">
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
                {{-- Hidden input para el form --}}
                <input type="hidden" name="codigo_proveedor" id="ab-proveedor" value="">
                {{-- Botón que abre modal --}}
                <button type="button" id="btn-abrir-prov" onclick="abrirModalProv()" style="text-align:left;padding:10px 14px;border:2px solid #dc2626;border-radius:8px;background:#faf5ff;font-size:13px;font-weight:600;color:#5b21b6;cursor:pointer;font-family:inherit;width:100%">
                    (Seleccionar proveedor)
                </button>
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

    {{-- Pestañas Generales / Información Adicional (pegadas a la card de arriba) --}}
    <div class="ab-header-card" style="margin-top:-1px;border-top:none;border-radius:0 0 12px 12px;margin-bottom:16px">
        <div style="display:flex;border-bottom:1px solid var(--border);background:var(--gray-soft)">
            <button type="button" class="ab-tab active" id="tab-generales" onclick="switchTab('generales')">2 Generales</button>
            <button type="button" class="ab-tab" id="tab-info" onclick="switchTab('info')">5 Información Adicional</button>
        </div>
        {{-- Panel Generales --}}
        <div id="panel-generales" style="padding:16px 22px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px 28px">
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase">Razón social</label>
                    <div id="info-razon" style="font-size:14px;font-weight:600;color:#111;margin-top:4px;padding:8px 12px;background:var(--gray-soft);border-radius:6px;min-height:36px">—</div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase">Cuenta</label>
                    <input type="text" name="cuenta_info" value="{{ old('cuenta_info', '(Ninguno)') }}" style="border:1.5px solid var(--border);border-radius:6px;padding:8px 12px;font-size:13px;font-family:inherit;width:100%;margin-top:4px">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase">Total</label>
                    <div id="info-total" style="font-size:18px;font-weight:800;color:var(--green);margin-top:4px;padding:8px 12px;background:var(--gray-soft);border-radius:6px">$0.00</div>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase">Saldo actual al 31 de diciembre de 2026</label>
                    <div style="font-size:13px;color:#6b7280;margin-top:4px;padding:8px 12px;background:var(--gray-soft);border-radius:6px">
                        <div>Saldo del Documento: <strong>$0.00</strong></div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Panel Información Adicional --}}
        <div id="panel-info" style="padding:16px 22px;display:none">
            <div style="font-size:12px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;margin-bottom:10px">Referencia y observaciones</div>
            <div style="display:grid;grid-template-columns:100px 1fr;gap:8px 14px;align-items:start">
                <label style="font-size:12px;font-weight:600;color:#374151;padding-top:8px">Referencia:</label>
                <input type="text" name="referencia" id="ab-referencia" value="{{ old('referencia') }}" placeholder="CK" style="border:1.5px solid var(--border);border-radius:6px;padding:8px 12px;font-size:13px;font-family:inherit;width:100%;max-width:400px">
                <label style="font-size:12px;font-weight:600;color:#374151;padding-top:8px">Observaciones:</label>
                <textarea name="observaciones" id="ab-observaciones" rows="3" placeholder="" style="border:1.5px solid var(--border);border-radius:6px;padding:8px 12px;font-size:13px;font-family:inherit;width:100%;max-width:400px;resize:vertical">{{ old('observaciones') }}</textarea>
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

{{-- Modal proveedor FUERA del form --}}
<div id="modal-prov" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:720px;max-height:75vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow:hidden;border:1px solid #e5e7eb;margin:auto">
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
                <tbody>
                    @foreach($proveedores as $p)
                        @php $rfc = is_array($p->datos_identificacion ?? null) ? ($p->datos_identificacion['rfc'] ?? '') : ''; @endphp
                        <tr class="prov-row" data-codigo="{{ $p->codigo }}" data-nombre="{{ $p->nombre }}" data-rfc="{{ $rfc }}" onclick="seleccionarProv(this)" style="cursor:pointer;border-bottom:1px solid #f3f4f6">
                            <td style="padding:10px 16px;font-weight:700;color:#6B3FA0">{{ $p->codigo }}</td>
                            <td style="padding:10px 16px;color:#111">{{ $p->nombre }}</td>
                            <td style="padding:10px 16px;font-size:12px;color:#6b7280;font-family:monospace">{{ $rfc }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

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
            abrirModalProv();
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

    // Función para cargar facturas (llamada desde seleccionarProv)
    window.cargarFacturasAbono = function(codigo) {
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
                var infoTotal = document.getElementById('info-total');
                if (infoTotal) infoTotal.textContent = '$' + totalPago.toLocaleString('en', {minimumFractionDigits:2});
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
    };

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
    var initCodigo = document.getElementById('ab-proveedor').value;
    if (initCodigo) {
        window.cargarFacturasAbono(initCodigo);
    }
})();

// ═══════════════════════════════════════════
// Tabs Generales / Info Adicional
// ═══════════════════════════════════════════
function switchTab(tab) {
    document.getElementById('panel-generales').style.display = tab === 'generales' ? '' : 'none';
    document.getElementById('panel-info').style.display = tab === 'info' ? '' : 'none';
    document.getElementById('tab-generales').classList.toggle('active', tab === 'generales');
    document.getElementById('tab-info').classList.toggle('active', tab === 'info');
}

// ═══════════════════════════════════════════
// Modal proveedor estilo Contpaqi
// ═══════════════════════════════════════════
function abrirModalProv() {
    var m = document.getElementById('modal-prov');
    m.style.display = 'flex';
    m.style.position = 'fixed';
    m.style.inset = '0';
    m.style.zIndex = '99999';
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
    var codigo = tr.dataset.codigo;
    var nombre = tr.dataset.nombre;
    // Setear hidden input
    document.getElementById('ab-proveedor').value = codigo;
    // Actualizar botón
    var btn = document.getElementById('btn-abrir-prov');
    btn.textContent = codigo + ' — ' + nombre;
    btn.style.color = '#111827';
    btn.style.borderColor = 'var(--green, #16a34a)';
    // Check visual
    var dot = document.getElementById('prov-dot');
    if (dot) dot.style.display = 'none';
    // Actualizar info adicional
    var infoRazon = document.getElementById('info-razon');
    if (infoRazon) infoRazon.textContent = nombre;
    // Cargar facturas
    if (window.cargarFacturasAbono) window.cargarFacturasAbono(codigo);
    // Cerrar modal
    cerrarModalProv();
    // Highlight
    document.querySelectorAll('.prov-row').forEach(function(r) { r.style.background = ''; });
    tr.style.background = '#dbeafe';
}
// Hover rows
document.querySelectorAll('.prov-row').forEach(function(tr) {
    tr.addEventListener('mouseenter', function() { this.style.background = '#f3e8ff'; });
    tr.addEventListener('mouseleave', function() { if (this.style.background !== 'rgb(219, 234, 254)') this.style.background = ''; });
});
// Cerrar solo con la X (no con click fuera ni ESC)

// ═══════════════════════════════════════════
// Navegación con Enter entre campos obligatorios
// Orden: Folio → Proveedor (abre modal)
// ═══════════════════════════════════════════
(function() {
    var poliza = document.getElementById('ab-poliza');
    var btnProv = document.getElementById('btn-abrir-prov');

    if (poliza) {
        poliza.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (poliza.value.trim() === '') {
                    alert('Escribe el número de póliza primero.');
                    return;
                }
                abrirModalProv();
            }
        });
    }
    if (btnProv) {
        btnProv.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                abrirModalProv();
            }
        });
    }
})();
</script>
@endpush
