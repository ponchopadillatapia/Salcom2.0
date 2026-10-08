# Bitácora de decisiones — Salcom 2.0

> Memoria externa del proyecto. Cada cambio importante o petición (de Alan, dirección, etc.)
> se anota aquí en pocas líneas: QUÉ se hizo, POR QUÉ, y DÓNDE en el código.
> Se lee de arriba (lo más reciente) hacia abajo.

---

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
