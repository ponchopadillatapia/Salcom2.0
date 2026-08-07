<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirma tu correo — Portal de Proveedores</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #6B3FA0; margin-top: 0;">Industrias Salcom — Bienvenido</h2>

        <p>Estimado proveedor <strong>{{ $nombreProveedor }}</strong>,</p>

        <p>Tu registro en el <strong>Portal de Proveedores</strong> se realizó correctamente. Para poder iniciar sesión debes <strong>confirmar que este correo es tuyo</strong>.</p>

        <div style="background: #f3eafa; border-left: 4px solid #6B3FA0; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0 0 8px;"><strong>Datos de acceso</strong></p>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Usuario: <strong>{{ $usuario !== '' ? $usuario : $correo }}</strong></li>
                <li>Correo: <strong>{{ $correo }}</strong></li>
                <li>Después de confirmar, inicia sesión con tu usuario o correo y la contraseña que registraste.</li>
            </ul>
        </div>

        @if($urlVerificacion !== '')
            <p style="text-align: center; margin: 28px 0;">
                <a href="{{ $urlVerificacion }}"
                   style="display: inline-block; background: #6B3FA0; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 700;">
                    Confirmar mi correo
                </a>
            </p>
            <p style="color: #6b7280; font-size: 13px; text-align: center;">
                Este enlace vence en 48 horas. Si no funciona, copia y pega esta dirección en tu navegador:<br>
                <span style="word-break: break-all;">{{ $urlVerificacion }}</span>
            </p>
        @endif

        <p>Una vez confirmado, el siguiente paso es completar tu <strong>onboarding</strong> (datos de identificación, contactos y documentos fiscales) para que Dirección pueda activar tu cuenta.</p>

        <p>Si no realizaste este registro, ignora este mensaje o contacta a Compras de Industrias Salcom.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="color: #6b7280; font-size: 12px;">
            Este correo fue enviado automáticamente por el sistema de Industrias Salcom.<br>
            Departamento de Compras — Portal de Proveedores
        </p>
    </div>
</body>
</html>
