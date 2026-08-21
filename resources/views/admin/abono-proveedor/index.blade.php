@extends('layouts.admin')
@section('title', 'Abono al proveedor')
@section('hero')
<div class="hero-band">
    <h1>Abono al proveedor</h1>
    <p>Registro interno — confirmar que ya no se debe al proveedor</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .inv-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
    .inv-metric{background:var(--white);border:1px solid var(--border-light, var(--border));border-radius:14px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:box-shadow .15s,border-color .15s;text-decoration:none;color:inherit;display:block}
    .inv-metric:hover{border-color:var(--purple-mid,#c4b5e0);box-shadow:var(--shadow-sm)}
    .inv-metric.is-active{border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.12)}
    .inv-metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .inv-metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px}
    .inv-metric-val{font-size:28px;font-weight:700;color:var(--gray-text);line-height:1}
    .inv-metric-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:14px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tbody tr.prov-row{cursor:pointer}
    .admin-table tbody tr.prov-row:hover td{background:var(--purple-subtle)}
    .date-row td{background:var(--purple-subtle)!important;font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)}
    .code-link{font-weight:700;color:var(--purple);text-decoration:none}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .bubble-roja{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;margin-left:8px;border-radius:999px;background:var(--red,#dc2626);color:#fff;font-size:10px;font-weight:700;vertical-align:middle}
    .hora-bubble{display:inline-flex;align-items:center;justify-content:center;padding:3px 8px;border-radius:999px;background:var(--red,#dc2626);color:#fff;font-size:11px;font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums}
    .hora-bubble.leida{background:var(--gray-muted);opacity:.85}
    .dias-count{font-weight:700;font-variant-numeric:tabular-nums;line-height:1.2;white-space:nowrap}
    .dias-count.warn{color:var(--amber,#d97706)}
    .dias-count.late{color:var(--red,#dc2626)}
    .dias-sub{font-size:10px;color:var(--gray-muted);margin-top:2px;white-space:nowrap}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500;margin:0}

    .ab-card{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
    .ab-head{padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
    .ab-head h4{font-size:14px;font-weight:700;margin:0}
    .ab-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:14px 22px;border-bottom:1px solid var(--border)}
    .ab-input{border:1.5px solid var(--border);border-radius:8px;padding:8px 14px;font-size:13px;font-family:inherit;outline:none}
    .ab-input:focus{border-color:var(--purple)}
    .ab-table{width:100%;border-collapse:collapse}
    .ab-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;padding:12px 16px;text-align:left;border-bottom:1px solid var(--border)}
    .ab-table td{padding:14px 16px;font-size:13px;border-bottom:1px solid var(--border)}
    .ab-table tr:hover td{background:var(--purple-subtle)}
    .btn-confirm{padding:10px 24px;background:#6B3FA0;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit}
    .pill-liq{background:#ecfdf5;color:#059669;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px}

    .filter-bar{display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
    .filter-bar input{border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none;flex:1;min-width:220px}
    .filter-bar input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .link-quitar{font-size:12px;color:var(--purple);font-weight:600;text-decoration:none}

    @media(max-width:768px){.inv-metrics{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')

@if(session('ok'))
    <div class="anim" style="background:var(--green-bg);color:var(--green);border:1px solid var(--green);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ session('ok') }}</div>
@endif
@if(session('error'))
    <div class="anim" style="background:var(--red-bg);color:var(--red);border:1px solid var(--red);border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px">{{ session('error') }}</div>
@endif

{{-- KPIs --}}
<div class="inv-metrics anim" style="grid-template-columns:repeat(4,1fr)">
    <a href="{{ route('admin.abono-proveedor', ['estatus' => 'cancelada']) }}" class="inv-metric {{ $filtroEstatus === 'cancelada' ? 'is-active' : '' }}">
        <div class="accent" style="background:#dc2626"></div>
        <div class="inv-metric-label">Canceladas</div>
        <div class="inv-metric-val" style="color:#dc2626">{{ $kpiCanceladas }}</div>
        <div class="inv-metric-sub">Facturas canceladas</div>
    </a>
    <a href="{{ route('admin.abono-proveedor', ['estatus' => 'pendiente']) }}" class="inv-metric {{ $filtroEstatus === 'pendiente' ? 'is-active' : '' }}">
        <div class="accent" style="background:#eab308"></div>
        <div class="inv-metric-label">Pendientes</div>
        <div class="inv-metric-val" style="color:#eab308">{{ $kpiPendientes }}</div>
        <div class="inv-metric-sub">Sin programar</div>
    </a>
    <a href="{{ route('admin.abono-proveedor', ['estatus' => 'pagada']) }}" class="inv-metric {{ $filtroEstatus === 'pagada' ? 'is-active' : '' }}">
        <div class="accent" style="background:var(--green,#16a34a)"></div>
        <div class="inv-metric-label">Pagadas</div>
        <div class="inv-metric-val">{{ $kpiPagadas }}</div>
        <div class="inv-metric-sub">Pendientes de abono interno</div>
    </a>
    <a href="{{ route('admin.abono-proveedor') }}" class="inv-metric {{ $filtroEstatus === '' ? 'is-active' : '' }}">
        <div class="accent" style="background:var(--purple,#6B3FA0)"></div>
        <div class="inv-metric-label">Todas</div>
        <div class="inv-metric-val">{{ $kpiPagadas + $kpiLiquidadas + $kpiPendientes + $kpiCanceladas }}</div>
        <div class="inv-metric-sub">Todas las facturas</div>
    </a>
</div>

@if(($modo ?? 'proveedores') === 'proveedores')
    {{-- Vista principal: proveedores agrupados por fecha, estilo WhatsApp --}}
    @php
        $lista = $proveedoresAgrupados->sortByDesc(fn ($r) => $r->ultima_at ? $r->ultima_at->timestamp : 0)->values();
        $total = $lista->count();
        $agrupados = $lista->groupBy(function ($row) {
            return $row->ultima_at ? $row->ultima_at->format('Y-m-d') : 'sin-fecha';
        });
    @endphp

    <div class="filter-bar anim" style="animation-delay:.03s">
        <input type="text" id="buscar-prov-abono" placeholder="Buscar por nombre o código de proveedor..." value="{{ $buscar }}">
    </div>

    <div class="adm-section anim" style="animation-delay:.06s">
        <div class="adm-section-head">
            <div>
                <h4>Proveedores</h4>
                <div class="adm-section-meta">{{ $total }} resultado{{ $total !== 1 ? 's' : '' }} · lo más reciente arriba · burbuja roja = sin revisar</div>
            </div>
        </div>

        @if($lista->isEmpty())
            <div class="empty-state"><p>No hay proveedores con facturas registradas.</p></div>
        @else
            <div style="overflow-x:auto">
                <table class="admin-table" id="tbl-prov-abono">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Proveedor</th>
                            <th>Facturas</th>
                            <th>Monto</th>
                            <th>Vencimiento</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agrupados as $fechaKey => $rows)
                            <tr class="date-row">
                                <td colspan="6">
                                    @if($fechaKey === 'sin-fecha')
                                        Sin fecha
                                    @else
                                        {{ \Illuminate\Support\Carbon::parse($fechaKey)->locale('es')->isoFormat('DD [de] MMMM YYYY') }}
                                    @endif
                                </td>
                            </tr>
                            @foreach($rows as $row)
                                @php
                                    $sinLeer = ($row->notif_sin_leer ?? 0) > 0;
                                    $hora = $row->ultima_at ? $row->ultima_at->format('h:i a') : '—';
                                @endphp
                                <tr class="prov-row" data-codigo="{{ $row->codigo }}" data-nombre="{{ mb_strtolower($row->nombre) }}">
                                    <td><span class="code-link">{{ $row->codigo }}</span></td>
                                    <td style="font-weight:600;">
                                        {{ $row->nombre }}
                                        @if($sinLeer)
                                            <span class="bubble-roja" title="Sin revisar">{{ $row->notif_sin_leer > 9 ? '9+' : $row->notif_sin_leer }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center">{{ $row->num_facturas }}</td>
                                    <td class="monto">${{ number_format($row->monto_total, 2) }}</td>
                                    <td>
                                        @include('partials.celda-vencimiento', ['fecha' => $row->proximo_vencimiento ?? null])
                                    </td>
                                    <td style="text-align:right;">
                                        <span class="hora-bubble {{ $sinLeer ? '' : 'leida' }}">{{ $hora }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@else
    {{-- Vista filtrada: tabla plana de facturas --}}
    <div class="ab-card anim" style="animation-delay:.06s">
        <div class="ab-head">
            <h4>
                @if($filtroEstatus === 'pendiente') Facturas pendientes
                @elseif($filtroEstatus === 'cancelada') Facturas canceladas
                @elseif($filtroEstatus === 'liquidada') Facturas liquidadas
                @elseif($filtroEstatus === 'pagada') Facturas pagadas — pendientes de abono interno
                @elseif($filtroEstatus === 'todas') Todas las facturas
                @else Facturas
                @endif
            </h4>
            <a href="{{ route('admin.abono-proveedor') }}" class="link-quitar">✕ Quitar filtro</a>
        </div>

        @if($filtroEstatus === 'pagada')
        <form method="POST" action="{{ route('admin.abono-proveedor.confirmar') }}" id="form-abono-interno">
            @csrf
            <div class="ab-toolbar">
                <input type="text" class="ab-input" style="flex:1;min-width:200px" placeholder="Buscar por código o folio..." value="{{ $buscar }}" id="buscar-abono" onkeydown="if(event.key==='Enter'){event.preventDefault();window.location='?estatus=pagada&q='+this.value}">
                <div style="display:flex;align-items:center;gap:8px">
                    <label style="font-size:12px;font-weight:600;color:var(--gray-muted)">Nº Póliza:</label>
                    <input type="text" class="ab-input" name="poliza" required placeholder="Ej. 8969-5" style="width:120px">
                </div>
                <input type="text" class="ab-input" name="notas" placeholder="Notas (opcional)" style="width:180px">
                <button type="submit" class="btn-confirm">Confirmar abono</button>
            </div>
        @endif

            @if($facturas && $facturas->isEmpty())
                <div class="empty-state"><p>No hay facturas {{ $filtroEstatus }}.</p></div>
            @elseif($facturas)
                <table class="ab-table">
                    <thead>
                        <tr>
                            @if($filtroEstatus === 'pagada')<th><input type="checkbox" id="chk-all-ab" checked></th>@endif
                            <th>Proveedor</th>
                            <th>Folio</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Estatus</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($facturas as $f)
                            @php $saldo = round((float)$f->total - (float)$f->monto_pagado, 2); @endphp
                            <tr>
                                @if($filtroEstatus === 'pagada')<td><input type="checkbox" name="factura_ids[]" value="{{ $f->id }}" class="chk-ab" checked></td>@endif
                                <td style="font-weight:600">{{ $f->codigo_proveedor }}</td>
                                <td>{{ $f->folio_cfdi ?: $f->id }}</td>
                                <td>${{ number_format((float)$f->total, 2) }}</td>
                                <td class="monto">${{ number_format((float)$f->monto_pagado, 2) }}</td>
                                <td>{{ $saldo <= 0 ? '$0.00' : '$'.number_format($saldo, 2) }}</td>
                                <td>
                                    @if($f->estatus === 'pagada')<span style="color:var(--green);font-weight:700;font-size:11px">Pagada</span>
                                    @elseif($f->estatus === 'pendiente')<span style="color:#eab308;font-weight:700;font-size:11px">Pendiente</span>
                                    @elseif($f->estatus === 'cancelada')<span style="color:#dc2626;font-weight:700;font-size:11px">Cancelada</span>
                                    @elseif($f->estatus === 'liquidada')<span style="color:var(--purple);font-weight:700;font-size:11px">Liquidada</span>
                                    @elseif($f->estatus === 'programada')<span style="color:#2563eb;font-weight:700;font-size:11px">Programada</span>
                                    @else<span style="font-size:11px">{{ $f->estatus }}</span>
                                    @endif
                                </td>
                                <td style="font-size:12px;color:var(--gray-muted)">{{ $f->updated_at?->format('d/m/Y h:i a') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($facturas->hasPages())
                    <div style="padding:14px;display:flex;justify-content:center">{{ $facturas->links() }}</div>
                @endif
            @endif

        @if($filtroEstatus === 'pagada')
        </form>
        @endif
    </div>
@endif

@endsection
@push('scripts')
<script>
(function(){
    // Checkbox master
    var chkAll = document.getElementById('chk-all-ab');
    if (chkAll) {
        chkAll.addEventListener('change', function(){
            document.querySelectorAll('.chk-ab').forEach(function(c){ c.checked = chkAll.checked; });
        });
    }

    // Click en proveedor → abrir facturas de ese proveedor
    document.querySelectorAll('#tbl-prov-abono tr.prov-row').forEach(function(tr){
        tr.addEventListener('click', function(){
            var codigo = tr.getAttribute('data-codigo');
            window.location.href = '{{ route("admin.abono-proveedor") }}?estatus=todas&q=' + encodeURIComponent(codigo);
        });
    });

    // Filtrar proveedores en JS
    var inputProv = document.getElementById('buscar-prov-abono');
    if (inputProv) {
        inputProv.addEventListener('input', function(){
            var val = this.value.toLowerCase().trim();
            var rows = document.querySelectorAll('#tbl-prov-abono tr.prov-row');
            var dateRows = document.querySelectorAll('#tbl-prov-abono tr.date-row');

            rows.forEach(function(tr){
                var codigo = (tr.getAttribute('data-codigo') || '').toLowerCase();
                var nombre = tr.getAttribute('data-nombre') || '';
                tr.style.display = (val === '' || codigo.indexOf(val) !== -1 || nombre.indexOf(val) !== -1) ? '' : 'none';
            });

            dateRows.forEach(function(dr){
                var next = dr.nextElementSibling;
                var hasVisible = false;
                while(next && !next.classList.contains('date-row')){
                    if(next.classList.contains('prov-row') && next.style.display !== 'none'){ hasVisible = true; break; }
                    next = next.nextElementSibling;
                }
                dr.style.display = hasVisible ? '' : 'none';
            });
        });
    }
})();
</script>
@endpush
