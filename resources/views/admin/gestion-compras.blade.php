@extends('layouts.admin')
@section('title', 'Gestión de Compras')
@section('hero')
<div class="hero-band">
    <h1>Gestión de Compras</h1>
    <p>Control de proveedores, forecast, inventario y autorizaciones</p>
</div>
@endsection
@push('styles')
<style>
    .toolbar{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
    .toolbar-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .filter-group{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
    .filter-btn{padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);cursor:pointer;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .filter-btn:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .filter-count{font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;background:rgba(0,0,0,.08);line-height:1.2}
    .filter-btn.active .filter-count{background:rgba(255,255,255,.25)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}
    .section-meta{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:12px 18px;margin-bottom:14px;font-size:13px;color:var(--gray-muted)}
    .section-meta strong{color:var(--gray-text);font-weight:600}

    .gc-panel{display:none}.gc-panel.active{display:block}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}

    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.ok,.badge-est.aprobado,.badge-est.pagada{background:var(--green-bg);color:var(--green)}
    .badge-est.warn,.badge-est.pendiente{background:var(--amber-bg);color:var(--amber)}
    .badge-est.err,.badge-est.rechazado,.badge-est.cancelada{background:var(--red-bg);color:var(--red)}
    .badge-est.gray,.badge-est.sin_documento{background:var(--gray-soft);color:var(--gray-muted)}
    .tipo-badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:6px;background:var(--purple-light);color:var(--purple)}

    .dias-badge{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;display:inline-block}
    .dias-ok{background:var(--green-bg);color:var(--green)}
    .dias-warn{background:var(--amber-bg);color:var(--amber)}
    .dias-crit{background:var(--red-bg);color:var(--red)}

    .btn-sm{padding:6px 14px;font-size:12px;font-weight:600;border-radius:8px;border:1.5px solid var(--border);cursor:pointer;font-family:inherit;transition:all .15s;background:var(--white);color:var(--gray-text)}
    .btn-sm.btn-alta{border-color:var(--green);color:var(--green)}.btn-sm.btn-alta:hover{background:var(--green);color:#fff}
    .btn-sm.btn-baja{border-color:var(--red);color:var(--red)}.btn-sm.btn-baja:hover{background:var(--red);color:#fff}
    .btn-sm.btn-auth{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}.btn-sm.btn-auth:hover{background:var(--purple);color:#fff}
    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer}

    .inline-form{display:inline-flex;align-items:center;gap:8px;flex-wrap:wrap}
    .inline-form input{border:1.5px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:inherit;color:var(--gray-text);outline:none;width:100px}
    .inline-form input:focus{border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,.1)}

    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green);font-weight:500}

    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.filter-group{width:100%}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif

@php
    $totalProveedores = \App\Models\ProveedorUser::count();
@endphp

<div class="toolbar">
    <div class="toolbar-top">
        <div class="filter-group">
            <button type="button" class="filter-btn gc-tab-btn active" data-tab="opinion" onclick="switchGC('opinion', this)">
                Opinión positiva <span class="filter-count">{{ count($opinionData) }}</span>
            </button>
            <button type="button" class="filter-btn gc-tab-btn" data-tab="autorizacion" onclick="switchGC('autorizacion', this)">
                Autorización <span class="filter-count">{{ $totalProveedores }}</span>
            </button>
            <button type="button" class="filter-btn gc-tab-btn" data-tab="dias" onclick="switchGC('dias', this)">
                Días inventario <span class="filter-count">{{ count($inventarioDias) }}</span>
            </button>
            <button type="button" class="filter-btn gc-tab-btn" data-tab="costos" onclick="switchGC('costos', this)">
                Autorizar costos <span class="filter-count">{{ count($productos) }}</span>
            </button>
        </div>
        <span class="badge-count" id="gc-panel-count">{{ count($opinionData) }} registros</span>
    </div>
</div>

