<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido al Portal de Proveedores</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #6B3FA0; margin-top: 0;">Industrias Salcom — Bienvenido</h2>

        <p>Estimado proveedor <strong>{{ $nombreProveedor }}</strong>,</p>

        <p>Tu registro en el <strong>Portal de Proveedores</strong> se realizó correctamente.</p>

        <div style="background: #f3eafa; border-left: 4px solid #6B3FA0; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0 0 8px;"><strong>Datos de acceso</strong></p>
            <ul style="margin: 0; padding-left: 20px;">
                <li>Usuario: <strong>{{ $usuario !== '' ? $usuario : $correo }}</strong></li>
                <li>Correo: <strong>{{ $correo }}</strong></li>
                <li>Puedes iniciar sesión con tu usuario o con tu correo, y la contraseña que registraste.</li>
            </ul>
        </div> 

        @if($urlLogin !== '')
            <p style="text-align: center; margin: 28px 0;">
                <a href="{{ $urlLogin }}"
                   style="display: inline-block; background: #6B3FA0; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 700;">
                    Confirmar registro
                </a>
            </p>
        @endif

        <p>El siguiente paso es completar tu <strong>onboarding</strong> (datos de identificación, contactos y documentos fiscales) para que Dirección pueda activar tu cuenta.</p>

        <p>Si no realizaste este registro, ignora este mensaje o contacta a Compras de Industrias Salcom.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="color: #6b7280; font-size: 12px;">
            Este correo fue enviado automáticamente por el sistema de Industrias Salcom.<br>
            Departamento de Compras — Portal de Proveedores
        </p>
    </div>
</body>
</html>
