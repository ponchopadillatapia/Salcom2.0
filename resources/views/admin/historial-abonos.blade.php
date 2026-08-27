@extends('layouts.admin')
@section('title', 'Historial de abonos')
@section('hero')
<div class="hero-band">
    <h1>Historial de abonos</h1>
    <p>Pólizas registradas · facturas liquidadas</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .inv-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px}
    .inv-metric{background:var(--white);border:1px solid var(--border-light, var(--border));border-radius:14px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:box-shadow .15s,border-color .15s;text-decoration:none;color:inherit;display:block}
    .inv-metric:hover{border-color:var(--purple-mid,#c4b5e0);box-shadow:var(--shadow-sm)}
    .inv-metric.is-active{border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.12)}
    .inv-metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .inv-metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px}
    .inv-metric-val{font-size:28px;font-weight:700;color:var(--gray-text);line-height:1}
    .inv-metric-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}

    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:16px 18px;margin-bottom:20px}
    .filter-form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:140px;flex:1}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase}
    .filter-field input,.filter-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;outline:none;background:var(--white)}
    .filter-field input:focus,.filter-field select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .date-row td{background:var(--purple-subtle)!important;font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .hora-pill{display:inline-flex;padding:3px 8px;border-radius:999px;background:var(--purple-subtle);color:var(--purple);font-size:11px;font-weight:700;white-space:nowrap}
    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.pendiente{background:#f3f4f6;color:#6b7280}
    .badge-est.programada{background:#fef2f2;color:#dc2626}
    .badge-est.pagada{background:#fefce8;color:#ca8a04}
    .badge-est.liquidada{background:#ecfdf5;color:#16a34a}
    .badge-est.cancelada{background:#fef2f2;color:#7f1d1d}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted);font-size:14px}
    .prov-chip{display:inline-flex;align-items:center;margin-top:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;color:var(--purple);background:var(--purple-subtle);border:1px solid rgba(107,63,160,.28);line-height:1.3}

    @media(max-width:768px){.inv-metrics{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')

{{-- KPIs --}}
<div class="inv-metrics anim">
    <a href="{{ route('admin.historial-abonos') }}" class="inv-metric {{ $estatus === '' ? 'is-active' : '' }}">
        <div class="accent" style="background:var(--purple,#6B3FA0)"></div>
        <div class="inv-metric-label">Liquidadas</div>
        <div class="inv-metric-val">{{ $kpiLiquidadas }}</div>
        <div class="inv-metric-sub">Con póliza registrada</div>
    </a>
    <a href="{{ route('admin.historial-abonos', ['estatus' => 'pagada']) }}" class="inv-metric {{ $estatus === 'pagada' ? 'is-active' : '' }}">
        <div class="accent" style="background:var(--green,#16a34a)"></div>
        <div class="inv-metric-label">Pagadas</div>
        <div class="inv-metric-val">{{ $kpiPagadas }}</div>
        <div class="inv-metric-sub">Pendientes de póliza</div>
    </a>
    <a href="{{ route('admin.historial-abonos', ['estatus' => 'todas']) }}" class="inv-metric {{ $estatus === 'todas' ? 'is-active' : '' }}">
        <div class="accent" style="background:#2563eb"></div>
        <div class="inv-metric-label">Todas</div>
        <div class="inv-metric-val">{{ $kpiTotal }}</div>
        <div class="inv-metric-sub">Liquidadas + Pagadas</div>
    </a>
</div>

{{-- Filtros --}}
<div class="filters-panel anim" style="animation-delay:.03s">
    <form method="get" class="filter-form">
        <div class="filter-field" style="flex:2">
            <label>Buscar</label>
            <input type="text" name="q" value="{{ $buscar }}" placeholder="Folio, código o proveedor...">
        </div>
        <div class="filter-field">
            <label>Desde</label>
            <input type="date" name="desde" value="{{ request('desde') }}">
        </div>
        <div class="filter-field">
            <label>Hasta</label>
            <input type="date" name="hasta" value="{{ request('hasta') }}">
        </div>
        <input type="hidden" name="estatus" value="{{ $estatus }}">
        <button type="submit" class="btn-primary">Filtrar</button>
        @if($buscar !== '' || request('desde') || request('hasta'))
            <a href="{{ route('admin.historial-abonos', ['estatus' => $estatus]) }}" style="font-size:12px;color:var(--purple);font-weight:600;text-decoration:none">✕ Limpiar</a>
        @endif
    </form>
</div>

{{-- Tabla agrupada por fecha --}}
@php
    $agrupados = $facturas->getCollection()->groupBy(function ($f) {
        return $f->updated_at ? $f->updated_at->format('Y-m-d') : 'sin-fecha';
    });
@endphp

<div class="adm-section anim" style="animation-delay:.06s">
    <div class="adm-section-head">
        <div>
            <h4>Historial de abonos</h4>
            <div class="adm-section-meta">{{ $facturas->total() }} resultado{{ $facturas->total() !== 1 ? 's' : '' }} · agrupado por fecha</div>
        </div>
    </div>

    @if($facturas->isEmpty())
        <div class="empty-state">No hay registros con esos filtros.</div>
    @else
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Proveedor</th>
                        <th>Nº Póliza</th>
                        <th>Monto</th>
                        <th>Estatus</th>
                        <th>Cuenta</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($agrupados as $fechaKey => $rows)
                        <tr class="date-row">
                            <td colspan="7">
                                @if($fechaKey === 'sin-fecha')
                                    Sin fecha
                                @else
                                    {{ \Illuminate\Support\Carbon::parse($fechaKey)->locale('es')->isoFormat('DD [de] MMMM YYYY') }}
                                @endif
                            </td>
                        </tr>
                        @foreach($rows as $f)
                            @php
                                $detalle = is_array($f->validacion_detalle) ? ($f->validacion_detalle['abono_interno'] ?? []) : [];
                                $polizaNum = $detalle['poliza'] ?? '';
                                $cuentaAbono = $detalle['cuenta'] ?? '';
                                $hora = $f->updated_at ? $f->updated_at->format('h:i a') : '—';
                            @endphp
                            <tr style="cursor:pointer" onclick="abrirDetalle({{ $f->id }})">
                                <td style="font-weight:700;color:var(--purple)">{{ $f->folio_cfdi ? 'FAC'.$f->folio_cfdi : 'FAC-'.$f->id }}</td>
                                <td>
                                    <div style="font-weight:600">{{ \App\Models\ProveedorUser::where('codigo', $f->codigo_proveedor)->value('nombre') ?? $f->codigo_proveedor }}</div>
                                    <span class="prov-chip">{{ $f->codigo_proveedor }}</span>
                                </td>
                                <td style="font-weight:700">
                                    @if($polizaNum)
                                        {{ $polizaNum }}
                                    @else
                                        <span style="color:#dc2626;font-size:12px">● Sin póliza</span>
                                    @endif
                                </td>
                                <td class="monto">${{ number_format((float)$f->monto_pagado, 2) }}</td>
                                <td>
                                    @if($f->estatus === 'liquidada')
                                        <span class="badge-est liquidada">Liquidada</span>
                                    @elseif($f->estatus === 'pagada')
                                        <span class="badge-est pagada">Pagada</span>
                                    @elseif($f->estatus === 'programada')
                                        <span class="badge-est programada">Programada</span>
                                    @elseif($f->estatus === 'cancelada')
                                        <span class="badge-est cancelada">Cancelada</span>
                                    @else
                                        <span class="badge-est pendiente">{{ ucfirst($f->estatus) }}</span>
                                    @endif
                                </td>
                                <td style="font-size:12px;color:var(--gray-muted)">{{ $cuentaAbono ?: '—' }}</td>
                                <td style="text-align:right"><span class="hora-pill">{{ $hora }}</span></td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($facturas->hasPages())
            <div style="padding:14px;display:flex;justify-content:center">{{ $facturas->links() }}</div>
        @endif
    @endif
</div>

{{-- Modal detalle --}}
<div class="modal-overlay" id="modal-overlay">
    <div class="modal-box" id="modal-contenido"></div>
</div>

@endsection
@push('styles')
<style>
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9999;align-items:center;justify-content:center}
    .modal-overlay.show{display:flex}
    .modal-box{background:#fff;border-radius:14px;max-width:520px;width:90%;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);padding:28px}
    .modal-title{font-size:16px;font-weight:800;color:var(--purple);margin-bottom:16px;display:flex;align-items:center;justify-content:space-between}
    .modal-close{background:none;border:none;font-size:22px;cursor:pointer;color:var(--gray-muted);line-height:1}
    .modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .modal-field label{display:block;font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;margin-bottom:3px}
    .modal-field div{font-size:14px;font-weight:600;color:var(--gray-text)}
</style>
@endpush
@push('scripts')
<script>
@php
$jsonData = $facturas->getCollection()->mapWithKeys(function($f) {
    $d = is_array($f->validacion_detalle) ? ($f->validacion_detalle['abono_interno'] ?? []) : [];
    return [$f->id => [
        'folio' => $f->folio_cfdi ?: 'FAC-'.$f->id,
        'proveedor' => $f->codigo_proveedor,
        'poliza' => $d['poliza'] ?? '—',
        'fecha' => $d['fecha'] ?? '—',
        'serie' => $d['serie'] ?? '—',
        'cuenta' => $d['cuenta'] ?? '—',
        'moneda' => $d['moneda'] ?? 'MXN',
        'tipo_cambio' => $d['tipo_cambio'] ?? '1.0000',
        'notas' => $d['notas'] ?? '—',
        'monto' => number_format((float)$f->monto_pagado, 2),
        'estatus' => $f->estatus,
        'registrado' => $d['registrado_at'] ?? ($f->updated_at ? $f->updated_at->format('d/m/Y h:i a') : '—'),
    ]];
});
@endphp
var facturasData = @json($jsonData);

function abrirDetalle(id) {
    var d = facturasData[id];
    if (!d) return;
    var html = '<div class="modal-title"><span>Detalle de abono · ' + d.folio + '</span><button class="modal-close" onclick="cerrarModal()">✕</button></div>';
    html += '<div class="modal-grid">';
    html += '<div class="modal-field"><label>Folio factura</label><div>' + d.folio + '</div></div>';
    html += '<div class="modal-field"><label>Proveedor</label><div>' + d.proveedor + '</div></div>';
    html += '<div class="modal-field"><label>Nº Póliza</label><div style="color:var(--purple);font-weight:800">' + d.poliza + '</div></div>';
    html += '<div class="modal-field"><label>Fecha póliza</label><div>' + d.fecha + '</div></div>';
    html += '<div class="modal-field"><label>Serie</label><div>' + d.serie + '</div></div>';
    html += '<div class="modal-field"><label>Cuenta</label><div>' + d.cuenta + '</div></div>';
    html += '<div class="modal-field"><label>Moneda</label><div>' + d.moneda + '</div></div>';
    html += '<div class="modal-field"><label>Tipo cambio</label><div>' + d.tipo_cambio + '</div></div>';
    html += '<div class="modal-field"><label>Monto pagado</label><div style="color:var(--green);font-weight:700">$' + d.monto + '</div></div>';
    html += '<div class="modal-field"><label>Estatus</label><div>' + d.estatus + '</div></div>';
    html += '<div class="modal-field" style="grid-column:span 2"><label>Notas</label><div>' + d.notas + '</div></div>';
    html += '<div class="modal-field" style="grid-column:span 2"><label>Registrado</label><div style="font-size:12px;color:var(--gray-muted)">' + d.registrado + '</div></div>';
    html += '</div>';
    document.getElementById('modal-contenido').innerHTML = html;
    document.getElementById('modal-overlay').classList.add('show');
}
function cerrarModal() {
    document.getElementById('modal-overlay').classList.remove('show');
}
document.getElementById('modal-overlay').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
</script>
@endpush
