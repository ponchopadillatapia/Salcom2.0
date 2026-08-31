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
    <div id="toast-ok" style="position:fixed;top:20px;right:20px;z-index:100000;background:#16a34a;color:#fff;border-radius:12px;padding:16px 22px;font-size:14px;font-weight:600;box-shadow:0 10px 40px rgba(22,163,74,.4);max-width:420px;display:flex;align-items:center;gap:12px">
        <span style="font-size:20px">✓</span>
        <span>{{ session('ok') }}</span>
    </div>
    <div class="anim" style="background:var(--green-bg);color:var(--green);border:1px solid var(--green);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ session('ok') }}</div>
    <script>
        setTimeout(function(){ var t=document.getElementById('toast-ok'); if(t){ t.style.transition='opacity .5s'; t.style.opacity='0'; setTimeout(function(){t.remove();}, 500); } }, 5000);
    </script>
@endif
@if(session('error'))
    <div style="position:fixed;top:20px;right:20px;z-index:100000;background:#dc2626;color:#fff;border-radius:12px;padding:16px 22px;font-size:14px;font-weight:600;box-shadow:0 10px 40px rgba(220,38,38,.4);max-width:420px">{{ session('error') }}</div>
    <div class="anim" style="background:var(--red-bg);color:var(--red);border:1px solid var(--red);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="anim" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">
        <strong>Revisa estos campos:</strong>
        <ul style="margin:6px 0 0;padding-left:18px">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Barra superior: cuenta activa + acceso al historial de esta cuenta --}}
<div class="anim" style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:14px">
    <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase">Cuenta</span>
        <span style="font-size:15px;font-weight:800;color:{{ $cuentaConfig['color'] ?? '#6B3FA0' }}">{{ $cuentaConfig['titulo'] ?? 'Abono' }}</span>
        <span style="font-size:12px;color:var(--gray-muted)">· {{ $cuentaConfig['moneda_label'] ?? '' }}</span>
    </div>
    <a href="{{ route('admin.historial-abonos', ['cuenta' => $cuentaKey]) }}" style="display:inline-flex;align-items:center;gap:8px;padding:9px 18px;background:#fff;border:1.5px solid var(--purple);border-radius:8px;font-size:13px;font-weight:700;color:var(--purple);text-decoration:none">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
        Ver historial de esta cuenta
    </a>
</div>

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
                @php
                    $esMXN = ($cuentaConfig['moneda'] ?? 'MXN') === 'MXN';
                    $monVal = $cuentaConfig['moneda'] ?? 'MXN';
                    $monNombre = $monVal === 'USD' ? 'DÓLAR AMERICANO' : ($monVal === 'MXN' ? 'PESO MEXICANO' : $monVal);
                @endphp
                <div style="border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;background:var(--white)">
                    <input type="hidden" name="moneda" id="ab-moneda" value="{{ old('moneda', $monVal) }}">
                    {{-- Línea Moneda (editable en las 4 cuentas) --}}
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                        <span style="font-size:13px;font-weight:600;color:var(--gray-muted);min-width:105px">Moneda:</span>
                        <select id="ab-moneda-select" onchange="cambiarMoneda(this.value)" style="border:1px solid var(--border);border-radius:5px;padding:5px 10px;font-size:14px;font-weight:700;color:#111;font-family:inherit;background:#fff">
                            <option value="MXN" {{ $monVal === 'MXN' ? 'selected' : '' }}>PESO MEXICANO (MXN)</option>
                            <option value="USD" {{ $monVal === 'USD' ? 'selected' : '' }}>DÓLAR AMERICANO (USD)</option>
                        </select>
                        <span style="font-size:14px;font-weight:700;color:#111;display:none" id="ab-moneda-label">{{ $monNombre }}</span>
                    </div>
                    {{-- Línea Tipo de cambio --}}
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:13px;font-weight:600;color:var(--gray-muted);min-width:105px">Tipo de cambio:</span>
                        <input type="text" name="tipo_cambio" id="ab-tc" inputmode="decimal"
                            value="{{ old('tipo_cambio', $esMXN ? '1.0000' : ($cuentaConfig['tipo_cambio_default'] ?? '')) }}"
                            placeholder="{{ $esMXN ? '1.0000' : '17.9042' }}"
                            style="flex:1;max-width:130px;border:1px solid var(--border);border-radius:5px;padding:5px 10px;font-size:14px;font-weight:600;font-family:inherit;{{ $esMXN ? 'color:var(--gray-muted);background:var(--gray-soft)' : 'color:#111;background:#fff' }}"
                            {{ $esMXN ? 'readonly' : '' }}>
                        {{-- Botón F3 para editar --}}
                        <button type="button" id="ab-tc-f3" onclick="toggleTC()" title="Editar tipo de cambio" style="border:1px solid #6B3FA0;background:#faf5ff;color:#5b21b6;border-radius:5px;padding:5px 12px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit">F3</button>
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
                    <input type="text" name="cuenta_info" id="ab-cuenta" value="{{ old('cuenta_info', '(Ninguno)') }}" style="border:1.5px solid var(--border);border-radius:6px;padding:8px 12px;font-size:13px;font-family:inherit;width:100%;margin-top:4px">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase">Total</label>
                    <input type="text" name="total_manual" id="ab-total-input" inputmode="decimal" value="{{ old('total_manual') }}" placeholder="0.00" style="border:1.5px solid var(--border);border-radius:6px;padding:8px 12px;font-size:16px;font-weight:800;color:var(--green);font-family:inherit;width:100%;margin-top:4px">
                </div>
                <div>
                    <label style="font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase">Saldos</label>
                    <div style="margin-top:4px;display:flex;flex-direction:column;gap:8px">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:12px;color:#374151;flex:1">Saldo actual al 31 de diciembre de 2026:</span>
                            <input type="text" name="saldo_actual" id="ab-saldo-actual" value="{{ old('saldo_actual', '0.00') }}" style="width:110px;text-align:right;border:1px solid var(--border);border-radius:5px;padding:5px 10px;font-size:13px;font-weight:600;font-family:inherit">
                        </div>
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="font-size:12px;color:#374151;flex:1">Saldo del Documento:</span>
                            <input type="text" name="saldo_documento" id="ab-saldo-doc" value="{{ old('saldo_documento', '0.00') }}" style="width:110px;text-align:right;border:1px solid var(--border);border-radius:5px;padding:5px 10px;font-size:13px;font-weight:600;font-family:inherit">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Panel Información Adicional --}}
        <div id="panel-info" style="padding:16px 22px;display:none">
            <div style="font-size:12px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;margin-bottom:10px">Referencia y observaciones</div>
            <div style="display:grid;grid-template-columns:100px 1fr;gap:8px 14px;align-items:start">
                <label style="font-size:12px;font-weight:600;color:#374151;padding-top:8px">Referencia:</label>
                <input type="text" name="referencia" id="ab-referencia" value="{{ old('referencia') }}" placeholder="Nº de orden / CK" style="border:1.5px solid var(--border);border-radius:6px;padding:8px 12px;font-size:13px;font-family:inherit;width:100%;max-width:400px">
                <label style="font-size:12px;font-weight:600;color:#374151;padding-top:8px">Observaciones:</label>
                <textarea name="observaciones" id="ab-observaciones" rows="3" placeholder="" style="border:1.5px solid var(--border);border-radius:6px;padding:8px 12px;font-size:13px;font-family:inherit;width:100%;max-width:400px;resize:vertical">{{ old('observaciones') }}</textarea>
            </div>
            @php $esExtranjera = in_array($cuentaKey ?? '', ['2026_base', '2026_extranjera'], true); @endphp
            @if($esExtranjera)
            <div style="margin-top:14px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:12px;color:#92400e">
                Si el proveedor aún no tiene la orden en el sistema, se guardará como <strong>anticipo</strong> (pon el Nº de orden en Referencia).
            </div>
            @endif

            {{-- Botón GUARDAR (decide abono o anticipo) --}}
            <div style="margin-top:18px;display:flex;justify-content:flex-end">
                <button type="button" id="btn-guardar-abono" onclick="guardarAbono()" style="padding:11px 32px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit">Guardar</button>
            </div>
        </div>
    </div>

    {{-- Contenedor oculto: aquí se inyectan los IDs de las facturas asociadas en la ventana Saldar --}}
    <div id="facturas-asociadas-inputs" style="display:none"></div>

    {{-- Total display (referencia interna, oculto) --}}
    <span id="ab-total-display" style="display:none">$0.00</span>

    {{-- Botón submit oculto (el guardado se dispara desde Información Adicional) --}}
    <button type="submit" id="btn-guardar" style="display:none" disabled></button>
