@extends('layouts.admin')
@section('title', "OC Borrador #{$oc->id}")
@section('hero')
<div class="hero-band">
    <h1>OC Borrador #{{ $oc->id }}</h1>
    <p>Detalle de orden de compra automática — {{ $oc->proveedor->nombre ?? $oc->proveedor->usuario ?? 'Sin proveedor' }}</p>
</div>
@endsection
@push('styles')
<style>
    @keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .anim{animation:fadeUp .4s cubic-bezier(.4,0,.2,1) both}

    .oc-header-info{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-lg);padding:22px 26px;margin-bottom:20px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;box-shadow:var(--shadow-sm)}
    .oc-header-info .info-item{min-width:120px}
    .oc-header-info .info-label{font-size:12px;color:var(--gray-muted);margin-bottom:4px}
    .oc-header-info .info-val{font-size:18px;font-weight:700;color:var(--gray-text)}
    .oc-header-info .info-val.monto{color:var(--purple)}
    .oc-header-info .info-val.urgente{color:var(--red)}

    .badge-est{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-est.pendiente{background:#fef3c7;color:#d97706}
    .badge-est.aprobada{background:var(--green-bg);color:var(--green)}
    .badge-est.rechazada{background:var(--red-bg);color:var(--red)}
    .badge-est.urgente{background:var(--red-bg);color:var(--red)}
    .badge-est.normal{background:var(--green-bg);color:var(--green)}

    .admin-table-wrap{background:var(--white);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px}
    .admin-table{width:100%;border-collapse:collapse}
    .admin-table th{font-size:11px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;background:var(--gray-soft);border-bottom:1px solid var(--border)}
    .admin-table td{padding:12px 16px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border)}
    .admin-table tr:last-child td{border-bottom:none}
    .admin-table tbody tr:hover td{background:var(--purple-subtle)}
    .admin-table tbody tr.row-urgente td{background:#fef2f2}
    .admin-table tbody tr.row-urgente:hover td{background:#fee2e2}

    .adm-section-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 22px;background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .adm-section-head h4{font-size:14px;font-weight:700;color:var(--gray-text);margin:0}
    .adm-section-meta{font-size:12px;color:var(--gray-muted)}

    .btn-primary{padding:9px 18px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:var(--transition)}
    .btn-primary:hover{opacity:.9;transform:scale(1.02)}
    .btn-primary:active{transform:scale(.97)}
    .btn-success{padding:9px 18px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:var(--transition)}
    .btn-success:hover{opacity:.9;transform:scale(1.02)}
    .btn-success:active{transform:scale(.97)}
    .btn-danger{padding:9px 18px;background:var(--red);color:#fff;border:none;border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:var(--transition)}
    .btn-danger:hover{opacity:.9;transform:scale(1.02)}
    .btn-danger:active{transform:scale(.97)}
    .btn-secondary{padding:9px 18px;background:var(--gray-soft);color:var(--gray-text);border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;font-weight:600;cursor:pointer;transition:var(--transition)}
    .btn-secondary:hover{background:var(--purple-subtle);color:var(--purple);border-color:var(--purple-mid)}
    .btn-sm{padding:6px 12px;font-size:12px}
    .btn-icon{padding:6px 10px;font-size:12px;display:inline-flex;align-items:center;gap:4px}

    .alert-success{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--green-bg);border:1px solid #a7f3d0;color:var(--green);font-weight:500}
    .alert-error{border-radius:8px;padding:10px 16px;font-size:13px;margin-bottom:16px;background:var(--red-bg);border:1px solid var(--red);color:var(--red);font-weight:500}

    .action-cards{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px}
    .action-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px}
    .action-card h4{font-size:14px;font-weight:700;color:var(--gray-text);margin-bottom:12px}
    .action-card p{font-size:12px;color:var(--gray-muted);margin-bottom:14px}

    .form-group{margin-bottom:12px}
    .form-group label{display:block;font-size:12px;font-weight:600;color:var(--gray-muted);margin-bottom:4px}
    .form-group input,.form-group select,.form-group textarea{width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;font-family:inherit;color:var(--gray-text);transition:var(--transition)}
    .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,0.1)}
    .form-group textarea{resize:vertical;min-height:60px}

    .input-cantidad{width:80px;text-align:center;padding:6px 8px;border:1px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit;color:var(--gray-text)}
    .input-cantidad:focus{outline:none;border-color:var(--purple);box-shadow:0 0 0 3px rgba(107,63,160,0.1)}

    .urgente-indicator{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:var(--red);background:var(--red-bg);padding:2px 8px;border-radius:999px}
    .urgente-indicator::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--red);animation:pulse 1.5s infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}

    .back-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--gray-muted);text-decoration:none;margin-bottom:20px}
    .back-link:hover{color:var(--purple)}

    .oc-actions-bar{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px}

    .agregar-form{display:grid;grid-template-columns:1fr auto auto;gap:10px;align-items:end}

    @media(max-width:768px){
        .action-cards{grid-template-columns:1fr}
        .admin-table-wrap{overflow-x:auto}
        .oc-header-info{flex-direction:column;align-items:flex-start}
        .oc-actions-bar{flex-direction:column}
        .agregar-form{grid-template-columns:1fr}
    }
