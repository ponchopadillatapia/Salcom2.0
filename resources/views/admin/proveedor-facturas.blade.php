@extends('layouts.admin')
@section('title', 'Facturas — ' . ($proveedor->nombre ?? $codigo))
@section('hero')
<div class="hero-band">
    <h1>{{ $proveedor->nombre ?? $codigo }}</h1>
    <p>Código: {{ $codigo }} · Detalle de adeudos pendientes</p>
</div>
@endsection
@push('styles')
<style>
    .summary{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .sum-card{background:var(--white);border:1px solid var(--border-light);border-radius:12px;padding:18px;text-align:center}
    .sum-val{font-size:24px;font-weight:800;line-height:1;margin-bottom:4px}
    .sum-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase}
    .toolbar-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:12px;font-weight:600;color:#fff;background:#059669;border-radius:8px;text-decoration:none;transition:all .15s}
    .btn-export:hover{background:#047857}
    .table-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden}
    .table-head{padding:14px 20px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:10px 14px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:10px 14px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-pagada{background:#ecfdf5;color:#059669}
    .badge-pendiente{background:#fefce8;color:#d97706}
    .badge-vencida{background:#fef2f2;color:#dc2626}
    .dias-vencido{font-size:12px;font-weight:700;color:#dc2626}
    .empty{text-align:center;padding:32px;color:var(--gray-muted);font-size:13px}
    @media(max-width:768px){.summary{grid-template-columns:1fr 1fr}.table-card{overflow-x:auto}}
</style>
@endpush
@section('content')

@php
    $pendientes = $facturas->where('estatus', 'pendiente');
    $totalDeuda = $pendientes->sum('total');
    $totalPagado = $facturas->where('estatus', 'pagada')->sum('total');
    $vencidas = $pendientes->filter(fn($f) => $f->fecha_vencimiento && $f->fecha_vencimiento->isPast());
    $diasMaxVencido = $vencidas->count() > 0 ? $vencidas->max(fn($f) => $f->fecha_vencimiento->diffInDays(now())) : 0;
@endphp

<div class="summary">
    <div class="sum-card"><div class="sum-val" style="color:#dc2626">${{ number_format($totalDeuda, 0) }}</div><div class="sum-label">Deuda total</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#059669">${{ number_format($totalPagado, 0) }}</div><div class="sum-label">Pagado</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#dc2626">{{ $vencidas->count() }}</div><div class="sum-label">Facturas vencidas</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#d97706">{{ $diasMaxVencido }}</div><div class="sum-label">Máx. días vencido</div></div>
</div>

<div class="toolbar-top">
    <span style="font-size:13px;color:var(--gray-muted)">{{ $facturas->count() }} facturas totales</span>
    <a href="{{ route('admin.proveedor-facturas', $codigo) }}?export=excel" class="btn-export">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exportar Excel
    </a>
</div>

<div class="table-card">
    <div class="table-head">Detalle de adeudos — {{ $proveedor->nombre ?? $codigo }}</div>
    @if($facturas->count())
    <table class="tbl">
        <thead>
            <tr>
                <th>Folio CFDI</th>
                <th>Producto</th>
                <th>Código producto</th>
                <th>Total</th>
                <th>Vencimiento</th>
                <th>Días vencido</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
        @foreach($facturas as $f)
            @php
                $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
                $diasV = $vencida ? $f->fecha_vencimiento->diffInDays(now()) : 0;
                // Buscar producto asociado (si hay pedido_id)
                $producto = null;
                if ($f->pedido_id) {
                    $pedido = \App\Models\Pedido::find($f->pedido_id);
                    if ($pedido && is_array($pedido->productos) && count($pedido->productos) > 0) {
                        $producto = $pedido->productos[0];
                    }
                }
            @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $f->folio_cfdi }}</td>
                <td>{{ $producto['nombre'] ?? 'Compra general' }}</td>
                <td style="color:var(--gray-muted)">{{ $producto['sku'] ?? $producto['codigo'] ?? '—' }}</td>
                <td style="font-weight:700;font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                <td style="color:{{ $vencida ? '#dc2626' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">
                    {{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
                </td>
                <td>
                    @if($vencida)
                        <span class="dias-vencido">{{ $diasV }} días</span>
                    @elseif($f->estatus === 'pendiente')
                        <span style="color:var(--gray-muted)">Vigente</span>
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if($f->estatus === 'pagada')
                        <span class="badge badge-pagada">Pagada</span>
                    @elseif($vencida)
                        <span class="badge badge-vencida">Vencida</span>
                    @else
                        <span class="badge badge-pendiente">Pendiente</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <div class="empty">No hay facturas para este proveedor</div>
    @endif
</div>

@endsection