</form>

{{-- Ventanita: Capture el importe a asociar --}}
<div id="modal-importe" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.4);z-index:100001;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:10px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;border:1px solid #e5e7eb">
        <div style="background:#6B3FA0;padding:10px 16px;color:#fff;font-size:13px;font-weight:700">Capture el importe a asociar</div>
        <div style="padding:16px">
            {{-- Leyenda de conversión (solo se muestra si el documento es en dólares) --}}
            <div id="imp-leyenda" style="display:none;background:#f8f7fc;border:1px solid #e9d5ff;border-radius:8px;padding:10px 12px;margin-bottom:14px;font-size:11.5px;color:#374151;line-height:1.7"></div>

            {{-- Tipo de cambio editable (solo dólares) --}}
            <div id="imp-tc-wrap" style="display:none;align-items:center;gap:8px;margin-bottom:12px">
                <label style="font-size:12px;font-weight:600;color:#5b21b6;white-space:nowrap">Tipo de cambio:</label>
                <input type="text" id="imp-tc-input" inputmode="decimal" oninput="recalcularImporte()" style="width:110px;border:1.5px solid #c4b5fd;border-radius:6px;padding:6px 10px;font-size:14px;font-weight:600;text-align:right;font-family:inherit;color:#111">
            </div>

            <label style="font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;display:block;margin-bottom:4px">Importe a asociar</label>
            <input type="text" id="imp-asociar-input" inputmode="decimal" onkeydown="if(event.key==='Enter'){event.preventDefault();confirmarImporte();}" style="width:100%;border:1.5px solid #c4b5fd;border-radius:6px;padding:10px 12px;font-size:16px;font-weight:700;text-align:right;font-family:inherit;color:#111">
            <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
                <button type="button" onclick="cerrarImporte()" style="padding:8px 18px;background:#fff;border:1.5px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;color:#374151">Cancel</button>
                <button type="button" onclick="confirmarImporte()" style="padding:8px 26px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit">OK</button>
            </div>
        </div>
    </div>
</div>

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

