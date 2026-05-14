@extends('layouts.cliente')
@section('title', 'Forecast')
@section('hero')
<div class="hero-band">
    <h1>Forecast — Tendencias de tus compras</h1>
    <p>Análisis por <strong>categorías del catálogo</strong> (mismas secciones que el filtro de productos), volumen y participación demo hasta conectar la API.</p>
</div>
@endsection

@php
    $ap = config('cliente_portal.analitica_portal');
    $fc = $ap['forecast'] ?? [];
    $alza = $fc['alza'] ?? [];
    $baja = $fc['baja'] ?? [];
    $distrib = $fc['distribucion_secciones'] ?? [];
    $tendencia = $fc['tendencia_mensual'] ?? ['meses' => [], 'series' => []];
@endphp

@push('styles')
<style>
    .fc-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .fc-card{background:var(--white);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);transition:var(--transition)}
    .fc-card:hover{box-shadow:var(--shadow-md)}
    .fc-card h3{font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px;letter-spacing:-0.3px}
    .fc-card h3.up{color:var(--green)}.fc-card h3.down{color:var(--red)}
    .fc-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border-light);font-size:13px}
    .fc-row:last-child{border-bottom:none}
    .fc-rank{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
    .fc-rank.up{background:var(--green-bg);color:var(--green)}.fc-rank.down{background:var(--red-bg);color:var(--red)}
    .fc-name{flex:1;font-weight:600;color:var(--gray-text);line-height:1.35}
    .fc-code{font-size:11px;color:var(--gray-muted);margin-top:3px;font-family:ui-monospace,Menlo,monospace}
    .fc-bar{width:80px;height:8px;background:var(--border-light);border-radius:4px;overflow:hidden;flex-shrink:0}
    .fc-fill{height:100%;border-radius:4px}
    .fc-score{font-size:12px;font-weight:700;width:50px;text-align:center;flex-shrink:0}
    .fc-trend{font-size:13px;font-weight:700;width:60px;text-align:right;flex-shrink:0}
    .fc-trend.up{color:var(--green)}.fc-trend.down{color:var(--red)}.fc-trend.flat{color:var(--gray-muted)}
    .fc-note{font-size:11px;color:var(--gray-muted);text-align:center;margin-top:8px}

    .chart-wrap{background:var(--white);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);transition:var(--transition);margin-bottom:20px}
    .chart-wrap:hover{box-shadow:var(--shadow-md)}
    .chart-wrap h3{font-size:16px;font-weight:700;color:var(--gray-text);margin-bottom:4px;letter-spacing:-0.3px}
    .chart-wrap p{font-size:13px;color:var(--gray-muted);margin-bottom:20px}
    .chart-container{position:relative;width:100%;height:300px}
    .chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}

    @media(max-width:768px){.fc-grid,.chart-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

<div class="fc-grid">
    <div class="fc-card">
        <h3 class="up">Top 5 — Categorías al alza</h3>
        @forelse($alza as $i => $row)
        <div class="fc-row" title="{{ $row['seccion'] ?? '' }}">
            <div class="fc-rank up">{{ $i + 1 }}</div>
            <div style="flex:1;min-width:0">
                <div class="fc-name">{{ $row['seccion'] ?? '—' }}</div>
                <div class="fc-code">{{ $row['slug'] ?? '' }}</div>
            </div>
            <div class="fc-bar"><div class="fc-fill" style="width:{{ (int)($row['score'] ?? 0) }}%;background:var(--green)"></div></div>
            <div class="fc-score" style="color:var(--green)">{{ (int)($row['score'] ?? 0) }}</div>
            <div class="fc-trend up">↑ {{ $row['trend'] ?? '' }}</div>
        </div>
        @empty
        <p style="color:var(--gray-muted);font-size:13px">Sin datos de demostración.</p>
        @endforelse
    </div>

    <div class="fc-card">
        <h3 class="down">Top 5 — Categorías a la baja</h3>
        @forelse($baja as $i => $row)
        @php $sc = (int)($row['score'] ?? 0); @endphp
        <div class="fc-row" title="{{ $row['seccion'] ?? '' }}">
            <div class="fc-rank down">{{ $i + 1 }}</div>
            <div style="flex:1;min-width:0">
                <div class="fc-name">{{ $row['seccion'] ?? '—' }}</div>
                <div class="fc-code">{{ $row['slug'] ?? '' }}</div>
            </div>
            <div class="fc-bar"><div class="fc-fill" style="width:{{ $sc }}%;background:{{ $sc < 60 ? 'var(--red)' : 'var(--amber)' }}"></div></div>
            <div class="fc-score" style="color:{{ $sc < 60 ? 'var(--red)' : 'var(--amber)' }}">{{ $sc }}</div>
            <div class="fc-trend down">↓ {{ $row['trend'] ?? '' }}</div>
        </div>
        @empty
        <p style="color:var(--gray-muted);font-size:13px">Sin datos de demostración.</p>
        @endforelse
    </div>
</div>

<div class="chart-grid">
    <div class="chart-wrap">
        <h3>Distribución de tus compras</h3>
        <p>Participación por <strong>categoría del catálogo</strong> en el monto total comprado (demo)</p>
        <div class="chart-container">
            <canvas id="chartDistribucionCliente"></canvas>
        </div>
    </div>

    <div class="chart-wrap">
        <h3>Tendencia de volumen por categoría (6 meses)</h3>
        <p>Unidades pedidas mes a mes, desglosadas por las principales secciones del catálogo (demo)</p>
        <div class="chart-container">
            <canvas id="chartTendenciaCliente"></canvas>
        </div>
    </div>
</div>

<div class="fc-note">Datos de prueba — se sustituirán por tu historial real al conectar la API</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const distribucionRaw = @json($distrib);
const tendenciaRaw = @json($tendencia);

const purple = '#6B3FA0';
const blue = '#007aff';
const green = '#34c759';
const amber = '#ff9f0a';
const red = '#ff3b30';
const gray = '#d2d2d7';
const palette = [purple, blue, green, amber, '#5856d6', red, '#8e8e93', gray];

const distLabels = distribucionRaw.map(r => r.seccion || '—');
const distData = distribucionRaw.map(r => Number(r.pct) || 0);
const distColors = distLabels.map((_, i) => palette[i % palette.length]);

new Chart(document.getElementById('chartDistribucionCliente'), {
    type: 'doughnut',
    data: {
        labels: distLabels,
        datasets: [{
            data: distData,
            backgroundColor: distColors,
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 8,
                    font: { size: 9, family: 'Inter' },
                    boxWidth: 8
                }
            },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        const v = ctx.raw != null ? ctx.raw : 0;
                        const full = ctx.label || '';
                        return full + ': ' + v + '%';
                    }
                }
            }
        }
    }
});

const meses = (tendenciaRaw.meses && tendenciaRaw.meses.length) ? tendenciaRaw.meses : ['Nov', 'Dic', 'Ene', 'Feb', 'Mar', 'Abr'];
const series = Array.isArray(tendenciaRaw.series) ? tendenciaRaw.series : [];

new Chart(document.getElementById('chartTendenciaCliente'), {
    type: 'line',
    data: {
        labels: meses,
        datasets: series.map(function(s, idx) {
            const col = palette[idx % palette.length];
            return {
                label: s.seccion || ('Serie ' + (idx + 1)),
                data: Array.isArray(s.unidades) ? s.unidades : [],
                borderColor: col,
                backgroundColor: col + '18',
                fill: false,
                tension: 0.35,
                pointBackgroundColor: col,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            };
        })
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { size: 9, family: 'Inter' },
                    boxWidth: 8,
                    padding: 8
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                stacked: false,
                title: { display: true, text: 'Unidades', font: { size: 12, family: 'Inter' } },
                grid: { color: '#f0f0f0' }
            },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
