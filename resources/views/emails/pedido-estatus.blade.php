<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualización de Pedido</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #1a56db; margin-top: 0;">Salcom — Actualización de Pedido</h2>

        <p>Hola <strong>{{ $nombreCliente }}</strong>,</p>

        <p>Tu pedido <strong>{{ $folio }}</strong> ha cambiado de estatus:</p>

        <div style="background: #eef2ff; border-left: 4px solid #1a56db; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <strong style="font-size: 18px;">{{ $estatus }}</strong>
        </div>

        @if($notas)
            <p><strong>Notas:</strong> {{ $notas }}</p>
        @endif

        <p>Puedes consultar el detalle completo desde tu portal de cliente.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="color: #6b7280; font-size: 12px;">
            Este correo fue enviado automáticamente por el sistema Salcom. No responder a este mensaje.
        </p>
    </div>
</body>
</html>
