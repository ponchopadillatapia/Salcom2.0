@extends('layouts.admin')
@section('title', 'Proveedores')
@section('hero')
<div class="hero-band">
    <h1>Proveedores</h1>
    <p>Gestión de proveedores, órdenes de compra y facturas</p>
</div>
@endsection
@push('styles')
<style>
    .prov-tabs{display:flex;gap:4px;background:var(--gray-soft);border-radius:12px;padding:4px;margin-bottom:20px;width:fit-content}
    .prov-tab{padding:10px 22px;font-size:13px;font-weight:600;color:var(--gray-muted);cursor:pointer;border:none;background:none;border-radius:10px;font-family:inherit;transition:all .2s}
    .prov-tab:hover{color:var(--purple);background:rgba(107,63,160,.06)}
    .prov-tab.active{color:var(--purple);background:var(--white);box-shadow:0 1px 4px rgba(0,0,0,.06)}
    .prov-panel{display:none}.prov-panel.active{display:block}

    .toolbar{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .filter-form{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;flex:1}
    .filter-field{display:flex;flex-direction:column;gap:4px;min-width:130px}
    .filter-field label{font-size:11px;font-weight:600;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.4px}
    .filter-field input,.filter-field select{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;background:var(--white)}
    .filter-field input:focus,.filter-field select:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}
    .filter-actions{display:flex;gap:8px;align-items:center;padding-bottom:1px}
    .btn-filtrar{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}
    .btn-filtrar:hover{background:var(--purple-dark)}
    .btn-limpiar{padding:9px 14px;background:var(--white);color:var(--gray-muted);border:1.5px solid var(--border);border-radius:8px;text-decoration:none;font-size:13px;font-family:inherit;font-weight:600;display:inline-flex;align-items:center}
    .btn-limpiar:hover{color:var(--purple);border-color:var(--purple)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap;padding-bottom:10px}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tr:hover td{background:var(--purple-subtle)}

    .score-bar{width:80px;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
    .score-fill{height:100%;border-radius:4px}
    .score-high .score-fill{background:var(--green)}
    .score-mid .score-fill{background:var(--amber)}
    .score-low .score-fill{background:var(--red)}

    .pct-cell{display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap}
    .pct-val{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}

    .badge-activo{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-activo.si{background:var(--green-bg);color:var(--green)}
    .badge-activo.no{background:var(--red-bg);color:var(--red)}

    .badge-estatus{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block;text-transform:capitalize}
    .badge-estatus.validacion{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.procesando{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.enviado{background:#ede9fe;color:#7c3aed}
    .badge-estatus.entregado{background:var(--green-bg);color:var(--green)}
    .badge-estatus.cancelado{background:var(--red-bg);color:var(--red)}
    .badge-estatus.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-estatus.pagada{background:var(--green-bg);color:var(--green)}
    .badge-estatus.aprobada{background:var(--green-bg);color:var(--green)}
    .badge-estatus.en_proceso{background:var(--blue-bg);color:var(--blue)}
    .badge-estatus.completada{background:#ede9fe;color:#7c3aed}

    .badge-vencida{font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--red-bg);color:var(--red);margin-left:6px}

    .btn-eliminar{padding:5px 14px;font-size:12px;font-weight:600;border:1px solid var(--red);border-radius:6px;background:var(--red);color:#fff;cursor:pointer;font-family:inherit;transition:all .15s}
    .btn-eliminar:hover{background:#b91c1c;border-color:#b91c1c}

    .pagination-wrap{padding:16px;display:flex;justify-content:center}
    .empty-state{text-align:center;padding:48px 20px;color:var(--gray-muted)}
    .empty-state p{font-size:14px;font-weight:500}
    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green)}

    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.toolbar{flex-direction:column;align-items:stretch}.filter-form{width:100%}.filter-field{min-width:100%}.prov-tabs{width:100%;overflow-x:auto}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif

<div class="prov-tabs">
    <button type="button" class="prov-tab {{ ($tabActiva ?? 'proveedores') === 'proveedores' ? 'active' : '' }}" data-tab="proveedores" onclick="switchProvTab('proveedores', this)">Proveedores ({{ $proveedores->total() }})</button>
    <button type="button" class="prov-tab {{ ($tabActiva ?? '') === 'forecast' ? 'active' : '' }}" data-tab="forecast" onclick="switchProvTab('forecast', this)">Forecast</button>
    <button type="button" class="prov-tab {{ ($tabActiva ?? '') === 'ordenes' ? 'active' : '' }}" data-tab="ordenes" onclick="switchProvTab('ordenes', this)">Órdenes de Compra ({{ $ordenes->count() }})</button>
    <button type="button" class="prov-tab {{ ($tabActiva ?? '') === 'facturas' ? 'active' : '' }}" data-tab="facturas" onclick="switchProvTab('facturas', this)">Facturas pendientes ({{ $facturasPendientes->count() }})</button>
</div>

{{-- ═══ TAB PROVEEDORES ═══ --}}
<div class="prov-panel {{ ($tabActiva ?? 'proveedores') === 'proveedores' ? 'active' : '' }}" id="panel-proveedores">
    <div class="toolbar">
        <form method="GET" action="{{ route('admin.proveedores') }}" class="filter-form">
            <input type="hidden" name="tab" value="proveedores">
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveOc])
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveFact])
            <div class="filter-field"><label>Nombre</label><input type="text" name="f_nombre" value="{{ $filtrosProv['nombre'] ?? '' }}" placeholder="Nombre del proveedor"></div>
            <div class="filter-field"><label>Código</label><input type="text" name="f_codigo" value="{{ $filtrosProv['codigo'] ?? '' }}" placeholder="Código compras"></div>
            <div class="filter-field"><label>Correo</label><input type="text" name="f_correo" value="{{ $filtrosProv['correo'] ?? '' }}" placeholder="correo@…"></div>
            <div class="filter-field"><label>Activo</label>
                <select name="f_activo">
                    <option value="">Todos</option>
                    <option value="1" {{ ($filtrosProv['activo'] ?? '') === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ ($filtrosProv['activo'] ?? '') === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filtrar">Filtrar</button>
                @if($filtrosProvActivos)<a href="{{ route('admin.proveedores', array_merge(['tab' => 'proveedores'], $preserveOc, $preserveFact)) }}" class="btn-limpiar">Limpiar</a>@endif
            </div>
        </form>
        <span class="badge-count">{{ $proveedores->total() }} proveedor{{ $proveedores->total() !== 1 ? 'es' : '' }}</span>
    </div>
    <div class="admin-table-wrap">
    @if($proveedores->count())
        <table class="admin-table">
            <thead><tr><th>Código</th><th>Nombre</th><th>Correo</th><th>OTIF</th><th>Entrega</th><th>Puntualidad</th><th>Activo</th><th>Acción</th></tr></thead>
            <tbody>
            @foreach($proveedores as $p)
                @php $m = $metricasProveedores[$p->id] ?? []; @endphp
                <tr>
                    <td style="font-weight:600;color:var(--purple)">{{ $p->codigo_compras ?? '—' }}</td>
                    <td>{{ $p->nombre ?? '—' }}</td>
                    <td>{{ $p->correo ?? '—' }}</td>
                    <td>
                        <div class="pct-cell">
                            <div class="score-bar {{ $m['score_class'] ?? 'score-low' }}"><div class="score-fill" style="width:{{ $p->score_total }}%"></div></div>
                            <span class="pct-val"><strong>{{ number_format($p->score_total, 0) }}%</strong>@include('partials.trend-arrow', ['value' => $m['trend_otif'] ?? 0, 'size' => '11'])</span>
                        </div>
                    </td>
                    <td><span class="pct-val">{{ number_format($p->score_entrega, 0) }}%@include('partials.trend-arrow', ['value' => $m['trend_entrega'] ?? 0, 'size' => '11'])</span></td>
                    <td><span class="pct-val">{{ number_format($p->score_puntualidad, 0) }}%@include('partials.trend-arrow', ['value' => $m['trend_puntualidad'] ?? 0, 'size' => '11'])</span></td>
                    <td><span class="badge-activo {{ $p->activo ? 'si' : 'no' }}">{{ $p->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.proveedores.eliminar', $p) }}" onsubmit="return confirm('¿Eliminar a {{ addslashes($p->nombre ?? $p->usuario) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-eliminar">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if($proveedores->hasPages())<div class="pagination-wrap">{{ $proveedores->links() }}</div>@endif
    @else
        <div class="empty-state"><p>No se encontraron proveedores{{ $filtrosProvActivos ? ' con esos filtros' : '' }}</p></div>
    @endif
    </div>
</div>

{{-- ═══ TAB FORECAST ═══ --}}
<div class="prov-panel {{ ($tabActiva ?? '') === 'forecast' ? 'active' : '' }}" id="panel-forecast">
    <div class="toolbar">
        <form method="GET" action="{{ route('admin.proveedores') }}" class="filter-form">
            <input type="hidden" name="tab" value="forecast">
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveOc])
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveFact])
            <div class="filter-field"><label>Nombre</label><input type="text" name="f_nombre" value="{{ $filtrosProv['nombre'] ?? '' }}" placeholder="Nombre del proveedor"></div>
            <div class="filter-field"><label>Código</label><input type="text" name="f_codigo" value="{{ $filtrosProv['codigo'] ?? '' }}" placeholder="Código compras"></div>
            <div class="filter-field"><label>Activo</label>
                <select name="f_activo">
                    <option value="">Todos</option>
                    <option value="1" {{ ($filtrosProv['activo'] ?? '') === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ ($filtrosProv['activo'] ?? '') === '0' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filtrar">Filtrar</button>
                @php
                    $filtrosForecastActivos = ($filtrosProv['nombre'] ?? '') !== '' || ($filtrosProv['codigo'] ?? '') !== '' || ($filtrosProv['activo'] ?? '') !== '';
                @endphp
                @if($filtrosForecastActivos)<a href="{{ route('admin.proveedores', array_merge(['tab' => 'forecast'], $preserveOc, $preserveFact)) }}" class="btn-limpiar">Limpiar</a>@endif
            </div>
        </form>
        <span class="badge-count">{{ $proveedores->total() }} proveedor{{ $proveedores->total() !== 1 ? 'es' : '' }}</span>
    </div>
    <div class="admin-table-wrap">
    @if($proveedores->count())
        <table class="admin-table">
            <thead><tr><th>Código</th><th>Proveedor</th><th>OTIF</th><th>Forecast %</th><th>Compras último trimestre</th><th>Estimado próximo mes</th></tr></thead>
            <tbody>
            @foreach($proveedores as $p)
                @php
                    $m = $metricasProveedores[$p->id] ?? [];
                    $forecast = $m['forecast'] ?? min(100, max(0, $p->score_total * 1.1));
                    $comprasTrim = $m['compras_trim'] ?? 0;
                    $estimado = $m['estimado'] ?? 0;
                @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $p->codigo_compras ?? '—' }}</td>
                    <td style="font-weight:600">{{ $p->nombre ?? $p->usuario }}</td>
                    <td><span class="pct-val">{{ number_format($p->score_total, 0) }}%@include('partials.trend-arrow', ['value' => $m['trend_otif'] ?? 0, 'size' => '11'])</span></td>
                    <td>
                        <div class="pct-cell">
                            <div class="score-bar {{ $m['forecast_class'] ?? 'score-low' }}"><div class="score-fill" style="width:{{ $forecast }}%"></div></div>
                            <span class="pct-val"><strong>{{ number_format($forecast, 0) }}%</strong>@include('partials.trend-arrow', ['value' => $m['trend_forecast'] ?? 0, 'size' => '11'])</span>
                        </div>
                    </td>
                    <td style="font-variant-numeric:tabular-nums"><span class="pct-val">${{ number_format($comprasTrim, 2) }}@include('partials.trend-arrow', ['value' => $m['trend_compras'] ?? 0, 'size' => '11'])</span></td>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($estimado, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @if($proveedores->hasPages())<div class="pagination-wrap">{{ $proveedores->links() }}</div>@endif
    @else
        <div class="empty-state"><p>No se encontraron proveedores{{ $filtrosProvActivos ? ' con esos filtros' : '' }}</p></div>
    @endif
    </div>
</div>

{{-- ═══ TAB ÓRDENES DE COMPRA ═══ --}}
<div class="prov-panel {{ ($tabActiva ?? '') === 'ordenes' ? 'active' : '' }}" id="panel-ordenes">
    <div class="toolbar">
        <form method="GET" action="{{ route('admin.proveedores') }}" class="filter-form">
            <input type="hidden" name="tab" value="ordenes">
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveProv])
            @include('partials.prov-admin-preserve-filters', ['preserve' => $preserveFact])
            <div class="filter-field"><label>Proveedor</label><input type="text" name="f_oc_proveedor" value="{{ $filtrosOc['proveedor'] ?? '' }}" placeholder="Nombre o código"></div>
            <div class="filter-field"><label>OC #</label><input type="text" name="f_oc_numero" value="{{ $filtrosOc['numero'] ?? '' }}" placeholder="Ej. 12"></div>
            <div class="filter-field"><label>Producto</label><input type="text" name="f_oc_producto" value="{{ $filtrosOc['producto'] ?? '' }}" placeholder="Nombre producto"></div>
            <div class="filter-field"><label>Status</label>
                <select name="f_oc_estatus">
                    <option value="">Todos</option>
                    @foreach($estatusOc as $est)
                    <option value="{{ $est }}" {{ ($filtrosOc['estatus'] ?? '') === $est ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $est)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field"><label>Fecha desde</label><input type="date" name="f_oc_fecha_desde" value="{{ $filtrosOc['fecha_desde'] ?? '' }}"></div>
            <div class="filter-field"><label>Fecha hasta</label><input type="date" name="f_oc_fecha_hasta" value="{{ $filtrosOc['fecha_hasta'] ?? '' }}"></div>
            <div class="filter-field"><label>Vencida</label>
                <select name="f_oc_vencida">
                    <option value="">Todas</option>
                    <option value="1" {{ ($filtrosOc['vencida'] ?? '') === '1' ? 'selected' : '' }}>Solo vencidas</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn-filtrar">Filtrar</button>
                @if($filtrosOcActivos)<a href="{{ route('admin.proveedores', array_merge(['tab' => 'ordenes'], $preserveProv, $preserveFact)) }}" class="btn-limpiar">Limpiar</a>@endif
            </div>
        </form>
        <span class="badge-count">{{ $ordenes->count() }} orden{{ $ordenes->count() !== 1 ? 'es' : '' }}</span>
    </div>
    <div class="admin-table-wrap">
    @if($ordenes->count())
        <table class="admin-table">
            <thead><tr><th>Proveedor</th><th>Orden de compra</th><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>Status</th><th>Vencimiento</th></tr></thead>
            <tbody>
            @foreach($ordenes as $o)
                @php
                    $lineasOc = $o->productos ?? [];
                    $proveedorNombre = $o->proveedor?->nombre ?? $o->proveedor?->usuario ?? '—';
                    $vencimiento = $o->created_at->copy()->addDays(30);
                    $vencida = $o->estatus !== 'completada' && $vencimiento->isPast();
                @endphp
                @forelse($lineasOc as $prod)
                <tr>
                    <td style="font-weight:600">{{ $proveedorNombre }}</td>
                    <td style="font-weight:700;color:var(--purple)">#{{ $o->id }}</td>
                    <td>{{ $o->created_at->format('d/m/Y') }}</td>
                    <td>{{ $prod['nombre'] ?? '—' }}</td>
                    <td style="font-variant-numeric:tabular-nums">{{ number_format($prod['cantidad'] ?? 0) }} {{ $prod['unidad'] ?? '' }}</td>
                    <td><span class="badge-estatus {{ $o->estatus }}">{{ ucfirst(str_replace('_', ' ', $o->estatus)) }}</span>@if($vencida)<span class="badge-vencida">VENCIDA</span>@endif</td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">{{ $vencimiento->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td style="font-weight:600">{{ $proveedorNombre }}</td>
                    <td style="font-weight:700;color:var(--purple)">#{{ $o->id }}</td>
                    <td>{{ $o->created_at->format('d/m/Y') }}</td>
                    <td>—</td>
                    <td>—</td>
                    <td><span class="badge-estatus {{ $o->estatus }}">{{ ucfirst(str_replace('_', ' ', $o->estatus)) }}</span>@if($vencida)<span class="badge-vencida">VENCIDA</span>@endif</td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">{{ $vencimiento->format('d/m/Y') }}</td>
                </tr>
                @endforelse
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state"><p>No hay órdenes de compra{{ $filtrosOcActivos ? ' con esos filtros' : ' registradas' }}</p></div>
    @endif
    </div>
</div>

{{-- ═══ TAB FACTURAS PENDIENTES ═══ --}}
<div class="prov-panel {{ ($tabActiva ?? '') === 'facturas' ? 'active' : '' }}" id="panel-facturas">
    <div class="toolbar" style="justify-content:space-between">
        <span class="badge-count">{{ $facturasPendientes->count() }} factura{{ $facturasPendientes->count() !== 1 ? 's' : '' }}</span>
        <a href="{{ route('admin.facturas-pendientes.excel') }}" style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:#fff;background:#059669;border-radius:8px;text-decoration:none">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exportar Excel
        </a>
    </div>
    <div class="admin-table-wrap">
    @if($facturasPendientes->count())
        <table class="admin-table">
            <thead><tr><th>Proveedor</th><th>Folio CFDI</th><th>Total</th><th>Estatus</th><th>Vencimiento</th></tr></thead>
            <tbody>
            @foreach($facturasPendientes as $f)
                @php
                    $vencida = $f->fecha_vencimiento && $f->fecha_vencimiento->isPast();
                    $provF = \App\Models\ProveedorUser::where('codigo_compras', $f->codigo_proveedor)->first();
                    $nombreProv = $provF->nombre ?? $f->codigo_proveedor;
                @endphp
                <tr style="cursor:pointer" onclick="window.location='{{ route('admin.proveedor-facturas', $f->codigo_proveedor) }}'">
                    <td style="font-weight:700;color:var(--purple)">{{ $nombreProv }}</td>
                    <td>{{ $f->folio_cfdi }}</td>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($f->total, 2) }}</td>
                    <td><span class="badge-estatus pendiente">Pendiente</span>@if($vencida)<span class="badge-vencida">VENCIDA</span>@endif</td>
                    <td style="color:{{ $vencida ? 'var(--red)' : 'var(--gray-muted)' }};font-weight:{{ $vencida ? '700' : '400' }}">{{ $f->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state"><p>No hay facturas pendientes</p></div>
    @endif
    </div>
</div>

@endsection
@push('scripts')
<script>
function switchProvTab(tab, btn) {
    document.querySelectorAll('.prov-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.prov-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    (btn || document.querySelector('.prov-tab[data-tab="' + tab + '"]'))?.classList.add('active');
}
document.addEventListener('DOMContentLoaded', function() {
    const tab = @json($tabActiva ?? 'proveedores');
    if (tab && tab !== 'proveedores') switchProvTab(tab);
});
</script>
@endpush
