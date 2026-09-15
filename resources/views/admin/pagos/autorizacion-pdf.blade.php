<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; margin: 30px; }
        .header { border-bottom: 3px solid #6B3FA0; padding-bottom: 12px; margin-bottom: 18px; }
        h1 { font-size: 16px; color: #6B3FA0; margin: 18px 0 6px; }
        .sub { font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 14px 0; }
        td, th { padding: 8px 10px; font-size: 12px; border: 1px solid #e5e5e5; text-align: left; }
        th { background: #f3e8ff; color: #4a148c; }
        .sello-box { margin-top: 24px; border: 1.5px solid #6B3FA0; border-radius: 8px; padding: 16px; background: #faf5ff; }
        .sello-box .titulo { font-size: 11px; font-weight: bold; color: #6B3FA0; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .sello-hash { font-family: 'Courier New', monospace; font-size: 10px; word-break: break-all; color: #333; background: #fff; padding: 8px; border-radius: 4px; }
        .campo { margin: 4px 0; font-size: 12px; }
        .campo strong { color: #4a148c; }
        .footer { margin-top: 30px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        .badge-auth { display: inline-block; background: #ecfdf5; color: #059669; font-weight: bold; font-size: 11px; padding: 4px 12px; border-radius: 999px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none;">
            <tr>
                <td style="border:none;width:60%;">
                    @include('admin.pagos._logo-salcom')
                    <div style="font-size:12px;margin-top:6px;font-weight:bold;">AUTORIZACIÓN DE PAGO</div>
                    <div class="sub">Comprobante interno con sello electrónico</div>
                </td>
                <td style="border:none;text-align:right;">
                    <div class="sub">Folio de autorización</div>
                    <div style="font-size:18px;font-weight:bold;color:#6B3FA0;">{{ $folioAut }}</div>
                    <div class="sub" style="margin-top:6px;">{{ $badge = 'AUTORIZADO' }}</div>
                    <span class="badge-auth">Autorizado</span>
                </td>
            </tr>
        </table>
    </div>

    <h1>Datos del pago autorizado</h1>
    <table>
        <tr><th style="width:40%;">Expediente de pago</th><td>#{{ $pago->id }}</td></tr>
        <tr><th>Proveedor</th><td>{{ $pago->proveedor->nombre ?? $pago->codigo_proveedor }} ({{ $pago->codigo_proveedor }})</td></tr>
        <tr><th>Facturas del pago</th><td>{{ $pago->num_facturas }}</td></tr>
        <tr><th>Monto total</th><td><strong>${{ number_format((float) $pago->monto_total, 2) }} {{ $pago->datos_confirmacion['moneda'] ?? 'MXN' }}</strong></td></tr>
        <tr><th>Fecha de pago</th><td>{{ $pago->fecha_pago?->format('d/m/Y') ?? '—' }}</td></tr>
    </table>

    @if($pago->lineas && $pago->lineas->count() > 0)
    <h1>Facturas incluidas</h1>
    <table>
        <thead><tr><th>#</th><th>Folio CFDI</th><th>Total</th></tr></thead>
        <tbody>
            @foreach($pago->lineas as $i => $linea)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $linea->folio_cfdi ?: '—' }}</td>
                <td>${{ number_format((float) $linea->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="sello-box">
        <div class="titulo">Sello electrónico de autorización</div>
        <div class="campo"><strong>Autorizado por:</strong> {{ $autorizadoPor }}</div>
        <div class="campo"><strong>Fecha y hora:</strong> {{ $fechaHora }}</div>
        <div class="campo"><strong>Dirección IP:</strong> {{ $ip }}</div>
        @if($notas)<div class="campo"><strong>Notas:</strong> {{ $notas }}</div>@endif
        <div class="campo" style="margin-top:10px;"><strong>Cadena original / Hash (SHA-256):</strong></div>
        <div class="sello-hash">{{ $hash }}</div>
    </div>

    <div class="footer">
        Documento generado y sellado electrónicamente por Salcom Link · {{ now()->format('d/m/Y H:i:s') }}<br>
        Este sello garantiza la integridad del documento y la identidad de quien autorizó. Folio: {{ $folioAut }}
    </div>
</body>
</html>
