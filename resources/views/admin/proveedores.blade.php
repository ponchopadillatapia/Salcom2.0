@extends('layouts.admin')
@section('title', 'Proveedores — Score')
@section('hero')
<div class="hero-band">
    <h1>🏭 Proveedores — Score</h1>
    <p>Score = 50% entrega a tiempo + 50% puntualidad</p>
</div>
@endsection
@push('styles')
<style>
    .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .search-box{display:flex;gap:8px;flex:1;max-width:420px}
    .search-box input{flex:1;border:1.5px solid var(--border);border-radius:8px;padding:9px 14px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .search-box input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .search-box button{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .search-box button:hover{background:var(--purple-dark)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500}
    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}
    .score-bar{width:80px;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .score-fill{height:100%;border-radius:4px}
    .score-high .score-fill{background:var(--green)}
    .score-mid .score-fill{background:var(--amber)}
    .score-low .score-fill{background:var(--red)}
    .badge-activo{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-activo.si{background:var(--green-bg);color:var(--green)}
    .badge-activo.no{background:var(--red-bg);color:var(--red)}
    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500}
    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.toolbar{flex-direction:column;align-items:stretch}.search-box{max-width:100%}}
</style>
@endpush
@section('content')

<div class="toolbar">
    <form method="GET" action="{{ route('admin.proveedores') }}" class="search-box">
        <input type="text" name="busqueda" placeholder="Buscar por nombre, correo o código…" value="{{ $busqueda ?? '' }}">
        <button type="submit">Buscar</button>
    </form>
    <span class="badge-count">{{ $proveedores->total() }} proveedor{{ $proveedores->total() !== 1 ? 'es' : '' }}</span>
</div>

<div class="admin-table-wrap">
@if($proveedores->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Score Total</th>
                <th>Entrega</th>
                <th>Puntualidad</th>
                <th>Activo</th>
            </tr>
        </thead>
        <tbody>
        @foreach($proveedores as $p)
            @php
                $scoreClass = $p->score_total >= 70 ? 'score-high' : ($p->score_total >= 40 ? 'score-mid' : 'score-low');
            @endphp
            <tr>
                <td style="font-weight:600;color:var(--purple)">{{ $p->codigo_compras ?? '—' }}</td>
                <td>{{ $p->nombre ?? '—' }}</td>
                <td>{{ $p->correo ?? '—' }}</td>
                <td>
                    <div class="score-bar {{ $scoreClass }}"><div class="score-fill" style="width:{{ $p->score_total }}%"></div></div>
                    <strong>{{ number_format($p->score_total, 0) }}%</strong>
                </td>
                <td>{{ number_format($p->score_entrega, 0) }}%</td>
                <td>{{ number_format($p->score_puntualidad, 0) }}%</td>
                <td><span class="badge-activo {{ $p->activo ? 'si' : 'no' }}">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($proveedores->hasPages())
        <div class="pagination-wrap">{{ $proveedores->links() }}</div>
    @endif
@else
    <div class="empty-state"><p>No se encontraron proveedores{{ $busqueda ? ' con esa búsqueda' : '' }}</p></div>
@endif
</div>

@endsection
