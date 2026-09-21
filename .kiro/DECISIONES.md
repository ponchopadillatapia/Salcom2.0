# Bitácora de decisiones — Salcom 2.0

> Memoria externa del proyecto. Cada cambio importante o petición (de Alan, dirección, etc.)
> se anota aquí en pocas líneas: QUÉ se hizo, POR QUÉ, y DÓNDE en el código.
> Se lee de arriba (lo más reciente) hacia abajo.

---

## 2026-09-20 — Conexión REAL de "buscar proveedor por RFC" (Wiese)
- **Qué:** `buscarProveedorPorRFC()` dejó de estar simulado/hardcodeado (antes solo servía para ORPACK). Ahora hace la llamada real a Wiese vía `/ClienteProveedor/BuscarPorRFC`.
- **Por qué:** el onboarding (paso "Confirmación de cuenta") lo usa para detectar la cuenta del proveedor en Wiese por su RFC y ligarla automáticamente. Debía funcionar con cualquier proveedor, no solo ORPACK.
- **Dónde:** `app/Services/ProveedorApiService.php` → `buscarProveedorPorRFC()` (reusa `buscarProveedorWiese()`). Lo consume `PortalProveedorController::index()` y `confirmarCuentaWiese()`.

## 2026-09-20 — Órdenes de compra de Wiese (resuelto el problema del vacío)
- **Qué:** el endpoint correcto es `/Documento/ListaDocumentosOCPorProveedor` (el viejo con "Fechas" ya no existe). Requiere 3 params: codigoProveedor, strIdConceptosOC (ID de concepto, NO 0) y fecha. Se itera sobre los conceptos de OC (19 MP, 2004 PT, 3015 Imp, 3151 Mtto, 3130 EPP) y se juntan.
- **Por qué:** con `strIdConceptosOC=0` Wiese devolvía []. Cada tipo de OC tiene su ID de concepto.
- **Dónde:** `app/Services/ProveedorApiService.php` → `listarDocumentosOCPorProveedorFechas()`. Se muestra en `admin/proveedores/{codigo}/facturas` (vista `admin.proveedor-facturas`, controlador `AdminPanelController::proveedorFacturas`).

## 2026-09-22 — Eliminada pestaña "Facturas" del módulo Proveedores
- **Qué:** el panel de la pestaña "Facturas" (tabla plana con "Detalle →") se desactivó envolviéndolo en @if(false). Único acceso a facturas: botón "Ver facturas →" del directorio (que trae Wiese + BD).
- **Por qué:** confundía tener dos accesos; el usuario pidió quedarse solo con el que funciona con Wiese.
- **Dónde:** `resources/views/admin/proveedores.blade.php` (bloque TAB FACTURAS).

## 2026-09-22 — Admin usa id_proveedor para Wiese + orden por recientes + quitar pestaña Órdenes
- **Qué:** (1) `proveedorFacturas` toma el código Wiese de id_proveedor (antes usaba solo 'codigo', que en ORPACK está vacío → fallaba la consulta a Wiese). (2) El directorio ordena por created_at desc (más nuevos arriba). (3) Se quitó la pestaña "Órdenes". (4) Único acceso a facturas del proveedor: botón "Ver facturas →".
- **Por qué:** el admin no traía las OC de ORPACK por usar el código equivocado; el usuario quiere ver los proveedores como van llegando y sin pestañas de más.
- **Dónde:** `AdminPanelController::proveedorFacturas()` y `proveedores()`; `resources/views/admin/proveedores.blade.php`.

## 2026-09-22 — KPIs de Proveedores reordenados (3 tarjetas)
- **Qué:** en Proveedores/Score quedan 3 tarjetas: Bajo rendimiento (izq), Alto rendimiento (der), Todas (morado, extrema der). Se quitó "Con OCs vencidas". Entra directo a la pestaña Proveedores (ya era el default).
- **Por qué:** simplificar la vista a lo que importa.
- **Dónde:** `resources/views/admin/proveedores.blade.php` (bloque .inv-metrics).
- **NOTA:** el código Wiese debe coincidir con el proveedor. Ej: PAQUEXPRESS (102003240) en Wiese es en realidad CORPORATIVO FERYMAR y no tiene OC. ORPACK (M213015002) sí tiene ~6329 OC.

## 2026-09-22 — Limpieza pestaña/KPI de facturas en Proveedores
- **Qué:** se quitó la pestaña "Facturas" (arriba), la tarjeta KPI "Con facturas pend." y el botón "Eliminar" del directorio (quedó solo "Ver facturas →").
- **Por qué:** las facturas ahora se ven por proveedor (BD + Wiese) con el botón "Ver facturas →"; la pestaña/KPI global de facturas ya no hace falta.
- **Dónde:** `resources/views/admin/proveedores.blade.php`.

## 2026-09-22 — Botón "Ver facturas" en el directorio de proveedores
- **Qué:** en la pestaña "Proveedores" (directorio), columna Acción, se agregó botón "Ver facturas →" que lleva al detalle del proveedor (OC de Wiese + KPIs combinados). Usa id_proveedor, o codigo, o el provisional 'P'.id.
- **Por qué:** antes solo se podía llegar al detalle desde la pestaña "Facturas", que solo lista facturas de BD; proveedores como ORPACK (solo en Wiese) no tenían forma de acceso por menú.
- **Dónde:** `resources/views/admin/proveedores.blade.php` (directorio, columna Acción).

## 2026-09-22 — KPIs del proveedor combinan BD + Wiese
- **Qué:** en el detalle de un proveedor, los KPIs (Deuda total, Pagado, Vencidas) ahora suman las facturas de la BD + las OC de Wiese (regla de Alan: cancelada no cuenta, pendiente=cpendiente>0, pagada=cpendiente 0). Se calcula sobre TODAS las OC del rango, no solo las mostradas.
- **Por qué:** el usuario quiere ver en un solo lugar sus facturas del portal y las compras de Wiese juntas, con KPIs que cuenten todo.
- **Dónde:** `AdminPanelController::proveedorFacturas()` (calcula $wieseDeuda/$wiesePagado/$wieseVencidas) y `resources/views/admin/proveedor-facturas.blade.php` (KPIs combinados).

## 2026-09-22 — Unificar detalle de OC con modal (admin = proveedor)
- **Qué:** la vista del admin dejó de usar acordeón; ahora abre el mismo MODAL "Detalle" que el portal del proveedor. Se mantiene "Ver más (50)".
- **Por qué:** al usuario le gustó más el diseño de modal del portal del proveedor; se buscó consistencia entre ambas pantallas.
- **Dónde:** `resources/views/admin/proveedor-facturas.blade.php` (sección OC Wiese). El modal del proveedor está en `resources/views/proveedores/facturas.blade.php`.

## 2026-09-20 — Pantalla de OC: detalle expandible + paginación (reemplazado el 22-sep por modal)
- **Qué:** en la tabla de OC de Wiese, clic en una fila despliega su detalle; botón "Ver más" de 50 en 50; columna Estatus (regla de Alan: cancelado=1 cancelada, pendiente>0 pendiente, else pagada).
- **Por qué:** eran cientos de OC y se trababa; y no se podía ver el detalle de cada una.
- **Dónde:** `resources/views/admin/proveedor-facturas.blade.php` (sección "Órdenes de compra (Wiese)").

## 2026-09-20 — Fix 404 en Formato para pago con proveedores nuevos
- **Qué:** los proveedores sin id_proveedor usan un código provisional `P`+id (ej. P55). Se agregó scope `porCualquierCodigo()` que busca por todas las columnas de código y por id.
- **Por qué:** el enlace usaba P55 pero la búsqueda solo miraba la columna codigo → 404.
- **Dónde:** `app/Models/ProveedorUser.php` (scope `porCualquierCodigo`), `AdminPagosController::proveedor()` y `estadoCuenta()`.

## NOTA IMPORTANTE — Acceso a Wiese
- Wiese vive en IP INTERNA `172.16.1.250:7186` → SOLO se alcanza con VPN de la empresa.
- Producción (SiteGround) NO está en esa VPN. Las consultas a Wiese solo funcionan en local con VPN
  o requieren infraestructura (VPN site-to-site / whitelist) — pendiente con Alan.
