<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Opinión de Cumplimiento SAT</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #6B3FA0; margin-top: 0;">Industrias Salcom — Aviso de Opinión de Cumplimiento</h2>

        <p>Estimado proveedor <strong>{{ $nombreProveedor }}</strong>,</p>

        @if($estatus === 'sin_documento')
            <p>Le informamos que <strong>no contamos con su Opinión de Cumplimiento</strong> ante el SAT en nuestros registros.</p>
            <p>Para continuar siendo proveedor activo de Industrias Salcom, es necesario que nos envíe su Opinión de Cumplimiento <strong>positiva y vigente</strong> (correspondiente al mes en curso).</p>
        @elseif($estatus === 'rechazado')
            <p>Le informamos que su Opinión de Cumplimiento ante el SAT fue <strong>rechazada</strong> en nuestra última revisión.</p>
            <p>Le solicitamos enviar una nueva Opinión de Cumplimiento <strong>positiva y vigente</strong> a la brevedad.</p>
        @else
            <p>Le informamos que su Opinión de Cumplimiento ante el SAT se encuentra <strong>pendiente de revisión</strong>.</p>
            <p>Si aún no la ha enviado, le solicitamos hacerlo a la brevedad para mantener su estatus como proveedor activo.</p>
        @endif

        <div style="background: #f3eafa; border-left: 4px solid #6B3FA0; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <strong>Requisitos:</strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                <li>Opinión de Cumplimiento del SAT (Art. 32-D del CFF)</li>
                <li>Debe ser <strong>POSITIVA</strong></li>
                <li>Debe corresponder al <strong>mes en curso</strong></li>
                <li>Formato PDF</li>
            </ul>
        </div>

        <p>Puede enviar su documento respondiendo a este correo o subiéndolo directamente en el portal de proveedores.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="color: #6b7280; font-size: 12px;">
            Este correo fue enviado automáticamente por el sistema de Industrias Salcom.<br>
            Departamento de Compras — Gerencia de Compras
        </p>
    </div>
</body>
</html>
