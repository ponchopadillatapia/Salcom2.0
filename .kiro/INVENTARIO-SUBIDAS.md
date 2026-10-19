# Inventario de SUBIDAS de archivos/datos — Salcom 2.0

> Todas las acciones del sistema donde se suben archivos o datos: quién, qué pantalla, qué
> método, en qué disco/carpeta quedan, y qué columna/modelo guarda la ruta.
> Para apuntar en el cuaderno y tener control total.

## Conceptos clave (aplican a TODAS las subidas)
- Método de guardado: Laravel `->store('carpeta','disco')` o `Storage::disk('disco')->put(...)`.
- Discos: `public` = storage/app/public (VISIBLE por link, sin login) · `local` = storage/app/private (PRIVADO).
- En la BASE DE DATOS se guarda solo la RUTA (texto), nunca el archivo.
- Guardar un archivo NO usa red externa: es escritura LOCAL en el disco de SiteGround.
  No toca el 5009, ni el 250 (Wiese 172.16.1.250:7183), ni AWS. (El único "externo" sería leer
  el XML para validar, pero se hace en memoria en el mismo servidor.)
- Servidor: SiteGround. IP del sitio 35.215.67.229. La página entra por HTTPS (puerto 443).
- Espacio hoy: 697 MB usados · 22,170 inodes (archivos). OJO: SiteGround limita inodes (nº de
  archivos), no solo GB — con muchos proveedores crece rápido.

---

## 1) PROVEEDOR — Docs fiscales en el ONBOARDING/alta (APIs)
- Pantalla: formulario de alta (APIS/empresa) — CIF, opinión, acta, ID rep legal, ID contribuyente,
  formato identificación, poder, carátula banco, acuses REPSE.
- Guarda: `EmpresaApiController` → `->store('cif','local')`, `('opiniones','local')`,
  `('caratula_banco','local')`, `('actas','local')`, etc.  ✅ disco LOCAL (privado) — BIEN.
