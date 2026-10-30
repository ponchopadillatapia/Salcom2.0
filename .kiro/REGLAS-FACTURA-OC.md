# Reglas del cruce Factura ↔ OC — Fases

> Validación de facturas de proveedor contra su Orden de Compra (OC) en Wiese.
> Fase 1 YA implementada. Fase 2 pendiente de confirmar reglas con Brenda/contabilidad.

## Cómo se liga la factura con su OC
El XML del CFDI **NO** trae el número de OC. Por eso el proveedor **escribe el número de OC**
(ej. 81686) al subir la factura. Con ese folio + el código del proveedor, el sistema busca la OC
en Wiese (API `ListarDocumentosCodigo`, CIDDOCUMENTODE=17) y saca sus movimientos (cantidades).

Datos clave del movimiento de la OC (Wiese):
- `cunidades` = cantidad pedida.
- `cunidadespendientes` = lo que falta por recibir.
- Recibido en Acatlán = `cunidades − cunidadespendientes`.

---

## ✅ FASE 1 (IMPLEMENTADA) — caso simple
- El proveedor sube PDF + XML + OC (PDF) + **número de OC** (obligatorio).
- El sistema valida lo fiscal (RFC, UUID, totales, régimen, forma/método/uso G01/G03, PPD→99/PUE→03).
- Cruce de cantidad: suma la Cantidad de los conceptos del XML vs la cantidad pedida en la OC.
  - Factura cobra **MÁS** que la OC → **se rechaza**.
  - Factura cobra **igual o menos** → pasa (puede ser entrega parcial).
- Si no se encuentra la OC por ese folio → error (verificar número).
- Archivos: `app/private/facturas-proveedor/{id}`. Datos: tabla `facturas` (incluye `validacion_detalle.oc_cruce_detalle`).

Dónde: `AltaFacturaValidationService::cruzarCantidadConOc()` + `ProveedorApiService::buscarOcPorFolio()`.

### Desempate cuando hay varias OC con el mismo folio (agregado 2026-09-07)
El folio NO es único en Wiese: pueden existir varias OC con el mismo folio y distinta **serie**
(ej. 81686 serie '' = 7200 pzas, y 81686 serie '-A' = 8754 pzas). Para elegir la correcta usamos el
**total de la factura**: entre las OC del mismo folio, se queda con la de total más parecido.
Se guarda `serie_oc` y `candidatas` en `oc_cruce_detalle`.
⚠️ Esto NO resuelve parcialidades (donde el total parcial no cuadra con la OC) — eso es Fase 2 y
ahí se usará la serie/prefijo.

---

## ⏳ FASE 2 — REGLAS CONFIRMADAS CON BRENDA (2026-09-09) — pendiente IMPLEMENTAR

> Brenda ya respondió. Estas son las reglas de negocio reales. Aún NO están implementadas en código.

### 1. Entrega de MÁS producto
- NO se rechaza como en Fase 1. Lo que pasa en la vida real: **se MODIFICA la OC** (ej. de 1 tonelada
  a 1.46). Ese ajuste lo hace compras directo en Wiese; NO genera PDF ni correo, es solo a nivel sistema.
- IMPLICACIÓN para nosotros: como la OC en Wiese ya queda ajustada ANTES de que llegue la factura, el
  cruce de Fase 1 (factura ≤ OC) debería cuadrar solo. O sea, puede que NO haya que hacer nada especial:
  si Wiese ya trae la OC ampliada, la factura mayor ya no excede. **A confirmar al implementar.**

### 2. Entregas en PARCIALIDADES — prefijo de letra en el folio — ✅ IMPLEMENTADO (2026-09-09)
- El proveedor entrega por partes. El **folio de la OC madre** (ej. 3456) lleva un **prefijo
  consecutivo** en cada parcial: `3456`, `A3456`, `B3456`, `C3456`… (A, B, C… consecutivo). El folio
  NO cambia; solo se le antepone la letra. Todas apuntan a la MISMA OC madre.
