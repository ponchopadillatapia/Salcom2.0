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
    <div class="pag-alert ok anim" id="msg-exito">{!! session('ok') !!}</div>
@endif
@if(request('creado'))
    @php $antCreado = \App\Models\AnticipoProveedor::find(request('creado')); @endphp
    @if($antCreado)
        <div class="pag-alert ok anim">
            Anticipo <strong>{{ $antCreado->folio_general }}</strong> registrado por <strong>${{ number_format((float)$antCreado->total_banco, 2) }}</strong> a {{ $antCreado->nombre_proveedor }}.
        </div>
        <script>window.open('{{ route("admin.anticipos.formato", $antCreado) }}', '_blank');</script>
    @endif
@endif
@if($errors->any())
    <div class="pag-alert" style="background:#fef2f2;color:#dc2626;border:1px solid #dc2626;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">
        <strong>Error:</strong> {{ $errors->first() }}
    </div>
@endif
<div class="pag-alert ok anim" id="msg-ajax" style="display:none"></div>

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
                <label>Concepto / Notas <span style="color:#dc2626">●</span></label>
                <textarea name="concepto" rows="2" required placeholder="Se paga anticipado para la liberación del material..."></textarea>
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
                        <th>Folio General</th>
                        <th>Formato</th>
                        <th>Proveedor</th>
                        <th>Depto</th>
                        <th>Total</th>
                        <th>Factura aplicada</th>
                        <th>Estatus</th>
                        <th style="text-align:right">Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agrupados as $fechaKey => $rows)
                        <tr class="date-row" style="background:var(--purple-subtle)!important">
                            <td colspan="8" style="font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)">
                                @if($fechaKey === 'sin-fecha')
                                    Sin fecha
                                @else
                                    {{ \Illuminate\Support\Carbon::parse($fechaKey)->locale('es')->isoFormat('DD [de] MMMM YYYY') }}
                                @endif
                            </td>
                        </tr>
                        @foreach($rows as $a)
                            <tr>
                                <td style="font-weight:700;color:var(--purple)">{{ $a->folio_general }}</td>
                                <td style="font-size:12px;color:var(--gray-muted)">FCONA-0040</td>
                                <td>
                                    <div style="font-weight:600">{{ $a->nombre_proveedor }}</div>
                                    <div style="font-size:11px;color:var(--gray-muted)">{{ $a->codigo_proveedor }}</div>
                                </td>
                                <td>{{ $a->departamento }}</td>
                                <td class="monto">${{ number_format((float)$a->total_banco, 2) }}</td>
                                <td style="font-size:12px">
                                    @if($a->factura_id)
                                        @php $facAplicada = \App\Models\Factura::find($a->factura_id); @endphp
                                        <span style="color:var(--purple);font-weight:600">{{ $facAplicada?->folio_cfdi ?: 'FAC-'.$a->factura_id }}</span>
                                    @else
                                        <span style="color:var(--gray-muted)">—</span>
                                    @endif
                                </td>
                                <td><span class="pill {{ $a->estatus }}">{{ ucfirst($a->estatus) }}</span></td>
                                <td style="text-align:right">
                                    <a href="{{ route('admin.anticipos.formato', $a) }}" target="_blank" style="font-size:11px;color:var(--purple);font-weight:600;text-decoration:none;padding:4px 10px;border:1px solid var(--purple);border-radius:6px">PDF</a>
                                    <span style="display:inline-flex;padding:3px 8px;border-radius:999px;background:var(--purple-subtle,#f3e8ff);color:var(--purple);font-size:11px;font-weight:700;margin-left:6px">{{ $a->created_at?->format('h:i a') }}</span>
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