{{-- Modal SALDAR CARGOS DEL PROVEEDOR --}}
<div id="modal-saldar" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.45);z-index:99999;align-items:center;justify-content:center;padding:20px">
    <div style="background:#fff;border-radius:12px;width:100%;max-width:1000px;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;border:1px solid #e5e7eb;margin:auto">
        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#4a2078,#6B3FA0);padding:12px 20px;display:flex;align-items:center;gap:12px">
            <button type="button" onclick="cerrarModalSaldar()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:28px;height:28px;border-radius:6px;font-size:16px;cursor:pointer;font-weight:700;display:flex;align-items:center;justify-content:center">&times;</button>
            <span style="font-size:15px;font-weight:700;color:#fff">Saldar cargos del proveedor</span>
        </div>

        {{-- Documento a saldar (cabecera) --}}
        <div style="padding:14px 20px;background:#faf5ff;border-bottom:1px solid #e9d5ff">
            <div style="font-size:12px;font-weight:700;color:#5b21b6;text-transform:uppercase;margin-bottom:8px">Documento a saldar</div>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:8px 20px;font-size:13px">
                <div><span style="color:var(--gray-muted)">Concepto:</span> <strong id="sal-concepto">—</strong></div>
                <div><span style="color:var(--gray-muted)">Serie:</span> <strong id="sal-serie">{{ $cuentaConfig['serie'] ?? '8969' }}</strong></div>
                <div><span style="color:var(--gray-muted)">Folio:</span> <strong id="sal-folio">—</strong></div>
                <div><span style="color:var(--gray-muted)">Fecha:</span> <strong id="sal-fecha">—</strong></div>
                <div><span style="color:var(--gray-muted)">Total:</span> <strong id="sal-total" style="color:var(--green)">$0.00</strong></div>
                <div><span style="color:var(--gray-muted)">Saldo:</span> <strong id="sal-saldo" style="color:var(--green)">$0.00</strong></div>
                <div style="grid-column:3/5"><span style="color:var(--gray-muted)">Proveedor:</span> <strong id="sal-prov">—</strong></div>
            </div>
        </div>

        {{-- Sección 1: Documentos pendientes --}}
        <div style="padding:10px 20px 6px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <span style="font-size:13px;font-weight:700;color:#111">1. Documentos con saldo pendiente de asociar</span>
            <div style="display:flex;align-items:center;gap:10px;margin-left:auto">
                <label style="font-size:12px;font-weight:600;color:#5b21b6">Documentos a partir del:</label>
                <input type="date" id="sal-fecha-desde" style="border:1.5px solid #c4b5fd;border-radius:8px;padding:6px 10px;font-size:12px;font-family:inherit" onchange="filtrarSaldar()">
                <input type="text" id="sal-buscar" placeholder="Buscar serie/folio..." style="border:1.5px solid #c4b5fd;border-radius:8px;padding:6px 12px;font-size:12px;font-family:inherit;width:180px" oninput="filtrarSaldar()">
            </div>
        </div>
        <div style="overflow-y:auto;flex:1;padding:0 20px;min-height:120px">
            <table style="width:100%;border-collapse:collapse;font-size:12px">
                <thead>
                    <tr style="background:#f3e8ff;position:sticky;top:0">
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#5b21b6;font-size:10px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Vencimiento</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#5b21b6;font-size:10px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Concepto</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#5b21b6;font-size:10px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Serie</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#5b21b6;font-size:10px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Folio</th>
                        <th style="padding:8px 10px;text-align:right;font-weight:700;color:#5b21b6;font-size:10px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Total</th>
                        <th style="padding:8px 10px;text-align:right;font-weight:700;color:#5b21b6;font-size:10px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Saldo</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#5b21b6;font-size:10px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Moneda</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#5b21b6;font-size:10px;text-transform:uppercase;border-bottom:2px solid #c4b5fd">Referencia</th>
                    </tr>
                </thead>
                <tbody id="sal-pendientes-body">
                    <tr><td colspan="8" style="padding:24px;text-align:center;color:var(--gray-muted)">Sin documentos pendientes</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Sección 2: Documentos asociados --}}
        <div style="padding:10px 20px 6px;background:#f9fafb;border-top:2px solid #e5e7eb">
            <span style="font-size:13px;font-weight:700;color:#111">2. Documentos asociados</span>
        </div>
        <div style="overflow-y:auto;max-height:180px;padding:0 20px 10px;background:#f9fafb">
            <table style="width:100%;border-collapse:collapse;font-size:12px">
                <thead>
                    <tr style="background:#eef2ff;position:sticky;top:0">
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Fecha</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Concepto</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Serie</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Folio</th>
                        <th style="padding:8px 10px;text-align:right;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Total</th>
                        <th style="padding:8px 10px;text-align:right;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Saldo</th>
                        <th style="padding:8px 10px;text-align:left;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Moneda</th>
                        <th style="padding:8px 10px;text-align:right;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Pago</th>
                        <th style="padding:8px 10px;text-align:center;font-weight:700;color:#3730a3;font-size:10px;text-transform:uppercase">Quitar</th>
                    </tr>
                </thead>
                <tbody id="sal-asociados-body">
                    <tr><td colspan="9" style="padding:16px;text-align:center;color:var(--gray-muted)">Doble click en un documento de arriba para asociarlo</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div style="padding:12px 20px;border-top:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;background:#fff">
            <div style="font-size:11px;color:var(--gray-muted)">&lt;Doble click&gt; en un documento para capturar el importe a asociar</div>
            <div style="display:flex;align-items:center;gap:18px">
                <div style="font-size:14px;font-weight:700">Total Pagos: <span id="sal-total-pagos" style="color:var(--green)">$0.00</span></div>
                <button type="button" onclick="cerrarModalSaldar()" style="padding:8px 22px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit">Cerrar</button>
            </div>
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
        if (typeof fechaEsValida === 'function' && !fechaEsValida()) {
            e.preventDefault();
            alert('Recordatorio: cambia la FECHA al día del pago (un día anterior a hoy).\n\nNo se puede guardar con la fecha de hoy o una futura.');
            document.getElementById('ab-fecha').style.borderColor = '#dc2626';
            document.getElementById('ab-fecha').focus();
            return false;
        }
    });
    var razonInput = document.getElementById('ab-razon');

    // Datos de proveedores para razón social
    var proveedoresData = @json($proveedores->mapWithKeys(fn($p) => [$p->codigo => ['nombre' => $p->nombre, 'moneda' => $p->moneda ?? 'MXN']]));

    // Función para cargar datos del proveedor (moneda, TC, razón) + facturas en memoria
    window.cargarFacturasAbono = function(codigo) {
        if (!codigo) {
            razonInput.value = '';
            window._facturasProveedor = [];
            return;
        }

        var pData = proveedoresData[codigo] || {};
        razonInput.value = pData.nombre || '';
        var mon = pData.moneda || 'MXN';
        document.getElementById('ab-moneda').value = mon;
        var monLabel = document.getElementById('ab-moneda-label');
        var monNombre = mon === 'USD' ? 'DÓLAR AMERICANO' : (mon === 'MXN' ? 'PESO MEXICANO' : mon);
        if (monLabel) monLabel.textContent = monNombre;
        var monSelect = document.getElementById('ab-moneda-select');
        if (monSelect) monSelect.value = mon;
        var tc = document.getElementById('ab-tc');
        var btnF3 = document.getElementById('ab-tc-f3');
        if (mon === 'MXN') {
            tc.value = '1.0000';
            tc.readOnly = true;
            tc.style.color = 'var(--gray-muted)';
            tc.style.background = 'var(--gray-soft)';
            if (btnF3) { btnF3.style.background = '#faf5ff'; btnF3.style.color = '#5b21b6'; }
        } else {
            tc.readOnly = false;
            tc.style.color = '#111';
            tc.style.background = '#fff';
            if (btnF3) { btnF3.style.background = '#6B3FA0'; btnF3.style.color = '#fff'; }
        }

        // Cargar facturas en memoria (para la ventana Saldar)
        fetch(@json(route('admin.abono-proveedor.facturas-json')) + '?codigo=' + encodeURIComponent(codigo), { headers: {'Accept':'application/json'}, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                window._facturasProveedor = data.facturas || [];
            })
            .catch(function(){
                window._facturasProveedor = [];
            });
    };

    function recalcTotal(){}
    window.recalcTotal = recalcTotal;

    // Si ya viene proveedor seleccionado (por query param)
    var initCodigo = document.getElementById('ab-proveedor').value;
    if (initCodigo) {
        window.cargarFacturasAbono(initCodigo);
    }
})();

