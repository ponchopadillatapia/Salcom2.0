# Checklist de prueba en LOCAL — Flujo de correos del pago (Alexa)

> Probar ANTES de subir a producción. Los correos van a gente real, así que primero se prueban
> en local SIN enviar de verdad (se escriben en el log).

## 0. Preparar el entorno de prueba (una sola vez)
En el `.env` LOCAL, poner el mailer en modo log (NO envía, solo escribe en el log):
```
MAIL_MAILER=log
```
Luego:
```
php artisan config:clear
```
Los correos "enviados" aparecerán en: storage/logs/laravel.log
(Tip: abre el log en una pestaña y vas viendo lo que cae.)

## 1. CORREO 1 — Formato confirmado → avisa a Alexa
- Entra como admin → Formato para pago → arma un formato (carrito) → genera → confírmalo.
- REVISAR en laravel.log: debe aparecer un correo con asunto
  "Nuevo formato para pago pendiente de autorizar" dirigido a alexa (autorizador).

## 2. CORREO 2 — Autorizado → avisa a Karen
- Entra al expediente de ese pago → Autorizar.
- REVISAR log: correo "Formato para pago AUTORIZADO — listo para pagar" a karen (pagador).
- Verificar que se generó el PDF de autorización en el expediente.

## 3. CORREO 3 — Rechazado → avisa al depa + facturas a pendiente
- Con OTRO formato confirmado, en su expediente → Rechazar (escribe un motivo).
- REVISAR log: correo "Tu formato para pago fue RECHAZADO" al correo del departamento, con el motivo.
- REVISAR en la BD / pantalla: las facturas de ese lote volvieron a estatus "pendiente"
  (deben reaparecer en Formato para pago como disponibles).

## 4. CORREO 4 — Subir CEP del banco → marca pagadas + avisa a departamentos
- En un expediente AUTORIZADO, sube un archivo en la zona "Comprobante del banco (CEP)".
- REVISAR: el mensaje debe decir "X factura(s) marcadas como pagadas...".
- REVISAR log: correo "Se realizó el pago de tus facturas" al correo del departamento.
- REVISAR en la BD: las facturas del lote quedaron en estatus "pagada".
- REVISAR: el PDF del CEP se guardó en disco privado (storage/app/private/pagos_comprobantes/...),
  NO en public.

## 5. Caso correo vacío (no debe romper)
- Si un departamento NO tiene correo en config/correos_pago.php, el flujo NO debe fallar:
  simplemente no manda ese correo (y puede loguear un warning). La operación (pagar/rechazar)
  se completa igual.

## Cuando TODO pase en local:
1. Volver el .env local como estaba (si quieres) y subir el código a git.
2. En producción: configurar la cuenta de correo REAL (bloque MAIL_* del .env de SiteGround,
   con la cuenta que dé Genaro) + llenar los correos de departamentos en config/correos_pago.php
   o por las env CORREO_DEPTO_*.
3. git pull + php artisan config:cache + view:clear + route:clear en SiteGround.

## Pendientes de datos (antes de producción)
- Cuenta de correo oficial de envío (Genaro).
- Confirmar correo de Alexa (¿salazar o salzar? ¿.com.mx?).
- Correos de los departamentos faltantes (compras, imex, fletes_acatlan, framfoods_a/b, seguridad, otros).
