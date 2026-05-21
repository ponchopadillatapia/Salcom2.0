@extends('layouts.admin')
@section('title', 'Materia Prima')
@section('hero')
<div class="hero-band">
    <h1>Materia Prima</h1>
    <p>Encargada: Alejandra · Control de materias primas e insumos</p>
</div>
@endsection
@push('styles')
<style>
    .kpi-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px}
    .kpi-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;text-align:center}
    .kpi-val{font-size:28px;font-weight:800;line-height:1;margin-bottom:4px}
    .kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px}
    .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .btn-primary:hover{background:var(--purple-dark)}
    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}
    .familia-head{padding:12px 16px;font-size:12px;font-weight:700;color:var(--purple);text-transform:uppercase;letter-spacing:.5px;background:var(--purple-subtle);border-bottom:1px solid var(--border)}
    .badge-stock{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-stock.ok{background:var(--green-bg);color:var(--green)}
    .badge-stock.low{background:var(--amber-bg);color:var(--amber)}
    .badge-stock.out{background:var(--red-bg);color:var(--red)}
    .badge-etapa{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;background:var(--blue-bg);color:var(--blue)}
    .section-title{font-size:14px;font-weight:700;color:var(--gray-text);margin:8px 0 12px}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted);font-size:14px;font-weight:500}
    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green)}
    @media(max-width:768px){.kpi-row{grid-template-columns:1fr}.admin-table-wrap{overflow-x:auto}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif

@php
    $total = $productos->count();
    $sinStock = $productos->where('stock', '<=', 0)->count();
    $stockBajo = $productos->where('stock', '>', 0)->where('stock', '<', 50)->count();
@endphp

<div class="kpi-row">
    <div class="kpi-card"><div class="kpi-val" style="color:var(--purple)">{{ $total }}</div><div class="kpi-label">Materias primas</div></div>
    <div class="kpi-card"><div class="kpi-val" style="color:var(--amber)">{{ $stockBajo }}</div><div class="kpi-label">Stock bajo</div></div>
    <div class="kpi-card"><div class="kpi-val" style="color:var(--red)">{{ $sinStock }}</div><div class="kpi-label">Agotados</div></div>
</div>

<div class="toolbar">
    <p class="section-title" style="margin:0">Inventario agrupado por familia</p>
    <a href="{{ route('admin.materia-prima.crear') }}" class="btn-primary">+ Nuevo producto</a>
</div>

<div class="admin-table-wrap">
    @if($productosPorFamilia->count())
        @foreach($productosPorFamilia as $familia => $items)
        <div class="familia-head">{{ $familia }} ({{ $items->count() }})</div>
        <table class="admin-table">
            <thead><tr><th>Código</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Unidad</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach($items as $p)
                @php $cls = $p->stock <= 0 ? 'out' : ($p->stock < 50 ? 'low' : 'ok'); $lbl = $p->stock <= 0 ? 'Agotado' : ($p->stock < 50 ? 'Bajo' : 'OK'); @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $p->codigo }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($p->precio, 2) }}</td>
                    <td style="font-weight:700">{{ number_format($p->stock) }}</td>
                    <td>{{ $p->unidad_venta }}</td>
                    <td><span class="badge-stock {{ $cls }}">{{ $lbl }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endforeach
    @else
    <div class="empty-state">No hay materias primas registradas</div>
    @endif
</div>

@if($muestras->count())
<p class="section-title">Muestras en proceso de validación</p>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Lote</th><th>Producto</th><th>Proveedor</th><th>Cantidad</th><th>Etapa</th><th>Fecha</th></tr></thead>
        <tbody>
        @foreach($muestras as $m)
        <tr>
            <td style="font-weight:700;color:var(--purple)">{{ $m->lote }}</td>
            <td>{{ $m->producto }}</td>
            <td>{{ $m->proveedor }}</td>
            <td>{{ $m->cantidad }} {{ $m->unidad }}</td>
            <td><span class="badge-etapa">{{ ucfirst($m->etapa) }}</span></td>
            <td style="color:var(--gray-muted)">{{ $m->fecha_registro?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
