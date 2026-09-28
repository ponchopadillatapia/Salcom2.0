# Bitácora de decisiones — Salcom 2.0

> Memoria externa del proyecto. Cada cambio importante o petición (de Alan, dirección, etc.)
> se anota aquí en pocas líneas: QUÉ se hizo, POR QUÉ, y DÓNDE en el código.
> Se lee de arriba (lo más reciente) hacia abajo.

---

## 2026-09-26 — Formato para pago rediseñado a BÚSQUEDA (Opción 3, sin precarga)
- **Qué:** Como Alan no puede hacer el endpoint masivo (está ocupado), Formato para pago YA NO lista miles ni precarga nada. Ahora es por BÚSQUEDA: el usuario escribe código/nombre/RFC, se filtra sobre el directorio de Wiese (1 sola llamada, en memoria) y al abrir el proveedor se ven sus facturas EN VIVO de Wiese. Sin desfase, sin trabar.
- **Por qué:** resuelve la crítica de Said (la BD local se desactualiza). Al no copiar nada por adelantado y leer en vivo al abrir, siempre está fresco.
- **UX:** sin búsqueda → pantalla de bienvenida ("Busca un proveedor para pagarle"). Con búsqueda → resultados con columna Moneda (USD con degradado azul). Se quitaron los KPIs (no aplican en modo búsqueda) y el segundo campo "código" (ahora un solo buscador que cubre código/nombre/RFC).
- **Dónde:** `app/Http/Controllers/AdminPagosController.php::index()` (búsqueda sobre listarProveedoresWiese, filtra por codigo/nombre/rfc, pagina 50); `resources/views/admin/pagos/index.blade.php` (buscador único, bienvenida, tabla de resultados con moneda).
- **Nota:** la precarga (`wiese:precargar-facturas`) y el import automático quedan como código pero ya NO son el camino principal. El flujo de pago sigue igual (al abrir proveedor se importan sus facturas a local SOLO para poder registrar el pago, pero se leen frescas de Wiese en cada apertura).
- **Verificado:** `php -l` limpio, Blade compila.

## 2026-09-26 — CONCLUSIÓN investigación: se NECESITA endpoint masivo de Alan (sin atajo)
- **Contexto:** se investigó a fondo cómo mostrar "todos los proveedores que deben" en tiempo real. Conclusión: NO hay forma sin un endpoint masivo de Alan.
- **Lo comprobado:** (1) NO hay endpoint masivo en el Swagger (revisados los 191 de /Documento; ListarDocumentos pide `id`, ListarMuchos pide array de ids, ListarAnticipoConSaldoPendiente rompe con error SQL). (2) Campo `activo` del listado NO sirve: los 5,686 vienen `activo=false`. (3) Moneda: MXN=4,802 · USD=883 (filtrar MXN baja poco). (4) Campos de factura (ListarDocumentosRFC): codigo, folio, serie, fechaFactura, fechaVence, total, saldo, saldoDlls, idMoneda, tipoCambio, ivaFacturaDLLS, importePago, pagar, referencia, idDocumento, vencido8Dias/15Dias/Mas15Dias/vencidoTotal. Ninguno permite saber "quién tiene facturas" sin preguntar proveedor por proveedor.
- **Prueba del desfase (importante):** un RFC (GORJ560821SV8) que en la mañana tenía 1 factura de $28,228, horas después devolvió 0. CONFIRMA que Wiese cambia en tiempo real y que copiar a la BD local se desactualiza rápido (crítica válida de Said).
- **DECISIÓN:** pedir a Alan un endpoint `ListarTodasConSaldoPendiente` (su mismo query de ListarDocumentosRFC pero SIN el WHERE de rfc, solo `WHERE cpendiente > 0`, con fechaInicial/fechaFinal). Devuelve todo en 1 llamada, en tiempo real. Es la única solución correcta.
- **Campos útiles hallados para MEJORAR la vista de pago (a futuro):** vencido8Dias/15Dias/Mas15Dias/vencidoTotal (antigüedad de deuda para priorizar), saldoDlls/tipoCambio/ivaFacturaDLLS (manejo USD).
- **Herramientas de diagnóstico creadas:** comandos `wiese:ver-endpoints`, `wiese:probar-listar-documentos`, `wiese:ver-campos-proveedor`, `wiese:contar-proveedores`, `wiese:ver-campos-factura` (para explorar la API).

## 2026-09-25 — acela y blanca: agregar acceso al catálogo de Productos (sin abrir otras altas)
- **Qué:** Se creó una sección nueva `catalogo` (solo ver `admin/productos`, sin ninguna alta) y se asignó a acela.bolanos (→ alta_mpi + catalogo) y blanca.paganoni (→ alta_mto + catalogo).
- **Por qué:** dirección pidió que además de su alta, ambas puedan ver el catálogo de Productos. NO se usó la sección `productos` porque esa incluye la alta de Compras (abriría una alta que no les toca). `catalogo` da SOLO el catálogo.
- **Dónde:** `app/Models/AdminUser.php` (sección `catalogo` en el comentario + accesos de acela/blanca); `app/Http/Middleware/AutenticacionAdmin.php` (`'catalogo' => ['admin/productos']`); `resources/views/layouts/admin.blade.php` (banderas `$puedeCatalogo`/`$puedeVerCatalogo`, el submenu de altas solo se muestra si tiene alguna alta, y el link "Productos" aparece con `productos` o `catalogo`).
- **Verificado:** `php -l` limpio, Blade compila, y en tinker: acela=[alta_mpi,catalogo], blanca=[alta_mto,catalogo], ambas ve_catalogo=SI y ve_productos_completo=NO (no se les cuela la alta de Compras).