// ═══════════════════════════════════════════
// Cambiar moneda (editable en las 4 cuentas)
// ═══════════════════════════════════════════
function cambiarMoneda(val) {
    document.getElementById('ab-moneda').value = val;
    var tc = document.getElementById('ab-tc');
    var btnF3 = document.getElementById('ab-tc-f3');
    if (val === 'MXN') {
        tc.value = '1.0000';
        tc.readOnly = true;
        tc.style.color = 'var(--gray-muted)';
        tc.style.background = 'var(--gray-soft)';
        if (btnF3) { btnF3.style.background = '#faf5ff'; btnF3.style.color = '#5b21b6'; }
    } else {
        tc.readOnly = false;
        tc.style.color = '#111';
        tc.style.background = '#fff';
        if (tc.value === '1.0000' || !tc.value) tc.value = '';
        if (btnF3) { btnF3.style.background = '#6B3FA0'; btnF3.style.color = '#fff'; }
        tc.focus();
    }
}

// ═══════════════════════════════════════════
// Toggle candado tipo de cambio
// ═══════════════════════════════════════════
function toggleTC() {
    var tc = document.getElementById('ab-tc');
    var btn = document.getElementById('ab-tc-f3');
    if (tc.readOnly) {
        // Desbloquear para editar
        tc.readOnly = false;
        tc.style.color = '#111';
        tc.style.background = '#fff';
        if (btn) { btn.style.background = '#6B3FA0'; btn.style.color = '#fff'; }
        tc.focus();
        tc.select();
    } else {
        // Bloquear
        tc.readOnly = true;
        tc.style.color = 'var(--gray-muted)';
        tc.style.background = 'var(--gray-soft)';
        if (btn) { btn.style.background = '#faf5ff'; btn.style.color = '#5b21b6'; }
    }
}

// ═══════════════════════════════════════════
// Guardar (decide: abono con facturas o anticipo sin facturas)
// ═══════════════════════════════════════════
function guardarAbono() {
    // ¿Hay facturas asociadas desde la ventana Saldar?
    var facturasMarcadas = document.querySelectorAll('.factura-asociada-input').length;
    if (facturasMarcadas > 0) {
        // Flujo abono normal: enviar el form principal (marca facturas como liquidadas)
        var poliza = document.getElementById('ab-poliza').value.trim();
        var referencia = document.getElementById('ab-referencia').value.trim();
        if (!poliza) { alert('Falta el Folio / Nº Póliza.'); switchTab('generales'); document.getElementById('ab-poliza').focus(); return; }
        if (typeof fechaEsValida === 'function' && !fechaEsValida()) {
            alert('Recordatorio: cambia la FECHA al día del pago (un día anterior a hoy).');
            switchTab('generales');
            document.getElementById('ab-fecha').focus();
            return;
        }
        if (!referencia) {
            alert('Escribe en Referencia las facturas o número de compra que estás pagando.');
            switchTab('info');
            document.getElementById('ab-referencia').focus();
            document.getElementById('ab-referencia').style.borderColor = '#dc2626';
            return;
        }
        document.getElementById('form-abono').submit();
    } else {
        // Sin facturas asociadas
        var esExtranjera = @json($esExtranjera ?? false);
        if (esExtranjera) {
            // Extranjera sin OC → guardar como anticipo
            guardarComoAnticipo();
        } else {
            alert('No has asociado ninguna factura.\n\nAbre la ventana de saldar (Enter en el Total) y asocia al menos una factura antes de guardar.');
        }
    }
}