{{-- ═══ OPINIÓN POSITIVA ═══ --}}
<div class="gc-panel active" id="panel-opinion" data-count="{{ count($opinionData) }} registros">
    <div class="section-meta" style="display:flex;justify-content:space-between;align-items:center">
        <span><strong>Opinión de cumplimiento SAT</strong> — Pendientes y estado · Correo automático el día 1 de cada mes</span>
        <a href="{{ route('admin.export-opinion') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:11px;font-weight:600;color:#fff;background:#059669;border-radius:6px;text-decoration:none">Exportar Excel</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proveedor</th>
                    <th>Estado</th>
                    <th>Correo</th>
                </tr>
            </thead>
            <tbody>
            @foreach($opinionData as $op)
                @php
                    $est = $op['estatus'];
                    $cls = match($est) { 'aprobado'=>'ok', 'pendiente'=>'warn', 'rechazado'=>'err', default=>'gray' };
                    $lbl = match($est) { 'aprobado'=>'Positiva', 'pendiente'=>'En revisión', 'rechazado'=>'Negativa', default=>'Sin documento' };
                @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $op['proveedor']->codigo_compras ?? '—' }}</td>
                    <td style="font-weight:600">{{ $op['proveedor']->nombre ?? $op['proveedor']->usuario }}</td>
                    <td><span class="badge-est {{ $cls }}">{{ $lbl }}</span></td>
                    <td style="font-size:12px;color:var(--gray-muted)">
                        @if($est === 'aprobado')
                            <span style="color:var(--green);font-weight:600">✓ Al día</span>
                        @else
                            Se notifica automáticamente
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ AUTORIZACIÓN PROVEEDORES ═══ --}}
<div class="gc-panel" id="panel-autorizacion" data-count="{{ $totalProveedores }} proveedores">
    <div class="section-meta" style="display:flex;justify-content:space-between;align-items:center">
        <span><strong>Autorización de proveedores</strong> — Dirección autoriza alta o baja para compras</span>
        <a href="{{ route('admin.export-autorizacion') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:11px;font-weight:600;color:#fff;background:#059669;border-radius:6px;text-decoration:none">Exportar Excel</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proveedor</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            @foreach(\App\Models\ProveedorUser::orderBy('nombre')->get() as $prov)
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $prov->codigo_compras ?? '—' }}</td>
                    <td style="font-weight:600">{{ $prov->nombre ?? $prov->usuario }}</td>
                    <td><span class="badge-est {{ $prov->activo ? 'ok' : 'err' }}">{{ $prov->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.autorizar-proveedor') }}" class="inline-form">
                            @csrf
                            <input type="hidden" name="proveedor_id" value="{{ $prov->id }}">
                            @if($prov->activo)
                                <input type="hidden" name="accion" value="baja">
                                <button type="submit" class="btn-sm btn-baja" onclick="return confirm('¿Dar de baja a {{ $prov->nombre ?? $prov->usuario }}?')">Dar de baja</button>
                            @else
                                <input type="hidden" name="accion" value="alta">
                                <button type="submit" class="btn-sm btn-alta">Dar de alta</button>
                            @endif
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ DÍAS DE INVENTARIO ═══ --}}
<div class="gc-panel" id="panel-dias" data-count="{{ count($inventarioDias) }} productos">
    <div class="section-meta" style="display:flex;justify-content:space-between;align-items:center">
        <span><strong>Días de inventario por artículo</strong> — Días de pedido y entrega</span>
        <a href="{{ route('admin.export-dias-inventario') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:11px;font-weight:600;color:#fff;background:#059669;border-radius:6px;text-decoration:none">Exportar Excel</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Stock</th>
                    <th>Días inventario</th>
                    <th>Días pedido</th>
                    <th>Días entrega</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            @foreach($inventarioDias as $item)
                @php
                    $dias = $item['dias_inventario'];
                    $cls = $dias >= 30 ? 'dias-ok' : ($dias >= 15 ? 'dias-warn' : 'dias-crit');
                @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $item['producto']->codigo }}</td>
                    <td>{{ $item['producto']->nombre }}</td>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">{{ number_format($item['producto']->stock) }}</td>
                    <td><span class="dias-badge {{ $cls }}">{{ $dias }} días</span></td>
                    <td>{{ $item['dias_pedido'] }} días</td>
                    <td>{{ $item['dias_entrega'] }} días</td>
                    <td>
                        @if($dias < $item['dias_pedido'] + $item['dias_entrega'])
                            <span class="badge-est err">Reordenar</span>
                        @else
                            <span class="badge-est ok">OK</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ AUTORIZAR COSTOS ═══ --}}
<div class="gc-panel" id="panel-costos" data-count="{{ count($productos) }} productos">
    <div class="section-meta" style="display:flex;justify-content:space-between;align-items:center">
        <span><strong>Autorizar actualización de costo</strong> — Aprobación por dirección</span>
        <a href="{{ route('admin.export-costos') }}" style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:11px;font-weight:600;color:#fff;background:#059669;border-radius:6px;text-decoration:none">Exportar Excel</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Precio actual</th>
                    <th>Nuevo precio</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            @foreach($productos as $prod)
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $prod->codigo }}</td>
                    <td>{{ $prod->nombre }}</td>
                    <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($prod->precio, 2) }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.autorizar-costo') }}" class="inline-form" id="form-costo-{{ $prod->id }}">
                            @csrf
                            <input type="hidden" name="producto_id" value="{{ $prod->id }}">
                            <input type="number" name="nuevo_precio" step="0.01" min="0" placeholder="0.00">
                    </td>
                    <td>
                            <button type="submit" class="btn-sm btn-auth">Autorizar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
@push('scripts')
<script>
function switchGC(tab, el) {
    document.querySelectorAll('.gc-tab-btn').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.gc-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    if (el) el.classList.add('active');
    const panel = document.getElementById('panel-' + tab);
    const countEl = document.getElementById('gc-panel-count');
    if (panel && countEl && panel.dataset.count) {
        countEl.textContent = panel.dataset.count;
    }
}
</script>
@endpush
