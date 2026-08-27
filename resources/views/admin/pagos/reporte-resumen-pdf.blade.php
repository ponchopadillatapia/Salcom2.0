<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FORMATO PARA PAGO · {{ 'FCONA-'.str_pad((string) $pago->id, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; border-bottom: 3px solid #6B3FA0; padding-bottom: 10px; }
        .header-left { font-size: 14px; font-weight: bold; }
        .header-left span { display: block; font-size: 10px; font-weight: normal; color: #666; }
        .header-right { text-align: right; font-size: 10px; }
        .header-right .folio { font-size: 16px; font-weight: bold; color: #6B3FA0; }
        h2 { font-size: 13px; color: #6B3FA0; margin: 20px 0 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table th { background: #f3e8ff; color: #5b21b6; font-size: 9px; text-transform: uppercase; padding: 8px 10px; text-align: left; border-bottom: 2px solid #c4b5fd; }
        table td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .total-row td { font-weight: bold; background: #f9fafb; border-top: 2px solid #6B3FA0; }
        .monto { font-weight: bold; text-align: right; }
        .concepto { background: #faf5ff; padding: 12px; border-radius: 6px; border: 1px solid #e9d5ff; margin-top: 10px; font-size: 11px; }
        .footer { margin-top: 30px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
@php
    $fechaDoc = $pago->fecha_pago?->format('d/m/Y')
        ?? $pago->confirmado_at?->format('d/m/Y')
        ?? now()->format('d/m/Y');
    $folioDoc = 'FCONA-'.str_pad((string) $pago->id, 4, '0', STR_PAD_LEFT);
@endphp

    <table style="border:none;margin-bottom:10px">
        <tr>
            <td style="border:none;padding:0;width:60%">
                <div style="font-size:16px;font-weight:bold">INDUSTRIAS SALCOM</div>
                <div style="font-size:9px;color:#666">S.A. DE C.V.</div>
                <div style="font-size:11px;margin-top:4px">FORMATO PARA PAGO</div>
            </td>
            <td style="border:none;padding:0;text-align:right">
                <div style="font-size:9px;color:#666">Formato</div>
                <div style="font-size:18px;font-weight:bold;color:#6B3FA0">{{ $folioDoc }}</div>
                <div style="font-size:10px;margin-top:4px">Fecha: {{ $fechaDoc }}</div>
            </td>
        </tr>
    </table>

    <h2>Datos del pago</h2>
    <table>
        <thead>
            <tr>
                <th>Banco</th>
                <th>Cuenta</th>
                <th>CLABE</th>
                <th>SWIFT</th>
                <th>Importe</th>
                <th>RFC</th>
                <th>IVA</th>
                <th>Proveedor</th>
                <th>Folio General</th>
                <th>Total del Banco</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $banco !== '' ? $banco : '—' }}</td>
                <td>{{ $cuenta !== '' ? $cuenta : '—' }}</td>
                <td>{{ $clabe !== '' ? $clabe : '—' }}</td>
                <td>{{ $swift !== '' ? $swift : '—' }}</td>
                <td class="monto">${{ number_format($importe, 2) }}</td>
                <td>{{ $rfc !== '' ? $rfc : '—' }}</td>
                <td class="monto">${{ number_format($iva, 2) }}</td>
                <td>{{ $nombreProveedor }}<br><span style="font-size:9px;color:#666">{{ $pago->codigo_proveedor }}</span></td>
                <td style="font-weight:bold">{{ $foliosGenerales !== '' ? $foliosGenerales : '—' }}</td>
                <td class="monto" style="font-size:13px;color:#059669">${{ number_format($totalBanco, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <tr>
            <td style="border:none;width:50%">
                <strong>Departamento:</strong> Compras
            </td>
            <td style="border:none;width:50%;text-align:right">
                <strong>Fecha:</strong> {{ $fechaDoc }}
            </td>
        </tr>
    </table>

    @if($pago->lineas && $pago->lineas->count() > 0)
    <h2>Detalle de facturas</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Folio CFDI</th>
                <th>Subtotal</th>
                <th>IVA</th>
                <th>Ret. IVA</th>
                <th>Ret. ISR</th>
                <th>Total</th>
                <th>Neto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pago->lineas as $i => $linea)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $linea->folio_cfdi ?: '—' }}</td>
                <td class="monto">${{ number_format((float)$linea->monto, 2) }}</td>
                <td class="monto">${{ number_format((float)$linea->monto_iva, 2) }}</td>
                <td class="monto">${{ number_format((float)$linea->retencion_iva, 2) }}</td>
                <td class="monto">${{ number_format((float)$linea->retencion_isr, 2) }}</td>
                <td class="monto">${{ number_format((float)$linea->total, 2) }}</td>
                <td class="monto" style="color:#059669">${{ number_format((float)$linea->neto, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($pago->notas)
    <div class="concepto">
        <strong>Concepto / Notas:</strong> {{ $pago->notas }}
    </div>
    @endif

    <table style="margin-top:20px">
        <tr class="total-row">
            <td colspan="8"></td>
            <td style="text-align:right;font-size:10px">TOTAL A PAGAR</td>
            <td class="monto" style="font-size:14px;color:#059669">${{ number_format($totalPagar, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        Generado por Salcom Link · {{ now()->format('d/m/Y H:i') }} · Formato interno de pago
    </div>
</body>
</html>
