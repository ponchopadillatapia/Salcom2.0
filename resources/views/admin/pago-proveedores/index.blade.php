@extends('layouts.admin')
@section('title', 'Pago a proveedor')
@section('hero')
<div class="hero-band">
    <h1>Pago a proveedor</h1>
    <p>Registro de pagos realizados · 8969 nacional · 2026 dólar</p>
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

    .filters-panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:16px}
    .filter-form{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:160px;flex:1}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase}
    .filter-field input,.filter-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;background:var(--white)}
    .filter-field input:focus,.filter-field select:focus{outline:none;border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .btn-primary{padding:9px 16px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px}
    .btn-outline{padding:9px 14px;background:#fff;border:1.5px solid var(--border);border-radius:8px;font-weight:600;font-size:13px;text-decoration:none;color:var(--gray-text)}

    .adm-section{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow-sm)}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--white);border-bottom:1px solid var(--border)}
    .admin-table td{padding:14px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tbody tr.prov-row{cursor:pointer}
    .admin-table tbody tr.prov-row:hover td{background:var(--purple-subtle)}
    .admin-table tbody tr.prov-row.is-disabled{cursor:not-allowed;opacity:.45}
    .admin-table tbody tr.prov-row.is-disabled:hover td{background:transparent}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .date-row td{background:var(--purple-subtle)!important;font-weight:700;font-size:12px;color:var(--purple);padding:8px 16px;border-bottom:2px solid var(--purple)}
    .code-link{font-weight:700;color:var(--purple);text-decoration:none}
    .monto{font-weight:700;font-variant-numeric:tabular-nums;color:var(--green)}
    .pill{font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;display:inline-block}
    .pill.ok{background:var(--green-bg);color:var(--green)}
    .pill.warn{background:var(--amber-bg);color:var(--amber)}
    .pill.bad{background:var(--red-bg);color:var(--red)}
    .bubble-roja{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;margin-left:8px;border-radius:999px;background:var(--red);color:#fff;font-size:10px;font-weight:700;vertical-align:middle}
    .hora-bubble{display:inline-flex;align-items:center;justify-content:center;padding:3px 8px;border-radius:999px;background:var(--red);color:#fff;font-size:11px;font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums}
    .hora-bubble.leida{background:var(--gray-muted);opacity:.85}
    .dias-count{font-weight:700;font-variant-numeric:tabular-nums;line-height:1.2;white-space:nowrap}
    .dias-count.warn{color:var(--amber)}
    .dias-count.late{color:var(--red)}
    .dias-sub{font-size:10px;color:var(--gray-muted);margin-top:2px;white-space:nowrap}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500;margin:0}
    .pag-alert{padding:12px 14px;border-radius:10px;margin-bottom:14px;font-size:13px}
    .pag-alert.ok{background:var(--green-bg);color:var(--green);border:1px solid var(--green)}
    .active-filters{font-size:12px;color:var(--gray-muted);display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:12px}
    .active-tag{background:var(--purple-subtle);color:var(--purple);padding:3px 10px;border-radius:999px;font-weight:600;font-size:11px}
    @media(max-width:768px){.inv-metrics{grid-template-columns:1fr 1fr}}
</style>
@endpush
@section('content')
@php
    $chipBase = array_filter([
        'agente' => $agente !== '' ? $agente : null,
    ]);
    $filtrosActivos = $agente !== '' || $estatus !== '';
    $modo = $modo ?? 'proveedores';
    $tiposAgente = $tiposAgente ?? config('polizas_pago');
    $agente = $agente ?? '';
    $q = $q ?? '';
    $poliza = $poliza ?? '';
@endphp

@if(session('ok'))
    <div class="pag-alert ok anim">{{ session('ok') }}</div>
@endif

<div class="inv-metrics anim">
    <a class="inv-metric {{ $estatus === 'cancelado' ? 'is-active' : '' }}" href="{{ route('admin.pago-proveedores', array_merge($chipBase, ['estatus' => 'cancelado'])) }}">
        <div class="accent" style="background:var(--red,#dc2626)"></div>
        <div class="inv-metric-label">Cancelados</div>
        <div class="inv-metric-val">{{ $kpiCancelados }}</div>
        <div class="inv-metric-sub">Abonos cancelados</div>
    </a>
    <a class="inv-metric {{ $estatus === 'pagado' ? 'is-active' : '' }}" href="{{ route('admin.pago-proveedores', array_merge($chipBase, ['estatus' => 'pagado'])) }}">
        <div class="accent" style="background:var(--green,#16a34a)"></div>
        <div class="inv-metric-label">Pagados</div>
        <div class="inv-metric-val">{{ $kpiPagados }}</div>
        <div class="inv-metric-sub">Pagos realizados</div>
    </a>
    <a class="inv-metric {{ $estatus === '' ? 'is-active' : '' }}" href="{{ route('admin.pago-proveedores', $chipBase) }}">
        <div class="accent" style="background:var(--purple,#6B3FA0)"></div>
        <div class="inv-metric-label">Totales</div>
        <div class="inv-metric-val">{{ $kpiTotales }}</div>
        <div class="inv-metric-sub">Todos los abonos</div>
    </a>
    <div class="inv-metric">
        <div class="accent" style="background:#2563eb"></div>
        <div class="inv-metric-label">Facturas pendientes</div>
        <div class="inv-metric-val" style="font-size:20px">${{ number_format((float)$kpiMontoPendiente, 2) }}</div>
        <div class="inv-metric-sub">{{ $kpiFacturasPendientes }} facturas por pagar</div>
    </div>
</div>

@if($estatus === '')
@if($agente === '' && ($modo ?? 'proveedores') !== 'abonos')
<div class="anim" style="animation-delay:.03s;margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:12px;padding:16px 20px;background:linear-gradient(135deg,#f3e8ff,#ede9fe);border:2px solid #a78bfa;border-radius:12px">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
            <div style="font-weight:800;font-size:14px;color:#5b21b6">Selecciona una Cuenta primero</div>
            <div style="font-size:12px;color:#6d28d9;margin-top:2px">Elige la cuenta en el filtro de abajo para poder registrar un pago al proveedor.</div>
        </div>
    </div>
</div>
@endif

<div class="filters-panel anim" style="animation-delay:.04s">
    <form method="get" class="filter-form">
        <div class="filter-field" style="flex:none;min-width:auto">
            <label>Cuenta</label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px">
                @foreach($tiposAgente as $key => $t)
                    @php
                        $isActive = $agente === $key;
                        $color = $t['color'] ?? '#6B3FA0';
                    @endphp
                    <label style="display:flex;align-items:center;gap:6px;padding:10px 16px;border:2px solid {{ $isActive ? $color : '#e5e7eb' }};border-radius:10px;cursor:pointer;background:{{ $isActive ? $color.'12' : '#fff' }};transition:all .15s;font-size:13px;font-weight:{{ $isActive ? '700' : '500' }};color:{{ $isActive ? $color : '#374151' }}">
                        <input type="radio" name="agente" value="{{ $key }}" {{ $isActive ? 'checked' : '' }} style="accent-color:{{ $color }};width:16px;height:16px" onchange="this.form.submit()">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $color }}"></span>
                        {{ $t['titulo'] }}
                    </label>
                @endforeach
                @if($agente !== '')
                    <label style="display:flex;align-items:center;gap:6px;padding:10px 16px;border:2px solid #e5e7eb;border-radius:10px;cursor:pointer;background:#f9fafb;font-size:13px;color:#6b7280">
                        <input type="radio" name="agente" value="" onchange="this.form.submit()" style="width:16px;height:16px">
                        Todas
                    </label>
                @endif
            </div>
        </div>
        <input type="hidden" name="estatus" value="{{ $estatus }}">
    </form>
    {{-- Buscador de proveedor --}}
    <div style="display:flex;gap:12px;margin-top:14px">
        <div style="flex:1">
            <label style="font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;display:block;margin-bottom:4px">Buscar</label>
            <input type="text" id="buscar-proveedor" placeholder="Nombre o código de proveedor..." style="width:100%;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none" oninput="filtrarProveedores()">
        </div>
    </div>
</div>
@else
{{-- Solo buscador cuando hay KPI activo (sin cuentas) --}}
<div class="filters-panel anim" style="animation-delay:.04s">
    <div style="display:flex;gap:12px">
        <div style="flex:1">
            <label style="font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;display:block;margin-bottom:4px">Buscar</label>
            <input type="text" id="buscar-proveedor" placeholder="Nombre o código de proveedor..." style="width:100%;border:1.5px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px;font-family:inherit;outline:none" oninput="filtrarProveedores()">
        </div>
    </div>
</div>
@endif

@if($modo === 'abonos')
    <a href="{{ route('admin.pago-proveedores', ['estatus' => $estatus, 'agente' => $agente]) }}" style="display:inline-flex;margin-bottom:12px;font-size:13px;font-weight:600;color:var(--purple);text-decoration:none">← Volver a proveedores</a>
    <div class="adm-section anim" style="animation-delay:.08s">
        <div class="adm-section-head">
            <div>
                <h4>Pagos{{ $q ? ' — '.$q : '' }}</h4>
                <div class="adm-section-meta">{{ $abonos->total() }} resultado{{ $abonos->total() !== 1 ? 's' : '' }} · estatus {{ $estatus }}</div>
            </div>
        </div>
        <div class="tbl-wrap" style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora alta</th>
                        <th>Factura</th>
                        <th>Cuenta</th>
                        <th>Proveedor</th>
                        <th>Pago</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($abonos as $a)
                    <tr style="cursor:pointer" onclick="window.location='{{ route('admin.pago-proveedores.show', $a) }}'">
                        <td>{{ optional($a->fecha)->format('d/m/Y') }}</td>
                        <td style="font-size:12px;color:var(--gray-muted)">{{ optional($a->created_at)->format('h:i a') }}</td>
                        <td style="font-weight:600">{{ $a->documentos->pluck('folio_doc')->filter()->implode(', ') ?: $a->serie.'-'.$a->folio }}</td>
                        <td>{{ config('polizas_pago.'.$a->poliza_key.'.titulo', $a->agente ?: '—') }}</td>
                        <td>
                            <div style="font-weight:600">{{ $a->nombre_proveedor }}</div>
                            <div style="font-size:11px;color:var(--gray-muted)">{{ $a->codigo_proveedor }}</div>
                        </td>
                        <td class="monto">${{ number_format((float)$a->monto_pago, 2) }}</td>
                        <td>
                            @if(in_array($a->estatus, ['guardado', 'pagado']))
                                <span class="pill ok">Pagado</span>
                            @elseif($a->estatus === 'borrador')
                                <span class="pill warn">Borrador</span>
                            @else
                                <span class="pill bad">Cancelado</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty-state"><p>No hay abonos con ese estatus.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($abonos->hasPages())
            <div style="padding:14px">{{ $abonos->links() }}</div>
        @endif
    </div>
