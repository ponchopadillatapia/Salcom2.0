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
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}
    .adm-summary{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:22px 26px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .adm-summary-main{text-align:center;min-width:100px}
    .adm-summary-pct{font-size:42px;font-weight:800;line-height:1;color:var(--purple)}
    .adm-summary-label{font-size:12px;color:var(--gray-muted);margin-top:6px}
    .adm-summary-metrics{flex:1;display:flex;gap:24px;flex-wrap:wrap}
    .adm-metric-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .adm-metric-val{font-size:22px;font-weight:700;display:flex;align-items:center;gap:8px}
    .adm-summary-badge{padding:10px 16px;border-radius:10px;font-size:12px;font-weight:600;line-height:1.4;text-decoration:none;transition:var(--transition)}
    .adm-summary-badge:hover{opacity:.85}
    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}
    .adm-section-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .btn-export{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;font-weight:600;color:var(--green);background:var(--green-bg);border:1px solid var(--green);border-radius:8px;cursor:pointer;text-decoration:none;font-family:inherit;transition:var(--transition)}
    .btn-export:hover{background:var(--green);color:#fff}
    .oc-form-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px}
    .oc-alert{background:var(--red-bg);border:1px solid var(--red);border-radius:10px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px}
    .toolbar{display:flex;flex-direction:column;gap:14px;margin-bottom:20px}
    .toolbar-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .filter-group{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
    .filter-btn{padding:8px 14px;font-size:12px;font-weight:600;border:1.5px solid var(--border);border-radius:8px;background:var(--white);color:var(--gray-text);cursor:pointer;font-family:inherit;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
    .filter-btn:hover{border-color:var(--purple);color:var(--purple);background:var(--purple-subtle)}
    .filter-btn.active{background:var(--purple);color:#fff;border-color:var(--purple)}
    .filter-count{font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;background:rgba(0,0,0,.08);line-height:1.2}
    .filter-btn.active .filter-count{background:rgba(255,255,255,.25)}
    .badge-count{font-size:13px;color:var(--gray-muted);font-weight:500;white-space:nowrap}
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

    @media(max-width:768px){.admin-table-wrap{overflow-x:auto}.filter-group{width:100%}.adm-summary{flex-direction:column;align-items:flex-start}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif

<div class="adm-summary anim">
    <div class="adm-summary-main">
        <div class="adm-summary-pct">{{ $conteoProveedoresActivos }}</div>
        <div class="adm-summary-label">Proveedores activos</div>
    </div>
    <div class="adm-summary-metrics">
        <div>
            <div class="adm-metric-label">Opinión positiva al día</div>
            <div class="adm-metric-val" style="color:var(--green)">{{ $conteoOpinionOk }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Opinión pendiente</div>
            <div class="adm-metric-val" style="color:{{ $conteoOpinionPendiente > 0 ? 'var(--amber)' : 'var(--green)' }}">{{ $conteoOpinionPendiente }}</div>
        </div>
        <div>
            <div class="adm-metric-label">Inventario crítico</div>
            <div class="adm-metric-val" style="color:{{ $conteoInventarioCritico > 0 ? 'var(--red)' : 'var(--green)' }}">{{ $conteoInventarioCritico }}</div>
        </div>
        <div>
            <div class="adm-metric-label">OC atrasadas</div>
            <div class="adm-metric-val" style="color:{{ $conteoOcAtrasadas > 0 ? 'var(--red)' : 'var(--green)' }}">{{ $conteoOcAtrasadas }}</div>
        </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
        <div class="adm-summary-badge" style="background:var(--purple-subtle);color:var(--purple)">
            {{ count($productos) }} productos · {{ count($ocProveedores) }} OC en seguimiento
        </div>
        <a href="{{ route('admin.proveedores', ['tab' => 'ordenes']) }}" class="adm-summary-badge" style="background:var(--green-bg);color:var(--green)">Ver órdenes en Proveedores →</a>
    </div>
</div>

<div class="toolbar anim" style="animation-delay:.04s">
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
            <button type="button" class="filter-btn gc-tab-btn" data-tab="oc-proveedores" onclick="switchGC('oc-proveedores', this)">
                OC Proveedores <span class="filter-count">{{ count($ocProveedores) }}</span>
            </button>
        </div>
        <span class="badge-count" id="gc-panel-count">{{ count($opinionData) }} registros</span>
    </div>
</div>

{{-- ═══ OPINIÓN POSITIVA ═══ --}}
<div class="gc-panel active" id="panel-opinion" data-count="{{ count($opinionData) }} registros">
    <div class="admin-table-wrap">
        <div class="adm-section-head">
            <div>
                <h4>Opinión de cumplimiento SAT</h4>
                <div class="adm-section-meta">Pendientes y estado · correo automático el día 1 de cada mes</div>
            </div>
            <div class="adm-section-toolbar">
                <a href="{{ route('admin.export-opinion') }}" class="btn-export">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar Excel
                </a>
            </div>
        </div>
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
    <div class="admin-table-wrap">
        <div class="adm-section-head">
            <div>
                <h4>Autorización de proveedores</h4>
                <div class="adm-section-meta">Dirección autoriza alta o baja para compras</div>
            </div>
            <div class="adm-section-toolbar">
                <a href="{{ route('admin.export-autorizacion') }}" class="btn-export">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar Excel
                </a>
            </div>
        </div>
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
    <div class="admin-table-wrap">
        <div class="adm-section-head">
            <div>
                <h4>Días de inventario por artículo</h4>
                <div class="adm-section-meta">Días de pedido y entrega · {{ $conteoInventarioCritico }} requieren reorden</div>
            </div>
            <div class="adm-section-toolbar">
                <a href="{{ route('admin.export-dias-inventario') }}" class="btn-export">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar Excel
                </a>
            </div>
        </div>
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
    <div class="admin-table-wrap">
        <div class="adm-section-head">
            <div>
                <h4>Autorizar actualización de costo</h4>
                <div class="adm-section-meta">Aprobación de nuevos precios por dirección</div>
            </div>
            <div class="adm-section-toolbar">
                <a href="{{ route('admin.export-costos') }}" class="btn-export">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Exportar Excel
                </a>
            </div>
        </div>
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

{{-- ═══ OC PROVEEDORES — Seguimiento de entregas ═══ --}}
<div class="gc-panel" id="panel-oc-proveedores" data-count="{{ count($ocProveedores) }} órdenes">

    <div class="oc-form-card">
        <h4 style="font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:14px;">Generar nueva Orden de Compra</h4>
        <form method="POST" action="{{ route('admin.crear-oc') }}" id="formCrearOC">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--gray-muted);display:block;margin-bottom:4px;">PROVEEDOR</label>
                    <select name="proveedor_id" required style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;color:var(--gray-text);">
                        <option value="">Seleccionar proveedor...</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->id }}">{{ $prov->nombre ?? $prov->usuario }} ({{ $prov->codigo_compras ?? '—' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:11px;font-weight:600;color:var(--gray-muted);display:block;margin-bottom:4px;">FECHA ENTREGA ESPERADA</label>
                    <input type="date" name="fecha_entrega" required min="{{ now()->addDay()->format('Y-m-d') }}" style="width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;color:var(--gray-text);">
                </div>
                <div style="display:flex;align-items:flex-end;">
                    <button type="button" onclick="agregarProductoOC()" style="padding:9px 16px;background:var(--purple-subtle);color:var(--purple);border:1.5px solid var(--purple-mid);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;">+ Agregar producto</button>
                </div>
            </div>

            <div id="productosOCContainer">
                <div class="oc-producto-row" style="display:grid;grid-template-columns:2fr 80px 1fr 40px;gap:10px;margin-bottom:8px;align-items:center;">
                    <select name="productos_oc[0][producto_id]" required style="padding:8px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:inherit;">
                        <option value="">Producto...</option>
                        @foreach($productos as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->codigo }} — {{ $prod->nombre }} (${{ number_format($prod->precio, 2) }})</option>
                        @endforeach
                    </select>
                    <select name="productos_oc[0][unidad]" style="padding:8px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:inherit;">
                        <option value="KG">KG</option>
                        <option value="PZA">PZA</option>
                        <option value="CAJA">CAJA</option>
                    </select>
                    <input type="number" name="productos_oc[0][cantidad]" placeholder="Cantidad" step="1" min="1" required style="padding:8px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:inherit;">
                    <span></span>
                </div>
            </div>

            <div style="margin-top:14px;display:flex;gap:10px;align-items:center;">
                <button type="submit" class="btn-primary">Generar OC</button>
                <span style="font-size:11px;color:var(--gray-muted);">La OC se crea como pendiente de entrega</span>
            </div>
        </form>
    </div>

    {{-- Tabla de OC existentes --}}
    @php
        $ocAtrasadas = $ocProveedores->where('atrasada', true);
    @endphp

    @if($ocAtrasadas->count() > 0)
    <div class="oc-alert">
        <div>
            <strong style="color:var(--red);font-size:13px;">{{ $ocAtrasadas->count() }} OC atrasadas</strong>
            <span style="font-size:12px;color:var(--red);margin-left:6px;">— Requieren seguimiento</span>
        </div>
    </div>
    @endif

    <div class="admin-table-wrap">
        <div class="adm-section-head">
            <div>
                <h4>Seguimiento de órdenes de compra</h4>
                <div class="adm-section-meta">{{ count($ocProveedores) }} órdenes en seguimiento · {{ $conteoOcAtrasadas }} atrasadas</div>
            </div>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>OC</th>
                    <th>Fecha OC</th>
                    <th>Productos</th>
                    <th>Fecha vencimiento</th>
                    <th>Estatus</th>
                    <th>Días atraso</th>
                </tr>
            </thead>
            <tbody>
            @forelse($ocProveedores as $ocData)
                @php
                    $oc = $ocData['oc'];
                    $prov = $ocData['proveedor'];
                    $prods = $ocData['productos'];
                    $atrasada = $ocData['atrasada'];
                    $diasAtraso = $ocData['dias_atraso'];
                @endphp
                <tr style="{{ $atrasada ? 'background:rgba(255,59,48,0.04);' : '' }}">
                    <td>
                        <div style="font-weight:600;">{{ $prov->nombre ?? $prov->usuario ?? '—' }}</div>
                        <div style="font-size:10px;color:var(--gray-muted);">{{ $prov->codigo_compras ?? '' }}</div>
                    </td>
                    <td style="font-weight:700;color:var(--purple);">OC-{{ str_pad($oc->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ $ocData['fecha_oc']->format('d/m/Y') }}</td>
                    <td>
                        @if(!empty($prods))
                            @foreach($prods as $prodOC)
                                <div style="font-size:11px;padding:2px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--border-light);' : '' }}">
                                    <span style="color:var(--purple);font-weight:600;">{{ $prodOC['codigo'] ?? '—' }}</span>
                                    {{ $prodOC['nombre'] ?? $prodOC['producto'] ?? '' }}
                                    <strong style="margin-left:4px;">×{{ $prodOC['cantidad'] ?? $prodOC['qty'] ?? '' }}</strong>
                                </div>
                            @endforeach
                        @else
                            <span style="color:var(--gray-muted);font-size:11px;">Sin detalle</span>
                        @endif
                    </td>
                    <td>{{ $ocData['fecha_vencimiento']->format('d/m/Y') }}</td>
                    <td>
                        @if($atrasada)
                            <span class="badge-est err">Atrasada</span>
                        @elseif($oc->estatus === 'aprobada')
                            <span class="badge-est ok">Aprobada</span>
                        @else
                            <span class="badge-est warn">Pendiente</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($atrasada)
                            <span class="dias-badge dias-crit">{{ $diasAtraso }} días</span>
                        @else
                            <span class="dias-badge dias-ok">A tiempo</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:var(--gray-muted);">
                        No hay órdenes de compra pendientes. Usa el formulario de arriba para crear una.
                    </td>
                </tr>
            @endforelse
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

var ocProductoIdx = 1;
function agregarProductoOC() {
    var container = document.getElementById('productosOCContainer');
    var html = '<div class="oc-producto-row" style="display:grid;grid-template-columns:2fr 80px 1fr 40px;gap:10px;margin-bottom:8px;align-items:center;">' +
        '<select name="productos_oc[' + ocProductoIdx + '][producto_id]" required style="padding:8px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:inherit;">' +
        document.querySelector('#productosOCContainer select[name*=producto_id]').innerHTML +
        '</select>' +
        '<select name="productos_oc[' + ocProductoIdx + '][unidad]" style="padding:8px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:inherit;">' +
        '<option value="KG">KG</option><option value="PZA">PZA</option><option value="CAJA">CAJA</option>' +
        '</select>' +
        '<input type="number" name="productos_oc[' + ocProductoIdx + '][cantidad]" placeholder="Cantidad" step="1" min="1" required style="padding:8px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;font-family:inherit;">' +
        '<button type="button" onclick="this.parentElement.remove()" style="width:28px;height:28px;border-radius:6px;border:1px solid var(--red);background:var(--red-bg);color:var(--red);cursor:pointer;font-size:14px;font-weight:700;">×</button>' +
        '</div>';
    container.insertAdjacentHTML('beforeend', html);
    ocProductoIdx++;
}
</script>
@endpush
