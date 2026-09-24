# Bitácora de decisiones — Salcom 2.0

> Memoria externa del proyecto. Cada cambio importante o petición (de Alan, dirección, etc.)
> se anota aquí en pocas líneas: QUÉ se hizo, POR QUÉ, y DÓNDE en el código.
> Se lee de arriba (lo más reciente) hacia abajo.

---

## 2026-09-22 — DECISIÓN de Alan: las APIs de Wiese se trabajan SOLO en local (~2 meses)
- **Qué:** Alan NO va a exponer la API de Wiese a internet por ahora. Razones: (1) no tiene seguridad (va por HTTP, sin HTTPS), (2) está saturado de trabajo los próximos ~2 meses. Acuerdo: mientras tanto se desarrolla/usa en LOCAL (con VPN de la oficina).
- **Qué implica:** el CÓDIGO que consume Wiese ya está en producción (listar proveedores + OC/facturas de ORPACK), PERO en producción NO funcionará (SiteGround no alcanza la IP interna 172.16.1.250). Las pantallas de Wiese mostrarán el aviso rojo "no se pudieron cargar (revisa VPN)" — esto es esperado, no rompe el resto de la página.
- **Cuando Alan tenga tiempo (en ~2 meses):** exponer la API con HTTPS en una URL pública y luego, en el `.env` de PRODUCCIÓN, cambiar `PROVEEDOR_API_DOCS_URL` a esa URL. Es el único cambio necesario para que producción jale. No hay que reprogramar nada.
- **Dónde:** config en `.env` (`PROVEEDOR_API_DOCS_URL` = interna 172.16.1.250; `PROVEEDOR_API_URL` = pública AWS 54.210.85.103 que hoy NO responde). Los métodos de Wiese usan `docsUrl` en `app/Services/ProveedorApiService.php`.

## 2026-09-22 — Directorio de Proveedores ahora muestra los REALES de Wiese (API de Alan)
- **Qué:** (1) Se agregó `ProveedorApiService::listarProveedoresWiese()` que consume el endpoint `/ClienteProveedor/ListarProveedorWeb` de la API C#/.NET de Alan (login de servicio `web`, GET, solo lectura). Trae ~5,684 proveedores reales (nombre=crazonsocial, rfc=crfc) de la base contable adSalcom18. (2) La pestaña "Proveedores" del admin ahora lista esos proveedores de Wiese (paginado 50/pág, buscador por nombre/RFC en memoria), en vez de los `proveedores_users` locales (que aquí son de prueba). (3) KPIs Bajo/Alto rendimiento quedaron SIN acción (tarjetas quietas) porque filtraban por score local que Wiese no manda; "Todas" muestra el total de Wiese.
- **Por qué:** los proveedores reales viven en Wiese (Salcom = Wiese, son lo mismo). El directorio debía mostrar esos, no los fakes locales.
- **Dónde:** `app/Services/ProveedorApiService.php` (método nuevo), `AdminPanelController::proveedores()` (inyecta lista Wiese paginada), `resources/views/admin/proveedores.blade.php` (tabla Nombre+RFC y KPIs sin acción).
- **PENDIENTE:** la API solo devuelve nombre y RFC (SELECT limitado en `ConProveedorWeb.cs`); si se quieren más columnas, Alan amplía el SELECT. Falta subir a PRODUCCIÓN. Nota: producción necesita alcanzar la red interna de Wiese (VPN/whitelist).

## 2026-09-22 — Eliminado por completo el "Catálogo de Proveedores" (era redundante)
- **Qué:** (1) La usuaria quitó a mano el enlace del sidebar y renombró "Proveedores / Score" → "Proveedores". (2) Después se borró TODO el catálogo: la ruta `admin/catalogo-proveedores`, el método `catalogoProveedores()` y su helper `monedaDollarConst()` en `AdminPanelController`, y la vista `admin/catalogo-proveedores.blade.php`. Ahora esa URL da 404.
- **Por qué:** era redundante con el nuevo directorio de Proveedores (Wiese). Cerrar el ciclo (no dejar código muerto ni ruta huérfana).
- **Dónde:** `resources/views/layouts/admin.blade.php`, `routes/web.php`, `app/Http/Controllers/AdminPanelController.php`, vista borrada.
- **Verificado:** `php -l` sin errores y `route:list` carga bien (no quedaron referencias rotas).

## 2026-09-22 — MySQL/MariaDB corrupto: reinicializado y salcom20 recreada desde migraciones
- **Qué:** XAMPP daba "MySQL shutdown unexpectedly". Diagnóstico: corrupción de InnoDB ("LSN in the future") y, lo grave, la instalación de MariaDB 10.4.32 quedó dañada a NIVEL MOTOR: cualquier consulta a `information_schema.columns` (de CUALQUIER base) crasheaba el server sin dejar error en el log. Por eso ni mysqldump ni migraciones funcionaban (moría en la migración add_rol_to_admin_users al leer metadatos).
- **Cómo se arregló:** (1) Backup completo de la carpeta data. (2) Se apartaron (NO borraron) los archivos corruptos a `C:\xampp\mysql\CORRUPTO_20260922_103917`. (3) Se reinicializaron las tablas de sistema con `mysql_install_db.exe` en carpeta temporal y se trajeron limpias a data. (4) Se recreó `salcom20` con `php artisan migrate --force` (72 migraciones OK) + `php artisan db:seed --force`. (5) Se subió `innodb_buffer_pool_size` de 16M a 256M en my.ini.
- **Por qué así:** el usuario confirmó que los datos reales también están en PRODUCCIÓN, así que no valía la pena una recuperación forense de los .ibd corruptos. Base limpia + migraciones = sistema operativo de inmediato.
- **Estado final:** MySQL arranca estable, information_schema ya no crashea, salcom20 con 38 tablas y datos de seeders (1 admin, 7 proveedores, 2 clientes, 8 productos). Bases viejas laravel/miamolchitodb quedaron en CORRUPTO_* (no se recuperaron; no eran del proyecto Salcom).
- **Dónde:** `C:\xampp\mysql\bin\my.ini` (buffer pool + nota), `C:\xampp\mysql\CORRUPTO_20260922_103917` (resguardo), `data_backup_20260922_093109` (backup inicial completo).
- **OJO producción:** esto fue SOLO local (XAMPP en la laptop). Producción (SiteGround) NO se tocó ni se ve afectada.

## 2026-09-22 — Confirmado: concepto 21 = facturas de compra (Wiese) + pendientes de proveedores
- **Qué:** se confirmó en Swagger que `/Documento/ListaDocumentosOCPorProveedor` con `strIdConceptosOC=21` (concepto "Compra") devuelve las FACTURAS DE COMPRA del proveedor (no las OC). Probado con ORPACK (`M213015002`): cientos de facturas ene–sep 2026. Cada factura trae `ciddocumentoorigen` = la OC interna que la respalda, y `cpendiente` = saldo por pagar (0 = pagada; >0 = pendiente). Coincide con lo que describió Karen: Factura → OC → Póliza (la póliza la hace Andrea a mano y NO viene en Wiese).
- **Por qué:** para poder mostrar facturas de proveedor reales (total, pendiente, vencimiento) usando el mismo endpoint de las OC, solo cambiando el concepto.
- **Dónde:** aún NO implementado en código; solo confirmado en Swagger. El servicio actual (`ProveedorApiService::listarDocumentosOCPorProveedorFechas`) itera conceptos de OC (19,2004,3015,3151,3130) — habría que decidir si se agrega el 21 para facturas de compra.

## 2026-09-22 — PENDIENTES ABIERTOS (traer TODOS los proveedores de Wiese)
- **Qué falta:** hoy NO existe método para "listar todos los proveedores de Wiese". Solo hay búsqueda de a UNO (`buscarProveedorWiese` por código/RFC). La lista del admin (`/admin/proveedores`) sale de la BD local (`ProveedorUser`), no de Wiese.
- **Decisión pendiente (preguntar a Alan):**
  - Camino A: si Wiese tiene endpoint tipo `ClienteProveedor/Listar`/"ListarTodos" → programar un método que traiga todos.
  - Camino B: si no existe → cargar proveedores con los códigos Wiese que dé Alan (uno por uno), como ORPACK.
- **Bloqueo de red (recordatorio):** producción (SiteGround) NO alcanza la IP interna de Wiese `172.16.1.250:7186` (timeout). Requiere VPN site-to-site / whitelist por infraestructura. Subir código NO basta para que salgan los proveedores de Wiese en producción.

## 2026-09-22 — Autocompletar datos de tarjeta al teclear número de empleado
- **Qué:** en el formulario de Reembolsos (admin), al escribir el número de empleado se llenan solos "Solicitante", "Número de cuenta" y "Titular de la tarjeta" desde la tabla `empleados`.
- **Por qué:** los datos bancarios ya están dados de alta una vez; re-escribirlos causa errores de captura. Se jala vía fetch a un endpoint JSON con debounce de 400ms.
- **Dónde:** `PortalEmpleadoController::buscarPorNumero`, ruta `admin.empleados.buscar`, JS al final de `resources/views/admin/reembolsos.blade.php`.

## 2026-09-22 — Panel "Administrador" renombrado a "Dirección" + control de accesos
- **Qué:** el panel admin ahora se llama "Dirección". Acceso total: fredcominu, alex.salazar, jesus.espinoza, sandra.gutierrez, rebeca (más roles gerente/admin). Brenda tiene acceso RESTRINGIDO: solo Productos y Anticipos; todo lo demás bloqueado (menú oculto + bloqueo por ruta).
- **Por qué:** dirección definió quién ve qué. Brenda solo gestiona productos y anticipos.
- **Dónde:** `AdminUser::esDireccion()/esRestringido()` con listas `USUARIOS_DIRECCION`/`USUARIOS_RESTRINGIDOS`; bloqueo centralizado en `AutenticacionAdmin` (revisa la ruta si el user es restringido); vistas comparten `$adminEsDireccion`/`$adminEsRestringido`; menú en `layouts/admin.blade.php` con `@if($adminEsDireccion)`. Título cambiado a "Dirección".

## 2026-09-22 — Empleado ahora crea sus Reembolsos de Viaje desde su portal
- **Qué:** se agregó botón "+ Nuevo viaje" en el portal del empleado y una pantalla propia con formulario dinámico (país, moneda, tipo de cambio, gastos múltiples con conversión a MXN en vivo). El empleado ya puede crear los 3 módulos: reembolso, gasolina y viaje.
- **Por qué:** los promotores/vendedores gestionan sus propios viáticos; no depender de que el admin capture. El código de empleado y departamento se toman de la sesión, no se re-piden.
- **Dónde:** `PortalEmpleadoController::crearViaje/guardarViaje`, vista `resources/views/empleados/viaje-crear.blade.php`, rutas `empleados.viaje.crear` y `empleados.viaje.guardar` en `routes/web.php`.

## 2026-09-22 — Portal de Empleados con auto-registro de reembolsos/gasolina
- **Qué:** los empleados ahora pueden ENTRAR a su propio portal (login solo con número de empleado, sin contraseña) y REGISTRAR ellos mismos sus reembolsos y su bitácora de gasolina desde modales, además de consultarlos. Antes solo el admin capturaba.
- **Por qué:** dirección pidió que el personal de ventas/promotores gestione sus propios gastos. El número de cuenta y titular de tarjeta se jalan automáticamente del alta del empleado para no re-capturarlos.
- **Dónde:** `PortalEmpleadoController` (`guardarGasolina`, `guardarReembolso`, `portal`), vista `resources/views/empleados/portal.blade.php` (modales), rutas `empleados.gasolina.guardar` y `empleados.reembolso.guardar` en `routes/web.php`.

## 2026-09-22 — Alta de Empleados en admin + regla de bloqueo por gasolina
- **Qué:** módulo admin para dar de alta empleados (número, nombre, departamento, cuenta de tarjeta, titular). Checkbox "es de ruta/gasolina": si está marcado, el empleado NO puede pedir reembolsos hasta registrar al menos una bitácora de gasolina.
- **Por qué:** el personal de ruta debe comprobar su consumo de gasolina antes de reembolsar. Regla operativa de dirección.
- **Dónde:** `PortalEmpleadoController::adminGuardar/adminActualizar`, modelo `App\Models\Empleado` (campo `requiere_gasolina`), migraciones `2026_09_04_*` y `2026_09_05_*`, validación en `AdminPanelController::enviarReembolso`.

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