</style>
@endpush
@section('content')

<a href="{{ route('admin.reorden-oc') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Volver a lista de OC
</a>

@if(session('mensaje'))
    <div class="alert-success">{{ session('mensaje') }}</div>
@endif
@if(session('error'))
    <div class="alert-error">{{ session('error') }}</div>
@endif

@php
    $productos = $oc->productos ?? [];
    $cantidadProductos = count($productos);
    $productosUrgentes = collect($productos)->where('urgente', true)->count();
@endphp

{{-- Información general de la OC --}}
<div class="oc-header-info anim">
    <div class="info-item">
        <div class="info-label">Proveedor</div>
        <div class="info-val">{{ $oc->proveedor->nombre ?? $oc->proveedor->usuario ?? '—' }}</div>
    </div>
    <div class="info-item">
        <div class="info-label">Estatus</div>
        <div class="info-val">
            <span class="badge-est {{ $oc->estatus }}">{{ ucfirst($oc->estatus) }}</span>
        </div>
    </div>
    <div class="info-item">
        <div class="info-label">Productos</div>
        <div class="info-val">{{ $cantidadProductos }}</div>
    </div>
    <div class="info-item">
        <div class="info-label">Monto estimado</div>
        <div class="info-val monto">${{ number_format($oc->monto_estimado, 2) }}</div>
    </div>
    <div class="info-item">
        <div class="info-label">Urgencia</div>
        <div class="info-val {{ $productosUrgentes > 0 ? 'urgente' : '' }}">
            @if($productosUrgentes > 0)
                <span class="urgente-indicator">{{ $productosUrgentes }} urgente{{ $productosUrgentes > 1 ? 's' : '' }}</span>
            @else
                <span style="font-size:13px;color:var(--green);font-weight:600">Normal</span>
            @endif
        </div>
    </div>
    <div class="info-item">
        <div class="info-label">Fecha generación</div>
        <div class="info-val" style="font-size:14px">{{ $oc->created_at->format('d/m/Y H:i') }}</div>
    </div>
</div>

{{-- Acciones de aprobación/rechazo (solo si está pendiente) --}}
@if($oc->estatus === 'pendiente')
<div class="action-cards anim" style="animation-delay:.04s">
    <div class="action-card">
        <h4>Aprobar orden de compra</h4>
        <p>Al aprobar, la OC cambia a estatus "aprobada" y se registra fecha y usuario.</p>
        <form method="POST" action="{{ route('admin.reorden-oc.aprobar', $oc->id) }}">
            @csrf
            <button type="submit" class="btn-success" onclick="return confirm('¿Aprobar esta orden de compra?')">
                Aprobar OC
            </button>
        </form>
    </div>
    <div class="action-card">
        <h4>Rechazar orden de compra</h4>
        <p>Indica el motivo del rechazo. La OC cambiará a estatus "rechazada".</p>
        <form method="POST" action="{{ route('admin.reorden-oc.rechazar', $oc->id) }}">
            @csrf
            <div class="form-group">
                <label for="motivo">Motivo de rechazo *</label>
                <textarea name="motivo" id="motivo" required placeholder="Ej: Proveedor no disponible, presupuesto excedido..."></textarea>
            </div>
            <button type="submit" class="btn-danger" onclick="return confirm('¿Rechazar esta orden de compra?')">
                Rechazar OC
            </button>
        </form>
    </div>
