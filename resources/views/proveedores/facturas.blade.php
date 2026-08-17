@extends('layouts.proveedor')

@section('title', 'Mis Facturas')

@section('hero')
<div class="hero-band">
    <h1>Mis Facturas</h1>
    <p>Consulta todas las facturas que has subido</p>
</div>
@endsection

@push('styles')
<style>
    .ph-info { background: var(--purple-light); border-radius: 10px; padding: 12px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 24px; font-size: 13px; color: var(--gray-text); flex-wrap: wrap; }
    .ph-info strong { color: var(--purple-dark); }
    .ph-info .change-link { color: var(--purple); font-weight: 600; text-decoration: none; margin-left: 4px; cursor: pointer; }

    .ph-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
    .ph-select { border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 12px; font-size: 13px; font-family: inherit; color: var(--gray-text); background: var(--white); outline: none; }
    .ph-search { flex: 1; min-width: 200px; border: 1.5px solid var(--border); border-radius: 8px; padding: 8px 14px; font-size: 13px; font-family: inherit; color: var(--gray-text); outline: none; }
    .ph-search:focus { border-color: var(--purple-mid); }
    .btn-download { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1.5px solid var(--border); border-radius: 8px; background: var(--white); font-size: 13px; font-family: inherit; color: var(--gray-text); cursor: pointer; font-weight: 500; text-decoration: none; margin-left: auto; }
    .btn-download:hover { border-color: var(--purple-mid); color: var(--purple); }
    .btn-filter { padding: 8px 16px; background: var(--purple); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .btn-clear { padding: 8px 14px; background: var(--gray-soft); color: var(--gray-text); border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; }

    .card { background: var(--white); border-radius: 14px; border: 1px solid var(--border); overflow: hidden; }
    .tabla { width: 100%; border-collapse: collapse; }
    .tabla th { font-size: 12px; font-weight: 600; color: var(--gray-text); padding: 14px 20px; text-align: left; border-bottom: 1px solid var(--border); background: var(--white); }
    .tabla th.sortable::after { content: ' ↓'; color: var(--purple-mid); }
    .tabla td { padding: 14px 20px; font-size: 13px; color: var(--gray-text); border-bottom: 1px solid var(--border); }
    .tabla tr:last-child td { border-bottom: none; }
    .tabla tr:hover td { background: var(--purple-light); }
    .tabla .link { color: var(--purple); font-weight: 600; text-decoration: none; }
    .monto { font-weight: 600; font-variant-numeric: tabular-nums; }
    .pill { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; display: inline-block; text-transform: capitalize; }
    .pill.pendiente { background: var(--amber-bg); color: var(--amber); }
    .pill.pagada, .pill.programada { background: var(--green-bg); color: var(--green); }
    .pill.aprobada, .pill.validada { background: var(--purple-light); color: var(--purple); }
    .dias-count { font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.2; }
    .dias-count.warn { color: var(--amber); }
    .dias-count.late { color: var(--red); }
    .dias-sub { font-size: 10px; color: var(--gray-muted); margin-top: 2px; }
    .empty { text-align: center; padding: 48px 20px; color: var(--gray-muted); font-size: 14px; }
    .pagination-wrap { padding: 16px; display: flex; justify-content: center; }

    .inv-metrics{display:grid;grid-template-columns:repeat(6,1fr);gap:16px;margin-bottom:24px}
    .inv-metric{background:var(--white);border:1px solid var(--border-light, var(--border));border-radius:14px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:box-shadow .15s,border-color .15s;text-decoration:none;color:inherit;display:block}
    .inv-metric:hover{border-color:var(--purple-mid,#c4b5e0)}
    .inv-metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .inv-metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px}
    .inv-metric-val{font-size:28px;font-weight:700;color:var(--gray-text);line-height:1}
    .inv-metric-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}
    @media(max-width:768px){.inv-metrics{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
@php
    $nombre = $proveedor->nombre ?? session('proveedor_nombre', 'Proveedor');
    $codigoShow = $codigo ?: session('proveedor_codigo', '—');
    $desdeLabel = $filtros['fecha_desde'] ? \Carbon\Carbon::parse($filtros['fecha_desde'])->format('d/m/Y') : 'Inicio';
    $hastaLabel = $filtros['fecha_hasta'] ? \Carbon\Carbon::parse($filtros['fecha_hasta'])->format('d/m/Y') : 'Hoy';
@endphp

<div class="ph-info">
    <span>Proveedor: <strong>{{ $codigoShow }}</strong> · {{ $nombre }}</span>
    <span>Período: <strong id="periodoLabel">{{ $desdeLabel }} - {{ $hastaLabel }}</strong></span>
    <a class="change-link" id="btnCambiarPeriodo" onclick="document.getElementById('periodoForm').style.display='flex'; this.style.display='none'; return false;">Cambiar</a>
    <form id="periodoForm" method="GET" action="{{ route('proveedores.facturas') }}" style="display:none;align-items:center;gap:8px;flex-wrap:wrap;">
        <input type="hidden" name="campo" value="{{ $filtros['campo'] }}">
        <input type="hidden" name="q" value="{{ $filtros['q'] }}">
        <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}" style="border:1.5px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px;font-family:inherit;">
        <span style="color:var(--gray-muted);">—</span>
        <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}" style="border:1.5px solid var(--border);border-radius:6px;padding:6px 10px;font-size:12px;font-family:inherit;">
        <button type="submit" class="btn-filter" style="padding:6px 14px;font-size:12px;">Aplicar</button>
        <a href="{{ route('proveedores.facturas', request()->except(['fecha_desde','fecha_hasta'])) }}" class="btn-clear" style="padding:6px 14px;font-size:12px;">Quitar fechas</a>
    </form>
</div>

@php
    $kpis = $kpis ?? ['rechazadas' => 0, 'pendientes' => 0, 'pagadas' => 0, 'totales' => 0];
    $wk = $wieseKpis ?? ['canceladas' => 0, 'pendientes' => 0, 'pagadas' => 0, 'totales' => 0];
    $baseKpiQuery = array_filter([
        'fecha_desde' => $filtros['fecha_desde'] ?: null,
        'fecha_hasta' => $filtros['fecha_hasta'] ?: null,
    ]);
@endphp

@if(session('exito'))
<div class="ph-info" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;">
    <strong>{{ session('exito') }}</strong>
</div>
@endif

<div class="inv-metrics">
    <a class="inv-metric" href="{{ route('proveedores.facturas', array_merge($baseKpiQuery, ['campo' => 'estatus', 'q' => 'rechazada'])) }}">
        <div class="accent" style="background:var(--red, #dc2626)"></div>
        <div class="inv-metric-label">Canceladas</div>
        <div class="inv-metric-val" id="kpi-canceladas">{{ $kpis['rechazadas'] }}</div>
        <div class="inv-metric-sub">No vigentes</div>
    </a>
    <a class="inv-metric" href="{{ route('proveedores.facturas', array_merge($baseKpiQuery, ['campo' => 'estatus', 'q' => 'pendiente'])) }}">
        <div class="accent" style="background:var(--amber, #d97706)"></div>
        <div class="inv-metric-label">Pendientes</div>
        <div class="inv-metric-val" id="kpi-pendientes">{{ $kpis['pendientes'] }}</div>
        <div class="inv-metric-sub">Por pagar</div>
    </a>
    <a class="inv-metric" href="{{ route('proveedores.facturas', array_merge($baseKpiQuery, ['campo' => 'estatus', 'q' => 'abonada'])) }}">
        <div class="accent" style="background:#ea580c"></div>
        <div class="inv-metric-label">Abonadas</div>
        <div class="inv-metric-val" id="kpi-abonadas">{{ $kpis['abonadas'] }}</div>
        <div class="inv-metric-sub">Pago parcial</div>
    </a>
    <a class="inv-metric" href="{{ route('proveedores.facturas', array_merge($baseKpiQuery, ['campo' => 'estatus', 'q' => 'pagada'])) }}">
        <div class="accent" style="background:var(--green, #16a34a)"></div>
        <div class="inv-metric-label">Pagadas</div>
        <div class="inv-metric-val" id="kpi-pagadas">{{ $kpis['pagadas'] }}</div>
        <div class="inv-metric-sub">Ya liquidadas</div>
    </a>
    <a class="inv-metric" href="{{ route('proveedores.facturas') }}">
        <div class="accent" style="background:var(--purple, #6B3FA0)"></div>
        <div class="inv-metric-label">Facturas totales</div>
        <div class="inv-metric-val" id="kpi-totales">{{ $kpis['totales'] }}</div>
        <div class="inv-metric-sub">Todas</div>
    </a>
</div>

<form method="GET" action="{{ route('proveedores.facturas') }}" class="ph-toolbar">
    @if($filtros['fecha_desde'])<input type="hidden" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}">@endif
    @if($filtros['fecha_hasta'])<input type="hidden" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}">@endif
    <select class="ph-select" name="campo">
        <option value="folio" {{ $filtros['campo'] === 'folio' ? 'selected' : '' }}>Folio / UUID</option>
        <option value="monto" {{ $filtros['campo'] === 'monto' ? 'selected' : '' }}>Monto</option>
        <option value="estatus" {{ $filtros['campo'] === 'estatus' ? 'selected' : '' }}>Estatus</option>
    </select>
    <input type="text" class="ph-search" name="q" value="{{ $filtros['q'] }}" placeholder="Buscar...">
    <button type="submit" class="btn-filter">Filtrar</button>
    @if($filtros['q'] !== '' || $filtros['fecha_desde'] || $filtros['fecha_hasta'])
        <a href="{{ route('proveedores.facturas') }}" class="btn-clear">Limpiar</a>
    @endif
    <a class="btn-download" href="{{ route('proveedores.facturas.excel', request()->query()) }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Descargar
    </a>
