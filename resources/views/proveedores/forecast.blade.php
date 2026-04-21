@extends('layouts.proveedor')
@section('title', 'Forecast')
@section('hero')
<div class="hero-band">
    <h1>📊 Forecast — Tendencias de productos</h1>
    <p>Productos al alza y a la baja basado en historial de pedidos</p>
</div>
@endsection
@push('styles')
<style>
    .forecast-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .fc-card{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:24px}
    .fc-card h3{font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .fc-card h3.up{color:var(--green)}.fc-card h3.down{color:var(--red)}
    .fc-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px}
    .fc-row:last-child{border-bottom:none}
    .fc-rank{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
    .fc-rank.up{background:var(--green-bg);color:var(--green)}.fc-rank.down{background:var(--red-bg);color:var(--red)}
    .fc-name{flex:1;font-weight:600;color:var(--gray-text)}
    .fc-code{font-size:11px;color:var(--gray-muted);margin-top:1px}
    .fc-bar{width:80px;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden}
    .fc-fill{height:100%;border-radius:4px}
    .fc-score{font-size:12px;font-weight:700;width:50px;text-align:center}
    .fc-trend{font-size:13px;font-weight:700;width:60px;text-align:right}
    .fc-trend.up{color:var(--green)}.fc-trend.down{color:var(--red)}.fc-trend.flat{color:var(--gray-muted)}
    .fc-note{font-size:11px;color:var(--gray-muted);text-align:center;margin-top:16px}
    @media(max-width:768px){.forecast-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

<div class="forecast-grid">
    <div class="fc-card">
        <h3 class="up">📈 Productos al alza</h3>
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
        <h3 class="down">📉 Productos a la baja</h3>
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

<div class="fc-note">⚠ Datos de prueba — se reemplazarán con datos reales de la API</div>

@endsection
