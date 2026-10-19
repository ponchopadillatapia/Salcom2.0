<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #6B3FA0; margin-top: 0;">Salcom — {{ $titulo }}</h2>

        @foreach($lineas as $linea)
            <p style="color:#374151;line-height:1.5;">{!! $linea !!}</p>
        @endforeach

        @if($urlBoton)
            <div style="margin: 24px 0;">
                <a href="{{ $urlBoton }}" style="display:inline-block;background:#6B3FA0;color:#fff;text-decoration:none;padding:11px 22px;border-radius:8px;font-weight:600;">
                    {{ $textoBoton ?: 'Abrir en el portal' }}
                </a>
            </div>
        @endif

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="color: #6b7280; font-size: 12px;">
            Correo automático del sistema Salcom (flujo de pago a proveedores). No responder a este mensaje.
        </p>
    </div>
</body>
</html>