</form>

<div class="card">
    @if($facturas->isEmpty())
        <div class="empty">No hay facturas registradas todavía.<br><span style="font-size:12px;">Súbelas desde Alta Facturas.</span></div>
    @else
        <table class="tabla" id="tablaFacturas">
            <thead>
                <tr>
                    <th class="sortable">Fecha</th>
                    <th>Folio</th>
                    <th>Monto</th>
                    <th>Abonado</th>
                    <th>Días</th>
                    <th>Estatus</th>
                    <th>Último abono</th>
                </tr>
            </thead>
            <tbody>
                @foreach($facturas as $f)
                    @php
                        $vd = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];
                        $moneda = strtoupper($vd['moneda'] ?? $vd['cfdi']['moneda'] ?? 'MXN');
                        $saldo = round((float)$f->total - (float)($f->monto_pagado ?? 0), 2);
                        $restantes = $f->diasRestantes();
                        $diasLabel = $restantes === null
                            ? '—'
                            : ($restantes > 0 ? $restantes.' días' : ($restantes === 0 ? 'Vence hoy' : 'Vencida ('.abs($restantes).')'));
                    @endphp
                    <tr class="fac-row" style="cursor:pointer"
                        data-folio="{{ $f->folio_cfdi ?: '—' }}"
                        data-uuid="{{ $f->uuid_cfdi ?? '—' }}"
                        data-fecha="{{ $f->created_at?->format('d/m/Y') ?? '—' }}"
                        data-vencimiento="{{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}"
                        data-dias="{{ $diasLabel }}"
                        data-plazo="{{ $f->dias_plazo ? $f->dias_plazo.' días' : '—' }}"
                        data-monto="${{ number_format((float)$f->monto, 2) }}"
                        data-iva="${{ number_format((float)$f->monto_iva, 2) }}"
                        data-total="${{ number_format((float)$f->total, 2) }}"
                        data-pagado="${{ number_format((float)($f->monto_pagado ?? 0), 2) }}"
                        data-saldo="${{ number_format($saldo, 2) }}"
                        data-moneda="{{ $moneda }}"
                        data-estatus="{{ $f->estatus }}"
                        data-notas="{{ $f->notas ?? '' }}"
                        data-pdf="{{ $f->archivo_pdf ? asset('storage/'.$f->archivo_pdf) : '' }}"
                        data-xml="{{ $f->archivo_xml ? asset('storage/'.$f->archivo_xml) : '' }}">
                        <td>{{ $f->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="link">{{ $f->folio_cfdi ?: '—' }}</span>
                            @if($f->uuid_cfdi)
                                <div style="font-size:10px;color:var(--gray-muted);margin-top:2px;">{{ \Illuminate\Support\Str::limit($f->uuid_cfdi, 28) }}</div>
                            @endif
                        </td>
                        <td class="monto">${{ number_format((float) $f->total, 2) }}</td>
                        <td style="font-size:12px;color:{{ (float)($f->monto_pagado ?? 0) > 0 ? 'var(--green)' : 'var(--gray-muted)' }};font-weight:600">${{ number_format((float)($f->monto_pagado ?? 0), 2) }}</td>
                        <td>
                            @if($restantes === null)
                                —
                            @else
                                <div class="dias-count {{ $restantes < 0 ? 'late' : ($restantes <= 15 ? 'warn' : '') }}">{{ $diasLabel }}</div>
                                @if($f->dias_plazo)
                                    <div class="dias-sub">de {{ $f->dias_plazo }}</div>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if($f->estatus === 'pendiente' && (float)($f->monto_pagado ?? 0) > 0)
                                <span class="pill" style="background:#fff7ed;color:#ea580c">Abonada</span>
                            @else
                                <span class="pill {{ $f->estatus }}">{{ $f->estatus }}</span>
                            @endif
                        </td>
                        <td style="font-size:11px;color:var(--gray-muted)">
                            @if((float)($f->monto_pagado ?? 0) > 0)
                                {{ $f->updated_at?->format('d/m/Y h:i a') ?? '—' }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($facturas->hasPages())
            <div class="pagination-wrap">{{ $facturas->links() }}</div>
        @endif
    @endif
</div>

{{-- ═══ Facturas del sistema Wiese ═══ --}}
@if(isset($wieseFacturas))
<div style="margin-top:32px;">
    <h3 style="font-size:16px;font-weight:700;color:var(--gray-text);margin-bottom:16px;">Facturas registradas en sistema</h3>

    @if($wieseError)
        <div style="background:var(--amber-bg,#fef3cd);border:1px solid var(--amber,#d97706);border-radius:10px;padding:14px;font-size:13px;color:var(--amber);">
            {{ $wieseError }}
        </div>
    @elseif($wieseFacturas->isEmpty())
        <div class="empty">No se encontraron facturas con ese filtro.</div>
    @else
        <div class="card">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Pendiente</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($wieseFacturas as $idx => $doc)
                        <tr style="cursor:pointer;" onclick="abrirModalFactura({{ $idx }})">
                            <td><span class="link">{{ $doc['cfolio'] ?? '' }}</span></td>
                            <td>{{ isset($doc['cfecha']) ? \Carbon\Carbon::parse($doc['cfecha'])->format('d/m/Y') : '—' }}</td>
                            <td class="monto">${{ number_format((float) ($doc['ctotal'] ?? 0), 2) }}</td>
                            <td class="monto">${{ number_format((float) ($doc['cpendiente'] ?? 0), 2) }}</td>
                            <td>
                                @php $est = $doc['_estatus'] ?? 'pendiente'; @endphp
                                <span class="pill {{ $est === 'cancelada' ? 'rechazada' : $est }}">{{ $est }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($wieseTotal > 100)
                <div style="padding:12px 20px;font-size:12px;color:var(--gray-muted);text-align:center;">
                    Mostrando 100 de {{ number_format($wieseTotal) }} facturas
                </div>
            @endif
        </div>
    @endif
</div>

{{-- Modal detalle factura --}}
<div id="modalFactura" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)cerrarModal()">
    <div style="background:#fff;border-radius:14px;padding:28px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;position:relative;">
        <button onclick="cerrarModal()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray-muted);">&times;</button>
        <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:var(--purple);">Detalle de Factura</h3>
        <div id="modalContenido"></div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
var facturasWiese = @json($wieseFacturas ?? []);

function abrirModalFactura(idx) {
    var doc = facturasWiese[idx];
    if (!doc) return;
    var folio = doc.cfolio || '';
    var fecha = doc.cfecha ? new Date(doc.cfecha).toLocaleDateString('es-MX') : '—';
    var vencimiento = doc.cfechavencimiento ? new Date(doc.cfechavencimiento).toLocaleDateString('es-MX') : '—';
    var estatus = doc._estatus || 'pendiente';
    var pillClass = estatus === 'pagada' ? 'pagada' : (estatus === 'cancelada' ? 'rechazada' : 'pendiente');

    var html = '<table style="width:100%;font-size:13px;border-collapse:collapse;">';
    html += fila('Folio', folio);
    html += fila('Fecha', fecha);
    html += fila('Razón Social', doc.crazonsocial || '—');
    html += fila('RFC', doc.crfc || '—');
    html += fila('Total', '$' + Number(doc.ctotal || 0).toLocaleString('es-MX', {minimumFractionDigits:2}));
    html += fila('Pendiente', '$' + Number(doc.cpendiente || 0).toLocaleString('es-MX', {minimumFractionDigits:2}));
    html += fila('Vencimiento', vencimiento);
    html += fila('Referencia', doc.creferencia || '—');
    html += fila('Observaciones', doc.cobservaciones || '—');
    html += fila('Estatus', '<span class="pill '+pillClass+'">'+estatus+'</span>');
    html += '</table>';

    document.getElementById('modalContenido').innerHTML = html;
    document.getElementById('modalFactura').style.display = 'flex';
}

function fila(label, valor) {
    return '<tr><td style="padding:8px 0;font-weight:600;color:var(--gray-muted);width:140px;vertical-align:top;">'+label+'</td><td style="padding:8px 0;color:var(--gray-text);">'+valor+'</td></tr>';
}

function cerrarModal() {
    document.getElementById('modalFactura').style.display = 'none';
}

// Click en facturas Salcom (tabla principal)
document.querySelectorAll('#tablaFacturas .fac-row').forEach(function(tr) {
    tr.addEventListener('click', function() {
        var d = tr.dataset;
        var pillClass = d.estatus === 'pagada' ? 'pagada' : (d.estatus === 'programada' ? 'pagada' : 'pendiente');
        var html = '<table style="width:100%;font-size:13px;border-collapse:collapse;">';
        html += fila('Folio', d.folio);
        html += fila('UUID', '<span style="font-size:11px;word-break:break-all;">' + d.uuid + '</span>');
        html += fila('Fecha emisión', d.fecha);
        html += fila('Vencimiento', d.vencimiento);
        html += fila('Días restantes', d.dias + (d.plazo && d.plazo !== '—' ? ' <span style="color:var(--gray-muted);font-size:11px;">(plazo '+d.plazo+')</span>' : ''));
        html += fila('Moneda', d.moneda);
        html += fila('Subtotal', d.monto);
        html += fila('IVA', d.iva);
        html += fila('Total', '<strong>' + d.total + '</strong>');
        html += fila('Pagado', d.pagado);
        html += fila('Saldo pendiente', '<strong style="color:var(--purple)">' + d.saldo + '</strong>');
        html += fila('Estatus', '<span class="pill ' + pillClass + '">' + d.estatus + '</span>');
        if (d.notas) html += fila('Notas', d.notas);
        html += '</table>';

        // Botones de archivos
        html += '<div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">';
        if (d.pdf) html += '<a href="' + d.pdf + '" target="_blank" style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;font-weight:600;color:var(--purple);text-decoration:none;">Ver PDF</a>';
        else html += '<span style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;color:var(--gray-muted);">PDF (no adjunto)</span>';
        if (d.xml) html += '<a href="' + d.xml + '" target="_blank" style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;font-weight:600;color:var(--purple);text-decoration:none;">Ver XML</a>';
        else html += '<span style="padding:6px 12px;border:1px solid var(--border);border-radius:6px;font-size:12px;color:var(--gray-muted);">XML (no adjunto)</span>';
        html += '</div>';

        document.getElementById('modalContenido').innerHTML = html;
        document.getElementById('modalFactura').style.display = 'flex';
    });
});

// Polling KPIs cada 3 segundos (actualización en tiempo real)
(function(){
    var kpiUrl = '{{ route("proveedores.facturas.kpis") }}';
    var lastPendientes = {{ $kpis['pendientes'] }};
    var lastPagadas = {{ $kpis['pagadas'] }};

    function refreshKpis() {
        fetch(kpiUrl, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){return r.json()})
            .then(function(data){
                var el;
                el = document.getElementById('kpi-canceladas'); if(el) el.textContent = data.rechazadas;
                el = document.getElementById('kpi-pendientes'); if(el) el.textContent = data.pendientes;
                el = document.getElementById('kpi-abonadas'); if(el) el.textContent = data.abonadas;
                el = document.getElementById('kpi-pagadas'); if(el) el.textContent = data.pagadas;
                el = document.getElementById('kpi-totales'); if(el) el.textContent = data.totales;

                // Si cambió algo, recargar la página para mostrar estatus actualizados
                if (data.pendientes !== lastPendientes || data.pagadas !== lastPagadas) {
                    lastPendientes = data.pendientes;
                    lastPagadas = data.pagadas;
                    window.location.reload();
                }
            })
            .catch(function(){});
    }
    setInterval(refreshKpis, 1500);
})();
</script>
@endpush
