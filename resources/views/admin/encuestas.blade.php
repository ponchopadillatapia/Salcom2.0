@extends('layouts.admin')
@section('title', 'Encuestas de Satisfacción')
@section('hero')
<div class="hero-band">
    <h1>📋 Encuestas de Satisfacción</h1>
    <p>Resultados y retroalimentación de clientes</p>
</div>
@endsection
@push('styles')
<style>
    .metrics-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
    .metric-card{background:var(--white);border-radius:12px;padding:18px 20px;border:1px solid var(--border);position:relative;overflow:hidden}
    .metric-card .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:12px 0 0 12px}
    .metric-label{font-size:12px;color:var(--gray-muted);font-weight:500;margin-bottom:6px;padding-left:8px}
    .metric-value{font-size:24px;font-weight:600;color:var(--purple-dark);padding-left:8px;line-height:1}
    .metric-sub{font-size:11px;color:var(--gray-muted);padding-left:8px;margin-top:4px}

    .stars{color:#f59e0b;font-size:14px;letter-spacing:1px}
    .stars-muted{color:#d1d5db}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}
    .comment-cell{max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .pagination-wrap nav{display:flex;gap:4px}
    .pagination-wrap .page-link{padding:6px 12px;font-size:12px;border:1px solid var(--border);border-radius:6px;color:var(--gray-text);text-decoration:none;transition:all .15s}
    .pagination-wrap .page-link:hover{background:var(--purple-light);color:var(--purple)}
    .pagination-wrap .page-item.active .page-link{background:var(--purple);color:#fff;border-color:var(--purple)}
    .pagination-wrap .page-item.disabled .page-link{color:var(--gray-muted);opacity:.5}

    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}

    @media(max-width:900px){.metrics-row{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:768px){.metrics-row{grid-template-columns:1fr}.admin-table-wrap{overflow-x:auto}}
</style>
@endpush
@section('content')

{{-- Métricas --}}
<div class="metrics-row">
    <div class="metric-card">
        <div class="accent" style="background:var(--purple)"></div>
        <div class="metric-label">Calificación general</div>
        <div class="metric-value">{{ $totalEncuestas ? number_format($promedioGeneral, 1) : '—' }}</div>
        <div class="metric-sub">de 5 estrellas</div>
    </div>
    <div class="metric-card">
        <div class="accent" style="background:var(--blue)"></div>
        <div class="metric-label">Tiempo de entrega</div>
        <div class="metric-value">{{ $totalEncuestas ? number_format($promedioEntrega, 1) : '—' }}</div>
        <div class="metric-sub">promedio</div>
    </div>
    <div class="metric-card">
        <div class="accent" style="background:var(--green)"></div>
        <div class="metric-label">Calidad de producto</div>
        <div class="metric-value">{{ $totalEncuestas ? number_format($promedioCalidad, 1) : '—' }}</div>
        <div class="metric-sub">promedio</div>
    </div>
    <div class="metric-card">
        <div class="accent" style="background:var(--amber)"></div>
        <div class="metric-label">Total encuestas</div>
        <div class="metric-value">{{ $totalEncuestas }}</div>
        <div class="metric-sub">respuestas recibidas</div>
    </div>
</div>

{{-- Tabla --}}
<div class="admin-table-wrap">
@if($encuestas->count())
    <table class="admin-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Calificación</th>
                <th>Tiempo entrega</th>
                <th>Calidad</th>
                <th>Comentarios</th>
            </tr>
        </thead>
        <tbody>
        @foreach($encuestas as $e)
            <tr>
                <td style="font-weight:600;color:var(--purple)">{{ $e->codigo_cliente }}</td>
                <td>{{ $e->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>
                    <span class="stars">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $e->calificacion ? '' : 'stars-muted' }}">★</span>
                        @endfor
                    </span>
                </td>
                <td>{{ $e->tiempo_entrega }}/5</td>
                <td>{{ $e->calidad_producto }}/5</td>
                <td class="comment-cell" title="{{ $e->comentarios }}">{{ $e->comentarios ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($encuestas->hasPages())
        <div class="pagination-wrap">{{ $encuestas->links() }}</div>
    @endif
@else
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <p>Aún no hay encuestas registradas</p>
    </div>
@endif
</div>

@endsection
