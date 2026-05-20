@extends('layouts.admin')
@section('title', 'Facturas — ' . ($proveedor->nombre ?? $codigo))
@section('hero')
<div class="hero-band">
    <h1>Facturas de {{ $proveedor->nombre ?? $codigo }}</h1>
    <p>Código: {{ $codigo }} · Detalle de adeudos y pagos</p>
</div>
@endsection
@push('styles')
<style>
    .summary{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .sum-card{background:var(--white);border:1px solid var(--border-light);border-radius:12px;padding:18px;text-align:center}
    .sum-val{font-size:24px;font-weight:800;line-height:1;margin-bottom:4px}
    .sum-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase}
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
    .badge-vencida{background:#fef2f2;color:#dc2626;margin-left:4px}
    .empty{text-align:center;padding:32px;color:var(--gray-muted);font-size:13px}
    @media(max-width:768px){.summary{grid-template-columns:1fr 1fr}.table-card{overflow-x:auto}}
</style>
@endpush
@section('content')

@php
    $totalDeuda = $facturas->where('estatus', 'pendiente')->sum('total');
    $totalPagado = $facturas->where('estatus', 'pagada')->sum('total');
    $vencidas = $facturas->where('estatus', 'pendiente')->filter(fn($f) => $f->fecha_vencimiento && $f->fecha_vencimiento->isPast())->count();
    $pendientes = $facturas->where('estatus', 'pendiente')->count();
@endphp

<div class="summary">
    <div class="sum-card"><div class="sum-val" style="color:#dc2626">${{ number_format($totalDeuda, 0) }}</div><div class="sum-label">Deuda total</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#059669">${{ number_format($totalPagado, 0) }}</div><div class="sum-label">Pagado</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#d97706">{{ $pendientes }}</div><div class="sum-label">Pendientes</div></div>
    <div class="sum-card"><div class="sum-val" style="color:#dc2626">{{ $vencidas }}</div><div class="sum-label">Vencidas</div></div>
</div>

<div class="table-card">
    <div class="table-head">Detalle de facturas</div>
    @if($facturas->count())
    <table class="tbl">
        <thead><tr><th>Folio CFDI</th><th>Monto</th><th>IVA</th><th>Total</th><th>Estatus</th><th>Vencimiento</th></tr></thead>
        <tbody>
        @foreach($facturas as $f)
            @php $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast(); @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $f->folio_cfdi }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto, 2) }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto_iva, 2) }}</td>
                <td style="font-weight:700;font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                <td>
                    <span class="badge badge-{{ $f->estatus }}">{{ ucfirst($f->estatus) }}</span>
                    @if($vencida)<span class="badge badge-vencida">VENCIDA</span>@endif
                </td>
                <td style="color:{{ $vencida ? '#dc2626' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">
                    {{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
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
