@extends('layouts.proveedor')
@section('title', 'Inventario')
@section('hero')
<div class="hero-band">
    <h1>Inventario — Stock Máximo y Mínimo</h1>
    <p>Reporte de niveles de inventario · DDI: 90 días · Al: {{ now()->format('d/m/Y') }}</p>
</div>
@endsection
@push('styles')
<style>
    .inv-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
    .inv-metric{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px;position:relative;overflow:hidden}
    .inv-metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .inv-metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px}
    .inv-metric-val{font-size:28px;font-weight:700;color:var(--gray-text);line-height:1}
    .inv-metric-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .inv-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden;margin-bottom:24px}
    .inv-card-head{padding:16px 22px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
    .inv-card-head h3{font-size:15px;font-weight:700;color:var(--gray-text)}
    .inv-table{width:100%;border-collapse:collapse}
    .inv-table th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:10px 12px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .inv-table td{padding:10px 12px;font-size:12px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .inv-table tr:last-child td{border-bottom:none}
    .inv-table tr:hover td{background:var(--purple-subtle)}
    .inv-table td.num{text-align:right;font-variant-numeric:tabular-nums}
    .badge-stock{font-size:10px;font-weight:600;padding:3px 8px;border-radius:999px;display:inline-block}
    .badge-stock.ok{background:var(--green-bg);color:var(--green)}
    .badge-stock.low{background:var(--amber-bg);color:var(--amber)}
    .badge-stock.out{background:var(--red-bg);color:var(--red)}
    .badge-stock.over{background:var(--blue-bg);color:var(--blue)}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;transition:var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}
    .formula-box{background:var(--purple-subtle);border-radius:10px;padding:14px 18px;margin-bottom:24px;font-size:12px}
    .formula-box strong{color:var(--purple)}
    @media(max-width:768px){.inv-metrics{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')

<div class="formula-box">
    <strong>Fórmula:</strong> Stock Mínimo = Consumo Diario × Días entrega proveedor &nbsp;|&nbsp;
    <strong>Stock Máximo</strong> = Consumo Diario × 90 días (DDI) &nbsp;|&nbsp;
    <strong>Cantidad a Pedir</strong> = Stock Máximo − Existencia − Pendiente de Recibir
</div>

@php
$ddi = 90;
$items = [
    ['SAL-001', 'Resina epóxica industrial', 'GUANGZHOU FANEI', 850, 280, 15, 0, 'KG', 85.00, 'Resinas'],
    ['SAL-003', 'Solvente grado técnico', 'INTERFLEX GROUP', 320, 180, 12, 50, 'LT', 42.50, 'Solventes'],
    ['SAL-005', 'Pigmento base agua', 'ALPHA AROMATICS', 90, 120, 20, 0, 'KG', 120.00, 'Pigmentos'],
    ['SAL-007', 'Catalizador rápido', 'QINGDAO GREENEI', 45, 60, 18, 30, 'KG', 210.00, 'Aditivos'],
    ['SAL-009', 'Aditivo antioxidante', 'RECOCHEMIC INC', 0, 40, 25, 0, 'KG', 55.00, 'Aditivos'],
    ['SAL-011', 'Fibra de refuerzo', 'SALCOM INDUSTRIE', 600, 150, 10, 100, 'KG', 320.00, 'Refuerzos'],
    ['SAL-015', 'Adhesivo estructural', 'DCC GROUP COMP', 220, 90, 14, 0, 'KG', 180.00, 'Resinas'],
    ['SAL-018', 'Disolvente especial', 'HANGZHOU HUALIC', 15, 35, 22, 0, 'LT', 65.00, 'Solventes'],
    ['SAL-020', 'Sellador industrial', 'BOBSON HYGIENE', 0, 45, 16, 0, 'KG', 95.00, 'Selladores'],
    ['SAL-022', 'Recubrimiento base', 'NINGBO REVIEW T', 0, 30, 30, 0, 'KG', 78.00, 'Pigmentos'],
];

// Colores por grupo/familia
$coloresGrupo = [
    'Resinas' => '#E3F2FD',     // Azul cielo
    'Solventes' => '#FFF9C4',   // Amarillo pastel
    'Pigmentos' => '#F3E5F5',   // Lila pastel
    'Aditivos' => '#E8F5E9',    // Verde pastel
    'Refuerzos' => '#FFF3E0',   // Naranja pastel
    'Selladores' => '#E0F7FA',  // Cyan pastel
];
$totalSKU = count($items);
$stockOk = 0; $stockLow = 0; $stockOut = 0; $stockOver = 0;
$rows = [];
foreach ($items as [$codigo, $nombre, $proveedor, $existencia, $consumoMes, $diasEntrega, $pendRecibir, $um, $precio, $grupo]) {
    $consumoDiario = round($consumoMes / 30, 2);
    $stockMinimo = round($consumoDiario * $diasEntrega);
    $stockMaximo = round($consumoDiario * $ddi);
    $diasInventario = $consumoDiario > 0 ? round($existencia / $consumoDiario) : 0;
    $totalAPedir = max(0, $stockMaximo - $existencia - $pendRecibir);
    $cobertura = $consumoDiario > 0 ? round($existencia / $consumoDiario) : 0;
    $ventasMes = $consumoMes * $precio;
    $colorFila = $coloresGrupo[$grupo] ?? '#FFFFFF';
    $porcentajeUso = $stockMaximo > 0 ? round(($existencia / $stockMaximo) * 100) : 0;
    $consumoAltoMes = round($consumoMes * 1.3); // Pico estimado: 30% arriba del promedio
    if ($existencia <= 0) { $estado = 'out'; $estadoLabel = 'Agotado'; $stockOut++; }
    elseif ($existencia < $stockMinimo) { $estado = 'low'; $estadoLabel = 'Bajo mínimo'; $stockLow++; }
    elseif ($existencia > $stockMaximo) { $estado = 'over'; $estadoLabel = 'Sobre stock'; $stockOver++; }
    else { $estado = 'ok'; $estadoLabel = 'OK'; $stockOk++; }
    $rows[] = compact('codigo','nombre','proveedor','existencia','consumoMes','consumoDiario','diasEntrega','stockMinimo','stockMaximo','diasInventario','pendRecibir','totalAPedir','cobertura','estado','estadoLabel','um','precio','ventasMes','grupo','colorFila','porcentajeUso','consumoAltoMes');
}
@endphp

<div class="inv-metrics">
    <div class="inv-metric" style="cursor:pointer;" onclick="filtrarInventario('out', this)">
        <div class="accent" style="background:var(--red)"></div>
        <div class="inv-metric-label">Agotados</div>
        <div class="inv-metric-val">{{ $stockOut }}</div>
        <div class="inv-metric-sub">Sin stock</div>
    </div>
    <div class="inv-metric" style="cursor:pointer;" onclick="filtrarInventario('low', this)">
        <div class="accent" style="background:var(--amber)"></div>
        <div class="inv-metric-label">Bajo mínimo</div>
        <div class="inv-metric-val">{{ $stockLow }}</div>
        <div class="inv-metric-sub">Requiere reorden</div>
    </div>
    <div class="inv-metric" style="cursor:pointer;" onclick="filtrarInventario('ok', this)">
        <div class="accent" style="background:var(--green)"></div>
        <div class="inv-metric-label">Stock OK</div>
        <div class="inv-metric-val">{{ $stockOk }}</div>
        <div class="inv-metric-sub">Dentro de rango</div>
    </div>
    <div class="inv-metric" style="cursor:pointer;" onclick="filtrarInventario('all', this)">
        <div class="accent" style="background:var(--purple)"></div>
        <div class="inv-metric-label">SKUs totales</div>
        <div class="inv-metric-val">{{ $totalSKU }}</div>
        <div class="inv-metric-sub">Productos en catálogo</div>
    </div>
</div>

<div class="inv-card">
    <div class="inv-card-head">
        <h3>Reporte Stock Máximo y Mínimo</h3>
        <a href="{{ route('proveedores.inventario.excel') }}" class="btn-export">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exportar Excel
        </a>
    </div>
    <div style="overflow-x:auto;">
        <table class="inv-table" id="tablaInventario">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Proveedor</th>
                    <th>U.M.</th>
                    <th>Precio</th>
                    <th>Existencia</th>
                    <th>%</th>
                    <th>Consumo/mes</th>
                    <th>Consumo alto</th>
                    <th>Ventas/mes</th>
                    <th>Consumo diario</th>
                    <th>Stock mínimo</th>
                    <th>Stock máximo</th>
                    <th>Días inventario</th>
                    <th>Días entrega</th>
                    <th>Pend. recibir</th>
                    <th>Cantidad a pedir</th>
                    <th>Cobertura</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                <tr style="background:{{ $r['colorFila'] }};" data-estado="{{ $r['estado'] }}">                    <td style="font-size:10px;font-weight:600;color:var(--gray-muted);">{{ $r['grupo'] }}</td>
                    <td style="font-weight:700;color:var(--purple)">{{ $r['codigo'] }}</td>
                    <td>{{ $r['nombre'] }}</td>
                    <td style="font-size:11px;color:var(--gray-muted);">{{ $r['proveedor'] }}</td>
                    <td>{{ $r['um'] }}</td>
                    <td class="num">${{ number_format($r['precio'], 2) }}</td>
                    <td class="num" style="font-weight:600;">{{ number_format($r['existencia'], 3) }}</td>
                    <td class="num" style="font-weight:700;color:{{ $r['porcentajeUso'] > 100 ? 'var(--red)' : ($r['porcentajeUso'] > 50 ? 'var(--green)' : 'var(--amber)') }};">{{ $r['porcentajeUso'] }}%</td>
                    <td class="num">{{ number_format($r['consumoMes'], 3) }}</td>
                    <td class="num" style="color:var(--red);font-weight:600;">{{ number_format($r['consumoAltoMes'], 3) }}</td>
                    <td class="num" style="font-weight:600;">${{ number_format($r['ventasMes'], 2) }}</td>
                    <td class="num">{{ number_format($r['consumoDiario'], 3) }}</td>
                    <td class="num" style="color:var(--amber);font-weight:600;">{{ number_format($r['stockMinimo'], 3) }}</td>
                    <td class="num" style="color:var(--green);font-weight:600;">{{ number_format($r['stockMaximo'], 3) }}</td>
                    <td class="num">{{ $r['diasInventario'] }} días</td>
                    <td class="num">{{ $r['diasEntrega'] }} días</td>
                    <td class="num">{{ number_format($r['pendRecibir'], 3) }}</td>
                    <td class="num" style="font-weight:700;color:{{ $r['totalAPedir'] > 0 ? 'var(--red)' : 'var(--green)' }};">{{ number_format($r['totalAPedir'], 3) }}</td>
                    <td class="num">{{ $r['cobertura'] }} días</td>
                    <td><span class="badge-stock {{ $r['estado'] }}">{{ $r['estadoLabel'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<p style="font-size:11px;color:var(--gray-muted);text-align:center;padding:12px;background:var(--gray-soft);border-radius:10px;">
    ⚠ Datos de prueba — Se conectará con la BD cuando Alan tenga la API lista. DDI configurado a 90 días.
</p>

@endsection
@push('scripts')
<script>
var filtroInvActivo = null;
function filtrarInventario(estado, card) {
    if (filtroInvActivo === estado) {
        filtroInvActivo = null;
        document.querySelectorAll('#tablaInventario tbody tr').forEach(f => f.style.display = '');
        document.querySelectorAll('.inv-metric').forEach(c => { c.style.boxShadow = ''; c.style.border = ''; });
        return;
    }
    filtroInvActivo = estado;
    document.querySelectorAll('#tablaInventario tbody tr').forEach(fila => {
        if (estado === 'all') { fila.style.display = ''; return; }
        fila.style.display = fila.getAttribute('data-estado') === estado ? '' : 'none';
    });
    document.querySelectorAll('.inv-metric').forEach(c => { c.style.boxShadow = ''; c.style.border = ''; });
    if (card) { card.style.boxShadow = '0 0 0 2px var(--purple)'; card.style.border = '1.5px solid var(--purple)'; }
}

function exportTable(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    let csv = [];
    table.querySelectorAll('tr').forEach(row => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];
        cols.forEach(col => { rowData.push('"' + col.innerText.replace(/"/g,'""').trim() + '"'); });
        csv.push(rowData.join(','));
    });
    const blob = new Blob(['\uFEFF' + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}
</script>
@endpush
