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
    .report-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
    .report-title{font-size:13px;color:var(--gray-muted);font-weight:500}
    .btn-export{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:10px;text-decoration:none;transition:all .15s;cursor:pointer;font-family:inherit}
    .btn-export:hover{background:#047857;transform:translateY(-1px);box-shadow:0 4px 12px rgba(5,150,105,.25)}
    .btn-export svg{flex-shrink:0}

    .report-table-wrap{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden}
    .report-table{width:100%;border-collapse:collapse}
    .report-table th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 14px;text-align:right;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .report-table th:first-child,.report-table th:nth-child(2){text-align:left}
    .report-table td{padding:11px 14px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light);text-align:right;font-variant-numeric:tabular-nums}
    .report-table td:first-child,.report-table td:nth-child(2){text-align:left}
    .report-table tr:last-child td{border-bottom:none}
    .report-table tbody tr:hover td{background:var(--purple-subtle)}
    .report-table tfoot td{font-weight:700;background:var(--gray-soft);border-top:2px solid var(--border);font-size:13px}

    .var-positive{color:#059669;font-weight:700}
    .var-negative{color:#dc2626;font-weight:700}
    .var-zero{color:var(--gray-muted)}
    .code-col{font-weight:700;color:var(--purple)}
    .name-col{font-weight:600}
    .score-col{font-weight:600}

    .company-banner{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:18px 22px;margin-bottom:20px}
    .company-banner h2{font-size:16px;font-weight:800;color:var(--gray-text);margin-bottom:2px}
    .company-banner p{font-size:12px;color:var(--gray-muted)}

    @media(max-width:768px){.report-table-wrap{overflow-x:auto}.report-header{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')

<div class="nav-tabs" style="display:flex;gap:8px;margin-bottom:20px">
    <a href="{{ route('admin.reporte-proveedores') }}" style="padding:9px 18px;font-size:12px;font-weight:600;border-radius:8px;background:var(--purple);color:#fff;text-decoration:none">Comparativo anual</a>
    <a href="{{ route('admin.reporte-corte') }}" style="padding:9px 18px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);text-decoration:none">Corte mensual</a>
</div>

<div class="company-banner">
    <h2>INDUSTRIAS SALCOM S.A. DE C.V.</h2>
    <p>REPORTE DE COMPRAS POR PROVEEDOR — COMPARATIVO ANUAL {{ $anioAnterior }} vs {{ $anioActual }}</p>
</div>

<div class="report-header">
    <div class="report-title">Generado: {{ now()->format('d/m/Y H:i') }} · {{ count($reporte) }} proveedores</div>
    <a href="{{ route('admin.reporte-proveedores.excel') }}" class="btn-export">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exportar a Excel
    </a>
</div>

<div class="report-table-wrap">
    <table class="report-table">
        <thead>
            <tr>
                <th style="text-align:left">Código</th>
                <th style="text-align:left">Proveedor</th>
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
                <td class="score-col">{{ number_format($r['score'], 0) }}%</td>
                <td>{{ $r['facturas_anterior'] }}</td>
                <td>{{ $r['facturas_actual'] }}</td>
                <td class="{{ $r['variacion_cant'] > 0 ? 'var-positive' : ($r['variacion_cant'] < 0 ? 'var-negative' : 'var-zero') }}">
                    {{ $r['variacion_cant'] > 0 ? '+' : '' }}{{ $r['variacion_cant'] }}%
                </td>
                <td>${{ number_format($r['compras_anterior'], 2) }}</td>
                <td>${{ number_format($r['compras_actual'], 2) }}</td>
                <td class="{{ $r['variacion_monto'] > 0 ? 'var-positive' : ($r['variacion_monto'] < 0 ? 'var-negative' : 'var-zero') }}">
                    {{ $r['variacion_monto'] > 0 ? '+' : '' }}{{ $r['variacion_monto'] }}%
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>GRAN TOTAL</td>
                <td></td>
                <td>{{ $totales['facturas_anterior'] }}</td>
                <td>{{ $totales['facturas_actual'] }}</td>
                <td></td>
                <td>${{ number_format($totales['compras_anterior'], 2) }}</td>
                <td>${{ number_format($totales['compras_actual'], 2) }}</td>
                <td class="{{ $totales['variacion_monto'] > 0 ? 'var-positive' : ($totales['variacion_monto'] < 0 ? 'var-negative' : 'var-zero') }}">
                    {{ $totales['variacion_monto'] > 0 ? '+' : '' }}{{ $totales['variacion_monto'] }}%
                </td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection
