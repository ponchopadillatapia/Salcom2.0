@extends('layouts.admin')
@section('title', 'Facturas')
@section('hero')
<div class="hero-band">
    <h1>Facturas de Proveedores</h1>
    <p>Control de facturación de proveedores</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .inv-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px}
    .inv-metric{background:var(--white);border:1px solid var(--border-light, var(--border));border-radius:14px;padding:20px;position:relative;overflow:hidden;cursor:pointer;transition:box-shadow .15s,border-color .15s;text-decoration:none;color:inherit;display:block}
    .inv-metric:hover{border-color:var(--purple-mid,#c4b5e0);box-shadow:var(--shadow-sm)}
    .inv-metric.is-active{border-color:var(--purple);box-shadow:0 0 0 2px rgba(107,63,160,.12)}
    .inv-metric .accent{position:absolute;top:0;left:0;width:4px;height:100%;border-radius:14px 0 0 14px}
    .inv-metric-label{font-size:12px;color:var(--gray-muted);font-weight:600;margin-bottom:6px}
    .inv-metric-val{font-size:28px;font-weight:700;color:var(--gray-text);line-height:1}
    .inv-metric-sub{font-size:12px;color:var(--gray-muted);margin-top:6px}

    .toolbar{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:16px 18px}
    .filter-form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:140px;flex:1}
    .filter-field.search-field{flex:2;min-width:200px}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px}
    .filter-field input,.filter-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .filter-field input:focus,.filter-field select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .filter-actions{display:flex;gap:8px;align-items:center;padding-bottom:1px;flex-wrap:wrap}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-primary:hover{background:var(--purple-dark)}
    .btn-outline{padding:9px 16px;background:var(--white);color:var(--gray-text);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;text-decoration:none}
    .btn-outline:hover{border-color:var(--purple);color:var(--purple)}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;text-decoration:none;font-family:inherit;transition:var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}
    .active-filters{font-size:12px;color:var(--gray-muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center}
    .active-tag{background:var(--purple-subtle);color:var(--purple);padding:3px 10px;border-radius:999px;font-weight:600;font-size:11px}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}
    .adm-section-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .admin-table tbody tr.date-row:hover td{background:var(--purple-subtle)!important}
    .date-row td{background:var(--purple-subtle)!important;font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)}
    .hora-pill{display:inline-flex;padding:3px 8px;border-radius:999px;background:var(--purple-subtle);color:var(--purple);font-size:11px;font-weight:700;white-space:nowrap}
    .tbl-wrap{overflow-x:auto}

    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.pendiente{background:#f3f4f6;color:#6b7280}
    .badge-est.programada{background:#fef2f2;color:#dc2626}
    .badge-est.pagada{background:#fefce8;color:#ca8a04}
    .badge-est.liquidada{background:#ecfdf5;color:#16a34a}
    .badge-est.cancelada{background:#fef2f2;color:#7f1d1d}
    .badge-vencida{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--red-bg);color:var(--red)}
    .badge-antic{font-size:11px;font-weight:700;padding:2px 9px;border-radius:999px;background:var(--purple-subtle,#f3e8ff);color:var(--purple);white-space:nowrap;cursor:help}
    .dias-count{font-weight:700;font-variant-numeric:tabular-nums;line-height:1.2;white-space:nowrap}
    .dias-count.warn{color:var(--amber)}
    .dias-count.late{color:var(--red)}
    .dias-sub{font-size:10px;color:var(--gray-muted);margin-top:2px;white-space:nowrap}
    .fact-row{cursor:pointer}
    .fact-row:focus{outline:2px solid rgba(107,63,160,.35);outline-offset:-2px}
    .admin-table tbody tr.fact-row.prov-match td{background:rgba(107,63,160,.09)!important;box-shadow:inset 3px 0 0 var(--purple)}
    .admin-table tbody tr.fact-row.prov-pinned td{background:rgba(107,63,160,.14)!important;box-shadow:inset 3px 0 0 var(--purple)}
    .prov-chip{display:inline-flex;align-items:center;margin-top:4px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.3px;color:var(--purple);background:var(--purple-subtle);border:1px solid rgba(107,63,160,.28);cursor:pointer;font-family:inherit;line-height:1.3}
    .prov-chip:hover{border-color:var(--purple);background:#efe8f8}
    .prov-chip.is-active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;display:inline-block}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.neut{background:var(--purple-subtle);color:var(--purple)}
    .pill.pendiente{background:#f3f4f6;color:#6b7280}
    .pill.programada{background:#fef2f2;color:#dc2626}
    .pill.pagada{background:#fefce8;color:#ca8a04}
    .pill.liquidada{background:#ecfdf5;color:#16a34a}
    .pill.cancelada,.pill.rechazada{background:#fef2f2;color:#7f1d1d}

    .fact-modal-overlay{position:fixed;inset:0;background:rgba(15,10,30,.45);z-index:2000;display:none;align-items:center;justify-content:center;padding:20px}
    .fact-modal-overlay.open{display:flex}
    .fact-modal{background:#fff;border-radius:14px;width:min(980px,100%);max-height:90vh;overflow:auto;box-shadow:0 20px 50px rgba(0,0,0,.22)}
    .fact-modal-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border-light);background:var(--gray-soft);position:sticky;top:0}
    .fact-modal-head h3{margin:0;font-size:16px;font-weight:700;color:var(--gray-text)}
    .fact-modal-head p{margin:4px 0 0;font-size:12px;color:var(--gray-muted)}
    .fact-modal-close{border:none;background:transparent;font-size:22px;line-height:1;cursor:pointer;color:var(--gray-muted);padding:4px 8px;border-radius:8px}
    .fact-modal-close:hover{background:var(--purple-subtle);color:var(--purple)}
    .fact-modal-body{padding:16px 20px 20px}
    .fact-modal-table{width:100%;border-collapse:collapse}
    .fact-modal-table th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px;padding:10px 12px;text-align:left;border-bottom:1px solid var(--border);background:#fff}
    .fact-modal-table td{padding:12px;font-size:13px;border-bottom:1px solid var(--border-light);vertical-align:top}
    .fact-docs{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
    .fact-doc{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;font-weight:600;color:var(--purple);text-decoration:none;background:#fff}
    .fact-doc:hover{border-color:var(--purple);background:var(--purple-subtle)}
    .fact-doc.off{opacity:.45;pointer-events:none;color:var(--gray-muted)}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state svg{margin-bottom:12px;opacity:.4}
    .empty-state p{font-size:14px;font-weight:500}

    @media(max-width:768px){
        .inv-metrics{grid-template-columns:1fr 1fr}
        .filter-field{min-width:100%}
        .filter-form{flex-direction:column;align-items:stretch}
    }
</style>
@endpush
@section('content')
@php
    $baseQuery = array_filter([
        'busqueda' => $filtros['busqueda'] ?: null,
        'fecha_desde' => $filtros['fecha_desde'] ?: null,
        'fecha_hasta' => $filtros['fecha_hasta'] ?: null,
    ]);
    $chipActive = fn ($est = null, $venc = false) => (!$filtros['estatus'] && !$filtros['vencidas'] && !$est && !$venc)
        || ($est && $filtros['estatus'] === $est && !$filtros['vencidas'])
        || ($venc && $filtros['vencidas']);
    $conteoProgramadas = (int) (($conteosEstatus['programada'] ?? 0)
        + ($conteosEstatus['aprobada'] ?? 0)
        + ($conteosEstatus['validada'] ?? 0));
@endphp

<div class="inv-metrics anim">
    <a class="inv-metric {{ $chipActive('pendiente') ? 'is-active' : '' }}" href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'pendiente'])) }}">
        <div class="accent" style="background:var(--red,#dc2626)"></div>
        <div class="inv-metric-label">Pendientes</div>
        <div class="inv-metric-val">{{ $conteoPendientes }}</div>
        <div class="inv-metric-sub">Por pagar</div>
    </a>
    <a class="inv-metric {{ $chipActive('programada') ? 'is-active' : '' }}" href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'programada'])) }}">
        <div class="accent" style="background:var(--amber,#d97706)"></div>
        <div class="inv-metric-label">Programadas</div>
        <div class="inv-metric-val">{{ $conteoProgramadas }}</div>
        <div class="inv-metric-sub">En proceso de pago</div>
    </a>
    <a class="inv-metric {{ $chipActive('pagada') ? 'is-active' : '' }}" href="{{ route('admin.facturas', array_merge($baseQuery, ['estatus' => 'pagada'])) }}">
        <div class="accent" style="background:var(--green,#16a34a)"></div>
        <div class="inv-metric-label">Pagadas</div>
        <div class="inv-metric-val">{{ $conteoPagadas }}</div>
        <div class="inv-metric-sub">Ya liquidadas</div>
    </a>
    <a class="inv-metric {{ $chipActive() ? 'is-active' : '' }}" href="{{ route('admin.facturas', $baseQuery) }}">
        <div class="accent" style="background:var(--purple,#6B3FA0)"></div>
        <div class="inv-metric-label">Todas</div>
        <div class="inv-metric-val">{{ $totalGeneral }}</div>
        <div class="inv-metric-sub">Facturas totales</div>
    </a>
    <div class="inv-metric">
        <div class="accent" style="background:#2563eb"></div>
        <div class="inv-metric-label">Total pendiente</div>
        <div class="inv-metric-val" style="font-size:18px">${{ number_format($montoPendiente ?? 0, 2) }}</div>
        <div class="inv-metric-sub">Por pagar</div>
    </div>
</div>

<div class="toolbar anim" style="animation-delay:.04s">
    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.facturas') }}" class="filter-form">
            @if($filtros['vencidas'])
                <input type="hidden" name="vencidas" value="1">
            @endif
            <div class="filter-field search-field">
                <label>Buscar</label>
                <input type="text" name="busqueda" value="{{ $filtros['busqueda'] }}" placeholder="Folio, código o proveedor…">
            </div>
            <div class="filter-field">
                <label>Estatus</label>
                <select name="estatus" {{ $filtros['vencidas'] ? 'disabled' : '' }}>
                    <option value="">Todos los estatus</option>
                    @foreach($estatusOpciones as $key => $label)
                        <option value="{{ $key }}" {{ $filtros['estatus'] === $key ? 'selected' : '' }}>
                            {{ $label }} ({{ $conteosEstatus[$key] ?? 0 }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label>Vence desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}">
            </div>
            <div class="filter-field">
                <label>Vence hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Filtrar</button>
                @if($filtrosActivos)
                    <a href="{{ route('admin.facturas') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosActivos)
        <div class="active-filters" style="margin-top:12px;">
            <span>Filtros activos:</span>
            @if($filtros['busqueda'])<span class="active-tag">«{{ $filtros['busqueda'] }}»</span>@endif
            @if($filtros['vencidas'])<span class="active-tag">Vencidas</span>@endif
            @if($filtros['estatus'])<span class="active-tag">{{ $estatusOpciones[$filtros['estatus']] ?? ucfirst($filtros['estatus']) }}</span>@endif
            @if($filtros['fecha_desde'])<span class="active-tag">Vence desde {{ $filtros['fecha_desde'] }}</span>@endif
            @if($filtros['fecha_hasta'])<span class="active-tag">Vence hasta {{ $filtros['fecha_hasta'] }}</span>@endif
        </div>
        @endif
    </div>
</div>

<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Listado de facturas</h4>
            <div class="adm-section-meta">
                {{ $facturas->total() }} resultado{{ $facturas->total() !== 1 ? 's' : '' }}
                · lo más reciente arriba · agrupado por fecha de alta
                @if($filtrosActivos)
                    · monto filtrado: <strong style="color:var(--green)">${{ number_format($montoFiltrado, 2) }}</strong>
                @endif
            </div>
        </div>
        <div class="adm-section-toolbar">
            <a href="{{ route('admin.facturas.excel', request()->query()) }}" class="btn-export">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exportar Excel
            </a>
        </div>
    </div>
    @if($facturas->count())
    <div class="tbl-wrap">
        <table class="admin-table" id="tableFacturas">
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Proveedor</th>
                    <th>Monto</th>
                    <th>IVA</th>
                    <th>Total</th>
                    <th>Anticipos</th>
                    <th>Estatus</th>
                    <th>Vencimiento</th>
                    <th style="text-align:right;">Alta</th>
                </tr>
            </thead>
            <tbody>
            @php
                $pagosSvc = app(\App\Services\PagoProveedorService::class);
                $lastDate = null;
            @endphp
            @foreach($facturas as $f)
                @php
                    $vencida = $f->estatus === 'pendiente' && $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
                    $diasVencido = $vencida ? (int) $f->fecha_vencimiento->diffInDays(now()) : 0;
                    $restantes = $f->diasRestantes();
                    $diasLabel = $restantes === null
                        ? '—'
                        : ($restantes > 0 ? $restantes.' días' : ($restantes === 0 ? 'Vence hoy' : 'Vencida ('.abs($restantes).')'));
                    $folioDisp = $pagosSvc->folioFacturaDisplay($f);
                    $saldoProv = (float) ($saldosPendientesProveedor[$f->codigo_proveedor] ?? 0);
                    $det = is_array($f->validacion_detalle) ? $f->validacion_detalle : [];
                    $currentDate = $f->created_at ? $f->created_at->format('Y-m-d') : null;
                @endphp
                @if($currentDate !== $lastDate)
                    <tr class="date-row">
                        <td colspan="8">
                            {{ $f->created_at ? $f->created_at->locale('es')->isoFormat('DD [de] MMMM YYYY') : 'Sin fecha' }}
                        </td>
                    </tr>
                    @php $lastDate = $currentDate; @endphp
                @endif
                <tr class="fact-row"
                    tabindex="0"
                    data-folio="{{ $folioDisp }}"
                    data-vencimiento="{{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}"
                    data-dias="{{ $diasLabel }}"
                    data-plazo="{{ $f->dias_plazo ? $f->dias_plazo.' días' : '' }}"
                    data-vencido="{{ $vencida ? '1' : '0' }}"
                    data-flete="{{ $f->es_fletera ? '1' : '0' }}"
                    data-regimen="{{ $f->regimen_fiscal ?: '—' }}"
                    data-proveedor="{{ $f->proveedor?->nombre ?? $f->codigo_proveedor ?? '—' }}"
                    data-codigo="{{ $f->codigo_proveedor ?? '' }}"
                    data-ret-iva="{{ number_format((float)($f->retencion_iva ?? 0), 2) }}"
                    data-ret-isr="{{ number_format((float)($f->retencion_isr ?? 0), 2) }}"
                    data-subtotal="{{ number_format((float)$f->monto, 2) }}"
                    data-total="{{ number_format((float)$f->total, 2) }}"
                    data-saldo="{{ number_format($saldoProv, 2) }}"
                    data-estatus="{{ $estatusOpciones[$f->estatus] ?? ucfirst($f->estatus) }}"
                    data-estatus-slug="{{ $f->estatus }}"
                    data-pdf="{{ $f->archivo_pdf ? asset('storage/'.$f->archivo_pdf) : '' }}"
                    data-xml="{{ $f->archivo_xml ? asset('storage/'.$f->archivo_xml) : '' }}"
                    data-oc="{{ $f->archivo_oc ? asset('storage/'.$f->archivo_oc) : '' }}"
                    data-producto="{{ $det['producto'] ?? '' }}"
                    data-advertencias="{{ e(json_encode($det['advertencias'] ?? [], JSON_UNESCAPED_UNICODE)) }}"
                >
                    <td style="font-weight:700;color:var(--purple)">{{ $folioDisp }}</td>
                    <td>
                        <div style="font-weight:600">{{ $f->proveedor?->nombre ?? $f->codigo_proveedor ?? '—' }}</div>
                        @if($f->codigo_proveedor)
                            <button type="button" class="prov-chip" data-codigo-chip="{{ $f->codigo_proveedor }}" title="Resaltar facturas de este proveedor">{{ $f->codigo_proveedor }}</button>
                        @endif
                    </td>
                    <td style="font-variant-numeric:tabular-nums">${{ number_format($f->monto, 2) }}</td>
                    <td style="font-variant-numeric:tabular-nums;color:var(--gray-muted)">${{ number_format($f->monto_iva, 2) }}</td>
                    <td style="font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)">${{ number_format($f->total, 2) }}</td>
                    <td>
                        @php
                            $antsFac = ($anticiposPorFactura[$f->id] ?? collect());
                            $numAnts = $antsFac->count();
                            $totalAnts = (float) $antsFac->sum('monto_aplicado');
                        @endphp
                        @if($numAnts > 0)
                            <span class="badge-antic"
                                title="{{ $antsFac->map(fn($a) => ($a->folio_general ?: 'Anticipo').' · $'.number_format((float)$a->monto_aplicado, 2))->implode(' | ') }}">
                                {{ $numAnts }} · ${{ number_format($totalAnts, 2) }}
                            </span>
                        @else
                            <span style="color:var(--gray-muted);font-size:12px">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge-est {{ $f->estatus }}">{{ $estatusOpciones[$f->estatus] ?? ucfirst($f->estatus) }}</span>
                        @if($vencida)
                            <span class="badge-vencida">{{ $diasVencido }} día{{ $diasVencido === 1 ? '' : 's' }}</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        @include('partials.celda-vencimiento', [
                            'fecha' => $f->fecha_vencimiento,
                            'plazo' => $f->dias_plazo,
                        ])
                    </td>
                    <td style="text-align:right;">
                        <span class="hora-pill">{{ $f->created_at?->format('h:i a') ?? '—' }}</span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if($facturas->hasPages())
        <div class="pagination-wrap">{{ $facturas->links() }}</div>
    @endif
    @else
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p>No se encontraron facturas con los filtros seleccionados.</p>
        @if($filtrosActivos)
            <p style="margin-top:8px;"><a href="{{ route('admin.facturas') }}" style="color:var(--purple);font-weight:600;">Quitar filtros</a></p>
        @endif
    </div>
    @endif
</div>

<div class="fact-modal-overlay" id="factModal" aria-hidden="true">
    <div class="fact-modal" role="dialog" aria-modal="true" aria-labelledby="factModalTitle">
        <div class="fact-modal-head">
            <div>
                <h3 id="factModalTitle">Detalle de factura</h3>
                <p id="factModalSub">—</p>
            </div>
            <button type="button" class="fact-modal-close" id="factModalClose" aria-label="Cerrar">&times;</button>
        </div>
        <div class="fact-modal-body">
            <div style="overflow-x:auto;">
                <table class="fact-modal-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Vencimiento</th>
                            <th>Flete</th>
                            <th>Régimen</th>
                            <th>Proveedor</th>
                            <th>Retenciones</th>
                            <th>Subtotal</th>
                            <th>Total</th>
                            <th>Saldo proveedor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="mFolio" style="font-weight:700;color:var(--purple)"></td>
                            <td id="mVenc"></td>
                            <td id="mFlete"></td>
                            <td id="mRegimen"></td>
                            <td id="mProv"></td>
                            <td id="mRet" style="font-size:12px;white-space:nowrap"></td>
                            <td id="mSub" class="monto"></td>
                            <td id="mTotal" class="monto"></td>
                            <td id="mSaldo" class="monto"></td>
                            <td id="mStatus"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p id="mProducto" style="margin:12px 0 0;font-size:12px;color:var(--gray-muted);display:none;"></p>
            <ul id="mAdvertencias" style="display:none;margin:10px 0 0;padding-left:18px;font-size:12px;color:#92400e;"></ul>
            <div class="fact-docs" id="mDocs"></div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(function () {
    var overlay = document.getElementById('factModal');
    if (!overlay) return;
    var closeBtn = document.getElementById('factModalClose');
    var table = document.getElementById('tableFacturas');
    var pinnedCodigo = null;

    function rows() {
        return Array.prototype.slice.call(document.querySelectorAll('#tableFacturas .fact-row'));
    }

    function setHighlight(codigo, mode) {
        rows().forEach(function (r) {
            var same = !!codigo && r.dataset.codigo === codigo;
            r.classList.toggle('prov-match', mode === 'hover' && same);
            r.classList.toggle('prov-pinned', mode === 'pin' && same);
        });
        document.querySelectorAll('.prov-chip').forEach(function (chip) {
            chip.classList.toggle('is-active', mode === 'pin' && chip.getAttribute('data-codigo-chip') === codigo);
        });
    }

    function clearHover() {
        if (pinnedCodigo) return;
        rows().forEach(function (r) { r.classList.remove('prov-match'); });
    }

    function openModal(row) {
        var d = row.dataset;
        document.getElementById('factModalTitle').textContent = 'Factura ' + (d.folio || '');
        document.getElementById('factModalSub').textContent = (d.proveedor || '') + (d.codigo ? ' · ' + d.codigo : '');
        document.getElementById('mFolio').textContent = d.folio || '—';
        var venc = document.getElementById('mVenc');
        var diasTxt = d.dias || '';
        var plazoTxt = d.plazo ? ' · de ' + d.plazo : '';
        venc.innerHTML = (d.vencimiento || '—') + (diasTxt && diasTxt !== '—' ? '<div style="font-size:11px;font-weight:600;margin-top:2px;">' + diasTxt + plazoTxt + '</div>' : '');
        venc.style.color = d.vencido === '1' ? 'var(--red)' : '';
        venc.style.fontWeight = d.vencido === '1' ? '700' : '';
        document.getElementById('mFlete').innerHTML = d.flete === '1'
            ? '<span class="pill warn">Sí</span>'
            : '<span class="pill neut">No</span>';
        document.getElementById('mRegimen').textContent = d.regimen || '—';
        document.getElementById('mProv').innerHTML =
            '<div style="font-weight:600;font-size:12px;">' + (d.proveedor || '—') + '</div>' +
            (d.codigo ? '<div style="font-size:10px;color:var(--gray-muted);">' + d.codigo + '</div>' : '');
        document.getElementById('mRet').innerHTML = 'IVA $' + (d.retIva || '0.00') + '<br>ISR $' + (d.retIsr || '0.00');
        document.getElementById('mSub').textContent = '$' + (d.subtotal || '0.00');
        document.getElementById('mTotal').textContent = '$' + (d.total || '0.00');
        document.getElementById('mSaldo').textContent = '$' + (d.saldo || '0.00');
        document.getElementById('mStatus').innerHTML =
            '<span class="badge-est ' + (d.estatusSlug || '') + '">' + (d.estatus || '—') + '</span>';
        var prod = document.getElementById('mProducto');
        if (d.producto) {
            prod.style.display = 'block';
            prod.textContent = 'Concepto: ' + d.producto;
        } else {
            prod.style.display = 'none';
            prod.textContent = '';
        }
        var warnList = document.getElementById('mAdvertencias');
        var warns = [];
        try { warns = JSON.parse(d.advertencias || '[]'); } catch (err) { warns = []; }
        warnList.innerHTML = '';
        if (Array.isArray(warns) && warns.length) {
            warns.forEach(function (w) {
                if (!w) return;
                var li = document.createElement('li');
                li.textContent = w;
                warnList.appendChild(li);
            });
            warnList.style.display = warnList.children.length ? 'block' : 'none';
        } else {
            warnList.style.display = 'none';
        }
        var docs = document.getElementById('mDocs');
        docs.innerHTML = '';
        [
            { label: 'PDF', href: d.pdf },
            { label: 'XML', href: d.xml },
            { label: 'Orden de compra', href: d.oc }
        ].forEach(function (doc) {
            var a = document.createElement(doc.href ? 'a' : 'span');
            a.className = 'fact-doc' + (doc.href ? '' : ' off');
            a.textContent = doc.href ? doc.label : (doc.label + ' (no adjunto)');
            if (doc.href) {
                a.href = doc.href;
                a.target = '_blank';
                a.rel = 'noopener';
            }
            docs.appendChild(a);
        });
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
    }

    rows().forEach(function (row) {
        row.addEventListener('mouseenter', function () {
            if (pinnedCodigo) return;
            if (!row.dataset.codigo) return;
            setHighlight(row.dataset.codigo, 'hover');
        });
        row.addEventListener('mouseleave', clearHover);
        row.addEventListener('click', function () { openModal(row); });
        row.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openModal(row);
            }
        });
    });

    document.querySelectorAll('.prov-chip').forEach(function (chip) {
        chip.addEventListener('click', function (e) {
            e.stopPropagation();
            var codigo = chip.getAttribute('data-codigo-chip') || '';
            if (!codigo) return;
            if (pinnedCodigo === codigo) {
                pinnedCodigo = null;
                setHighlight(null, 'pin');
            } else {
                pinnedCodigo = codigo;
                setHighlight(codigo, 'pin');
            }
        });
    });

    if (table) {
        table.addEventListener('mouseleave', clearHover);
    }

    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });
})();
</script>
@endpush
