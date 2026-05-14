@extends('layouts.admin')
@section('title', 'OC Borradores')
@section('hero')
<div class="hero-band">
    <h1>📋 OC Generadas por IA</h1>
    <p>Borradores de órdenes de compra generados automáticamente — Requieren aprobación</p>
</div>
@endsection
@push('styles')
<style>
    .oc-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px}
    .oc-stat{background:var(--white);border:1px solid var(--border-light);border-radius:12px;padding:18px;text-align:center}
    .oc-stat-val{font-size:28px;font-weight:700;color:var(--gray-text)}
    .oc-stat-label{font-size:12px;color:var(--gray-muted);margin-top:4px}
    .oc-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:20px;margin-bottom:16px}
    .oc-card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    .oc-card-title{font-size:14px;font-weight:700;color:var(--gray-text)}
    .oc-card-meta{font-size:12px;color:var(--gray-muted)}
    .oc-productos{font-size:12px;color:var(--gray-text);margin-bottom:12px}
    .oc-producto-item{padding:6px 0;border-bottom:1px solid var(--border-light)}
    .oc-producto-item:last-child{border-bottom:none}
    .oc-actions{display:flex;gap:8px}
    .btn-aprobar{padding:8px 16px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer}
    .btn-aprobar:hover{background:#15803d}
    .btn-rechazar{padding:8px 16px;background:var(--red-bg);color:var(--red);border:1px solid var(--red);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer}
    .btn-rechazar:hover{background:var(--red);color:#fff}
    .badge-oc{font-size:10px;font-weight:600;padding:3px 8px;border-radius:999px}
    .badge-oc.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-oc.aprobada{background:var(--green-bg);color:var(--green)}
    .badge-oc.rechazada{background:var(--red-bg);color:var(--red)}
    .alert-success{background:var(--green-bg);border:1px solid var(--green);border-radius:8px;padding:10px 16px;font-size:13px;color:var(--green);margin-bottom:16px}
</style>
@endpush
@section('content')

@if(session('mensaje'))
<div class="alert-success">{{ session('mensaje') }}</div>
@endif

<div class="oc-stats">
    <div class="oc-stat">
        <div class="oc-stat-val" style="color:var(--amber)">{{ $stats['pendientes'] }}</div>
        <div class="oc-stat-label">Pendientes de aprobación</div>
    </div>
    <div class="oc-stat">
        <div class="oc-stat-val" style="color:var(--green)">{{ $stats['aprobadas'] }}</div>
        <div class="oc-stat-label">Aprobadas</div>
    </div>
    <div class="oc-stat">
        <div class="oc-stat-val">${{ number_format($stats['monto_pendiente'], 0) }}</div>
        <div class="oc-stat-label">Monto pendiente</div>
    </div>
</div>

@forelse($borradores as $oc)
<div class="oc-card">
    <div class="oc-card-head">
        <div>
            <span class="oc-card-title">OC #{{ $oc->id }} — {{ $oc->tipo }}</span>
            <span class="badge-oc {{ $oc->estatus }}">{{ ucfirst($oc->estatus) }}</span>
        </div>
        <div class="oc-card-meta">
            Proveedor: <strong>{{ $oc->proveedor?->nombre ?? 'N/A' }}</strong> · ${{ number_format($oc->monto_estimado, 2) }} · {{ $oc->created_at->format('d/m/Y H:i') }}
        </div>
    </div>
    <div class="oc-card-meta" style="margin-bottom:8px;">Motivo: {{ $oc->motivo }}</div>
    <div class="oc-productos">
        @foreach($oc->productos ?? [] as $prod)
        <div class="oc-producto-item">
            <strong>{{ $prod['codigo'] ?? 'N/A' }}</strong> — {{ $prod['nombre'] ?? '' }} · {{ $prod['cantidad'] ?? 0 }} {{ $prod['unidad'] ?? '' }} · ${{ number_format($prod['precio_estimado'] ?? 0, 2) }}/u
        </div>
        @endforeach
    </div>
    @if($oc->estatus === 'pendiente')
    <div class="oc-actions">
        <form method="POST" action="{{ route('admin.alertas.oc.aprobar', $oc->id) }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-aprobar">✓ Aprobar</button>
        </form>
        <form method="POST" action="{{ route('admin.alertas.oc.rechazar', $oc->id) }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn-rechazar">✕ Rechazar</button>
        </form>
    </div>
    @endif
</div>
@empty
<div style="text-align:center;padding:48px;color:var(--gray-muted);background:var(--white);border-radius:14px;border:1px solid var(--border-light);">
    <p style="font-size:14px;font-weight:500;">No hay borradores de OC</p>
    <p style="font-size:12px;margin-top:4px;">Se generarán automáticamente cuando el inventario baje del mínimo.</p>
</div>
@endforelse

@if($borradores->hasPages())
<div style="padding:16px;display:flex;justify-content:center;">{{ $borradores->links() }}</div>
@endif

@endsection