</div>
@endif

{{-- Tabla de productos con formulario de modificación --}}
<div class="admin-table-wrap anim" style="animation-delay:.08s">
    <div class="adm-section-head">
        <div>
            <h4>Productos de la orden</h4>
            <div class="adm-section-meta">{{ $cantidadProductos }} producto{{ $cantidadProductos !== 1 ? 's' : '' }} en esta OC</div>
        </div>
        @if($oc->estatus === 'pendiente')
        <button type="submit" form="form-actualizar" class="btn-primary btn-sm">Guardar cantidades</button>
        @endif
    </div>

    @if($oc->estatus === 'pendiente')
    <form id="form-actualizar" method="POST" action="{{ route('admin.reorden-oc.actualizar-productos', $oc->id) }}">
        @csrf
        @method('PUT')
    </form>
    @endif

    <table class="admin-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Cantidad sugerida</th>
                <th>Unidad</th>
                <th>Precio unitario</th>
                <th>Subtotal</th>
                <th>Stock actual</th>
                <th>Urgencia</th>
                @if($oc->estatus === 'pendiente')
                <th>Acciones</th>
                @endif
            </tr>
        </thead>
        <tbody>
        @forelse($productos as $index => $producto)
            @php
                $esUrgente = $producto['urgente'] ?? false;
            @endphp
            <tr class="{{ $esUrgente ? 'row-urgente' : '' }}">
                <td style="font-weight:600;font-family:monospace;font-size:12px">{{ $producto['codigo'] }}</td>
                <td style="font-weight:500">{{ $producto['nombre'] }}</td>
                <td>
                    @if($oc->estatus === 'pendiente')
                        <input type="number"
                               name="cantidades[{{ $producto['codigo'] }}]"
                               value="{{ $producto['cantidad_sugerida'] }}"
                               min="1"
                               class="input-cantidad"
                               form="form-actualizar">
                    @else
                        <span style="font-weight:600;font-variant-numeric:tabular-nums">{{ number_format($producto['cantidad_sugerida']) }}</span>
                    @endif
                </td>
                <td>{{ $producto['unidad'] ?? '—' }}</td>
                <td style="font-variant-numeric:tabular-nums">${{ number_format($producto['precio_unitario'], 2) }}</td>
                <td style="font-weight:600;font-variant-numeric:tabular-nums">${{ number_format($producto['subtotal'], 2) }}</td>
                <td style="font-variant-numeric:tabular-nums">
                    {{ number_format($producto['stock_actual'], 0) }}
                    @if(isset($producto['punto_reorden']) && $producto['punto_reorden'])
                        <span style="font-size:11px;color:var(--gray-muted)">/ {{ number_format($producto['punto_reorden'], 0) }} PR</span>
                    @endif
                </td>
                <td>
                    @if($esUrgente)
                        <span class="urgente-indicator">Stock cero</span>
                    @else
                        <span class="badge-est normal">Normal</span>
                    @endif
                </td>
                @if($oc->estatus === 'pendiente')
                <td>
                    <form method="POST" action="{{ route('admin.reorden-oc.eliminar-producto', $oc->id) }}" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="codigo" value="{{ $producto['codigo'] }}">
                        <button type="submit" class="btn-danger btn-icon" onclick="return confirm('¿Eliminar {{ $producto['nombre'] }} de esta OC?')" title="Eliminar producto">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </form>
                </td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ $oc->estatus === 'pendiente' ? 9 : 8 }}" style="text-align:center;padding:32px;color:var(--gray-muted);">
                    No hay productos en esta orden de compra.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Agregar producto (solo si está pendiente) --}}
@if($oc->estatus === 'pendiente')
<div class="admin-table-wrap anim" style="animation-delay:.12s">
    <div class="adm-section-head">
        <div>
            <h4>Agregar producto</h4>
            <div class="adm-section-meta">Solo productos del mismo proveedor</div>
        </div>
    </div>
    <div style="padding:20px 22px">
        <form method="POST" action="{{ route('admin.reorden-oc.agregar-producto', $oc->id) }}">
            @csrf
            <div class="agregar-form">
                <div class="form-group" style="margin-bottom:0">
                    <label for="producto_id">Producto</label>
                    <select name="producto_id" id="producto_id" required>
                        <option value="">Seleccionar producto...</option>
                        @php
                            $productosProveedor = \App\Models\ProductoProveedorPrecio::where('proveedor_id', $oc->proveedor_id)
                                ->with('producto')
                                ->get()
                                ->filter(fn($pp) => $pp->producto && $pp->producto->activo)
                                ->sortBy(fn($pp) => $pp->producto->nombre);
                        @endphp
                        @foreach($productosProveedor as $pp)
                            <option value="{{ $pp->producto->id }}">
                                {{ $pp->producto->codigo }} — {{ $pp->producto->nombre }} (Stock: {{ number_format($pp->producto->stock, 0) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label for="cantidad">Cantidad</label>
                    <input type="number" name="cantidad" id="cantidad" min="1" value="1" required style="width:100px">
                </div>
                <div style="padding-top:18px">
                    <button type="submit" class="btn-primary">Agregar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Notas/Motivo si existe --}}
@if($oc->notas)
<div class="admin-table-wrap anim" style="animation-delay:.16s">
    <div class="adm-section-head">
        <h4>Notas</h4>
    </div>
    <div style="padding:16px 22px;font-size:13px;color:var(--gray-text)">
        {{ $oc->notas }}
    </div>
</div>
@endif

{{-- Historial de modificaciones --}}
@if(!empty($oc->historial_modificaciones))
<div class="admin-table-wrap anim" style="animation-delay:.2s">
    <div class="adm-section-head">
        <div>
            <h4>Historial de modificaciones</h4>
            <div class="adm-section-meta">{{ count($oc->historial_modificaciones) }} cambio{{ count($oc->historial_modificaciones) !== 1 ? 's' : '' }} registrado{{ count($oc->historial_modificaciones) !== 1 ? 's' : '' }}</div>
        </div>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Producto</th>
                <th>Antes</th>
                <th>Después</th>
            </tr>
        </thead>
        <tbody>
        @foreach($oc->historial_modificaciones as $cambio)
            <tr>
                <td style="font-size:12px">{{ \Carbon\Carbon::parse($cambio['fecha'])->format('d/m/Y H:i') }}</td>
                <td style="font-weight:500">{{ $cambio['usuario_nombre'] ?? 'Sistema' }}</td>
                <td>
                    @switch($cambio['accion'])
                        @case('modificar_cantidad')
                            <span style="color:var(--purple);font-weight:600">Modificar cantidad</span>
                            @break
                        @case('eliminar_producto')
                            <span style="color:var(--red);font-weight:600">Eliminar producto</span>
                            @break
                        @case('agregar_producto')
                            <span style="color:var(--green);font-weight:600">Agregar producto</span>
                            @break
                        @default
                            <span>{{ $cambio['accion'] }}</span>
                    @endswitch
                </td>
                <td style="font-family:monospace;font-size:12px">{{ $cambio['producto_codigo'] ?? '—' }}</td>
                <td style="font-variant-numeric:tabular-nums">{{ $cambio['valor_anterior'] ?? '—' }}</td>
                <td style="font-variant-numeric:tabular-nums">{{ $cambio['valor_nuevo'] ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