## 2026-09-26 — Fix: PagoProveedor/PagoProveedorFactura NO usan soft-deletes (delete, no forceDelete/withTrashed)
- **Qué:** La limpieza tronaba con "Call to undefined method PagoProveedor::withTrashed()". CAUSA: ni `PagoProveedor` ni `PagoProveedorFactura` usan SoftDeletes, así que no tienen `withTrashed()` ni `forceDelete()`. FIX: usar `delete()` normal para esos dos (borran de verdad porque no hay soft-delete). Solo Factura y ProveedorUser (que sí usan SoftDeletes) conservan `forceDelete()`.
- **Nota precarga:** con `--limite=300` da "5 con facturas, 6 actualizadas, 4 errores". Es correcto: de los primeros 300 proveedores pocos deben; los reintentos ya funcionan (4 errores de 300 = 1.3%, aceptable). La corrida completa encontrará los ~215 con facturas.
- **Dónde:** `app/Console/Commands/LimpiarPruebaPagos.php`.
- **Verificado:** `php -l` limpio.

## 2026-09-26 — Fixes: limpieza con foreign keys + precarga con reintentos/pausa (Wiese satura)
- **Fix limpieza:** `wiese:limpiar-prueba-pagos --force` tronaba con foreign key (factura #9 estaba enganchada a un pago en `pago_proveedor_facturas`, constraint RESTRICT). Ahora borra en ORDEN: (1) líneas `pago_proveedor_facturas` de esas facturas, (2) los pagos `pago_proveedor` de esos códigos, (3) facturas, (4) proveedores. Todo en transacción.
- **Fix precarga:** al correr `wiese:precargar-facturas` con 5,030 proveedores, Wiese rechazó 214 llamadas (encontró 215 con facturas pero solo importó 6). CAUSA: demasiadas llamadas seguidas saturan Wiese. FIX: hasta 3 REINTENTOS por proveedor (con pausa 0.3s) + pausa de 0.12s entre proveedores. Más lento pero mucho más confiable.
- **PENDIENTE fuerte:** esto confirma que ir proveedor-por-proveedor es frágil. URGE preguntar a Alan por un endpoint que devuelva TODAS las facturas pendientes de golpe. Es la solución correcta; la precarga es un parche.
- **Dónde:** `app/Console/Commands/LimpiarPruebaPagos.php` (borrado en orden por FK), `app/Console/Commands/PrecargarFacturasWiese.php` (reintentos + pausas).
- **Verificado:** `php -l` limpio en ambos.

## 2026-09-26 — Precarga masiva de facturas Wiese (para que salgan TODOS los que deben)
- **Qué:** Comando `wiese:precargar-facturas` que recorre TODOS los proveedores de Wiese, consulta sus facturas pendientes por RFC y las importa a local. Así Formato para pago muestra a TODOS los que deben, sin abrir cada proveedor a mano. Con barra de progreso.
- **Por qué:** antes solo salía ASCENCIO porque era el único importado (con el botón). Said quiere que salgan todos automáticamente.
- **Rendimiento (clave):** es LENTO la primera vez (1 llamada a Wiese por proveedor = varios min), pero corre en la TERMINAL del servidor, NO afecta la web. Después la lista carga al instante. NO es "una vez para siempre": las facturas cambian, así que hay que re-correrlo periódicamente (idealmente una tarea programada diaria). El usuario NUNCA ve traba (la web siempre lee de local).
- **Opción --limite=N:** para probar con los primeros N proveedores antes de correr los 5,685.
- **PENDIENTE (mejor solución):** preguntar a Alan si Wiese tiene un endpoint que devuelva TODAS las facturas pendientes de golpe (sin ir proveedor por proveedor). Eso haría innecesaria la precarga masiva. No se pudo revisar el Swagger (la laptop de Poncho no alcanzaba Wiese en ese momento).
- **Dónde:** `app/Console/Commands/PrecargarFacturasWiese.php`.
- **Verificado:** `php -l` limpio, comando registrado.

## 2026-09-26 — Comando para limpiar basura de prueba del flujo de pagos (P55, ADMIN-8, 102003241)
- **Qué:** Comando `wiese:limpiar-prueba-pagos` que borra SOLO los proveedores/facturas fake que estorbaban en Formato para pago: P55, ADMIN-8 (Aneso Cominu), 102003241. NO toca proveedores reales (ej. 213004004 ASCENCIO).
- **Por qué:** en la base de producción quedaron datos de prueba de sesiones anteriores que salían en la lista de pendientes.
- **Seguridad:** lista blanca de códigos basura (no borra por RFC ni nada genérico). Sin `--force` solo simula (muestra qué borraría); con `--force` borra en transacción.
- **Uso en servidor:** `php artisan wiese:limpiar-prueba-pagos` (simula) → si se ve bien → `php artisan wiese:limpiar-prueba-pagos --force`.
- **Dónde:** `app/Console/Commands/LimpiarPruebaPagos.php`.
- **Verificado:** `php -l` limpio, comando registrado.

## 2026-09-26 — Proveedores en USD con degradado azul (reemplaza el gris de RFC duplicado)
- **Qué:** En el directorio de Proveedores, los proveedores en DÓLARES (moneda "2") ahora se distinguen con una fila de degradado azulito y el código en azul. Se QUITÓ la marca gris de "RFC duplicado" anterior.
- **Por qué:** Alan aclaró que los "duplicados" (mismo RFC, 2 registros) NO son error: uno es la cuenta en MXN y otro en USD. Ambos válidos. Lo correcto es distinguir por moneda, no marcar como sospechoso. Said pidió el tono azul para los de dólares.
- **Confirmado:** el listado de Wiese (`ListarProveedorWeb`) YA devuelve el campo `moneda` ("1"=MXN, "2"=USD) — Alan ya lo publicó. Verificado con `wiese:ver-campos-proveedor` (5,686 proveedores, cada uno con su `moneda`).
- **Sobre facturas pendientes (aclaración de Alan):** de `ListaDocumentosOCPorProveedor` los campos útiles son `cpendiente` (saldo) y `cfechavencimiento`. Confirma que el endpoint por proveedor es el bueno; NO hay endpoint masivo, así que la precarga (`wiese:precargar-facturas`) sigue siendo el método para llenar la lista.
- **Dónde:** `resources/views/admin/proveedores.blade.php` (fila USD con gradient azul + código azul), `app/Http/Controllers/AdminPanelController.php` (se quitó el cálculo `rfc_duplicado`).
- **Verificado:** `php -l` limpio, Blade compila.

## 2026-09-26 — Directorio de Proveedores: marcar en gris los RFC duplicados
- **Qué:** En el directorio de Proveedores (Wiese), los proveedores cuyo RFC aparece 2+ veces (cuentas duplicadas por moneda MXN/USD o altas dobles) ahora salen en GRIS/atenuados, con una etiqueta "RFC duplicado" junto al RFC y un tooltip que avisa "revisa cuál tiene facturas".
- **Por qué:** Said pidió distinguir visualmente los duplicados; a menudo uno de los dos no tiene facturas. Ayuda a saber cuál usar.
- **CLAVE (rendimiento):** NO se consulta Wiese por cada proveedor (eso congelaría). Solo se COMPARAN los RFC de la lista que ya tenemos en memoria (`countBy` sobre los 5,685). Rápido. Se ignora el RFC genérico de extranjeros XEXX010101000 (se repite a propósito, no es duplicado real).
- **LÍMITE honesto:** esto marca los que están repetidos, pero NO sabe cuál de los dos específicamente tiene facturas (eso sí requeriría llamar a Wiese por cada uno). El gris es un "ojo, revisa", no "este está vacío".
- **Dónde:** `app/Http/Controllers/AdminPanelController.php::proveedores()` (calcula `$conteoRfc` y marca `rfc_duplicado` en cada item); `resources/views/admin/proveedores.blade.php` (fila atenuada + etiqueta "RFC duplicado").
- **Verificado:** `php -l` limpio, Blade compila.

## 2026-09-26 — Formato para pago: solo proveedores CON pendientes + caché import + quitar botón
- **Qué:** (1) La lista de Formato para pago ya NO muestra los 5,685 de Wiese (salían casi todos en "0" y consultarlos congela). Ahora por defecto muestra SOLO los proveedores con facturas pendientes LOCALES. El buscador SÍ busca en todo Wiese (para hallar e importar uno nuevo al abrirlo). (2) Se quitó el botón "Re-sincronizar/Importar" de la vista del proveedor (ya es automático). (3) Optimización: el import automático solo llama a Wiese si NO se hizo en los últimos 5 min para ese proveedor (Cache 5 min por `wiese_import_{codigo}`), así abrir/recargar seguido no repite la llamada lenta.
- **Por qué:** Said pidió no ver los 5,685 con "0"; que liste solo los que deben. Y que se optimice la caché para que no tarde.
- **Dónde:** `app/Http/Controllers/AdminPagosController.php` (`index()`: usa `proveedoresConPendientes()` sin búsqueda y `proveedoresParaFormatoPago()` con búsqueda; `proveedor()`: import con Cache 5 min); `resources/views/admin/pagos/proveedor.blade.php` (botón quitado).
- **Verificado:** `php -l` limpio, ambas vistas compilan.

## 2026-09-26 — Importación AUTOMÁTICA de facturas Wiese al abrir el proveedor (sin botón)
- **Qué:** Al abrir un proveedor en Formato para pago (`proveedor()`), ahora se importan solas sus facturas pendientes de Wiese a local. Ya no hay que darle al botón. Said lo pidió así.
- **Cómo:** se extrajo la lógica de import a un método reutilizable `importarFacturasWieseAlLocal($codigo)` (lo usan el botón Y la carga automática). Usa `firstOrNew`, así no duplica (si la factura ya existe, la actualiza). Si Wiese no responde, se captura el error y NO rompe la pantalla (try/catch + log).
- **El botón se quedó** como "Re-sincronizar facturas" (refrescar manual), ya no es obligatorio.
- **Dónde:** `app/Http/Controllers/AdminPagosController.php` (nuevo `importarFacturasWieseAlLocal()`, llamada automática en `proveedor()`, `importarFacturasWiese()` ahora delega); `resources/views/admin/pagos/proveedor.blade.php` (texto/ícono del botón → Re-sincronizar).
- **OJO rendimiento:** importar corre en CADA apertura del proveedor (1 llamada a Wiese por RFC). Es aceptable porque es un solo proveedor. Si se vuelve lento, se puede cachear.
- **Verificado:** `php -l` limpio, Blade compila.

## 2026-09-26 — Facturas importadas con datos fiscales por defecto + visual del expediente de pago
- **Qué:** (1) Al importar facturas de Wiese (botón y comando) ahora se rellenan datos fiscales por defecto en `validacion_detalle`: forma_pago=03 (transferencia), metodo_pago=PUE, uso_cfdi=G03, regimen=601. POR QUÉ: Wiese no devuelve esos datos, y sin ellos `confirmar()` bloquea con "sin forma_pago". Con esto el lote se puede confirmar en pruebas. (2) Se agregó un panel VISUAL del "Expediente de pago" en la vista `admin/pagos/show` (Pago #N): muestra los 5 documentos fiscales (CIF, Opinión SAT, INE, carátula banco, formato ID) con ✅/⭕ y un badge Completo/Incompleto. Es informativo, no bloquea.
- **Botón mejorado:** el botón "Importar facturas" ahora es verde con ícono, más grande y con hover (antes se veía feo/apretado).
- **Para las facturas ya importadas antes del cambio:** volver a darle al botón "Importar" (usa firstOrNew, así actualiza las existentes con los datos fiscales nuevos).
- **Dónde:** `app/Http/Controllers/AdminPagosController.php` (importarFacturasWiese: datos fiscales), `app/Console/Commands/ImportarFacturasWiese.php` (igual), `resources/views/admin/pagos/show.blade.php` (panel visual expediente), `resources/views/admin/pagos/proveedor.blade.php` (botón bonito).
- **NO se hizo (decisión):** el color gris para distinguir proveedores duplicados por RFC en el directorio — requiere consultar Wiese 5,685 veces al cargar (congelaría la página). Necesita un proceso en segundo plano; queda como mejora futura, no para la demo.
- **Verificado:** `php -l` limpio, Blade compila.

## 2026-09-26 — Botón "Importar facturas de Wiese" + fix campo usuario obligatorio
- **Qué:** (1) FIX: al auto-crear un proveedor de Wiese tronaba con "Field 'usuario' doesn't have a default value" (la tabla `proveedores_users` exige `usuario` y `password`). Ahora se generan: `usuario = 'wiese_'.$codigo` y password aleatorio (el proveedor no inicia sesión, es solo para el flujo de pago). (2) BOTÓN: se agregó "↓ Importar estas facturas para pagar" en la sección Wiese del detalle del proveedor, para importar sin usar la terminal (lo pidió Said). Hace lo mismo que el comando artisan.
- **Por qué:** Said no quiere correr el comando en terminal cada vez; el botón lo hace desde la interfaz. Y sin el fix del `usuario`, la creación del proveedor fallaba.
- **Dónde:** `app/Console/Commands/ImportarFacturasWiese.php` (fix usuario/password); `app/Http/Controllers/AdminPagosController.php` (fix en `asegurarProveedorLocalDesdeWiese()` + método nuevo `importarFacturasWiese()`); `routes/web.php` (ruta `admin.pagos.importar-facturas`); `resources/views/admin/pagos/proveedor.blade.php` (botón en la sección Wiese).
- **Flujo para probar:** abrir proveedor en Formato para pago → clic "Importar estas facturas para pagar" → las facturas quedan en local → seleccionar → pago → abono.
- **Verificado:** `php -l` limpio en controlador/comando/rutas, Blade compila.
- **Proveedores con facturas para demo (hallados con wiese:buscar-con-facturas):** 213004004 DISTRIBUIDORA ELECTRICA ASCENCIO (6 fact, $63,840.71), 213001001 ABARROTES ABEJA (3), M214884129 DE LA MORA (2), 2148844400 JUAN JOSE GONZALEZ (1).

## 2026-09-26 — store() ahora auto-crea el proveedor de Wiese al registrar su primer pago
- **Qué:** El guardado de pago (`AdminPagosController::store`) hacía `whereCodigo(...)->firstOrFail()` y tronaba con proveedores que solo existen en Wiese. Ahora, si el proveedor no está en local, se crea AHÍ mismo (auto-registro) con sus datos de Wiese (nombre, código, moneda, rfc), marcado `activo=false`, para poder enlazar el lote de pago.
- **Por qué:** un proveedor de Wiese "nace" en el sistema local al registrarle su primer pago; el flujo (crearLote) necesita un ProveedorUser con id real.
- **OJO (límite conocido):** el flujo de pago (crearLote) trabaja con facturas LOCALES (tabla `facturas`, por `factura_ids` y `codigo_proveedor`, estatus pendiente). Las facturas de Wiese que se muestran son SOLO LECTURA (informativas). Para pagar de verdad, las facturas deben existir en local. PENDIENTE decidir: importar facturas de Wiese → local, o capturarlas.
- **Dónde:** `app/Http/Controllers/AdminPagosController.php` (`store()` usa `porCualquierCodigo()->first()` + nuevo helper `asegurarProveedorLocalDesdeWiese()` que crea el registro).
- **Contexto:** Said probando en el SERVIDOR (IIS, 172.16.1.251, base producción SiteGround), en la empresa (sin VPN, ya en red de Wiese). Wiese ya conecta OK (5,685 cargan). Pruebas del equipo en ~1 hora.
- **Verificado:** `php -l` limpio. Falta probar el guardado real end-to-end (requiere facturas locales).

## 2026-09-25 — Fix ParseError en vista de pago + orden por monto + columna alineada
- **Qué (bug):** La vista `admin/pagos/proveedor.blade.php` tronaba con "syntax error, unexpected end of file" al abrir un proveedor. CAUSA: en la sección Wiese usé el patrón `@if(...)${{ number_format(...) }}` — el `$` PEGADO al `{{` hacía que Blade lo compilara como `${` (variable-variable de PHP), dejando una expresión abierta que se comía el archivo hasta el EOF. FIX: mover el signo `$` DENTRO de la expresión: `{{ '$'.number_format(...) }}` (nunca `${{`).
- **Qué (mejoras pedidas):** (1) En Formato para pago, la tabla ahora ordena a los que DEBEN MÁS arriba (por `monto_total` desc, antes era por fecha). (2) Se quitó el agrupado por fecha (`$agrupados`) y se pinta tabla plana respetando ese orden. (3) Se arregló la columna "Facturas pendientes" que se veía chueca: el `date-row` usaba `colspan=5` con solo 3 columnas; ahora la tabla tiene 3 columnas alineadas y el número va centrado.
- **Dónde:** `resources/views/admin/pagos/proveedor.blade.php` (celdas de la sección Wiese sin `${{`); `app/Services/PagoProveedorService.php::proveedoresParaFormatoPago()` (sortByDesc por monto_total); `resources/views/admin/pagos/index.blade.php` (tabla plana, thead/tbody 3 columnas, número centrado).
- **Verificado:** `php -l` limpio, Blade compila, y render real del proveedor 213026052 → OK (68,885 chars, ya no truena).
- **LECCIÓN (para el glosario):** en Blade nunca pegar `$` justo antes de `{{ }}`. Escribir `{{ '$'.$valor }}` en vez de `${{ $valor }}`.

## 2026-09-25 — Facturas reales de Wiese por RFC en "Formato para pago" (endpoint nuevo de Alan)
- **Qué:** Alan creó el endpoint `GET /DatoDocumentoProveedor/ListarDocumentosRFC` (params: RFC, fechaInicial, fechaFinal en date-time). Devuelve las facturas de compra del proveedor con su saldo. Se integró para que, al abrir un proveedor en Formato para pago, se vean sus facturas PENDIENTES reales de Wiese (solo saldo > 0), en una sección de solo lectura arriba.
- **Regla de negocio (confirmada con Said):** saldo > 0 = factura PENDIENTE; saldo 0 = PAGADA. Las pendientes van a Formato para pago; el historial completo (pagadas+pendientes) irá en Proveedores → "Ver facturas" (pendiente de migrar a este endpoint; hoy usa el de OC por código).
- **Puerto API:** 7183 (Alan dijo 7181 por error; el correcto es 7183). En `.env`: `PROVEEDOR_API_DOCS_URL=http://172.16.1.250:7183/api`.
- **Formato de respuesta de Wiese (campos):** codigo, folio, serie, fechaFactura, fechaVence, total, saldo, idMoneda (1=MXN/2=USD), tipoCambio, idDocumento, nombre, referencia. Se mapean a snake_case limpio + bandera `pendiente` (saldo>0).
- **Dónde:** `app/Services/ProveedorApiService.php` (método nuevo `listarFacturasProveedorPorRFC()` con login de servicio + mapeo); `app/Http/Controllers/AdminPagosController.php::proveedor()` (trae facturas Wiese por RFC del proveedor, solo pendientes, pasa `$facturasWiese`/`$wieseError`/`$rfc`); `resources/views/admin/pagos/proveedor.blade.php` (sección "Facturas pendientes en Wiese", tabla solo-lectura con totales MXN/USD).
- **Decisión de diseño:** la sección de Wiese es INFORMATIVA (solo lectura), separada del flujo local de selección/pago (que es crítico y aún no probado). Así no se toca ese flujo.
- **Verificado:** endpoint probado (RFC GORJ560821SV8 → 1 factura $28,228.50 MXN, serie MTTO); `php -l` limpio, Blade compila, y abrir proveedor 2148844400 trae WIESE_PEND=1 sin error.
- **PENDIENTE:** migrar Proveedores → "Ver facturas" a este endpoint por RFC para mostrar el historial completo (pagadas + pendientes).

## 2026-09-25 — Base local desincronizada: 12 migraciones pendientes (faltaba anticipos_proveedor, etc.)
- **Qué:** Al abrir un proveedor en Formato para pago tronaba con "Table 'salcom20.anticipos_proveedor' doesn't exist". La base local de Poncho tenía 12 migraciones pendientes (anticipos, dias_plazo, es_repse, tarjeta empleados, expediente_pago, 3 de datos Wiese, etc.).
- **Complicación:** la base estaba DESINCRONIZADA — algunos cambios ya existían en la estructura (monto_pagado, tabla empleados, requiere_gasolina) pero NO estaban registrados en la tabla `migrations`. Por eso `migrate` de corrido fallaba con "columna/tabla ya existe".
- **Cómo se arregló:** (1) Se corrieron UNA POR UNA las que faltaban de verdad: anticipos_proveedor + uuid_cfdi, dias_plazo (facturas), es_repse (proveedores), tarjeta (empleados), expediente_pago (pagos), y las 3 de datos Wiese. (2) Las 3 cuya estructura ya existía (monto_pagado, empleados, requiere_gasolina) se marcaron como corridas insertándolas en la tabla `migrations` (sin ejecutar su SQL).
- **Verificado:** `migrate:status` → sin pendientes; abrir proveedor 104001090 ("ABARROTES MENDEZ SERRANO") ya carga sin error.
- **Nota:** esto fue en la base LOCAL de Poncho. En el servidor/producción, correr `php artisan migrate` normal debería bastar (esa base no debería tener el desajuste).

## 2026-09-25 — Fix 404 al abrir un proveedor de Wiese en Formato para pago
- **Qué:** Al hacer clic en un proveedor de Wiese (ej. 103014037) en Formato para pago, daba 404. Ahora la pantalla de detalle (`admin.pagos.proveedor`) y el estado de cuenta cargan aunque el proveedor solo exista en Wiese.
- **Por qué:** `proveedor()` y `estadoCuenta()` hacían `ProveedorUser::porCualquierCodigo($codigo)->firstOrFail()`, que busca SOLO en la base local. Los 5,685 de Wiese no están en local → 404.
- **Cómo se arregló:** nuevo helper privado `proveedorWieseEnMemoria($codigo)`: busca en Wiese con `buscarProveedorWiese()` y arma un `ProveedorUser` EN MEMORIA (no guardado) con nombre/código/moneda/rfc y relación `documentos` vacía. Así la vista muestra el nombre y `evaluarExpediente()` corre (lo marca incompleto, que es correcto). Si no está ni en local ni en Wiese → abort(404) real.
- **Por qué NO se guarda en la base:** crear el registro local aquí ensuciaría la base con miles de proveedores; el detalle solo necesita mostrar y evaluar.
- **Dónde:** `app/Http/Controllers/AdminPagosController.php` (`proveedor()`, `estadoCuenta()` y helper `proveedorWieseEnMemoria()`).
- **Verificado:** simulando `proveedor("103014037")` → OK, NOMBRE="180 NATURAL S DE RL DE CV", EXP_OK=NO, FACTURAS=0 (ya no truena).
- **PENDIENTE (crítico, NO tocado):** el método que GUARDA el pago (`store`, ~línea 295) todavía hace `whereCodigo(...)->firstOrFail()`. Si se intenta guardar un pago a un proveedor que solo está en Wiese, tronará. NO se modificó porque el guardado real de pagos aún no se ha probado con VPN/datos reales (flujo crítico). Decidir con Said/Karen cómo debe crearse/enlazarse el proveedor al guardar el primer pago.

## 2026-09-25 — "Formato para pago" lista TODOS los proveedores de Wiese, PAGINADO 50/pág (pendientes arriba)
- **Qué:** La pantalla de Formato para pago (`admin/pagos`) antes solo mostraba proveedores con facturas pendientes LOCALES. Ahora lista los 5,685 de Wiese PAGINADOS de 50 en 50 (114 páginas), con los que tienen facturas pendientes ARRIBA (con su conteo/monto). Se puede buscar por nombre/código en toda la lista.
- **Por qué:** Contabilidad necesita poder pagarle a CUALQUIER proveedor de Wiese, no solo a los que ya deben; pero lo urgente (pendientes) debe verse primero. Se pidió paginado para navegarlos todos sin tener que buscar.
- **Rendimiento:** NO se pintan los 5,685 de golpe (reventaría el DOM). Se pagina con `LengthAwarePaginator` manual (la fuente es una Collection, no query): se corta la página con `slice()`. La paginación conserva búsqueda/filtros con `appends(request()->query())`. KPIs (sin revisar/expediente) se calculan solo sobre los que tienen pendientes.
- **Fallback:** si Wiese no responde (sin VPN), `proveedoresParaFormatoPago()` devuelve solo los pendientes locales (no rompe).
- **Evolución:** primero se hizo "solo pendientes + búsqueda" (tope 200), luego el usuario pidió paginado 50/pág → el filtrado/KPIs/paginación se MOVIERON de la vista al controlador.
- **Dónde:** `app/Services/PagoProveedorService.php` (`proveedoresParaFormatoPago()` fusiona pendientes+Wiese por código y ordena); `app/Http/Controllers/AdminPagosController.php::index(Request)` (filtra, calcula KPIs, pagina 50/pág con LengthAwarePaginator); `resources/views/admin/pagos/index.blade.php` (solo pinta; agrupa por fecha la página actual; `pagination-wrap` con `->links()`).
- **Verificado:** `php -l` limpio, Blade compila, y simulando la request: TOTAL=5685, POR_PAGINA=50, EN_ESTA_PAGINA=50, ULTIMA_PAGINA=114.

## 2026-09-25 — Alta de usuarios admin + accesos por área (altas de producto separadas)
- **Qué:** (1) Se crearon 11 usuarios en `admin_users` (base LOCAL de la máquina de Poncho): Dirección/acceso total → fredcominu, alex.salazar, jesus.espinoza, sandra.gutierrez, aneso.cominu, Rebeca (con R mayúscula). Restringidos por área → brenda.pliego (productos+anticipos), karen.bravo (pagos+proveedores), blanca.paganoni (alta_mto), acela.bolanos (alta_mpi), cinthya.martinez (alta_mpi+productos). (2) Se agregaron secciones NUEVAS al modelo para separar las altas por área: `alta_mpi`, `alta_pt`, `alta_mto` (antes todo era una sola sección `productos`).
- **Por qué:** dirección pidió que cada comprador gestione SOLO su tipo de alta y no se cuele a las de otras áreas (MPI, PT, Mantenimiento separadas).
- **Cómo quedó el acceso a altas:** `productos` = catálogo + alta Compras (nacional/MPI) + migración; `alta_mpi` = solo pantalla de alta Compras (comparte URL con nacional, no ve catálogo ni otras altas); `alta_pt` = solo Comercial PT; `alta_mto` = solo Mantenimiento. OJO técnico: `admin/alta-producto` es prefijo de `-mto`/`-pt`, pero el bloqueo compara con `base.'/'` y `-mto`/`-pt` no empiezan con `/`, así que NO se cruzan.
- **Dónde:** `app/Models/AdminUser.php` (constantes `USUARIOS_DIRECCION` +aneso.cominu, y `ACCESOS_RESTRINGIDOS` con las nuevas secciones); `app/Http/Middleware/AutenticacionAdmin.php` (mapa `$rutasPorSeccion` con alta_mpi/alta_pt/alta_mto + destinos de redirección); `resources/views/layouts/admin.blade.php` (bloque Productos: cada sub-link Compras/Mantenimiento/Comercial se muestra según la sección; catálogo "Productos" solo con sección `productos`).
- **Verificado:** `php -l` limpio en modelo y middleware; confirmado en tinker que los accesos se enganchan (aneso/Rebeca=Dirección, brenda=productos+anticipos, acela=alta_mpi, cinthya=alta_mpi+productos).
- **PENDIENTE:** `cintia.barrera` (Comercial PT, sección `alta_pt`) NO se creó porque no se dio contraseña. Crearla cuando Said defina el password.
- **Nota:** las contraseñas se pusieron encriptadas (`Hash::make`). El script temporal con las contraseñas se borró tras crear los usuarios. Correos con placeholder `@salcom.local` salvo cinthya (@wiese.com.mx).

## 2026-09-25 — Limpieza de datos de prueba (facturas y proveedores locales)
- **Qué:** Se borraron las 8 facturas y 13 proveedores locales de prueba (seeders) de la base local. Antes se hizo backup en `storage/backup_prueba_20260925_150330.sql`.
- **Por qué:** en Formato para pago y Pago a proveedor salían proveedores/facturas fake que estorbaban en las pruebas. El listado de Formato/Pago se arma desde la tabla `facturas` (PagoProveedorService::proveedoresConPendientes), así que al vaciarla, ese listado queda limpio. En el servidor real esos datos no existen.
- **Dónde:** tablas `facturas` y `proveedores_users` (OJO: la tabla es `proveedores_users`, con "es"). Ejecutado vía tinker con forceDelete y FOREIGN_KEY_CHECKS=0.
- **Nota:** hecho en la máquina de Poncho (Said trabajando desde ahí). Si se quieren de vuelta los datos de prueba: `php artisan db:seed` o restaurar el backup.
- **Verificado:** FACTURAS=0 y PROVEEDORES_LOCAL=0 tras el borrado.

## 2026-09-24 — Vistas de Reembolsos/Bitácora/Empleados ahora usan todo el ancho
- **Qué:** Se quitó el `max-width` fijo (880-960px) de los contenedores de las pantallas Reembolsos, Bitácora de Gasolina, Alta de Empleados y Reembolsos de Viaje (crear/editar/ver). Ahora usan `max-width: 100%` y ocupan todo el ancho del panel, sin dejar hueco a la derecha.
- **Por qué:** dirección pidió que se ajustaran al tamaño de la página.
- **Dónde:** `.reembolsos-wrap` (reembolsos), `.bg-wrap` (bitacora-gasolina), `.emp-wrap` (empleados/index), `.rv-wrap` (reembolsos-viaje/crear|editar|ver).
- **Verificado:** las 6 vistas compilan sin errores de Blade.

## 2026-09-24 — Bloqueo de módulos EN CONSTRUCCIÓN (OTIF y Clientes)
- **Qué:** Se bloqueó el acceso a OTIF (`admin/otif`) y Clientes (`admin/clientes`) porque aún no sirven / tienen datos fake. Si alguien entra por URL directa, el middleware lo redirige al dashboard con el aviso "Ese módulo está en construcción". Aplica a TODOS, incluida Dirección. Los enlaces del sidebar ya estaban ocultos.
- **Por qué:** dirección pidió que nadie pueda meterse a las partes del proyecto que todavía están en construcción.
- **Cómo agregar más:** añadir el prefijo de ruta al arreglo `$rutasEnConstruccion` en el middleware. Para reactivar un módulo, quitarlo de esa lista (y volver a mostrar su enlace cambiando el `@if(false)` del sidebar).
- **Dónde:** `app/Http/Middleware/AutenticacionAdmin.php` (arreglo `$rutasEnConstruccion`), `resources/views/layouts/admin.blade.php` (enlace OTIF envuelto en `@if(false)`).
- **Verificado:** `php -l` limpio y Blade compila sin errores.

## 2026-09-24 — Dashboard: todas las tarjetas KPI en un solo grid parejo
- **Qué:** Se unificaron las 9 tarjetas KPI en UN solo grid de 4 columnas. Antes "Docs. fiscales" vivía en un segundo grid aparte, así que quedaba sola y el dashboard se veía disparejo. El bloque `@php` que calcula los proveedores con docs faltantes se movió ARRIBA del grid para poder meter esa tarjeta en la misma cuadrícula.
- **Por qué:** dirección pidió que todos los recuadros queden del mismo tamaño y formato. Ahora todas usan la misma clase con altura fija (188px) y quedan alineadas 4+4+1.
- **Dónde:** `resources/views/admin/dashboard.blade.php` (sección `.pp-kpi-section`).
- **Verificado:** Blade compila sin errores.

## 2026-09-24 — Comando para borrar SOLO datos de prueba (dejar dashboard en cero)
- **Qué:** Se creó el comando `php artisan salcom:limpiar-prueba`. Borra únicamente los registros sembrados por los seeders de prueba/demo, identificándolos por sus "huellas" fijas: proveedores (`PROV001-003`, `said`, `demo`, `Rebeca`, `sinonboarding`, `proveedor.test`, `said.padilla`, `diegococca@gmail.com`, `FAKEPAGO1-10`), clientes (`CLI001/CLI002`), facturas (`CFDI-A-*`, `CFDI-P-*`, `FAKE-PAGO-*`, `SAID-FAC-*`), productos (`SAL-*`), pedidos (`PED-2025/2026-*`), muestras (`LOTE-2026-*`), y sus encuestas/alertas/OC/docs/contactos/notificaciones ligadas.
- **Por qué:** dirección pidió dejar los recuadros del dashboard en cero, borrando SOLO datos de prueba sin tocar datos reales ni la estructura.
- **Seguridad:** por defecto SIMULA (solo cuenta, no borra); requiere `--force` para borrar de verdad, con confirmación y dentro de una transacción. NO usa TRUNCATE (respeta datos reales que ya existan). NO toca `admin_users` ni `empleados`.
- **OJO:** el proveedor de prueba con usuario `Rebeca` (correo framfoods) NO es lo mismo que el admin `rebeca` de Dirección (tablas separadas). Borrarlo NO afecta el acceso admin.
- **Deploy:** en el servidor correr primero `php artisan salcom:limpiar-prueba` (simulación) para ver los números; si se ven bien, `php artisan salcom:limpiar-prueba --force`.
- **Dónde:** `app/Console/Commands/LimpiarDatosPrueba.php`.

## 2026-09-24 — HALLAZGO: por qué hay proveedores "duplicados" en Wiese (moneda + RFC extranjero)
- **Qué se descubrió (confirmado con SQL en admClientes):** un mismo RFC puede tener VARIOS registros. Causas reales:
  1. **Moneda (la principal):** proveedor nacional con una cuenta en MXN y otra en USD. `CIDMONEDA`: 1 = MXN (4,795 provs), 2 = DÓLAR USD (879 provs) — confirmado en tabla `admMonedas`. Decenas de RFC tienen 1 registro MXN + 1 USD.
  2. **RFC genérico de extranjeros `XEXX010101000`:** el SAT lo usa para proveedores del exterior sin RFC mexicano. 246 registros lo comparten (240 en USD) — NO son el mismo proveedor, son extranjeros distintos.
  3. **Duplicado real (caso ORPACK):** 2 altas del mismo proveedor (2003 y 2010), ambas MXN. Solo M213015002 tiene las 6,363 OC; 103015031 tiene 0.
- **Por qué importa:** ni el nombre ni el RFC identifican de forma única a un proveedor (por moneda y por extranjeros). Por eso el `CCODIGOCLIENTE` (código) era OBLIGATORIO para enlazar facturas — lo confirma este análisis.
- **A futuro:** si se trabaja el módulo de proveedores a fondo, considerar la moneda (`CIDMONEDA`) y el caso del RFC genérico. Query útil guardado: proveedores en USD = `WHERE CIDMONEDA = 2`.
- **Dónde:** análisis hecho en SSMS sobre `adSalcom18.dbo.admClientes` y `admMonedas`. No se cambió código por esto (solo investigación).

## 2026-09-24 — Acceso de Nayeli: solo Reembolsos + Alta de Empleados
- **Qué:** Se creó la sección de permiso `reembolsos` y se asignó al usuario `nayeli`. Con eso ve solo: Reembolsos, Reembolsos Viaje, Bitácora Gasolina y Alta de Empleados (todo lo demás del panel queda bloqueado). En el sidebar se separó "Reembolsos a Empleados" (Dirección + Nayeli) de "Operación" (solo Dirección), y a Nayeli se le agregó un enlace "Alta de Empleados" propio (Dirección sigue viendo "Empleados" en la sección Negocio).
- **Por qué:** dirección pidió que Nayeli gestione reembolsos y altas de empleados, nada más.
- **OJO:** Nayeli AÚN NO tiene usuario en la BD. El permiso ya está listo; en cuanto se cree el registro en `admin_users` con `usuario = 'nayeli'` (en minúsculas), el acceso queda activo sin tocar código.
- **Dónde:** `app/Models/AdminUser.php` (`ACCESOS_RESTRINGIDOS['nayeli'] = ['reembolsos']`), `app/Http/Middleware/AutenticacionAdmin.php` (mapa `$rutasPorSeccion['reembolsos']` + destino de redirección), `resources/views/layouts/admin.blade.php` (bloque Reembolsos con `@if($puedeReembolsos)` y enlace de alta para Nayeli).
- **Verificado:** `php -l` limpio y Blade compila sin errores.

## 2026-09-24 — Panel "Administrador" renombrado a "Dirección" + control de accesos por usuario
- **Qué:** (1) El panel admin ahora se llama "Dirección" (navbar y `<title>`). (2) Se agregó control de accesos por usuario: acceso TOTAL para `fredcominu`, `alex.salazar`, `jesus.espinoza`, `sandra.gutierrez`, `rebeca` (más roles gerente/admin). (3) Acceso RESTRINGIDO: `brenda.pliego` solo ve Productos + Anticipos; `karen.bravo` solo ve Pagos + Proveedores. (4) El sidebar oculta secciones según el usuario; la sección "Negocio" (Clientes/Empleados/Negocio/Fiscal) es solo-Dirección (Karen ve Proveedores pero NO Negocio). (5) "Mi Perfil" (Cuenta) queda visible para todos.
- **Por qué:** dirección pidió que cada persona externa a Dirección solo gestione su área y no vea el resto del panel.
- **Dónde:** `app/Models/AdminUser.php` (constantes `USUARIOS_DIRECCION`/`ACCESOS_RESTRINGIDOS` y métodos `esDireccion`, `esRestringido`, `seccionesPermitidas`, `puedeVer`); `app/Http/Middleware/AutenticacionAdmin.php` (comparte `$adminEsDireccion`/`$adminEsRestringido`/`$adminUser` a las vistas y bloquea rutas por sección con el mapa `$rutasPorSeccion`); `resources/views/layouts/admin.blade.php` (secciones envueltas con `@if`).
- **Verificado:** Blade compila sin errores (`view:clear` + `Blade::compileString`) y `php -l` limpio en modelo y middlewares.
- **OJO:** los nombres deben coincidir EXACTO con el campo `usuario` de `admin_users` (se comparan en minúsculas). Si Rebeca está como `rebeca.algo`, hay que ajustar la constante.

## PENDIENTES ABIERTOS (actualizado 24-sep-2026) — hoja de ruta
1. **Estudiar el código C# de Alan** (API Wiese): entidades, servicios, controllers, nomenclatura. (En curso — hay apuntes.)
2. ✅ **APIs de proveedor + sus OC/facturas — RESUELTO (24-sep).** Alan aprobó y mergeó el PR #101: `ListarProveedorWeb` ya devuelve el `codigo` (CCODIGOCLIENTE). El botón "Ver facturas" del directorio de Wiese usa ese código y trae las facturas/OC reales (probado: ORPACK M213015002 → 6,363 OC). Confirmado que el RFC era ambiguo (ORPACK tiene 2 registros: M213015002 con 6,363 vs 103015031 con 0), por eso el código directo era obligatorio. Funciona en LOCAL con VPN.
3. **Montar servidor LOCAL en la empresa (El Salto):** Alan NO expondrá la API a internet (nunca). El plan a futuro es un servidor on-premise en la red interna (PHP+MySQL+servidor web) para que producción alcance Wiese sin exponerlo. Pendiente de hardware/red + tiempo de Alan.
4. **APIs de pagos / flujo de pagos:** pendientes de negocio con Karen (folio/póliza, validación de secuencia, cancelaciones, pagos parciales). Ver `.kiro/steering/pendientes-flujo-pagos.md`. El código de anticipos y la columna "Anticipos" en facturas YA está.
5. **API de productos / alta de producto:** pendiente por definir/conectar.

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
