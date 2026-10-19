# Checklist de prueba en LOCAL — cambios del 2026-10-16

> Probar TODO esto en local (XAMPP) ANTES de subir a producción. Son cambios grandes que tocan
> el flujo de pagos, seguridad de archivos y correos. Marca cada casilla al pasar.

## Preparación
- [ ] En el .env LOCAL: `MAIL_MAILER=log` (los correos NO se envían, se escriben en el log).
- [ ] Correr: `php artisan config:clear` y `php artisan view:clear`
- [ ] Tener abierto `storage/logs/laravel.log` para ver los correos que "caen".
- [ ] Verificar que existe el symlink: `php artisan storage:link` (si no estaba).

---

## BLOQUE A — Eliminación de "Pago a proveedor"
- [ ] En el menú admin YA NO aparece "Pagos" (el que era "Pago a proveedor").
- [ ] Sí siguen apareciendo: "Expediente de pago", "Abono al proveedor"/saldar, "Historial de abonos", "Anticipos".
- [ ] Entrar a la URL vieja a mano (/admin/pago-proveedores) → debe dar 404 (ya no existe la ruta). OK que falle.
- [ ] La campanita de alertas abre sin error (se arregló una referencia que apuntaba a la ruta borrada).

## BLOQUE B — Seguridad: documentos en privado
- [ ] Como PROVEEDOR: subir una factura (PDF+XML) que valide bien → se registra.
- [ ] Como proveedor: abrir esa factura (ver PDF) → se ve (ahora por ruta protegida, no link directo).
- [ ] Como ADMIN: en Formato para pago / expediente, abrir el PDF/XML de esa factura → se ve.
- [ ] Verificar en disco: el archivo está en `storage/app/private/facturas-proveedor/...` (NO en public).
- [ ] Probar que el link directo viejo NO funciona: copiar una URL tipo
      `/storage/facturas-proveedor/...` en el navegador → debe dar 404 / no encontrado.
- [ ] (Opcional) Subir un doc fiscal, un reembolso, un anticipo → que se vean por su botón y queden en private.

## BLOQUE C — Flujo de correos del pago (los 4)
- [ ] CORREO 1: armar formato para pago (carrito) → generar → CONFIRMAR.
      Revisar log: correo "Nuevo formato para pago pendiente de autorizar" a Alexa (autorizador).
- [ ] CORREO 2: entrar al expediente de ese pago → AUTORIZAR.
      Revisar log: correo "Formato para pago AUTORIZADO" a Karen (pagador).
      Verificar que se generó el PDF de autorización (en private).
- [ ] CORREO 3 + factura a pendiente: con OTRO formato confirmado → RECHAZAR (con motivo).
      Revisar log: correo "Tu formato para pago fue RECHAZADO" al correo del departamento, con el motivo.
      Verificar: las facturas de ese lote volvieron a estatus "pendiente".
- [ ] CORREO 4 + marcar pagadas: en un expediente AUTORIZADO, subir archivo en
      "Comprobante del banco (CEP)".
      Verificar mensaje "X factura(s) marcadas como pagadas...".
      Revisar log: correo "Se realizó el pago de tus facturas" al depto.
      Verificar: las facturas quedaron en estatus "pagada".
      Verificar: el CEP se guardó en `storage/app/private/pagos_comprobantes/...` (NO public).

## BLOQUE D — Que el saldar en 0 (Abono al proveedor) sigue funcionando
- [ ] Ir a "Abono al proveedor" → debe listar las facturas en estatus "pagada" (las que marcó el CEP).
- [ ] Confirmar el abono → las facturas pasan a "liquidada".
- [ ] Verificar que el mensaje de error (si intentas saldar una que no está pagada) ahora dice
      "sube el comprobante del banco (CEP)" y NO "Pago a proveedor".
- [ ] (Si Contpaqi local no está, el abono igual se completa; solo loguea warning. Eso es correcto.)

## BLOQUE E — Comando limpiar basura (opcional en local, útil en producción)
- [ ] `php artisan limpiar:basura` → muestra la lista y pide confirmar. (En local puedes NO confirmar.)

---

## Si TODO pasó → SUBIR A PRODUCCIÓN
Desde la laptop:
```
git add -A
git commit -m "Seguridad archivos privados + flujo correos de pago + elimina Pago a proveedor"
git push
```
En SiteGround (SSH):
```
cd ~/www/salcomlink.mx/public_html
git pull
php artisan storage:link        (si no existía)
php artisan route:clear
php artisan view:clear
php artisan config:cache
```
Y SOLO en producción, cuando esté lista la cuenta de correo real (Genaro):
- Configurar el bloque MAIL_* del .env de SiteGround con la cuenta oficial.
- Llenar los correos de departamentos en config/correos_pago.php o por env CORREO_DEPTO_*.
- Confirmar el correo de Alexa (salazar vs salzar, .com.mx).
- (Opcional) `php artisan limpiar:basura` para quitar los scripts de prueba.

## Pendientes de datos (no bloquean el código, sí el envío real de correos)
- [ ] Cuenta de correo oficial de envío (Genaro).
- [ ] Correo de Alexa confirmado.
- [ ] Correos de departamentos faltantes.

## Pendientes a futuro (anotados en DECISIONES.md)
- Mover archivos a S3 cuando haya cuenta AWS (escalabilidad; seguridad ya resuelta).
- Migrar archivos viejos de public→private (hoy hay compatibilidad: se buscan en ambos).
- Borrar tabla AbonoProveedor del todo (solo si se confirma que no se necesita el histórico).
