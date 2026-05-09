@extends('layouts.area')
@section('title', 'Materia Prima')
@section('area-title', 'Materia Prima')
@section('hero')
    <h1>Materia Prima</h1>
    <p>Encargada: Alejandra · Control de materias primas e insumos</p>
@endsection
@push('styles')
<style>
    .kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
    .kpi{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:22px;text-align:center;transition:box-shadow .2s}
    .kpi:hover{box-shadow:0 4px 14px rgba(0,0,0,.04)}
    .kpi-val{font-size:30px;font-weight:800;line-height:1;margin-bottom:6px}
    .kpi-label{font-size:11px;color:var(--gray-muted);font-weight:600;text-transform:uppercase;letter-spacing:.4px}

    .card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden;margin-bottom:24px;transition:box-shadow .2s}
    .card:hover{box-shadow:0 4px 14px rgba(0,0,0,.04)}
    .card-head{padding:16px 22px;font-size:14px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.6px;padding:11px 16px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}
    .badge{font-size:11px;font-weight:600;padding:4px 12px;border-radius:999px;display:inline-block}
    .badge-ok{background:var(--green-bg);color:var(--green)}
    .badge-low{background:var(--amber-bg);color:var(--amber)}
    .badge-out{background:var(--red-bg);color:var(--red)}
    .badge-etapa{background:var(--blue-bg);color:var(--blue)}
    .empty{text-align:center;padding:36px;color:var(--gray-muted);font-size:13px}
    @media(max-width:768px){.kpis{grid-template-columns:1fr}.card{overflow-x:auto}}
</style>
@endpush
@section('content')

@php
    $total = $productos->count();
    $sinStock = $productos->where('stock', '<=', 0)->count();
    $stockBajo = $productos->where('stock', '>', 0)->where('stock', '<', 50)->count();
@endphp

<div class="kpis">
    <div class="kpi"><div class="kpi-val" style="color:var(--purple)">{{ $total }}</div><div class="kpi-label">Materias primas</div></div>
    <div class="kpi"><div class="kpi-val" style="color:var(--amber)">{{ $stockBajo }}</div><div class="kpi-label">Stock bajo</div></div>
    <div class="kpi"><div class="kpi-val" style="color:var(--red)">{{ $sinStock }}</div><div class="kpi-label">Agotados</div></div>
</div>

<div class="card">
    <div class="card-head">Inventario de Materia Prima</div>
    @if($productos->count())
    <table class="tbl">
        <thead><tr><th>Código</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Unidad</th><th>Estado</th></tr></thead>
        <tbody>
        @foreach($productos as $p)
            @php $cls = $p->stock <= 0 ? 'out' : ($p->stock < 50 ? 'low' : 'ok'); $lbl = $p->stock <= 0 ? 'Agotado' : ($p->stock < 50 ? 'Bajo' : 'OK'); @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($p->precio, 2) }}</td>
                <td style="font-weight:700">{{ number_format($p->stock) }}</td>
                <td>{{ $p->unidad_venta }}</td>
                <td><span class="badge badge-{{ $cls }}">{{ $lbl }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <div class="empty">No hay materias primas registradas</div>
    @endif
</div>

@if($muestras->count())
<div class="card">
    <div class="card-head">Muestras en proceso de validación</div>
    <table class="tbl">
        <thead><tr><th>Lote</th><th>Producto</th><th>Proveedor</th><th>Cantidad</th><th>Etapa</th><th>Fecha</th></tr></thead>
        <tbody>
        @foreach($muestras as $m)
        <tr>
            <td style="font-weight:700;color:var(--purple)">{{ $m->lote }}</td>
            <td>{{ $m->producto }}</td>
            <td>{{ $m->proveedor }}</td>
            <td>{{ $m->cantidad }} {{ $m->unidad }}</td>
            <td><span class="badge badge-etapa">{{ ucfirst($m->etapa) }}</span></td>
            <td style="color:var(--gray-muted)">{{ $m->fecha_registro?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