@else
    @php
        $lista = $proveedoresPendientes;
        if ($q !== '') {
            $lista = $lista->filter(fn ($r) => str_contains(mb_strtolower($r->nombre), mb_strtolower($q))
                || str_contains((string) $r->codigo, $q));
        }
        // Ordenar más reciente arriba
        $lista = $lista->sortByDesc(fn ($r) => $r->ultima_factura_at ? $r->ultima_factura_at->timestamp : 0)->values();
        $total = $lista->count();
        $agrupados = $lista->groupBy(function ($row) {
            return $row->ultima_factura_at
                ? $row->ultima_factura_at->format('Y-m-d')
                : 'sin-fecha';
        });
    @endphp

    <div class="adm-section anim" style="animation-delay:.08s">
        <div class="adm-section-head">
            <div>
                <h4>Proveedores</h4>
                <div class="adm-section-meta">{{ $total }} resultado{{ $total !== 1 ? 's' : '' }} · lo más reciente arriba · burbuja roja = sin revisar</div>
            </div>
        </div>

        @if($lista->isEmpty())
            <div class="empty-state">
                <p>No hay proveedores con facturas pendientes{{ $filtrosActivos ? ' para esos filtros' : '' }}.</p>
            </div>
        @else
            <div class="tbl-wrap" style="overflow-x:auto">
                <table class="admin-table" id="tbl-proveedores-abono">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Proveedor</th>
                            <th>Facturas pendientes</th>
                            <th>Monto</th>
                            <th style="text-align:right">Hora alta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($agrupados as $fechaKey => $rows)
                            <tr class="date-row">
                                <td colspan="5">
                                    @if($fechaKey === 'sin-fecha')
                                        Sin fecha
                                    @else
                                        {{ \Illuminate\Support\Carbon::parse($fechaKey)->locale('es')->isoFormat('DD [de] MMMM YYYY') }}
                                    @endif
                                </td>
                            </tr>
                            @foreach($rows as $row)
                                @php
                                    $hora = $row->ultima_factura_at
                                        ? $row->ultima_factura_at->format('h:i a')
                                        : '—';
                                @endphp
                                <tr class="prov-row {{ $agente === '' ? 'is-disabled' : '' }}" data-codigo="{{ $row->codigo }}">
                                    <td>
                                        <a class="code-link js-abrir-abono" href="#" data-codigo="{{ $row->codigo }}">{{ $row->codigo }}</a>
                                    </td>
                                    <td style="font-weight:600;">{{ $row->nombre }}</td>
                                    <td style="text-align:center">{{ $row->num_facturas }}</td>
                                    <td class="monto">${{ number_format((float) $row->monto_total, 2) }}</td>
                                    <td style="text-align:right;white-space:nowrap">
                                        <span class="hora-bubble leida">{{ $hora }}</span>
                                        <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#dc2626;margin-left:8px;vertical-align:middle"></span>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    function agenteKey() {
        var checked = document.querySelector('input[name="agente"]:checked');
        return checked ? (checked.value || '').trim() : '';
    }

    function abrir(codigo) {
        var key = agenteKey();
        var estatus = '{{ $estatus ?? '' }}';

        // Si hay filtro de KPI activo, mostrar abonos de ese proveedor con ese estatus (no necesita cuenta)
        if (estatus !== '') {
            window.location.href = '/admin/pago-proveedores?estatus=' + encodeURIComponent(estatus)
                + '&q=' + encodeURIComponent(codigo)
                + '&ver_abonos=1'
                + (key ? '&agente=' + encodeURIComponent(key) : '');
            return;
        }

        if (!key) {
            alert('Selecciona una Cuenta antes de abrir el proveedor.');
            return;
        }
        window.location.href = '/admin/pago-proveedores/nuevo/' + encodeURIComponent(key)
            + '?codigo=' + encodeURIComponent(codigo);
    }

    // No deshabilitar filas cuando hay KPI activo
    function syncRows() {
        var on = !!agenteKey();
        var estatus = '{{ $estatus ?? '' }}';
        if (estatus !== '') on = true; // KPI activo = siempre clickeable
        document.querySelectorAll('#tbl-proveedores-abono tr.prov-row').forEach(function (tr) {
            tr.classList.toggle('is-disabled', !on);
        });
    }

    // Sync al cargar
    syncRows();

    document.querySelectorAll('#tbl-proveedores-abono tr.prov-row').forEach(function (tr) {
        tr.addEventListener('click', function (e) {
            e.preventDefault();
            abrir(tr.getAttribute('data-codigo'));
        });
    });
    document.querySelectorAll('.js-abrir-abono').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            abrir(a.getAttribute('data-codigo'));
        });
    });
})();

// Filtrar proveedores por nombre o código
function filtrarProveedores() {
    var input = document.getElementById('buscar-proveedor');
    var val = (input.value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#tbl-proveedores-abono tr.prov-row');
    var dateRows = document.querySelectorAll('#tbl-proveedores-abono tr.date-row');

    rows.forEach(function(tr) {
        var codigo = (tr.getAttribute('data-codigo') || '').toLowerCase();
        var nombre = (tr.children[1] ? tr.children[1].textContent : '').toLowerCase();
        var match = val === '' || codigo.indexOf(val) !== -1 || nombre.indexOf(val) !== -1;
        tr.style.display = match ? '' : 'none';
    });

    // Ocultar separadores de fecha si no tienen filas visibles debajo
    dateRows.forEach(function(dr) {
        var next = dr.nextElementSibling;
        var hasVisible = false;
        while (next && !next.classList.contains('date-row')) {
            if (next.classList.contains('prov-row') && next.style.display !== 'none') {
                hasVisible = true;
                break;
            }
            next = next.nextElementSibling;
        }
        dr.style.display = hasVisible ? '' : 'none';
    });
}
</script>
@endpush
