@extends('layouts.admin')
@section('title', 'Facturas — ' . ($proveedor->nombre ?? $codigo))
@section('hero')
<div class="hero-band">
    <h1>{{ $proveedor->nombre ?? $codigo }}</h1>
    <p>Código: {{ $codigo }} · Detalle de adeudos pendientes</p>
</div>
@endsection
@push('styles')
<style>
    .summary{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .sum-card{background:var(--white);border:1px solid var(--border-light);border-radius:12px;padding:18px;text-align:center}
    .sum-val{font-size:24px;font-weight:800;line-height:1;margin-bottom:4px}
    .sum-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase}
    .toolbar-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:12px;font-weight:600;color:#fff;background:#059669;border-radius:8px;text-decoration:none;transition:all .15s}
    .btn-export:hover{background:#047857}
    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden}
    .table-head{padding:14px 20px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:10px 14px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:10px 14px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-pagada{background:#ecfdf5;color:#059669}
    .badge-pendiente{background:#fefce8;color:#d97706}
    .badge-vencida{background:#fef2f2;color:#dc2626}
    .dias-vencido{font-size:12px;font-weight:700;color:#dc2626}
    .empty{text-align:center;padding:32px;color:var(--gray-muted);font-size:13px}
    .pag-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none}
    .oc-filters{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;padding:14px 20px;border-bottom:1px solid var(--border-light);background:#fafafa}
    .oc-filters label{display:flex;flex-direction:column;gap:4px;font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase}
    .oc-filters input{border:1.5px solid var(--border);border-radius:8px;padding:8px 10px;font-size:13px;font-family:inherit}
    .btn-primary{padding:9px 16px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit}
    .oc-alert{margin:14px 20px;padding:12px 14px;border-radius:10px;font-size:13px;background:#fff7ed;color:#c2410c;border:1px solid #fdba74}
    .oc-meta{padding:10px 20px;font-size:12px;color:var(--gray-muted);border-bottom:1px solid var(--border-light)}
    .section-gap{margin-top:28px}
    @media(max-width:768px){.summary{grid-template-columns:1fr 1fr}.table-card{overflow-x:auto}}
</style>
@endpush
@section('content')

<a class="pag-back" href="{{ route('admin.proveedores', ['tab' => 'facturas']) }}">← Volver a proveedores</a>

@php
    // KPIs de la BD (facturas del portal)
    $pendientes = $facturas->where('estatus', 'pendiente');
    $deudaBd = $pendientes->sum('total');
    $pagadoBd = $facturas->where('estatus', 'pagada')->sum('total');
    $vencidasBd = $pendientes->filter(fn($f) => $f->fecha_vencimiento && $f->fecha_vencimiento->isPast());
    $diasMaxVencido = $vencidasBd->count() > 0 ? $vencidasBd->max(fn($f) => $f->fecha_vencimiento->diffInDays(now())) : 0;

    // KPIs COMBINADOS: BD + Wiese (las variables wiese* llegan del controlador)
    $totalDeuda   = $deudaBd + ($wieseDeuda ?? 0);
    $totalPagado  = $pagadoBd + ($wiesePagado ?? 0);
    $totalVencidas = $vencidasBd->count() + ($wieseVencidas ?? 0);
@endphp

<div class="summary">
    <div class="sum-card"><div class="sum-val" style="color:#dc2626">${{ number_format($totalDeuda, 0) }}</div><div class="sum-label">Deuda total</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#059669">${{ number_format($totalPagado, 0) }}</div><div class="sum-label">Pagado</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#dc2626">{{ $totalVencidas }}</div><div class="sum-label">Vencidas (BD + Wiese)</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#d97706">{{ $diasMaxVencido }}</div><div class="sum-label">Máx. días vencido</div></div>
</div>

<div class="toolbar-top">
    <span style="font-size:13px;color:var(--gray-muted)">{{ $facturas->count() }} facturas totales · Código portal: {{ $codigo }}@if(!empty($wieseCodigo) && $wieseCodigo !== $codigo) · Wiese: {{ $wieseCodigo }}@endif</span>
    <a href="{{ route('admin.proveedor-facturas', $codigo) }}?export=excel" class="btn-export">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exportar Excel
    </a>
</div>

<div class="table-card">
    <div class="table-head">Detalle de adeudos — {{ $proveedor->nombre ?? $codigo }}</div>
    @if($facturas->count())
    <table class="tbl">
        <thead>
            <tr>
                <th>Folio CFDI</th>
                <th>Producto</th>
                <th>Código producto</th>
                <th>Total</th>
                <th>Vencimiento</th>
                <th>Días vencido</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
        @foreach($facturas as $f)
            @php
                $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
                $diasV = $vencida ? $f->fecha_vencimiento->diffInDays(now()) : 0;
                $producto = null;
                if ($f->pedido_id) {
                    $pedido = \App\Models\Pedido::find($f->pedido_id);
                    if ($pedido && is_array($pedido->productos) && count($pedido->productos) > 0) {
                        $producto = $pedido->productos[0];
                    }
                }
            @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $f->folio_cfdi }}</td>
                <td>{{ $producto['nombre'] ?? 'Compra general' }}</td>
                <td style="color:var(--gray-muted)">{{ $producto['sku'] ?? $producto['codigo'] ?? '—' }}</td>
                <td style="font-weight:700;font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                <td style="color:{{ $vencida ? '#dc2626' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">
                    {{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                </td>
                <td>
                    @if($vencida)
                        <span class="dias-vencido">{{ $diasV }} días</span>
                    @elseif($f->estatus === 'pendiente')
                        <span style="color:var(--gray-muted)">Vigente</span>
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if($f->estatus === 'pagada')
                        <span class="badge badge-pagada">Pagada</span>
                    @elseif($vencida)
                        <span class="badge badge-vencida">Vencida</span>
                    @else
                        <span class="badge badge-pendiente">Pendiente</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <div class="empty">No hay facturas para este proveedor</div>
    @endif
</div>

{{-- Órdenes de compra desde Wiese --}}
<div class="table-card section-gap">
    <div class="table-head">Órdenes de compra (Wiese)</div>
    <form method="GET" action="{{ route('admin.proveedor-facturas', $codigo) }}" class="oc-filters">
        <label>
            Desde
            <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}">
        </label>
        <label>
            Hasta
            <input type="date" name="fecha_fin" value="{{ $fechaFin }}">
        </label>
        <button type="submit" class="btn-primary">Actualizar</button>
    </form>

    @if($ocError)
        <div class="oc-alert">{{ $ocError }}</div>
    @else
        <div class="oc-meta">
            Código Wiese: <strong>{{ $wieseCodigo }}</strong>
            · {{ number_format($ocTotal) }} documento{{ $ocTotal === 1 ? '' : 's' }}
            @if($ocTotal > $ocLimit)
                · mostrando los primeros {{ $ocLimit }} (acorta el rango de fechas para ver menos)
            @endif
        </div>
        @if($ocItems->count())
        <div style="overflow-x:auto;">
            <table class="tbl" id="oc-tabla">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Razón social</th>
                        <th>RFC</th>
                        <th>Total</th>
                        <th>Pendiente</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($ocItems as $i => $doc)
                    @php
                        $serie = $doc['cseriedocumento'] ?? '';
                        $folio = $doc['cfolio'] ?? '';
                        $folioNum = is_numeric($folio) ? (string)(int)$folio : (string)$folio;
                        $folioDisp = trim($serie.$folioNum) !== '' ? $serie.$folioNum : '—';
                        $fecha = $doc['cfecha'] ?? null;
                        try {
                            $fechaFmt = $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') : '—';
                        } catch (\Throwable) {
                            $fechaFmt = is_string($fecha) ? $fecha : '—';
                        }
                        // Estatus segun regla de Alan: cancelado=1 -> cancelada; pendiente>0 -> pendiente; else pagada
                        $cancelado = (int)($doc['ccancelado'] ?? 0) === 1;
                        $pendienteMonto = (float)($doc['cpendiente'] ?? 0);
                        if ($cancelado) { $estatusOc = 'cancelada'; }
                        elseif ($pendienteMonto > 0) { $estatusOc = 'pendiente'; }
                        else { $estatusOc = 'pagada'; }
                    @endphp
                    {{-- Clic en la fila abre el modal de detalle (mismo estilo que el portal del proveedor) --}}
                    <tr class="oc-row" data-idx="{{ $i }}" onclick="abrirModalOc({{ $i }})" style="cursor:pointer;">
                        <td style="font-weight:700;color:var(--purple)">{{ $folioDisp }}</td>
                        <td>{{ $fechaFmt }}</td>
                        <td>{{ $doc['crazonsocial'] ?? '—' }}</td>
                        <td style="color:var(--gray-muted)">{{ $doc['crfc'] ?? '—' }}</td>
                        <td style="font-weight:700;font-variant-numeric:tabular-nums">${{ number_format((float)($doc['ctotal'] ?? 0), 2) }}</td>
                        <td style="font-variant-numeric:tabular-nums">${{ number_format($pendienteMonto, 2) }}</td>
                        <td>
                            @if($estatusOc === 'cancelada')
                                <span class="badge badge-vencida">Cancelada</span>
                            @elseif($estatusOc === 'pendiente')
                                <span class="badge badge-pendiente">Pendiente</span>
                            @else
                                <span class="badge badge-pagada">Pagada</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="text-align:center;padding:16px;" id="oc-vermas-wrap">
            <button type="button" class="btn-primary" id="oc-vermas" onclick="verMasOc()" style="display:none;">Ver más (50)</button>
            <div style="font-size:12px;color:var(--gray-muted);margin-top:8px;" id="oc-contador"></div>
        </div>

        {{-- Modal de detalle de la OC (mismo diseño que el portal del proveedor) --}}
        <div id="modalOc" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)cerrarModalOc()">
            <div style="background:#fff;border-radius:14px;padding:28px;max-width:500px;width:90%;max-height:80vh;overflow-y:auto;position:relative;">
                <button onclick="cerrarModalOc()" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray-muted);">&times;</button>
                <h3 style="font-size:16px;font-weight:700;margin-bottom:16px;color:var(--purple);">Detalle de Orden de Compra</h3>
                <div id="modalOcContenido"></div>
            </div>
        </div>

        <script>
            // Datos de las OC para el modal (se leen en JS, sin recargar).
            var ocDocs = @json($ocItems->values());

            // Paginación en el navegador: mostramos de 50 en 50 para que no se trabe.
            const OC_POR_PAGINA = 50;
            let ocMostradas = 0;

            function filasOc() {
                return Array.from(document.querySelectorAll('#oc-tabla tr.oc-row'));
            }

            function pintarOc() {
                const filas = filasOc();
                filas.forEach((fila, idx) => {
                    fila.style.display = idx < ocMostradas ? '' : 'none';
                });
                const total = filas.length;
                document.getElementById('oc-vermas').style.display = ocMostradas < total ? 'inline-flex' : 'none';
                document.getElementById('oc-contador').textContent =
                    'Mostrando ' + Math.min(ocMostradas, total) + ' de ' + total;
            }

            function verMasOc() {
                ocMostradas += OC_POR_PAGINA;
                pintarOc();
            }

            function filaOc(label, valor) {
                return '<tr><td style="padding:8px 0;font-weight:600;color:var(--gray-muted);width:140px;vertical-align:top;">' + label +
                       '</td><td style="padding:8px 0;color:var(--gray-text);">' + valor + '</td></tr>';
            }

            function abrirModalOc(idx) {
                var doc = ocDocs[idx];
                if (!doc) return;
                var serie = doc.cseriedocumento || '';
                var folio = doc.cfolio != null ? String(parseInt(doc.cfolio)) : '';
                var folioDisp = (serie + folio) || '—';
                var fecha = doc.cfecha ? new Date(doc.cfecha).toLocaleDateString('es-MX') : '—';
                var venc = doc.cfechavencimiento ? new Date(doc.cfechavencimiento).toLocaleDateString('es-MX') : '—';
                var cancelado = Number(doc.ccancelado) === 1;
                var pend = Number(doc.cpendiente || 0);
                var estatus = cancelado ? 'cancelada' : (pend > 0 ? 'pendiente' : 'pagada');
                var badge = estatus === 'pagada' ? 'badge-pagada' : (estatus === 'cancelada' ? 'badge-vencida' : 'badge-pendiente');

                var html = '<table style="width:100%;font-size:13px;border-collapse:collapse;">';
                html += filaOc('Folio', folioDisp);
                html += filaOc('Fecha', fecha);
                html += filaOc('Razón Social', doc.crazonsocial || '—');
                html += filaOc('RFC', doc.crfc || '—');
                html += filaOc('Total', '$' + Number(doc.ctotal || 0).toLocaleString('es-MX', {minimumFractionDigits:2}));
                html += filaOc('Pendiente', '$' + pend.toLocaleString('es-MX', {minimumFractionDigits:2}));
                html += filaOc('Vencimiento', venc);
                html += filaOc('Moneda', (Number(doc.cidmoneda) === 1 ? 'MXN' : 'USD') + ' · TC ' + (doc.ctipocambio || '1'));
                html += filaOc('Unidades', (doc.ctotalunidades ?? '—') + ' (pend: ' + (doc.cunidadespendientes ?? '—') + ')');
                html += filaOc('Referencia', doc.creferencia || '—');
                html += filaOc('Observaciones', doc.cobservaciones || '—');
                html += filaOc('Registró', doc.cusuario || '—');
                html += filaOc('Estatus', '<span class="badge ' + badge + '">' + estatus + '</span>');
                html += '</table>';

                document.getElementById('modalOcContenido').innerHTML = html;
                document.getElementById('modalOc').style.display = 'flex';
            }

            function cerrarModalOc() {
                document.getElementById('modalOc').style.display = 'none';
            }

            ocMostradas = OC_POR_PAGINA;
            pintarOc();
        </script>
        @else
            <div class="empty">Sin OC en ese rango de fechas</div>
        @endif
    @endif
</div>

@endsection
