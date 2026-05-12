@extends('layouts.admin')
@section('title', 'Corte de Proveedores')
@section('hero')
<div class="hero-band">
    <h1>Corte de Compras por Proveedor</h1>
    <p>Enero a {{ $meses[$mesActual] }} {{ $anio }} — Acumulado mensual</p>
</div>
@endsection
@push('styles')
<style>
    .report-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px}
    .report-title{font-size:13px;color:var(--gray-muted);font-weight:500}
    .btn-export{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:10px;text-decoration:none;transition:all .15s}
    .btn-export:hover{background:#047857;transform:translateY(-1px);box-shadow:0 4px 12px rgba(5,150,105,.25)}

    .company-banner{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:18px 22px;margin-bottom:20px}
    .company-banner h2{font-size:16px;font-weight:800;color:var(--gray-text);margin-bottom:2px}
    .company-banner p{font-size:12px;color:var(--gray-muted)}

    .report-table-wrap{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow-x:auto}
    .report-table{width:100%;border-collapse:collapse;min-width:800px}
    .report-table th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 12px;text-align:right;background:var(--gray-soft);border-bottom:1px solid var(--border-light);white-space:nowrap}
    .report-table th:first-child,.report-table th:nth-child(2){text-align:left}
    .report-table td{padding:10px 12px;font-size:12px;color:var(--gray-text);border-bottom:1px solid var(--border-light);text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
    .report-table td:first-child,.report-table td:nth-child(2){text-align:left}
    .report-table tr:last-child td{border-bottom:none}
    .report-table tbody tr:hover td{background:var(--purple-subtle)}
    .report-table tfoot td{font-weight:700;background:var(--gray-soft);border-top:2px solid var(--border)}
    .code-col{font-weight:700;color:var(--purple)}
    .name-col{font-weight:600}
    .total-col{font-weight:700;color:var(--purple)}
    .zero{color:var(--gray-muted);opacity:.5}

    .nav-tabs{display:flex;gap:8px;margin-bottom:20px}
    .nav-tab{padding:9px 18px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);text-decoration:none;transition:all .15s}
    .nav-tab:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .nav-tab.active{background:var(--purple);color:#fff;border-color:var(--purple)}
</style>
@endpush
@section('content')

<div class="nav-tabs">
    <a href="{{ route('admin.reporte-proveedores') }}" class="nav-tab">Comparativo anual</a>
    <a href="{{ route('admin.reporte-corte') }}" class="nav-tab active">Corte mensual</a>
</div>

<div class="company-banner">
    <h2>INDUSTRIAS SALCOM S.A. DE C.V.</h2>
    <p>CORTE DE COMPRAS POR PROVEEDOR — ENERO A {{ strtoupper($meses[$mesActual]) }} {{ $anio }}</p>
</div>

<div class="report-header">
    <div class="report-title">Generado: {{ now()->format('d/m/Y H:i') }} · {{ count($reporte) }} proveedores</div>
    <a href="{{ route('admin.reporte-corte.excel') }}" class="btn-export">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exportar a Excel
    </a>
</div>

<div class="report-table-wrap">
    <table class="report-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Proveedor</th>
                @foreach($meses as $m => $nombre)
                    <th>{{ strtoupper(substr($nombre, 0, 3)) }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($reporte as $r)
            <tr>
                <td class="code-col">{{ $r['codigo'] ?? '—' }}</td>
                <td class="name-col">{{ $r['nombre'] }}</td>
                @foreach($meses as $m => $nombre)
                    <td class="{{ $r['meses'][$m] == 0 ? 'zero' : '' }}">${{ number_format($r['meses'][$m], 2) }}</td>
                @endforeach
                <td class="total-col">${{ number_format($r['total'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>TOTAL POR MES</td>
                @foreach($meses as $m => $nombre)
                    <td>${{ number_format($totalesMes[$m], 2) }}</td>
                @endforeach
                <td class="total-col">${{ number_format($granTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection
