@extends('layouts.cliente')
@section('title', 'Forecast')
@section('hero')
<div class="hero-band">
    <h1>Forecast — Tendencias de tus compras</h1>
    <p>Análisis de volumen, tendencias y distribución de tus compras con Salcom</p>
</div>
@endsection
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
    .fc-name{flex:1;font-weight:600;color:var(--gray-text)}
    .fc-code{font-size:11px;color:var(--gray-muted);margin-top:1px}
    .fc-bar{width:80px;height:8px;background:var(--border-light);border-radius:4px;overflow:hidden}
    .fc-fill{height:100%;border-radius:4px}
    .fc-score{font-size:12px;font-weight:700;width:50px;text-align:center}
    .fc-trend{font-size:13px;font-weight:700;width:60px;text-align:right}
    .fc-trend.up{color:var(--green)}.fc-trend.down{color:var(--red)}.fc-trend.flat{color:var(--gray-muted)}
    .fc-note{font-size:11px;color:var(--gray-muted);text-align:center;margin-top:8px}

    .fc-full{grid-column:1/-1}
    .chart-wrap{background:var(--white);border-radius:var(--radius-lg);padding:24px;box-shadow:var(--shadow-sm);transition:var(--transition);margin-bottom:20px}
    .chart-wrap:hover{box-shadow:var(--shadow-md)}
    .chart-wrap h3{font-size:16px;font-weight:700;color:var(--gray-text);margin-bottom:4px;letter-spacing:-0.3px}
    .chart-wrap p{font-size:13px;color:var(--gray-muted);margin-bottom:20px}
    .chart-container{position:relative;width:100%;height:280px}
    .chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}

    @media(max-width:768px){.fc-grid,.chart-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

{{-- TOP 5 AL ALZA + TOP 5 A LA BAJA (según tu historial de compras) --}}
<div class="fc-grid">
    <div class="fc-card">
        <h3 class="up">Top 5 — Productos al alza</h3>
        @php
        $alza = [
            ['Resina epóxica industrial', 'SAL-001', 92, '+12%'],
            ['Solvente grado técnico', 'SAL-003', 88, '+8%'],
            ['Pigmento base agua', 'SAL-005', 81, '+5%'],
            ['Fibra de refuerzo', 'SAL-011', 79, '+3%'],
            ['Adhesivo estructural', 'SAL-015', 76, '+2%'],
        ];
        @endphp
        @foreach($alza as $i => [$nombre, $codigo, $score, $trend])
        <div class="fc-row">
            <div class="fc-rank up">{{ $i + 1 }}</div>
            <div style="flex:1"><div class="fc-name">{{ $nombre }}</div><div class="fc-code">{{ $codigo }}</div></div>
            <div class="fc-bar"><div class="fc-fill" style="width:{{ $score }}%;background:var(--green)"></div></div>
            <div class="fc-score" style="color:var(--green)">{{ $score }}</div>
            <div class="fc-trend up">↑ {{ $trend }}</div>
        </div>
        @endforeach
    </div>

    <div class="fc-card">
        <h3 class="down">Top 5 — Productos a la baja</h3>
        @php
        $baja = [
            ['Aditivo antioxidante', 'SAL-009', 58, '-15%'],
            ['Catalizador rápido', 'SAL-007', 62, '-5%'],
            ['Sellador industrial', 'SAL-020', 65, '-3%'],
            ['Disolvente especial', 'SAL-018', 68, '-2%'],
            ['Recubrimiento base', 'SAL-022', 70, '-1%'],
        ];
        @endphp
        @foreach($baja as $i => [$nombre, $codigo, $score, $trend])
        <div class="fc-row">
            <div class="fc-rank down">{{ $i + 1 }}</div>
            <div style="flex:1"><div class="fc-name">{{ $nombre }}</div><div class="fc-code">{{ $codigo }}</div></div>
            <div class="fc-bar"><div class="fc-fill" style="width:{{ $score }}%;background:{{ $score < 60 ? 'var(--red)' : 'var(--amber)' }}"></div></div>
            <div class="fc-score" style="color:{{ $score < 60 ? 'var(--red)' : 'var(--amber)' }}">{{ $score }}</div>
            <div class="fc-trend down">↓ {{ $trend }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- GRÁFICAS: Distribución + Tendencia --}}
<div class="chart-grid">
    <div class="chart-wrap">
        <h3>Distribución de tus compras</h3>
        <p>Participación de cada producto en el monto total que compraste con Salcom</p>
        <div class="chart-container" style="height:260px;">
            <canvas id="chartDistribucionCliente"></canvas>
        </div>
    </div>

    <div class="chart-wrap">
        <h3>Tendencia de tu volumen (6 meses)</h3>
        <p>Evolución de las unidades que pediste mes a mes</p>
        <div class="chart-container" style="height:260px;">
            <canvas id="chartTendenciaCliente"></canvas>
        </div>
    </div>
</div>

<div class="fc-note">⚠ Datos de prueba — se reemplazarán con datos reales de tu historial cuando se conecte la API</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const mesesCliente = ['Nov', 'Dic', 'Ene', 'Feb', 'Mar', 'Abr'];
const purple = '#6B3FA0';
const green = '#34c759';
const blue = '#007aff';
const amber = '#ff9f0a';
const red = '#ff3b30';

new Chart(document.getElementById('chartDistribucionCliente'), {
    type: 'doughnut',
    data: {
        labels: ['Resina epóxica', 'Solvente técnico', 'Pigmento base agua', 'Catalizador rápido', 'Otros'],
        datasets: [{
            data: [38, 22, 16, 12, 12],
            backgroundColor: [purple, blue, green, amber, '#d2d2d7'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12, family: 'Inter' } } }
        }
    }
});

new Chart(document.getElementById('chartTendenciaCliente'), {
    type: 'line',
    data: {
        labels: mesesCliente,
        datasets: [{
            label: 'Unidades pedidas',
            data: [11800, 12100, 13200, 12800, 15100, 14800],
            borderColor: purple,
            backgroundColor: purple + '15',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: purple,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Unidades', font: { size: 12, family: 'Inter' } }, grid: { color: '#f0f0f0' } },
            x: { grid: { display: false } }
        }
    }
});
</script>
@endpush
