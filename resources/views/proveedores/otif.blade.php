@extends('layouts.proveedor')

@section('title', 'OTIF — Cumplimiento de entregas a Salcom')

@section('hero')
<div class="hero-band">
    <h1>OTIF proveedor</h1>
    <p>On Time In Full — Compromisos de tus órdenes de compra frente a la fecha y cantidad recibida en planta Salcom (no es el mismo criterio que el OTIF del portal de clientes).</p>
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

    {{-- ═══ Mi Score ═══ --}}
    @php
        $miProv = \App\Models\ProveedorUser::find(session('proveedor_id'));
        $miScore = $miProv ? (float)$miProv->score_total : 0;
        $miEntrega = $miProv ? (float)$miProv->score_entrega : 0;
        $miPuntualidad = $miProv ? (float)$miProv->score_puntualidad : 0;
        $scoreColor = $miScore >= 80 ? 'var(--green)' : ($miScore >= 60 ? 'var(--amber)' : 'var(--red)');
    @endphp
    <div style="background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;display:flex;align-items:center;gap:32px;flex-wrap:wrap;">
        <div style="text-align:center;min-width:120px;">
            <div style="font-size:48px;font-weight:800;color:{{ $scoreColor }};line-height:1;">{{ $miScore }}%</div>
            <div style="font-size:12px;color:var(--gray-muted);margin-top:6px;">Score General</div>
        </div>
        <div style="flex:1;display:flex;gap:24px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;color:var(--gray-muted);margin-bottom:4px;">Entrega (In Full)</div>
                <div style="font-size:24px;font-weight:700;color:{{ $miEntrega >= 80 ? 'var(--green)' : ($miEntrega >= 60 ? 'var(--amber)' : 'var(--red)') }};">{{ $miEntrega }}%</div>
            </div>
            <div>
                <div style="font-size:12px;color:var(--gray-muted);margin-bottom:4px;">Puntualidad (On Time)</div>
                <div style="font-size:24px;font-weight:700;color:{{ $miPuntualidad >= 80 ? 'var(--green)' : ($miPuntualidad >= 60 ? 'var(--amber)' : 'var(--red)') }};">{{ $miPuntualidad }}%</div>
            </div>
        </div>
        <div style="padding:10px 16px;border-radius:10px;background:{{ $miScore >= 80 ? 'var(--green-bg)' : ($miScore >= 60 ? 'var(--amber-bg)' : 'var(--red-bg)') }};font-size:12px;font-weight:600;color:{{ $scoreColor }};">
            {{ $miScore >= 80 ? 'Excelente — Proveedor preferente' : ($miScore >= 60 ? 'Aceptable — Puede mejorar' : 'Bajo rendimiento — Requiere atención') }}
        </div>
    </div>

    {{-- ═══ Donut Charts ═══ --}}
    @php
        // Calcular OT e IF desde los datos del proveedor (misma fuente que el score)
        $otValue = $miPuntualidad; // On Time = puntualidad
        $ifValue = $miEntrega;     // In Full = entrega
    @endphp
    <div class="otif-charts">
        <div class="otif-chart-card">
            <div class="otif-canvas-wrap">
                <canvas id="otifDonutOT" width="180" height="180"></canvas>
                <div class="otif-center">
                    <div class="otif-percent" id="otPercent">{{ $otValue }}%</div>
                </div>
            </div>
            <span class="otif-chart-label">OT — recepción en planta vs. fecha promesa (OC)</span>
        </div>
        <div class="otif-chart-card">
            <div class="otif-canvas-wrap">
                <canvas id="otifDonutIF" width="180" height="180"></canvas>
                <div class="otif-center">
                    <div class="otif-percent" id="ifPercent">{{ $ifValue }}%</div>
                </div>
            </div>
            <span class="otif-chart-label">IF — cantidad recibida vs. cantidad ordenada (OC)</span>
        </div>
    </div>

    {{-- ═══ Tabla On Time ═══ --}}
    <div class="otif-table-section">
        <div class="otif-table-header">
            <h4>On Time — Llegada a planta vs. fecha comprometida en OC</h4>
            <button class="btn-export" onclick="exportTable('tableOT', 'OTIF_Proveedor_OnTime_OC')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="otif-table" id="tableOT">
                <thead>
                    <tr>
                        <th>OC Salcom</th>
                        <th>Material</th>
                        <th>Fecha promesa (OC)</th>
                        <th>Recepción en planta</th>
                        <th>Días diferencia</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1045</td>
                        <td>Resina epóxica industrial</td>
                        <td>02/05/2026</td>
                        <td>02/05/2026</td>
                        <td>@include('partials.trend-arrow', ['value' => 0])</td>
                        <td><span class="badge-ok">A tiempo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1038</td>
                        <td>Adhesivo poliuretano</td>
                        <td>29/04/2026</td>
                        <td>28/04/2026</td>
                        <td style="color:var(--green);font-weight:700;">↑ 1 día antes</td>
                        <td><span class="badge-ok">A tiempo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1021</td>
                        <td>Solvente grado técnico</td>
                        <td>24/04/2026</td>
                        <td>27/04/2026</td>
                        <td style="color:var(--red);font-weight:700;">↓ 3 días tarde</td>
                        <td><span class="badge-late">Retraso</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1012</td>
                        <td>Hardener alifático</td>
                        <td>20/04/2026</td>
                        <td>20/04/2026</td>
                        <td>@include('partials.trend-arrow', ['value' => 0])</td>
                        <td><span class="badge-ok">A tiempo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-0994</td>
                        <td>Diluyente aromático</td>
                        <td>15/04/2026</td>
                        <td>16/04/2026</td>
                        <td style="color:var(--red);font-weight:700;">↓ 1 día tarde</td>
                        <td><span class="badge-late">Retraso</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ Tabla In Full ═══ --}}
    <div class="otif-table-section">
        <div class="otif-table-header">
            <h4>In Full — Recibido en almacén vs. cantidad en OC</h4>
            <button class="btn-export" onclick="exportTable('tableIF', 'OTIF_Proveedor_InFull_OC')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="otif-table" id="tableIF">
                <thead>
                    <tr>
                        <th>OC Salcom</th>
                        <th>Material / insumo</th>
                        <th>Cantidad en OC</th>
                        <th>Cantidad recibida</th>
                        <th>% Cumplimiento</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1042</td>
                        <td>Ácido clorhídrico 37%</td>
                        <td>12,000 lt</td>
                        <td>12,000 lt</td>
                        <td style="color:var(--green);font-weight:700;">100%</td>
                        <td><span class="badge-ok">Completo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1038</td>
                        <td>Resina poliéster insaturada</td>
                        <td>8 t</td>
                        <td>8 t</td>
                        <td style="color:var(--green);font-weight:700;">100%</td>
                        <td><span class="badge-ok">Completo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1025</td>
                        <td>Solvente aromático grado técnico</td>
                        <td>20,000 lt</td>
                        <td>20,000 lt</td>
                        <td style="color:var(--green);font-weight:700;">100%</td>
                        <td><span class="badge-ok">Completo</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1019</td>
                        <td>Cloro industrial</td>
                        <td>10 t</td>
                        <td>6 t</td>
                        <td style="color:var(--red);font-weight:700;">60%</td>
                        <td><span class="badge-partial">Parcial</span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:600;color:var(--purple)">OC-2026-1004</td>
                        <td>Catalizador organometálico</td>
                        <td>2,400 kg</td>
                        <td>2,400 kg</td>
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
    /**
     * Dona: gris → faltante (rojo ≤95%, naranja >95%) → cumplido (verde).
     * Mismo grosor y lineCap butt para alinear segmentos sin artefactos.
     */
    function drawDonut(canvasId, percent, percentElId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const css = 180;
        const dpr = window.devicePixelRatio || 1;
        canvas.width = css * dpr;
        canvas.height = css * dpr;
        canvas.style.width = css + 'px';
        canvas.style.height = css + 'px';
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const center = css / 2;
        const radius = 70;
        const lineWidth = 16;
        const startAngle = -Math.PI / 2;
        const p = Math.min(100, Math.max(0, percent));
        const sweep = (2 * Math.PI * p) / 100;
        const endAngle = startAngle + sweep;
        const fullEnd = startAngle + 2 * Math.PI;
        const gapColor = p > 95 ? '#ff9500' : '#ff3b30';

        ctx.clearRect(0, 0, css, css);
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'butt';

        ctx.beginPath();
        ctx.arc(center, center, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#e8e8ed';
        ctx.stroke();

        if (p < 100) {
            ctx.beginPath();
            ctx.arc(center, center, radius, endAngle, fullEnd);
            ctx.strokeStyle = gapColor;
            ctx.stroke();
        }

        if (p > 0) {
            ctx.beginPath();
            ctx.arc(center, center, radius, startAngle, endAngle);
            ctx.strokeStyle = '#34c759';
            ctx.stroke();
        }

        const el = document.getElementById(percentElId);
        if (el) {
            el.style.color = p <= 95 ? '#ff3b30' : (p < 100 ? '#34c759' : '#34c759');
        }
    }

    drawDonut('otifDonutOT', {{ $otValue }}, 'otPercent');
    drawDonut('otifDonutIF', {{ $ifValue }}, 'ifPercent');
});

// ── Exportar tabla a Excel (CSV) ──
function exportTable(tableId, filename) {
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

    const csvContent = '\uFEFF' + csv.join('\n'); // BOM para Excel
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}
</script>
@endpush
