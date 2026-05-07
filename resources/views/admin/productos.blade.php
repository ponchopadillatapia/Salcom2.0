@extends('layouts.admin')
@section('title', 'Productos')
@section('hero')
<div class="hero-band">
    <h1>Catálogo de Productos</h1>
    <p>Inventario y gestión de productos registrados</p>
</div>
@endsection
@push('styles')
<style>
    .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .search-box{display:flex;gap:8px;flex:1;max-width:480px}
    .search-box input{flex:1;border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .search-box input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .search-box button,.filter-btn{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap}
    .search-box button:hover,.filter-btn:hover{background:var(--purple-dark)}
    .filter-btn.outline{background:var(--white);color:var(--gray-text);border:1.5px solid var(--border)}
    .filter-btn.outline:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .filter-btn.active{background:var(--red);color:#fff;border-color:var(--red)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500}
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
    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500}
    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green)}
    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.toolbar{flex-direction:column;align-items:stretch}.search-box{max-width:100%}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif

<div class="toolbar">
    <form method="GET" action="{{ route('admin.productos') }}" class="search-box">
        <input type="text" name="busqueda" placeholder="Buscar por nombre o código…" value="{{ $busqueda ?? '' }}">
        <button type="submit">Buscar</button>
    </form>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="{{ route('admin.productos', ['sin_stock' => $sinStock ? '' : '1']) }}" class="filter-btn {{ $sinStock ? 'active' : 'outline' }}">
            {{ $sinStock ? '✕ Sin stock' : '⚠ Solo sin stock' }}
        </a>
        <span class="badge-count">{{ $productos->total() }} producto{{ $productos->total() !== 1 ? 's' : '' }}</span>
    </div>
</div>

<div class="admin-table-wrap">
@if($productos->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Precio</th>
                <th>Unidad</th>
                <th>Stock</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
        @foreach($productos as $p)
            @php
                $stockClass = $p->stock <= 0 ? 'out' : ($p->stock < 50 ? 'low' : 'ok');
                $stockLabel = $p->stock <= 0 ? 'Agotado' : ($p->stock < 50 ? 'Bajo' : 'OK');
            @endphp
            <tr>
                <td style="font-weight:700;color:var(--purple)">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td>{{ $p->categoria }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($p->precio, 2) }}</td>
                <td>{{ $p->unidad_venta }}</td>
                <td style="font-weight:600;font-variant-numeric:tabular-nums">{{ number_format($p->stock) }}</td>
                <td><span class="badge-stock {{ $stockClass }}">{{ $stockLabel }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($productos->hasPages())
        <div class="pagination-wrap">{{ $productos->links() }}</div>
    @endif
@else
    <div class="empty-state"><p>No se encontraron productos{{ $busqueda ? ' con esa búsqueda' : '' }}</p></div>
@endif
</div>
@endsection