- REGLA (confirmada con Brenda): **toda factura (parcial o completa) solo puede cobrar hasta lo que
  Acatlán YA registró como RECIBIDO en Wiese** (`cunidades − cunidadespendientes`). No se paga lo que
  no ha llegado. Sabemos que llegó porque Acatlán ya lo registró (bajó `cunidadespendientes`).
- 3 resultados: factura ≤ recibido → OK; factura > recibido → rechaza; recibido = 0 → revisión
  (esperando que almacén registre la recepción, no es error).
- El prefijo A/B/C ya se normaliza en `buscarOcPorFolio` (soloDigitos), así que A3456 = 3456 = la misma OC.
- Dónde: `AltaFacturaValidationService::cruzarCantidadConOc()` (`oc_cruce_detalle['estado']`:
  ok / excede_recibido / esperando_recepcion).

### 3. NOTAS DE CRÉDITO (NC) — ✅ DETECCIÓN/LIGADO IMPLEMENTADO (2026-09-09)
> IMPLEMENTADO: aceptar NC (tipo E), validar sus reglas fiscales (G02/PUE/forma 15 o 30), clasificar
> subtipo y ligar por UUID (CFDI relacionados). NO se calcula el descuento al saldo (eso es contabilidad).
> `clasificacion_nc`: devolucion (forma 15) / aplicacion_anticipo (forma 30) / generica.
> Sin UUID relacionado → revisión (no bloquea). Dónde: `validarNotaCredito()`.

El proveedor la liga en su XML (CFDI Relacionados). Hay 3 tipos:

**3a. Descuento por PRONTO PAGO**
- Flujo: se genera la OC → factura 1 → después hacen la NC → la NC se descuenta de la factura
  relacionada. La factura a pagar baja por el monto de la NC.

**3b. DEVOLUCIÓN de material**
- Validar que la NC sea: **UsoCFDI G02** (devoluciones/descuentos/bonificaciones), **método PUE**,
  **forma de pago 15**.
- La NC se descuenta de la factura ligada (viene en "CFDI Relacionados" del XML). Devuelven el dinero.
- Operativa real: el proveedor PREGUNTA (por correo) a qué factura aplicar la NC, y contabilidad lo
  confirma/checa. O sea hay un paso humano de correo.

**3c. NC que "MATA" una factura de ANTICIPO** (caso DIMOSA de las imágenes)
- Flujo real (ejemplo DIMOSA, equipo apilador Raymond):
  1. Se genera la OC (ej. OC 81313, apilador, total ~$24,934 USD).
  2. El proveedor hace la **factura 1 = ANTICIPO** (UsoCFDI **G03** gastos en general, un % del total,
     ej. 30%). Esta factura 1 lleva la OC.
  3. Pasan ~40 días (tiempo de fabricación).
  4. El proveedor emite una **NOTA DE CRÉDITO que MATA la factura 1** (anticipo). La NC: UsoCFDI
     **G02**, método **PUE**, forma de pago **30** (aplicación de anticipo). Tipo relación 07.
  5. Inmediatamente emite la **factura 3 = factura final ("la chida")** por el total, que SÍ lleva la OC.
- Tiempos: factura 1 en abril; NC + factura 3 el MISMO día ~40 días después (primero la NC, luego la 3).
- Cómo se ligan: la OC la comparten la factura 1 y la factura 3. La NC se liga por **UUID** (CFDI
  relacionados) a la factura de anticipo. Si una factura no tiene OC, se liga por UUID.
- Validaciones de la NC de anticipo: UsoCFDI **G02** siempre, método **PUE**, forma de pago **30**.
  (El anticipo original = UsoCFDI G03 "gastos en general".)

- ❓ PENDIENTE (Brenda aún no sabe): **cómo afecta exactamente el saldo a pagar** cuando entra una NC.
  Hay que definir la aritmética final con contabilidad.

