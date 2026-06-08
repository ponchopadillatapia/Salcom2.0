@extends('layouts.admin')
@section('title', 'Reporte de Proveedores')
@section('hero')
<div class="hero-band">
    <h1>Reporte de Compras por Proveedor</h1>
    <p>Comparativo anual {{ $anioAnterior }} vs {{ $anioActual }}</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}
    .adm-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:22px 26px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .adm-summary-main{text-align:center;min-width:100px}
    .adm-summary-pct{font-size:42px;font-weight:800;line-height:1;color:var(--purple)}
    .adm-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .adm-summary-metrics{flex:1;display:flex;gap:24px;flex-wrap:wrap}
    .adm-metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .adm-metric-val{font-size:22px;font-weight:700;display:flex;align-items:center;gap:8px}
    .adm-summary-badge{padding:10px 16px;border-radius:10px;font-size:12px;font-weight:600;line-height:1.4;text-decoration:none;transition:var(--transition)}
    .adm-summary-badge:hover{opacity:.85}
    .toolbar{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
    .toolbar-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .filter-group{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
    .filter-btn{padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);cursor:pointer;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .filter-btn:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}
    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}
    .adm-section-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;text-decoration:none;font-family:inherit;transition:var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:right;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table th:first-child,.admin-table th:nth-child(2){text-align:left}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border);text-align:right;font-variant-numeric:tabular-nums}
    .admin-table td:first-child,.admin-table td:nth-child(2){text-align:left}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .admin-table tfoot td{font-weight:700;background:var(--gray-soft);border-top:2px solid var(--border);font-size:13px}
    .admin-table tfoot tr:last-child td{border-bottom:none}
    .tbl-wrap{overflow-x:auto}
    .code-col{font-weight:700;color:var(--purple)}
    .name-col{font-weight:600}
    .score-ok{color:var(--green);font-weight:700}
    .score-mid{color:var(--amber);font-weight:700}
    .score-low{color:var(--red);font-weight:700}
    .monto-up{color:var(--green);font-weight:600}
    @media(max-width:768px){.adm-summary{flex-direction:column;align-items:flex-start}}
</style>
@endpush
@section('content')

@php
    $varMonto = $totales['variacion_monto'];
    $varColor = $varMonto > 0 ? 'var(--green)' : ($varMonto < 0 ? 'var(--red)' : 'var(--gray-muted)');
    $varBg = $varMonto > 0 ? 'var(--green-bg)' : ($varMonto < 0 ? 'var(--red-bg)' : 'var(--purple-subtle)');
@endphp

<div class="adm-summary anim">
    <div class="adm-summary-main">
        <div class="adm-summary-pct">{{ count($reporte) }}</div>
        <div class="adm-summary-label">Proveedores activos</div>
    </div>
    <div class="adm-summary-metrics">
        <div>
            <div class="adm-metric-label">Compras {{ $anioActual }}</div>
            <div class="adm-metric-val" style="color:var(--green)">${{ number_format($totales['compras_actual'], 0) }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Compras {{ $anioAnterior }}</div>
            <div class="adm-metric-val" style="color:var(--gray-text)">${{ number_format($totales['compras_anterior'], 0) }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Facturas {{ $anioActual }}</div>
            <div class="adm-metric-val">{{ $totales['facturas_actual'] }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Variación compras</div>
            <div class="adm-metric-val" style="color:{{ $varColor }}">@include('partials.trend-arrow', ['value' => (int) round($varMonto), 'size' => '14'])</div>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
        <div class="adm-summary-badge" style="background:{{ $varBg }};color:{{ $varColor }}">
            Comparativo {{ $anioAnterior }} vs {{ $anioActual }} · Generado {{ now()->format('d/m/Y H:i') }}
        </div>
        <a href="{{ route('admin.negocio') }}" class="adm-summary-badge" style="background:var(--purple-subtle);color:var(--purple)">Ver detalle de negocio →</a>
    </div>
</div>

<div class="toolbar anim" style="animation-delay:.04s">
    <div class="toolbar-top">
        <div class="filter-group">
            <a href="{{ route('admin.reporte-proveedores') }}" class="filter-btn active">Comparativo anual</a>
            <a href="{{ route('admin.reporte-corte') }}" class="filter-btn">Corte mensual</a>
        </div>
        <span class="badge-count">{{ count($reporte) }} proveedor{{ count($reporte) !== 1 ? 'es' : '' }}</span>
    </div>
</div>

<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Comparativo anual por proveedor</h4>
            <div class="adm-section-meta">INDUSTRIAS SALCOM S.A. DE C.V. · {{ $anioAnterior }} vs {{ $anioActual }}</div>
        </div>
        <div class="adm-section-toolbar">
            <a href="{{ route('admin.reporte-proveedores.excel') }}" class="btn-export">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
        </div>
    </div>
    <div class="tbl-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proveedor</th>
                    <th>Score</th>
                    <th>Facturas {{ $anioAnterior }}</th>
                    <th>Facturas {{ $anioActual }}</th>
                    <th>Var. %</th>
                    <th>Compras {{ $anioAnterior }}</th>
                    <th>Compras {{ $anioActual }}</th>
                    <th>Var. %</th>
                </tr>
            </thead>
            <tbody>
            @foreach($reporte as $r)
                <tr>
                    <td class="code-col">{{ $r['codigo'] ?? '—' }}</td>
                    <td class="name-col">{{ $r['nombre'] }}</td>
                    <td class="{{ $r['score'] >= 80 ? 'score-ok' : ($r['score'] >= 50 ? 'score-mid' : 'score-low') }}">{{ number_format($r['score'], 0) }}%</td>
                    <td>{{ $r['facturas_anterior'] }}</td>
                    <td>{{ $r['facturas_actual'] }}</td>
                    <td>@include('partials.trend-arrow', ['value' => $r['variacion_cant']])</td>
                    <td>${{ number_format($r['compras_anterior'], 2) }}</td>
                    <td class="{{ $r['variacion_monto'] > 0 ? 'monto-up' : '' }}">${{ number_format($r['compras_actual'], 2) }}</td>
                    <td>@include('partials.trend-arrow', ['value' => $r['variacion_monto']])</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td>Gran total</td>
                    <td></td>
                    <td>{{ $totales['facturas_anterior'] }}</td>
                    <td>{{ $totales['facturas_actual'] }}</td>
                    <td>@include('partials.trend-arrow', ['value' => $totales['variacion_cant']])</td>
                    <td>${{ number_format($totales['compras_anterior'], 2) }}</td>
                    <td class="{{ $totales['variacion_monto'] > 0 ? 'monto-up' : '' }}">${{ number_format($totales['compras_actual'], 2) }}</td>
                    <td>@include('partials.trend-arrow', ['value' => $totales['variacion_monto']])</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection
