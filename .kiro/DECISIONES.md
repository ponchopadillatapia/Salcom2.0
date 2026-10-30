# Bitácora de decisiones — Salcom 2.0

> Memoria externa del proyecto. Cada cambio importante o petición (de Alan, dirección, etc.)
> se anota aquí en pocas líneas: QUÉ se hizo, POR QUÉ, y DÓNDE en el código.
> Se lee de arriba (lo más reciente) hacia abajo.

---

## 2026-09-09 — Fix: el botón "Subir" no servía tras quitar el plazo + redirect a Mis Facturas

- **QUÉ (bug):** Tras validar una factura, al pulsar "Subir" no pasaba nada. Causa: el JS de
  fiscal.blade.php seguía amarrado al selector de plazo (que ya quitamos). `syncPaso()` solo mostraba/
  habilitaba el botón Subir si `eligioPlazo()` era true, pero `diasPlazo` ya no existe → el botón nunca
  se habilitaba. También el handler del submit bloqueaba por `plazoSeleccionado()`.
- **FIX:** limpié el JS: el botón Subir se muestra/habilita en cuanto `puedeSubir` es true (factura
  validada), sin depender de plazo. Quité eligioPlazo/plazoSeleccionado y sus bloqueos en el submit.
- **QUÉ (mejora UX):** al subir, ahora redirige a "Mis Facturas" (`proveedores.facturas`) con flash
  `ok` = "Factura subida correctamente. Queda pendiente de revisión contable." (antes se quedaba en el
  formulario sin feedback claro). Se limpia el pendiente de sesión. Banner verde de éxito agregado a
  la vista facturas.blade.php.
- **QUÉ (orden):** en "Mis Facturas", la sección "Facturas registradas en sistema" (las de Wiese)
  ahora se ordena por fecha DESC (más recientes arriba). Antes salían por folio ascendente (viejas
  primero). Las facturas locales ya estaban con orderByDesc(created_at).
- **DÓNDE:** `resources/views/proveedores/fiscal.blade.php` (JS); `PortalProveedorController::subir...`
  (redirect + flash) y `mostrarFacturas` (sortByDesc de wieseFacturas); `facturas.blade.php` (banner ok).
- **PRUEBA:** 47 tests verdes (el de subir sigue pasando: verifica redirect + factura creada).

---

## 2026-09-09 — PUENTE portal ↔ Formato para pago (marcar facturas con documentos)

- **QUÉ:** El Formato para pago (que lee facturas EN VIVO de Wiese) ahora MARCA cuáles ya tienen
  documentos subidos por el portal del proveedor (PDF/XML). Columna nueva "Documentos" con badge
  verde "📄 Con docs" (link al PDF) o gris "Sin docs". Aplica en la tabla global (index) y en el
  detalle del proveedor (proveedor()).
- **POR QUÉ (Said):** juntar los dos mundos sin duplicar. La factura vive en Wiese (fuente de verdad);
  el portal solo le aporta PDF/XML + validación. Es DECORACIÓN de solo lectura: NO crea ni duplica.
- **CÓMO (match):** por `codigo_proveedor + folio`. OJO: el endpoint de Wiese NO devuelve el UUID del
  CFDI (solo serie/folio/codigo/id_documento), así que NO se puede casar por UUID todavía. El folio
  local (`folio_cfdi` = serie+folio, a veces con sufijo "-xxxx" por colisión) se normaliza (sin sufijo)
  y se compara contra `serie.folio` y el folio crudo del XML (`validacion_detalle.folio`). Match en
  memoria con UNA sola query local (whereIn codigo), sin N+1.
- **DÓNDE:** `AdminPagosController::marcarFacturasConDocumentosPortal()` (nuevo); llamado en `index()`
  (tabla global) y `proveedor()` ($facturasWiese). Vista `admin/pagos/index.blade.php` columna nueva.
  Banderas añadidas a cada factura: `tiene_docs`, `factura_local_id`, `docs_pdf`, `docs_xml`,
  `estatus_local`.
- **PENDIENTE (mejora futura con Alan):** que el endpoint de Wiese devuelva el UUID del CFDI → cambiar
  el match a UUID (más robusto que folio+codigo, que puede fallar con sufijos de colisión).
- **PRUEBA:** `tests/Feature/PuenteFacturasDocumentosTest.php` (3 tests: con docs se marca; tolera
  sufijo -xxxx; otro proveedor no matchea).

---

## 2026-09-09 — Quitado el selector de "días de plazo" del proveedor (plazo fijo por Salcom)

- **QUÉ:** El proveedor YA NO elige los días de plazo al subir la factura. Se quitó el bloque
  "DÍAS DE PLAZO" (select + opción "otro") del formulario. Al subir, la factura se registra con un
  plazo FIJO por defecto (config `facturas.plazo_default_dias`, env `FACTURAS_PLAZO_DEFAULT_DIAS`,
  default 30). fecha_vencimiento = hoy + ese plazo.
- **POR QUÉ (Said):** si el proveedor elige el plazo, pondría "1 día" para cobrar ya. El plazo de pago
  lo define Salcom/contabilidad, no el proveedor.
- **DÓNDE:** `resources/views/proveedores/fiscal.blade.php` (bloque plazo reemplazado por aviso
  "El plazo lo define Industrias Salcom"); `PortalProveedorController::subir...` (ya no valida
  dias_plazo/dias_plazo_otro; usa el default); `config/facturas.php` (nuevo plazo_default_dias;
  plazos_dias se deja por compatibilidad).
- **PRUEBA:** tests del plazo viejo reemplazados por 2 nuevos (subir sin pedir plazo usa el default;
  la vista ya no muestra el selector). 44 tests verdes.

---

## 2026-09-09 — Producto sin OC: el proveedor DEBE declarar (cierra hueco de "sin OC = validada")

- **QUÉ:** Si una factura de producto (mercancía) llega SIN número de OC, el proveedor ahora DEBE
  declarar por qué en el formulario:
  - **flete_servicio**: no lleva OC (se refuerza lo que ya se detecta por ClaveProdServ).
  - **remision**: compra por remisión/requisición sin OC en sistema → pasa pero marcada
    `requiere_autorizacion_admin=true`, estatus `pendiente_autorizacion` (NO "validada correctamente").
  - **nada declarado** → ERROR: "Falta el número de OC… o declara que es remisión". Ya NO se cuela.
- **POR QUÉ:** En pruebas, FLEXITEK subió una factura de producto SIN OC (teniendo OC) y el sistema la
  mostró como "validada correctamente" en verde. Hueco: una compra sin su OC no debe verse igual que
  una aprobación limpia. Idea de Said: que el proveedor DECLARE (responsabilidad de él). No se consulta
  Wiese (sería lento —cientos de OC— y no concluyente: tener OCs no prueba que ESTA tenga).
- **DÓNDE:** `AltaFacturaValidationService::validar()` nuevo parámetro `$declaracionSinOc`; caso (C)
  ramifica por la declaración. `PortalProveedorController::validarAltaFactura` valida `tipo_sin_oc`
  (in:flete_servicio,remision), lo pasa al validador y lo conserva en el pendiente; el mensaje de
  éxito dice "PENDIENTE DE AUTORIZACIÓN" cuando aplica. Vista `fiscal.blade.php`: bloque amarillo
  `#bloqueSinOc` con 2 radios que aparece cuando el campo OC está vacío.
- **PRUEBA:** 2 tests nuevos (sin declaración → error; declara remisión → pasa con
  requiere_autorizacion_admin). 2 tests viejos de mercancía-sin-OC ajustados para declarar 'remision'.
  47 tests verdes.

---

## 2026-09-09 — Timeout de búsqueda de OC subido a 60s (el 250 tarda 20-30s)

- **QUÉ:** El timeout de `buscarOcPorFolio` subió de 25s a 60s (config `facturas.oc_timeout_seg`,
  env `FACTURAS_OC_TIMEOUT_SEG`).
- **POR QUÉ:** En demo con Brenda, la factura 93150 dio "OC 81686 no encontrada / no se pudo conectar".
  El log mostró cURL error 28 (timeout a los 25s). NO era bug del fix anterior: el rango corto y el
  prefijo ya estaban bien. La causa: el 250 NO sabe filtrar por folio (probado: ignora
  folio/Folio/cfolio/numeroDocumento y SIEMPRE devuelve las 1864 OC de ORPACK con movimientos). Esa
  consulta tarda 20-30s (medido: hasta 30.7s), variable; con 25s a veces cortaba. Con 60s hay margen.
- **PRUEBA:** `buscarOcPorFolio('M213015002','81686',77479.90)` → SUCCESS en 16.8s, serie -A, pedido
  8754, recibido 8754. 46 tests verdes.
- **SOLUCIÓN DE FONDO (pendiente Alan):** que el API del 250 acepte filtrar por folio y devuelva solo
  esa OC → respondería en ms. Mientras no exista, toca esperar a que traiga todo.
- **DÓNDE:** `ProveedorApiService::buscarOcPorFolio()`; `config/facturas.php` (oc_timeout_seg).

---

## 2026-09-09 — Fase 2: Notas de crédito y anticipos (detectar y ligar, NO calcular saldos)

- **QUÉ:** El validador ahora acepta y clasifica NOTAS DE CRÉDITO (CFDI de Egreso "E") y detecta
  ANTICIPOS. Antes rechazaba todo lo que no fuera Ingreso ("I").
- **NOTA DE CRÉDITO (tipo E):**
  - Se acepta E además de I (P/N/T siguen rechazados). `datos.es_nota_credito`.
  - Reglas fiscales (confirmadas Brenda): UsoCFDI **G02**, método **PUE**, forma **15** (devolución) o
    **30** (aplicación de anticipo / mata-anticipo).
  - Subtipo por forma: 15→`devolucion`, 30→`aplicacion_anticipo`, otra→`generica` (ej. pronto pago).
  - Debe traer CFDI Relacionado (UUID de la factura que afecta). Sin UUID → advertencia/revisión (no
    bloquea): contabilidad confirma a qué factura aplica. `datos.nc_detalle`, `datos.clasificacion_nc`.
  - La NC NO lleva OC ni cruza cantidad (rama D en validar()).
- **ANTICIPO (factura de Ingreso):** se detecta por ClaveProdServ **84111506** (config
  `facturas.claves_anticipo`). Lleva OC (no es "servicio sin OC"). Se marca `datos.es_anticipo` +
  `clasificacion_oc='anticipo_con_oc'` + advertencia de que después llegará su NC (forma 30). Caso DIMOSA.
- **ALCANCE (Said):** SOLO detectar/validar/ligar. El "cuánto se descuenta del saldo" es de
  CONTABILIDAD en Formato para pago, NO de este flujo. No se tocan montos aquí.
- **DÓNDE:** `AltaFacturaValidationService`: check tipo E/I; `validarNotaCredito()`;
  `facturaEsAnticipo()`; `validarDatosPagoCfdi()` ramifica NC vs factura; rama (D) en `validar()`.
  Config nueva `facturas.claves_anticipo`.
- **PRUEBA:** 4 tests NC nuevos (devolución ligada / mata-anticipo forma30 / sin relacionado=revisión /
  uso G01 incorrecto rechaza). 46 tests verdes.

---

## 2026-09-09 — Fase 2 parcialidades: factura se compara contra lo RECIBIDO (no lo pedido)

- **QUÉ:** El cruce de cantidad (`cruzarCantidadConOc`) ahora compara la cantidad facturada contra lo
  RECIBIDO en almacén (`cunidades − cunidadespendientes` = `cantidad_recibida_total`), NO contra lo
  pedido (`cunidades`). 3 estados en `oc_cruce_detalle['estado']`:
  - `ok`: factura ≤ recibido → pasa.
  - `excede_recibido`: factura > recibido → ERROR, se rechaza ("no puede facturar más de lo que llegó").
  - `esperando_recepcion`: recibido = 0 (Acatlán aún no registra entrada) → ADVERTENCIA, NO bloquea
    (queda en revisión hasta que se registre la recepción).
- **POR QUÉ (Brenda):** no se paga lo que no ha llegado. Acatlán registra cada recepción en Wiese y
  baja `cunidadespendientes`. Para saber si la factura es válida, se ve si el material ya llegó (si
  Acatlán ya lo registró). Esto cubre parcialidades sin distinguir parcial/completa: una completa es
  el caso donde recibido == pedido. El prefijo A/B/C del folio (A3456, B3456…) apunta a la MISMA OC
  madre (folio base 3456) y ya se normaliza con soloDigitos en buscarOcPorFolio.
- **ALCANCE (Said):** compras solo VALIDA y LIGA; el "cuánto se paga" (saldos con NC/anticipos) es de
  CONTABILIDAD en Formato para pago, NO de este flujo. Por eso aquí no se calcula saldo.
- **DÓNDE:** `AltaFacturaValidationService::cruzarCantidadConOc()` (ahora recibe &$advertencias).
- **PRUEBA:** 3 tests nuevos (parcial pasa / excede recibido rechaza / nada recibido = revisión).
  42 tests verdes.
- **PENDIENTE Fase 2 (siguiente):** detección y ligado de Notas de Crédito (G02; devolución forma 15;
  NC que mata anticipo forma 30, relación 07; ligar por UUID de CFDI relacionados) y anticipos
  (G03, ligados a NC). Sin tocar saldos (eso es contabilidad). Ver REGLAS-FACTURA-OC.md.

---

## 2026-09-08 — Servicios/fletes sin OC + producto sin OC = revisión manual

- **QUÉ:** La validación de factura ahora clasifica en 3 caminos antes de cruzar OC:
  - **(A) Servicio/flete** (ClaveProdServ de servicio, rango 70-99, o concepto de flete detectado):
    NO lleva OC. Se salta el cruce; valida solo lo fiscal. `clasificacion_oc='servicio_sin_oc'`.
  - **(B) Producto con número de OC:** cruce de cantidad contra Wiese (Fase 1). `='producto_con_oc'`.
  - **(C) Producto SIN número de OC:** NO se rechaza ni se traba. Queda para AUTORIZACIÓN MANUAL del
    admin (caso requisición de papel / adquisición de activos que no están en sistema).
    `='producto_sin_oc_revision'` + advertencia.
- **POR QUÉ:** un flete (ej. LARO Logistics, ClaveProdServ 78101800) no tiene OC; si se la exigíamos,
  se rechazaba/trababa. El UsoCFDI NO sirve para distinguir (flete y compra comparten G01/G03); la
  ClaveProdServ sí (mercancía 10-69, servicio 70-99). Lo del producto-sin-OC es por las requisiciones
  en papel firmadas a mano que no están en Wiese: el sistema no puede cruzar lo que no existe, así que
  lo manda a revisión humana en vez de adivinar.
- **DÓNDE:** `AltaFacturaValidationService::facturaEsServicioSinOc()` (nuevo) + clasificación en
  `validar()`. `numero_oc` ya NO es required: `PortalProveedorController::validarAltaFactura`
  (nullable) ni en `resources/views/proveedores/fiscal.blade.php` (quitado required, etiqueta nueva
  "déjalo vacío si es flete o servicio"). Config no cambió.
- **PRUEBA:** 2 tests nuevos (flete sin OC → servicio_sin_oc aprueba; producto sin OC → revisión
  manual, no rechaza). 39 tests verdes.
- **🟡 PENDIENTE BRENDA (Fase 2):** (1) requisición de papel: ¿quién autoriza y cómo? ¿entran algún
  día a Wiese como OC? (2) maquinaria/activos: ¿llevan OC o van por requisición? (3) ¿hay servicios
  que SÍ lleven OC (maquila/contratos)? Si sí, la regla "servicio=sin OC" no sería absoluta.

## 2026-09-08 — Proveedores de PRUEBA locales: LARO (flete) y FLEXITEK (producto)

- **QUÉ:** creados 2 proveedores de prueba en LOCAL (docs fiscales aprobados + activos) para probar
  los 3 escenarios junto con ORPACK.
- **CREDENCIALES DE PRUEBA (solo local):**
  - **ORPACK** (producto con OC): `wiese_M213015002` / `orpack123` — código Wiese M213015002.
  - **LARO** (flete, sin OC): `laro.flete` / `laro123` — persona física, RFC QUVA991011I34, código
    LARO-TEST (NO está en Wiese; es flete, no cruza OC).
  - **FLEXITEK** (producto con OC): `flexitek.prov` / `flexitek123` — persona moral, RFC FME010503KZ7,
    código REAL de Wiese M213006022.
- **POR QUÉ LARO no tiene código Wiese:** no aparece en el directorio de proveedores de Wiese (los
  fletes a veces no están dados de alta como proveedor de compras). No importa: el flete valida solo
  fiscal, no necesita cruzar OC.

---

## 2026-09-07 — Fix 400 en listarFacturasPendientesTodos (faltaba CIDDOCUMENTODE=19)

- **QUÉ:** El Formato para pago no traía facturas (tabla vacía). Causa: `listarFacturasPendientesTodos()`
  llamaba a `ListarDocumentos` SIN `CIDDOCUMENTODE`, y Alan lo volvió obligatorio → 400
  ("CIDDOCUMENTODE field is required"). Se agregó `CIDDOCUMENTODE=19` (facturas de compra).
- **POR QUÉ 19:** las facturas de proveedor son tipo 19 (compras) en Wiese; las OC son 17.
  Confirmado buscando la factura REAL 93150 de ORPACK: aparece en tipo 19.
- **PRUEBA:** `listarFacturasPendientesTodos('2025-01-01','2026-12-31')` → SUCCESS, 1518 facturas
  pendientes de todos; 286 de ORPACK. Antes: 400. 37 tests verdes.
- **DÓNDE:** `app/Services/ProveedorApiService.php` → `listarFacturasPendientesTodos()`;
  config nueva `facturas.tipo_documento_compra` (env `FACTURAS_TIPO_DOC_COMPRA`, default 19).
- **CONTEXTO (plan de juntar portal ↔ Formato para pago, sin tocar API de Alan):** la factura que el
  proveedor sube por el portal YA existe en Wiese (Acatlán la captura al recibir material). El plan es
  hacer MATCH (no duplicar): cuando el proveedor sube PDF+XML+OC, el sistema encuentra esa misma
  factura en Wiese (por UUID, o folio+serie+código) y le pega los documentos. Pendiente de implementar.

---

## 2026-09-07 — Desempate de OC por total (folio NO es único en Wiese)

- **QUÉ:** `buscarOcPorFolio()` ahora recibe el total de la factura y, cuando hay VARIAS OC con el
  mismo folio, elige la que su total se parezca más al de la factura. Antes se quedaba con la primera
  (break) y podía agarrar la equivocada. También devuelve `serie_oc` y `candidatas` (cuántas
  compartían folio) y los guarda en `oc_cruce_detalle`.
- **POR QUÉ:** Con ORPACK descubrimos DOS OC folio 81686: serie '' = 7200 pzas ($63,725.76) y
  serie '-A' = 8754 pzas ($77,479.90). La factura real es la '-A'. El folio solo no distingue; el
  total sí. La serie '-A' es justo el "prefijo" del que habla Said (parte de Fase 2).
- **PRUEBA:** `buscarOcPorFolio('M213015002','81686', 77479.90)` → elige serie '-A', 8754 pzas. Sin
  total → agarra la de 7200 (viejo). 37 tests verdes.
- **DÓNDE:** `app/Services/ProveedorApiService.php` → `buscarOcPorFolio()` (junta candidatas + usort
  por |total_oc − total_factura|); `AltaFacturaValidationService::cruzarCantidadConOc()` le pasa
  `$datos['total']` y guarda `serie_oc`/`candidatas`.
- **OJO Fase 2:** el desempate por total falla en PARCIALIDADES (la factura parcial NO cuadra con el
  total de la OC). Ahí habrá que usar la serie/prefijo. Por eso ya guardamos `serie_oc`. Pendiente
  confirmar con Brenda (ver `.kiro/REGLAS-FACTURA-OC.md` y el doc de preguntas).

---

## 2026-09-07 — ✅ RESUELTO: el cruce de OC se colgaba por pedir rango de 15 años (era NUESTRO)

- **QUÉ:** El cruce factura↔OC se quedaba en "No se pudo conectar con Wiese". Causa real: en
  `buscarOcPorFolio()` pedíamos `fechaInicial=2020-01-01` / `fechaFinal=2035-12-31` (15 años). Con
  `CIDDOCUMENTODE=17` el 250 trae las OC CON todos sus Movimientos, y 15 años de historia tardan más
  que el límite de PHP → timeout. NO era culpa del API de Alan ni de la red.
- **PRUEBA (diagnóstico directo al 250, probando variantes de parámetros):**
  - Rango 2020-2035 + CIDDOCUMENTODE=17 → cuelga (timeout, 0 bytes).
  - Rango SOLO 2026 + CIDDOCUMENTODE=17 → **200 OK en ~8s, 1 MB**. El endpoint y el parámetro están bien.
  - Sin CIDDOCUMENTODE o sin codigo → 400 inmediato (ambos son required). O sea el API responde rápido;
    lo que lo ahoga es el tamaño del resultado por el rango enorme.
- **FIX:** acotar el rango a una ventana de ~18 meses (config `facturas.oc_ventana_meses`, env
  `FACTURAS_OC_VENTANA_MESES`). Con eso `buscarOcPorFolio('M213015002','81686')` devuelve SUCCESS:
  OC 81686, 7200 pedidas, total 63725.76, en ~18s.
- **DÓNDE:** `app/Services/ProveedorApiService.php` → `buscarOcPorFolio()` (rango con `now()->subMonths`);
  `config/facturas.php` → `oc_ventana_meses`. Timeout de la llamada quedó en 25s (del fix anterior).
- **OJO PENDIENTE (optimización, NO urgente):** aun con ventana corta tarda ~18s porque el 250 manda
  TODAS las OC del proveedor y filtramos el folio en PHP. Idealmente Alan podría agregar filtro por
  folio en el 250 para devolver solo esa OC. Mientras, funciona dentro del timeout.
- **OJO DATOS DE PRUEBA:** la OC 81686 pidió 7200 pzas pero la factura de prueba F0000093150 cobra
  8754 → la regla Fase 1 la RECHAZA ("no puede facturar más de lo pedido"). Es correcto; para ver el
  camino feliz hace falta una factura con cantidad ≤ 7200.

---

## 2026-09-07 — Fix cuelgue 60s al buscar OC + folio tolera prefijo "A"

- **QUÉ:** (1) Bajé el timeout de la llamada que busca la OC en Wiese de 60s a **25s**.
  (2) La comparación del folio de OC ahora **ignora letras y ceros a la izquierda** ("A81686",
  "081686" y "81686" se tratan como la misma OC).
- **POR QUÉ:** Al probar con ORPACK el portal murió con "Maximum execution time of 60 seconds
  exceeded" (Guzzle/curl). El timeout de 60s == límite de PHP (max_execution_time), así que PHP
  reventaba ANTES de que Guzzle cortara limpio. Con 25s, si Wiese no responde falla rápido y
  muestra mensaje bonito en vez de pantalla de error. Lo del prefijo "A" evita falsos "OC no
  encontrada" porque en Wiese las parcialidades llevan prefijo de letra.
- **DÓNDE:** `app/Services/ProveedorApiService.php` → método `buscarOcPorFolio()`
  (timeout `$this->http(25)` + normalización `$soloDigitos` antes de comparar folios).
- **NOTA:** `tests/Unit/WieseGatewayTest.php` está ROTO (requiere `wiese-gateway/src/Gateway.php`
  que no existe) y tumba `php artisan test` completo. Los 37 tests de factura pasan corriéndolos
  por ruta directa con phpunit. Pendiente revisar ese test aparte.

---

## 2026-10-27 — CIDDOCUMENTODE en ListarDocumentos + comentado Contpaqi del gateway

- **QUÉ:** (1) Alan agregó al **APIWIESE** (250) el parámetro `CIDDOCUMENTODE` a los métodos de
  listar documentos (ListarDocumentos/Codigo/Rfc) + ahora traen `Movimientos` (renglones/cantidades).
  (2) En MI gateway **APIPortalWeb** (251), actualicé `ListarDocumentos` para mandar ese parámetro
  (2 líneas: firma con `string ciddocumentode = "19"` + `&CIDDOCUMENTODE={ciddocumentode}` en la URL al 250).
  (3) **COMENTÉ** (no borré, con Ctrl+K Ctrl+C) los 3 endpoints del gateway que usaban SDK de Contpaqi
  LOCAL: `CrearPago`, `FacturasProveedor`, `CrearPagoYSaldar`.
- **POR QUÉ:** CIDDOCUMENTODE = tipo de documento Contpaqi (17=OC, 19=Compras/facturas, 32=entradas
  almacén). Antes el endpoint traía fijo 19; ahora puedo pedir 17 (OC) para el flujo nuevo factura↔OC.
  Los de Contpaqi se comentan porque usan el SDK local (`C:\Compac\Empresas\adSalcom18`) y se va a
  migrar a SAP; el día que quiten Contpaqi, esos truenan.
- **DÓNDE:** `APIPortalWeb/Controllers/PagoProveedorController.cs`. Compiló 0 errores.
- **⚠️ PENDIENTE CRÍTICO:** comentar los de Contpaqi deja al sistema SIN poder crear/saldar pagos.
  HOY no rompe nada (producción no está en uso), pero ANTES del arranque real Alan debe tener listo
  el **reemplazo de crear/saldar pagos sin Contpaqi (vía SAP/APIWIESE)**. Confirmar con Alan.
- **FALTA:** desplegar al 251 (publish → copiar → restart). Por ahora solo está en la laptop.
  Conceptos Contpaqi documentados en `.kiro/CONCEPTOS-CONTPAQI.md`.

---

## 2026-10-27 — 3 reglas SAT de coherencia en validación de facturas (Anexo 20)

- **QUÉ:** Se agregaron 3 validaciones nuevas al alta de facturas, en
  `app/Services/AltaFacturaValidationService.php`, método `validarDatosPagoCfdi()`:
  1. **PUE/PPD ↔ FormaPago** (regla oficial SAT CFDI40105): PPD obliga FormaPago 99;
     PUE no puede ser 99.
  2. **Moneda extranjera** (≠ MXN) debe traer `TipoCambio > 0`.
  3. **UsoCFDI "D" (D01-D10)** rechazado porque Salcom/Fram son personas MORALES
     (los usos D son deducciones personales, solo de físicas).
- **POR QUÉ:** son las mismas reglas que aplican Alegra/PAC al emitir (Anexo 20). Antes el
  sistema solo checaba que forma/método/uso EXISTIERAN en catálogo, no la COHERENCIA entre ellos.
- **DÓNDE/EXTRA:** se agregó el campo `tipo_cambio` al objeto del CFDI (se lee de `TipoCambio`
  del comprobante; default 1 si no viene) — antes no se leía. Si falla → `$okPago = false`.
- **VERIFICADO:** `php -l` OK; tests en verde (27 unit + 10 feature de alta de factura).
- Pendientes de las reglas SAT que NO se hicieron (decisión de negocio, confirmar con Contabilidad):
  ampliar catálogo de formas de pago (05,06,29,31) y usos CFDI (I02-I07) si los proveedores los usan.

---

## 🔴 REACTIVAR EL SÁBADO — bloqueos de validación de factura desactivados para pruebas

> Desactivados el 27-oct SOLO para probar (factura de ORPACK estando logueado como Aneso, y
> facturas de meses viejos). REACTIVAR el SÁBADO:
> En el `.env` (local y producción) poner en **true** o quitar estas 2 líneas:
> - `FACTURAS_SOLO_MES_ACTUAL=false`  → true (solo acepta facturas del mes en curso)
> - `FACTURAS_VALIDAR_RFC_EMISOR=false` → true (RFC emisor del XML debe = RFC del proveedor logueado)
> Luego `php artisan config:clear`. Son validaciones de seguridad importantes, NO dejarlas apagadas.

---

## ⚠️ PENDIENTES ANTES DE REABRIR EL API 5009/5443 AL PÚBLICO (NO OLVIDAR)

> La conexión pública la apagó Genaro hasta ~30-nov. Estos 4 puntos hay que cerrarlos
> ANTES de que se reabra al público:

1. **✅ RESUELTO — HTTPS del 5443 YA ENTRA DESDE INTERNET.** La policy **"Wiese Chapalita"** en el
   FortiGate (FortiNave3, 172.16.1.1) YA EXISTÍA pero estaba **DESACTIVADA**. Daniel Crespo solo la
   **ACTIVÓ (Enabled)** y empezó a pasar tráfico (se ve el contador subiendo, ~6.65 MB).
   La policy: Incoming wan1 → Outgoing lan, Source all, Destination = VIP "chapalita web 2"
   (189.254.241.195 → 172.16.1.251), Service ALL, Action ACCEPT. No se creó nada nuevo, solo se habilitó.
   VERIFICADO desde fuera: `https://api-pagos.salcomlink.mx:5443/swagger` abre desde celular con
   datos; el swagger.json se lee desde la nube pública. Endpoints y seguridad Bearer visibles.
   NOTA: la policy usa Service=ALL (no solo 5443) y el VIP es Static NAT compartido con "chapalita".
   Mejora futura (con redes): acotar a Service TCP 5443 y restringir Source a la IP de SiteGround
   (35.215.67.229), para no exponer de más. No urgente.
   El `.env` de SiteGround ya apunta a `https://api-pagos.salcomlink.mx:5443`.
2. **Renovar el certificado ~16 dic** (repetir el registro TXT en SiteGround con win-acme) —
   o pasarlo a automático cuando reabra el puerto 80 (validación HTTP-01).
3. **Regenerar los 2 tokens** (JWT_KEY y GATEWAY_TOKEN) antes de reabrir al público
   (quedaron expuestos en el chat/notas). Cambiarlos en el 251 (NSSM) y en el `.env` de SiteGround.
4. **Pasar a Production** (`ASPNETCORE_ENVIRONMENT=Production` en NSSM) para ocultar Swagger
   antes de reabrir. OJO: `AppEnvironmentExtra` reemplaza TODA la lista, hay que volver a poner
   los 4 secretos cambiando solo esa palabra.

---

## 2026-09-07 — Seguridad del API 5009 (APIPortalWeb): JWT + secretos fuera del código

- **QUÉ:** El gateway 5009 ya NO es abierto. Ahora todos los endpoints exigen un token JWT, y los
  secretos (llave JWT, usuario/clave de Wiese) salieron del código y viven como variables de
  entorno del servicio en el 251 (vía NSSM).
- **POR QUÉ:** lo pidió Alan (seguridad OWASP). Antes cualquiera con la URL del 5009 podía listar
  proveedores, documentos y crear pagos sin credencial. Riesgo directo de fuga/fraude.
- **CÓMO funciona (nivel B, JWT):**
  1. El cliente (SiteGround) hace `POST /login` con un `GATEWAY_TOKEN` (contraseña de entrada).
  2. Si el token es correcto, el 5009 devuelve un **JWT que dura 1 hora**.
  3. Cada llamada a los endpoints lleva el header `Authorization: Bearer <jwt>`.
  4. Sin token o con token malo → **401 Unauthorized**.
- **DÓNDE en el código** (proyecto C# `C:\Users\IT\source\repos_git\APIPortalWeb\APIPortalWeb\`):
  - `APIPortalWeb.csproj`: paquete `Microsoft.AspNetCore.Authentication.JwtBearer` 8.0.0.
  - `appsettings.json`: `Jwt.Issuer` / `Jwt.Audience` (NO secreto, solo identificadores).
  - `Program.cs`: lee `JWT_KEY` y `GATEWAY_TOKEN` de variables de entorno (defaults solo para dev
    local); AddAuthentication/AddJwtBearer; Swagger con botón Authorize (candado); endpoint
    `POST /login` que valida GatewayToken y firma el JWT 1h; bloque HTTPS Kestrel 5443 COMENTADO
    (espera el certificado SAN de Genaro). Tipos auxiliares al final: SeguridadConfig, LoginRequest,
    JwtHelper.GenerarToken.
  - `Controllers/PagoProveedorController.cs`: `[Authorize]` sobre la clase; usuario/clave Wiese
    sacados a `WIESE_USER`/`WIESE_PWD` (ya no "CONSULTA/12345" hardcodeados).
- **SECRETOS (en el 251, NO en código ni en git)** — configurados con:
  `nssm set APIPortalWeb AppEnvironmentExtra "JWT_KEY=..." "GATEWAY_TOKEN=..." "WIESE_USER=..." "WIESE_PWD=..."`
- **VERIFICADO en el 251 (07-sep):** sin token → 401; `/login` con GATEWAY_TOKEN → JWT (3600 s);
  con el JWT en `Authorization: Bearer` → endpoints responden datos. OK.
- **LADO SITEGROUND (Laravel) — hecho el mismo día:** `ProveedorApiService` ya se autentica
  contra el 5009. Nuevo método privado `tokenGateway5009()` que hace `POST /login` con el
  GATEWAY_TOKEN, recibe el JWT (1h) y lo **cachea ~55 min** (clave con hash del token para no
  reusar uno viejo si se rota). Los 3 métodos del camino 5009 (`listarFacturasPendientesTodos`,
  `buscarProveedorWiese`, `listarProveedoresWiese`) ya mandan ese JWT como Bearer (antes iban con
  token vacío porque "el puente autenticaba solo"). Nueva config `services.proveedor_api.pagos_gateway_token`
  = env `PAGOS_GATEWAY_TOKEN`. Verificado: `php -l` OK y 321 tests del servicio en verde.
  OJO `WieseBancoContpaqiService` (CrearPagoYSaldar) NO se tocó: usa `CONTPAQI_API_URL` (127.0.0.1:7090),
  flujo local de oficina, no pasa por el 5009 público. Confirmar si en prod apunta a otra URL.
- **FILTRO POR IP (lista blanca) — código agregado al Program.cs:** middleware `app.Use(...)`
  colocado DESPUÉS del `if (IsDevelopment)` de Swagger y ANTES de `app.UseAuthentication()`.
  Lee la lista de la variable de entorno `IPS_PERMITIDAS` (IPs separadas por coma). Si la lista
  está VACÍA no filtra nada (modo dev / actual). Si tiene IPs y la de origen no está, corta con
  403 y lo escribe en consola (`[IP-BLOCK] ...`). Normaliza IPv6 mapeada (`::ffff:` → IPv4).
  También se lee `var ipsPermitidas` al inicio, junto a jwtKey/gatewayToken.
  POR QUÉ dormido: la conexión pública la apagó Genaro hasta ~30-nov; no se puede saber qué IP
  ve el 5009 cuando llega SiteGround (por el NAT) hasta que se reabra. Al reabrir: hacer una
  llamada, ver la IP en el log, y recién llenar `IPS_PERMITIDAS` (SiteGround 35.215.67.229 +
  red interna 172.16.1.x + 127.0.0.1).
- **DESPLEGADO al 251 (versión con JWT + filtro por IP):** publish win-x86 → copiado a
  C:\APIPortalWeb → Start-Service. Verificado: 401 sin token, /login da JWT, filtro dormido
  deja pasar. OK.
- **HTTPS ACTIVADO (paso 6 de Alan) — 07-sep (hecho por Said, sin Genaro):** certificado REAL
  de Let's Encrypt para `api-pagos.salcomlink.mx`, emitido con **win-acme** (`C:\win-acme\wacs.exe`)
  por **validación DNS-01 manual** (registro TXT en SiteGround), porque el puerto 80 público está
  cerrado. Certificado instalado en el almacén de Windows del 251 (LocalMachine\My),
  thumbprint `23A6C89F42819C2CED006D2881DD4AF6B59EA792`, vence 20-ene-2027 (~90 días).
  - DNS creado en SiteGround: `A api-pagos.salcomlink.mx → 189.254.241.195`.
  - El servicio corre como **LocalSystem**, así que ya tiene acceso a la clave privada (sin paso extra).
  - `Program.cs`: bloque HTTPS DESCOMENTADO, Kestrel escucha HTTPS en **5443** buscando el cert
    por THUMBPRINT (más robusto que por nombre). El 5009 HTTP se dejó temporal (no romper mientras se prueba).
  - Firewall 251: regla inbound TCP 5443 ("API HTTPS 5443").
  - VERIFICADO en el 251: `netstat` muestra 5443 LISTENING; `https://localhost:5443/login` con el
    GATEWAY_TOKEN devuelve JWT (3600 s). HTTPS OK.
  - RENOVACIÓN: por ahora MANUAL cada ~90 días (validación DNS, hay que repetir el TXT). Próxima ~16-dic.
    Cuando reabra el puerto 80 público, se puede pasar a automática (HTTP-01).
- **PENDIENTE:**
  1. **Regenerar JWT_KEY y GATEWAY_TOKEN antes de reabrir** (quedaron expuestos en el chat).
  1b. **Probar HTTPS desde fuera** cuando se reabra el NAT (puerto 5443 público → `https://api-pagos.salcomlink.mx:5443`).
      Dentro del 251 el nombre no resuelve por el NAT apagado; por eso se probó con localhost.
  1c. **Renovar el certificado ~16-dic** (TXT manual en SiteGround) o pasar a auto al reabrir el 80.
  2. **Poner `PAGOS_GATEWAY_TOKEN=<gateway_token real>` en el `.env` de SiteGround** (igual al del 251),
     y `php artisan config:cache`. Sin eso, SiteGround no se autentica y el 5009 responde 401.
  3. **HTTPS (opción A):** cuando Genaro instale el certificado SAN en el almacén del 251,
     descomentar el bloque Kestrel 5443 de Program.cs con el DNS real, recompilar y redesplegar.

---

## 2026-10-16 — Migración del API 5009 (APIPortalWeb) de la laptop al servidor 251

- **QUÉ:** El gateway 5009 (proyecto C# APIPortalWeb, puente SiteGround→5009→250 Wiese) dejó de
  vivir en la laptop de Said y ahora corre en el servidor fijo 172.16.1.251, como SERVICIO de
  Windows (arranque automático, sin ventana abierta).
- **POR QUÉ:** si la laptop se apagaba, se caía producción (la tabla/lupa/proveedores de Formato
  para pago dependen del 5009). En un server fijo ya no depende de la laptop.
- **CÓMO (hecho hoy, pasos reproducibles):**
  1. Instalado .NET 8 en el 251. La app es x86 (por Contpaqi), así que se necesitaron los runtimes
     x86: `aspnetcore-runtime`, `dotnet-runtime` (base) y `windowsdesktop-runtime` (usa WinForms).
     Links directos: builds.dotnet.microsoft.com/dotnet/{Runtime|WindowsDesktop|aspnetcore}/8.0.32/...
     OJO: el ASP.NET runtime NO trae el base ni el desktop; faltaban esos dos y por eso no arrancaba
     (errores "hostfxr.dll not found" y "Microsoft.WindowsDesktop.App no frameworks were found").
  2. `dotnet publish -c Release -r win-x86 --self-contained false -o C:\APIPortalWeb_publish` (EN LA
     LAPTOP; el server NO compila, solo corre). Trae Configuracion.dll + SDKWieseNET48* por los
     ProjectReference.
  3. Copiada la carpeta publish a `C:\APIPortalWeb` del 251 (por Escritorio Remoto / copiar-pegar).
  4. Firewall del 251: regla inbound TCP 5009 ("API 5009").
  5. Servicio con NSSM (C:\nssm): install APIPortalWeb → `C:\APIPortalWeb\APIPortalWeb.exe`,
     AppDirectory=C:\APIPortalWeb, AppEnvironmentExtra=`ASPNETCORE_ENVIRONMENT=Development`
     (Development para dejar Swagger visible), Start=SERVICE_AUTO_START. `Start-Service APIPortalWeb`.
  6. Verificado en el 251: `http://localhost:5009/swagger` y `.../ListarDocumentos` devuelven JSON OK.
- **PENDIENTE:**
  - Alan: instalar Contpaqi en el 251 (ruta esperada por el código `C:\Compac\Empresas\adSalcom18`)
    para los endpoints que usan el SDK (CrearPago, FacturasProveedor, CrearPagoYSaldar). Los que solo
    reenvían al 250 (ListarDocumentos/ListarProveedores/BuscarPorCodigo/RFC) ya funcionan sin Contpaqi.
  - Genaro: cambiar el NAT de la IP pública 189.254.241.195:5009 para que apunte al 251 (hoy va a la
    laptop 172.16.1.158). Hasta que lo haga, SiteGround sigue pegándole a la laptop.
  - Verificar desde SSH de SiteGround con curl al 5009 una vez movido el NAT; luego ya se puede apagar
    la laptop. Nota: el `.env` de SiteGround no cambia (la IP pública es la misma, solo cambia a qué
    máquina interna la manda el NAT).

---

## PENDIENTE (ESCALABILIDAD) — Mover almacenamiento de archivos a S3 (nube)

- **PREGUNTA DE SAID (muy buena):** ¿es rentable guardar archivos en carpeta vs BD? ¿se llenará
  el servidor en 7 años con 6,000 proveedores subiendo facturas?
- **RESPUESTA/DECISIÓN:**
  1) NUNCA meter archivos (PDF/XML) en la base de datos — infla la BD, la vuelve lenta y cara,
     complica respaldos. La BD guarda SOLO la ruta (como ya se hace). Es la práctica correcta.
  2) Estimación: ~250 KB/factura. 6,000 prov × 10 facturas/mes ≈ 15 GB/mes ≈ 180 GB/año ≈
     ~1.25 TB en 7 años. ESO NO CABE en hosting compartido de SiteGround (10-40 GB). La
     preocupación de Said es legítima a largo plazo.
  3) SOLUCIÓN correcta: almacenamiento de objetos en la nube = Amazon S3. Escala infinito,
     ~$0.023 USD/GB/mes (1.25 TB ≈ ~$30/mes al final, menos los primeros años). El servidor y los
     respaldos quedan ligeros.
  4) VENTAJA: el proyecto YA tiene el disco `s3` preconfigurado en config/filesystems.php. Migrar
     es cambiar el disco por defecto (FILESYSTEM_DISK=s3) + poner credenciales AWS en .env. El
     código ->store(...) sigue igual.
- **PLAN:** hacerlo JUNTO con el arreglo de seguridad público/privado (tocan los mismos lugares):
  un solo proyecto "archivos a S3 + privados con ruta protegida". No urge hoy (volumen bajo), pero
  planear antes de que crezca.
- **ESTADO:** pendiente; prioridad a definir.

---

## 2026-10-16 — HECHO: Documentos sensibles movidos a disco PRIVADO + comando limpiar basura

- **QUÉ:** Se resolvió el pendiente de seguridad. Todos los documentos sensibles ahora se guardan
  en disco `local` (storage/app/private, NO accesible por link directo) y se sirven por rutas
  protegidas. Antes estaban en `public` (cualquiera con el link los abría sin login).
- **PIEZAS (10 lugares de guardado migrados public→local):**
  1. Facturas del proveedor (PDF/XML/OC): PortalProveedorController@registrarFacturaDesdePendiente.
     Rutas protegidas: proveedores.facturas.archivo (valida que la factura sea del proveedor en
     sesión) y admin.pagos.factura-archivo (auth.admin). Vista proveedores/facturas usa esas rutas.
  2. Docs fiscales (expediente_fiscal): PortalProveedorController, EmpresaApiController (2),
     SolicitudModificacionDatosService (cif+acta). Se ven por admin.expediente-fiscal.descargar
     (ya existía; se ajustó para buscar en app/private primero). 
  3. Proveedor extranjero (foreign_supplier): nuevo descargarDocumento + ruta
     proveedores.documento.archivo (valida pertenencia). Vista admin usa expediente-fiscal.descargar.
  4. Expediente de pago (pagos_comprobantes: adjuntos, autorización, comprobantes, formato):
     ruta FIRMADA admin.pagos.expediente-archivo + helper urlExpedienteArchivo() en PagoProveedorService.
  5. Formato de pago del abono (pagos_formatos): AdminPagoProveedoresController.
  6. Anticipos (anticipos/pdf, /xml): AdminPagoProveedoresController; anticiposFormatoAdjunto sirve de local.
  7. Reembolsos y gasolina: AdminPanelController, PortalEmpleadoController, ReembolsoViajeController.
     Ruta GENÉRICA FIRMADA admin.archivo-privado (lista blanca de carpetas). Vistas reembolsos-viaje/ver
     y admin/facturas usan signedRoute.
- **PATRÓN usado:** disco 'local' + ruta protegida. Dos tipos de protección:
  (a) rutas con {id} que validan pertenencia/sesión (facturas, documento);
  (b) rutas FIRMADAS (signedRoute + hasValidSignature) con lista blanca de carpetas, para cuando la
  ruta del archivo viaja como parámetro (expediente, archivo-privado). La firma evita pedir archivos
  arbitrarios del disco.
- **COMPATIBILIDAD:** todas las rutas buscan en 'local' y, si no, en 'public' (archivos viejos
  subidos antes de la migración siguen viéndose). Como producción casi no tiene archivos aún (697 MB),
  el impacto de archivos viejos es mínimo.
- **NO migrado (sigue público, está bien):** fotos de perfil/producto, Excels de proceso.
- **COMANDO limpiar:basura** (app/Console/Commands/LimpiarBasuraProduccion.php): borra scripts de
  prueba sueltos de la raíz y storage/app. Pide confirmación (--force para saltarla). NO toca
  carpetas del sistema. Correr en producción tras desplegar: `php artisan limpiar:basura`.
- **VERIFICADO:** php -l de los 10 archivos OK, 5 rutas registradas, comando registrado, view:cache OK.
- **PENDIENTE a futuro:** mover estos mismos a S3 cuando haya cuenta AWS (seguridad ya resuelta;
  falta escalabilidad). Opcional: migrar los archivos viejos de public→local y quitar el respaldo 'public'.

---

## 2026-09-07 (histórico) — Documentos sensibles estaban en disco PÚBLICO (ya resuelto arriba)

- **PROBLEMA:** casi todos los archivos subidos se guardan en el disco `public`
  (storage/app/public), que es accesible por LINK DIRECTO sin login. O sea, cualquiera con la
  URL puede abrir facturas de proveedores, constancias fiscales, comprobantes de pago, etc.
- **DETECTADO POR:** Said (buena observación) — "no todos los docs deberían ser sensibles/privados".
- **CLASIFICACIÓN:**
  - 🔴 SENSIBLE (debe ir a disco `private` + ruta protegida con login/permiso):
    facturas de proveedores (PDF/XML), constancias fiscales, actas, comprobantes del banco (CEP),
    formatos de pago, autorizaciones, documentos de reembolsos de empleados.
  - 🟢 Puede seguir público: fotos de productos, fotos de perfil, logos/imágenes decorativas.
- **SOLUCIÓN:** mover los sensibles a disco `private` (storage/app/private) y servirlos por una
  ruta protegida que verifique sesión + permiso antes de entregar el archivo (como YA se hace con
  las solicitudes de modificación CIF/acta en SolicitudModificacionDatosService). Hacerlo por
  tipos, empezando por facturas (lo más sensible). NO es trivial: hay que migrar archivos
  existentes de public→private y crear las rutas protegidas + actualizar las vistas que los
  muestran (hoy usan asset('storage/...')).
- **ESTADO:** pendiente; hacerlo como su propio proyecto, bien probado. Prioridad a definir con Said.

---

## 2026-10-16 — ELIMINADO el módulo "Pago a proveedor" (lo sustituye el CEP del banco)

- **QUÉ:** Se quitó "Pago a proveedor" del sistema. Ya NO se usa porque el flujo nuevo marca las
  facturas como 'pagada' cuando Karen/Alexa suben el comprobante del banco (CEP) en el Expediente
  de pago (AdminPagosController@adjuntarDocumento → marcarPagadasDesdeComprobanteBanco).
- **POR QUÉ se pudo quitar sin romper nada (verificado con investigación):**
  - El "Historial de abonos" NO lee de AbonoProveedor (lee de facturas liquidadas + anticipos).
  - El "Abono al proveedor"/saldar en 0 solo necesita que la factura esté 'pagada' (ya no importa
    quién la puso ahí); el CEP la pone. El reflejo a Contpaqi/WieseBanco sigue igual.
  - Los KPIs de esa pantalla solo vivían en esa pantalla.
- **QUÉ SE TOCÓ:**
  1. routes/web.php: eliminadas las 8 rutas admin.pago-proveedores.*
  2. layouts/admin.blade.php: quitado el enlace "Pagos" del menú (se conserva "Expediente de pago").
  3. AutenticacionAdmin.php: quitado 'admin/pago-proveedores' del permiso 'pagos' (se conservan
     abono-proveedor, historial-abonos, expedientes-pago).
  4. AdminPagosController@alertasJson: la alerta 'pago_realizado' ya no apunta a la ruta borrada
     (ahora a admin.pagos).
  5. Mensajes del saldar en 0 (validarSecuenciaFacturas / abonoInternoConfirmar): ya no dicen
     "registrar en Pago a proveedor", ahora dicen "sube el comprobante del banco (CEP)".
  6. Borradas las 4 vistas resources/views/admin/pago-proveedores/ (index, form, show, comprobante-pdf).
- **LIMPIEZA HECHA:** se borraron los 8 métodos muertos (index/nuevo/create/facturasJson/store/
  show/comprobantePago/cancelar) del AdminPagoProveedoresController — 577 líneas eliminadas.
  Se CONSERVARON los helpers compartidos que usan Anticipos/Abono interno (polizaOrFail,
  extraerTextoPdfNormalizado, normalizarIdentificador, extraerUuidDePdf/Xml, siguienteFolio,
  proveedoresParaFlujoPago, folioSugeridoWieseBanco, validarSecuenciaFacturas, departamentoDelAdmin)
  y los módulos vivos (abonoInterno*, historialAbonos, anticipos*). Verificado: php -l OK,
  rutas vivas resuelven, view:cache OK, sin referencias activas a pago-proveedores (solo un comentario).
  OJO: al editar con PowerShell cuidar el BOM (Set-Content UTF8 mete BOM y rompe el <?php;
  se corrigió escribiendo con UTF8 sin BOM).
- **CONSERVADO:** el modelo AbonoProveedor y su tabla abonos_proveedor (histórico). Si algún día se
  quieren borrar del todo, es una migración aparte (irreversible).
- **OJO al desplegar:** correr php artisan route:clear + view:clear en SiteGround (se quitaron rutas
  y vistas). Verificado en local: route:list ya no muestra pago-proveedores, php -l OK, view:cache OK.

## 2026-10-16 — HECHO: Flujo de correos automáticos del pago (petición de Alexa)

- **QUÉ:** Se implementaron los 4 correos automáticos del flujo de pago + el disparador de
  "marcar pagadas". Todo con destinatarios configurables en config/correos_pago.php.
- **LOS 4 CORREOS:**
  1. Formato CONFIRMADO → correo al autorizador (Alexa). Disparado en AdminPagosController@confirmar
     y en store() con confirmar=1. (Se eligió confirmar, no borrador, porque el expediente solo
     muestra pagos confirmados.)
  2. AUTORIZADO → correo al pagador (Karen) para que descargue layout y pague. En autorizarExpediente.
  3. RECHAZADO → correo al departamento con el motivo + las facturas 'programada' del lote VUELVEN a
     'pendiente' (acordado con Alexa). En rechazarExpediente.
  4. Comprobante del banco (CEP) subido en el expediente → marca las facturas 'programada' como
     'pagada' (monto_pagado=total) y avisa por correo a cada departamento dueño. En adjuntarDocumento
     (se detecta por el tipo que contenga 'banco' o 'cep'). El PDF del CEP se guarda en disco PRIVADO
     (ya quedó así en la migración de seguridad).
- **ARQUITECTURA:**
  - config/correos_pago.php: mapeo de correos (autorizador, pagador, departamentos). Editable sin
    tocar código; cada valor se puede sobreescribir por .env (CORREO_AUTORIZADOR, CORREO_PAGADOR,
    CORREO_DEPTO_*). Correo vacío = no se manda (solo se loguea).
  - app/Mail/NotificacionFlujoPago.php: una sola clase Mail parametrizable (asunto, título, líneas,
    botón) para los 4 avisos. Vista: resources/views/emails/flujo-pago.blade.php.
  - app/Services/CorreosFlujoPagoService.php: centraliza los 4 envíos. Todos en try/catch (si el
    correo falla NO tumba la operación; solo loguea).
- **CORREOS CONOCIDOS (config actual):** Alexa alexa.salzar@wiese.com (CONFIRMAR si es .com o
  .com.mx — Said escribió ambos), Karen karen.bravo@wiese.com, Mantenimiento blanca.paganoni@wiese.com.
  Resto de departamentos: vacíos, llenar cuando se tengan.
- **PENDIENTE para que los correos SALGAN de verdad:** cuenta de correo oficial (Genaro la crea
  mañana). Hoy saldrían por el Gmail de pruebas del .env. Cuando esté la cuenta, solo cambiar el
  bloque MAIL_* del .env. NO toca código.
- **FUERA DEL SISTEMA (acordado):** los avisos Karen↔Alexa de "ya pagué"/"ya firmé" NO los maneja
  el sistema; eso lo hacen ellas por fuera. El sistema solo retoma cuando Karen sube el CEP (correo 4).
- **VERIFICADO:** php -l OK, config carga OK (tinker), vistas compilan OK.
- **FALTA PROBAR en local:** generar→confirmar (correo 1), autorizar (correo 2), rechazar (correo 3
  + facturas a pendiente), subir CEP (correo 4 + pagadas). Con MAIL_MAILER=log se ven en laravel.log.

---

## (resuelto arriba) Flujo de correos automáticos del pago (petición de Alexa)

Alexa (quien autoriza pagos) pidió que CADA movimiento del flujo mande correo AUTOMÁTICO (por
Outlook). Hoy el sistema solo manda UN correo automático: al CONFIRMAR el formato, va al proveedor
(`PagoProveedorService::notificarPagoConfirmadoAlProveedor`). Falta construir el resto. Flujo pedido:

1. Un depa genera Formato para pago → se genera PDF → correo AUTOMÁTICO a Alexa (según el depa).
2. Formato se va al Expediente.
3. En el expediente Alexa RECHAZA (❓ falta definir qué pasa) o AUTORIZA.
4. Si autoriza → se genera PDF de "autorizado" + se habilita descargar layout → correo a Karen (ya autoricé).
5. Karen (ya autorizado) descarga el layout del expediente.
6. Karen sale del sistema → sube layout a BBVA (fuera de Salcom) → paga.
7. Karen avisa a Alexa que pagó → Alexa firma (en banco) → Alexa avisa a Karen que firmó.
8. Karen descarga los PDFs del banco → los sube al expediente en Salcom Link.
9. Al subir los PDFs → facturas se marcan PAGADAS → correo a los departamentos dueños ("ya se pagó").
10. Los departamentos revisan en Salcom Link.
11. Karen salda los pagos en 0 (ya quedó en Wiese/Contpaqi).

DUDAS por resolver antes de implementar:
- RECHAZO: ¿correo a quien generó el formato + motivo? ¿la factura regresa a "pendiente" o se queda?
- Correo paso 1: ¿siempre a Alexa o cada depa tiene correo distinto? Falta el/los correos reales
  y el mapeo departamento→correo (depas: compras, fletes_acatlan, imex, mantenimiento, framfoods_a,
  framfoods_b, seguridad, otros).
- Disparador de "pagadas": ¿subir el PDF del banco (CEP) en el expediente marca pagadas + avisa a
  los depas, o eso lo sigue haciendo Karen en "Pago a proveedor" como hoy?
- Avisos Alexa↔Karen (pagó / firmó): ¿automáticos por el sistema con botones, o lo hacen por fuera?
- Correo: hoy SALE por Gmail SMTP (padillsaid39@gmail.com). ¿Cambiar a cuenta Outlook de Salcom
  (correo + contraseña de aplicación) o dejar el Gmail actual aunque los destinatarios usen Outlook?

Dónde tocaría: `AdminPagosController` (expediente/autorizar/rechazar/subir PDF),
`PagoProveedorService`, nuevas clases en `app/Mail/`, `config/mail.php`/.env, y un mapeo
departamento→correo (quizá en `PagoProveedor::DEPARTAMENTOS` o config nuevo).

---

## 2026-09-07 — Formato para pago: barra del botón verde movida ARRIBA

- **QUÉ:** La barra flotante con el botón verde "+ Agregar al carrito" pasó de estar abajo
  (sticky bottom) a estar ARRIBA de la tabla (sticky top).
- **POR QUÉ:** estando abajo había que bajar toda la tabla (es larga) para ver el botón.
- **DÓNDE:** `resources/views/admin/pagos/index.blade.php` → div `#fp-barra-carrito` movido antes
  de la tabla; `bottom:0`→`top:0` y sombra `0 -4px`→`0 4px`.

## 2026-09-07 — Formato para pago: un solo botón verde para varios proveedores

- **QUÉ:** En la tabla global de "Formato para pago" cada factura tiene checkbox. Se marcan
  facturas de VARIOS proveedores a la vez y UN solo botón verde "+ Agregar al carrito" (barra
  flotante abajo) las manda todas juntas. Muestra cuántas facturas y de cuántos proveedores
  llevas. Reemplaza el botón gris por-proveedor anterior.
- **POR QUÉ (petición dirección):** el botón gris por proveedor se veía feo y obligaba a agregar
  proveedor por proveedor; se quería uno verde que junte todo sin entrar al detalle.
- **CÓMO (backend):** nuevo endpoint `carritoAgregarMulti` (ruta `admin.pagos.carrito.agregar-multi`)
  que recibe `seleccion[codigo][]=folio` (folios agrupados por proveedor). La lógica de agregar
  UN proveedor se extrajo al método privado `agregarProveedorAlCarrito()` que reutilizan tanto
  `carritoAgregar` (uno) como `carritoAgregarMulti` (varios), sin duplicar código.
- **CÓMO (vista):** los checkboxes se asocian a un form global `#fp-form-multi` por el atributo
  `form=`; el name `seleccion[CODIGO][]` agrupa por proveedor. Barra flotante (sticky bottom) con
  el botón verde `.fp-btn-carrito-global`; JS cuenta facturas y proveedores distintos y muestra/
  oculta la barra. "Seleccionar todas" por proveedor sigue en cada separador.
- **DÓNDE:** `app/Http/Controllers/AdminPagosController.php` (carritoAgregarMulti +
  agregarProveedorAlCarrito), `routes/web.php` (ruta nueva),
  `resources/views/admin/pagos/index.blade.php` (checkboxes seleccion[], form global, barra verde, JS).

## 2026-09-07 — Fix: carrito de pago salía en 0 facturas en SiteGround

- **QUÉ:** Al agregar un proveedor al carrito en producción, salía con $0.00 y 0 facturas, y
  al "Generar formato de pago" daba "No se pudieron preparar las facturas (¿Wiese respondió?)".
  Luego el reporte del pago daba error 500.
- **POR QUÉ:** `carritoAgregar` traía las facturas con `listarFacturasProveedorPorRFC()`, que
  pega al 250 directo (docsUrl vacío en SiteGround) → 0 facturas. Además se saltaba a los
  proveedores con RFC vacío. Se cambió a `listarFacturasPendientesTodos(..., $codigo)` (filtra
  por código y pasa por el puente 5009), igual que la tabla y la materialización.
- **DÓNDE:** `app/Http/Controllers/AdminPagosController.php` → `carritoAgregar()`.
- **PENDIENTE:** el pago #28 quedó guardado sin facturas (basura de la prueba fallida); su
  reporte da 500. Hay que cancelarlo/limpiarlo en la BD (no se tocó producción sin permiso).

## 2026-09-07 — Blanca Paganoni: acceso a Formato para pago

- **QUÉ:** Se le dio a `blanca.paganoni` la sección `pagos` (antes solo tenía `alta_mto` y
  `catalogo`). Con eso ya entra a Formato para pago y al resto del módulo de pagos.
- **POR QUÉ:** dirección pidió que Blanca pueda acceder a Formato para pago.
- **OJO:** la sección `pagos` abre TODO el módulo (pago-proveedores, abono, historial,
  expedientes), no solo Formato para pago. Si se quiere acceso más fino, habría que crear
  una sección nueva.
- **DÓNDE:** `app/Models/AdminUser.php` → const `ACCESOS_RESTRINGIDOS`, línea de blanca.paganoni.

## 2026-09-07 — Lupa y lista de proveedores vía puente 5009 (producción)

- **QUÉ:** La lupa (buscar proveedor por código/RFC) y la pantalla de Proveedores no cargaban en
  SiteGround porque pegaban al 250 directo. Ahora, igual que la tabla, cuando `PAGOS_API_URL`
  (5009) está lleno usan el puente 5009 sin exigir docsUrl ni login al 250.
- **POR QUÉ:** mismo problema raíz que la tabla: en SiteGround docsUrl va vacío, así que
  validarDocsConfiguracion/loginServicio abortaban antes de llegar al 5009.
- **DÓNDE:** `app/Services/ProveedorApiService.php` → `listarProveedoresWiese()` (ruta
  `/PagoProveedor/ListarProveedores`) y `buscarProveedorWiese()` (rutas
  `/PagoProveedor/BuscarPorCodigo` y `/PagoProveedor/BuscarPorRFC`). `buscarProveedorPorRFC()`
  se arregla solo porque reutiliza `buscarProveedorWiese()`.
- **C# (5009):** se agregaron en `PagoProveedorController` los endpoints `ListarProveedores`,
  `BuscarPorCodigo`, `BuscarPorRFC` (patrón idéntico a `ListarDocumentos`: login al 250 +
  reenvío). NOTA: Genaro cambió la IP pública del 5009 a 189.254.241.195 (antes .194).

## 2026-09-07 — Fix: Formato para pago salía vacío en SiteGround (producción)

- **QUÉ:** En SiteGround la tabla global de "Formato para pago" nunca cargaba (decía
  "Aún no hay facturas pendientes"), aunque el servidor SÍ alcanzaba la API 5009 (probado con
  curl y file_get_contents). Se reordenó la lógica para que, cuando `PAGOS_API_URL` (el 5009)
  esté configurado, el método NO exija `PROVEEDOR_API_DOCS_URL` ni haga login al 250.
- **POR QUÉ:** `listarFacturasPendientesTodos()` llamaba `validarDocsConfiguracion()` y
  `loginServicio()` como PRIMERA cosa. En SiteGround `docsUrl` va vacío (no alcanza el 250),
  así que el método moría en esa validación y nunca llegaba a usar el 5009. El 5009 ya hace
  su propio login interno, por eso en ese camino no se necesita docsUrl ni token del 250.
- **DÓNDE:** `app/Services/ProveedorApiService.php` → `listarFacturasPendientesTodos()`.
  Se movió el cálculo de `$pagosUrl` arriba y se metieron las validaciones de docs/login
  SOLO dentro de la rama del 250 directo (oficina/local). Token vacío ya no manda header Bearer.

## 2026-09-09 — Formato para pago: tabla global de proveedores (estilo reporte Wiese)

- **QUÉ:** "Formato para pago" ya NO arranca vacío pidiendo buscar. Ahora muestra de entrada la
  lista de proveedores con sus facturas pendientes, ordenados por código (consecutivo), con las
  facturas VENCIDAS marcadas en rojo. El buscador de arriba sigue para abrir uno específico.
- **POR QUÉ así (petición Brenda):** quería ver la tabla tipo su reporte de Wiese sin tener que
  buscar uno por uno; y pagar un proveedor específico (ej. ORPACK) buscándolo.
- **DE DÓNDE salen los datos (importante):** EN VIVO del endpoint de Wiese
  `/DatoDocumentoProveedor/ListarDocumentosRFC` con **RFC VACÍO = todos** (ajuste que hizo Alan).
  NO se precarga ni duplica nada en la BD local (Said no quería desincronía). Una sola llamada,
  Wiese es la única fuente de verdad.
- **CAMPOS:** el endpoint ya devuelve calculadas las columnas del reporte (vencido8Dias,
  vencido15Dias, vencidoMas15Dias, vencidoTotal, saldoDlls, importePago, ivaFacturaDLLS), así que
  no se recalcula nada del lado de Salcom.
- **DÓNDE:**
  - `ProveedorApiService::listarFacturasPendientesTodos()` (NUEVO): llama el endpoint con rfc=''.
  - `AdminPagosController::index()`: arma `$tablaProveedores` (facturas agrupadas por proveedor,
    ordenadas por código) + `$tablaError`. A prueba de fallos: si Alan aún no libera el RFC vacío
    o Wiese no responde, la tabla sale vacía con aviso y la página NO se rompe.
  - `resources/views/admin/pagos/index.blade.php`: tabla con fila-cabecera por proveedor + una
    fila por factura; vencidas en rojo (#fef2f2 fondo, saldo/fecha en rojo).
- **YA QUEDÓ (2026-09-09):** Alan liberó el endpoint. CAMBIÓ DE NOMBRE a
  `/DatoDocumentoProveedor/ListarDocumentos` (sin RFC; solo fechaInicial/fechaFinal) en el 7183.
  Probado desde el método real: devuelve **1,936 facturas de 175 proveedores distintos**. ✓
- **AJUSTES que hubo que hacer del lado Salcom:** (1) cambiar el endpoint a `ListarDocumentos`,
  (2) quitar el parámetro `rfc`, (3) subir el timeout a 180s (trae ~2,000 facturas y tardaba),
  (4) fechas en formato corto (2020-01-01). Todo en `listarFacturasPendientesTodos()`.
- **OJO validar:** el campo `vencidoTotal` venía 0 aunque hay facturas con fecha de vencimiento
  pasada (2020, 2022). Por eso la tabla marca "vencida" por DOS vías: `vencido_total > 0` O que la
  fecha de vencimiento ya pasó. Así las viejas SÍ salen en rojo. Confirmar con Brenda que el rojo
  salga donde ella espera.
- **AL VALIDAR cuando Alan termine (checklist):**
  1. Entrar a Formato para pago → la tabla debe llenarse sola.
  2. Si sale VACÍA: revisar que el parámetro sea `rfc` (minúsculas) vs `RFC`; es lo más probable.
     Se ajusta en `listarFacturasPendientesTodos()` (ProveedorApiService).
  3. Comparar números LADO A LADO con el reporte de Wiese de Brenda.
  4. Verificar "Total dólares" (hoy lee `saldoDlls`) e "Importe pago" (`importePago`, venía 0):
     confirmar con Brenda si esos valores son los correctos o leen otro campo.
- **COLUMNAS (ajuste Brenda):** se QUITARON las 3 de "Vencido 8/15/+15 días" (Wiese las manda en 0).
  Quedan 11: Serie, Folio, Fecha factura, Vence, Total, Saldo pendiente, Referencia, Total dólares,
  Importe pago, IVA dólares, Moneda. Separador por proveedor usa clase `date-row` (morado estándar).
  El ROJO va SOLO en la columna "Vence" (antes era toda la fila). Scroll horizontal ESPEJO arriba
  (barra sincronizada con JS); solo aparece si la tabla no cabe en pantalla.
- **BUG 2 CORREGIDO (no se podían SELECCIONAR las facturas / no salía la tabla con checkboxes):**
  `proveedor.blade.php` envolvía TODA la sección de facturas en `@if($rfc !== '')`. Para SHEN FA
  (RFC vacío) el resumen decía "1 factura" pero la tabla con checkboxes NO se pintaba, así que no
  se podía seleccionar ni mandar al carrito. FIX: se quitó ese `@if($rfc)` (y su @endif); la tabla
  se muestra siempre que haya facturas, sin importar el RFC (ya se traen por código).
- **BUG 3 CORREGIDO (carrito por RFC):** `materializarFacturasSeleccionadas()` también buscaba por
  RFC; se cambió a `listarFacturasPendientesTodos(..., $codigo)`. Así "agregar al carrito" y "pagar
  solo este" funcionan para proveedores con RFC vacío.
- **PESTAÑAS (petición Brenda, visual):** la pantalla ahora tiene 2 pestañas sin recargar:
  "Formato para pago" (buscador + tabla de proveedores) y "Formatos para pago creados" (tabla
  FCONA). Solo CSS/JS en index.blade.php; no toca lógica ni API.

- **BUG CORREGIDO (detalle del proveedor daba "0 facturas"):** la tabla global trae por CÓDIGO
  (endpoint ListarDocumentos), pero el detalle (AdminPagosController::proveedor) buscaba por RFC
  (ListarDocumentosRFC). Proveedores como SHEN FA (RFC vacío/no encontrado por ese método) salían
  con 0 aunque en la tabla sí tenían factura. FIX: el detalle ahora usa
  `listarFacturasPendientesTodos(..., $codigo)` (mismo endpoint, filtrado por código). Nuevo
  parámetro `$codigoFiltro` en el método del servicio. Verificado: SHEN FA (21306085) devuelve su
  1 factura; ORPACK 321. La vista proveedor.blade.php ya NO depende de $rfc para contar.

---

## PENDIENTE (validar con Alexa) — Fusionar "Pago a proveedor" dentro del Expediente

- **CONTEXTO:** Said notó que el módulo "Pago a proveedor" se siente redundante. El flujo real
  es: Formato → Expediente (autoriza + baja Excel + paga en banco) → ??? → Abono (liquida).
  El paso que marca la factura 'pagada' hoy vive en "Pago a proveedor", un módulo aparte.
- **IDEA APROBADA por Said (falta validar con Alexa el martes AM):** mover el paso 'pagada' AL
  EXPEDIENTE. Cuando Alexa regrese del banco con los comprobantes (CEP, un PDF por pago), los
  sube EN EL EXPEDIENTE y ESO marca las facturas como 'pagada'. Así "pagada" = "el banco ya
  mandó el dinero y aquí está la prueba", no solo "autorizado". Y el módulo "Pago a proveedor"
  se retira del flujo (una sola pantalla: el expediente).
- **POR QUÉ no se hizo ya:** toca el corazón del flujo; Said lo valida con Alexa/Karen (es su
  proceso diario) antes de tocar código.
- **PLAN cuando se apruebe:**
  1. En el expediente, agregar paso "Confirmar pago (subir comprobante del banco / CEP)".
  2. Al subir el CEP → las facturas del pago pasan de 'programada' a 'pagada' + se guarda el PDF
     como prueba. (Hoy `adjuntarDocumento()` SOLO guarda el archivo, NO cambia estatus: ESE es el
     eslabón que falta de conectar.)
  3. Retirar/ocultar el módulo "Pago a proveedor" del menú (OCULTAR, no borrar, por si acaso).
- **DECISIONES ABIERTAS para Alexa:**
  a) ¿'pagada' se dispara al subir el CEP, o con un botón "Confirmar pago" aparte? (reco: botón
     que haga las dos cosas, para no marcar pagada sin prueba).
  b) ¿El módulo viejo se oculta un tiempo o se quita de una? (reco: ocultar).
- **DÓNDE tocaría:** `AdminPagosController::adjuntarDocumento()` (conectar estatus),
  `expediente.blade.php` (sección confirmar pago), `layouts/admin.blade.php` (ocultar módulo),
  validación `ORDEN_FLUJO` en `AdminPagoProveedoresController` (que programada→pagada siga bien).
- **OJO (riesgo de negocio):** autorizar ≠ pagar. NO marcar 'pagada' al autorizar; solo cuando
  hay comprobante del banco. Si el banco rechaza una transferencia, la factura NO debe quedar
  como pagada.

---

## 2026-09-09 — Pagos vía API pública 5009 (puente a Wiese, para SiteGround)

- **QUÉ:** la tabla de "Formato para pago" ahora puede consultar las facturas a través de la API
  pública APIPortalWeb (`http://189.254.241.194:5009/api`), que hace de PUENTE y reenvía al 250
  interno (172.16.1.250:7183). Así SiteGround (que no alcanza el 250) sí obtiene las facturas.
- **POR QUÉ:** el 250 es interno y no se expone a internet. Alan/Said agregaron en APIPortalWeb
  (controlador PagoProveedor) un endpoint `ListarDocumentos` que por dentro llama al 250.
- **CÓMO (lado Laravel):** NUEVA env `PAGOS_API_URL` (config `proveedor_api.pagos_url`). Si está
  llena, `listarFacturasPendientesTodos()` usa esa URL + ruta `/PagoProveedor/ListarDocumentos`.
  Si está vacía, cae al 250 directo (`docs_url` + `/DatoDocumentoProveedor/ListarDocumentos`),
  que sirve en la oficina/VPN. NO se tocó `PROVEEDOR_API_DOCS_URL` (los demás endpoints —login,
  facturas por RFC, OC— siguen yendo al 250; el 5009 solo tiene ListarDocumentos).
- **DÓNDE:** `.env` (PAGOS_API_URL), `config/services.php` (pagos_url),
  `ProveedorApiService::listarFacturasPendientesTodos()` (elige base+ruta según pagos_url).
- **PROBADO:** con PAGOS_API_URL llena, Laravel trae 1,994 facturas / 186 proveedores vía 5009 en
  ~2s. Flujo completo: Laravel → 5009 (pública) → 250 (interna) → Wiese. ✓
- **OJO seguridad (pendiente):** el 5009 se expone a internet; conviene protegerlo (token/IP) para
  que no cualquiera lo llame. Said tiene el código del token pendiente de pedir.

---

## 2026-09-09 — Excel del banco: ajustes de columnas (petición Brenda)

- **B CUENTA DE ABONO (CLABE):** se deja como TEXTO (18 dígitos exactos, con el 0 inicial, ej.
  072320006470108493) y se ENSANCHA la columna B a 22 para que no se vea "####". POR QUÉ texto y
  no número: como número Excel pierde el 0 de la izquierda (quedaba 17 díg) y el banco la
  rechazaría. El "####" no era error del dato, solo ancho de columna. (Decisión A de Said.)
- **E MOTIVO DE PAGO:** siempre "SALCOM" (fijo), ya no el nombre del proveedor.
- **H REFERENCIA NUMÉRICA:** los ÚLTIMOS 7 dígitos de la referencia/folio (`substr($dig, -7)`).
- **DÓNDE:** `LayoutBancarioService::generarXlsm()`. Probado: CLABE 18 díg, motivo SALCOM, ref 7
  díg, macros conservadas.

---

## 2026-09-09 — Layout del banco: ahora descarga el EXCEL macro (no el .txt)

- **QUÉ:** el botón del expediente que antes bajaba `pagosmix.txt` ahora descarga una COPIA del
  Excel "Pagos Mixtos" (.xlsm) PRE-LLENADO con las facturas del formato de pago. Alexa lo abre,
  revisa, y con el botón "Generar archivo" (macro VBA del propio Excel) crea ella el .txt para
  BBVA NetCash.
- **POR QUÉ (petición Said/Alexa):** Alexa quiere conservar su paso de revisión/confirmación en
  el Excel antes de generar el .txt. El .txt directo le quitaba ese control.
- **CÓMO conserva formato y macros:** se parte de la plantilla real (copiada al proyecto) y SOLO
  se escriben valores en las celdas; nunca se tocan estilos ni el VBA. Probado: el .xlsm generado
  conserva macros (hasMacros()=true), ambas hojas (pagosmix/consideraciones) y el formato azul.
  CLABE/RFC/referencia se escriben como TEXTO explícito para que Excel no los vuelva notación
  científica ni les quite ceros.
- **DÓNDE:**
  - Plantilla: `storage/app/plantillas/pagos-mixtos.xlsm` (copiada de
    `C:\Users\IT\Documents\Pagos Mixtos  FLETES SALCOM.xlsm` — OJO: dos espacios en el nombre).
  - `app/Services/LayoutBancarioService.php` → nuevo método `generarXlsm()` (const PLANTILLA_XLSM,
    XLSM_FILA_INICIO=3). `generarTxt()` se CONSERVA por si se quiere el .txt directo a futuro.
  - `AdminPagosController::exportarLayoutBanco()` → ahora `response()->download($xlsm)...
    ->deleteFileAfterSend(true)`.
  - `resources/views/admin/pagos/expediente.blade.php` → botón "Descargar Excel para el banco" +
    texto que explica el paso de "Generar archivo".
  - `storage/app/.gitignore` → excepción `!plantillas/` para que la plantilla VIAJE al servidor
    (si no, el botón tronaría en producción).
- **DEPLOY:** al subir a git, la plantilla ya se incluye (verificado con git check-ignore).

---

## 2026-09-09 — Proveedores: la búsqueda ahora incluye el CÓDIGO

- **SÍNTOMA:** en la pantalla Proveedores, buscar por código (ej. M214884091 - BEBIDAS
  PURIFICADAS) no devolvía nada, aunque el proveedor SÍ existe en Wiese.
- **CAUSA:** el filtro de búsqueda en `AdminPanelController::proveedores()` solo comparaba
  NOMBRE y RFC, pero el placeholder de la caja promete "ID proveedor" (el código). Faltaba
  incluir el código en la comparación.
- **ARREGLO:** se agregó `$codigo` al filtro (lee 'codigo'/'Codigo'); ahora busca por nombre,
  RFC o código. Verificado contra la API: por nombre/RFC siempre funcionó; solo faltaba código.
- **DÓNDE:** `app/Http/Controllers/AdminPanelController.php`, dentro de `proveedores()`, el
  `->filter()` del bloque `if ($busquedaWiese !== '')`.
- **NOTA (no bug):** si al buscar por NOMBRE no sale, revisar que el chip de moneda esté en
  "Todos" y no en "Dólar/Extranjeros": esos chips filtran por moneda y pueden esconder al
  proveedor (BEBIDAS es MXN, moneda="1"). Con "Todos" aparece.
- **CONFIRMADO:** BEBIDAS PURIFICADAS NO tiene facturas pendientes (la API devuelve 0); por eso
  no sale en "Formato para pago" (ese módulo solo lista proveedores con pendientes). Eso es
  correcto, no es bug.

---

## 2026-09-09 — WieseBanco: dejar SOLO BBVA en el menú (bug "no aparece el abono")

- **SÍNTOMA:** Karen hacía el abono (saldar en 0), entraba a WieseBanco y la hoja salía vacía,
  aunque el movimiento SÍ se guardaba en la base (verificado: ORPACK folio 80195 en cuenta
  clave_corta 8969, estatus pendiente_contpaqi porque Contpaqi no está en local).
- **CAUSA RAÍZ:** el submenú WieseBanco listaba 7 bancos (bbva, santander, banorte, citibanamex,
  hsbc, scotiabank, banco-base) desde `config/wiese_bancos.php`, pero SOLO BBVA tiene cuenta real.
  `AdminPanelController::claveCuentaWiese()` solo mapea 'bbva'=>'8969'; los otros 6 devuelven ''.
  Si Karen entraba a un banco que no era BBVA, no veía el movimiento (todo vive en 8969). Confuso.
- **DECISIÓN (Opción A, elegida por Said):** dejar en el menú SOLO BBVA. Mostrar solo lo que
  funciona. Cuando existan las otras cuentas se agregan.
- **DÓNDE:** `config/wiese_bancos.php` → ahora solo `'bbva' => 'BBVA 8969 MXN'`. Se corrió
  `php artisan config:clear` (el config estaba cacheado). El sidebar
  (`layouts/admin.blade.php`) ya itera este config, no hubo que tocarlo.
- **PENDIENTE/duda:** el nombre de la cuenta en la BD es "BBVA 969 SALCOM PESOS" (dice 969, no
  8969). Es solo el título grande dentro de la pantalla. NO se cambió sin permiso de Said; queda
  a su decisión corregirlo a "BBVA 8969 MXN".
- **NOTA:** el movimiento SÍ se refleja al liquidar; en local queda 'pendiente_contpaqi' (normal,
  Contpaqi vive en el server interno). En el server interno saldrá 'enviado'.

---

## 2026-09-09 — Formato para pago: vencimiento + "Referencia / Folio" (petición Alexa)

- **QUÉ:** en el PDF "Formato para pago" (el que Alexa descarga del expediente para autorizar)
  se agregó: (1) arriba, en rojo, "Pagar antes del: DD/MM/AAAA" = vencimiento más próximo de las
  facturas; (2) columna "Vence" por factura en el detalle; (3) se renombró la columna
  "Folio General" → "Referencia / Folio".
- **POR QUÉ:** Alexa mandó por correo lo que necesita ANTES de autorizar un pago: qué se compró,
  hasta cuándo puede pagar (vencimiento), y el recuadro banco/cuenta/clave/referencia/total. El
  recuadro y el total ya salían; faltaba el vencimiento y aclarar que "Folio General" ES la
  referencia que ella pide.
- **DECISIÓN:** NO se creó campo/migración de "referencia": la columna "Folio General" ya era esa
  referencia, solo se renombró. La fecha es la de VENCIMIENTO (emisión + días de crédito), que ya
  vive en `facturas.fecha_vencimiento`; NO la de emisión.
- **DÓNDE:**
  - `app/Services/PagoProveedorService.php` → `datosReporteResumen()`: calcula `fechaLimite`
    (vencimiento más próximo recorriendo `lineas.factura.fecha_vencimiento`).
  - `resources/views/admin/pagos/reporte-resumen-pdf.blade.php`: bloque "Pagar antes del" en el
    header, columna "Vence" en el detalle, encabezado "Referencia / Folio".
- **PENDIENTE/duda:** la "explicación de qué se compró" usa el campo Concepto/Notas; hoy NO es
  obligatorio. Si Alexa lo quiere forzado, hacerlo obligatorio en el formulario (otro paso).

---

## 2026-09-09 — Departamentos oficiales del archivero (RH)

- **QUÉ:** RH pasó los departamentos reales; se reemplazó la lista provisional por los oficiales
  en `PagoProveedor::DEPARTAMENTOS`.
- **POR QUÉ:** la lista provisional (otros/mantenimiento/compras/imex/produccion/auditoria/acatlan)
  era un placeholder; ya hay los que de verdad usan conta.
- **LISTA FINAL:** compras, fletes_acatlan (Fletes - Acatlán), imex, mantenimiento,
  framfoods_a (Framfoods A), framfoods_b (Framfoods B), seguridad, y 'otros' al final como
  cajón de seguridad para pagos viejos.
- **DÓNDE:** `app/Models/PagoProveedor.php`, const `DEPARTAMENTOS`. El selector del carrito y el
  agrupado del expediente la leen de ahí, no hay que tocar nada más.
- **DUDA ABIERTA:** "Framfoods A y B" se tomó como DOS departamentos separados. Si es uno solo,
  juntar en `framfoods` => 'Framfoods A y B'.

---

## 2026-09-09 — Puerta (gateway) para que SiteGround use la API de Wiese

- **PROBLEMA:** SiteGround (35.215.67.229) no alcanza la IP interna de Wiese (172.16.1.250:7183)
  y esa API no tiene seguridad propia (no se puede exponer a internet).
- **SOLUCIÓN:** carpeta `wiese-gateway/` NUEVA (no existía; la pedía el plan de Chuy pero no
  estaba). Es una puerta PHP que se instala en un server de la OFICINA (que sí ve el 7183),
  se publica por HTTPS, y reenvía al 7183 solo si: IP = SiteGround, header X-Salcom-Gateway con
  token correcto, y la ruta está en lista blanca.
- **ARCHIVOS CREADOS:**
  - `wiese-gateway/public/index.php` — la puerta (valida IP+token+ruta, reenvía con cURL,
    propaga Authorization Bearer de Wiese).
  - `wiese-gateway/config.example.php` — plantilla (upstream, token, allowed_ips, allowed_paths
    con los 7 endpoints reales). Chuy la copia a config.php (fuera de public/, con el token real).
  - `wiese-gateway/README.md` — instrucciones de instalación (Parte 1 oficina / 2 SiteGround /
    3 desarrollo).
  - `.gitignore`: se ignora `/wiese-gateway/config.php` (lleva el token, nunca se sube).
- **CÓDIGO SALCOM:** `config/services.php` nuevo `proveedor_api.gateway_token` (env
  WIESE_GATEWAY_TOKEN). `ProveedorApiService` y `ClienteApiService` ahora mandan el header
  `X-Salcom-Gateway` en TODAS las llamadas SI el token está lleno (SiteGround); si vacío
  (oficina), llaman directo al 7183 sin header.
- **PROBADO en local:** con token vacío, la API sigue directa (ORPACK carga). No rompe nada.
- **PENDIENTE (Chuy/infra):** instalar wiese-gateway en el server de oficina, publicar HTTPS,
  firewall (443 solo desde SiteGround, 7183 no a internet), y en SiteGround llenar las URLs +
  WIESE_GATEWAY_TOKEN + `php artisan config:cache`.

## 2026-09-09 — Formato para pago: título + tabla de formatos creados

- **QUÉ:** 1) el título de la pantalla admin.pagos (index.blade.php) cambió de "Pagos al
  proveedor" a "Formato para pago". 2) Se agregó abajo una tabla con los formatos de pago YA
  creados, del más nuevo al más viejo, con separador de fecha date-row (estándar) agrupando por
  created_at, y columna de estatus (Borrador/Pend. autorizar/Autorizado/Rechazado/Cancelado).
- **DÓNDE:** `AdminPagosController::index()` (nueva consulta $formatosCreados, últimos 100
  PagoProveedor ordenados por created_at desc) + `resources/views/admin/pagos/index.blade.php`
  (tabla al final). Folio mostrado: FCONA-#### con el id del pago.

## 2026-09-09 — Layout banco = .txt de ANCHO FIJO (idéntico al de la macro del banco)

- **CONTEXTO:** el Excel "Pagos Mixtos" tiene una macro (botón "Generar archivo") que produce
  `pagosmix.txt`, que es LO QUE REALMENTE SE SUBE a BBVA NetCash. No es CSV ni Excel: es ancho
  fijo (cada campo en posición exacta).
- **ESTRUCTURA (línea interbancaria PSC, 165 chars), descifrada del pagosmix.txt real:**
  PSC + CLABE(18, ceros der) + ctaCargo(18, ceros izq) + divisa(3) + importe(16, ceros izq .00)
  + motivo(30 esp der) + tipoCuenta(2) + bancoReceptor(3 = primeros díg CLABE) + titular(30 esp
  der) + referencia(7 ceros izq) + disponibilidad(1=H) + "0" + RFC(18 esp) + iva(15=000000000000.00).
- **FIX:** `LayoutBancarioService::generarTxt()` genera ese ancho fijo exacto.
  `AdminPagosController::exportarLayoutBanco()` ahora descarga `pagosmix.txt` (text/plain), ya NO
  el Excel/CSV. Se quitó la generación con PhpSpreadsheet.
- **PROBADO:** la línea generada mide 165 chars, idéntica en estructura a la del banco (CLABE
  completa, sin notación científica). Pago de prueba borrado.

## 2026-09-09 — Expediente: "Volver" va al archivero, no al pago

- **QUÉ:** el botón del expediente que decía "Volver al pago" (iba a admin.pagos.show) ahora
  dice "Volver a expedientes" y lleva al ARCHIVERO: a la carpeta del departamento del pago
  (admin.expedientes-pago.departamento) si tiene depto, o al archivero general.
- **POR QUÉ:** quien revisa/autoriza (Alexa) no debe ir al Formato para pago; al terminar un
  expediente debe volver a su lista para seguir con los pendientes.
- **DÓNDE:** `resources/views/admin/pagos/expediente.blade.php`.
- **NOTA (CEP):** "Comprobante del banco (CEP)" = Comprobante Electrónico de Pago que da el
  BANCO tras la transferencia (el PDF de Banxico). Alexa lo SUBE ahí como evidencia de que el
  pago se realizó, para que el expediente quede auditable. No lo genera el sistema.

## 2026-09-09 — Retirado módulo de IA del admin (/admin/ia)

- **QUÉ:** se quitaron las rutas `/admin/ia` (dashboard IA + 3 POST pronostico/inventario/
  proveedor). Módulo viejo que Said ya no usa.
- **MISTERIO RESUELTO:** al recargar/tras un error te mandaba a /admin/ia porque las páginas de
  error (errors/404.blade.php y 500.blade.php) tenían el botón "volver al panel" del admin
  apuntando a `/admin/ia`. Se cambió a `/admin/dashboard`.
- **NO SE BORRÓ:** el IaDashboardController ni las rutas de IA de proveedor/cliente (siguen por
  si esos portales las usan). La vista ia-dashboard.blade.php quedó huérfana (ya no hay ruta que
  la abra) pero no estorba.
- **DÓNDE:** `routes/web.php`, `resources/views/errors/404.blade.php`, `errors/500.blade.php`.

## 2026-09-09 — Layout banco ahora es EXCEL (.xlsx) real, no CSV

- **PROBLEMA:** el CSV convertía la CLABE de 18 díg a notación científica (1.23E+16),
  corrompiéndola → el banco la rechazaría.
- **FIX:** `exportarLayoutBanco()` ahora genera un .xlsx real con PhpSpreadsheet, replicando el
  Excel original de Alexa ("Pagos Mixtos"): fila 1 encabezados de grupo (F1/J1), fila 2 nombres
  de columna EXACTOS, datos desde fila 3. La CLABE (B), tipo cuenta (G) y referencia (H) se
  escriben como TEXTO explícito (setCellValueExplicit TYPE_STRING) → NO se convierten a número.
  Importe (D) e IVA (K) como número con formato #,##0.00.
- **PROBADO:** CLABE queda como texto de 18 caracteres (072320006470108493), importe correcto.
- **DÓNDE:** `AdminPagosController::exportarLayoutBanco()`. (El servicio LayoutBancarioService
  sigue armando las filas; solo cambió cómo se escriben al archivo.)

## 2026-09-09 — Layout banco: solo tras autorizar + rediseño iOS

- **QUÉ 1:** el bloque "Pago al banco" (layout + comprobante CEP) ahora SOLO aparece si el pago
  está AUTORIZADO (`$pago->estaAutorizado()`). Si no, muestra un recuadro con candado: "El
  layout se genera cuando se autorice el pago". POR QUÉ: no debe bajarse el layout para pagar
  algo que Dirección aún no autorizó.
- **QUÉ 2:** rediseño estilo iOS/Apple del bloque (clases .bank-*): system font, tarjeta
  redondeada 18px, icono en cuadro morado, botones "pill", tips en caja amarilla suave, input
  de archivo estilizado.
- **DÓNDE:** `resources/views/admin/pagos/expediente.blade.php` (bloque Pago al banco + CSS).

## 2026-09-09 — FIX: expediente por departamento tronaba (Undefined property $id)

- **SÍNTOMA:** entrar a /admin/expedientes-pago/departamento/{x} daba
  "Undefined property: stdClass::$id" en expedientes-proveedor.blade.php:59.
- **CAUSA:** esa vista (nivel 2) la comparten el flujo por proveedor y el nuevo por depto. Los
  botones de filtro de estatus usaban `$proveedor->id`, pero en modo departamento el $proveedor
  es un stdClass simulado (solo ->nombre), sin ->id.
- **FIX:** la vista ahora usa `$urlFiltro` (URL base de filtro ya armada que manda cada
  controlador). `expedientesProveedor()` y `expedientesDepartamento()` pasan su propia $urlFiltro.
- **DÓNDE:** `AdminPagosController` (ambos métodos) + `expedientes-proveedor.blade.php`.

## 2026-09-09 — Blindar expediente por permiso (en vez de borrar la ruta)

- **CONTEXTO:** Said pidió "quitar la ruta del expediente por las moscas". NO se borró porque
  la ruta `admin.pagos.expediente` la usa el flujo correcto (archivero → departamento →
  expediente, en expedientes-proveedor.blade.php). Borrarla rompería el acceso de Alexa.
- **QUÉ SE HIZO:** candado de permiso en `AdminPagosController::expediente()`: si el admin no
  tiene permiso 'pagos', aborta 403. Así la URL directa queda bloqueada para quien no autoriza,
  pero Alexa/Dirección (con permiso) entra normal. Combinado con que ya se quitó el botón desde
  la pantalla del pago.

## 2026-09-09 — Quitar acceso al expediente desde la pantalla del pago

- **QUÉ:** se eliminó el botón "Expediente de pago" de la pantalla del pago (show.blade.php).
- **POR QUÉ:** quien arma el Formato para pago no debe entrar al expediente (eso es para quien
  autoriza: Alexa/Dirección). Desde la pantalla del pago ahora solo se ve "Ver reporte resumen"
  (el PDF del formato). El expediente se abre únicamente desde el menú "Expediente de pago".
- **DÓNDE:** `resources/views/admin/pagos/show.blade.php` (se quitó el `<a>` a
  admin.pagos.expediente; se conservó "Ver reporte resumen").

## 2026-09-09 — Expediente agrupado por DEPARTAMENTO (opción A: selector)

- **QUÉ:** el archivero de Expedientes de Pago ahora se organiza en carpetas por DEPARTAMENTO.
  Al generar el pago (carrito) se elige el departamento con un selector.
- **DEPARTAMENTOS (provisionales, hasta que RH pase los reales):** otros, mantenimiento, compras,
  imex, produccion, auditoria, acatlan. Definidos en `PagoProveedor::DEPARTAMENTOS` (una sola
  fuente; el selector y el agrupado la leen de ahí).
- **CAMBIOS:**
  - Migración `2026_10_14_120000_add_departamento_a_pagos_proveedor` (columna `departamento`
    nullable). Fillable del modelo actualizado.
  - `carrito.blade.php`: selector "Departamento" (required) en el form de "Generar formato".
  - `AdminPagosController::carritoGenerar()`: valida e guarda el departamento en el pago.
  - `expedientes()` (nivel 1): agrupa por departamento (antes por proveedor); pagos sin depto →
    "Sin departamento". La tabla ahora tiene columna Departamento (se quitó Código).
  - `expedientesDepartamento()` (nuevo, nivel 2) + ruta `admin.expedientes-pago.departamento`:
    muestra los expedientes de un departamento agrupados por mes (reutiliza la vista
    expedientes-proveedor con un objeto proveedor simulado que solo lleva ->nombre).
- **PROBADO:** 2 pagos (compras, mantenimiento) → archivero los agrupa en sus carpetas con
  totales correctos. Pagos de prueba borrados.
- **OJO:** los pagos de UN SOLO proveedor (flujo "Pagar solo este" / store) todavía NO piden
  departamento (solo el carrito). Si se usan, caerán en "Sin departamento". Pendiente: agregar
  el selector también a ese flujo si se decide usarlo.

## 2026-09-09 — Reacomodo del menú lateral (admin.blade.php)

- **NUEVO ORDEN DE SECCIONES:**
  - INICIO → Inicio
  - PRODUCTOS → Alta de Producto, Productos
  - FORMATO PARA PAGO (sección NUEVA propia) → Formato para pago (antes estaba dentro de "Pagos")
  - CONTABILIDAD (antes se llamaba "Pagos") → Abono al proveedor, Anticipo, Nota de crédito
  - REEMBOLSOS A EMPLEADOS (igual) → Reembolsos, Reembolsos Viaje, Bitácora Gasolina
  - PAGOS (antes "Operación") → "Pagos" (antes "Pago a proveedor"), Expediente de pago,
    WieseBanco, Agentes Aduanales (se le quitó su subtítulo "Comercio exterior" para que caiga
    aquí).
  - PROVEEDORES (igual, NO se renombró ni fusionó — Said decidió dejarlo tal cual tras evaluar
    que fusionar Proveedores con Formato para pago trabaría/arriesgaba).
- **RENOMBRES:** "Pago a proveedor" → "Pagos"; sección "Pagos" → "Contabilidad"; sección
  "Operación" → "Pagos"; "Comercio exterior" (subtítulo) se quitó.
- **DÓNDE:** `resources/views/layouts/admin.blade.php`. Se conservaron todos los permisos
  (`$puedePagos`, `$puedeAnticipos`, `$puedeAgentes`, `$adminEsDireccion`). Compila OK.
- PENDIENTE (Said lo explicará): qué hacer con Proveedores vs Formato para pago (se evaluó
  fusionar; se descartó por riesgo. Alternativa segura: renombrar + enlace, sin fusionar).

## 2026-09-09 — Módulo layout bancario (BBVA NetCash) en el expediente

- **QUÉ:** el expediente de pago ahora deja a Alexa exportar el LAYOUT para subir el pago a
  BBVA NetCash, ver un resumen antes de autorizar, y subir el comprobante real del banco.
- **1) Layout banco:** `app/Services/LayoutBancarioService.php` genera las 11 columnas del
  layout (A=cuenta cargo 132458969 Salcom, B=CLABE proveedor de Contpaqi limpia a 18 díg,
  C=divisa, D=importe, E=motivo, F=titular, G=40 CLABE, H=referencia, I=disponibilidad H,
  J=RFC, K=IVA). MAYÚSCULAS sin acentos, máx 400 ops, avisa si falta CLABE. Una fila por
  factura. Método `exportarLayoutBanco()` en AdminPagosController (CSV) + ruta
  `admin.pagos.layout-banco`. Botón "Exportar layout banco" en el expediente + recordatorios
  NetCash (lote mixto, cuentas dadas de alta, máx 400).
- **2) Resumen para autorizar:** `expediente()` pasa resumenExpediente (concepto=notas del pago,
  vence=fecha_vencimiento más próxima, num_proveedores, num_facturas). Recuadro arriba del
  expediente con qué se paga / proveedores / facturas / se puede pagar hasta / total.
- **3) Comprobante del banco (CEP):** NO se genera (lo emite el BANCO tras las transferencias).
  Se agregó en el expediente una sección para que Alexa SUBA el PDF de CEP que descarga de BBVA
  (reutiliza adjuntarDocumento con tipo "Comprobante del banco (CEP)"). ACLARACIÓN: el PDF de
  12 hojas se descarga del banco DESPUÉS de pagar, no lo genera el sistema.
- **NO SE SUSTITUYÓ NADA:** se aclaró con Said que los PDFs que genera el sistema (comprobante
  de autorización sellado + Formato de pago) SÍ tienen valor (evidencia); se conservan. Lo que
  faltaba era AGREGAR el comprobante real del banco, no sustituir.
- **PROBADO e2e:** layout de pago multi (cuenta cargo + CLABE 18díg + aviso sin CLABE), y
  expediente completo (4 grupos + resumen concepto/vence/total). Pagos de prueba borrados.

## 2026-09-09 — Layout del banco (BBVA NetCash) para pagos — flujo de Alexa

- **CONTEXTO:** Alexa (hace los pagos y autoriza expedientes) usa un Excel macro
  "Pagos Mixtos FLETES SALCOM.xlsm" (en C:\Users\IT\Documents) como LAYOUT para subir pagos
  masivos a BBVA NetCash. Ese Excel genera un .txt que importa al banco.
- **FLUJO REAL:** 1) Formato para pago → 2) Expediente de pago (Alexa autoriza) → 3) Hace el
  pago (sube el .txt a BBVA) → 4) descarga comprobantes (CEP Banxico, el PDF FLETES) → 5) Abono.
- **LAYOUT (pestaña 'pagosmix'), columnas:** A=cuenta cargo (132458969=BBVA Salcom),
  B=cuenta abono (CLABE proveedor), C=divisa (MXP/USD/EUR), D=importe, E=motivo,
  F=titular beneficiario, G=tipo cuenta (40=CLABE), H=referencia num, I=disponibilidad (H/M),
  J=RFC, K=IVA.
- **REGLAS banco (pestaña 'consideraciones'):** MAYÚSCULAS, sin caracteres especiales, máx 400
  operaciones, cuentas dadas de alta previamente en el banco, marcar 'lote mixto' al importar.
- **OPORTUNIDAD:** el sistema YA tiene proveedor/CLABE/importe/RFC/referencia. Se puede generar
  ese layout .txt/Excel automático desde el Formato para pago (sobre todo el multi-proveedor),
  ahorrándole a Alexa el llenado manual.
- **ALEXA pide (correo) antes de autorizar:** explicación de qué se compró, fecha de vencimiento/
  hasta cuándo pagar, y recuadro con BANCO/CUENTA/CLAVE/REFERENCIA-FOLIO/TOTAL.
- **PENDIENTE confirmar con Said:** ¿la CLABE de Contpaqi (CTEXTOEXTRA3) sirve para el layout?
  ¿Empezar por el botón "Exportar layout banco" o por el expediente/autorización?

## 2026-09-09 (martes) — Expedientes de Pago: grid de folders → tabla horizontal

- **QUÉ:** la pantalla "Expedientes de Pago" (nivel 1, archivero por proveedor) pasó de mostrar
  tarjetas/folders en cuadrícula a una TABLA horizontal (columnas: Proveedor, Código,
  Expedientes, Pendientes, Monto total, Ver).
- **POR QUÉ:** dirección pidió verlo como lista/tabla, más fácil de escanear.
- **DÓNDE:** `resources/views/admin/pagos/expedientes.blade.php`.
- Se conservó toda la lógica: click en la fila lleva al detalle, punto azul "nuevo", badge de
  pendientes, y la carpeta "Pagos múltiples" sigue enlazando con ?multi=1.

## 2026-09-07 (13) — WieseBanco: el abono SIEMPRE refleja movimiento (ya no "todo o nada")

- **CAMBIO:** `WieseBancoContpaqiService::reflejarFacturasLiquidadas()` ya NO descarta el
  movimiento cuando Contpaqi falla. Ahora SIEMPRE crea el MovimientoBancario:
  - Contpaqi OK  -> estatus 'enviado' + folio/idDocumento de Contpaqi.
  - Contpaqi falla -> estatus 'pendiente_contpaqi' (sin folio Contpaqi) para conciliar después.
- **POR QUÉ:** antes, en local (donde Contpaqi 127.0.0.1:7090 NO existe) el abono se guardaba
  pero WieseBanco quedaba vacío, lo cual confundía (parecía bug). Y en producción, un fallo de
  Contpaqi hacía perder el movimiento en silencio. Guardar como pendiente es más seguro y
  permite demos locales (enseñar a Karen) sin depender de Contpaqi.
- **DEMO:** se reflejó el abono existente de ORPACK ($201,738.07) → WieseBanco muestra
  NUM 80195, payee ORPACK, payment 201738.07, estatus pendiente_contpaqi.
- **PENDIENTE menor:** falta un proceso para reintentar/conciliar los 'pendiente_contpaqi'
  cuando Contpaqi vuelva (en el servidor interno sí conecta, así que allá saldrán 'enviado').

## 2026-09-07 (12) — Auditoría de rutas rotas en vistas (tras los merges)

- Se revisaron TODAS las vistas buscando route('...') que no existan. Resultado:
  - `admin.wiese-banco.guardar` / `.borrar` → ARREGLADAS (ver entrada anterior).
  - `admin.alertas.config.guardar` (en admin/alertas/configuracion.blade.php) → la vista SÍ se
    renderiza (AlertaController). Truena si se abre "configuración de alertas". PENDIENTE revisar
    si esa pantalla se usa; si sí, falta la ruta POST de guardar config.
  - `empresa.form` (en clientes/onboarding.blade.php, se renderiza en PortalClienteController) →
    nombre viejo; revisar si el onboarding de cliente se usa.
  - `login` y `register` (en welcome.blade.php) → vista por defecto de Laravel, casi seguro
    MUERTA (ustedes tienen sus propios logins). Baja prioridad.
- NOTA: estas 3 últimas NO son del módulo de pagos ni de los merges de hoy; son más viejas.
  No se tocan hasta confirmar que las pantallas se usan (no meter mano a pantallas muertas).

## 2026-09-07 (11) — FIX: WieseBanco tronaba (rutas guardar/borrar perdidas en merge)

- **SÍNTOMA:** entrar a /admin/wiese-banco/bbva daba RouteNotFoundException:
  "Route [admin.wiese-banco.guardar] not defined" (vista wiese-banco.blade.php línea 273).
- **CAUSA:** las rutas `admin.wiese-banco.guardar` y `admin.wiese-banco.borrar` se perdieron
  en un merge de git (al juntar con agentes aduanales). Los métodos del controlador
  (`wieseBancoGuardar`, `wieseBancoBorrar`) SÍ existían; solo faltaban las rutas.
- **FIX:** se re-agregaron en `routes/web.php` tras la ruta de ver:
  - POST   /admin/wiese-banco/{banco}/guardar  → admin.wiese-banco.guardar
  - DELETE /admin/wiese-banco/{banco}/movimiento/{movimiento} → admin.wiese-banco.borrar
- **LECCIÓN:** tras un merge, revisar que no se hayan perdido rutas/código propio. Verificado
  con route:list (3 rutas wiese-banco OK).

## PENDIENTE FUTURO — Carga masiva de docs fiscales de proveedores activos (Chuy)

- Chuy tiene una carpeta con MUCHOS PDFs (opinión positiva, etc.) de proveedores YA activos,
  pero no están en el sistema. Por eso el expediente de pago los marca "FALTA".
- CÓMO guarda el sistema estos docs: tabla `documentos_proveedor` (proveedor_id, tipo, archivo,
  estatus, resultado_validacion) + el PDF físico en `storage/` (expediente_fiscal/{tipo} en
  disco public, o cif/opiniones/caratula_banco en disco local).
- SOLUCIÓN propuesta (cuando se retome): comando de carga masiva que lea la carpeta, identifique
  el proveedor por RFC/código en el nombre del archivo, copie el PDF a storage y cree el registro
  en documentos_proveedor con estatus aprobado.
- FALTA definir con Chuy: cómo está organizada la carpeta, cómo se liga cada PDF a su proveedor
  (RFC/código en el nombre?), qué tipos, cuántos, y dónde está la carpeta. (Chuy no sabía aún.)
- PRIORIDAD: baja por ahora. Primero: que el módulo de pagos (formato/pago/abono) jale completo.

## 2026-09-07 (10) — Expediente multi-proveedor: docs de TODOS los involucrados

- **QUÉ:** Karen confirmó que el expediente de un pago múltiple debe llevar los documentos
  fiscales de TODOS los proveedores involucrados (antes solo mostraba al principal).
- **CÓMO:**
  - `PagoProveedorService::proveedoresDelPago()` (nuevo): lista los proveedores de un pago
    (por codigo_proveedor distinto de las líneas). Si un proveedor no existe en local, lo arma
    EN MEMORIA con su nombre y docs vacíos (sale como "falta", correcto).
  - `documentosFiscalesMultiProveedor()` (nuevo): Opinión + Constancia de CADA proveedor,
    etiquetadas con su nombre; un solo "Formato de pago (todos los proveedores)".
  - `documentosExpedientePago()`: usa el camino multi si es_multi_proveedor (fiscales +
    carátula de banco por cada proveedor).
  - `evaluarExpedientePago()` (nuevo): evalúa a TODOS; los motivos van prefijados con el nombre
    del proveedor. `show()` ahora llama a este en vez de evaluarExpediente(principal).
  - Títulos: show.blade.php y expediente.blade.php muestran "Varios proveedores (N)" si es multi.
- **PROBADO:** pago ORPACK + SUMINISTROS → evaluación lista faltantes de AMBOS; expediente
  muestra Opinión+Constancia de cada uno + 1 formato. Pago de prueba borrado.
- **SIGUE PENDIENTE (RH):** organización por departamentos del archivero (carpetas depto → mes).

## 2026-09-07 (9) — PENDIENTE con RH/Karen: expediente multi-proveedor

- **PROBLEMA:** el expediente de un pago múltiple (`admin.pagos.show` → `admin.pagos.expediente`)
  muestra solo al proveedor PRINCIPAL. Ej: "Expediente de Pago #10 · DE LA MORA ORTIZ · 3
  facturas · $370,676.32" como si las 3 facturas fueran de él, cuando son de 3 proveedores.
  Los documentos fiscales (Opinión SAT, Constancia) también salen de uno solo.
- **CAUSA técnica:** `AdminPagosController::show()` hace `evaluarExpediente($pago->proveedor)`
  y `documentosFiscalesParaPago()` usan el proveedor principal; no recorren las líneas.
- **NO SE TOCA aún:** falta definir con RH/Karen las reglas de negocio (ver artifact
  "Preguntas para RH/Karen — Expediente multi-proveedor"): ¿docs de todos los involucrados?
  ¿título? ¿departamentos y cómo se asigna cada pago? ¿autorización una o por proveedor?
- Cuando haya respuestas, se ajusta show() + expediente + evaluarExpediente para multi.

## 2026-09-07 (8) — Pulido visual del carrito (estilo iOS) + navegación