// Guardar como anticipo (Info Adicional)
// ═══════════════════════════════════════════
function guardarComoAnticipo() {
    var poliza = document.getElementById('ab-poliza').value.trim();
    var codigo = document.getElementById('ab-proveedor').value;
    var referencia = document.getElementById('ab-referencia').value.trim();
    var totalRaw = document.getElementById('ab-total-input').value;
    var total = Number(String(totalRaw).replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;

    if (typeof fechaEsValida === 'function' && !fechaEsValida()) { alert('Recordatorio: cambia la FECHA al día del pago (un día anterior a hoy).'); document.getElementById('ab-fecha').style.borderColor = '#dc2626'; document.getElementById('ab-fecha').focus(); return; }
    if (!poliza) { alert('Falta el Folio / Nº Póliza.'); switchTab('generales'); document.getElementById('ab-poliza').focus(); return; }
    if (!codigo) { alert('Selecciona un proveedor.'); abrirModalProv(); return; }
    if (total <= 0) { alert('Escribe el Total del pago (en la pestaña Generales).'); switchTab('generales'); document.getElementById('ab-total-input').focus(); return; }
    if (!referencia) { alert('Escribe el Nº de orden en el campo Referencia.'); document.getElementById('ab-referencia').focus(); return; }

    if (!confirm('¿Guardar este pago como ANTICIPO?\n\nProveedor: ' + codigo + '\nReferencia/Orden: ' + referencia + '\nTotal: $' + total.toLocaleString('en', {minimumFractionDigits:2}))) {
        return;
    }

    // Construir form oculto y enviar a la ruta de anticipo
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = @json(route('admin.abono-proveedor.anticipo'));
    f.style.display = 'none';

    function add(name, val) {
        var i = document.createElement('input');
        i.type = 'hidden'; i.name = name; i.value = val;
        f.appendChild(i);
    }
    add('_token', @json(csrf_token()));
    add('poliza', poliza);
    add('fecha', document.getElementById('ab-fecha').value);
    add('serie', document.querySelector('input[name="serie"]') ? document.querySelector('input[name="serie"]').value : '');
    add('cuenta_key', @json($cuentaKey ?? ''));
    add('codigo_proveedor', codigo);
    add('moneda', document.getElementById('ab-moneda').value);
    add('tipo_cambio', document.getElementById('ab-tc').value);
    add('total_manual', total);
    add('referencia', referencia);
    add('observaciones', document.getElementById('ab-observaciones').value);

    document.body.appendChild(f);
    f.submit();
}

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
var _provActivoIdx = -1; // índice dentro de las filas visibles

function filasVisibles() {
    return Array.prototype.filter.call(
        document.querySelectorAll('.prov-row'),
        function(tr) { return tr.style.display !== 'none'; }
    );
}
function resaltarProv(idx) {
    var vis = filasVisibles();
    if (!vis.length) { _provActivoIdx = -1; return; }
    if (idx < 0) idx = 0;
    if (idx > vis.length - 1) idx = vis.length - 1;
    _provActivoIdx = idx;
    // Limpiar y resaltar
    document.querySelectorAll('.prov-row').forEach(function(r) { r.style.background = ''; });
    var tr = vis[idx];
    tr.style.background = '#dbeafe';
    // Scroll para mantenerla visible
    tr.scrollIntoView({ block: 'nearest' });
}
function abrirModalProv() {
    var m = document.getElementById('modal-prov');
    m.style.display = 'flex';
    m.style.position = 'fixed';
    m.style.inset = '0';
    m.style.zIndex = '99999';
    _provActivoIdx = -1;
    setTimeout(function() {
        var b = document.getElementById('prov-buscar');
        b.focus();
        resaltarProv(0); // resaltar la primera al abrir
    }, 100);
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
    // Al filtrar, resaltar la primera coincidencia
    resaltarProv(0);
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
    // Asegurar que la pestaña Generales esté abierta y enfocar Cuenta
    switchTab('generales');
    setTimeout(function() {
        var cuenta = document.getElementById('ab-cuenta');
        if (cuenta) { cuenta.focus(); cuenta.select(); }
    }, 150);
}
// Hover rows (respeta la fila activa por teclado)
document.querySelectorAll('.prov-row').forEach(function(tr) {
    tr.addEventListener('mouseenter', function() { if (this.style.background !== 'rgb(219, 234, 254)') this.style.background = '#f3e8ff'; });
    tr.addEventListener('mouseleave', function() { if (this.style.background !== 'rgb(219, 234, 254)') this.style.background = ''; });
});

// Navegación con flechas ↑↓ y Enter en el buscador de proveedores
(function() {
    var buscar = document.getElementById('prov-buscar');
    if (!buscar) return;
    buscar.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            resaltarProv(_provActivoIdx + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            resaltarProv(_provActivoIdx - 1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var vis = filasVisibles();
            if (_provActivoIdx >= 0 && vis[_provActivoIdx]) {
                seleccionarProv(vis[_provActivoIdx]);
            } else if (vis.length === 1) {
                seleccionarProv(vis[0]);
            }
        } else if (e.key === 'Escape') {
            cerrarModalProv();
        }
    });
})();
// Cerrar solo con la X

// ═══════════════════════════════════════════
// Navegación con Enter entre campos obligatorios
// Orden: Folio → Proveedor (abre modal)
// ═══════════════════════════════════════════
// Valida que la fecha sea anterior a hoy (no hoy, no futura)
function fechaEsValida() {
    var f = document.getElementById('ab-fecha').value; // YYYY-MM-DD
    if (!f) return false;
    var hoy = new Date();
    hoy.setHours(0,0,0,0);
    var partes = f.split('-');
    var fecha = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
    fecha.setHours(0,0,0,0);
    return fecha.getTime() < hoy.getTime(); // debe ser ESTRICTAMENTE anterior a hoy
}

(function() {
    var fecha = document.getElementById('ab-fecha');
    var poliza = document.getElementById('ab-poliza');
    var btnProv = document.getElementById('btn-abrir-prov');

    // Fecha → Folio (validando que sea anterior a hoy)
    if (fecha) {
        fecha.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!fechaEsValida()) {
                    alert('Recordatorio: cambia la FECHA al día del pago (un día anterior a hoy).\n\nNo se puede continuar con la fecha de hoy o una futura.');
                    fecha.style.borderColor = '#dc2626';
                    fecha.focus();
                    return;
                }
                fecha.style.borderColor = 'var(--green, #16a34a)';
                if (poliza) poliza.focus();
            }
        });
    }

    // Folio → Proveedor (abre modal), revalidando fecha
    if (poliza) {
        poliza.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!fechaEsValida()) {
                    alert('Recordatorio: cambia la FECHA al día del pago (un día anterior a hoy).');
                    document.getElementById('ab-fecha').style.borderColor = '#dc2626';
                    document.getElementById('ab-fecha').focus();
                    return;
                }
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

    // Cuenta → Total con Enter
    var cuenta = document.getElementById('ab-cuenta');
    var totalInput = document.getElementById('ab-total-input');
    if (cuenta) {
        cuenta.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (totalInput) { totalInput.focus(); totalInput.select(); }
            }
        });
    }
    if (totalInput) {
        // Formatear con comas al salir
        totalInput.addEventListener('blur', function() {
            var val = Number(String(this.value).replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
            if (val > 0) this.value = val.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
        });
        totalInput.addEventListener('focus', function() {
            var val = Number(String(this.value).replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
            this.value = val > 0 ? String(val) : '';
        });
        // Enter en Total → abrir modal Saldar
        totalInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var val = Number(String(this.value).replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
                if (val <= 0) {
                    alert('Escribe el monto total primero.');
                    return;
                }
                abrirModalSaldar(val);
            }
        });
    }
})();

// ═══════════════════════════════════════════
// Modal Saldar cargos del proveedor
// ═══════════════════════════════════════════
var _saldarAsociados = [];

function abrirModalSaldar(totalPago) {
    // Validar fecha (anterior a hoy)
    if (!fechaEsValida()) {
        alert('Recordatorio: cambia la FECHA al día del pago (un día anterior a hoy).\n\nNo se puede continuar con la fecha de hoy o una futura.');
        var fEl = document.getElementById('ab-fecha');
        fEl.style.borderColor = '#dc2626';
        fEl.focus();
        return;
    }
    // Validar Folio/Póliza obligatorio
    var poliza = document.getElementById('ab-poliza');
    if (!poliza.value.trim()) {
        alert('Debes llenar el Folio / Nº Póliza antes de continuar.');
        poliza.focus();
        poliza.style.borderColor = '#dc2626';
        return;
    }
    // Validar proveedor
    var codigo = document.getElementById('ab-proveedor').value;
    if (!codigo) {
        alert('Selecciona un proveedor primero.');
        abrirModalProv();
        return;
    }

    var m = document.getElementById('modal-saldar');
    var nombre = document.getElementById('ab-razon').value || '—';
    var concepto = document.querySelector('input[name="cuenta"]') ? document.querySelector('input[name="cuenta"]').value : '';
    var fecha = document.getElementById('ab-fecha').value || '';
    var moneda = document.getElementById('ab-moneda').value || 'MXN';

    // Cabecera
    document.getElementById('sal-concepto').textContent = concepto || 'Abono';
    document.getElementById('sal-prov').textContent = codigo + ' — ' + nombre;
    document.getElementById('sal-fecha').textContent = fecha;
    document.getElementById('sal-folio').textContent = poliza.value.trim() || '—';
    var totalFmt = '$' + Number(totalPago).toLocaleString('en', {minimumFractionDigits:2});
    document.getElementById('sal-total').textContent = totalFmt;
    document.getElementById('sal-saldo').textContent = totalFmt;

    // Reset asociados
    _saldarAsociados = [];
    renderAsociados();

    // Mostrar modal de inmediato
    m.style.display = 'flex';
    setTimeout(function() { var sb = document.getElementById('sal-buscar'); if (sb) sb.focus(); }, 200);

    // Pintar documentos pendientes: SIEMPRE fetch fresco
    var body = document.getElementById('sal-pendientes-body');
    body.innerHTML = '<tr><td colspan="8" style="padding:24px;text-align:center;color:var(--gray-muted)">Cargando documentos...</td></tr>';

    var url = '/admin/abono-proveedor/facturas-json?codigo=' + encodeURIComponent(codigo);
    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function(r){ return r.text(); })
        .then(function(txt){
            var data;
            try { data = JSON.parse(txt); }
            catch (e) {
                console.error('Respuesta no-JSON del servidor:', txt.substring(0, 200));
                body.innerHTML = '<tr><td colspan="8" style="padding:24px;text-align:center;color:#dc2626">Sesión expirada o error del servidor. Recarga la página.</td></tr>';
                return;
            }
            window._facturasProveedor = data.facturas || [];
            pintarPendientes(window._facturasProveedor, concepto, moneda);
        })
        .catch(function(err){
            console.error('Error saldar facturas:', err);
            body.innerHTML = '<tr><td colspan="8" style="padding:24px;text-align:center;color:#dc2626">Error al cargar documentos. Revisa la consola.</td></tr>';
        });
}

