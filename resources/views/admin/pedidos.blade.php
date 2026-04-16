@extends('layouts.admin')
@section('title', 'Pedidos')
@section('hero')
<div class="hero-band">
    <h1>📦 Pedidos</h1>
    <p>Consulta y seguimiento de todos los pedidos del sistema</p>
</div>
@endsection
@push('styles')
<style>
    .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .filter-box{display:flex;align-items:center;gap:8px}
    .filter-box label{font-size:12px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px}
    .filter-box select{border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white);cursor:pointer}
    .filter-box select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}

    .badge-estatus{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-estatus.validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.procesando{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.enviado{background:#ede9fe;color:#7c3aed}
    .badge-estatus.entregado{background:var(--green-bg);color:var(--green)}
    .badge-estatus.cancelado{background:var(--red-bg);color:var(--red)}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .pagination-wrap nav{display:flex;gap:4px}
    .pagination-wrap .page-link{padding:6px 12px;font-size:12px;border:1px solid var(--border);border-radius:6px;color:var(--gray-text);text-decoration:none;transition:all .15s}
    .pagination-wrap .page-link:hover{background:var(--purple-light);color:var(--purple)}
    .pagination-wrap .page-item.active .page-link{background:var(--purple);color:#fff;border-color:var(--purple)}
    .pagination-wrap .page-item.disabled .page-link{color:var(--gray-muted);opacity:.5}

    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}

    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.toolbar{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')

<div class="toolbar">
    <form method="GET" action="{{ route('admin.pedidos') }}" class="filter-box">
        <label>Estatus</label>
        <select name="estatus" onchange="this.form.submit()">
            <option value="">Todos</option>
            @foreach($estatusDisponibles as $est)
                <option value="{{ $est }}" {{ ($estatus ?? '') === $est ? 'selected' : '' }}>{{ ucfirst($est) }}</option>
            @endforeach
        </select>
    </form>
    <span class="badge-count">{{ $pedidos->total() }} pedido{{ $pedidos->total() !== 1 ? 's' : '' }}</span>
</div>

<div class="admin-table-wrap">
@if($pedidos->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Tipo pago</th>
                <th>Estatus</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
        @foreach($pedidos as $p)
            <tr>
                <td style="font-weight:600;color:var(--purple)">{{ $p->folio }}</td>
                <td>{{ $p->nombre_cliente }}</td>
                <td>${{ number_format($p->total, 2) }}</td>
                <td>{{ ucfirst($p->tipo_pago) }}</td>
                <td>
                    <span class="badge-estatus {{ $p->estatus }}">{{ ucfirst($p->estatus) }}</span>
                </td>
                <td>{{ $p->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($pedidos->hasPages())
        <div class="pagination-wrap">{{ $pedidos->links() }}</div>
    @endif
@else
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        <p>No se encontraron pedidos{{ $estatus ? ' con estatus "'.ucfirst($estatus).'"' : '' }}</p>
    </div>
@endif
</div>

@endsection