### 4. ANTICIPOS — ✅ DETECCIÓN IMPLEMENTADA (2026-09-09)
- Dos orígenes: (a) precio fijado en negociación, (b) proveedor nuevo.
- **El anticipo SIEMPRE va ligado a una nota de crédito** (ver 3c). No es un flujo aparte: anticipo →
  después NC que lo mata → después factura final.
- IMPLEMENTADO: se detecta por ClaveProdServ **84111506** (config `facturas.claves_anticipo`). El
  anticipo SÍ lleva OC (no es "servicio sin OC"). Se marca `es_anticipo` + `anticipo_con_oc` y se avisa
  que vendrá su NC (forma 30). Dónde: `facturaEsAnticipo()`. NO se calcula saldo (contabilidad).

### Requisiciones (producto SIN OC) — ACLARADO
- La requisición es como una OC pero INFORMAL: un formato de compras (tabla) donde cada quien anota
  producto, cantidad, unidad, descripción, fecha, departamento, y se FIRMA para autorizar.
- Con eso firmado, se le pide al proveedor de forma informal (WhatsApp/correo) y el proveedor hace la
  factura **SIN OC**.
- Las requisiciones **NUNCA entran a Wiese**. Por eso el camino "producto sin OC → autorización manual"
  (ya implementado) es el correcto y permanente para este caso.
- **Maquinaria/activos SÍ llevan OC** (no van por requisición). Ver caso DIMOSA (apilador con OC 81313).

### 5. Servicios / fletes SIN OC — ✅ IMPLEMENTADO (2026-09-08)
- Fletes y servicios no tienen OC ni entran a almacén (Acatlán). YA NO se les exige OC.
- El sistema clasifica por ClaveProdServ: mercancía (10-69) = producto con OC; servicio (70-99) o
  flete detectado = servicio sin OC (valida solo fiscal). El UsoCFDI NO sirve para distinguir.
- Producto SIN número de OC → NO se rechaza; queda para AUTORIZACIÓN MANUAL (requisición de papel /
  activos que no están en sistema).
- Dónde: `AltaFacturaValidationService::facturaEsServicioSinOc()` + 3 caminos en `validar()`
  (`clasificacion_oc`: servicio_sin_oc / producto_con_oc / producto_sin_oc_revision).

### 5b. Casos de "sin OC" — ACLARADO por Brenda + ✅ confirmación del proveedor (2026-09-09)
- Requisición = OC informal firmada en papel; el proveedor factura SIN OC. NUNCA entra a Wiese.
- IMPLEMENTADO: si una factura de PRODUCTO llega sin número de OC, el proveedor DEBE declarar en el
  formulario:
  - **flete/servicio** → no lleva OC.
  - **remisión** → compra sin OC en sistema; pasa PERO queda `requiere_autorizacion_admin` (estatus
    `pendiente_autorizacion`), NO "validada correctamente". El admin la autoriza a la vista contra la
    remisión firmada.
  - **nada declarado** → ERROR (pide OC o declarar remisión). Cierra el hueco de "sin OC = válida".
- POR QUÉ no se consulta Wiese para esto: sería lento (cientos de OC) y no concluyente. Mejor que el
  proveedor declare (su responsabilidad).
- Maquinaria/activos SÍ llevan OC.

---

## Preguntas que SIGUEN abiertas (para resolver al implementar Fase 2)
- NC: **cómo afecta exactamente el saldo a pagar** (la aritmética final). Brenda aún no lo define.
- Resumen de validaciones por tipo de documento (confirmar al implementar):
  | Documento            | UsoCFDI | Método | Forma pago | Liga por        |
  |----------------------|---------|--------|------------|-----------------|
  | Factura normal       | G01/G03 | PUE/PPD| 03/99      | OC (folio)      |
  | Anticipo (factura 1) | G03     | PUE    | 03         | OC (folio)      |
  | NC devolución        | G02     | PUE    | 15         | UUID (relación) |
  | NC que mata anticipo | G02     | PUE    | 30         | UUID (relación) |
  | Factura final (3)    | G01/G03 | PUE/PPD| 03/99      | OC (folio)      |
