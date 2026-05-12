@extends('layouts.proveedor')
@section('title', 'Inventario')
@section('hero')
<div class="hero-band">
    <h1>Inventario</h1>
    <p>Consulta el estado de tus productos, stock y niveles de reorden</p>
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
    .inv-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .inv-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .inv-table tr:last-child td{border-bottom:none}
    .inv-table tr:hover td{background:var(--purple-subtle)}
    .badge-stock{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-stock.ok{background:var(--green-bg);color:var(--green)}
    .badge-stock.low{background:var(--amber-bg);color:var(--amber)}
    .badge-stock.out{background:var(--red-bg);color:var(--red)}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;transition:var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}
    @media(max-width:768px){.inv-metrics{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')

<div class="inv-metrics">
    <div class="inv-metric">
        <div class="accent" style="background:var(--purple)"></div>
        <div class="inv-metric-label">SKUs activos</div>
        <div class="inv-metric-val">48</div>
        <div class="inv-metric-sub">Productos en catálogo</div>
    </div>
    <div class="inv-metric">
        <div class="accent" style="background:var(--green)"></div>
        <div class="inv-metric-label">Stock OK</div>
        <div class="inv-metric-val">42</div>
        <div class="inv-metric-sub">Nivel adecuado</div>
    </div>
    <div class="inv-metric">
        <div class="accent" style="background:var(--amber)"></div>
        <div class="inv-metric-label">Stock bajo</div>
        <div class="inv-metric-val">3</div>
        <div class="inv-metric-sub">Requiere atención</div>
    </div>
    <div class="inv-metric">
        <div class="accent" style="background:var(--red)"></div>
        <div class="inv-metric-label">Agotados</div>
        <div class="inv-metric-val">3</div>
        <div class="inv-metric-sub">Sin stock disponible</div>
    </div>
</div>

<div class="inv-card">
    <div class="inv-card-head">
        <h3>Detalle de inventario</h3>
        <button class="btn-export" onclick="exportTable('tablaInventario', 'Inventario')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exportar Excel
        </button>
    </div>
    <div style="overflow-x:auto;">
        <table class="inv-table" id="tablaInventario">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Stock actual</th>
                    <th>Mínimo</th>
                    <th>Máximo</th>
                    <th>Demanda/mes</th>
                    <th>Cobertura</th>
                    <th>Tendencia</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @php
                $items = [
                    ['SAL-001', 'Resina epóxica industrial', 850, 200, 1000, 280, 'creciente', 'ok'],
                    ['SAL-003', 'Solvente grado técnico', 320, 150, 800, 180, 'estable', 'ok'],
                    ['SAL-005', 'Pigmento base agua', 90, 100, 500, 120, 'creciente', 'low'],
                    ['SAL-007', 'Catalizador rápido', 45, 80, 400, 60, 'decreciente', 'low'],
                    ['SAL-009', 'Aditivo antioxidante', 0, 50, 300, 40, 'decreciente', 'out'],
                    ['SAL-011', 'Fibra de refuerzo', 600, 100, 700, 150, 'creciente', 'ok'],
                    ['SAL-015', 'Adhesivo estructural', 220, 80, 500, 90, 'estable', 'ok'],
                    ['SAL-018', 'Disolvente especial', 15, 50, 250, 35, 'decreciente', 'low'],
                    ['SAL-020', 'Sellador industrial', 0, 60, 300, 45, 'estable', 'out'],
                    ['SAL-022', 'Recubrimiento base', 0, 40, 200, 30, 'decreciente', 'out'],
                ];
                @endphp
                @foreach($items as [$codigo, $nombre, $stock, $min, $max, $demanda, $tendencia, $estado])
                @php
                    $cobertura = $demanda > 0 ? round($stock / $demanda, 1) : 0;
                    $estadoLabel = $estado === 'ok' ? 'OK' : ($estado === 'low' ? 'Bajo' : 'Agotado');
                    $trendVal = $tendencia === 'creciente' ? rand(3,12) : ($tendencia === 'decreciente' ? rand(-12,-1) : 0);
                @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $codigo }}</td>
                    <td>{{ $nombre }}</td>
                    <td style="font-weight:600;">{{ number_format($stock) }}</td>
                    <td>{{ number_format($min) }}</td>
                    <td>{{ number_format($max) }}</td>
                    <td>{{ number_format($demanda) }}</td>
                    <td>{{ $cobertura }} meses</td>
                    <td>@include('partials.trend-arrow', ['value' => $trendVal])</td>
                    <td><span class="badge-stock {{ $estado }}">{{ $estadoLabel }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<p style="font-size:11px;color:var(--gray-muted);text-align:center;">⚠ Datos de prueba — se reemplazarán con datos reales cuando se conecte la API</p>

@endsection
@push('scripts')
<script>
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