- Pero al confirmar/aprobar copia algunos a PUBLIC (expediente_fiscal/*) — ahí se vuelve público.
- Modelo/columna: DocumentoProveedor.archivo (string).

## 2) PROVEEDOR — Subir docs fiscales desde el portal (ya registrado)
- Pantalla: portal proveedor → subir documentos.
- Guarda: `PortalProveedorController` (~1701) → `->store("expediente_fiscal/{tipo}", 'public')`.  🔴 PÚBLICO.
- Comportamiento: updateOrCreate por (proveedor,tipo) → SE SOBREESCRIBE, no se acumula.
  (OJO: el archivo viejo en disco puede quedar huérfano; conviene borrarlo al reemplazar.)
- Modelo/columna: DocumentoProveedor.archivo (string).

## 3) PROVEEDOR — Alta de FACTURAS (PDF/XML/OC)
- Pantalla: portal proveedor → alta de facturas (valida y sube).
- Validación: temporal en disco LOCAL (path_pdf/path_xml). Aprobación automática
  (AltaFacturaValidationService). Si aprueba:
- Guarda final: `PortalProveedorController@registrarFacturaDesdePendiente` (~2048) →
  `Storage::disk('public')->put(...)` en `facturas-proveedor/{proveedor_id}/`.  🔴 PÚBLICO.
- Comportamiento: SE ACUMULAN (cada factura es única por UUID; por ley SAT se guardan ~5 años).
- Modelo/columnas: Factura.archivo_pdf, archivo_xml, archivo_oc (string).

## 4) PROVEEDOR EXTRANJERO — docs
- Guarda: `PortalProveedorController` (~2637) → `->store("foreign_supplier/{tipo}", 'public')`.  🔴 PÚBLICO.
- Modelo/columna: DocumentoProveedor.archivo (string).

## 5) PROVEEDOR — foto de perfil
- Guarda: `PortalProveedorController` (~829) → `->store('proveedores-fotos','public')`.  🟢 no sensible.

## 6) ADMIN/ALEXA — Formato de pago del abono ("Pago a proveedor")
- Pantalla: Pago a proveedor (sube el PDF del formato de pago).
- Guarda: `AdminPagoProveedoresController` (~552) → `->store('pagos_formatos/{abono_id}','public')`. 🔴 PÚBLICO.
- GOTCHA: la ruta NO va en columna propia; se embute como texto en AbonoProveedor.cuenta_bancaria
  ('formato:'+ruta). Caso frágil.

## 7) ADMIN — Expediente de pago: adjuntos + autorización
- Pantalla: expediente de pago (adjuntar comprobante del banco/CEP, póliza, etc.).
- Guarda: `AdminPagosController@adjuntarDocumento` (~1343) →
  `->store('pagos_comprobantes/{pago_id}/adjuntos','public')`.  🔴 PÚBLICO.
- PDF de autorización sellado: `Storage::disk('public')->put('pagos_comprobantes/{id}/adjuntos/autorizacion_*.pdf')` (~1418).
- Comprobantes/formato del pago: `PagoProveedorService` (~1322, ~1366) → public.
- Modelo: PagoProveedor.documentos_adjuntos (JSON) y comprobantes (JSON) — la ruta va DENTRO del JSON.

## 8) ADMIN — Anticipos a proveedor (PDF/XML)
- Guarda: `AdminPagoProveedoresController` (~1291/1295/1428) → `->store('anticipos/pdf'|'anticipos/xml'|'anticipos','public')`. 🔴 PÚBLICO.
- Modelo: AnticipoProveedor.datos (JSON) — ruta dentro del JSON (datos['formato_pdf'], etc.).

## 9) ADMIN / EMPLEADO — Reembolsos
- Admin: `AdminPanelController` (~3715) → `reembolsos/facturas`, `/xml`, `/materialidad` (public). 🔴
- Empleado: `PortalEmpleadoController` (~90/134/136) → mismas carpetas (public). 🔴
- Reembolso de viaje: `ReembolsoViajeController` (~108/113/118) → `reembolsos-viaje/*` (public). 🔴
- Gasolina: `reembolsos`/bitacora-gasolina (public).
- Modelos/columnas: Reembolso.archivo_factura/xml/materialidad, ReembolsoViaje.archivo_comprobantes (string).

## 10) ADMIN — Agentes aduanales (EL PATRÓN BUENO ✅)
- Guarda: `AgenteAduanalArchivoService` → disco LOCAL (privado), carpeta `agentes-aduanales/`,
  nombre UUID, valida MIME y rechaza .key/.cer/e.firma.
- Se ve por ruta PROTEGIDA: `admin.agentes-aduanales.archivo` (con auth admin) → Storage::disk('local')->download.
- Modelo/columna: FacturaAgenteAduanal.ruta + nombre_archivo (string).
- ESTE es el molde a copiar para todo lo demás.

## 11) ADMIN — Fotos/Excels (no sensibles)
- Fotos admin/productos: `admin-fotos`, `productos-fotos` (public). 🟢
- Excels de alta/migración: `excel-productos*`, `migraciones-masivas` (public). 🟢 (temporales de proceso).

---

## RESUMEN DE SEGURIDAD (qué está mal hoy)
🔴 En PÚBLICO (deberían ser privados): facturas de proveedor, docs fiscales (portal + extranjero),
   formatos de pago, comprobantes/adjuntos del expediente, autorizaciones, anticipos, reembolsos.
🟢 OK público: fotos de perfil/producto, Excels de proceso.
✅ Ya privado: onboarding (EmpresaApiController a 'local'), solicitudes de modificación, agentes aduanales.

## PENDIENTES LIGADOS (ya en DECISIONES.md)
- Mover los 🔴 a privado + ruta protegida (molde: AgenteAduanalArchivoService).
- A futuro: mover a S3 (nube) por espacio/inodes. Hoy 697 MB, pero con 8,000 prov crece rápido.
  NO hay cuenta AWS todavía — pendiente conseguirla con Genaro.
- Borrar archivo viejo al sobreescribir docs fiscales (evitar huérfanos).
