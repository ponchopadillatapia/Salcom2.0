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

{{-- POR QUÉ tabla en vez de tarjetas: dirección pidió verlo como lista horizontal (tipo
     tabla), más fácil de escanear que la cuadrícula de folders. Se conserva toda la lógica
     (enlace multi-proveedor, punto azul de "nuevo", pendientes). --}}
<div class="exp-tabla-wrap">
    <table class="exp-tabla">
        <thead>
            <tr>
                <th>Departamento</th>
                <th style="text-align:center">Expedientes</th>
                <th style="text-align:center">Pendientes</th>
                <th style="text-align:right">Monto total</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($proveedores as $prov)
                @php
                    $destino = route('admin.expedientes-pago.departamento', ['departamento' => $prov->departamento]);
                @endphp
                <tr class="exp-row {{ ($prov->no_vistos ?? 0) > 0 ? 'nuevo' : '' }}" onclick="window.location='{{ $destino }}'">
                    <td class="exp-nombre">
                        @if(($prov->no_vistos ?? 0) > 0)<span class="prov-dot" title="Expedientes nuevos sin ver"></span>@endif
                        <svg class="exp-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                        {{ $prov->nombre }}
                    </td>
                    <td style="text-align:center">{{ $prov->total }}</td>
                    <td style="text-align:center">
                        @if($prov->pendientes > 0)
                            <span class="prov-pend">{{ $prov->pendientes }}</span>
                        @else
                            <span style="color:#9ca3af">—</span>
                        @endif
                    </td>
                    <td style="text-align:right;font-weight:700;color:#6B3FA0">${{ number_format((float) $prov->monto_total, 2) }}</td>
                    <td style="text-align:right"><a href="{{ $destino }}" class="exp-ver">Ver →</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="arch-empty">
                        @if($busqueda !== '')
                            No se encontraron expedientes con "{{ $busqueda }}".
                        @else
                            Aún no hay expedientes de pago. Se crean cuando confirmas un pago.
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('styles')
<style>
    .exp-tabla-wrap{background:var(--white);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .exp-tabla{width:100%;border-collapse:collapse}
    .exp-tabla th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:12px 18px;text-align:left;border-bottom:1px solid var(--border);background:var(--gray-soft)}
    .exp-tabla td{padding:14px 18px;font-size:13px;border-bottom:1px solid var(--border-light);vertical-align:middle}
    .exp-row{cursor:pointer;transition:background .12s}
    .exp-row:hover td{background:var(--purple-subtle)}
    .exp-row.nuevo td{background:#f5f9ff}
    .exp-nombre{font-weight:700;color:var(--gray-text);display:flex;align-items:center;gap:8px}
    .exp-ico{width:20px;height:20px;color:var(--purple);flex-shrink:0}
    .exp-ver{color:var(--purple);text-decoration:none;font-weight:600;font-size:12px}
    .exp-ver:hover{text-decoration:underline}
    .prov-pend{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;background:#fffbeb;color:#d97706}
    .prov-dot{display:inline-block;width:9px;height:9px;border-radius:50%;background:#2563eb;flex-shrink:0;animation:provBlink 1.3s ease-in-out infinite}
    @keyframes provBlink{0%,100%{opacity:1}50%{opacity:.35}}
    .arch-empty{text-align:center;padding:40px 20px;color:var(--gray-muted)}
</style>
@endpush
