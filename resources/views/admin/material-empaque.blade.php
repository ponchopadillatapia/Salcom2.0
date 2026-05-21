@extends('layouts.admin')
@section('title', 'Material de Empaque')
@section('hero')
<div class="hero-band">
    <h1>Material de Empaque</h1>
    <p>Encargada: Rosy · Control de materiales de empaque y producto terminado</p>
</div>
@endsection
@push('styles')
<style>
    .kpi-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}
    .kpi-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;text-align:center}
    .kpi-val{font-size:28px;font-weight:800;line-height:1;margin-bottom:4px}
    .kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px}
    .section-title{font-size:14px;font-weight:700;color:var(--gray-text);margin:0 0 12px}
    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}
    .badge-stock{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-stock.ok{background:var(--green-bg);color:var(--green)}
    .badge-stock.low{background:var(--amber-bg);color:var(--amber)}
    .badge-stock.out{background:var(--red-bg);color:var(--red)}
    .badge-cat{font-size:10px;font-weight:600;padding:3px 10px;border-radius:6px;background:var(--purple-subtle);color:var(--purple)}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted);font-size:14px;font-weight:500}
    @media(max-width:768px){.kpi-row{grid-template-columns:1fr}.admin-table-wrap{overflow-x:auto}}
</style>
@endpush
@section('content')

@php
    $total = $productos->count();
    $sinStock = $productos->where('stock', '<=', 0)->count();
    $stockBajo = $productos->where('stock', '>', 0)->where('stock', '<', 50)->count();
@endphp

<div class="kpi-row">
    <div class="kpi-card"><div class="kpi-val" style="color:var(--purple)">{{ $total }}</div><div class="kpi-label">Materiales</div></div>
    <div class="kpi-card"><div class="kpi-val" style="color:var(--amber)">{{ $stockBajo }}</div><div class="kpi-label">Stock bajo</div></div>
    <div class="kpi-card"><div class="kpi-val" style="color:var(--red)">{{ $sinStock }}</div><div class="kpi-label">Agotados</div></div>
</div>

<p class="section-title">Inventario de material de empaque</p>

<div class="admin-table-wrap">
    @if($productos->count())
    <table class="admin-table">
        <thead><tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Unidad</th><th>Estado</th></tr></thead>
        <tbody>
        @foreach($productos as $p)
            @php $cls = $p->stock <= 0 ? 'out' : ($p->stock < 50 ? 'low' : 'ok'); $lbl = $p->stock <= 0 ? 'Agotado' : ($p->stock < 50 ? 'Bajo' : 'OK'); @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td><span class="badge-cat">{{ $p->categoria }}</span></td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($p->precio, 2) }}</td>
                <td style="font-weight:700">{{ number_format($p->stock) }}</td>
                <td>{{ $p->unidad_venta }}</td>
                <td><span class="badge-stock {{ $cls }}">{{ $lbl }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <div class="empty-state">No hay materiales de empaque registrados</div>
    @endif
</div>

@endsection
