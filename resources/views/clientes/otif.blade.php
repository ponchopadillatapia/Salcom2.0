@extends('layouts.cliente')

@section('title', 'OTIF — Entregas a tiempo y completas')

@section('hero')
<div class="hero-band">
    <h1>OTIF</h1>
    <p>On Time In Full — Métricas de entrega de tus pedidos con Salcom</p>
</div>
@endsection

@push('styles')
<style>
    .otif-wrap{max-width:1140px;margin:0 auto}
    .otif-charts{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:32px}
    .otif-chart-card{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:32px;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:var(--shadow-sm);transition:var(--transition)}
    .otif-chart-card:hover{border-color:var(--purple-mid);box-shadow:var(--shadow-md)}
    .otif-canvas-wrap{position:relative;width:180px;height:180px;margin-bottom:16px}
    .otif-canvas-wrap canvas{position:absolute;top:0;left:0}
    .otif-center{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
    .otif-percent{font-size:32px;font-weight:700;line-height:1}
    .otif-chart-label{font-size:14px;color:var(--gray-muted);font-weight:600;margin-top:8px}

    .otif-table-section{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:22px;margin-bottom:24px;box-shadow:var(--shadow-sm)}
    .otif-table-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
    .otif-table-header h4{font-size:15px;font-weight:700;color:var(--gray-text);margin:0}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;text-decoration:none;transition:var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}
    .otif-table{width:100%;border-collapse:collapse}
    .otif-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .otif-table td{padding:12px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .otif-table tr:last-child td{border-bottom:none}
    .otif-table tr:hover td{background:var(--purple-subtle)}
    .badge-ok{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--green-bg);color:var(--green)}
    .badge-late{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--red-bg);color:var(--red)}
    .badge-partial{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--amber-bg);color:var(--amber)}

    @media(max-width:768px){.otif-charts{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="otif-wrap">

    <div class="otif-charts">
        <div class="otif-chart-card">
            <div class="otif-canvas-wrap">
                <canvas id="clienteOtifDonutOT" width="180" height="180"></canvas>
                <div class="otif-center">
                    <div class="otif-percent" id="clienteOtPercent">98.5%</div>
                </div>
            </div>
            <span class="otif-chart-label">OT (On Time)</span>
        </div>
        <div class="otif-chart-card">
            <div class="otif-canvas-wrap">
                <canvas id="clienteOtifDonutIF" width="180" height="180"></canvas>
                <div class="otif-center">
                    <div class="otif-percent" id="clienteIfPercent">95%</div>
                </div>
            </div>
            <span class="otif-chart-label">IF (In Full)</span>
        </div>
    </div>

    <div class="otif-table-section">
        <div class="otif-table-header">
            <h4>On Time — Entregas a tiempo</h4>
            <button type="button" class="btn-export" onclick="exportTableClienteOtif('tableOTCliente', 'OTIF_Cliente_OnTime')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="otif-table" id="tableOTCliente">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Producto</th>
                        <th>Fecha compromiso</th>
                        <th>Fecha entrega</th>
                        <th>Días diferencia</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2026-005</td>
                        <td>Resina epóxica industrial</td>
                        <td>01/05/2026</td>
                        <td>01/05/2026</td>
                        <td>@include('partials.trend-arrow', ['value' => 0])</td>
                        <td><span class="badge-ok">A tiempo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2026-004</td>
                        <td>Pigmento base agua</td>
                        <td>28/04/2026</td>
                        <td>27/04/2026</td>
                        <td style="color:var(--green);font-weight:700;">↑ 1 día antes</td>
                        <td><span class="badge-ok">A tiempo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2026-002</td>
                        <td>Solvente grado técnico</td>
                        <td>25/04/2026</td>
                        <td>28/04/2026</td>
                        <td style="color:var(--red);font-weight:700;">↓ 3 días tarde</td>
                        <td><span class="badge-late">Retraso</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2026-001</td>
                        <td>Adhesivo estructural</td>
                        <td>22/04/2026</td>
                        <td>22/04/2026</td>
                        <td>@include('partials.trend-arrow', ['value' => 0])</td>
                        <td><span class="badge-ok">A tiempo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2025-118</td>
                        <td>Catalizador rápido</td>
                        <td>18/04/2026</td>
                        <td>19/04/2026</td>
                        <td style="color:var(--red);font-weight:700;">↓ 1 día tarde</td>
                        <td><span class="badge-late">Retraso</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="otif-table-section">
        <div class="otif-table-header">
            <h4>In Full — Entregas completas</h4>
            <button type="button" class="btn-export" onclick="exportTableClienteOtif('tableIFCliente', 'OTIF_Cliente_InFull')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="otif-table" id="tableIFCliente">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Producto</th>
                        <th>Cantidad solicitada</th>
                        <th>Cantidad entregada</th>
                        <th>% Cumplimiento</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2026-005</td>
                        <td>Resina epóxica industrial</td>
                        <td>500 kg</td>
                        <td>500 kg</td>
                        <td style="color:var(--green);font-weight:700;">100%</td>
                        <td><span class="badge-ok">Completo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2026-004</td>
                        <td>Pigmento base agua</td>
                        <td>200 kg</td>
                        <td>200 kg</td>
                        <td style="color:var(--green);font-weight:700;">100%</td>
                        <td><span class="badge-ok">Completo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2026-002</td>
                        <td>Solvente grado técnico</td>
                        <td>1,000 lt</td>
                        <td>1,000 lt</td>
                        <td style="color:var(--green);font-weight:700;">100%</td>
                        <td><span class="badge-ok">Completo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2026-001</td>
                        <td>Cloro industrial</td>
                        <td>300 kg</td>
                        <td>180 kg</td>
                        <td style="color:var(--red);font-weight:700;">60%</td>
                        <td><span class="badge-partial">Parcial</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">PED-2025-118</td>
                        <td>Catalizador rápido</td>
                        <td>150 kg</td>
                        <td>150 kg</td>
                        <td style="color:var(--green);font-weight:700;">100%</td>
                        <td><span class="badge-ok">Completo</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function drawDonut(canvasId, percent, percentElId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const size = canvas.width;
        const center = size / 2;
        const radius = 70;
        const lineWidth = 16;
        const startAngle = -Math.PI / 2;
        const endAngle = startAngle + (2 * Math.PI * percent / 100);

        const gapColor = percent > 95 ? '#ff9500' : '#ff3b30';
        const mainColor = '#34c759';

        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#e8e8ed';
        ctx.lineWidth = lineWidth;
        ctx.stroke();

        if (percent < 100) {
            ctx.beginPath();
            ctx.arc(center, center, radius, endAngle, startAngle + 2 * Math.PI);
            ctx.strokeStyle = gapColor;
            ctx.lineWidth = lineWidth;
            ctx.lineCap = 'round';
            ctx.stroke();
        }

        ctx.beginPath();
        ctx.arc(center, center, radius, startAngle, endAngle);
        ctx.strokeStyle = mainColor;
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'round';
        ctx.stroke();

        const el = document.getElementById(percentElId);
        if (el) {
            el.style.color = percent <= 95 ? '#ff3b30' : '#34c759';
        }
    }

    drawDonut('clienteOtifDonutOT', 98.5, 'clienteOtPercent');
    drawDonut('clienteOtifDonutIF', 95, 'clienteIfPercent');
});

function exportTableClienteOtif(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];
        cols.forEach(col => {
            let text = col.innerText.replace(/"/g, '""').trim();
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });

    const csvContent = '\uFEFF' + csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}
</script>
@endpush
