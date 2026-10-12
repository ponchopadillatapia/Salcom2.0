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
    /* POR QUÉ esta fuente: es el "system font stack" de Apple (San Francisco en iOS/Mac).
       Usa la tipografía nativa del dispositivo, que es la que da el look limpio de iPhone.
       Con -webkit-font-smoothing el texto se ve más fino, como en iOS. */
    .cart-wrap{
        max-width:1040px;margin:0 auto;
        font-family:-apple-system,BlinkMacSystemFont,"SF Pro Text","SF Pro Display","Segoe UI",Roboto,Helvetica,Arial,sans-serif;
        -webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;
        color:#1d1d1f; /* gris casi negro de Apple, no negro puro */
    }
    /* Botón de regreso: discreto, con la flecha pegada al texto (estilo iOS) */
    .cart-back{display:inline-flex;align-items:center;gap:4px;margin-bottom:20px;font-size:15px;font-weight:500;color:#6B3FA0;text-decoration:none;letter-spacing:-.01em;transition:opacity .15s}
    .cart-back:hover{opacity:.6}

    .cart-alert{padding:13px 16px;border-radius:12px;margin-bottom:16px;font-size:14px;font-weight:500;letter-spacing:-.01em}
    .cart-alert.ok{background:#eafbf0;color:#1a7f45;border:1px solid #c7efd5}
    .cart-alert.err{background:#fdeded;color:#c0392b;border:1px solid #f7cfcf}

    /* Tarjeta de proveedor: bordes muy redondeados y sombra suave, como las "cards" de iOS */
    .cart-prov{background:#fff;border:1px solid #ececef;border-radius:18px;margin-bottom:16px;overflow:hidden;box-shadow:0 1px 2px rgba(0,0,0,.04),0 8px 24px rgba(0,0,0,.04)}
    .cart-prov-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:16px 20px;background:#faf7ff;border-bottom:1px solid #f1eafb}
    .cart-prov-head h3{margin:0;font-size:17px;font-weight:600;color:#1d1d1f;letter-spacing:-.02em}
    .cart-prov-head .meta{font-size:13px;color:#86868b;margin-top:3px;font-weight:400;letter-spacing:-.01em}
    .cart-prov-sub{text-align:right}
    .cart-prov-sub .lbl{font-size:11px;font-weight:600;color:#a78bda;text-transform:uppercase;letter-spacing:.4px}
    .cart-prov-sub .amt{font-size:19px;font-weight:600;color:#1a7f45;font-variant-numeric:tabular-nums;letter-spacing:-.02em;margin-top:1px}

    table.cart-tbl{width:100%;border-collapse:collapse}
    table.cart-tbl th{font-size:11px;font-weight:600;color:#86868b;text-transform:uppercase;letter-spacing:.3px;padding:11px 20px;text-align:left;border-bottom:1px solid #f0f0f2}
    table.cart-tbl td{padding:13px 20px;font-size:14px;border-bottom:1px solid #f5f5f7;letter-spacing:-.01em;color:#1d1d1f}
    table.cart-tbl tbody tr:last-child td{border-bottom:none}

    /* Gran total: la "card" destacada, borde morado fino y mucho radio */
    .cart-grand{background:#fff;border:1.5px solid #6B3FA0;border-radius:18px;padding:20px 24px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-top:4px;box-shadow:0 8px 24px rgba(107,63,160,.08)}
    .cart-grand .lbl{font-size:15px;font-weight:600;color:#5b21b6;letter-spacing:-.01em}
    .cart-grand .amt{font-size:26px;font-weight:700;color:#1a7f45;font-variant-numeric:tabular-nums;letter-spacing:-.03em}

    /* Botones estilo iOS: muy redondeados (pill), sin borde duro, con transición suave */
    .cart-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
    .btn-generar{padding:13px 26px;background:#059669;color:#fff;border:none;border-radius:980px;font-size:15px;font-weight:600;cursor:pointer;letter-spacing:-.01em;transition:transform .12s,background .15s;font-family:inherit}
    .btn-generar:hover{background:#047a55}
    .btn-generar:active{transform:scale(.97)}
    .btn-vaciar{padding:13px 26px;background:#f5f5f7;color:#c0392b;border:none;border-radius:980px;font-size:15px;font-weight:500;cursor:pointer;letter-spacing:-.01em;transition:transform .12s,background .15s;font-family:inherit}
    .btn-vaciar:hover{background:#ececef}
    .btn-vaciar:active{transform:scale(.97)}
    .btn-quitar{padding:5px 12px;background:#f5f5f7;color:#c0392b;border:none;border-radius:980px;font-size:12px;font-weight:500;cursor:pointer;transition:background .15s;font-family:inherit}
    .btn-quitar:hover{background:#fdeded}

    .cart-empty{background:#fff;border:1px solid #ececef;border-radius:18px;padding:56px 20px;text-align:center;color:#86868b;box-shadow:0 1px 2px rgba(0,0,0,.04)}
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
