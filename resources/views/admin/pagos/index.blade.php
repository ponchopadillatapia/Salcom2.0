@extends('layouts.admin')
@section('title', 'Pagos a proveedores')
@section('hero')
<div class="hero-band">
    <h1>Pagos a proveedores</h1>
    <p>Selecciona proveedor, arma el lote de facturas y descarga el reporte de folios</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}
    .adm-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:22px 26px;margin-bottom:20px;display:flex;align-items:center;gap:28px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .adm-summary-main{text-align:center;min-width:110px}
    .adm-summary-pct{font-size:36px;font-weight:800;line-height:1;color:var(--purple)}
    .adm-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .adm-summary-metrics{flex:1;display:flex;gap:28px;flex-wrap:wrap}
    .adm-metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .adm-metric-val{font-size:22px;font-weight:700}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm);margin-bottom:20px}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:14px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .tbl-wrap{overflow-x:auto}
    .pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;display:inline-block}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.bad{background:var(--red-bg);color:var(--red)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.neut{background:var(--purple-subtle);color:var(--purple)}
    .motivos{font-size:11px;color:var(--gray-muted);margin-top:4px;max-width:260px;line-height:1.35}
    .btn-go{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:var(--purple);color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none}
    .btn-go:hover{background:var(--purple-dark);color:#fff}
    .btn-go.disabled{opacity:.45;pointer-events:none}
    .link{color:var(--purple);font-weight:600;text-decoration:none;font-size:13px}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500;margin:0}
</style>
@endpush
@section('content')
@php
    $okCount = $proveedoresPendientes->filter(fn ($r) => $r->expediente['ok'])->count();
    $blocked = $proveedoresPendientes->count() - $okCount;
    $monto = $proveedoresPendientes->sum('monto_total');
    $facts = $proveedoresPendientes->sum('num_facturas');
@endphp

@if(session('mensaje'))
    <div class="pag-alert ok anim">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
    <div class="pag-alert err anim">{{ session('error') }}</div>
@endif

<div class="adm-summary anim">
    <div class="adm-summary-main">
        <div class="adm-summary-pct">{{ $proveedoresPendientes->count() }}</div>
        <div class="adm-summary-label">Proveedores a pagar</div>
    </div>
    <div class="adm-summary-metrics">
        <div>
            <div class="adm-metric-label">Facturas pendientes</div>
            <div class="adm-metric-val">{{ $facts }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Monto total</div>
            <div class="adm-metric-val">${{ number_format($monto, 2) }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Listos / bloqueados</div>
            <div class="adm-metric-val" style="display:flex;gap:8px;align-items:center;">
                <span class="pill ok">{{ $okCount }} OK</span>
                <span class="pill bad">{{ $blocked }} bloq.</span>
            </div>
        </div>
        <div>
            <div class="adm-metric-label">Lotes recientes</div>
            <div class="adm-metric-val">{{ $lotes->count() }}</div>
        </div>
    </div>
</div>

<div class="adm-section anim">
    <div class="adm-section-head">
        <h4>Proveedores con facturas pendientes</h4>
        <span class="adm-section-meta">Clic en Armar pago para elegir folios</span>
    </div>
    @if($proveedoresPendientes->isEmpty())
        <div class="empty-state">
            <p>No hay facturas en estatus pendiente.</p>
            <p style="margin-top:8px;font-size:12px;">Cuando un proveedor suba facturas en Alta Facturas, aparecerán aquí.</p>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Código</th>
                        <th>Facturas</th>
                        <th>Monto</th>
                        <th>Expediente</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proveedoresPendientes as $row)
                        <tr>
                            <td style="font-weight:600;">{{ $row->nombre }}</td>
                            <td>{{ $row->codigo }}</td>
                            <td>{{ $row->num_facturas }}</td>
                            <td style="font-weight:700;">${{ number_format($row->monto_total, 2) }}</td>
                            <td>
                                @if($row->expediente['ok'])
                                    <span class="pill ok">OK para pagar</span>
                                @else
                                    <span class="pill bad">Bloqueado</span>
                                    <div class="motivos">{{ implode(' · ', $row->expediente['motivos']) }}</div>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <a class="btn-go" href="{{ route('admin.pagos.proveedor', $row->codigo) }}">Armar pago</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="adm-section anim">
    <div class="adm-section-head">
        <h4>Lotes recientes</h4>
        <span class="adm-section-meta">Borradores y confirmados</span>
    </div>
    @if($lotes->isEmpty())
        <div class="empty-state"><p>Aún no hay lotes de pago.</p></div>
    @else
        <div class="tbl-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Proveedor</th>
                        <th>Facturas</th>
                        <th>Neto</th>
                        <th>Estatus</th>
                        <th>Creado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lotes as $lote)
                        <tr>
                            <td>{{ $lote->id }}</td>
                            <td style="font-weight:600;">{{ $lote->proveedor?->nombre ?? $lote->codigo_proveedor }}</td>
                            <td>{{ $lote->num_facturas }}</td>
                            <td>${{ number_format((float)$lote->monto_neto, 2) }}</td>
                            <td>
                                @if($lote->estatus === 'confirmado')
                                    <span class="pill ok">confirmado</span>
                                @elseif($lote->estatus === 'borrador')
                                    <span class="pill warn">borrador</span>
                                @else
                                    <span class="pill neut">{{ $lote->estatus }}</span>
                                @endif
                            </td>
                            <td>{{ $lote->created_at?->format('d/m/Y H:i') }}</td>
                            <td style="text-align:right;"><a class="link" href="{{ route('admin.pagos.show', $lote) }}">Ver detalle</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
