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
    .gc-tabs{display:flex;gap:4px;background:var(--gray-soft);border-radius:12px;padding:4px;margin-bottom:24px;width:fit-content;flex-wrap:wrap}
    .gc-tab{padding:9px 18px;font-size:12px;font-weight:600;color:var(--gray-muted);cursor:pointer;border:none;background:none;border-radius:10px;font-family:inherit;transition:all .2s}
    .gc-tab:hover{color:var(--purple);background:rgba(107,63,160,.06)}
    .gc-tab.active{color:var(--purple);background:var(--white);box-shadow:0 1px 4px rgba(0,0,0,.06)}
    .gc-panel{display:none}.gc-panel.active{display:block}

    .card{background:var(--white);border:1px solid var(--border-light);border-radius:14px;overflow:hidden;margin-bottom:20px}
    .card-head{padding:14px 20px;font-size:13px;font-weight:700;color:var(--gray-text);background:var(--gray-soft);border-bottom:1px solid var(--border-light)}
    .card-body{padding:20px}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:10px;font-weight:700;color:var(--gray-muted);text-transform:uppercase;letter-spacing:.5px;padding:10px 14px;text-align:left;border-bottom:1px solid var(--border-light)}
    .tbl td{padding:10px 14px;font-size:13px;color:var(--gray-text);border-bottom:1px solid var(--border-light)}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:var(--purple-subtle)}

    .badge{font-size:11px;font-weight:600;padding:3px 10px;border-radius:999px;display:inline-block}
    .badge-ok{background:#ecfdf5;color:#059669}
    .badge-warn{background:#fefce8;color:#d97706}
    .badge-err{background:#fef2f2;color:#dc2626}
    .badge-gray{background:#f3f4f6;color:#6b7280}

    .btn-sm{padding:5px 12px;font-size:11px;font-weight:600;border-radius:6px;border:none;cursor:pointer;font-family:inherit;transition:all .15s}
    .btn-alta{background:#ecfdf5;color:#059669;border:1px solid #a7f3d0}.btn-alta:hover{background:#059669;color:#fff}
    .btn-baja{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}.btn-baja:hover{background:#dc2626;color:#fff}
    .btn-auth{background:var(--purple-light);color:var(--purple);border:1px solid var(--purple-mid)}.btn-auth:hover{background:var(--purple);color:#fff}

    .forecast-bar{width:80px;height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px}
    .forecast-fill{height:100%;border-radius:4px;background:var(--purple)}

    .dias-badge{font-size:12px;font-weight:700;padding:4px 10px;border-radius:8px;display:inline-block}
    .dias-ok{background:#ecfdf5;color:#059669}
    .dias-warn{background:#fefce8;color:#d97706}
    .dias-crit{background:#fef2f2;color:#dc2626}

    .alert-msg{border-radius:10px;padding:10px 16px;font-size:13px;margin-bottom:16px;font-weight:500}
    .alert-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#059669}

    .inline-form{display:inline-flex;align-items:center;gap:6px}
    .inline-form input{border:1px solid var(--border-light);border-radius:6px;padding:5px 8px;font-size:12px;width:80px}

    @media(max-width:768px){.gc-tabs{width:100%}.card{overflow-x:auto}}
</style>
@endpush
@section('content')

@if(session('mensaje'))
    <div class="alert-msg alert-ok">{{ session('mensaje') }}</div>
@endif

<div class="gc-tabs">
    <button class="gc-tab active" onclick="switchGC('opinion')">Opinión Positiva</button>
    <button class="gc-tab" onclick="switchGC('forecast')">Forecast</button>
    <button class="gc-tab" onclick="switchGC('autorizacion')">Autorización Proveedores</button>
    <button class="gc-tab" onclick="switchGC('dias')">Días de Inventario</button>
    <button class="gc-tab" onclick="switchGC('costos')">Autorizar Costos</button>
</div>

{{-- ═══ OPINIÓN POSITIVA ═══ --}}
<div class="gc-panel active" id="panel-opinion">
    <div class="card">
        <div class="card-head">Opinión de Cumplimiento SAT — Pendientes y estado</div>
        <table class="tbl">
            <thead><tr><th>Código</th><th>Proveedor</th><th>Estado</th><th>Acción</th></tr></thead>
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
                    <td><span class="badge badge-{{ $cls }}">{{ $lbl }}</span></td>
                    <td>
                        @if($est !== 'aprobado')
                            <a href="mailto:{{ $op['proveedor']->correo }}?subject=Opinión de Cumplimiento SAT Pendiente&body=Estimado proveedor, le solicitamos actualizar su Opinión de Cumplimiento ante el SAT." class="btn-sm btn-auth">Enviar correo</a>
                        @else
                            <span style="font-size:11px;color:#059669;font-weight:600">✓ Al día</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ FORECAST ═══ --}}
<div class="gc-panel" id="panel-forecast">
    <div class="card">
        <div class="card-head">% Forecast por Proveedor — Estimado por Dirección</div>
        <table class="tbl">
            <thead><tr><th>Código</th><th>Proveedor</th><th>Score</th><th>Forecast %</th><th>Estimado</th></tr></thead>
            <tbody>
            @foreach($proveedores as $prov)
                @php $forecast = min(100, max(0, $prov->score_total * 1.1)); @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $prov->codigo_compras ?? '—' }}</td>
                    <td style="font-weight:600">{{ $prov->nombre ?? $prov->usuario }}</td>
                    <td>{{ number_format($prov->score_total, 0) }}%</td>
                    <td>
                        <div class="forecast-bar"><div class="forecast-fill" style="width:{{ $forecast }}%"></div></div>
                        <strong>{{ number_format($forecast, 0) }}%</strong>
                    </td>
                    <td style="font-size:11px;color:var(--gray-muted)">Autorizado por dirección</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ AUTORIZACIÓN PROVEEDORES ═══ --}}
<div class="gc-panel" id="panel-autorizacion">
    <div class="card">
        <div class="card-head">Dirección autoriza al proveedor para comprar — Alta / Baja</div>
        <table class="tbl">
            <thead><tr><th>Código</th><th>Proveedor</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>
            @foreach(\App\Models\ProveedorUser::orderBy('nombre')->get() as $prov)
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $prov->codigo_compras ?? '—' }}</td>
                    <td style="font-weight:600">{{ $prov->nombre ?? $prov->usuario }}</td>
                    <td><span class="badge {{ $prov->activo ? 'badge-ok' : 'badge-err' }}">{{ $prov->activo ? 'Activo' : 'Inactivo' }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('admin.autorizar-proveedor') }}" class="inline-form">
                            @csrf
                            <input type="hidden" name="proveedor_id" value="{{ $prov->id }}">
                            @if($prov->activo)
                                <input type="hidden" name="accion" value="baja">
                                <button type="submit" class="btn-sm btn-baja" onclick="return confirm('¿Dar de baja a {{ $prov->nombre }}?')">Dar de baja</button>
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
<div class="gc-panel" id="panel-dias">
    <div class="card">
        <div class="card-head">Días de Inventario por Artículo — Días de pedido y entrega</div>
        <table class="tbl">
            <thead><tr><th>Código</th><th>Producto</th><th>Stock</th><th>Días inventario</th><th>Días pedido</th><th>Días entrega</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach($inventarioDias as $item)
                @php
                    $dias = $item['dias_inventario'];
                    $cls = $dias >= 30 ? 'dias-ok' : ($dias >= 15 ? 'dias-warn' : 'dias-crit');
                @endphp
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $item['producto']->codigo }}</td>
                    <td>{{ $item['producto']->nombre }}</td>
                    <td style="font-weight:600">{{ number_format($item['producto']->stock) }}</td>
                    <td><span class="dias-badge {{ $cls }}">{{ $dias }} días</span></td>
                    <td>{{ $item['dias_pedido'] }} días</td>
                    <td>{{ $item['dias_entrega'] }} días</td>
                    <td>
                        @if($dias < $item['dias_pedido'] + $item['dias_entrega'])
                            <span class="badge badge-err">Reordenar</span>
                        @else
                            <span class="badge badge-ok">OK</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ AUTORIZAR COSTOS ═══ --}}
<div class="gc-panel" id="panel-costos">
    <div class="card">
        <div class="card-head">Autorizar Actualización de Costo por Dirección</div>
        <table class="tbl">
            <thead><tr><th>Código</th><th>Producto</th><th>Precio actual</th><th>Nuevo precio</th><th>Acción</th></tr></thead>
            <tbody>
            @foreach($productos as $prod)
                <tr>
                    <td style="font-weight:700;color:var(--purple)">{{ $prod->codigo }}</td>
                    <td>{{ $prod->nombre }}</td>
                    <td style="font-weight:600">${{ number_format($prod->precio, 2) }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.autorizar-costo') }}" class="inline-form" id="form-costo-{{ $prod->id }}">
                            @csrf
                            <input type="hidden" name="producto_id" value="{{ $prod->id }}">
                            <input type="number" name="nuevo_precio" step="0.01" min="0" placeholder="$0.00" style="width:90px">
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
function switchGC(tab) {
    document.querySelectorAll('.gc-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.gc-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
@endpush
