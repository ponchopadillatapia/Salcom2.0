@extends('layouts.admin')
@section('title', 'Carrito de pago')
@section('hero')
<div class="hero-band">
    <h1>Carrito de pago</h1>
    <p>Facturas de varios proveedores juntas para un solo formato de pago</p>
</div>
@endsection

@push('styles')
<style>
    .cart-wrap{max-width:1100px;margin:0 auto}
    .cart-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;font-size:13px;font-weight:600;color:#6B3FA0;text-decoration:none}
    .cart-alert{padding:12px 14px;border-radius:10px;margin-bottom:16px;font-size:13px}
    .cart-alert.ok{background:#ecfdf5;color:#16a34a;border:1px solid #16a34a}
    .cart-alert.err{background:#fef2f2;color:#dc2626;border:1px solid #dc2626}
    .cart-prov{background:#fff;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:18px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
    .cart-prov-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 18px;background:#faf5ff;border-bottom:1px solid #f0e6ff}
    .cart-prov-head h3{margin:0;font-size:15px;font-weight:700;color:#6B3FA0}
    .cart-prov-head .meta{font-size:12px;color:#9ca3af;margin-top:2px}
    .cart-prov-sub{text-align:right}
    .cart-prov-sub .lbl{font-size:10px;font-weight:700;color:#a78bda;text-transform:uppercase;letter-spacing:.3px}
    .cart-prov-sub .amt{font-size:18px;font-weight:800;color:#059669;font-variant-numeric:tabular-nums}
    table.cart-tbl{width:100%;border-collapse:collapse}
    table.cart-tbl th{font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.4px;padding:10px 14px;text-align:left;border-bottom:1px solid #e5e7eb}
    table.cart-tbl td{padding:10px 14px;font-size:13px;border-bottom:1px solid #f3f4f6}
    .cart-grand{background:#fff;border:2px solid #6B3FA0;border-radius:12px;padding:18px 22px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-top:8px}
    .cart-grand .lbl{font-size:14px;font-weight:700;color:#5b21b6}
    .cart-grand .amt{font-size:24px;font-weight:800;color:#059669;font-variant-numeric:tabular-nums}
    .cart-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
    .btn-generar{padding:12px 22px;background:#059669;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer}
    .btn-generar:hover{background:#22a34d}
    .btn-vaciar{padding:12px 22px;background:#fff;color:#dc2626;border:1.5px solid #dc2626;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer}
    .btn-quitar{padding:4px 10px;background:#fff;color:#dc2626;border:1px solid #fca5a5;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer}
    .cart-empty{background:#fff;border:1px dashed #c4b5fd;border-radius:12px;padding:48px 20px;text-align:center;color:#9ca3af}
</style>
@endpush

@section('content')
<div class="cart-wrap">
    <a class="cart-back" href="{{ route('admin.pagos') }}">← Buscar más proveedores</a>

    @if(session('mensaje'))<div class="cart-alert ok">{{ session('mensaje') }}</div>@endif
    @if(session('error'))<div class="cart-alert err">{{ session('error') }}</div>@endif

    @if(count($grupos) === 0)
        <div class="cart-empty">
            <p style="font-size:15px;margin:0 0 6px;font-weight:600;color:#6b7280">El carrito está vacío.</p>
            <p style="margin:0">Busca un proveedor, selecciona sus facturas y dale "Agregar al carrito".</p>
        </div>
    @else
        @foreach($grupos as $g)
        <div class="cart-prov">
            <div class="cart-prov-head">
                <div>
                    <h3>{{ $g['nombre'] }}</h3>
                    <div class="meta">{{ $g['codigo'] }} · RFC {{ $g['rfc'] ?: '—' }} · Banco: {{ $g['banco'] ?: '—' }}</div>
                </div>
                <div class="cart-prov-sub">
                    <div class="lbl">Subtotal proveedor</div>
                    <div class="amt">${{ number_format($g['subtotal'], 2) }}</div>
                </div>
            </div>
            <table class="cart-tbl">
                <thead>
                    <tr>
                        <th>Serie</th>
                        <th>Factura</th>
                        <th>Fecha</th>
                        <th style="text-align:right">Saldo</th>
                        <th>Moneda</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($g['folios'] as $f)
                    <tr>
                        <td>{{ $f['serie'] ?: '—' }}</td>
                        <td style="font-weight:600">{{ $f['folio'] }}</td>
                        <td>{{ $f['fecha_factura'] ? \Illuminate\Support\Carbon::parse($f['fecha_factura'])->format('d/m/Y') : '—' }}</td>
                        <td style="text-align:right;font-weight:700;color:#b45309">${{ number_format((float) $f['saldo'], 2) }}</td>
                        <td>{{ $f['moneda'] }}</td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('admin.pagos.carrito.quitar') }}" style="display:inline">
                                @csrf
                                <input type="hidden" name="codigo_proveedor" value="{{ $g['codigo'] }}">
                                <input type="hidden" name="folio" value="{{ $f['folio'] }}">
                                <button type="submit" class="btn-quitar">✕ Quitar</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach

        {{-- Gran total: suma de todos los proveedores. Separado por moneda si hay USD. --}}
        <div class="cart-grand">
            <div class="lbl">GRAN TOTAL ({{ $totalFacturas }} factura{{ $totalFacturas === 1 ? '' : 's' }} de {{ count($grupos) }} proveedor{{ count($grupos) === 1 ? '' : 'es' }})</div>
            <div style="text-align:right">
                @if($granTotalMxn > 0)<div class="amt">${{ number_format($granTotalMxn, 2) }} MXN</div>@endif
                @if($granTotalUsd > 0)<div class="amt">${{ number_format($granTotalUsd, 2) }} USD</div>@endif
            </div>
        </div>

        <div class="cart-actions">
            <form method="POST" action="{{ route('admin.pagos.carrito.generar') }}">
                @csrf
                <button type="submit" class="btn-generar"
                    onclick="return confirm('¿Generar el formato de pago con todas las facturas del carrito?');">
                    Generar formato de pago
                </button>
            </form>
            <form method="POST" action="{{ route('admin.pagos.carrito.vaciar') }}">
                @csrf
                <button type="submit" class="btn-vaciar"
                    onclick="return confirm('¿Vaciar el carrito? Se quitan todas las facturas seleccionadas.');">
                    Vaciar carrito
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
