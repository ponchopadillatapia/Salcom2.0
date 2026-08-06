<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago confirmado</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #6B3FA0; margin-top: 0;">Industrias Salcom — Pago a proveedor</h2>

        <p>Estimado proveedor <strong>{{ $nombreProveedor }}</strong>,</p>

        @if($estatusFactura === 'pagada')
            <p>Contabilidad confirmó el pago de tus facturas. Ya quedaron marcadas como <strong>pagadas</strong>.</p>
        @else
            <p>Contabilidad registró un lote de pago de tus facturas. Quedaron como <strong>programadas</strong>.</p>
        @endif

        <div style="background: #f3eafa; border-left: 4px solid #6B3FA0; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0 0 8px;"><strong>Detalle</strong></p>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Facturas en el lote: <strong>{{ $numFacturas }}</strong></li>
                <li>Monto: <strong>${{ number_format($montoTotal, 2) }}</strong></li>
                <li>Fecha de pago: <strong>{{ $fechaPago ?: 'Por definir' }}</strong></li>
            </ul>
        </div>

        @if($urlPagos !== '')
            <p style="text-align: center; margin: 28px 0;">
                <a href="{{ $urlPagos }}"
                   style="display: inline-block; background: #6B3FA0; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 700;">
                    Ver mis pagos
                </a>
            </p>
        @endif

        <p>Si tienes dudas, contacta al área de Contabilidad / Compras de Industrias Salcom.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="color: #6b7280; font-size: 12px;">
            Este correo fue enviado automáticamente por el sistema de Industrias Salcom.<br>
            Portal de Proveedores
        </p>
    </div>
</body>
</html>
