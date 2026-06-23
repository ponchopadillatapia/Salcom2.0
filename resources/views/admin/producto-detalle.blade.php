@extends('layouts.admin')
@section('title', $producto->codigo . ' — Detalle')
@section('hero')
<div class="hero-band">
    <h1>{{ $producto->codigo }}</h1>
    <p>{{ $producto->nombre }}</p>
</div>
@endsection
@push('styles')
<style>
    .detalle-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .detalle-card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;padding:24px}
    .detalle-card h3{font-size:13px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px}
    .detalle-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-light)}
    .detalle-row:last-child{border-bottom:none}
    .detalle-label{font-size:12px;color:var(--gray-muted);font-weight:600}
    .detalle-value{font-size:13px;color:var(--gray-text);font-weight:600;text-align:right;max-width:60%;word-break:break-word}
    .detalle-value.empty{color:var(--gray-muted);font-style:italic;font-weight:400}
    .detalle-value.precio{color:var(--green);font-size:16px;font-weight:700}
    .detalle-value.codigo{color:var(--purple);font-weight:700}
    .badge-activo{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600}
    .badge-activo.on{background:var(--green-bg);color:var(--green)}
    .badge-activo.off{background:var(--red-bg);color:var(--red)}
    .back-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--purple);text-decoration:none;font-weight:600;margin-bottom:16px}
    .back-link:hover{opacity:.8}
    .btn-edit{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--purple);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none}
    .btn-edit:hover{background:var(--purple-dark)}
    .btn-delete{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--red-bg);color:var(--red);border:1px solid var(--red);border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:none}
    .btn-delete:hover{background:var(--red);color:#fff}
    @media(max-width:768px){.detalle-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')

<a href="{{ route('admin.productos') }}" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
    Volver al catálogo
</a>

<div class="detalle-grid">
    {{-- Información principal --}}
    <div class="detalle-card">
        <h3>Información del producto</h3>
        <div class="detalle-row">
            <span class="detalle-label">Código</span>
            <span class="detalle-value codigo">{{ $producto->codigo }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Código alterno</span>
            <span class="detalle-value {{ !$producto->codigo_alterno ? 'empty' : '' }}">{{ $producto->codigo_alterno ?: 'No asignado' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Nombre</span>
            <span class="detalle-value">{{ $producto->nombre }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Nombre alterno</span>
            <span class="detalle-value {{ !$producto->nombre_alterno ? 'empty' : '' }}">{{ $producto->nombre_alterno ?: 'No asignado' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Descripción corta</span>
            <span class="detalle-value {{ !$producto->descripcion_corta ? 'empty' : '' }}">{{ $producto->descripcion_corta ?: 'Sin descripción' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Descripción</span>
            <span class="detalle-value {{ !$producto->descripcion ? 'empty' : '' }}">{{ $producto->descripcion ?: 'Sin descripción' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Clave SAT</span>
            <span class="detalle-value {{ !$producto->clave_sat ? 'empty' : '' }}">{{ $producto->clave_sat ?: 'No asignada' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Estatus</span>
            <span class="badge-activo {{ $producto->activo ? 'on' : 'off' }}">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</span>
        </div>
    </div>

    {{-- Clasificación --}}
    <div class="detalle-card">
        <h3>Clasificación</h3>
        <div class="detalle-row">
            <span class="detalle-label">Categoría</span>
            <span class="detalle-value {{ !$producto->categoria ? 'empty' : '' }}">{{ $producto->categoria ?: 'Sin categoría' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Familia</span>
            <span class="detalle-value {{ !$producto->familia ? 'empty' : '' }}">{{ $producto->familia ?: 'Sin familia' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Subfamilia</span>
            <span class="detalle-value {{ !$producto->subfamilia ? 'empty' : '' }}">{{ $producto->subfamilia ?: 'No asignada' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Tipo de producto</span>
            <span class="detalle-value {{ !$producto->tipo_producto ? 'empty' : '' }}">{{ $producto->tipo_producto ?: 'No asignado' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Segmento de mercado</span>
            <span class="detalle-value {{ !$producto->segmento_mercado ? 'empty' : '' }}">{{ $producto->segmento_mercado ?: 'No asignado' }}</span>
        </div>
    </div>

    {{-- Comercial --}}
    <div class="detalle-card">
        <h3>Datos comerciales</h3>
        <div class="detalle-row">
            <span class="detalle-label">Precio</span>
            <span class="detalle-value precio">${{ number_format($producto->precio, 2) }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Unidad de venta</span>
            <span class="detalle-value">{{ $producto->unidad_venta ?: '—' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Stock</span>
            <span class="detalle-value">{{ number_format($producto->stock) }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Maneja lotes</span>
            <span class="detalle-value">{{ $producto->maneja_lotes ? 'Sí' : 'No' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">IVA</span>
            <span class="detalle-value">{{ $producto->iva ? $producto->iva . '%' : '—' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">IEPS</span>
            <span class="detalle-value">{{ $producto->ieps ? $producto->ieps . '%' : '—' }}</span>
        </div>
    </div>

    {{-- Logística --}}
    <div class="detalle-card">
        <h3>Logística y empaque</h3>
        <div class="detalle-row">
            <span class="detalle-label">Cajas por tarima</span>
            <span class="detalle-value {{ !$producto->cajas_por_tarima ? 'empty' : '' }}">{{ $producto->cajas_por_tarima ?: 'No definido' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Piezas por caja</span>
            <span class="detalle-value {{ !$producto->piezas_por_caja ? 'empty' : '' }}">{{ $producto->piezas_por_caja ?: 'No definido' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Peso bruto caja</span>
            <span class="detalle-value {{ !$producto->peso_bruto_caja ? 'empty' : '' }}">{{ $producto->peso_bruto_caja ? $producto->peso_bruto_caja . ' kg' : 'No definido' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Peso bruto</span>
            <span class="detalle-value {{ !$producto->peso_bruto ? 'empty' : '' }}">{{ $producto->peso_bruto ? $producto->peso_bruto . ' kg' : 'No definido' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Volumen</span>
            <span class="detalle-value {{ !$producto->volumen ? 'empty' : '' }}">{{ $producto->volumen ?: 'No definido' }}</span>
        </div>
        <div class="detalle-row">
            <span class="detalle-label">Unidad XML</span>
            <span class="detalle-value {{ !$producto->unidad_xml ? 'empty' : '' }}">{{ $producto->unidad_xml ?: 'No definida' }}</span>
        </div>
    </div>
</div>

{{-- Metadatos --}}
<div class="detalle-card" style="margin-bottom:24px">
    <h3>Registro</h3>
    <div class="detalle-row">
        <span class="detalle-label">Registrado por</span>
        <span class="detalle-value">{{ $producto->proveedor_nombre ?: 'Sistema' }} ({{ $producto->proveedor_tipo ?: '—' }})</span>
    </div>
    <div class="detalle-row">
        <span class="detalle-label">Fecha de alta</span>
        <span class="detalle-value">{{ $producto->created_at ? $producto->created_at->format('d/m/Y H:i') : '—' }}</span>
    </div>
    <div class="detalle-row">
        <span class="detalle-label">Última modificación</span>
        <span class="detalle-value">{{ $producto->updated_at ? $producto->updated_at->format('d/m/Y H:i') : '—' }}</span>
    </div>
</div>

{{-- Acciones --}}
<div style="display:flex;gap:12px;">
    <a href="{{ route('admin.productos') }}" class="btn-edit" style="background:var(--gray-soft);color:var(--gray-text);border:1px solid var(--border);">Volver</a>
    <form method="POST" action="{{ route('admin.productos.borrar', $producto->id) }}" onsubmit="return confirm('¿Eliminar este producto? No se puede deshacer.')">
        @csrf
        <button type="submit" class="btn-delete">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Eliminar producto
        </button>
    </form>
</div>

@endsection