- **QUÉ:**
  1. En la pantalla del carrito se ocultó el botón "Regresar al panel" (del layout) porque
     confundía con el "← Buscar más proveedores" propio de esa vista.
  2. "← Buscar más proveedores" queda como único botón de regreso, arriba.
  3. Se rediseñó el CSS del carrito con estética iOS/Apple: system font stack (San Francisco),
     antialiasing, bordes 18px, botones "pill" (980px radio), grises de Apple (#1d1d1f/#86868b),
     sombras suaves.
- **DÓNDE:**
  - `resources/views/layouts/admin.blade.php`: se agregó `request()->is('admin/pagos/carrito')`
    a la condición `@unless` que oculta "Regresar al panel".
  - `resources/views/admin/pagos/carrito.blade.php`: bloque `<style>` reescrito.
- **PENDIENTE (servidor interno):** `php artisan db:backup` falla ahí porque no encuentra
  mysqldump (no hay XAMPP; base 'db6zwduj6dd2xt'). Hay que ubicar mysqldump.exe en ese server y,
  idealmente, agregar su ruta a RespaldarBaseDatos::ubicarMysqldump(). Limpieza de pruebas del
  server interno quedó en pausa hasta tener respaldo funcional.

## 2026-09-07 (7) — Formato de pago MULTI-PROVEEDOR (carrito + PDF gigante B1 + expediente único)

- **QUÉ:** nuevo flujo para armar UN formato de pago que junta facturas de VARIOS proveedores
  (como en la vida real: ORPACK + Álvaro + otros en una lista grandota con gran total).
- **DECISIONES (Said):** B1 = una fila de banco POR proveedor en "Datos del pago" + detalle de
  todas las facturas + gran total. Expediente = UNO solo que agrupa todos (Opción 2).
- **CÓMO FUNCIONA:**
  1. En la pantalla de un proveedor: botón "+ Agregar al carrito" (además del "Pagar solo este").
  2. El carrito vive en session('pagos_carrito'): [codigo => nombre/rfc/banco/folios...].
  3. Pantalla "Ver carrito" (`admin.pagos.carrito`): lista agrupada por proveedor, subtotales,
     gran total MXN/USD, quitar factura, vaciar, "Generar formato de pago".
  4. Generar crea UN PagoProveedor con es_multi_proveedor=true; cada línea guarda su propio
     codigo_proveedor + nombre_proveedor. El PDF pinta 1 fila de banco por proveedor + gran total.
- **CAMBIOS:**
  - Migración `2026_10_12_140000_add_proveedor_a_pago_lineas`: +codigo_proveedor, +nombre_proveedor
    en pago_proveedor_facturas; +es_multi_proveedor en pagos_proveedor.
  - Modelos PagoProveedor y PagoProveedorFactura: fillable/casts actualizados.
  - `routes/web.php`: rutas admin.pagos.carrito.(agregar|ver|quitar|vaciar|generar).
  - `AdminPagosController`: métodos carrito* + ajuste de expedientes() (carpeta "Pagos múltiples")
    y expedientesProveedor(?multi=1).
  - `PagoProveedorService`: crearPagoMultiProveedor() + datosReporteResumen (esMulti +
    proveedoresPago via filasProveedoresMulti) .
  - Vistas: proveedor.blade.php (botones carrito), carrito.blade.php (nueva), reporte-resumen-pdf
    (filas B1 + columna Proveedor en detalle), expedientes.blade.php (link ?multi=1).
- **PROBADO:** pago multi con ORPACK (2 fact, BANORTE) + SUMINISTROS (2 fact, sin banco en
  Contpaqi); 1 pago, 4 facturas, gran total $305,028.05; PDF generado OK (142 KB). Pago de
  prueba borrado después. El flujo mono-proveedor ("Pagar solo este") sigue intacto.
- **NO ROTO:** los pagos normales (un proveedor) funcionan igual; es_multi_proveedor=false.
- **PENDIENTE menor:** evaluarExpediente() del pago multi evalúa solo al proveedor principal
  (los papeles fiscales son por proveedor). Para el expediente único es aceptable; revisar con
  Karen si se requiere validar los docs de TODOS los proveedores del pago.

## 2026-09-07 (6) — Limpieza de pruebas + reordenar menú de Pagos

- **LIMPIEZA:** se borraron los 7 pagos de prueba (16 líneas + 11 facturas materializadas de
  Wiese) que generaban los 6 expedientes duplicados. Respaldo previo en
  `storage/backups/salcom20_20261012_132139.sql`. Pagos restantes: 0.
- **MENÚ (sidebar `resources/views/layouts/admin.blade.php`):**
  - Dentro de "Pagos", nuevo orden: Formato para pago → Pago a proveedor → Abono al proveedor
    → Anticipo → Nota de crédito.
  - "Nota de crédito": se quitó el texto "(próximamente)" (sigue deshabilitada visualmente).
  - "Expediente de pago": se SACÓ de "Pagos" y se puso abajo, junto a WieseBanco (sección
    Operación).
- **PERMISO CONFIRMADO:** "Expediente de pago" lo ve SOLO Dirección (Said lo confirmó). Queda
  bien bajo `@if($adminEsDireccion)` en Operación, no hay que cambiarlo.
- **COLOR:** el morado del dibujo de Said era solo para señalar dónde mover las cosas, no un
  color nuevo. El sidebar ya usa morado (#6B3FA0). Nada que cambiar.

## 2026-09-07 (5) — Evitar formatos de pago DUPLICADOS de la misma factura

- **SÍNTOMA:** una factura (ej. 93335) seguía apareciendo como "pendiente" aunque ya se le
  habían generado 6 formatos de pago; se podían hacer N formatos y salían 6 expedientes del
  mismo monto ($201,738.07).
- **CAUSA:** la lista de "Facturas pendientes en Wiese" se lee EN VIVO de Wiese, que las sigue
  reportando pendientes hasta que se saldan ALLÁ (en el abono). No se descartaban las que ya
  tenían un pago/formato en proceso en local.
- **ARREGLO:** antes de mostrar, se juntan los `folio_cfdi` que ya están en un
  `pago_proveedor_facturas` cuyo pago NO está cancelado (de ese proveedor) y se ocultan de la
  lista con `->reject(...)`.
- **DÓNDE:** `app/Http/Controllers/AdminPagosController.php`, método `proveedor()`.
- **PRUEBA:** ORPACK tenía 309 pendientes en Wiese; tras el filtro quedan 299 (se ocultan las
  10 que ya tienen pago: 93335, 93268, 93269, 93270, 93336-93341). La 93335 ya no reaparece.
- **NOTA:** si se CANCELA el pago, la factura vuelve a aparecer (el filtro excluye estatus
  'cancelado'). Correcto.

## 2026-09-07 (4) — Banco salía vacío en el PDF: proveedor local viejo sin refrescar

- **SÍNTOMA:** el Formato para pago mostraba Banco/Cuenta/CLABE en "—" aunque la API ya traía
  los datos. El RFC sí salía (proveedor correcto M213015002).
- **CAUSA:** ORPACK ya existía en la base LOCAL (proveedores_users id=5) guardado en pruebas
  viejas SIN banco/cuenta/clabe. El PDF lee del proveedor local, no de la API fresca. El código
  que copia el banco solo corría al CREAR el proveedor desde Wiese; como ORPACK ya existía, se
  quedó con los datos vacíos.
- **ARREGLO (fondo):** `AdminPagosController::proveedor()` ahora llama a
  `refrescarDatosBancariosDesdeWiese()` al abrir un proveedor local existente: si le falta
  banco/cuenta/clabe, los trae de Wiese (por código) y los guarda. Solo completa lo que falte,
  no pisa datos buenos, y si Wiese está caído no hace nada.
- **ARREGLO (inmediato):** se refrescó a mano el ORPACK local id=5 → ya tiene
  BANORTE / 0064701-0849 / 0723200-06470108493.
- **DÓNDE:** `app/Http/Controllers/AdminPagosController.php` (método `proveedor()` + nuevo
  método privado `refrescarDatosBancariosDesdeWiese()`).
- **PUERTO API:** Alan movió la API; el flujo volvió a funcionar en 7183 cuando lo relevantó.

## 2026-09-07 (3) — Búsqueda de "Formato para pago" solo muestra proveedores CON facturas

- **QUÉ:** En la búsqueda de proveedor del Formato para pago, ahora solo aparecen los que
  tienen facturas PENDIENTES. Antes salían todos los que coincidían con el texto (ej. buscar
  "ORPA" traía 4, pero 2 TRANSFORPAPER no tenían nada que pagar).
- **POR QUÉ:** Said pidió que la lista no muestre proveedores sin facturas por pagar.
- **CÓMO:** tras filtrar por texto, se consulta a Wiese las facturas de cada proveedor (por RFC,
  reutilizando `listarFacturasProveedorPorRFC`) y se descartan los que tienen 0 pendientes.
  De paso se llena `num_facturas` y `monto_total` reales (antes iban en 0).
- **DÓNDE:** `app/Http/Controllers/AdminPagosController.php`, método `index()`.
- **NOTA RENDIMIENTO:** hace 1 consulta a Wiese por proveedor que coincidió. Como las búsquedas
  por nombre devuelven pocos (2-5), es aceptable. Si una búsqueda muy amplia trae muchos, podría
  tardar; se puede optimizar después (cache o límite) si molesta.
- **PRUEBA:** buscar "ORPA" → antes 4, ahora 2 (los dos ORPACK con 309 pendientes); los 2
  TRANSFORPAPER (0 pendientes) se ocultan. Correcto.

## 2026-09-07 (2) — Investigación C#: BuscarPorRFC/BuscarPorCodigo + ORPACK duplicado

- **QUÉ SE INVESTIGÓ:** qué clase/consulta usan los endpoints del flujo de pago en la API C#
  (repo `C:\Users\IT\source\repos_git\APIWIESE`).
- **HALLAZGO 1 — dos clases distintas:**
  - `ListarProveedorWeb` → clase `ProveedorWeb` (`ConProveedorWeb.cs`). Es la que Said editó
    para agregar CTEXTOEXTRA1/2/3. (Pendiente: Alan recompila.)
  - `BuscarPorRFC` y `BuscarPorCodigo` → clase `ClienteProveedor` (`ConClienteProveedor.cs`),
    OTRA consulta. **Esta YA traía CTEXTOEXTRA1/2/3 en el SELECT y YA los expone como
    textoExtra1/2/3.** NO necesita cambios en C#.
- **HALLAZGO 2 — nombres distintos según endpoint:** ProveedorWeb manda banco/cuenta/clabe;
  ClienteProveedor manda textoExtra1/2/3. El PHP ahora lee AMBOS (en
  `AdminPagosController::proveedorWieseEnMemoria` y `AdminPagoProveedoresController::proveedoresParaFlujoPago`).
- **HALLAZGO 3 — PROBLEMA DE DATOS (la causa de que salga vacío):** en Contpaqi hay DOS
  registros de ORPACK con el MISMO RFC (OME0207015E8): uno con CTEXTOEXTRA lleno (BANORTE /
  0064701-0849 / 0723200-06470108493) y otro vacío. `QBuscarPorRFC` hace
  `WHERE CRFC=@rfc` + `reader.Read()` (primera fila) y trae el VACÍO.
- **DECISIÓN PENDIENTE (con Alan):** lo correcto es limpiar/corregir el ORPACK duplicado vacío
  en Contpaqi (que quede uno solo). Alternativas de parche: filtrar por CESTATUS=1 en la
  consulta, o preferir el registro con CTEXTOEXTRA no vacío, o buscar por código en vez de RFC.

## 2026-09-07 — Banco/Cuenta/CLABE del proveedor en el Formato para pago

- **QUÉ:** Se conectó el circuito para que el "Formato para pago" muestre banco/cuenta/clabe
  del proveedor (antes salían con "—").
- **DÓNDE VIVE EL DATO (confirmado con Alan + consulta SQL):** NO hay columnas dedicadas en
  Wiese. Salcom los guarda en los campos comodín de `adSalcom18.dbo.admClientes`:
  `CTEXTOEXTRA1` = banco, `CTEXTOEXTRA2` = cuenta, `CTEXTOEXTRA3` = clabe.
  Ejemplo real ORPACK: BANORTE / 0064701-0849 / 0723200-06470108493.
- **CAMBIOS PHP (hechos):**
  - `AdminPagoProveedoresController::proveedoresParaFlujoPago()` — ahora mete banco/cuenta/clabe
    en `datos_identificacion` de cada proveedor de Wiese.
  - `AdminPagosController::proveedorWieseEnMemoria()` — recoge banco/cuenta/clabe de la respuesta
    de la API y los pone en `datos_identificacion`; de ahí se copian solos al proveedor local
    (`asegurarProveedorLocalDesdeWiese` ya guardaba el `datos_identificacion` completo) y llegan
    al PDF (el blade ya los mostraba).
- **CAMBIOS C# (pendientes de que Alan pegue + recompile):** agregar `CTEXTOEXTRA1/2/3` al SELECT
  y propiedades Banco/Cuenta/Clabe en `ProveedorWeb.cs`. OJO: hay que hacerlo en LAS DOS consultas
  que usa el flujo: `ListarProveedorWeb` (ConProveedorWeb.cs) y `BuscarPorCodigo`/`BuscarPorRFC`.
- **OJO datos duplicados:** en admClientes hay DOS registros de ORPACK (mismo RFC): uno con los
  CTEXTOEXTRA vacíos y otro lleno. Revisar con Alan cuál es el "bueno" para no traer el vacío.

## 2026-09-07 — Folio del Formato para pago ahora es fijo FCONA-0040

- **QUÉ:** El "Formato para pago" (PDF) ahora muestra SIEMPRE el folio fijo `FCONA-0040`.
  Antes se calculaba con el id del pago (`FCONA-` + id → salía FCONA-0004, etc.).
- **POR QUÉ:** Contabilidad lo pidió como número fijo de contabilidad, no consecutivo.
- **DÓNDE:** `resources/views/admin/pagos/reporte-resumen-pdf.blade.php` — variable `$folioDoc`
  y el `<title>` del documento.
- **PENDIENTE RELACIONADO (datos bancarios del proveedor en el formato):** se investigó y
  NI la API de Wiese NI la base local tienen banco/cuenta/clabe del proveedor (ORPACK vacío
  en `datos_identificacion`, `solicitudes_alta` sin datos, Wiese no los devuelve). La tabla
  `cuentas_bancarias` solo tiene la cuenta de SALCOM (BBVA 8969, origen del pago), no la del
  proveedor. Falta definir con contabilidad si esas columnas del formato son del PROVEEDOR
  (hay que capturarlas a mano) o de SALCOM (se jalan de `cuentas_bancarias`).

## 2026-09-07 — Limpieza de estatus viejos en filtro de facturas

- **QUÉ:** Se quitaron `aprobada` y `validada` del filtro de facturas de proveedor.
  Antes el filtro de "programada" también traía esos dos nombres viejos; ahora solo
  busca `programada`.
- **POR QUÉ:** Said confirmó que en producción NO hay facturas con esos estatus, así que
  el respaldo de compatibilidad ya no hace falta y solo causaba confusión.
- **DÓNDE:** `app/Http/Controllers/AdminPanelController.php`, método `queryFacturasFiltradas()`
  (bloque `elseif ($estatus === 'programada')`, ~línea 1405).
- **NO SE TOCÓ:** `aprobada` sigue viva en OcBorrador, SolicitudAlta, SolicitudModificacionDatos
  y AltaFacturaValidationService — ahí es código vivo de OTRAS entidades, borrarla rompería
  la IA de reorden, el alta de proveedores y las alertas. Son flujos distintos aunque usen
  la misma palabra.

## 2026-10-07 — WieseBanco Pieza 4: reflejar abono en WieseBanco + Contpaqi (sin duplicar)
- **Qué:** Sandra definió el flujo real: NO duplicar procesos. El saldado lo hace Karen en el módulo de Abono (saldar documento en 0, liga folio de factura); cuando eso pasa, debe aparecer solo en el "Quicken" (WieseBanco) y registrarse en Contpaqi. Se enganchó en `AdminPagoProveedoresController::abonoInternoConfirmar` (el punto donde las facturas pasan a estatus 'liquidada' = el "saldar en 0"). Por cada factura liquidada se crea UN movimiento en WieseBanco (decisión: un renglón por factura, fiel a Quicken; cada uno con su NUM consecutivo, payee=proveedor, memo=folio, payment=monto, categoria=PROVEEDOR) y se llama a la API C# para registrar el pago en Contpaqi, guardando el iddocumento_contpaqi.
- **Decisiones de dirección:** TODO va a la cuenta BBVA 8969 por ahora (aunque el abono tenga 4 cuentas a elegir), porque es la única cuenta de WieseBanco que existe (igual que el Quicken real). Un movimiento por factura (recomendación del agente, aprobada).
- **Diseño defensivo:** el reflejo va en try/catch y el servicio NO deja que un fallo de Contpaqi tumbe el abono de Karen. Si la API C# está apagada/falla, el movimiento igual se guarda en WieseBanco con estatus 'pendiente_contpaqi' (para reintentar luego); si registra OK, queda 'enviado' con su iddocumento_contpaqi.
- **Dónde:** NUEVO `app/Services/WieseBancoContpaqiService.php` (método `reflejarFacturasLiquidadas` + `registrarEnContpaqi` que hace Http POST a la API C#). `config/services.php` → `contpaqi_api.url` (env CONTPAQI_API_URL, default https://localhost:7090). Enganche en `AdminPagoProveedoresController::abonoInternoConfirmar`.
- **PARTE A VALIDADA (07-oct):** se probó el servicio con una factura 'pagada' de prueba (proveedor PRUEBAWB, folio TEST-5001, 4000). Resultado: se creó el movimiento en WieseBanco (NUM consecutivo, payee, memo=folio, payment, balance) con estatus 'pendiente_contpaqi' porque la API C# no estaba levantada (log: cURL error 7, no conecta a :7090). CONFIRMA el diseño defensivo: Contpaqi caído NO rompe el flujo. Datos de prueba limpiados después (base en 0, consecutivo 80194).
- **PARTE B EN CURSO:** se entregó (para copiar/pegar en C#) el `CrearPago` modificado que ahora recibe `(double folioQuicken, string codigoProveedor, double importe)` y los usa (proveedor ya no fijo ORPACK, folio=folioQuicken, importe real). El servicio PHP ya los envía con esos nombres exactos. La usuaria pegó el código correctamente.
- **PROBLEMA Visual Studio (07-oct):** al compilar, VS da error MSB4236 "El SDK Microsoft.NET.Sdk.Web no se pudo encontrar". NO es del código: compilando con `dotnet build` por línea de comandos da "Compilación correcta, 0 Errores". Causa: en la máquina solo está instalado el SDK .NET **10.0.401**, pero el proyecto es **net8.0-windows**; VS quedó desalineado (probablemente .NET 10 se instaló recientemente). SOLUCIÓN sugerida: (1) reiniciar Visual Studio completo; si persiste (2) instalar el SDK de .NET 8 (dotnet.microsoft.com/download/dotnet/8.0, SDK x64). El runtime AspNetCore 8.0.31 sí está; falta el SDK 8.
- **FIX VS (07-oct):** reiniciar VS NO bastó. Se instaló el SDK de .NET 8 con `winget install Microsoft.DotNet.SDK.8` (quedó 8.0.425). Ahora `dotnet --list-sdks` muestra 8.0.425 y 10.0.401. Falta: cerrar VS completo, reabrir y compilar — el error MSB4236 debe desaparecer.
- **VS COMPILA OK (07-oct):** tras instalar SDK 8 y reabrir VS, compila con 0 errores.
- **PARTE B VALIDADA (07-oct):** se probó `CrearPago` en Swagger con parámetros reales (folioQuicken, codigoProveedor=M213015002, importe) con Alan presente. Creó el documento en Contpaqi usando los datos de afuera (ya no los fijos ORPACK/100). Alan lo verificó y lo borró para no dejar pagos de prueba. CrearPago ya acepta datos externos = listo para que la web se lo mande.
## 2026-10-08 — DECISIÓN CLAVE: el FOLIO lo genera Contpaqi (no WieseBanco)
- **Qué:** Al probar el flujo real salió error SDK 130101 "El documento ya existe" (folio 80195 ya usado en Contpaqi por un intento previo). Causa de fondo: WieseBanco y Contpaqi generaban folios por su cuenta → chocan. DECISIÓN (con Alan): el folio lo genera SOLO Contpaqi con `BuscarSiguienteSerieYFolio`, consultado en TIEMPO REAL. Así, si contabilidad hizo más movimientos (ej. van en 80199), Contpaqi devuelve 80200 — nunca se desfasa. Fuente única de verdad del folio = Contpaqi.
- **Bug encontrado en C#:** `CrearPago` SÍ llamaba a `BuscarSiguienteSerieYFolio` pero NO usaba el resultado: hacía `pago.Folio = folioQuicken` (el de afuera). FIX: `pago.Folio = siguiente.aFolio` (el real de Contpaqi). La usuaria ya corrigió, compiló y corrió el API.
- **Lado PHP ajustado:** `WieseBancoContpaqiService` ahora: (1) llama a Contpaqi PRIMERO (sin mandar folioQuicken, solo codigoProveedor + importe); (2) Contpaqi devuelve folio + idDocumento; (3) crea el movimiento WieseBanco usando ESE folio (num) y el idDocumento. Si Contpaqi falla, guarda el movimiento 'pendiente_contpaqi' sin num (se asigna al reintentar). WieseBanco YA NO autogenera folio para pagos a proveedor.
- **Implicación UI (RESUELTO):** el campo "Folio" del abono ya NO precarga número; muestra "Se asigna al guardar" (readonly) porque el folio real lo pone Contpaqi al guardar. En backend `abonoInternoConfirmar` el campo `poliza` pasó de required a nullable (se normaliza a '(se asigna en Contpaqi)' si viene el marcador). Nombre interno del campo sigue siendo `poliza` (Opción A: no se renombró a `folio` porque "poliza" significa varias cosas en el código -poliza_key, polizas_pago, método poliza()- y renombrar masivo rompería; la etiqueta visible ya dice "Folio").
- **Dónde:** C# `PagoProveedorController.CrearPago` (pago.Folio = siguiente.aFolio). PHP `app/Services/WieseBancoContpaqiService.php` (registrarEnContpaqi devuelve [folio, idDocumento]; el movimiento usa el folio de Contpaqi). Timeout subido a 60s (crear pago en Contpaqi tarda).
- **LIMPIEZA (08-oct):** se borraron los datos de la prueba fallida de la BD local: movimiento WieseBanco NUM 80195 (pendiente_contpaqi), factura de prueba 92744, proveedor de prueba ORPACK (M213015002), y se reseteó la cuenta BBVA a consecutivo=80194, saldo=0. OJO: el 92744 es una factura REAL de ORPACK en Contpaqi/Wiese (aparece en el detalle de adeudos del proveedor); solo se borró la COPIA de prueba que estaba en la BD local. El abono fallido se había registrado localmente (factura liquidada, folio 80195) pero NO llegó bien a Contpaqi por el choque de folios.
- **BUG Enter resuelto (08-oct):** tras poner el Folio readonly se rompieron TODOS los Enter del formulario de abono. Causa (vista en consola F12): `Uncaught TypeError: Cannot read properties of null (reading 'style') at checkPoliza` — la función buscaba el elemento `poliza-dot` (el puntito rojo de obligatorio) que se había quitado del campo Folio; al ser null y hacer `.style`, tronaba y TUMBABA todo el JS (por eso ningún Enter corría). FIX: se hicieron robustas `checkPoliza/checkFecha/checkProveedor` (si el input o el dot no existen, no truenan: `if(!input) return;` + `if(dot)`). Además la navegación Enter ahora: Fecha→(salta Folio readonly)→modal Proveedor→Total→modal Saldar. Dónde: `resources/views/admin/abono-proveedor/index.blade.php`.
## 2026-10-08 — AUTOMATIZAR el SALDADO: cambio a CrearPagoYSaldar (no dejar nada manual)
- **Qué:** Dirección fue clara: el objetivo es AUTOMATIZAR; NO dejar que conta salde la factura a mano en Contpaqi. Antes el servicio usaba `CrearPago` (solo creaba el abono suelto, sin saldar). AHORA usa `CrearPagoYSaldar`: crea el pago Y salda la factura de compra en Contpaqi, de corrido.
- **Cómo (flujo nuevo del servicio):** por cada factura liquidada → (1) busca la factura de compra en Contpaqi con la API `FacturasProveedor` (codigo proveedor + concepto 21) para obtener su SERIE real; (2) llama a `CrearPagoYSaldar` con proveedor+importe+conceptoFactura("21")+serie+folio; (3) Contpaqi crea el abono (folio generado por él) y salda la factura; (4) guarda folio+idDocumento en WieseBanco. Soporta pago PARCIAL (manda el importe que sea).
- **Concepto compra = 21** (confirmado por Alan y visto en las 78 facturas de ORPACK). Es constante en el código (`CONCEPTO_COMPRA`).
- **C# `CrearPagoYSaldar` corregido** (la usuaria pegó y compiló): recibe `(codigoProveedor, importe, conceptoFactura, serieFactura, folioFactura)`; folio del pago = `siguiente.aFolio` (Contpaqi); importe real; usa `SaldarCargo` para ligar a la factura.
- **Dónde:** `app/Services/WieseBancoContpaqiService.php` (métodos `registrarYSaldarEnContpaqi` y `buscarSerieFacturaEnContpaqi`). C# `PagoProveedorController.CrearPagoYSaldar`.
- **UI detalle proveedor (08-oct):** se aclararon los folios confusos en `proveedor-facturas.blade.php`: "Folio CFDI" → "Folio factura (CFDI)" (facturas BD local) y "Folio" → "Folio OC (Wiese)" (órdenes de compra de Wiese). Se agregó un buscador de texto (input `#oc-buscar` + `filtrarOC()`) para filtrar la tabla de OC por folio/razón/RFC (ej. escribir 92744). LIMITACIÓN: el buscador solo filtra entre las OC ya cargadas (primeros 500 del rango); si la factura está más allá, no la encuentra (hay que acortar el rango de fechas). NOTA: el "folio consecutivo de Quicken" (80195) es el del PAGO, NO aparece en esta pantalla (sale en WieseBanco); aquí solo se ven facturas/OC. DATO ÚTIL: la tabla OC de Wiese YA trae la serie (`cseriedocumento`) y folio (`cfolio`) — sirve para el saldado sin consultar el SDK lento.
- **PROBLEMA timeout saldado (08-oct):** consultar `FacturasProveedor` del SDK para sacar la serie se CUELGA (timeout 120s) porque ORPACK tiene miles de documentos. El saldado automático depende de esa consulta → se colgaría. PENDIENTE resolver: (opción buena) usar la serie que YA trae la OC de Wiese (`cseriedocumento`) en vez de consultar el SDK lento; o acotar el rango de fechas al mes de la factura; o endpoint C# que busque UNA factura por folio. Preguntar a Alan la forma rápida de ubicar 1 factura por folio en el SDK.
## 2026-10-08 — 🎉 PRUEBA FINAL EXITOSA: puente completo web → Contpaqi → WieseBanco
- **Qué:** Se probó el flujo COMPLETO del abono desde la web: saldar factura 92744 de ORPACK ($1,240.64) → se creó el pago en Contpaqi (idDocumento **2430630**, folio **3850965172**) → se reflejó en WieseBanco (NUM 3850965172, ORPACK, $1,240.64, estatus ENVIADO). Historial de abonos muestra Pendiente $0.00. Las notificaciones (admin+proveedor) se dispararon correctamente.
- **Por qué funciona ahora:** (1) folio generado por Contpaqi (no WieseBanco); (2) serie ALMP tomada de validacion_detalle de la factura (no del SDK lento); (3) diseño "todo o nada" (Contpaqi respondió OK → se guardó en WieseBanco; si hubiera fallado, no se habría guardado nada).
- **VALIDADO CON ALAN (08-oct):** Alan borró las 2 pruebas de Contpaqi. Confirmó: el **3850965172 es el folio INTERNO/BRUTO de Contpaqi** (no un consecutivo "bonito"). El "folio de Quicken" NO existe en Contpaqi (es solo de su sistema viejo). La factura 92744 era nuestra de prueba (Alan no la ubica).
- **HALLAZGO IMPORTANTE (choque de concepto de folio):** nosotros queríamos que WieseBanco mostrara folios BONITOS consecutivos estilo Quicken (80195, 80196...), que es a lo que Sandra/Karen están acostumbradas. PERO al decidir "folio lo genera Contpaqi", WieseBanco ahora muestra el folio BRUTO de Contpaqi (3850965172), que NO es consecutivo bonito. Son dos folios distintos: (a) folio bruto Contpaqi = id interno; (b) folio bonito consecutivo = el que ven los humanos en Quicken.
- **PREGUNTA PENDIENTE CLAVE PARA ALAN:** ¿de dónde sale el folio bonito consecutivo (80195) que Sandra ve en Quicken viejo? ¿Contpaqi lo tiene en algún campo (ej. CSERIEDOCUMENTO + algún folio visible) o es exclusivo de Quicken? Define qué número mostrar en WieseBanco (humanos esperan el bonito, no el bruto 3850965172).
- **El flujo técnico FUNCIONÓ** (pago creado idDoc 2430630, reflejado en WieseBanco, todo-o-nada OK). Lo pendiente era COSMÉTICO: qué folio mostrar (bruto vs bonito).

## 2026-10-08 — ⚠️ ACLARACIÓN DE KAREN: el "folio factura" y el "folio Quicken" son EL MISMO
- **Qué (resuelve días de confusión):** Karen aclaró que el folio que se ve en "Facturas pendientes en Wiese" (ej. 93269, 92744) ES el folio de Quicken/Wiese. NO existen dos folios distintos ("folio factura CFDI" vs "folio Quicken"): es UNO solo. Lo que el agente llamó "folio factura" (92744) y el "folio Quicken" eran el mismo número todo el tiempo. En el código, ese folio viene de la API Wiese (`$f['folio']`) y se guarda como `folio_cfdi`.
- **El consecutivo real va en los 93000s, NO en 80194.** El número 80194/80152 que se usó como consecutivo inicial estaba DESACTUALIZADO (dato viejo). Por eso chocaban los folios: generábamos números viejos (80195) cuando Quicken/Contpaqi ya iba en 93000+.
- **IMPLICACIÓN para el diseño del folio:** el "folio bonito 80195" que WieseBanco generaba está MAL (número viejo e inventado). Como quedamos que CONTPAQI genera el folio, el folio correcto es el que devuelve Contpaqi (va en los 93000s), NO un 80195 local. REVISAR: quitar/corregir el `consecutivo_actual=80194` de la cuenta BBVA; el folio mostrado en WieseBanco debe ser el real de Contpaqi/Wiese, no el consecutivo local desactualizado.
- **CONFIRMAR con Karen/Alan:** ¿quién lleva la numeración definitiva? (a) Contpaqi/Wiese la lleva y WieseBanco solo refleja el folio real que viene (recomendado, es lo que ya hace Contpaqi al generar folio); o (b) WieseBanco reemplaza a Quicken y lleva el consecutivo (entonces arrancar desde el último real ~93270, no 80194).

## 2026-10-08 — Folio BONITO en WieseBanco (consecutivo estilo Quicken) + folio Contpaqi aparte
- **Qué:** Decisión: WieseBanco muestra el folio BONITO consecutivo (80195, 80196...) estilo Quicken, que es a lo que Sandra está acostumbrada; NO el folio bruto de Contpaqi (3850965172). Se agregó columna `folio_contpaqi` (migración `2026_10_08_120000`) para guardar el folio bruto de Contpaqi como referencia (conciliación), sin mostrarlo. El servicio ahora: `num` = `cuenta->siguienteFolio()` (bonito, lo genera WieseBanco); `folio_contpaqi` = folio bruto de Contpaqi; `iddocumento_contpaqi` = id del documento.
- **Por qué NO vuelve a chocar:** el folio bonito (80195) es SOLO para mostrar en WieseBanco; NO se le manda a Contpaqi. Contpaqi genera su folio bruto por su cuenta. Son independientes: bonito=humanos (WieseBanco), bruto=interno (Contpaqi). Ya no hay choque como antes.
- **Dónde:** migración `...add_folio_contpaqi_to_movimientos_bancarios.php`; modelo `MovimientoBancario` (fillable+casts folio_contpaqi); `WieseBancoContpaqiService` (num=siguienteFolio, folio_contpaqi=resultado folio).
- **PENDIENTE:** probar de nuevo (con Alan) y confirmar que WieseBanco ahora muestra 80195 (bonito) y guarda 3850965172 en folio_contpaqi por detrás. El consecutivo bonito arranca en 80195 (cuenta BBVA en 80194). Confirmar con Alan/Karen que 80194 es el último folio real del Quicken viejo (para continuar la secuencia correcta).

## 2026-10-08 — Diccionario de folios + UI WieseBanco (quitar Overview, colores lila)
- **DICCIONARIO DE FOLIOS (oficial, para no confundir):**
  - **Folio Quicken** (= folio pago) = consecutivo bonito de la cuenta, el "Num" de WieseBanco (80195). Lo genera WieseBanco. SÍ se muestra (el principal).
  - **Folio factura** = folio del CFDI que emite el PROVEEDOR (92744). Su número de factura. Lo genera el proveedor/SAT. SÍ se muestra.
  - **Folio OC** = folio de la orden de compra en Wiese (78264). Lo genera Wiese. SÍ se muestra.
  - **Folio Contpaqi** = folio bruto/interno de Contpaqi (3850965172). Lo genera Contpaqi. NO se muestra (ref. en folio_contpaqi).
  - **idDocumento Contpaqi** (no es folio) = id del documento en Contpaqi (2430630).
- **UI WieseBanco (`wiese-banco.blade.php`):** se quitó la pestaña Overview; colores cambiados de AZUL a LILA/MORADO (variables --qk-* + azules hardcodeados → #4a2078/#6B3FA0/#5b3a86/#e0d3f2/#c9adec, etc.); la columna sigue llamándose "Num".
- Artifact "Diccionario de Folios" creado para referencia de la usuaria.

## 2026-10-08 — RIESGO de nomenclatura OC vs Factura (CRÍTICO, confirmar con Alan)
- **Qué:** La usuaria señaló (muy bien) que lo que se PAGA es la FACTURA, pero Wiese le llama "OC" a la factura en su nomenclatura. Riesgo: creer que OC y factura son cosas distintas y saldar el documento EQUIVOCADO en Contpaqi.
- **Conceptos de negocio (aclarado):** OC = pedido (Salcom pide, "te compro") → Factura = cobro fiscal/CFDI (el proveedor cobra, "me debes") → Pago/Abono = saldar ("te pagué"). "Documento" = palabra genérica de Contpaqi para cualquiera (cada uno con su concepto). PERO Wiese mezcla la nomenclatura (le dice OC a la factura).
- **PREGUNTA CRÍTICA PARA ALAN (sin esto el saldado puede aplicar al doc equivocado):** "En Contpaqi, el documento que debo SALDAR al pagar ¿es el de concepto 21? ¿El concepto 21 es la FACTURA de compra o la ORDEN DE COMPRA? ¿Son el mismo documento o hay dos (OC y factura) con conceptos distintos?"
- **Protección actual:** el diseño "todo o nada" + SaldarCargo con concepto 21/serie/folio: si el documento no existe con ESA llave, SaldarCargo falla y NO se guarda nada (no salda a ciegas). Pero hay que confirmar que 21 sea el documento correcto a saldar.

## 2026-10-08 — Cómo convive la DB con Contpaqi + cómo probar SIN afectar producción
- **Qué se guarda dónde:** TU DB guarda facturas (flujo pendiente→pagada→liquidada), movimientos WieseBanco, proveedores, usuarios. A la API C#→Contpaqi SOLO se manda en el momento del ABONO (saldar): el pago (concepto 28) + saldar la factura. Los dos mundos conviven y se comunican solo al saldar.
- **PROBLEMA al probar:** Contpaqi es REAL (empresa adSalcom18); cada saldado crea un documento real en la contabilidad. Por eso Alan borra las pruebas.
- **PREGUNTA CLAVE PARA ALAN:** ¿hay una EMPRESA DE PRUEBAS en Contpaqi (copia de adSalcom18) para probar pagos sin tocar la contabilidad real? Si existe, cambiar en el C# la ruta `C:\Compac\Empresas\adSalcom18` por la de pruebas. Evita que Alan tenga que borrar y protege lo real.
- **REGLA para producción:** las pruebas SIEMPRE en LOCAL (laptop), nunca en el servidor productivo; en Contpaqi usar la empresa de pruebas si existe. Nunca probar saldos en el sistema que usa Sandra a diario.
- **Para probar el flujo:** la factura debe existir en Contpaqi para poder saldarla (por eso se usa el folio 92744 que SÍ existe allá). En la DB local se mete esa factura en estatus 'pagada' para disparar el abono.
- **NO hay empresa de pruebas en Contpaqi (confirmado 08-oct):** por eso las pruebas se hacen con facturas REALES de ORPACK (las que muestra la pantalla "Facturas pendientes en Wiese" en `admin/pagos/proveedor` — datos reales del sistema contable, con serie ALMP, folio y saldo reales) y Alan borra el pago de prueba después. NO sirve inventar facturas de montos chiquitos ($12/$9/$10): Contpaqi no las tiene y el saldado fallaría. SUGERENCIA a dirección: pedir una empresa de pruebas en Contpaqi (copia de adSalcom18) para no ensuciar la real.
- **UI (08-oct):** se agregó buscador de texto en `admin/pagos/proveedor.blade.php` (input `#buscar-factura` + `filtrarFacturas()`) para filtrar las facturas pendientes de Wiese por folio/serie/fecha. Facilita encontrar la factura a pagar entre muchas.
- **ACLARACIÓN FINAL (Karen, 08-oct):** el 93269 (y 92744) es simplemente el folio de la FACTURA (de Wiese/Quicken). El agente SÍ estaba bien al identificarlo; solo lo nombramos confuso ("folio" vs "factura"). Se renombró la columna "Folio"/"Folio factura (CFDI)" → **"Factura"** en `admin/pagos/proveedor.blade.php` y `admin/proveedor-facturas.blade.php` para que quede claro. Resumen folios: FACTURA (93269, de Wiese) es lo que se paga; el folio del PAGO/abono (lo genera Contpaqi al saldar) es otro número distinto.

## 2026-10-08 — AUDITORÍA de estatus de FACTURA
- **Estatus OFICIALES (en uso, correctos):** `pendiente` (importada de Wiese / al cancelar pago) → `programada` (Formato) → `pagada` (Pago a proveedor / anticipo que cubre total) → `liquidada` (Abono). Más `cancelada`. El flujo está COHERENTE y bien conectado.
- **Estatus FANTASMA de factura:** solo `aprobada` y `validada`, y SOLO en 1 lugar: `AdminPanelController` ~línea 1406, en el filtro `whereIn('estatus', ['programada','aprobada','validada'])`. Son nombres VIEJOS de "programada" (pre-APIs). En la BD local hay 0 facturas con esos estatus. Es un PARCHE de compatibilidad (filtro de mostrar, no lógica), inofensivo. DECISIÓN: DEJARLO (por si en producción hay facturas viejas con esos nombres; quitarlo podría ocultarlas). No está regado por el código, es un solo punto.
- **NO confundir (son de OTRAS entidades, no factura):** AbonoProveedor usa borrador/guardado/pagado/cancelado (correctos, apuntes sección 3). OcBorrador usa pendiente/aprobada/rechazada. MigracionMasiva: pendiente/procesando. Alerta: pendiente/enviada/leida. AltaFacturaValidationService: aprobada_con_observaciones. Esos tienen sus propios flujos, NO tocar.
- **Conclusión:** el código de estatus de factura está limpio; el único resto viejo (aprobada/validada en 1 línea) se conserva como compatibilidad. No hay basura peligrosa regada.

## 2026-10-08 — BUG corregido: "Pago a proveedor" no mostraba facturas "programada"
- **Flujo correcto (apuntes de la usuaria, confirmado):** FACTURA: pendiente → programada → pagada → liquidada. Cambia en: Formato para pago (→programada), Pago a proveedor (→pagada), Abono al proveedor (→liquidada). Orden estricto, no se brinca.
- **Bug:** `PagoProveedorService::proveedoresConPendientes()` buscaba `estatus='pendiente'` (valor VIEJO, de antes de las APIs). Como "Formato para pago" deja las facturas en 'programada', nunca aparecían en "Pago a proveedor". FIX: cambiar a `estatus='programada'`. (El `store()` de pago ya buscaba 'programada' y validaba secuencia; solo el listado estaba mal.)
- **Dónde:** `app/Services/PagoProveedorService.php` método `proveedoresConPendientes`.
- **Aclaración de las 2 dudas de la usuaria:** (1) la factura SIGUE en "Formato para pago" tras pagar porque esa pantalla lee WIESE en tiempo real (y en Wiese sigue pendiente hasta que se salde en el Abono); NO es bug. (2) NO salía en "Pago a proveedor" por el bug del estatus + porque hay que SELECCIONAR una cuenta primero (las 4: 8969 MXN, etc.).
- **OJO para probar:** "Pago a proveedor" requiere elegir una de las 4 cuentas arriba; sin eso no muestra proveedores.

## 2026-10-08 — Estandarizar orden de columnas (tabla de Contpaqi) + sidebar Nota de crédito
- **Orden estándar de columnas (de la tabla real de Contpaqi que mostró Karen):** Fecha | Serie | Folio | Razón social | Total | Moneda | Pendiente | Referencia | Cancelado | Tipo de cambio (+ Hora al final). Esta tabla es SÚPER importante: de ahí se hacen los abonos. Hoy existe en `historial-abonos.blade.php` (ya casi idéntica).
- **Qué se hizo:** en `admin/pagos/proveedor.blade.php` (Facturas pendientes en Wiese) se REORDENARON las columnas existentes para parecerse al estándar (sin agregar columnas vacías): Fecha factura | Serie | Factura | Vence | Total | Moneda | Saldo pendiente. Se ajustaron thead y tbody (colspan del tfoot sigue en 7, OK).
- **Sidebar:** se agregó "Nota de crédito" entre Expedientes de pago y Anticipo, como PLACEHOLDER deshabilitado ("próximamente") porque el módulo aún no existe (no hay ruta/controlador). En `layouts/admin.blade.php`.
- **PENDIENTE:** cuando se desarrolle "Nota de crédito", crear ruta+controlador+vista y activar el link. Revisar si Historial de abonos necesita ajuste de orden (ya tiene las columnas, orden casi igual; "Tipo" al inicio es extra útil, "Hora" al final como pidió la usuaria).

## 2026-10-08 — Rediseño "TODO O NADA" + fix timeout + limpieza
- **Qué (3 arreglos conectados):**
  1. **"TODO O NADA" (inconsistencia entre sistemas):** antes el movimiento se guardaba en WieseBanco aunque Contpaqi fallara → quedaba un pago en la BD local que NO existía en Contpaqi (descuadre). AHORA: si Contpaqi falla, NO se guarda nada en WieseBanco (continue). Solo si Contpaqi responde OK se crea el movimiento, con el folio+idDocumento REALES. Ambos sistemas quedan consistentes o ninguno.
  2. **Fix timeout:** ya NO se consulta `FacturasProveedor` del SDK para sacar la serie (se colgaba con los 2041 docs de ORPACK). La serie se toma de la propia factura (`validacion_detalle['serie']`, guardada al capturarla desde Wiese). Se eliminó el método `buscarSerieFacturaEnContpaqi`. `registrarYSaldarEnContpaqi` ahora recibe la serie directo.
  3. **Limpieza:** se borraron factura de prueba 92744, proveedor ORPACK y movimientos; cuenta BBVA reseteada (80194, saldo 0).
- **Dónde:** `app/Services/WieseBancoContpaqiService.php`.
- **LOS 3 FOLIOS (aclaración definitiva):** (a) Folio factura (CFDI) = la factura del proveedor, ej. 92744; (b) Folio OC (Wiese) = la orden de compra, ej. 78264; (c) Folio del PAGO = consecutivo cuenta BBVA/NUM Quicken, lo genera CONTPAQI al pagar, ej. 80195. Son 3 distintos.
- **LISTO PARA PRUEBA CON ALAN (08-oct):** recreados ORPACK + factura 92744 (serie ALMP en validacion_detalle, estatus pagada). Flujo: abono-proveedor?cuenta=8969_mxn → ORPACK → Total 1240.64 → modal Saldar (doble clic 92744) → Referencia → Guardar. Con todo-o-nada: si Contpaqi OK se crea pago+salda+WieseBanco; si falla, NADA se guarda.
- **PREGUNTAS PARA ALAN (resolver en la prueba):** (1) ¿el folio del detalle de adeudos (92744) = folio INTERNO de Contpaqi para saldar, o el CFDI tiene folio fiscal distinto? (2) ¿la serie de compras es siempre ALMP o varía? ¿de dónde tomarla confiable? (3) que borre pagos de prueba viejos de ORPACK en Contpaqi. (4) ¿pago parcial deja saldo pendiente para volver a pagar?
- **PENDIENTES mayores (no hoy):** otras 3 cuentas de WieseBanco (hoy solo BBVA 8969); decisión facturas API vs BD local (con Sandra/Karen); deploy a producción del batch; confirmar que la serie guardada coincide con Contpaqi.

## 2026-10-08 — AVISO a Alan (08-oct) — posibles pagos de prueba en Contpaqi: durante la depuración de la conexión PHP→API se llamó 3 veces a `CrearPago` (ORPACK M213015002, importe 1240.64, folio 80195). Intento 1: falló por conexión (no creó nada). Intento 2: error 400 (API rechazó, no creó nada). Intento 3: TIMEOUT 90s sin respuesta → NO se sabe si alcanzó a crear el documento. PEDIR A ALAN que revise en Contpaqi si quedó un abono de ORPACK por $1,240.64 (concepto 28) de hoy y lo BORRE si existe. REGLA reafirmada: no ejecutar POST que inserten en Contpaqi sin Alan presente.
- **PROBLEMA conexión PHP→API (08-oct):** (1) "Failed to connect localhost:7090" se resolvió cambiando la URL a 127.0.0.1 (localhost resolvía a IPv6 ::1). (2) Luego error 400 "codigoProveedor required": CrearPago en C# recibe params por QUERY STRING, no body; se corrigió el servicio PHP para mandar `?folioQuicken=&codigoProveedor=&importe=` en la URL. (3) Ahora la llamada CONECTA pero se CUELGA (timeout 90s): probable que el SDK intente abrir una ventana (como el error Windows.Forms previo) al ser llamado por PHP (sin humano que la cierre). PENDIENTE diagnosticar SIN ejecutar contra Contpaqi; la prueba real del POST se hará con Alan presente.
- **MEJORA UX pendiente (abono):** en el modal "Saldar cargos del proveedor", asociar una factura se hace con DOBLE CLICK en el renglón, pero no es obvio (hasta la propia dev lo olvidó). Agregar algo visual claro (botón "Asociar", o texto/ícono destacado) para que se entienda sin adivinar. No urgente; pulir después.
- **FOLIO del abono precargado (08-oct):** el campo "FOLIO" del abono (antes "Folio / Nº Póliza") ahora se precarga con el siguiente consecutivo de la cuenta WieseBanco BBVA 8969 (`folioSugeridoWieseBanco()` = consecutivo_actual+1) para que coincida con el NUM del movimiento. Label simplificado a "Folio". Dónde: `AdminPagoProveedoresController::abonoInterno` + helper `folioSugeridoWieseBanco`, vista `abono-proveedor/index.blade.php`.
- **OPCIÓN C VALIDADA (08-oct):** se probó el flujo web→WieseBanco SIN tocar Contpaqi (API C# apagada a propósito). Se creó proveedor ORPACK (M213015002) y factura 'pagada' (folio 92744, $1240.64) de prueba; al reflejar, WieseBanco creó el movimiento: NUM 80195 (consecutivo correcto), payee=ORPACK, memo=92744, pago=1240.64, saldo=-1240.64, estatus='pendiente_contpaqi' (porque API apagada). Consecutivo avanzó a 80195. CONFIRMA que la mitad del puente (web→WieseBanco) jala con datos reales. OJO: los datos de prueba (ORPACK + factura 92744 + movimiento) quedaron en la BD para que la usuaria los vea en el navegador; LIMPIAR antes de la prueba final con Alan.
- **PENDIENTE (prueba final, con Alan):** OPCIÓN A — flujo COMPLETO con la API C# LEVANTADA: saldar y ver que el movimiento quede estatus 'enviado' con iddocumento_contpaqi (pago creado en Contpaqi). Antes: limpiar datos de prueba (movimientos, factura 92744, proveedor ORPACK) y resetear consecutivo a 80194.

## 2026-10-07 — WieseBanco: fix búsqueda de cuenta (por clave_corta) + captura en orden estricto
- **Qué:** (1) BUG encontrado: al cambiar la etiqueta de bbva a "BBVA 8969 MXN (28)" en config, se rompió la búsqueda de la cuenta (el controlador buscaba `WHERE banco = nombre_del_config`, que ya no coincidía con el campo banco="BBVA" de la BD). Por eso Num salía vacío y el guardado fallaba. FIX: vincular por `clave_corta` (estable, '8969') vía helper `claveCuentaWiese(bbva)=>8969`, no por el nombre. (2) Captura en ORDEN ESTRICTO izq→der solo con Enter/Tab: date→payee→category→memo→payment→deposit; al terminar deposit guarda. Flechas y otras teclas no cambian de celda. Al abrir, el cursor arranca en Payee (date y num ya precargados).
- **Por qué:** el nombre del banco es una etiqueta editable; vincular por ella es frágil. La clave_corta no cambia. El orden estricto + Enter replica el flujo de Quicken y evita capturas desordenadas.
- **Dónde:** `AdminPanelController` (helper `claveCuentaWiese`; los 3 métodos wieseBanco/Guardar/Borrar ahora buscan por clave_corta), `resources/views/admin/wiese-banco.blade.php` (JS: ORDEN array, enfocar(col), keydown solo Enter/Tab en orden, foco inicial en payee).
- **PENDIENTE:** probar en navegador (Ctrl+F5): Num 80195 precargado, captura con Enter en orden, guardado sin rojo, y Delete (seleccionar fila guardada → se activa).

## 2026-10-07 — WieseBanco: Num precargado + diagnóstico "no guardaba" (era MySQL caído)
- **Qué:** (1) La fila de captura NO guardaba (borde rojo, Num/Balance vacíos, Ending Balance 0): el log mostró SQLSTATE[HY000] [2002] — MySQL estaba caído al momento de guardar (volvió a arrancar después; recordatorio de la fragilidad de XAMPP). No era bug del código. (2) A pedido de dirección, el NUM ahora se PRECARGA visualmente con el siguiente consecutivo (consecutivo_actual + 1 = 80195) en la fila de captura, igual que la fecha. Al guardar una fila, la nueva fila que aparece precarga el siguiente num (num+1). El folio REAL lo sigue asignando el servidor al guardar (el precargado es solo visual).
- **Por qué:** dar feedback visual del folio que tomará el movimiento (como Quicken) y confirmar que la cuenta está lista. OJO operativo: si MySQL se cae, el guardado falla con borde rojo; verificar que MySQL/servicio esté arriba antes de capturar.
- **Dónde:** `AdminPanelController::wieseBanco` (pasa `$siguienteNum`), `resources/views/admin/partials/wiese-banco-row.blade.php` (num value=$siguienteNum), `resources/views/admin/wiese-banco.blade.php` (JS: al crear fila nueva precarga num+1).
- **PENDIENTE:** probar guardado+delete ahora que MySQL está arriba. Luego Pieza 4 (pago a Contpaqi).

## 2026-10-07 — WieseBanco Pieza 3d: botón Delete funcional + recálculo de saldos
- **Qué:** El botón Delete de la barra ahora funciona. Flujo: clic en una fila guardada → se selecciona (azul fuerte) y se activa Delete; clic en Delete → confirma y borra ese movimiento. Al borrar, el servidor RECALCULA en cadena los balances de todos los movimientos (orden fecha, id) desde 0 y actualiza `saldo_actual` de la cuenta. Probado vía tinker: 3 movs (10000→7000→5000), borrado el de en medio (pago 3000) → quedó 10000→8000 correctamente.
- **Por qué recalcular:** el balance de cada fila depende de las anteriores; borrar una de en medio dejaría mal todas las siguientes. Se rehace el saldo acumulado completo dentro de una transacción con lockForUpdate.
- **Dónde:** `app/Http/Controllers/AdminPanelController.php` (método `wieseBancoBorrar`, usa route-model binding de `MovimientoBancario` + valida que el mov pertenezca a la cuenta), `routes/web.php` (ruta DELETE `admin.wiese-banco.borrar`), `resources/views/admin/wiese-banco.blade.php` (CSS .seleccionada, JS: clic selecciona fila, botón Delete hace fetch DELETE y recarga para repintar balances; filas guardadas llevan data-id; al guardar una fila nueva se le asigna data-id para poder borrarla sin recargar).
- **PENDIENTE:** validar en navegador. Luego Pieza 4 (conectar pago a Contpaqi vía API C#).

## 2026-10-07 — WieseBanco Pieza 3c: look idéntico a Quicken + bloqueo de otras cuentas
- **Qué:** (1) Sidebar: solo BBVA es clickeable; las demás cuentas (Santander, Banorte, etc.) se muestran en gris con "(próximamente)" y NO enlazan. Además el controlador `wieseBanco` redirige a bbva si alguien entra a otra cuenta por URL. (2) Rediseño total de la hoja para que sea idéntica a Quicken 2007: cabecera azul con nombre de cuenta + pestañas Register/Overview (Overview desactivada, dirección no sabe aún qué va); barra de acciones Delete/Find/Transfer/Reconcile/Write Checks/Set Up Online (solo Find activo, Delete deshabilitado hasta seleccionar, resto "próximamente"); tabla con bandeado azul/blanco, Payee arriba + Category en gris abajo, scroll vertical en la zona de movimientos; pie con "Ending Balance" a la derecha que se actualiza al guardar. Paleta y tipografía (Tahoma) replicadas de las capturas.
- **Por qué:** dirección pidió replicar Quicken EXACTAMENTE para que Contabilidad no sienta el cambio. CLR se mantiene QUITADA (Sandra no la usa) aunque Quicken la tenga.
- **Dónde:** `resources/views/layouts/admin.blade.php` (sidebar: solo bbva habilitado), `app/Http/Controllers/AdminPanelController.php` (`wieseBanco` redirige a bbva si no es bbva), `resources/views/admin/wiese-banco.blade.php` (rediseño Quicken completo + CSS .qk-*), `resources/views/admin/partials/wiese-banco-row.blade.php` (clases qk-cell/c-*).
- **PENDIENTE:** que la usuaria valide el look en el navegador (Ctrl+F5). Posibles ajustes finos de color/espaciado. Luego Pieza 4 (conectar pago a Contpaqi). Mejoras: botón Delete funcional, Find, Overview cuando dirección defina qué lleva.

## 2026-10-07 — WieseBanco Pieza 3b: ajustes de dirección (etiqueta, fecha hoy, Enter, azul)
- **Qué:** 4 ajustes pedidos por dirección a la hoja de WieseBanco: (1) la cuenta BBVA se etiqueta "BBVA 8969 MXN (28)" en el sidebar y en el título (cambiado en `config/wiese_bancos.php`, se refleja en ambos lados). (2) La fecha de cada fila nueva viene PRECARGADA a hoy (`now()->format('d/m/Y')`), solo se da Enter para confirmar. (3) Enter ahora avanza a la siguiente casilla (igual que Tab), ya no baja en la misma columna. (4) La fila que se está capturando se resalta en AZUL estilo Quicken (clase `.wb-row-activa`, fondo #cfe3fb).
- **Por qué:** replicar la experiencia de Quicken para que Contabilidad (Sandra) y dirección se sientan en casa; menos tecleo (fecha automática) y navegación con Enter como en el programa viejo.
- **Dónde:** `config/wiese_bancos.php` (label bbva), `resources/views/admin/partials/wiese-banco-row.blade.php` (value fecha hoy), `resources/views/admin/wiese-banco.blade.php` (CSS .wb-row-activa azul; JS: focusin resalta fila, keydown Enter=Tab avanza casilla).

## 2026-10-07 — WieseBanco Pieza 3: la hoja (estilo Excel) ya GUARDA de verdad
- **Qué:** Se conectó la vista `admin/wiese-banco.blade.php` (estilo Quicken/Excel, decisión de la usuaria: conservar edición en celdas). Ahora: (1) el controlador `wieseBanco` carga la cuenta real (match por banco) y sus movimientos; (2) la vista pinta los movimientos guardados como filas de solo lectura + filas vacías para capturar; (3) nuevo método `wieseBancoGuardar` + ruta POST `admin/wiese-banco/{banco}/movimiento` guardan por AJAX; (4) al capturar una fila con fecha + monto y salir de ella, el JS la envía, el servidor genera el NUM con `siguienteFolio()`, calcula balance (saldo + deposit - payment) y lo devuelve para pintarlo. Se QUITÓ la columna CLR (Sandra no la usa). Columnas num y balance quedaron readonly (las genera el servidor). Probado vía tinker: primer movimiento tomó NUM 80195 y balance correcto; luego se limpió (truncate + reset a 80194/saldo 0) para dejar la pantalla lista.
- **Por qué:** la hoja antes era solo cáscara (20 filas que no guardaban). Ahora respeta el estilo Excel pero persiste de verdad, con el folio naciendo del consecutivo por cuenta (no del usuario) y el balance calculado en el servidor (no manipulable desde el navegador).
- **Dónde:** `app/Http/Controllers/AdminPanelController.php` (`wieseBanco`, `wieseBancoGuardar`, +use CuentaBancaria/MovimientoBancario/DB), `routes/web.php` (ruta POST guardar), `resources/views/admin/wiese-banco.blade.php` (tabla + JS de guardado AJAX), `resources/views/admin/partials/wiese-banco-row.blade.php` (sin clr; num/balance readonly).
- **PENDIENTE:** Pieza 4 = al capturar un pago a PROVEEDOR, además de guardar el movimiento, llamar a la API C# `CrearPagoYSaldar` pasando el NUM como folioQuicken + datos de la factura, y guardar el `iddocumento_contpaqi`. Mejoras menores posibles: editar/borrar movimientos ya guardados, validar formato de fecha en el front, mostrar el saldo inicial de la cuenta.

## 2026-10-07 — WieseBanco Pieza 2: modelos + consecutivo por cuenta (probado)
- **Qué:** Se crearon los modelos `CuentaBancaria` y `MovimientoBancario`. El método clave es `CuentaBancaria::siguienteFolio()`: lee el `consecutivo_actual` real de la BD, le suma 1, lo guarda y lo devuelve. Probado con tinker: 80194 → 80195 → 80196 (y se restauró a 80194 porque la prueba consumió folios). El consecutivo real para el primer pago será 80195.
- **Por qué con transacción + lockForUpdate:** si dos pagos ocurren casi al mismo tiempo, sin bloqueo podrían tomar el MISMO número. El `lockForUpdate` aparta la fila de la cuenta hasta terminar, garantizando folios únicos y consecutivos (como Quicken). El folio NO se pasa por fuera: la cuenta es la única fuente del número.
- **Dónde:** `app/Models/CuentaBancaria.php` (método `siguienteFolio()`, relación `movimientos()`), `app/Models/MovimientoBancario.php` (relación `cuenta()`).
- **PENDIENTE WieseBanco:** Pieza 3 = conectar la vista `admin/wiese-banco.blade.php` para cargar/guardar movimientos reales y calcular balance (hoy es solo cáscara, 20 filas vacías que no guardan). Pieza 4 = al registrar un pago a proveedor: generar movimiento con `siguienteFolio()`, llamar a la API C# `CrearPagoYSaldar` pasando ese NUM como folioQuicken, y guardar el `iddocumento_contpaqi` que devuelva.

## 2026-10-07 — Base limpia para producción: migrate:fresh + solo 12 usuarios reales
- **Qué:** Se dejó la BD local `salcom20` VACÍA de datos de prueba/fakes, con solo los usuarios reales. Comando: `php artisan migrate:fresh --seeder=Database\Seeders\UsuariosProduccionSeeder`. Resultado verificado: admin_users=12, proveedores=0, clientes=0, facturas=0, cuentas_bancarias=1 (BBVA 969, config que se conserva). Respaldo del estado limpio: `storage/backups/salcom20_20261007_134729.sql`.
- **Por qué:** la usuaria pidió base en 0 para producción, solo con los logins reales. Los 12 usuarios (ya existían en `UsuariosProduccionSeeder`): 7 admin (alex.salazar, aneso.cominu, fredcominu, jesus.espinoza, karen.bravo, Rebeca, sandra.gutierrez), cintia.barrera (comercial), acela.bolanos y cinthya.martinez (compras_importacion), brenda.pliego (compras_nacional), blanca.paganoni (mantenimiento). CLI001 y PROV001 se dejaron FUERA a propósito (se crearán al dar de alta cliente/proveedor reales). Todo esto es SOLO en la BD local, NO toca producción.
- **Dónde:** `database/seeders/UsuariosProduccionSeeder.php` (los 12 users).
- **AJUSTE HECHO:** `DatabaseSeeder.php` ahora corre SOLO `UsuariosProduccionSeeder` (se quitaron ProveedorUserSeeder, ClienteUserSeeder, AdminUserSeeder, DatosPruebaSeeder y el User::factory de prueba). Así `php artisan db:seed` y `migrate:fresh --seed` dejan la base limpia + 12 usuarios, sin reinyectar fakes. Si se quieren datos de prueba en local: `php artisan db:seed --class=DatosPruebaSeeder` a mano. Verificado: tras db:seed, admins=12, proveedores=0, facturas=0.

## 2026-10-07 — Prevención: comando de respaldo db:backup + MySQL como servicio
- **Qué:** (1) Se repobló la base con `php artisan db:seed` (7 proveedores, admins, datos de prueba). (2) Se creó el comando `php artisan db:backup` que exporta la base a `storage/backups/salcom20_AAAAMMDD_HHMMSS.sql` usando mysqldump; conserva los últimos 15 respaldos (opción `--keep`). Primer respaldo OK: 46 tablas, 94 KB. (3) La usuaria configuró XAMPP para abrir siempre como admin e instaló MySQL como SERVICIO de Windows (casilla verde) para que Windows lo apague ordenadamente.
- **Por qué:** MySQL se corrompe cuando se cierra mal. Correrlo como servicio + respaldo frecuente convierte una corrupción futura en "restaurar 1 archivo" en vez de reinstalar. Prepara el terreno para producción.
- **Dónde:** comando en `app/Console/Commands/RespaldarBaseDatos.php` (signature `db:backup`). Respaldos en `storage/backups/`.
- **Cómo restaurar (para el yo futuro):** crear la BD vacía y correr `mysql -u root salcom20 < ruta_del_respaldo.sql` (o importar el .sql en phpMyAdmin). PENDIENTE opcional: programar el `db:backup` diario en el scheduler de Laravel.
- **Para producción (migraciones):** la ESTRUCTURA (tablas, columnas, usuarios/perfiles) se replica corriendo `php artisan migrate` en el servidor tras subir el código con git — nunca cambiar la BD a mano. Los DATOS de producción son los reales (no seeders). Así local y producción quedan con el MISMO esquema.

## 2026-10-07 — MySQL corrupto OTRA VEZ: reinicializado de raíz + tablas WieseBanco
- **Qué:** MariaDB (XAMPP) no arrancaba: error InnoDB "log sequence number is in the future" = los `ib_logfile` dejaron de empatar con `ibdata1`. force_recovery (niveles 1 y 3) NO lo resolvió (ese error no es corrupción simple, es desajuste logs/datos). Tampoco sirvió restaurar el respaldo del 22-sep (venía tocado). SOLUCIÓN DEFINITIVA: se apartó la `data` corrupta (renombrada a `data_restaurorota_*`, NO borrada), se reinicializaron las tablas de sistema con `mysql_install_db.exe --datadir=C:/xampp/mysql/data`, se recreó la BD `salcom20` y se corrieron TODAS las migraciones (`php artisan migrate --force`). MySQL quedó sano (puerto 3306 OK, 0 errores).
- **POR QUÉ se corrompe cada cierto tiempo (agosto, sept, oct):** MySQL se cierra en mal estado (laptop se suspende/apaga sin parar XAMPP, o se mata el proceso). NO es mala suerte, es patrón. PREVENCIÓN: (1) parar MySQL en XAMPP antes de apagar la laptop; (2) no suspender/hibernar con MySQL en verde; (3) nunca matar mysqld desde el administrador de tareas. PENDIENTE sugerido: script de respaldo automático (mysqldump) para que restaurar sea 1 comando.
- **Dónde:** `C:\xampp\mysql\bin\my.ini` (se dejó `innodb_force_recovery = 0`). Carpetas apartadas con datos viejos: `C:\xampp\mysql\data_restaurorota_20261007_132304` y `data_rota_20261007_132200` (por si se quiere rescatar algo después). La base recreada está vacía de datos de negocio: FALTA correr `php artisan db:seed` para repoblar datos de prueba.

## 2026-10-07 — WieseBanco: tablas base (cuentas_bancarias + movimientos_bancarios)
- **Qué:** Primera pieza de WieseBanco (el registro bancario que reemplaza a Quicken 2007). Migración `2026_10_06_120000_create_wiese_banco_tables.php` con 2 tablas: `cuentas_bancarias` (nombre, banco, clave_corta, concepto_contpaqi, consecutivo_actual, saldo_actual) y `movimientos_bancarios` (cuenta_id, fecha, num, payee, categoria, memo, payment, deposit, balance, iddocumento_contpaqi, codigo_proveedor, estatus). Se insertó la cuenta "BBVA 969 SALCOM PESOS" (banco BBVA, clave 8969, concepto Contpaqi 28, consecutivo_actual=80194).
- **Por qué:** Decisión con Chuy (dirección): WieseBanco es el ORIGEN del pago (Opción 1), no un espejo. El folio del pago sale del consecutivo por cuenta (columna NUM de Quicken); BBVA 969 iba en 80194 en Quicken, así el siguiente será 80195 y no choca con lo viejo. SIN columnas CLR ni R (Sandra, quien concilia, no les ve uso). Por ahora SOLO la cuenta BBVA MXN 8969 (concepto 28); las demás después.
- **Dónde:** `database/migrations/2026_10_06_120000_create_wiese_banco_tables.php`. Vista ya existente (solo cáscara, aún no guarda): `resources/views/admin/wiese-banco.blade.php`.
- **PENDIENTE (plan WieseBanco):** Pieza 2 = lógica del consecutivo por cuenta (tomar consecutivo_actual +1 en transacción). Pieza 3 = conectar la vista para que cargue/guarde movimientos reales y calcule balance. Pieza 4 = al registrar pago, llamar a la API C# `CrearPagoYSaldar` pasando el NUM como folio y guardar el iddocumento_contpaqi.

## 2026-10-05 — ETAPA 2 COMPLETA + 2 correcciones de Alan (folio Quicken y observaciones)
- **Qué:** Se probó `CrearPagoYSaldar` con Alan y SALIÓ EXITOSO: el pago se crea y se salda contra la factura real en Contpaqi (puente C#→Contpaqi funcionando de punta a punta). Alan pidió 2 correcciones que ya se aplicaron:
  1. FOLIO del pago = número de la "nota de pago de Quicken" (viene de Quicken, solo números), NO el folio autogenerado por Contpaqi. Antes: `pago.Folio = siguiente.aFolio;` → Ahora: `pago.Folio = folioQuicken;`.
  2. OBSERVACIONES del pago = el folio de la factura que se está pagando. Antes: texto fijo → Ahora: `pago.Observacion = "Factura: " + folioFactura;`.
- **Por qué:** así cada pago en Contpaqi queda identificado con su nota de Quicken (folio) y se sabe qué factura paga (observaciones). Es la forma en que concilian con su otro sistema (Quicken).
- **Dónde:** `PagoProveedorController.cs` método `CrearPagoYSaldar`, que ahora recibe por URL: `folioQuicken` (double), `conceptoFactura` (string), `serieFactura` (string), `folioFactura` (double). La SERIE del pago se dejó como la autogenerada por Contpaqi (`siguiente.aSerie`) porque Alan solo mencionó folio y observaciones; revisar con él si la serie también cambia.
- **PENDIENTE:** probar con Alan la versión con las 2 correcciones; confirmar si la serie del pago queda como está. Luego: conectar con la web PHP (que folioQuicken/importe/datos de factura lleguen desde el flujo de pagos de la web en vez de escribirse a mano), manejar importe real/parcial (hoy sigue fijo en 100).

## 2026-10-05 — FacturasProveedor FUNCIONA: 78 facturas reales de ORPACK desde Contpaqi
- **Qué:** Con concepto **21** (compra, lo dio Alan) + código ORPACK M213015002, el endpoint `FacturasProveedor` trajo 78 facturas de compra REALES de Contpaqi (serie mayormente "ALMP", folios ~92610 a 93230, totales reales). El `""` NO servía: el filtro necesita el concepto de compra (21). Antes tardaba mucho con rango de 2 años; se recomendó bajar a `AddMonths(-6)` para que responda rápido.
- **Por qué importa:** se confirma que la fuente de verdad es Contpaqi y que la API ya la lee bien (serie/folio/concepto reales). Esto es lo que alimentará el saldado y, después, la web.
- **Dónde:** `PagoProveedorController.cs` método `FacturasProveedor` (concepto 21). Para probar el saldado se eligió una factura de total bajo: concepto 21, serie ALMP, folio 92744, total 1240.6432.
- **PENDIENTE:** poner esos 3 datos en `CrearPagoYSaldar` (Paso 6: conceptoFactura="21", serieFactura="ALMP", folioFactura=92744) y probar el saldado con Alan. OJO: el pago de prueba es de importe 100 → saldaría PARCIALMENTE esa factura (queda debiendo el resto); avisar a Alan antes de que lo vea en Contpaqi. idDocumentos creados: 2424366 (pedido), 2425654 (abono suelto).

## 2026-10-05 — Prueba de FacturasProveedor: ORPACK sin facturas (SDK devuelve "2" = vacío)
- **Qué:** Al correr `GET /api/PagoProveedor/FacturasProveedor?codigo=M213015002` (ORPACK) devolvió error 400 "No se encontró el registro". NO es bug del endpoint: significa que ORPACK no tiene facturas de compra en Contpaqi adSalcom18 en los últimos 2 años.
- **Por qué (confirmado leyendo el SDK):** en `SDKComercial\Extras\Repositories\DocumentoSdkRepository.cs` (método `TraerPorRangoFechaYCodigoConceptoYCodigoClienteProveedor`) hay un comentario de Wiese: cuando `fSetFiltroDocumento` devuelve resultado "2" = NO hay documentos en el filtro (y ellos lo manejan devolviendo lista vacía, no excepción). La versión que usamos (`DocumentoSdk.BuscarDocumentosPorFiltro`) NO maneja ese caso: hace `fPosPrimerDocumento().TirarSiEsError()` y truena. Por eso el 400. También se confirmó que mandar concepto vacío ("") es válido (ellos pasan el codigoConcepto tal cual, sin obligar valor).
- **Dónde:** `PagoProveedorController.cs` método `FacturasProveedor`. Referencia del comportamiento correcto: `DocumentoSdkRepository.cs` líneas ~70-90.
- **PENDIENTE con Alan:** pedir un proveedor que SÍ tenga facturas de compra en Contpaqi adSalcom18 (o directamente concepto+serie+folio de una factura real) para probar `CrearPagoYSaldar`. MEJORA opcional sugerida: hacer que `FacturasProveedor` devuelva lista vacía (como el repo de Wiese) en vez de tronar cuando no hay facturas.

## 2026-10-02 — Etapa 2 diseñada (saldar factura) + endpoint para listar facturas de Contpaqi
- **Qué:** Se entregó (para copiar a mano) el código de la Etapa 2: endpoint `POST /api/PagoProveedor/CrearPagoYSaldar` que crea el abono (igual que Etapa 1) y además lo liga a una factura con `pago.SaldarCargo(conceptoFactura, serieFactura, folioFactura, importe, 1, fecha)`. También se entregó un endpoint de consulta `GET /api/PagoProveedor/FacturasProveedor?codigo=...` que usa `DocumentoSdk.BuscarDocumentosPorFiltro(desde, hasta, "", codigo)` para listar las facturas REALES del proveedor en Contpaqi (concepto vacío = trae todo; rango 2 años).
- **Por qué:** las facturas de la base local de la web son datos de PRUEBA ficticios (folios CFDI-A-00xxxx, uuid null) que NO existen en Contpaqi, así que no sirven para saldar. La solución sin "pared" es que la fuente de verdad sea Contpaqi: la web pedirá las facturas a la API (que las trae del SDK), por eso serie/folio/concepto coincidirán por diseño. Decisión con la usuaria: para probar la Etapa 2 NO hay que modificar la web; el ajuste real (que la web lea facturas desde Contpaqi en vez de la tabla local, y borrar las ficticias) se hará DESPUÉS, cuando el saldado funcione.
- **Dónde:** `PagoProveedorController.cs` en `APIPortalWeb` (dos métodos nuevos: `FacturasProveedor` GET y `CrearPagoYSaldar` POST). Requiere `using System.Linq;`. Proveedor de prueba ORPACK = `M213015002`.
- **Detalle contemplado:** en `CrearPagoYSaldar`, si el pago se crea pero el saldado falla, se devuelve el idDocumento igual (pago "suelto" que habría que cancelar/saldar a mano). Importante para no dejar pagos huérfanos.
- **PENDIENTE mañana:** (1) correr `FacturasProveedor` con ORPACK y anotar concepto+serie+folio de una factura real de Contpaqi; (2) rellenar esos 3 datos en el Paso 6 de `CrearPagoYSaldar`; (3) probar con Alan. Si ORPACK no tiene facturas en Contpaqi, usar otro proveedor que sí tenga.

## 2026-10-02 — ETAPA 1 COMPLETA: la API crea un pago real en Contpaqi (idDoc 2425654)
- **Qué:** Con el proveedor ORPACK (código `M213015002`, confirmado con Alan) el endpoint `POST /api/PagoProveedor/CrearPago` creó el documento de abono correctamente. Respuesta: `ok=true, idDocumento=2425654, serie=8969, folio=3850965172`. Alan lo verificó en su Contpaqi: el documento existe.
- **Por qué importa:** cierra la Etapa 1. La API ya conecta, usa concepto 28, pide serie/folio y da de alta el abono sin ventanitas. El pago, sin embargo, está "suelto": NO está ligado a ninguna factura todavía (Alan lo señaló: "¿a qué factura está relacionado ese pago?"). Eso es exactamente lo que resuelve la Etapa 2.
- **Dónde:** `PagoProveedorController.cs` (controlador de la API), proveedor `M213015002`. idDocumentos de prueba creados hasta ahora: 2424366 (pedido prueba), 2425654 (abono/pago ORPACK).
- **ETAPA 2 (siguiente):** agregar `pago.SaldarCargo(conceptoFactura, serieFactura, folioFactura, importe, idMoneda, fecha)` para ligar el pago a una factura real de ORPACK y saldarla. PENDIENTE: pedir a Alan concepto+serie+folio de una factura de ORPACK pendiente en Contpaqi para probar el saldado.

## 2026-10-02 — Etapa 1 avanza: resueltos 2 errores de compatibilidad; falta proveedor válido
- **Qué:** Al probar el endpoint `POST /api/PagoProveedor/CrearPago` salieron 2 errores que ya se resolvieron, y un 3ro que es de datos (no de código):
  1. "Method requires System.Windows.Forms" → el SDK intenta mostrar un MsgBox de error (ventana de escritorio) y la API web no tiene pantalla. Se activó `<UseWindowsForms>true</UseWindowsForms>` en `APIPortalWeb.csproj`.
  2. "La plataforma de destino debe establecerse en Windows" → Windows Forms solo existe en Windows. Se cambió `<TargetFramework>net8.0</TargetFramework>` a `net8.0-windows`. (Esto va de la mano con el x86: la API siempre corre en el server Windows de Contpaqi.)
  3. (ACTUAL) "Codigo SDK: 120119 - El Proveedor no está registrado como Proveedor/Cliente" → el código de prueba `PROPIO2` es un CLIENTE (lo usaba DocumentoPrueba para ventas), pero un abono/pago va a un PROVEEDOR. PROPIO2 no está dado de alta como proveedor en Contpaqi adSalcom18.
- **Por qué importa:** el error 120119 CONFIRMA que el código del pago funciona (conexión OK, concepto 28 OK, serie/folio OK, ya pasó la ventanita). Solo falta usar un código que SÍ sea proveedor. Alan confirmó antes que el código de proveedor es el mismo de Wiese.
- **Dónde:** `APIPortalWeb.csproj` (`UseWindowsForms` + `net8.0-windows`); `PagoProveedorController.cs` línea de `ClienteSdk.BuscarClientePorCodigo("PROPIO2")` → cambiar PROPIO2 por un código de proveedor real/de prueba que exista en Contpaqi.
- **PENDIENTE:** pedir a Alan un código de proveedor válido en Contpaqi adSalcom18 para probar; sustituirlo en el controlador; volver a probar hasta obtener idDocumento del abono.

## 2026-09-08 — Respuestas de Alan + Etapa 1 del pago (crear documento de abono)
- **Qué:** Alan respondió las 3 dudas: (1) la "llave" de la factura a pagar (concepto+serie+folio) sale de NUESTRO sistema/web, NO se busca en Contpaqi — el reto está en nuestro lado; mencionó usar `SaldarCargo` + `SdkDocumentoAbono`. (2) El código de proveedor es el MISMO que usamos en Wiese (no hay catálogo aparte). (3) SÍ se permite pago parcial (se manda el importe que sea). Decisión: usar la clase `SdkDocumentoAbono` (`Insertar()` + `SaldarCargo()`), NO `DocumentoSdk.SaldarDocumento`, porque es lo que mencionó Alan y `SaldarCargo` recibe directo concepto+serie+folio. Regla acordada: si algo del flujo PHP no cuadra con el SDK/Contpaqi, se ajusta NUESTRO lado (Contpaqi y el SDK no se tocan).
- **Por qué:** confirmamos que la web YA tiene los datos de la factura (`AdminPagoProveedoresController`: serie desde `$vd['serie']`, folio = `folio_cfdi`, concepto = 'Compra', saldo = total - monto_pagado). El pago parcial sale gratis porque `SaldarCargo`/`Insertar` reciben un importe. El código de proveedor reutilizable evita mapeos.
- **Dónde:** se entregó código (para copiar a mano) de `PagoProveedorController.cs` en la carpeta Controllers de `APIPortalWeb` (namespace `APIPortalWeb.Controllers`). Endpoint `POST /api/PagoProveedor/CrearPago`. Flujo: `ConexionSdk.IniciarSdk` → `AbrirEmpresa(adSalcom18)` → `ConceptoSdk.BuscarConceptoPorCodigo("28")` → `ClienteSdk.BuscarClientePorCodigo("PROPIO2")` (prueba) → `DocumentoSdk.BuscarSiguienteSerieYFolio` → `new SdkDocumentoAbono{...}` → `await pago.Insertar()` → `CerrarEmpresa`/`TerminarSdk`. Nombres de métodos confirmados leyendo `ConexionSdk.cs`, `ConceptoSdk.cs`, `SdkDocumentoAbono.cs`.
- **ETAPA 1 (actual):** solo CREA el documento de pago (importe 100 fijo, proveedor PROPIO2 de prueba), NO salda factura. Objetivo: que devuelva un idDocumento como la prueba del 2424366. **PENDIENTE probar y anotar el idDocumento.**
- **ETAPA 2 (siguiente):** agregar `pago.SaldarCargo(conceptoFactura, serieFactura, folioFactura, importe, idMoneda, fecha)` para ligar el pago a la factura real; recibir los datos reales (concepto 28/283 según cuenta, código proveedor real, serie/folio/importe de la factura) desde la web PHP.

## 2026-09-07 — Puente C# → Contpaqi: definido el camino para el pago a proveedor
- **Qué:** Se estudió el SDK de Wiese (`SDKWieseNET48`) para armar el "pago a proveedor" real en Contpaqi desde la API `APIPortalWeb`. Se decidió usar la clase `DocumentoSdk` (la misma que ya funcionó en la prueba del idDocumento 2424366), NO la clase `SdkDocumentoAbono`. Un pago se arma en 2 pasos: (1) `DocumentoSdk.CrearDocumentoCargoAbono(doc)` crea el documento de pago; (2) `DocumentoSdk.SaldarDocumento(facturaAPagar, pago, importe, fecha)` liga el pago a la factura y la salda.
- **Por qué:** `DocumentoSdk` ya tiene AMBOS métodos (crear cargo/abono y saldar) y es código que ya corrió y Alan avaló. Mantenerse en una sola clase = menos confusión y menos riesgo que mezclar con `SdkDocumentoAbono`. El concepto de pago NO es fijo: depende de la cuenta bancaria. Alan confirmó: cuenta MXN (banco 8969) → concepto **28**; agente aduanal (8969) → concepto **283**. Faltan los otros 2 conceptos (las otras 2 cuentas del formulario de abono). La serie y folio del pago los autogenera Contpaqi con `BuscarSiguienteSerieYFolio(concepto)` (esto responde la duda del "Folio/Nº Póliza" que había para Karen: se autogenera).
- **Dónde:** SDK en `C:\Users\IT\source\repos_git\SDKWieseNET481\...\SDKComercial\Entidades\DocumentoSdk.cs` (métodos `CrearDocumentoCargoAbono`, `SaldarDocumento`, `BuscarSiguienteSerieYFolio`); patrón de conexión visto en `SDKComercial\Pruebas\DocumentoPrueba.cs` (`CrearPedido2`). La API `APIPortalWeb` ya está en x86 y referencia al SDK; `PruebaController` ya probado.
- **PENDIENTE (esperando respuestas de Alan para mañana):** (1) de dónde salen concepto+serie+folio de la factura de proveedor a saldar; (2) si el código de proveedor es el mismo de Wiese o uno propio de Contpaqi; (3) si se permite pago parcial y cómo se maneja la moneda (hoy todo asume pesos, moneda 1, tipo de cambio 1). Siguiente código a entregar: endpoint de pago en 2 etapas (Etapa 1: solo crear el documento de pago; Etapa 2: agregar el saldado).

## 2026-09-30 — Portal EXTRANJERO independiente (no comparte nada con el nacional)
- **Qué (dirección):** el proveedor extranjero, al entrar al portal, NO debe ver nada del portal nacional (ni facturas MX, OC, onboarding mexicano, Wiese, identificación fiscal, módulo IA). Tiene su PROPIO portal en inglés con menú: Home · My Onboarding · Invoices · Purchase Orders · My Profile.
- **Cómo:**
  - Layout nuevo `layouts/proveedor-extranjero.blade.php` (sidebar propio, en inglés, standalone; NO usa el layout nacional).
  - Vistas nuevas: `foreign-portal` (Home/dashboard con estado del onboarding), `foreign-invoices`, `foreign-po`, `foreign-profile`.
  - Controlador `PortalProveedorController`: `mostrarForeignPortal/Invoices/Po/Profile` + helper `soloExtranjero()` (si no es extranjero, redirige al portal nacional).
  - Rutas `proveedores.foreign-portal/-invoices/-po/-profile`.
  - Login (`AuthProveedorController::redirectTrasLoginProveedor`): extranjero activo → `foreign-portal`; inactivo → `foreign-onboarding`; nunca ve el portal/onboarding mexicano.
  - AISLAMIENTO en middleware `AutenticacionProveedor`: lista blanca `$rutasExtranjero`; un extranjero que pida una ruta nacional → redirect a su portal (y salta TODA la lógica mexicana: onboarding, 2 contactos, módulos). Un nacional que pida una ruta `foreign-*` → redirect al portal nacional.
  - Fix: el link "Go to portal" del onboarding extranjero ahora apunta a `foreign-portal` (antes iba al nacional).
- **Verificado (e2e):** sus 5 rutas PASAN; las 5 nacionales (facturas, oc, onboarding, fiscal, ia) REDIRIGEN a su portal; las 4 vistas renderizan OK. `php -l` OK, vistas compilan, rutas registradas.
- **PENDIENTE deploy:** subir + `php artisan migrate` en prod (columna es_extranjero) + limpiar caché/OPcache.

## 2026-09-30 — Portal de proveedores en inglés para extranjeros — Parte A: layout/sidebar (i18n real)
- **Qué:** se montó el sistema de traducción real de Laravel (locale + archivos lang) y se tradujo el layout/sidebar/header del portal de proveedores. Un proveedor extranjero ve el portal en inglés; el nacional en español.
- **Cómo:**
  - `AutenticacionProveedor` (middleware `auth.proveedor`) fija el locale: `app()->setLocale($prov->esExtranjero() ? 'en' : 'es')`. OJO: el default de la app es `en`, por eso se fuerza `es` explícitamente para el nacional (no basta con no tocar nada).
  - Archivos `lang/es/portal.php` y `lang/en/portal.php` con las claves del sidebar/header (secciones, enlaces, notificaciones, salir, banner onboarding, tooltips).
  - `layouts/proveedor.blade.php`: todos los textos fijos → `__('portal.*')`; `<html lang>` y "Portal de Proveedores"/"Supplier Portal" según locale.
- **Por qué así (Opción A):** es el enfoque escalable; se irá traduciendo pantalla por pantalla reusando este mismo mecanismo (solo agregar claves y envolver textos en `__()`).
- **Verificado:** `php -l` OK, vistas compilan, `__('portal.*')` resuelve EN y ES correctamente.
- **Parte B — Dashboard/Inicio (`proveedores/portal.blade.php`):** traducido con claves `portal.dash.*` en `lang/es` y `lang/en`. Incluye las 5 KPI cards (Alta de producto, Facturas, Mis productos, Alta de facturas, OTIF), la sección "Accesos directos" con sus 5 tarjetas, los "Ver detalle →"/tooltips de módulos bloqueados y el title de la pestaña. Verificado: compila y resuelve EN/ES.
- **PENDIENTE:** seguir con las demás vistas del portal (facturas, OC, OTIF, perfil; onboarding mexicano NO aplica al extranjero → usa `/foreign-onboarding`). Y desplegar (incluye crear `lang/` en prod vía git + limpiar caché).
- **OJO onboarding:** el extranjero NO debe ver el onboarding mexicano; su onboarding es `/foreign-onboarding` (ya en inglés). Si en prod ve el mexicano, es porque falta correr `php artisan migrate` allá (sin la columna `es_extranjero` lo tratan como nacional).

## 2026-09-30 — Registro extranjero: traducir TODA la página a inglés al elegir "Foreign"
- **Qué:** al seleccionar "Foreign supplier" en el registro, ahora se traduce a inglés toda la página (antes solo se traducían algunas etiquetas): encabezado "Registro de Proveedor"/subtítulo, "PORTAL DE PROVEEDORES", "Volver al login", "Verificación de seguridad", "¿Ya tienes cuenta?/Inicia sesión", footer, sección "Contacto y acceso", y los placeholders (teléfono, correo, contraseña).
- **Por qué:** dirección pidió que todo lo del extranjero esté en inglés; quedaban textos en español.
- **Dónde:** `resources/views/proveedores/registro.blade.php` — se agregaron ids (`txt_portal`, `txt_card_title`, `txt_card_sub`, `txt_back_login`, `txt_divider`, `txt_have_account`, `txt_footer`) y la función JS `setOrigen()` ahora usa helpers `setText/setHtml/setPh` para traducir todo según nacional/extranjero.
- **Verificado:** la vista compila con `view:cache` (incluye `{{ route() }}`/`{{ date() }}` dentro del JS).
- **PENDIENTE deploy:** subir y limpiar caché en producción (`view:clear`); OPcache puede requerir reset.

## 2026-09-30 — Proveedor EXTRANJERO (importación / C-TPAT) — Parte 1: base + registro
- **Qué (dirección):** nuevo flujo para proveedores de importación. Se auto-registran con el MISMO login de los nacionales pero modificado: eligen "Foreign supplier" y el formulario cambia a inglés, pidiendo Company name + Tax ID (sin RFC/REPSE/Opinión SAT). Todo lo del extranjero va en inglés.
- **Por qué:** un proveedor extranjero no tiene RFC ni documentos fiscales mexicanos; usa el paquete C-TPAT (dos cuestionarios + 4 documentos).
- **Decisiones:** (1) selector National/Foreign al inicio del registro; (2) discriminador = columna booleana `es_extranjero` en `proveedores_users`; (3) flujo en dos pasos (registro corto → onboarding en inglés con cuestionarios + docs); (4) entrega partida: Parte 1 = base+registro, Parte 2 = onboarding en inglés, Parte 3 = panel admin de aprobación.
- **Hecho en Parte 1:**
  - Migración `2026_09_30_add_es_extranjero_to_proveedores_users.php` (columna `es_extranjero`).
  - Migración `2026_09_30_create_foreign_supplier_profiles_table.php` + modelo `ForeignSupplierProfile`: una tabla con el Business Profile (PDF 1) y el Security Questionnaire C-TPAT (PDF 2, ~24 Yes/No + Section III firma), columnas reales.
  - `ProveedorUser`: `es_extranjero` en fillable/casts, helper `esExtranjero()`, relación `perfilExtranjero()`, y `documentosRequeridos()` ramifica: si extranjero devuelve los 4 docs en inglés (`address_proof`, `tax_id_proof`, `contact_info`, `bank_letter`).
  - `AuthProveedorController::guardar` desvía a `guardarExtranjero()` cuando `es_extranjero=1`: valida company/tax_id/tel/correo/password (sin RFC), crea proveedor inactivo con `es_extranjero=true`, `moneda=DOLLAR`, pre-crea su `ForeignSupplierProfile`, textos en inglés.
  - Vista `proveedores/registro.blade.php`: selector National/Foreign + bloque de empresa en inglés + JS `setOrigen()` que togglea bloques, required y traduce labels compartidos.
- **Hecho en Parte 2 (onboarding en inglés, 1 pantalla / 3 pasos):**
  - Vista `proveedores/foreign-onboarding.blade.php`: una sola pantalla con pasos navegables — Step 1 Business Profile (PDF 1), Step 2 Security Questionnaire C-TPAT (Section I + 24 Yes/No con radios + Section III firma), Step 3 subida de los 4 documentos. Todo en inglés, JS `foGo(step)` para alternar pasos y `?step=` en la URL.
  - `PortalProveedorController`: `mostrarForeignOnboarding` (firstOrCreate del perfil + estado de docs), `guardarPerfilExtranjero` (step 1), `guardarSeguridadExtranjero` (step 2; checkbox/radio no marcado → null explícito), `subirDocumentoExtranjero` (los 4 docs a `foreign_supplier/{tipo}`, estatus pendiente, mensajes en inglés).
  - Rutas `proveedores.foreign-onboarding(.profile/.security/.document)` en routes/web.php.
  - `AuthProveedorController::redirectTrasLoginProveedor`: si el proveedor es extranjero e inactivo → lo manda a `foreign-onboarding` (en inglés); si es nacional, al onboarding mexicano de siempre.
- **Hecho en Parte 3 (panel admin de aprobación, en español para Dirección):**
  - `AdminPanelController::solicitudesAlta` ahora también carga `$extranjeros` (proveedores `es_extranjero` + inactivos) y los pasa a la vista.
  - Métodos nuevos: `verProveedorExtranjero` (pantalla de detalle), `aprobarProveedorExtranjero` (gate propio: perfil + cuestionario completos y los 4 docs subidos/no-rechazados; NO exige RFC/Wiese/CLABE/2 contactos), `rechazarProveedorExtranjero`. Notificaciones al proveedor en inglés.
  - Vista `admin/solicitudes-alta.blade.php`: sección nueva "Proveedores extranjeros (importación)" con tarjetas (borde azul) + aprobar/rechazar/ver; el botón Aprobar se deshabilita si no está "Listo".
  - Vista `admin/proveedor-extranjero-ver.blade.php`: detalle completo (Business Profile + las 24 Yes/No del cuestionario con badges + firma + los 4 documentos con enlace) y botones aprobar/rechazar. Closure `$peYn` (no función global) para los Yes/No/—.
  - Rutas `admin.solicitudes-alta.extranjero.ver/.aprobar/.rechazar`.
- **FLUJO COMPLETO (3 partes) terminado:** registro en inglés → onboarding en inglés (perfil + C-TPAT + 4 docs) → Dirección revisa y activa → el extranjero entra al panel de proveedores.
- **Verificado:** migraciones corridas, `php -l` OK, vistas compilan, rutas registradas; pruebas e2e: Parte 1 (registro), Parte 2 (profile+security con Yes/No/null + firma + 4 docs), Parte 3 (gate rechaza incompletos, aprueba y activa a completos).
- **PENDIENTE deploy:** correr `php artisan migrate` en producción tras subir (crea `foreign_supplier_profiles` y la columna `es_extranjero`).

## 2026-09-30 — Reembolsos con tabla propia (antes vivían en alertas/JSON)
- **Qué:** los reembolsos de empleados dejaron de guardarse en la tabla genérica `alertas` (tipo `solicitud_reembolso`, todo en JSON `datos`) y ahora tienen su propia tabla `reembolsos` con columnas reales. La autorización de Sandra, que vivía dentro del JSON (`autorizado_sandra`), ahora son columnas `autorizado` (bool) + `autorizado_fecha`. El `estatus` también es columna real (lo usa el conteo de pendientes del dashboard).
- **Por qué:** mismo motivo que gasolina: con JSON los filtros/conteos se hacían en PHP; con columnas se usan WHERE/COUNT/SUM de SQL. Dirección pidió tabla propia por módulo.
- **Dónde:**
  - Migración `2026_09_30_create_reembolsos_table.php`, modelo `App\Models\Reembolso`.
  - Escritura: `AdminPanelController::enviarReembolso` (guarda todos los campos fiscales) y `PortalEmpleadoController::guardarReembolso` (subconjunto; solicitante/numero de la sesión). Monto texto "$1,250" → decimal, campo `origen` admin/empleado.
  - Lectura: `AdminPanelController::panel` (2 conteos), `reembolsos` (listado), `reembolsosExcel` (export CSV), `PortalEmpleadoController::portal` (filtro por `numero_empleado` ahora con SQL, antes era ->filter en PHP).
  - Update: `autorizarReembolso` ahora recibe `Reembolso $reembolso` (model binding) y setea `autorizado`/`autorizado_fecha`; ruta cambiada de `{alerta}` a `{reembolso}`.
  - Vistas `admin/reembolsos.blade.php` y `empleados/portal.blade.php` leen columnas (`$r->campo`) en vez de `$r->datos['campo']`.
- **Migración de datos:** comando `php artisan reembolsos:migrar` (idempotente, NO borra originales). En local había 0; si en PRODUCCIÓN hay reembolsos, correr tras desplegar.
- **OJO fecha_factura:** se mantiene el comportamiento previo (siempre guarda la fecha de hoy, ignora el input del form). Si dirección quiere la fecha real de la factura, es un cambio aparte.
- **Verificado:** 4 archivos PHP pasan `php -l`, vistas compilan, rutas registradas, prueba e2e (alta/conteo/autorizar/filtro) OK.

## 2026-09-30 — Exportar Excel en listado de Empleados
- **Qué:** botón "Exportar Excel" en el listado de empleados (admin). Genera un CSV (abre en Excel) respetando los filtros activos de búsqueda y banco, con columnas: Nº empleado, nombre, departamento, ruta/gasolina, nº cuenta, titular, banco, estatus.
- **Por qué:** dirección pidió poder descargar la lista. Mismo patrón que el export de la bitácora de gasolina (CSV con BOM UTF-8 para acentos).
- **Dónde:** `PortalEmpleadoController::adminExcel` + helper `filtrarEmpleados()` (reutilizado por `adminIndex` y el export para que ambos devuelvan lo mismo), ruta `admin.empleados.excel` en `routes/web.php` (GET, declarada antes de las rutas con `{empleado}`), botón en `admin/empleados/index.blade.php`.
- **Verificado:** `php -l` OK, ruta registrada, prueba e2e del CSV con acentos OK.

## 2026-09-30 — Bitácora de gasolina con tabla propia (antes vivía en alertas/JSON)
- **Qué:** la bitácora de gasolina dejó de guardarse en la tabla genérica `alertas` (tipo `bitacora_gasolina`, todo en JSON `datos`) y ahora tiene su propia tabla `bitacoras_gasolina` con columnas reales (fecha, numero_empleado, empleado, cantidad_litros, rendimiento, monto, vehiculo, kilometraje, notas, factura, origen).
- **Por qué:** con JSON los filtros/sumas se hacían recorriendo en PHP y no escalaba. Con columnas reales se usan WHERE/SUM de SQL. Dirección pidió que cada registro se guarde en su propia tabla.
- **Dónde:**
  - Migración `2026_09_30_create_bitacoras_gasolina_table.php`, modelo `App\Models\BitacoraGasolina`.
  - Escritura: `AdminPanelController::bitacoraGasolinaGuardar` y `PortalEmpleadoController::guardarGasolina` (monto texto "$1,500" → decimal; campo `origen` admin/empleado).
  - Lectura/filtros: `AdminPanelController::bitacoraGasolina` y `bitacoraGasolinaExcel` (filtro con LIKE SQL), `PortalEmpleadoController::portal`.
  - Validación de reembolsos (que exige bitácora previa): 2 checks en `AdminPanelController::enviarReembolso` y 1 en `PortalEmpleadoController::guardarReembolso` + el `$tieneBitacoraGasolina` del listado de reembolsos, ahora consultan `BitacoraGasolina`.
  - Vistas `admin/bitacora-gasolina.blade.php` y `empleados/portal.blade.php` leen columnas (`$r->campo`) en vez de `$r->datos['campo']`.
- **Migración de datos:** comando `php artisan bitacora:migrar` copia lo viejo de `alertas` a la tabla nueva SIN borrar los originales (idempotente). En local había 0 registros viejos; si en PRODUCCIÓN hay registros, correr el comando allá tras desplegar.
- **Verificado:** los 4 archivos PHP pasan `php -l`; prueba e2e de alta/lectura/SUM/filtro OK.

## 2026-09-30 — Fix 500 al dar de alta empleado sin número (columna NOT NULL)
- **Qué:** el alta de empleado sin número tronaba con error 500. Causa: la columna `numero_empleado` se creó NOT NULL en la migración original (`2026_09_03`), pero al hacer el número opcional guardamos NULL cuando viene vacío → "Column 'numero_empleado' cannot be null". Se agregó migración que vuelve la columna `nullable()`.
- **Por qué:** NULL (no '') es lo correcto para que el índice UNIQUE permita varios empleados sin número; ya se verificó que se pueden crear varios sin chocar.
- **Dónde:** migración `2026_09_30_make_numero_empleado_nullable.php` (`->nullable()->change()`, nativo en Laravel 12, sin doctrine/dbal).

## 2026-09-30 — Banco de la tarjeta (INNTEC/BBVA) en empleados + filtro
- **Qué:** nuevo campo "Banco de la tarjeta" con opciones INNTEC / BBVA en el alta y edición de empleados, columna "Banco" en el listado, y un filtro por banco en el buscador.
- **Por qué:** dirección maneja tarjetas de dos bancos (INNTEC y BBVA) y quiere poder separar/filtrar a los empleados según de dónde salga su tarjeta.
- **Dónde:** migración `2026_09_30_add_banco_tarjeta_to_empleados_table.php` (columna `banco_tarjeta`), modelo `Empleado` (`$fillable`), `PortalEmpleadoController::adminIndex` (filtro `banco`), `adminGuardar/adminActualizar` (validación `in:INNTEC,BBVA` + guardado, vacío→null), vista `admin/empleados/index.blade.php` (select en alta, en modal de edición y en filtro).
- **PENDIENTE:** correr `php artisan migrate` cuando MySQL esté prendido (al hacer el cambio la BD local estaba apagada, conexión rechazada en 127.0.0.1:3306).

## 2026-09-30 — Número de empleado opcional + edición de empleados
- **Qué:** el "Número de empleado" en el alta ya NO es obligatorio (era `required`). Además se agregó un botón "Editar" en cada fila del listado que abre un modal para modificar todos los datos del empleado (número, nombre, departamento, correo, cuenta, titular, checkbox ruta/gasolina).
- **Por qué:** dirección pidió poder registrar empleados sin número y asignárselo/corregirlo después. El backend de edición (`adminActualizar` + ruta PUT) ya existía, faltaba solo el frontend.
- **Cómo se resolvió el UNIQUE:** la columna `numero_empleado` es UNIQUE; MySQL permite varios NULL pero NO varias cadenas vacías. Por eso se agregó el helper `limpiarNumeroEmpleado()` que convierte vacío → NULL, así conviven varios empleados sin número.
- **Dónde:** `PortalEmpleadoController::adminGuardar/adminActualizar` + helper `limpiarNumeroEmpleado`; vista `resources/views/admin/empleados/index.blade.php` (input sin `required`, botón Editar con `data-*`, modal `#modalEditar` y JS en `@push('scripts')`).

## 2026-09-26 — Detalle de proveedor: la tabla de Wiese ES el formulario de pago
- **Qué:** En `admin/pagos/proveedor.blade.php` la tabla "Facturas pendientes en Wiese" pasó de solo lectura a ser el formulario de pago. Ahora tiene checkbox por fila (`name="folios[]"` con el folio de Wiese como value), un "seleccionar todas" (`chkAll`), barra con contador (`selCount`) y botón "Pagar seleccionadas" (`btnConfirmar`, arranca disabled). Se eliminó por completo la tabla vieja de facturas LOCALES (la que usaba `$facturas`, `factura_ids[]`, columnas Flete/Régimen/Docs, etc.).
- **Por qué:** el controlador `proveedor()` ya no pasa `$facturas`/`$idsFacturasNoVistas`/`$monto`; ahora `store()` recibe los `folios[]` seleccionados y materializa esas facturas en local solo. La vista debía dejar de depender de datos locales y pagar directo sobre lo que está EN VIVO en Wiese.
- **Dónde:** `resources/views/admin/pagos/proveedor.blade.php` — form `#formPagarLote` sobre `$facturasWiese`; el `.adm-summary` ahora calcula conteo y saldo desde `$facturasWiese->sum('saldo')`; el JS del modal de anticipos se adaptó para leer folio y saldo (`.monto-saldo`) desde la nueva tabla.
- **Anticipos:** el modal `#modal-anticipos` y su JS se conservaron intactos; se mantuvieron los ids/clases (`fact-chk`, `selCount`, `btnConfirmar`, `chkAll`) para no romperlo.
- **Verificado:** `php artisan view:clear` + `Blade::compileString` → OK; grep confirma que no quedan referencias a `$facturas`, `$idsFacturasNoVistas`, `$monto` ni `factura_ids`.

## 2026-09-26 — Pago EN VIVO desde Wiese (adiós importación al abrir; se materializa solo lo seleccionado)
- **Qué:** Se eliminó la importación automática de TODAS las facturas al abrir el proveedor (causaba timeout con ORPACK y duplicaba la vista). Ahora: al abrir, se LEEN las facturas en vivo de Wiese (sección única con checkboxes). Al PAGAR, se materializan en local SOLO las facturas seleccionadas (2-3), rápido, y con esos IDs se crea el lote. `crearLote`/`confirmar` quedan intactos.
- **Por qué:** (1) ORPACK tronaba con "Maximum execution time 30s" al importar cientos de facturas. (2) La vista mostraba las facturas duplicadas (tabla Wiese en vivo + tabla local importada). (3) Said: la BD local se desactualiza; leer en vivo evita el desfase.
- **Cómo:** `proveedor()` ya no importa (solo lee `$facturasWiese` en vivo, pasa `proveedor/codigo/expediente/facturasWiese/wieseError/rfc`). El form de pago está sobre la tabla de Wiese, manda `folios[]`. `store()` valida `folios` y llama a `materializarFacturasSeleccionadas($codigo,$folios)` (nuevo) que baja de Wiese SOLO las seleccionadas, las guarda en local con datos fiscales por defecto, y devuelve sus IDs → `crearLote`.
- **Quitado:** método `importarFacturasWiese` + ruta `admin.pagos.importar-facturas` + botón. La vista ya no tiene tabla local ni `factura_ids[]`. El modal de anticipos se conservó.
- **Dónde:** `app/Http/Controllers/AdminPagosController.php` (`proveedor()`, `store()`, nuevo `materializarFacturasSeleccionadas()`, se quitó `importarFacturasWiese()`); `routes/web.php` (ruta quitada); `resources/views/admin/pagos/proveedor.blade.php` (tabla Wiese = form de pago con checkboxes `folios[]`, se quitó tabla local).
- **Nota:** el comando `wiese:importar-facturas` y la precarga quedan como código pero ya no son el camino. `estadoCuenta()` sigue usando su propia $facturas local (correcto, no se tocó).
- **Verificado:** `php -l` limpio en controlador y rutas, Blade compila, sin referencias muertas a $facturas/factura_ids/idsFacturasNoVistas en el flujo de pago.

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
