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
    .toolbar{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
    .toolbar-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .filter-group{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
    .filter-btn{padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);cursor:pointer;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .filter-btn:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}
    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:16px 18px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .filters-meta{font-size:13px;color:var(--gray-muted)}
    .filters-meta strong{color:var(--gray-text);font-weight:700;display:block;font-size:14px;margin-bottom:2px}
    .btn-export{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;text-decoration:none;transition:all .15s;font-family:inherit;white-space:nowrap}
    .btn-export:hover{background:#047857}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;overflow-x:auto}
    .admin-table{width:100%;border-collapse:collapse;min-width:800px}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:right;background:var(--gray-soft);border-bottom:1px solid var(--border);white-space:nowrap}
    .admin-table th:first-child,.admin-table th:nth-child(2){text-align:left}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border);text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
    .admin-table td:first-child,.admin-table td:nth-child(2){text-align:left}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .admin-table tfoot td{font-weight:700;background:var(--gray-soft);border-top:2px solid var(--border);font-size:13px}
    .admin-table tfoot tr:last-child td{border-bottom:none}
    .code-col{font-weight:700;color:var(--purple)}
    .name-col{font-weight:600}
    .total-col{font-weight:700;color:var(--purple)}
    .zero{color:var(--gray-muted);opacity:.5}

    @media(max-width:768px){.filters-panel{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')

<div class="toolbar">
    <div class="toolbar-top">
        <div class="filter-group">
            <a href="{{ route('admin.reporte-proveedores') }}" class="filter-btn">Comparativo anual</a>
            <a href="{{ route('admin.reporte-corte') }}" class="filter-btn active">Corte mensual</a>
        </div>
        <span class="badge-count">{{ count($reporte) }} proveedor{{ count($reporte) !== 1 ? 'es' : '' }}</span>
    </div>

    <div class="filters-panel">
        <div class="filters-meta">
            <strong>INDUSTRIAS SALCOM S.A. DE C.V.</strong>
            Enero a {{ $meses[$mesActual] }} {{ $anio }} · Generado {{ now()->format('d/m/Y H:i') }}
        </div>
        <a href="{{ route('admin.reporte-corte.excel') }}" class="btn-export">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exportar a Excel
        </a>
    </div>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
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
                <td>Total por mes</td>
                @foreach($meses as $m => $nombre)
                    <td>${{ number_format($totalesMes[$m], 2) }}</td>
                @endforeach
                <td class="total-col">${{ number_format($granTotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

@endsection