function pintarPendientes(facturas, concepto, moneda) {
    var body = document.getElementById('sal-pendientes-body');
    if (!facturas || !facturas.length) {
        body.innerHTML = '<tr><td colspan="8" style="padding:24px;text-align:center;color:var(--gray-muted)">Este proveedor no tiene documentos pendientes</td></tr>';
        return;
    }
    body.innerHTML = facturas.map(function(f, i) {
        var saldo = Math.max(0, (f.total || 0) - (f.monto_pagado || 0));
        var fechaDoc = f.fecha_vencimiento || f.fecha_pago || '';
        return '<tr class="sal-row" data-idx="' + i + '" data-fecha="' + fechaDoc + '" ondblclick="asociarDoc(' + i + ')" style="cursor:pointer;border-bottom:1px solid #f3f4f6" tabindex="0">' +
            '<td style="padding:7px 10px">' + (fechaDoc || '—') + '</td>' +
            '<td style="padding:7px 10px">' + (concepto || 'Factura') + '</td>' +
            '<td style="padding:7px 10px">' + (f.serie || '—') + '</td>' +
            '<td style="padding:7px 10px;font-weight:600">' + (f.folio_cfdi || f.id) + '</td>' +
            '<td style="padding:7px 10px;text-align:right">$' + Number(f.total || 0).toLocaleString('en', {minimumFractionDigits:2}) + '</td>' +
            '<td style="padding:7px 10px;text-align:right;color:var(--green);font-weight:600">$' + Number(saldo).toLocaleString('en', {minimumFractionDigits:2}) + '</td>' +
            '<td style="padding:7px 10px">' + (moneda || 'MXN') + '</td>' +
            '<td style="padding:7px 10px;color:#6b7280">' + (f.referencia || '—') + '</td>' +
            '</tr>';
    }).join('');
    document.querySelectorAll('.sal-row').forEach(function(tr) {
        tr.addEventListener('mouseenter', function() { if (this.style.background !== 'rgb(219, 234, 254)') this.style.background = '#f3e8ff'; });
        tr.addEventListener('mouseleave', function() { if (this.style.background !== 'rgb(219, 234, 254)') this.style.background = ''; });
    });
    resaltarSaldar(0); // resaltar la primera al pintar
}
function cerrarModalSaldar() {
    // Al cerrar, inyectar en el form los IDs de las facturas asociadas
    var cont = document.getElementById('facturas-asociadas-inputs');
    if (cont) {
        cont.innerHTML = '';
        _saldarAsociados.forEach(function(a) {
            var i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'factura_ids[]';
            i.value = a.factura.id;
            i.className = 'factura-asociada-input';
            cont.appendChild(i);
        });
    }
    document.getElementById('modal-saldar').style.display = 'none';
    // Si asoció documentos, llevar a Información Adicional para capturar y guardar
    if (_saldarAsociados.length && typeof switchTab === 'function') {
        switchTab('info');
        setTimeout(function() {
            var ref = document.getElementById('ab-referencia');
            if (ref) ref.focus();
        }, 150);
    }
}

// ── Navegación con flechas ↑↓ y Enter en la ventana Saldar ──
var _salActivoIdx = -1;
function salFilasVisibles() {
    return Array.prototype.filter.call(
        document.querySelectorAll('.sal-row'),
        function(tr) { return tr.style.display !== 'none'; }
    );
}
function resaltarSaldar(idx) {
    var vis = salFilasVisibles();
    if (!vis.length) { _salActivoIdx = -1; return; }
    if (idx < 0) idx = 0;
    if (idx > vis.length - 1) idx = vis.length - 1;
    _salActivoIdx = idx;
    document.querySelectorAll('.sal-row').forEach(function(r) { r.style.background = ''; });
    var tr = vis[idx];
    tr.style.background = '#dbeafe';
    tr.scrollIntoView({ block: 'nearest' });
}
function filtrarSaldar() {
    var q = document.getElementById('sal-buscar').value.toLowerCase().trim();
    var desde = document.getElementById('sal-fecha-desde').value; // YYYY-MM-DD
    document.querySelectorAll('.sal-row').forEach(function(tr) {
        var matchTexto = tr.textContent.toLowerCase().includes(q);
        var matchFecha = true;
        if (desde) {
            var f = tr.dataset.fecha || '';
            matchFecha = f && f >= desde;
        }
        tr.style.display = (matchTexto && matchFecha) ? '' : 'none';
    });
    resaltarSaldar(0);
}
// Listener de teclado en el buscador de saldar
(function() {
    var b = document.getElementById('sal-buscar');
    if (!b) return;
    b.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            resaltarSaldar(_salActivoIdx + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            resaltarSaldar(_salActivoIdx - 1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            var vis = salFilasVisibles();
            if (_salActivoIdx >= 0 && vis[_salActivoIdx]) {
                asociarDoc(parseInt(vis[_salActivoIdx].dataset.idx));
            }
        }
    });
})();
var _importeIdxActual = null;
function asociarDoc(idx) {
    var facturas = window._facturasProveedor || [];
    var f = facturas[idx];
    if (!f) return;
    // Evitar duplicados
    if (_saldarAsociados.find(function(a) { return a.idx === idx; })) {
        alert('Este documento ya está asociado.');
        return;
    }
    _importeIdxActual = idx;
    var saldoUSD = Math.max(0, (f.total || 0) - (f.monto_pagado || 0)); // saldo del documento (en su moneda)
    var monedaAbono = document.getElementById('ab-moneda').value || 'MXN';
    var inp = document.getElementById('imp-asociar-input');
    var leyenda = document.getElementById('imp-leyenda');
    var tcWrap = document.getElementById('imp-tc-wrap');
    var tcInput = document.getElementById('imp-tc-input');

    if (monedaAbono === 'USD') {
        // Documento en dólares: mostrar leyenda + TC editable, recalcular importe en MXN
        var tcAbono = Number(String(document.getElementById('ab-tc').value).replace(/[^0-9.]/g, '')) || 0;
        tcWrap.style.display = 'flex';
        tcInput.value = tcAbono ? Number(tcAbono).toFixed(4) : '';
        leyenda.style.display = 'block';
        // El importe se calcula en recalcularImporte()
        recalcularImporte();
    } else {
        // MXN: sin conversión, importe = saldo directo
        tcWrap.style.display = 'none';
        leyenda.style.display = 'none';
        inp.value = Number(saldoUSD).toFixed(2);
    }

    document.getElementById('modal-importe').style.display = 'flex';
    setTimeout(function() { inp.focus(); inp.select(); }, 100);
}

