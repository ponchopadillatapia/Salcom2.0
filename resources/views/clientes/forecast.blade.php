@extends('layouts.cliente')
@section('title', 'Forecast')
@section('hero')
<div class="hero-band">
    <h1>Forecast — Tendencias de tus compras</h1>
    <p>Productos que más compras y sus tendencias de precio y disponibilidad</p>
</div>
@endsection
@push('styles')
<style>
    .forecast-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .fc-card{background:var(--white);border:1px solid var(--border);border-radius:14px;padding:24px}
    .fc-card h3{font-size:16px;font-weight:700;margin-bottom:16px;display:flex;align-items:center;gap:8px}
    .fc-card h3.up{color:var(--green)}.fc-card h3.down{color:var(--red)}.fc-card h3.info{color:var(--purple)}
    .fc-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);font-size:13px}
    .fc-row:last-child{border-bottom:none}
    .fc-rank{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0}
    .fc-rank.up{background:var(--green-bg);color:var(--green)}.fc-rank.down{background:var(--red-bg);color:var(--red)}.fc-rank.info{background:var(--purple-light);color:var(--purple)}
    .fc-name{flex:1;font-weight:600;color:var(--gray-text)}
    .fc-code{font-size:11px;color:var(--gray-muted);margin-top:1px}
    .fc-bar{width:80px;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden}
    .fc-fill{height:100%;border-radius:4px}
    .fc-trend{font-size:13px;font-weight:700;width:60px;text-align:right}
    .fc-trend.up{color:var(--green)}.fc-trend.down{color:var(--red)}.fc-trend.flat{color:var(--gray-muted)}
    .fc-note{font-size:11px;color:var(--gray-muted);text-align:center;margin-top:16px}
    @media(max-width:768px){.forecast-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

<div class="forecast-grid">
    <div class="fc-card">
        <h3 class="up">Productos que más compras — Al alza</h3>
        @php
        $alza = [
            ['Resina epóxica industrial', 'SAL-001', 'Demanda creciente', '+12%'],
            ['Solvente grado técnico', 'SAL-003', 'Compra recurrente', '+8%'],
            ['Pigmento base agua', 'SAL-005', 'Nuevo en tu catálogo', '+5%'],
        ];
        @endphp
        @foreach($alza as $i => [$nombre, $codigo, $nota, $trend])
        <div class="fc-row">
            <div class="fc-rank up">{{ $i + 1 }}</div>
            <div style="flex:1"><div class="fc-name">{{ $nombre }}</div><div class="fc-code">{{ $codigo }} · {{ $nota }}</div></div>
            <div class="fc-trend up">↑ {{ $trend }}</div>
        </div>
        @endforeach
    </div>

    <div class="fc-card">
        <h3 class="down">Productos a la baja</h3>
        @php
        $baja = [
            ['Catalizador rápido', 'SAL-007', 'Menos pedidos este trimestre', '-5%'],
            ['Aditivo antioxidante', 'SAL-009', 'Stock bajo en almacén', '-15%'],
        ];
        @endphp
        @foreach($baja as $i => [$nombre, $codigo, $nota, $trend])
        <div class="fc-row">
            <div class="fc-rank down">{{ $i + 1 }}</div>
            <div style="flex:1"><div class="fc-name">{{ $nombre }}</div><div class="fc-code">{{ $codigo }} · {{ $nota }}</div></div>
            <div class="fc-trend down">↓ {{ $trend }}</div>
        </div>
        @endforeach
    </div>
</div>

<div class="fc-card" style="margin-bottom:24px">
    <h3 class="info">💡 Recomendaciones de compra</h3>
    <div class="fc-row">
        <div class="fc-rank info">1</div>
        <div style="flex:1"><div class="fc-name">Resina epóxica industrial</div><div class="fc-code">Tu demanda ha crecido 12% — considera aumentar tu pedido mensual</div></div>
    </div>
    <div class="fc-row">
        <div class="fc-rank info">2</div>
        <div style="flex:1"><div class="fc-name">Pigmento base agua</div><div class="fc-code">Producto nuevo con buena tendencia — disponible en catálogo</div></div>
    </div>
    <div class="fc-row">
        <div class="fc-rank info">3</div>
        <div style="flex:1"><div class="fc-name">Catalizador rápido</div><div class="fc-code">Stock bajo en almacén — te recomendamos hacer pedido pronto</div></div>
    </div>
</div>

<div class="fc-note">⚠ Datos de prueba — se reemplazarán con datos reales de tu historial de pedidos</div>

{{-- Tabla Mínimos y máximos --}}
<div class="fc-card" style="margin-top:24px;">
    <h3>Mínimos y máximos</h3>
    <p style="font-size:13px;color:var(--gray-muted);margin-bottom:16px;">Comparativo de tus compras — análisis de tendencia</p>
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="border-bottom:2px solid var(--border);text-align:left;">
                    <th style="padding:10px 8px;color:var(--gray-muted);font-weight:600;font-size:11px;text-transform:uppercase;">Código</th>
                    <th style="padding:10px 8px;color:var(--gray-muted);font-weight:600;font-size:11px;text-transform:uppercase;">Producto</th>
                    <th style="padding:10px 8px;color:var(--gray-muted);font-weight:600;font-size:11px;text-transform:uppercase;text-align:right;">UDS 2025</th>
                    <th style="padding:10px 8px;color:var(--gray-muted);font-weight:600;font-size:11px;text-transform:uppercase;text-align:right;">UDS 2026</th>
                    <th style="padding:10px 8px;color:var(--gray-muted);font-weight:600;font-size:11px;text-transform:uppercase;text-align:center;">Variación</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:12px 8px;color:var(--gray-muted);">SAL-001</td>
                    <td style="padding:12px 8px;font-weight:600;">Resina epóxica industrial</td>
                    <td style="padding:12px 8px;text-align:right;">1,200</td>
                    <td style="padding:12px 8px;text-align:right;">1,344</td>
                    <td style="padding:12px 8px;text-align:center;">@include('partials.trend-arrow', ['value' => 12])</td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:12px 8px;color:var(--gray-muted);">SAL-003</td>
                    <td style="padding:12px 8px;font-weight:600;">Solvente grado técnico</td>
                    <td style="padding:12px 8px;text-align:right;">800</td>
                    <td style="padding:12px 8px;text-align:right;">864</td>
                    <td style="padding:12px 8px;text-align:center;">@include('partials.trend-arrow', ['value' => 8])</td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:12px 8px;color:var(--gray-muted);">SAL-007</td>
                    <td style="padding:12px 8px;font-weight:600;">Catalizador rápido</td>
                    <td style="padding:12px 8px;text-align:right;">500</td>
                    <td style="padding:12px 8px;text-align:right;">475</td>
                    <td style="padding:12px 8px;text-align:center;">@include('partials.trend-arrow', ['value' => -5])</td>
                </tr>
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:12px 8px;color:var(--gray-muted);">SAL-009</td>
                    <td style="padding:12px 8px;font-weight:600;">Aditivo antioxidante</td>
                    <td style="padding:12px 8px;text-align:right;">300</td>
                    <td style="padding:12px 8px;text-align:right;">255</td>
                    <td style="padding:12px 8px;text-align:center;">@include('partials.trend-arrow', ['value' => -15])</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

@endsection
