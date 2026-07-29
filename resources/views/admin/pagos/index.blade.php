@extends('layouts.admin')
@section('title', 'Pagos a proveedores')
@section('hero')
<div class="hero-band">
    <h1>Pagos a proveedores</h1>
    <p>Proveedores con facturas pendientes de pago</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .adm-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:16px 26px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .adm-summary-main{text-align:center;min-width:100px}
    .adm-summary-pct{font-size:42px;font-weight:800;line-height:1;color:var(--purple)}
    .adm-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}

    .toolbar{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
    .toolbar-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .filter-group{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
    .filter-btn{padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .filter-count{font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;background:rgba(0,0,0,.08);line-height:1.2}
    .filter-btn.active .filter-count{background:rgba(255,255,255,.25)}
    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:16px 18px}
    .filter-form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:140px;flex:1}
    .filter-field.search-field{flex:2;min-width:200px}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px}
    .filter-field input{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .filter-field input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .filter-actions{display:flex;gap:8px;align-items:center;padding-bottom:1px}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-primary:hover{background:var(--purple-dark)}
    .btn-outline{padding:9px 16px;background:var(--white);color:var(--gray-text);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;text-decoration:none}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:14px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tbody tr.prov-row{cursor:pointer}
    .admin-table tbody tr.prov-row:hover td{background:var(--purple-subtle)}
    .tbl-wrap{overflow-x:auto}
    .code-link{font-weight:700;color:var(--purple);text-decoration:none}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-block}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .bubble-roja{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;margin-left:8px;border-radius:999px;background:var(--red);color:#fff;font-size:10px;font-weight:700;vertical-align:middle}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500;margin:0}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}

    @media(max-width:768px){
        .filter-field{min-width:100%}
        .filter-form{flex-direction:column;align-items:stretch}
    }
</style>
@endpush
@section('content')
@php
    $q = trim((string) request('q', ''));
    $codigo = trim((string) request('codigo', ''));
    $lista = $proveedoresPendientes;
    if ($q !== '') {
        $lista = $lista->filter(fn ($r) => str_contains(mb_strtolower($r->nombre), mb_strtolower($q))
            || str_contains((string) $r->codigo, $q));
    }
    if ($codigo !== '') {
        $lista = $lista->filter(fn ($r) => str_contains((string) $r->codigo, $codigo));
    }
    $total = $lista->count();
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
        <div class="adm-summary-label">Proveedores con pagos pendientes</div>
    </div>
</div>

<div class="toolbar anim" style="animation-delay:.04s">
    <div class="toolbar-top">
        <div class="filter-group">
            <a href="{{ route('admin.pagos') }}" class="filter-btn {{ $q === '' && $codigo === '' ? 'active' : '' }}">
                Todos <span class="filter-count">{{ $proveedoresPendientes->count() }}</span>
            </a>
        </div>
        <span class="badge-count">{{ $total }} resultado{{ $total !== 1 ? 's' : '' }}</span>
    </div>

    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.pagos') }}" class="filter-form">
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Nombre del proveedor…">
            </div>
            <div class="filter-field">
                <label>Código</label>
                <input type="text" name="codigo" value="{{ $codigo }}" placeholder="Código proveedor…">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($q !== '' || $codigo !== '')
                    <a href="{{ route('admin.pagos') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Proveedores</h4>
            <div class="adm-section-meta">{{ $total }} resultado{{ $total !== 1 ? 's' : '' }} · arriba los que tienen más facturas pendientes</div>
        </div>
    </div>

    @if($lista->isEmpty())
        <div class="empty-state">
            <p>No hay proveedores con facturas pendientes.</p>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Proveedor</th>
                        <th>Facturas pendientes</th>
                        <th>Expediente</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lista as $row)
                        <tr class="prov-row" onclick="window.location='{{ route('admin.pagos.proveedor', $row->codigo) }}'">
                            <td>
                                <a class="code-link" href="{{ route('admin.pagos.proveedor', $row->codigo) }}" onclick="event.stopPropagation()">{{ $row->codigo }}</a>
                            </td>
                            <td style="font-weight:600;">
                                {{ $row->nombre }}
                                @if(($row->notif_sin_leer ?? 0) > 0)
                                    <span class="bubble-roja" title="Pago pendiente sin revisar">{{ $row->notif_sin_leer > 9 ? '9+' : $row->notif_sin_leer }}</span>
                                @endif
                            </td>
                            <td>{{ $row->num_facturas }}</td>
                            <td>
                                @if($row->expediente['ok'])
                                    <span class="pill ok">OK</span>
                                @else
                                    <span class="pill warn">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
