@extends('layouts.admin')
@section('title', 'Expedientes · '.($proveedor->nombre ?? ''))
@section('hero')
<div class="hero-band">
    <h1>{{ $proveedor->nombre ?? $proveedor->usuario }}</h1>
    <p>Expedientes de pago del proveedor, por mes</p>
</div>
@endsection

@push('styles')
<style>
    .arch-back{display:inline-flex;align-items:center;gap:6px;color:var(--purple);font-weight:600;font-size:13px;text-decoration:none;margin-bottom:16px}
    .arch-filtros{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
    .arch-fbtn{padding:7px 14px;border:1.5px solid var(--border);border-radius:999px;background:var(--white);color:var(--gray-text);font-size:12px;font-weight:600;text-decoration:none}
    .arch-fbtn.active{background:var(--purple);color:#fff;border-color:var(--purple)}

    .mes-carpeta{border:1px solid var(--border);border-radius:12px;margin-bottom:16px;overflow:hidden;background:var(--white);box-shadow:var(--shadow-sm)}
    .mes-carpeta-head{display:flex;align-items:center;gap:11px;cursor:pointer;padding:14px 18px;font-size:14px;font-weight:700;color:var(--gray-text);background:var(--purple-subtle,#f3e8ff);list-style:none;user-select:none}
    .mes-carpeta-head::-webkit-details-marker{display:none}
    .mes-carpeta-head svg.folder{color:var(--purple);flex-shrink:0}
    .mes-carpeta-titulo{flex:1;text-transform:capitalize}
    .mes-carpeta-count{font-weight:600;font-size:11px;color:var(--purple);background:var(--white);padding:3px 11px;border-radius:999px}
    .mes-chevron{transition:transform .2s;color:var(--purple);flex-shrink:0}
    .mes-carpeta[open] .mes-chevron{transform:rotate(180deg)}
    .mes-actual-tag{font-size:10px;font-weight:700;color:#059669;background:#ecfdf5;padding:2px 9px;border-radius:999px;text-transform:uppercase}

    .tbl-wrap{overflow-x:auto}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:10px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border);white-space:nowrap}
    .admin-table td{padding:11px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light);vertical-align:middle}
    .admin-table tbody tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .exp-num{font-weight:700;color:var(--purple);text-decoration:none}
    .exp-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--red,#dc2626);margin-right:7px;vertical-align:middle;animation:expBlink 1.2s ease-in-out infinite}
    @keyframes expBlink{0%,100%{opacity:1}50%{opacity:.3}}
    .exp-monto{font-weight:700;font-variant-numeric:tabular-nums}
    .exp-badge{font-size:10px;font-weight:700;padding:4px 10px;border-radius:999px;text-transform:uppercase}
    .exp-badge.pendiente{background:#fffbeb;color:#d97706}
    .exp-badge.autorizado{background:#ecfdf5;color:#059669}
    .exp-badge.rechazado{background:#fef2f2;color:#dc2626}
    .exp-ver{color:var(--purple);font-weight:600;text-decoration:none;font-size:12px}
    .arch-empty{text-align:center;padding:48px 24px;color:var(--gray-muted)}
</style>
@endpush

@section('content')
@php
    $filtros = ['' => 'Todos', 'pendiente' => 'Pendientes', 'autorizado' => 'Autorizados', 'rechazado' => 'Rechazados'];
    $badgeLabel = ['pendiente' => 'Pendiente', 'autorizado' => 'Autorizado', 'rechazado' => 'Rechazado'];
@endphp

<a href="{{ route('admin.expedientes-pago') }}" class="arch-back">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Todos los proveedores
</a>

<div class="arch-filtros">
    @foreach($filtros as $val => $label)
        <a href="{{ route('admin.expedientes-pago.proveedor', array_filter(['proveedor' => $proveedor->id, 'estatus' => $val])) }}"
           class="arch-fbtn {{ $estatus === $val ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

@forelse($grupos as $mesKey => $expedientes)
    @php
        $esMesActual = ($mesKey === $mesActual);
        try {
            $tituloMes = $mesKey === 'sin-fecha' ? 'Sin fecha' : \Carbon\Carbon::createFromFormat('Y-m', $mesKey)->locale('es')->isoFormat('MMMM YYYY');
        } catch (\Throwable $e) { $tituloMes = $mesKey; }
    @endphp
    <details class="mes-carpeta" {{ $esMesActual ? 'open' : '' }}>
        <summary class="mes-carpeta-head">
            <svg class="folder" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <span class="mes-carpeta-titulo">{{ $tituloMes }}</span>
            @if($esMesActual)<span class="mes-actual-tag">Mes actual</span>@endif
            <span class="mes-carpeta-count">{{ $expedientes->count() }} expediente(s)</span>
            <svg class="mes-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </summary>
        <div class="tbl-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Expediente</th>
                        <th>Facturas</th>
                        <th>Monto</th>
                        <th>Estatus</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expedientes as $pago)
                        @php $estAut = $pago->estatus_autorizacion ?? 'pendiente'; @endphp
                        <tr>
                            <td>
                                @if(in_array($pago->id, $idsNoVistos ?? []))<span class="exp-dot" title="Nuevo, sin ver"></span>@endif
                                <a href="{{ route('admin.pagos.expediente', $pago) }}" class="exp-num">Expediente #{{ $pago->id }}</a>
                            </td>
                            <td>{{ $pago->num_facturas }}</td>
                            <td class="exp-monto">${{ number_format((float) $pago->monto_total, 2) }}</td>
                            <td><span class="exp-badge {{ $estAut }}">{{ $badgeLabel[$estAut] ?? $estAut }}</span></td>
                            <td><a href="{{ route('admin.pagos.expediente', $pago) }}" class="exp-ver">Ver expediente</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
@empty
    <div class="arch-empty">
        @if($estatus !== '')
            Este proveedor no tiene expedientes {{ $filtros[$estatus] ?? '' }}.
        @else
            Este proveedor aún no tiene expedientes de pago.
        @endif
    </div>
@endforelse
@endsection