// Recalcula el importe en MXN según el tipo de cambio del abono (documento en dólares)
function recalcularImporte() {
    if (_importeIdxActual === null) return;
    var facturas = window._facturasProveedor || [];
    var f = facturas[_importeIdxActual];
    if (!f) return;

    var saldoUSD = Math.max(0, (f.total || 0) - (f.monto_pagado || 0));
    var tcCompra = Number(f.tipo_cambio_compra || f.tipo_cambio || 0) || 0; // TC con que se registró la compra (si existe)
    var tcAbono = Number(String(document.getElementById('imp-tc-input').value).replace(/[^0-9.]/g, '')) || 0;

    // Importe a asociar en MXN = saldo USD × TC del abono
    var importeMXN = saldoUSD * tcAbono;
    document.getElementById('imp-asociar-input').value = importeMXN > 0 ? importeMXN.toFixed(2) : '0.00';

    // Datos precargados del ejemplo
    var concepto = document.querySelector('input[name="cuenta"]') ? document.querySelector('input[name="cuenta"]').value : 'Compra';
    var folioDoc = f.folio_cfdi || f.id;
    var serieDoc = f.serie || '';
    var cuentaTitulo = @json($cuentaConfig['titulo'] ?? '');
    var folioAbono = document.getElementById('ab-poliza').value || '—';

    // Construir leyenda estilo Contpaqi
    var lineas = [];
    lineas.push('<strong>Compra</strong> serie ' + (serieDoc || concepto) + ' folio ' + folioDoc + ' saldo <strong>' + saldoUSD.toLocaleString('en',{minimumFractionDigits:2}) + '</strong> (DÓLAR AMERICANO)');
    if (tcCompra > 0) {
        lineas.push('Saldo <strong>' + (saldoUSD * tcCompra).toLocaleString('en',{minimumFractionDigits:2}) + '</strong> al tipo de cambio ' + tcCompra.toFixed(4) + ' (compra)');
    }
    lineas.push('Saldo <strong>' + importeMXN.toLocaleString('en',{minimumFractionDigits:2}) + '</strong> al tipo de cambio ' + tcAbono.toFixed(4) + ' (' + cuentaTitulo + ')');
    lineas.push(cuentaTitulo + ' folio ' + folioAbono + ' disponible <strong>' + importeMXN.toLocaleString('en',{minimumFractionDigits:2}) + '</strong> (MXN)');
    document.getElementById('imp-leyenda').innerHTML = lineas.join('<br>');
}

function cerrarImporte() {
    document.getElementById('modal-importe').style.display = 'none';
    _importeIdxActual = null;
}
function confirmarImporte() {
    if (_importeIdxActual === null) return;
    var facturas = window._facturasProveedor || [];
    var f = facturas[_importeIdxActual];
    var pago = Number(String(document.getElementById('imp-asociar-input').value).replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
    if (pago <= 0) { alert('Captura un importe válido.'); return; }
    var saldoPendiente = Math.max(0, (f.total || 0) - (f.monto_pagado || 0));
    var tcAbono = Number(String(document.getElementById('imp-tc-input').value).replace(/[^0-9.]/g, '')) || null;
    // Guardamos el saldo pendiente original + tc usado para el registro
    _saldarAsociados.push({ idx: _importeIdxActual, factura: f, pago: pago, saldoPendiente: saldoPendiente, tipo_cambio: tcAbono });
    cerrarImporte();
    renderAsociados();
    // Devolver foco al buscador para seguir navegando con flechas
    setTimeout(function() { var sb = document.getElementById('sal-buscar'); if (sb) sb.focus(); }, 100);
}
function quitarAsociado(idx) {
    _saldarAsociados = _saldarAsociados.filter(function(a) { return a.idx !== idx; });
    renderAsociados();
}
function renderAsociados() {
    var body = document.getElementById('sal-asociados-body');
    var moneda = document.getElementById('ab-moneda').value || 'MXN';
    var concepto = document.querySelector('input[name="cuenta"]') ? document.querySelector('input[name="cuenta"]').value : 'Compra';
    var totalPagos = 0;

    if (!_saldarAsociados.length) {
        if (body) body.innerHTML = '<tr><td colspan="9" style="padding:16px;text-align:center;color:var(--gray-muted)">Doble click en un documento de arriba para asociarlo</td></tr>';
        var lbl0 = document.getElementById('sal-total-pagos');
        if (lbl0) lbl0.textContent = '$0.00';
        return;
    }

    if (body) {
        body.innerHTML = _saldarAsociados.map(function(a) {
            var f = a.factura;
            totalPagos += a.pago;
            var total = Number(f.total || 0);
            var saldoPend = a.saldoPendiente != null ? a.saldoPendiente : Math.max(0, total - (f.monto_pagado || 0));
            var saldoRestante = Math.round((saldoPend - a.pago) * 100) / 100; // redondeo a 2 decimales
            if (saldoRestante < 0) saldoRestante = 0;
            var fechaDoc = f.fecha_vencimiento || f.fecha_pago || '—';
            var saldoColor = saldoRestante <= 0.001 ? 'var(--green)' : '#d97706';
            return '<tr style="border-bottom:1px solid #e5e7eb;background:#eef6ff">' +
                '<td style="padding:7px 10px">' + fechaDoc + '</td>' +
                '<td style="padding:7px 10px">' + concepto + '</td>' +
                '<td style="padding:7px 10px">' + (f.serie || '—') + '</td>' +
                '<td style="padding:7px 10px;font-weight:600">' + (f.folio_cfdi || f.id) + '</td>' +
                '<td style="padding:7px 10px;text-align:right">$' + total.toLocaleString('en', {minimumFractionDigits:2}) + '</td>' +
                '<td style="padding:7px 10px;text-align:right;font-weight:700;color:' + saldoColor + '">$' + saldoRestante.toLocaleString('en', {minimumFractionDigits:2}) + '</td>' +
                '<td style="padding:7px 10px">' + moneda + '</td>' +
                '<td style="padding:7px 10px;text-align:right;color:var(--green);font-weight:700">$' + a.pago.toLocaleString('en', {minimumFractionDigits:2}) + '</td>' +
                '<td style="padding:7px 10px;text-align:center"><button type="button" onclick="quitarAsociado(' + a.idx + ')" style="background:#fee2e2;color:#dc2626;border:none;border-radius:4px;padding:2px 8px;cursor:pointer;font-weight:700">&times;</button></td>' +
                '</tr>';
        }).join('');
    }

    var lbl = document.getElementById('sal-total-pagos');
    if (lbl) lbl.textContent = '$' + totalPagos.toLocaleString('en', {minimumFractionDigits:2});
}
</script>
@endpush
