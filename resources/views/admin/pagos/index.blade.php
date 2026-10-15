@extends('layouts.admin')
@section('title', 'Formato para pago')
@section('hero')
<div class="hero-band">
    <h1>Formato para pago</h1>
    <p>Genera formatos de pago y consulta los ya creados</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    /* Botón verde global para agregar al carrito facturas de varios proveedores a la vez. */
    .fp-btn-carrito-global{padding:10px 22px;background:var(--green,#2ecc71);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap;transition:background .15s}
    .fp-btn-carrito-global:hover{background:#22a34d}

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
    .filter-actions{display:flex;gap:8px;align-items:center;padding-bottom:1px}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-primary:hover{background:var(--purple-dark)}
    .btn-outline{padding:9px 16px;background:var(--white);color:var(--gray-text);border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;text-decoration:none}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:14px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tbody tr.prov-row{cursor:pointer}
    .admin-table tbody tr.prov-row:hover td{background:var(--purple-subtle)}
    .admin-table tbody tr.row-nuevo td{background:#f5f9ff}
    /* Patrón "visto": punto azul (nivel padre con items nuevos) y rojo (item sin ver) */
    .dot-azul{display:inline-block;width:9px;height:9px;border-radius:50%;background:#2563eb;margin-right:7px;vertical-align:middle;animation:dotBlink 1.3s ease-in-out infinite}
    .dot-rojo{display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc2626;margin-right:7px;vertical-align:middle;animation:dotBlink 1.2s ease-in-out infinite}
    @keyframes dotBlink{0%,100%{opacity:1}50%{opacity:.3}}
    .tbl-wrap{overflow-x:auto}
    .code-link{font-weight:700;color:var(--purple);text-decoration:none}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-block}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.pendiente{background:#f3f4f6;color:#6b7280}
    .pill.programada{background:#fef2f2;color:#dc2626}
    .pill.pagada{background:#fefce8;color:#ca8a04}
    .pill.liquidada{background:#ecfdf5;color:#16a34a}
    .pill.cancelada,.pill.rechazada{background:#fef2f2;color:#7f1d1d}
    .bubble-roja{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;margin-left:8px;border-radius:999px;background:var(--red);color:#fff;font-size:10px;font-weight:700;vertical-align:middle}
    .hora-bubble{display:inline-flex;align-items:center;justify-content:center;padding:3px 8px;border-radius:999px;background:var(--red);color:#fff;font-size:11px;font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums}
    .hora-bubble.leida{background:var(--gray-muted);opacity:.85}
    .dias-count{font-weight:700;font-variant-numeric:tabular-nums;line-height:1.2;white-space:nowrap}
    .dias-count.warn{color:var(--amber)}
    .dias-count.late{color:var(--red)}
    .dias-sub{font-size:10px;color:var(--gray-muted);margin-top:2px;white-space:nowrap}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500;margin:0}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .pag-alert.err{background:var(--red-bg);color:var(--red);border:1px solid var(--red)}
    .active-filters{font-size:12px;color:var(--gray-muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:12px}
    .active-tag{background:var(--purple-subtle);color:var(--purple);padding:3px 10px;border-radius:999px;font-weight:600;font-size:11px}

    @media(max-width:768px){
        .inv-metrics{grid-template-columns:1fr 1fr}
        .filter-field{min-width:100%}
        .filter-form{flex-direction:column;align-items:stretch}
    }

    /* Pestañas: dos vistas (lista de proveedores / formatos creados) sin recargar. */
    .fp-tabs{display:flex;gap:6px;margin-bottom:18px;border-bottom:2px solid var(--border-light,var(--border))}
    .fp-tab{appearance:none;background:none;border:none;cursor:pointer;font-family:inherit;
        font-size:14px;font-weight:600;color:var(--gray-muted);padding:10px 18px;border-radius:8px 8px 0 0;
        border-bottom:3px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s}
    .fp-tab:hover{color:var(--purple)}
    .fp-tab.is-active{color:var(--purple);border-bottom-color:var(--purple);background:var(--purple-subtle)}
    .fp-panel{display:none}
    .fp-panel.is-active{display:block}
</style>
@endpush
@section('content')
@php
    // El filtrado, los KPIs y la paginación ahora vienen del controlador
    // (AdminPagosController::index). Aquí solo se pinta.
    $filtrosBusqueda = $q !== '' || $codigo !== '';
    $filtrosActivos = $q !== '' || $codigo !== '' || $expediente !== '';

    // $proveedoresPendientes es un paginador (LengthAwarePaginator). Se pinta plano,
    // respetando el orden del controlador (los que deben más, arriba).
    $total = $proveedoresPendientes->total();

    $chipBase = array_filter([
        'q' => $q ?: null,
        'codigo' => $codigo ?: null,
    ]);
@endphp

@if(session('mensaje'))
    <div class="pag-alert ok anim">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
    <div class="pag-alert err anim">{{ session('error') }}</div>
@endif

{{-- Dos pestañas: (1) buscar/listar proveedores con facturas pendientes; (2) formatos ya creados.
     Es solo visual: ambas secciones ya existían; aquí se separan en pestañas sin recargar. --}}
<div class="fp-tabs anim">
    <button type="button" class="fp-tab is-active" data-fp-tab="lista">Formato para pago</button>
    <button type="button" class="fp-tab" data-fp-tab="creados">Formatos para pago creados</button>
</div>

{{-- ── PANEL 1: proveedores con facturas pendientes (buscador + tabla) ── --}}
<div class="fp-panel is-active" id="fp-panel-lista">

<div class="toolbar anim" style="animation-delay:.04s">
    <div class="filters-panel">
        <form method="GET" action="{{ route('admin.pagos') }}" class="filter-form">
            <div class="filter-field search-field" style="flex:1;">
                <label>Buscar proveedor</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="Escribe código, nombre o RFC del proveedor…" autofocus>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-primary">Buscar</button>
                @if($filtrosBusqueda)
                    <a href="{{ route('admin.pagos') }}" class="btn-outline">Limpiar</a>
                @endif
            </div>
        </form>
        @if($filtrosBusqueda)
        <div class="active-filters">
            <span>Buscando:</span>
            <span class="active-tag">«{{ $q ?: $codigo }}»</span>
        </div>
        @endif
    </div>
</div>

@if(! $filtrosBusqueda)
    {{-- ── TABLA GLOBAL estilo reporte Wiese: proveedores por consecutivo con sus facturas ──
         POR QUÉ: Brenda pidió ver de entrada la lista de proveedores (ordenados por código) con
         sus facturas pendientes, marcando en ROJO las vencidas. Los datos vienen en vivo del
         endpoint de Wiese (facturas pendientes de todos). Si no hay datos aún (Alan no liberó el
         RFC vacío, o Wiese no respondió), se muestra un aviso y el buscador de arriba sigue útil. --}}
    @if($tablaProveedores->isEmpty())
        <div class="adm-section anim" style="animation-delay:.08s">
            <div class="empty-state" style="padding:40px 20px;text-align:center;">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#6B3FA0" stroke-width="1.5" style="margin-bottom:12px;"><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <h3 style="margin:0 0 6px;color:#374151;">Aún no hay facturas pendientes para mostrar</h3>
                <p style="color:#6b7280;max-width:520px;margin:0 auto;">
                    @if($tablaError)
                        No se pudo traer la lista completa en este momento. Mientras tanto, usa el buscador de arriba
                        para abrir un proveedor específico y ver sus facturas.
                    @else
                        No hay facturas pendientes, o la lista global aún no está disponible. Usa el buscador de arriba
                        para abrir un proveedor específico.
                    @endif
                </p>
            </div>
        </div>
    @else
    <div class="adm-section anim" style="animation-delay:.08s">
        <div class="adm-section-head">
            <div>
                <h4>Proveedores con facturas pendientes</h4>
                <div class="adm-section-meta">Orden alfabético · las facturas <span style="color:#dc2626;font-weight:700;">vencidas salen en rojo</span></div>
            </div>
        </div>
        {{-- Scroll horizontal ARRIBA (barra espejo): una barrita que, al moverla, mueve la tabla
             de abajo. Útil porque la tabla es ancha y así no hay que bajar hasta el final para
             deslizar. El JS al final sincroniza ambas barras. --}}
        <div id="wie-scroll-top" style="overflow-x:auto;overflow-y:hidden;">
            <div id="wie-scroll-top-inner" style="height:1px;"></div>
        </div>
        {{-- min-width fuerza que las columnas no se apachurren: si no caben, el contenedor
             (overflow-x:auto) muestra scroll horizontal como en Excel/Wiese. --}}
        <div class="tbl-wrap" id="wie-scroll-bottom">
            <table class="admin-table" id="wie-tabla" style="min-width:1200px;">
                <thead>
                    <tr>
                        <th style="width:34px;text-align:center;">✓</th>
                        <th>Serie</th>
                        <th>Folio</th>
                        <th>Fecha factura</th>
                        <th>Vence</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:right;">Saldo pendiente</th>
                        <th>Referencia</th>
                        <th style="text-align:right;">Total dólares</th>
                        <th style="text-align:right;">Importe pago</th>
                        <th style="text-align:right;">IVA dólares</th>
                        <th style="text-align:center;">Moneda</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tablaProveedores as $codigoProv => $facturas)
                        @php
                            $primera = $facturas->first();
                            $nombreProv = $primera['nombre'] ?? $codigoProv;
                        @endphp
                        {{-- Separador de proveedor. Trae un "seleccionar todas" de ESTE proveedor.
                             Ya NO hay botón por proveedor: se marca de varios proveedores a la vez
                             y se envían todos juntos con el botón verde global de abajo. --}}
                        <tr class="date-row">
                            <td colspan="12">
                                <input type="checkbox" class="fp-chk-all" data-prov="{{ $codigoProv }}" title="Seleccionar todas de este proveedor" style="margin-right:6px;vertical-align:middle;">
                                <a class="code-link" href="{{ route('admin.pagos.proveedor', $codigoProv) }}">{{ $codigoProv }}</a>
                                — {{ $nombreProv }}
                                <span style="font-weight:500;opacity:.75;">· {{ $facturas->count() }} factura(s)</span>
                            </td>
                        </tr>
                        {{-- Una fila por factura del proveedor. --}}
                        @foreach($facturas as $f)
                            @php
                                // Vencida: la fecha de vencimiento ya pasó. (No usamos vencido8/15 del
                                // endpoint: Wiese los manda en 0, por eso se quitaron de la tabla.)
                                $vence = ! empty($f['fecha_vence']) ? \Illuminate\Support\Carbon::parse($f['fecha_vence']) : null;
                                $vencida = $vence && $vence->isPast();
                                $esUsd = ($f['moneda'] ?? 'MXN') === 'USD';
                            @endphp
                            <tr>
                                {{-- Checkbox de factura. name = seleccion[CODIGO][] para que el
                                     backend reciba un arreglo de folios AGRUPADO por proveedor.
                                     Pertenece al form global #fp-form-multi (atributo form=). --}}
                                <td style="text-align:center;">
                                    <input type="checkbox" class="fp-chk" form="fp-form-multi" data-prov="{{ $codigoProv }}" name="seleccion[{{ $codigoProv }}][]" value="{{ $f['folio'] ?? '' }}">
                                </td>
                                <td>{{ $f['serie'] ?? '' }}</td>
                                <td style="font-weight:600;">{{ $f['folio'] ?? '—' }}</td>
                                <td>{{ ! empty($f['fecha_factura']) ? \Illuminate\Support\Carbon::parse($f['fecha_factura'])->format('d/m/Y') : '—' }}</td>
                                {{-- SOLO esta columna va en rojo cuando la factura está vencida. --}}
                                <td style="white-space:nowrap;{{ $vencida ? 'color:#dc2626;font-weight:700;' : '' }}">
                                    {{ $vence ? $vence->format('d/m/Y') : '—' }}
                                    @if($vencida)<span style="font-size:10px;font-weight:700;"> · VENCIDA</span>@endif
                                </td>
                                <td style="text-align:right;white-space:nowrap;">${{ number_format((float) ($f['total'] ?? 0), 2) }}</td>
                                <td style="text-align:right;font-weight:700;white-space:nowrap;">${{ number_format((float) ($f['saldo'] ?? 0), 2) }}</td>
                                <td style="color:#6b7280;">{{ $f['referencia'] ?? '' }}</td>
                                <td style="text-align:right;white-space:nowrap;">${{ number_format((float) ($f['saldo_dlls'] ?? 0), 2) }}</td>
                                <td style="text-align:right;white-space:nowrap;">${{ number_format((float) ($f['importe_pago'] ?? 0), 2) }}</td>
                                <td style="text-align:right;white-space:nowrap;">${{ number_format((float) ($f['iva_dlls'] ?? 0), 2) }}</td>
                                <td style="text-align:center;">
                                    @if($esUsd)<span class="mon-badge mon-usd" style="background:#dbeafe;color:#1d4ed8;">USD</span>@else<span class="mon-badge mon-mxn">MXN</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Form global: recoge TODOS los checkboxes marcados (de cualquier proveedor) por el
             atributo form=. El name seleccion[CODIGO][] agrupa los folios por proveedor, que es
             justo lo que espera carritoAgregarMulti. --}}
        <form method="POST" action="{{ route('admin.pagos.carrito.agregar-multi') }}" id="fp-form-multi">
            @csrf
        </form>

        {{-- Barra flotante con el botón VERDE global. Aparece solo cuando hay algo marcado.
             POR QUÉ flotante: la tabla es larga; así el botón siempre está a la vista. --}}
        <div id="fp-barra-carrito" style="display:none;position:sticky;bottom:0;z-index:5;margin-top:12px;
             padding:12px 16px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;
             box-shadow:0 -4px 14px rgba(0,0,0,.08);display:none;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div style="font-weight:600;color:#374151;">
                <span id="fp-sel-fact">0</span> factura(s) de <span id="fp-sel-prov">0</span> proveedor(es) seleccionadas
            </div>
            <button type="submit" form="fp-form-multi" class="fp-btn-carrito-global">
                + Agregar al carrito
            </button>
        </div>
    </div>
    @endif
@else
<div class="adm-section anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Resultados</h4>
            <div class="adm-section-meta">
                {{ number_format($total) }} proveedor{{ $total !== 1 ? 'es' : '' }} que coinciden con «{{ $q ?: $codigo }}»
                @if($proveedoresPendientes->lastPage() > 1) · página {{ $proveedoresPendientes->currentPage() }} de {{ $proveedoresPendientes->lastPage() }} @endif
            </div>
        </div>
    </div>

    @if($proveedoresPendientes->isEmpty())
        <div class="empty-state">
            <p>No se encontró ningún proveedor con «{{ $q ?: $codigo }}». Revisa el código, nombre o RFC.</p>
        </div>
    @else
        <div class="tbl-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Proveedor</th>
                        <th style="text-align:center;">Moneda</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proveedoresPendientes as $row)
                        @php $esUsd = ($row->moneda ?? 'MXN') === 'USD'; @endphp
                        <tr class="prov-row" onclick="window.location='{{ route('admin.pagos.proveedor', $row->codigo) }}'"
                            style="cursor:pointer;@if($esUsd)background:linear-gradient(90deg,#eff6ff 0%,#f8fbff 60%,#ffffff 100%);@endif">
                            <td>
                                <a class="code-link" href="{{ route('admin.pagos.proveedor', $row->codigo) }}" onclick="event.stopPropagation()" style="{{ $esUsd ? 'color:#1d4ed8;' : '' }}">{{ $row->codigo }}</a>
                            </td>
                            <td style="font-weight:600;">{{ $row->nombre }}</td>
                            <td style="text-align:center;">
                                @if($esUsd)
                                    <span class="mon-badge mon-usd" style="background:#dbeafe;color:#1d4ed8;">USD</span>
                                @else
                                    <span class="mon-badge mon-mxn">MXN</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación: 50 por página. appends conserva la búsqueda al cambiar de página. --}}
        @if($proveedoresPendientes->hasPages())
            <div class="pagination-wrap">{{ $proveedoresPendientes->appends(request()->query())->links() }}</div>
        @endif
    @endif
</div>
@endif

</div>{{-- /fp-panel-lista --}}

{{-- ── PANEL 2: Formatos para pago YA creados ──
     POR QUÉ: dirección pidió ver los formatos generados, del más nuevo arriba, con su estatus.
     Se agrupan por fecha de creación con el separador date-row (estilo estándar de nuestras
     tablas). Vive en su propia pestaña. --}}
<div class="fp-panel" id="fp-panel-creados">
<div class="adm-section anim" style="animation-delay:.12s;">
    <div class="adm-section-head">
        <div>
            <h4>Formatos para pago creados</h4>
            <div class="adm-section-meta">Del más reciente al más antiguo · {{ $formatosCreados->count() }} formato(s)</div>
        </div>
    </div>

    @if($formatosCreados->isEmpty())
        <div class="empty-state"><p>Aún no se ha creado ningún formato para pago.</p></div>
    @else
        <div class="tbl-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Formato</th>
                        <th>Departamento</th>
                        <th>Proveedor(es)</th>
                        <th style="text-align:center;">Facturas</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:center;">Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    @php $fechaSep = null; @endphp
                    @foreach($formatosCreados as $fmt)
                        @php
                            $fGrupo = optional($fmt->created_at)->format('Y-m-d');
                            $fLabel = $fmt->created_at
                                ? $fmt->created_at->locale('es')->isoFormat('DD [de] MMMM YYYY')
                                : 'Sin fecha';
                            // Estatus a mostrar: borrador / confirmado + estado de autorización.
                            $estPago = $fmt->estatus; // borrador | confirmado | cancelado
                            $estAut = $fmt->estatus_autorizacion ?? 'pendiente';
                            if ($estPago === 'borrador') { $badgeTxt = 'Borrador'; $badgeBg = '#f3f4f6'; $badgeFg = '#6b7280'; }
                            elseif ($estPago === 'cancelado') { $badgeTxt = 'Cancelado'; $badgeBg = '#fef2f2'; $badgeFg = '#b91c1c'; }
                            elseif ($estAut === 'autorizado') { $badgeTxt = 'Autorizado'; $badgeBg = '#dcfce7'; $badgeFg = '#15803d'; }
                            elseif ($estAut === 'rechazado') { $badgeTxt = 'Rechazado'; $badgeBg = '#fef2f2'; $badgeFg = '#b91c1c'; }
                            else { $badgeTxt = 'Pend. autorizar'; $badgeBg = '#fef3c7'; $badgeFg = '#b45309'; }
                            $numProv = $fmt->es_multi_proveedor
                                ? ($fmt->lineas->pluck('codigo_proveedor')->filter()->unique()->count() ?: 1)
                                : 1;
                        @endphp
                        @if($fGrupo !== $fechaSep)
                            @php $fechaSep = $fGrupo; @endphp
                            <tr class="date-row"><td colspan="6">{{ $fLabel }}</td></tr>
                        @endif
                        <tr>
                            {{-- Folio: FCONA-0040 es el folio contable fijo (lo pide conta), pero
                                 TODOS salían igual y no se distinguían. Se le agrega el nº de lote
                                 (#id, único por formato) + la hora, para reconocer cuál es cuál. --}}
                            <td style="font-weight:600;white-space:nowrap;">
                                FCONA-0040
                                <div style="font-size:11px;font-weight:500;color:#6b7280;">
                                    Lote #{{ $fmt->id }}@if($fmt->created_at) · {{ $fmt->created_at->format('H:i') }}@endif
                                </div>
                            </td>
                            {{-- Departamento: etiqueta legible desde la constante DEPARTAMENTOS;
                                 si el pago no tiene departamento (viejos), muestra "—". --}}
                            <td>{{ \App\Models\PagoProveedor::DEPARTAMENTOS[$fmt->departamento] ?? ($fmt->departamento ?: '—') }}</td>
                            <td>
                                @if($fmt->es_multi_proveedor)
                                    Varios proveedores ({{ $numProv }})
                                @else
                                    {{ $fmt->proveedor->nombre ?? $fmt->codigo_proveedor }}
                                @endif
                            </td>
                            <td style="text-align:center;">{{ $fmt->num_facturas }}</td>
                            <td style="text-align:right;font-weight:700;">${{ number_format((float) $fmt->monto_total, 2) }}</td>
                            <td style="text-align:center;">
                                <span style="font-size:11px;font-weight:700;padding:4px 12px;border-radius:999px;background:{{ $badgeBg }};color:{{ $badgeFg }};white-space:nowrap;">{{ $badgeTxt }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</div>{{-- /fp-panel-creados --}}

{{-- Cambio de pestañas (sin recargar): muestra/oculta los dos paneles. --}}
<script>
(function () {
    var tabs = document.querySelectorAll('.fp-tab');
    var paneles = { lista: document.getElementById('fp-panel-lista'), creados: document.getElementById('fp-panel-creados') };
    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            tabs.forEach(function (b) { b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            var destino = btn.getAttribute('data-fp-tab');
            Object.keys(paneles).forEach(function (k) {
                if (paneles[k]) paneles[k].classList.toggle('is-active', k === destino);
            });
        });
    });
})();
</script>

{{-- Sincroniza la barra de scroll de ARRIBA con la tabla (ancha) de abajo: mover una mueve la otra.
     POR QUÉ: la tabla de proveedores es más ancha que la pantalla; con la barra arriba no hay que
     bajar hasta el final para deslizar a los lados. --}}
<script>
(function () {
    var top = document.getElementById('wie-scroll-top');
    var topInner = document.getElementById('wie-scroll-top-inner');
    var bottom = document.getElementById('wie-scroll-bottom');
    var tabla = document.getElementById('wie-tabla');
    if (!top || !topInner || !bottom || !tabla) return;

    // La barra de arriba necesita un "contenido" tan ancho como la tabla para que aparezca scroll.
    function ajustar() { topInner.style.width = tabla.scrollWidth + 'px'; }
    ajustar();
    window.addEventListener('resize', ajustar);

    // Al mover una barra, se mueve la otra (evita bucle con una bandera simple).
    var sincronizando = false;
    top.addEventListener('scroll', function () {
        if (sincronizando) return; sincronizando = true;
        bottom.scrollLeft = top.scrollLeft; sincronizando = false;
    });
    bottom.addEventListener('scroll', function () {
        if (sincronizando) return; sincronizando = true;
        top.scrollLeft = bottom.scrollLeft; sincronizando = false;
    });
})();
</script>

{{-- Selección de facturas en la tabla global (varios proveedores a la vez).
     POR QUÉ: dirección pidió marcar facturas de distintos proveedores y mandarlas TODAS con un
     solo botón verde, sin entrar al detalle. Aquí contamos lo marcado (facturas y proveedores
     distintos) y mostramos/ocultamos la barra flotante del botón global. --}}
<script>
(function () {
    var barra   = document.getElementById('fp-barra-carrito');
    var spanF   = document.getElementById('fp-sel-fact');
    var spanP   = document.getElementById('fp-sel-prov');
    if (!barra) return;

    function refrescar() {
        var checks = document.querySelectorAll('.fp-chk');
        var facturas = 0;
        var provsSet = {}; // códigos de proveedor distintos con al menos una marcada

        checks.forEach(function (c) {
            if (c.checked) {
                facturas++;
                provsSet[c.getAttribute('data-prov')] = true;
            }
        });
        var proveedores = Object.keys(provsSet).length;

        // Contadores en la barra.
        if (spanF) spanF.textContent = facturas;
        if (spanP) spanP.textContent = proveedores;

        // La barra aparece solo si hay algo marcado.
        barra.style.display = (facturas > 0) ? 'flex' : 'none';

        // Sincronizar cada "seleccionar todas" por proveedor (marcado si todas las suyas lo están).
        document.querySelectorAll('.fp-chk-all').forEach(function (all) {
            var prov = all.getAttribute('data-prov');
            var delProv = document.querySelectorAll('.fp-chk[data-prov="' + CSS.escape(prov) + '"]');
            var marcadas = 0;
            delProv.forEach(function (c) { if (c.checked) marcadas++; });
            all.checked = (delProv.length > 0 && marcadas === delProv.length);
        });
    }

    // Click en una factura → recontar global.
    document.querySelectorAll('.fp-chk').forEach(function (c) {
        c.addEventListener('change', refrescar);
    });

    // "Seleccionar todas" de un proveedor → marca/desmarca las suyas.
    document.querySelectorAll('.fp-chk-all').forEach(function (all) {
        all.addEventListener('change', function () {
            var prov = all.getAttribute('data-prov');
            document.querySelectorAll('.fp-chk[data-prov="' + CSS.escape(prov) + '"]').forEach(function (c) {
                c.checked = all.checked;
            });
            refrescar();
        });
    });

    refrescar();
})();
</script>
@endsection
