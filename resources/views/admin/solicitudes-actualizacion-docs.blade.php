@extends('layouts.admin')

@section('title', 'Actualización de docs')
@section('page_title', 'Actualización de docs')

@section('content')
<style>
    .sol-tabs{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
    .sol-tab{padding:7px 14px;border-radius:8px;border:1px solid var(--border,#e5e7eb);background:#fff;font-size:12px;font-weight:600;text-decoration:none;color:var(--gray-text,#374151)}
    .sol-tab.active{background:var(--purple,#6B3FA0);color:#fff;border-color:transparent}
    .sol-card{background:#fff;border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:16px 18px;margin-bottom:12px}
    .sol-card h3{font-size:14px;margin:0 0 6px}
    .sol-meta{font-size:12px;color:var(--gray-muted,#6b7280);margin-bottom:10px}
    .sol-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .btn-ok{padding:7px 14px;background:#16a34a;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer}
    .btn-no{padding:7px 14px;background:#dc2626;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer}
    .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600}
    .badge.pendiente{background:#fef3c7;color:#92400e}
    .badge.aprobada{background:#dcfce7;color:#166534}
    .badge.rechazada{background:#fee2e2;color:#991b1b}
</style>

@if(session('mensaje'))
    <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:10px 14px;border-radius:10px;margin-bottom:14px;font-size:13px;">{{ session('mensaje') }}</div>
@endif

<div class="sol-tabs">
    @foreach(['pendiente'=>'Pendientes','aprobada'=>'Aprobadas','rechazada'=>'Rechazadas','todas'=>'Todas'] as $k=>$label)
        <a class="sol-tab {{ $estatus === $k || ($k==='todas' && !in_array($estatus,['pendiente','aprobada','rechazada'],true)) ? 'active' : '' }}"
           href="{{ route('admin.solicitudes-docs', ['estatus' => $k === 'todas' ? 'all' : $k]) }}">{{ $label }}</a>
    @endforeach
</div>

@forelse($solicitudes as $s)
    <div class="sol-card">
        <h3>
            {{ $s->proveedor->nombre ?? 'Proveedor #'.$s->proveedor_id }}
            <span class="badge {{ $s->estatus }}">{{ ucfirst($s->estatus) }}</span>
        </h3>
        <div class="sol-meta">
            Campo: <strong>{{ $s->campo }}</strong>
            · Actual: {{ $s->valor_actual ?: '—' }}
            · Propuesto: {{ $s->valor_propuesto ?: '—' }}
            · {{ $s->created_at?->format('d/m/Y H:i') }}
        </div>
        @if($s->motivo)<div class="sol-meta">Motivo: {{ $s->motivo }}</div>@endif
        @if($s->notas)<div class="sol-meta">Notas: {{ $s->notas }}</div>@endif

        @if($s->estatus === 'pendiente')
        <div class="sol-actions">
            <form method="POST" action="{{ route('admin.solicitudes-docs.marcar') }}">
                @csrf
                <input type="hidden" name="solicitud_id" value="{{ $s->id }}">
                <input type="hidden" name="accion" value="aprobar">
                <button class="btn-ok" type="submit">✓ Marcar revisada / OK</button>
            </form>
            <form method="POST" action="{{ route('admin.solicitudes-docs.marcar') }}">
                @csrf
                <input type="hidden" name="solicitud_id" value="{{ $s->id }}">
                <input type="hidden" name="accion" value="rechazar">
                <button class="btn-no" type="submit">✕ Rechazar</button>
            </form>
            @if($s->proveedor)
                <a href="{{ route('admin.expediente-fiscal.ver', $s->proveedor) }}" style="font-size:12px;font-weight:600;color:var(--purple,#6B3FA0);">Ver expediente</a>
            @endif
        </div>
        @endif
    </div>
@empty
    <div class="sol-card"><p style="margin:0;color:var(--gray-muted);">No hay solicitudes en este filtro.</p></div>
@endforelse

<div style="margin-top:12px;">{{ $solicitudes->links() }}</div>
@endsection
