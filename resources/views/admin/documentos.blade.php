@extends('layouts.admin')
@section('title', 'Documentos Fiscales')
@section('hero')
<div class="hero-band">
    <h1>📄 Documentos Fiscales de Proveedores</h1>
    <p>Revisión y validación de documentos subidos por proveedores</p>
</div>
@endsection
@push('styles')
<style>
    .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .filter-group{display:flex;gap:6px;flex-wrap:wrap}
    .filter-btn{padding:8px 16px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);cursor:pointer;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-block}
    .filter-btn:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500}
    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}
    .badge-doc{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-doc.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-doc.aprobado{background:var(--green-bg);color:var(--green)}
    .badge-doc.rechazado{background:var(--red-bg);color:var(--red)}
    .tipo-badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px;background:var(--purple-light);color:var(--purple);text-transform:uppercase;letter-spacing:.3px}
    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500}
    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.toolbar{flex-direction:column;align-items:stretch}}
</style>
@endpush
@section('content')

<div class="toolbar">
    <div class="filter-group">
        <a href="{{ route('admin.documentos') }}" class="filter-btn {{ !$estatus ? 'active' : '' }}">Todos</a>
        <a href="{{ route('admin.documentos', ['estatus' => 'pendiente']) }}" class="filter-btn {{ $estatus === 'pendiente' ? 'active' : '' }}">Pendientes</a>
        <a href="{{ route('admin.documentos', ['estatus' => 'aprobado']) }}" class="filter-btn {{ $estatus === 'aprobado' ? 'active' : '' }}">Aprobados</a>
        <a href="{{ route('admin.documentos', ['estatus' => 'rechazado']) }}" class="filter-btn {{ $estatus === 'rechazado' ? 'active' : '' }}">Rechazados</a>
    </div>
    <span class="badge-count">{{ $documentos->total() }} documento{{ $documentos->total() !== 1 ? 's' : '' }}</span>
</div>

<div class="admin-table-wrap">
@if($documentos->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>Tipo</th>
                <th>Estatus</th>
                <th>Notas</th>
                <th>Fecha revisión</th>
                <th>Subido</th>
            </tr>
        </thead>
        <tbody>
        @foreach($documentos as $d)
            <tr>
                <td style="font-weight:600">{{ $d->proveedor->nombre ?? $d->proveedor->usuario ?? 'ID: '.$d->proveedor_id }}</td>
                <td><span class="tipo-badge">{{ str_replace('_', ' ', $d->tipo) }}</span></td>
                <td><span class="badge-doc {{ $d->estatus }}">{{ ucfirst($d->estatus) }}</span></td>
                <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $d->notas_revision ?? '—' }}</td>
                <td style="color:var(--gray-muted)">{{ $d->revisado_at?->format('d/m/Y') ?? '—' }}</td>
                <td style="color:var(--gray-muted)">{{ $d->created_at?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($documentos->hasPages())
        <div class="pagination-wrap">{{ $documentos->links() }}</div>
    @endif
@else
    <div class="empty-state"><p>No se encontraron documentos con ese filtro</p></div>
@endif
</div>
@endsection
