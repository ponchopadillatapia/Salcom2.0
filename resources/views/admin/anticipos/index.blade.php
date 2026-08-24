@extends('layouts.admin')
@section('title', 'Anticipos')
@section('hero')
<div class="hero-band">
    <h1>Anticipos</h1>
    <p>Pagos adelantados a proveedores · antes de la factura</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .ant-card{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:24px}
    .ant-header{background:linear-gradient(135deg,#4a2078,#6B3FA0);padding:14px 22px;display:flex;align-items:center;gap:16px}
    .ant-header h3{color:#fff;font-size:15px;font-weight:700;margin:0}
    .ant-body{padding:20px 22px;display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px 20px}
    .ant-field{display:flex;flex-direction:column;gap:4px}
    .ant-field label{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase}
    .ant-field input,.ant-field select,.ant-field textarea{border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none}
    .ant-field input:focus,.ant-field select:focus,.ant-field textarea:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .ant-field.span2{grid-column:span 2}
    .ant-field.span3{grid-column:span 3}
    .ant-actions{padding:16px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}
    .btn-ant{padding:10px 24px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .btn-cancel{padding:10px 20px;background:var(--white);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;color:var(--gray-text);text-decoration:none}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .adm-section-head h4{font-size:14px;font-weight:700;margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;padding:12px 16px;text-align:left;border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;border-bottom:1px solid var(--border)}
    .admin-table tr:hover td{background:var(--purple-subtle)}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-block}
    .pill.pagado{background:#ecfdf5;color:#059669}
    .pill.pendiente{background:#f3f4f6;color:#6b7280}
    .pill.aplicado{background:var(--purple-subtle);color:var(--purple)}
    .pill.cancelado{background:#fef2f2;color:#7f1d1d}
    .empty-state{text-align:center;padding:40px;color:var(--gray-muted);font-size:14px}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}

    @media(max-width:768px){.ant-body{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

@if(session('ok'))
    <div class="pag-alert ok anim">{{ session('ok') }}</div>
@endif

{{-- Formulario --}}
<form method="POST" action="{{ route('admin.anticipos.store') }}">
    @csrf
    <div class="ant-card anim">
        <div class="ant-header">
            <h3>Registrar anticipo</h3>
        </div>
        <div class="ant-body">
            <div class="ant-field span3">
                <label>Proveedor <span style="color:#dc2626">●</span></label>
                <select name="proveedor_id" required id="ant-proveedor" onchange="precargarProveedor()">
                    <option value="">(Seleccionar)</option>
                    @foreach($proveedores as $p)
                        @php
                            $di = is_array($p->datos_identificacion) ? $p->datos_identificacion : [];
                        @endphp
                        <option value="{{ $p->id }}"
                            data-banco="{{ $di['banco'] ?? '' }}"
                            data-cuenta="{{ $di['cuenta'] ?? '' }}"
                            data-clabe="{{ $di['clabe'] ?? '' }}"
                            data-rfc="{{ $di['rfc'] ?? '' }}">
                            {{ $p->id_proveedor ?: $p->codigo }} — {{ $p->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="ant-field">
                <label>Banco</label>
                <input type="text" name="banco" id="ant-banco" placeholder="BANCOMER" readonly style="background:var(--gray-soft)">
            </div>
            <div class="ant-field">
                <label>Cuenta</label>
                <input type="text" name="cuenta_banco" id="ant-cuenta" placeholder="0-110016003" readonly style="background:var(--gray-soft)">
            </div>
            <div class="ant-field">
                <label>CLABE</label>
                <input type="text" name="clabe" id="ant-clabe" placeholder="0-121800011001604..." readonly style="background:var(--gray-soft)">
            </div>
            <div class="ant-field">
                <label>Importe <span style="color:#dc2626">●</span></label>
                <input type="text" name="importe" required placeholder="236,640.00" id="ant-importe" inputmode="decimal" oninput="soloNumeros(this);calcIvaYTotal()">
            </div>
            <div class="ant-field">
                <label>RFC</label>
                <input type="text" name="rfc" id="ant-rfc" placeholder="SMA820602U1A" style="text-transform:uppercase;background:var(--gray-soft)" readonly>
            </div>
            <div class="ant-field">
                <label>IVA (16%)</label>
                <input type="text" name="iva" id="ant-iva" readonly style="background:var(--gray-soft)" value="$0.00">
            </div>
            <div class="ant-field">
                <label>Folio General <span style="color:#dc2626">●</span> (OC/Proforma)</label>
                <input type="text" name="folio_general" required placeholder="OC MPA7018/ COT 36493">
            </div>
            <div class="ant-field">
                <label>Total del banco</label>
                <input type="text" id="ant-total-banco" readonly style="background:var(--gray-soft);font-weight:700;color:var(--green)" value="$0.00">
            </div>
            <div class="ant-field">
                <label>Departamento <span style="color:#dc2626">●</span></label>
                <select name="departamento" required>
                    <option value="">(Seleccionar)</option>
                    <option value="Mantenimiento">Mantenimiento</option>
                    <option value="IMEX">IMEX</option>
                    <option value="Compras Nacional">Compras Nacional</option>
                    <option value="Compras Internacional">Compras Internacional</option>
                    <option value="Logística">Logística</option>
                </select>
            </div>
            <div class="ant-field">
                <label>Fecha <span style="color:#dc2626">●</span></label>
                <input type="date" name="fecha" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="ant-field">
                <label>&nbsp;</label>
            </div>
            <div class="ant-field span3">
                <label>Concepto / Notas</label>
                <textarea name="concepto" rows="2" placeholder="Se paga anticipado para la liberación del material..."></textarea>
            </div>
        </div>
        <div class="ant-actions">
            <a href="{{ route('admin.anticipos') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-ant">Registrar anticipo</button>
        </div>
    </div>
</form>

{{-- Historial --}}
@php
    $agrupados = $anticipos->getCollection()->groupBy(function ($a) {
        return $a->created_at ? $a->created_at->format('Y-m-d') : 'sin-fecha';
    });
@endphp

<div class="inv-metrics anim" style="animation-delay:.04s;display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px">
    <a href="{{ route('admin.anticipos', ['estatus' => 'pendiente']) }}" class="inv-metric {{ $estatus === 'pendiente' ? 'is-active' : '' }}" style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;position:relative;overflow:hidden;text-decoration:none;color:inherit;display:block;cursor:pointer">
        <div style="position:absolute;top:0;left:0;width:4px;height:100%;background:#dc2626;border-radius:14px 0 0 14px"></div>
        <div style="font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px">50% Anticipo</div>
        <div style="font-size:28px;font-weight:700;color:var(--gray-text)">{{ $kpiPendientes }}</div>
        <div style="font-size:12px;color:var(--gray-muted);margin-top:6px">Pendientes de completar</div>
    </a>
    <a href="{{ route('admin.anticipos', ['estatus' => 'pagado']) }}" class="inv-metric {{ $estatus === 'pagado' ? 'is-active' : '' }}" style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;position:relative;overflow:hidden;text-decoration:none;color:inherit;display:block;cursor:pointer">
        <div style="position:absolute;top:0;left:0;width:4px;height:100%;background:#16a34a;border-radius:14px 0 0 14px"></div>
        <div style="font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px">Pagados</div>
        <div style="font-size:28px;font-weight:700;color:var(--gray-text)">{{ $kpiPagados }}</div>
        <div style="font-size:12px;color:var(--gray-muted);margin-top:6px">Anticipo entregado</div>
    </a>
    <a href="{{ route('admin.anticipos', ['estatus' => 'aplicado']) }}" class="inv-metric {{ $estatus === 'aplicado' ? 'is-active' : '' }}" style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;position:relative;overflow:hidden;text-decoration:none;color:inherit;display:block;cursor:pointer">
        <div style="position:absolute;top:0;left:0;width:4px;height:100%;background:var(--purple);border-radius:14px 0 0 14px"></div>
        <div style="font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px">Aplicados</div>
        <div style="font-size:28px;font-weight:700;color:var(--gray-text)">{{ $kpiAplicados }}</div>
        <div style="font-size:12px;color:var(--gray-muted);margin-top:6px">Descontados en factura</div>
    </a>
    <a href="{{ route('admin.anticipos') }}" class="inv-metric {{ $estatus === '' ? 'is-active' : '' }}" style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:20px;position:relative;overflow:hidden;text-decoration:none;color:inherit;display:block;cursor:pointer">
        <div style="position:absolute;top:0;left:0;width:4px;height:100%;background:#2563eb;border-radius:14px 0 0 14px"></div>
        <div style="font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px">Todos</div>
        <div style="font-size:28px;font-weight:700;color:var(--gray-text)">{{ $kpiTotal }}</div>
        <div style="font-size:12px;color:var(--gray-muted);margin-top:6px">Total anticipos</div>
    </a>
</div>

<div class="adm-section anim" style="animation-delay:.05s">
    <div class="adm-section-head">
        <h4>Anticipos registrados</h4>
        <span class="adm-section-meta">{{ $anticipos->total() }} registro{{ $anticipos->total() !== 1 ? 's' : '' }} · agrupado por fecha</span>
    </div>
    @if($anticipos->isEmpty())
        <div class="empty-state">No hay anticipos registrados.</div>
    @else
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Proveedor</th>
                        <th>Depto</th>
                        <th>Folio General</th>
                        <th>Total</th>
                        <th>Estatus</th>
                        <th style="text-align:right">Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agrupados as $fechaKey => $rows)
                        <tr class="date-row" style="background:var(--purple-subtle)!important">
                            <td colspan="7" style="font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)">
                                @if($fechaKey === 'sin-fecha')
                                    Sin fecha
                                @else
                                    {{ \Illuminate\Support\Carbon::parse($fechaKey)->locale('es')->isoFormat('DD [de] MMMM YYYY') }}
                                @endif
                            </td>
                        </tr>
                        @foreach($rows as $a)
                            <tr>
                                <td style="font-weight:700;color:var(--purple)">{{ $a->folio }}</td>
                                <td>
                                    <div style="font-weight:600">{{ $a->nombre_proveedor }}</div>
                                    <div style="font-size:11px;color:var(--gray-muted)">{{ $a->codigo_proveedor }}</div>
                                </td>
                                <td>{{ $a->departamento }}</td>
                                <td style="font-weight:600">{{ $a->folio_general }}</td>
                                <td class="monto">${{ number_format((float)$a->total_banco, 2) }}</td>
                                <td><span class="pill {{ $a->estatus }}">{{ ucfirst($a->estatus) }}</span></td>
                                <td style="text-align:right">
                                    <span style="display:inline-flex;padding:3px 8px;border-radius:999px;background:var(--purple-subtle,#f3e8ff);color:var(--purple);font-size:11px;font-weight:700">{{ $a->created_at?->format('h:i a') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($anticipos->hasPages())
            <div style="padding:14px;display:flex;justify-content:center">{{ $anticipos->links() }}</div>
        @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
function soloNumeros(el) {
    el.value = el.value.replace(/[^0-9.,]/g, '');
}

function parseNum(val) {
    return Number(String(val).replace(/,/g, '').replace(/[^0-9.]/g, '')) || 0;
}

function calcIvaYTotal() {
    var importe = parseNum(document.getElementById('ant-importe').value);
    var iva = importe * 0.16;
    var total = importe + iva;
    document.getElementById('ant-iva').value = '$' + iva.toLocaleString('en', {minimumFractionDigits:2});
    document.getElementById('ant-total-banco').value = '$' + total.toLocaleString('en', {minimumFractionDigits:2});
}

function precargarProveedor() {
    var sel = document.getElementById('ant-proveedor');
    var opt = sel.options[sel.selectedIndex];
    if (sel.value) {
        document.getElementById('ant-banco').value = opt.dataset.banco || '';
        document.getElementById('ant-cuenta').value = opt.dataset.cuenta || '';
        document.getElementById('ant-clabe').value = opt.dataset.clabe || '';
        document.getElementById('ant-rfc').value = opt.dataset.rfc || '';
    } else {
        document.getElementById('ant-banco').value = '';
        document.getElementById('ant-cuenta').value = '';
        document.getElementById('ant-clabe').value = '';
        document.getElementById('ant-rfc').value = '';
    }
}

// Formatear al blur
var impEl = document.getElementById('ant-importe');
if (impEl) {
    impEl.addEventListener('blur', function() {
        var val = parseNum(impEl.value);
        if (val > 0) impEl.value = val.toLocaleString('en', {minimumFractionDigits:2});
        calcIvaYTotal();
    });
    impEl.addEventListener('focus', function() {
        var val = parseNum(impEl.value);
        impEl.value = val > 0 ? val.toFixed(2) : '';
    });
}

// Al enviar, limpiar comas para el backend
document.querySelector('form').addEventListener('submit', function() {
    var imp = document.getElementById('ant-importe');
    var iva = document.getElementById('ant-iva');
    var importe = parseNum(imp.value);
    imp.value = importe.toFixed(2);
    iva.value = (importe * 0.16).toFixed(2);
});
</script>
@endpush
