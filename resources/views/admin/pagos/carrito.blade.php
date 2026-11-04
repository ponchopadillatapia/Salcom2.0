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

    /* Banner de aviso (mensajes de sesión): ícono + texto + botón cerrar, estilo iOS.
       POR QUÉ flex: para alinear el ícono, el mensaje (que crece) y la × en una sola línea. */
    .cart-alert{display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:14px;margin-bottom:16px;font-size:14px;font-weight:500;letter-spacing:-.01em;box-shadow:0 1px 2px rgba(0,0,0,.04),0 8px 24px rgba(0,0,0,.05);animation:cartAlertIn .35s cubic-bezier(.4,0,.2,1) both}
    .cart-alert.ok{background:#eafbf0;color:#1a7f45;border:1px solid #c7efd5}
    .cart-alert.err{background:#fdeded;color:#c0392b;border:1px solid #f7cfcf}
    /* Ícono circular a la izquierda: hereda el color del banner (verde/rojo). */
    .cart-alert .ca-ico{flex:0 0 auto;width:24px;height:24px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;background:rgba(255,255,255,.7)}
    .cart-alert .ca-msg{flex:1 1 auto;min-width:0}
    /* Botón cerrar: discreto, se ilumina al pasar el mouse. */
    .cart-alert .ca-close{flex:0 0 auto;background:none;border:none;color:inherit;opacity:.55;font-size:18px;line-height:1;cursor:pointer;padding:2px 4px;border-radius:8px;transition:opacity .15s,background .15s;font-family:inherit}
    .cart-alert .ca-close:hover{opacity:1;background:rgba(0,0,0,.06)}
    @keyframes cartAlertIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
    /* Clase que se agrega al cerrar/auto-ocultar: anima la salida antes de quitarlo del DOM. */
    .cart-alert.ca-hide{opacity:0;transform:translateY(-8px);transition:opacity .3s,transform .3s}

    /* ── Modal de confirmación (reemplaza los confirm() nativos del navegador) ──
       POR QUÉ: el confirm() del sistema se ve gris y rompe el look iOS del carrito.
       Este modal usa los mismos radios, sombras y botones pill que el resto de la vista. */
    .cm-overlay{position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.4);backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:20px}
    .cm-overlay.is-open{display:flex;animation:cmFade .2s ease both}
    .cm-box{background:#fff;border-radius:20px;max-width:380px;width:100%;padding:26px 24px 20px;box-shadow:0 20px 60px rgba(0,0,0,.25);text-align:center;animation:cmPop .25s cubic-bezier(.34,1.3,.64,1) both;font-family:inherit;color:#1d1d1f}
    .cm-box .cm-ico{width:52px;height:52px;border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;font-size:24px}
    .cm-box .cm-ico.warn{background:#fff4e5;color:#b45309}
    .cm-box .cm-ico.danger{background:#fdeded;color:#c0392b}
    .cm-box h4{margin:0 0 6px;font-size:18px;font-weight:600;letter-spacing:-.02em}
    .cm-box p{margin:0 0 20px;font-size:14px;color:#86868b;line-height:1.45;letter-spacing:-.01em}
    .cm-box .cm-actions{display:flex;gap:10px}
    .cm-box .cm-actions button{flex:1;padding:12px 18px;border-radius:980px;font-size:15px;font-weight:600;cursor:pointer;border:none;letter-spacing:-.01em;transition:transform .12s,background .15s;font-family:inherit}
    .cm-box .cm-actions button:active{transform:scale(.97)}
    .cm-cancel{background:#f5f5f7;color:#1d1d1f}
    .cm-cancel:hover{background:#ececef}
    .cm-ok{background:#059669;color:#fff}
    .cm-ok:hover{background:#047a55}
    .cm-ok.danger{background:#c0392b}
    .cm-ok.danger:hover{background:#a53125}
    @keyframes cmFade{from{opacity:0}to{opacity:1}}
    @keyframes cmPop{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}

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
    .cart-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;align-items:flex-end}
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

    @if(session('mensaje'))
        <div class="cart-alert ok" role="status">
            <span class="ca-ico">✓</span>
            <span class="ca-msg">{{ session('mensaje') }}</span>
            <button type="button" class="ca-close" aria-label="Cerrar aviso" onclick="cartCerrarAlerta(this)">×</button>
        </div>
    @endif
    @if(session('error'))
        <div class="cart-alert err" role="alert">
            <span class="ca-ico">!</span>
            <span class="ca-msg">{{ session('error') }}</span>
            <button type="button" class="ca-close" aria-label="Cerrar aviso" onclick="cartCerrarAlerta(this)">×</button>
        </div>
    @endif

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
            <form method="POST" action="{{ route('admin.pagos.carrito.generar') }}" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
                @csrf
                {{-- Selector de departamento: define en qué carpeta del archivero queda este pago.
                     Lista provisional (PagoProveedor::DEPARTAMENTOS) hasta que RH pase los reales. --}}
                <div style="display:flex;flex-direction:column;gap:4px;">
                    <label style="font-size:12px;font-weight:600;color:#6B3FA0;">Departamento</label>
                    <select name="departamento" required
                        style="font-size:14px;padding:11px 14px;border:1.5px solid #c4b5fd;border-radius:10px;background:#fff;color:#1d1d1f;font-family:inherit;min-width:200px;">
                        <option value="">Selecciona…</option>
                        @foreach(\App\Models\PagoProveedor::DEPARTAMENTOS as $clave => $nombre)
                            <option value="{{ $clave }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- type="button": NO envía el form directo; abre el modal y el submit real
                     lo dispara el modal al confirmar (así respetamos el <select> de depto). --}}
                <button type="submit" class="btn-generar"
                    onclick="return cartConfirmar(event, this.form, {titulo:'Generar formato de pago', texto:'Se creará un formato con todas las facturas del carrito.', ok:'Generar', icono:'warn'});">
                    Generar formato de pago
                </button>
            </form>
            <form method="POST" action="{{ route('admin.pagos.carrito.vaciar') }}">
                @csrf
                <button type="submit" class="btn-vaciar"
                    onclick="return cartConfirmar(event, this.form, {titulo:'Vaciar carrito', texto:'Se quitarán todas las facturas seleccionadas. Esta acción no se puede deshacer.', ok:'Vaciar', icono:'danger', peligro:true});">
                    Vaciar carrito
                </button>
            </form>
        </div>
    @endif

    {{-- Modal de confirmación reutilizable: una sola instancia; cartConfirmar() lo rellena. --}}
    <div class="cm-overlay" id="cartModal" role="dialog" aria-modal="true" aria-labelledby="cmTitulo">
        <div class="cm-box">
            <div class="cm-ico warn" id="cmIcono">⚠️</div>
            <h4 id="cmTitulo">Confirmar</h4>
            <p id="cmTexto">¿Seguro?</p>
            <div class="cm-actions">
                <button type="button" class="cm-cancel" onclick="cartCerrarModal()">Cancelar</button>
                <button type="button" class="cm-ok" id="cmOk">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Form que se enviará cuando el usuario confirme en el modal.
    let cartFormPendiente = null;

    // Intercepta el submit del botón: abre el modal en vez del confirm() nativo.
    // Devuelve false SIEMPRE para frenar el envío; el envío real lo hace cmOk al confirmar.
    function cartConfirmar(event, form, opts) {
        event.preventDefault();
        cartFormPendiente = form;

        const modal = document.getElementById('cartModal');
        document.getElementById('cmTitulo').textContent = opts.titulo || 'Confirmar';
        document.getElementById('cmTexto').textContent = opts.texto || '';

        const ico = document.getElementById('cmIcono');
        ico.className = 'cm-ico ' + (opts.icono || 'warn');
        ico.textContent = opts.peligro ? '🗑️' : '⚠️';

        const btnOk = document.getElementById('cmOk');
        btnOk.textContent = opts.ok || 'Confirmar';
        btnOk.className = 'cm-ok' + (opts.peligro ? ' danger' : '');

        modal.classList.add('is-open');
        return false;
    }

    function cartCerrarModal() {
        document.getElementById('cartModal').classList.remove('is-open');
        cartFormPendiente = null;
    }

    // Al confirmar: enviar el form que quedó pendiente (el de generar o el de vaciar).
    document.getElementById('cmOk').addEventListener('click', function () {
        if (cartFormPendiente) cartFormPendiente.submit();
    });

    // Cerrar el modal al hacer clic fuera de la tarjeta o con la tecla Escape.
    document.getElementById('cartModal').addEventListener('click', function (e) {
        if (e.target === this) cartCerrarModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') cartCerrarModal();
    });

    // Banners de sesión: cerrar manual (×) y auto-ocultar a los 5s.
    function cartCerrarAlerta(btn) {
        const el = btn.closest('.cart-alert');
        if (!el) return;
        el.classList.add('ca-hide');
        setTimeout(() => el.remove(), 320); // espera a que termine la animación de salida
    }
    document.querySelectorAll('.cart-alert').forEach(function (el) {
        setTimeout(function () {
            const btn = el.querySelector('.ca-close');
            if (btn) cartCerrarAlerta(btn);
        }, 5000);
    });
</script>
@endpush
@endsection
