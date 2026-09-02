<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de pago · {{ $abono->etiquetaFolio() }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 20px; }
        h2 { font-size: 13px; color: #6B3FA0; margin: 20px 0 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table th { background: #ecfdf5; color: #065f46; font-size: 9px; text-transform: uppercase; padding: 8px 10px; text-align: left; border-bottom: 2px solid #6ee7b7; }
        table td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .total-row td { font-weight: bold; background: #f9fafb; border-top: 2px solid #059669; }
        .monto { font-weight: bold; text-align: right; }
        .sello {
            display: inline-block; border: 3px solid #059669; color: #059669;
            font-size: 22px; font-weight: bold; padding: 6px 18px; border-radius: 8px;
            transform: rotate(-6deg); letter-spacing: 3px;
        }
        .datos-box { background: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb; margin-top: 10px; font-size: 11px; line-height: 1.8; }
        .footer { margin-top: 30px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <table style="border:none;margin-bottom:10px">
        <tr>
            <td style="border:none;padding:0;width:55%">
                <div style="font-size:16px;font-weight:bold">INDUSTRIAS SALCOM</div>
                <div style="font-size:9px;color:#666">S.A. DE C.V.</div>
                <div style="font-size:12px;margin-top:4px;font-weight:bold">COMPROBANTE DE PAGO A PROVEEDOR</div>
            </td>
            <td style="border:none;padding:0;text-align:right">
                <div style="font-size:9px;color:#666">Folio de pago</div>
                <div style="font-size:18px;font-weight:bold;color:#6B3FA0">{{ $abono->etiquetaFolio() }}</div>
                <div style="font-size:10px;margin-top:4px">Fecha de pago: {{ $abono->fecha?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div style="text-align:center;margin:14px 0">
        <span class="sello">PAGADO</span>
    </div>

    <h2>Datos del pago</h2>
    <div class="datos-box">
        <strong>Proveedor:</strong> {{ $abono->nombre_proveedor }} ({{ $abono->codigo_proveedor }})<br>
        <strong>Concepto:</strong> {{ $abono->concepto ?: '—' }}<br>
        <strong>Moneda:</strong> {{ $abono->moneda }} &nbsp;·&nbsp; <strong>Tipo de cambio:</strong> {{ number_format((float) $abono->tipo_cambio, 4) }}<br>
        <strong>Cuenta bancaria:</strong> {{ $cuentaBancaria ?: '—' }}
    </div>

    <h2>Facturas pagadas</h2>
    <table>
        <thead>
            <tr>
                <th>Serie</th>
                <th>Folio</th>
                <th>Referencia</th>
                <th>Importe pagado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($abono->documentos as $doc)
                <tr>
                    <td>{{ $doc->serie_doc ?: 'FAC' }}</td>
                    <td>{{ $doc->folio_doc ?: '—' }}</td>
                    <td style="font-size:9px;color:#666">{{ $doc->referencia ?: '—' }}</td>
                    <td class="monto">${{ number_format((float) $doc->importe_pago, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#999">Sin documentos</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="3" style="text-align:right;font-size:10px">TOTAL PAGADO</td>
                <td class="monto" style="font-size:14px;color:#059669">${{ number_format((float) $abono->monto_pago, 2) }} {{ $abono->moneda }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generado por Salcom Link · {{ now()->format('d/m/Y H:i') }} · Comprobante interno de pago a proveedor · Pago {{ $abono->etiquetaFolio() }}
    </div>
</body>
</html>
