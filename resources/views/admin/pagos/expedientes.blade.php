@extends('layouts.admin')
@section('title', 'Expedientes de Pago')
@section('hero')
<div class="hero-band">
    <h1>Expedientes de Pago</h1>
    <p>Archivero digital de los pagos. Entra a un proveedor para ver sus expedientes por mes.</p>
</div>
@endsection

@push('styles')
<style>
    .arch-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:20px}
    .arch-search{position:relative;flex:1;min-width:240px;max-width:400px}
    .arch-search input{width:100%;border:1.5px solid var(--border);border-radius:9px;padding:9px 12px 9px 36px;font-size:13px;font-family:inherit;outline:none}
    .arch-search input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .arch-search svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);opacity:.5}
    .arch-count{font-size:13px;color:var(--gray-muted);font-weight:500}

    .prov-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
    .prov-card{display:block;text-decoration:none;background:var(--white);border:1px solid var(--border);border-radius:14px;padding:18px;
        transition:transform .15s,box-shadow .15s,border-color .15s;box-shadow:var(--shadow-sm);position:relative}
    .prov-card:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(107,63,160,.12);border-color:var(--purple-mid,#c4b5fd)}
    .prov-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
    .prov-ico{width:42px;height:42px;color:var(--purple)}
    .prov-pend{font-size:10px;font-weight:700;padding:4px 10px;border-radius:999px;background:#fffbeb;color:#d97706;text-transform:uppercase}
    /* Punto azul: expedientes nuevos sin ver (estilo WhatsApp) */
    .prov-card.nuevo{border-color:#bfdbfe;background:#f5f9ff}
    .prov-dot{display:inline-block;width:9px;height:9px;border-radius:50%;background:#2563eb;margin-right:7px;vertical-align:middle;animation:provBlink 1.3s ease-in-out infinite}
    @keyframes provBlink{0%,100%{opacity:1}50%{opacity:.35}}
    .prov-nombre{font-size:14px;font-weight:700;color:var(--gray-text);margin:0 0 6px;line-height:1.3}
    .prov-meta{font-size:12px;color:var(--gray-muted);line-height:1.6}
    .prov-monto{font-size:15px;font-weight:700;color:var(--purple);margin-top:8px}
    .arch-empty{text-align:center;padding:48px 24px;color:var(--gray-muted)}
</style>
@endpush

@section('content')
<div class="arch-toolbar">
    <form method="GET" action="{{ route('admin.expedientes-pago') }}" class="arch-search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="busqueda" value="{{ $busqueda }}" placeholder="Buscar proveedor por nombre o código..." onchange="this.form.submit()">
    </form>
    <span class="arch-count">{{ $proveedores->count() }} proveedor(es) · {{ $total }} expediente(s)</span>
</div>

<div class="prov-grid">
    @forelse($proveedores as $prov)
        <a href="{{ route('admin.expedientes-pago.proveedor', $prov->proveedor_id) }}" class="prov-card {{ ($prov->no_vistos ?? 0) > 0 ? 'nuevo' : '' }}">
            <div class="prov-top">
                <svg class="prov-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                @if($prov->pendientes > 0)
                    <span class="prov-pend">{{ $prov->pendientes }} pendiente(s)</span>
                @endif
            </div>
            <p class="prov-nombre">@if(($prov->no_vistos ?? 0) > 0)<span class="prov-dot" title="Expedientes nuevos sin ver"></span>@endif{{ $prov->nombre }}</p>
            <div class="prov-meta">
                {{ $prov->codigo ?? '—' }}<br>
                {{ $prov->total }} expediente(s)
            </div>
            <div class="prov-monto">${{ number_format((float) $prov->monto_total, 2) }}</div>
        </a>
    @empty
        <div class="arch-empty" style="grid-column:1/-1">
            @if($busqueda !== '')
                No se encontraron proveedores con "{{ $busqueda }}".
            @else
                Aún no hay expedientes de pago. Se crean cuando confirmas un pago a proveedor.
            @endif
        </div>
    @endforelse
</div>
@endsection
