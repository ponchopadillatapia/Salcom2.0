# Contexto de las APIs — Salcom 2.0

> Documento de traspaso para que cualquier chat de Kiro entienda a fondo el módulo de APIs
> sin tener que redescubrirlo. Basado en lectura directa del código (no suposiciones).
> Stack: Laravel (PHP). Base de datos: SQLite (se usa `strftime` en `analisis()`).

---

## Mapa rápido

Todo vive en `app/Http/Controllers/APIS/` y se enruta desde `routes/api.php`.

| Bloque | Controlador | Auth | Propósito |
|--------|-------------|------|-----------|
| Validación fiscal | `EmpresaApiController` | Sesión web proveedor (`web` + `auth.proveedor`) | Recibe PDFs fiscales, OCR y valida documento por documento |
| API de datos Salcom | `SalcomApiController` | Bearer token (`auth.api_token`) | Consulta de datos del negocio (la que consume el MCP de Kiro) |
| Código postal | Closure en `routes/api.php` | Ninguna | Devuelve estado/municipio/ciudad/colonias de un CP mexicano |

---

## 1. EmpresaApiController — Validación fiscal (OCR)

Archivo: `app/Http/Controllers/APIS/EmpresaApiController.php` (~3300 líneas).

- **Ruta:** `POST /empresa` → `validar(Request)` — nombre de ruta `proveedores.validacion-fiscal.api`.
- **Auth:** middleware `web` + `auth.proveedor`. Usa `session('proveedor_id')`, NO token.
- **Entrada:** PDFs vía multipart (máx 20MB c/u). Campos principales:
  - `cif_pdf` (requerido), `opinion_pdf` (requerido), `caratula_banco_pdf` (requerido)
  - `formato_identificacion_pdf` — requerido solo para altas nuevas que NO sean REPSE; opcional para proveedores ya activos o REPSE
  - `acta_pdf`, `rep_legal_pdf`, `contribuyente_pdf`, `poder_pdf` — opcionales
  - Paquete REPSE: `repse_registro_pdf`, `repse_isr_retenido_pdf`, `repse_iva_pdf`, `repse_opinion_sat_pdf`, `repse_opinion_infonavit_pdf`, `repse_opinion_imss_pdf`, `repse_pago_imss_infonavit_pdf`, `repse_cedula_imss_pdf`, `repse_cedula_obrero_patronal_pdf`, `repse_sipare_pdf`, `repse_sua_pdf`, `repse_cfdi_nomina_pdf`, `repse_acuse_padron_pdf` (todos opcionales)
  - `tipo_persona` = `moral` | `fisica`
  - `es_repse` = `1`/`si`, `repse_en_padron` = `si`/`no`

### Reglas de negocio importantes
- Si el proveedor NO está activo, su onboarding está bloqueado y ya tiene los documentos fiscales completos → responde **423** ("expediente en revisión o aprobado"). Los activos SÍ pueden renovar/actualizar.
- Persona moral: acta constitutiva obligatoria salvo que suba poder notarial.
- Los documentos REPSE se guardan pero no tienen validación automática estricta (se extrae texto y se valida vencimiento/RFC).

### Cómo funciona internamente
1. Guarda cada PDF en disco `local` (`storage/app/private/<carpeta>/`).
2. Extrae texto con `extraerTexto()`: primero `Smalot\PdfParser`, y si el PDF es escaneado cae a **OCR**.
3. OCR con doble estrategia: **Tesseract** local (`thiagoalessio\TesseractOCR`) y **AWS Textract** (`Aws\Textract\TextractClient`), con reconstrucción de imagen vía Imagick/GD como respaldo.
4. Valida cada tipo con métodos privados dedicados:
   - `validarCIF`, `validarOpinion`, `validarActa`, `validarPoder`, `validarFormatoIdentificacion`, `validarINE`, `validarCaratulaBanco`, `validarRepse`
   - Helpers: `extraerRfcDeTexto`, `extraerNombreCifPersonaFisica`, `extraerNombreIne`, `normalizarNombre`, `nombresCoinciden`, `validarRFC`, `documentoRepseVencido`, `limpiarTextoOcr`, `separarPalabrasPegadas`
5. Compara RFC y nombre extraídos contra los datos del proveedor para confirmar coincidencia.

### Servicios que usa
`IaService`, `AlertEngineService`, `DocumentCrossCheckService`, `CaratulaBancariaValidationService`.
Modelos: `ProveedorUser`, `DocumentoProveedor`, `SolicitudAlta`, `SolicitudModificacionDatos`, `Alerta`, `AuditLog`, `AdminUser`.

> ⚠️ Nota de deuda técnica: la lógica de validación fiscal está **duplicada** en dos lugares.
> `EmpresaApiController` tiene su propia implementación embebida, mientras que
> `SalcomApiController::validarDocumento` delega en `App\Services\DocumentValidationService`.
> Si se cambia una regla de validación hay que revisar AMBOS caminos.

---

## 2. SalcomApiController — API de datos del negocio

Archivo: `app/Http/Controllers/APIS/SalcomApiController.php`.

- **Auth:** middleware `auth.api_token` (`App\Http\Middleware\ApiTokenAuth`).
  - Lee `Authorization: Bearer <token>` y lo compara contra `config('services.salcom_api.token')`.
  - Ese valor viene de `SALCOM_API_TOKEN` en `.env` (ver `config/services.php`).
  - Si no hay token configurado o no coincide → **401** `{"error":"No autorizado"}`.
- **Prefijo:** todas las rutas cuelgan de `/salcom`.
- Respuesta típica de listas: `{ "total": N, "data": [...] }`.

### Endpoints

| Método | Ruta | Función | Filtros (query) |
|--------|------|---------|-----------------|
| GET | `/salcom/resumen` | `resumen` | — |
| GET | `/salcom/analisis` | `analisis` | — |
| GET | `/salcom/clientes` | `clientes` | `busqueda`, `activo`, `limit` (def 50) |
| GET | `/salcom/clientes/{cliente}` | `clienteDetalle` | route model binding |
| GET | `/salcom/proveedores` | `proveedores` | `busqueda`, `limit` |
| GET | `/salcom/proveedores/{proveedor}` | `proveedorDetalle` | route model binding |
| GET | `/salcom/pedidos` | `pedidos` | `estatus`, `cliente`, `limit` |
| GET | `/salcom/pedidos/{pedido}` | `pedidoDetalle` | route model binding |
| GET | `/salcom/productos` | `productos` | `busqueda`, `activo`, `sin_stock`, `limit` |
| GET | `/salcom/productos/{producto}` | `productoDetalle` | route model binding |
| GET | `/salcom/facturas` | `facturas` | `estatus`, `cliente`, `proveedor`, `vencidas`, `limit` |
| GET | `/salcom/muestras` | `muestras` | `etapa`, `proveedor`, `limit` |
| GET | `/salcom/encuestas` | `encuestas` | `cliente`, `limit` |
| GET | `/salcom/documentos` | `documentos` | `estatus`, `proveedor_id`, `tipo`, `limit` |
| GET | `/salcom/documentos/{documento}/validar` | `validarDocumento` | corre OCR + validación |
| PATCH | `/salcom/documentos/{documento}/revisar` | `revisarDocumento` | body: `estatus` (aprobado/rechazado), `notas` |

### Detalles de comportamiento
- `resumen`: totales agregados de clientes, proveedores, pedidos (por estatus + monto), encuestas (prom. calificación), productos (activos/sin stock), facturas (pendientes + monto), muestras (por etapa), documentos (por estatus).
- `clienteDetalle`: cliente + sus últimos 20 pedidos, 10 encuestas, 10 facturas. Oculta `password` y `remember_token`.
- `proveedorDetalle`: proveedor + documentos (agrupados por estatus), muestras (match por nombre `like`), facturas por `codigo_proveedor`.
- `productoDetalle`: busca pedidos que contengan el producto dentro del campo JSON `productos` con `like` por código o nombre.
- `validarDocumento`: lee el archivo en `storage/app/private/<archivo>`, y según `documento->tipo` llama a `DocumentValidationService` (`validarCIF`, `validarOpinion`, `validarActa`, `validarINE` para rep_legal/contribuyente, `validarCaratulaBanco`). Guarda `resultado_validacion`.
- `revisarDocumento`: valida `estatus in:aprobado,rechazado` y `notas` (máx 2000). Setea `revisado_at = now()`.
- `analisis`: pedidos por mes (últimos 6, usa `strftime` → **SQLite**), top 10 clientes por monto, productos con stock ≤ 10, facturas vencidas, muestras en proceso.

Modelos usados: `ClienteUser`, `ProveedorUser`, `Pedido`, `Producto`, `Factura`, `Muestra`, `Encuesta`, `DocumentoProveedor`, `TrackingPedido`.

---

## 3. API de Código Postal

- **Ruta:** `GET /codigo-postal/{cp}` (closure en `routes/api.php`, sin auth).
- Limpia el CP a 5 dígitos; si no, **400**.
- Estrategia en cascada:
  1. API SEPOMEX (`api-sepomex.hckdrk.mx`, timeout 3s) → estado, municipio, ciudad, colonias.
  2. Zippopotam MX (`api.zippopotam.us/MX`, timeout 4s) + mapeo local de municipio.
  3. Mapeo local por rangos de CP (closure `municipioPorCP`, cubre Jalisco a detalle + CDMX, EdoMex, NL, Querétaro, Ags, Gto).
- Si nada responde y no hay dato local → **404**.

---

## Configuración / entorno

- Token de API Salcom: `SALCOM_API_TOKEN` en `.env` → `config/services.php` (`services.salcom_api.token`).
- OCR requiere Tesseract instalado localmente y/o credenciales de AWS Textract; Imagick para rasterizar PDFs escaneados.
- Archivos de proveedores se guardan en el disco `local` = `storage/app/private/`.

## Dónde empezar según la tarea

- **Tocar validación de un documento fiscal en el alta del proveedor** → `EmpresaApiController::validar` y el método `validarXxx` correspondiente.
- **Tocar validación desde el panel admin / MCP** → `SalcomApiController::validarDocumento` + `DocumentValidationService`.
- **Agregar un dato/consulta nueva al negocio** → nuevo método en `SalcomApiController` + ruta en el grupo `/salcom`.
- **Regla de auth de la API de datos** → `ApiTokenAuth` middleware.

---

## 4. API de Wiese (sistema contable externo) — ProveedorApiService

> Esta API NO vive en `app/Http/Controllers/APIS/`, sino en `app/Services/ProveedorApiService.php`.
> Es la que conecta el portal con el sistema contable Wiese (proveedores, órdenes de compra, RFC).
> Datos aportados por el usuario (notas de "Said" / info de Alan).

### Acceso a la API (Swagger)
- **URL Swagger:** `http://172.16.1.250:7186/swagger/index.html`
- **Credenciales de servicio (usuario `web`):**
  - código: `web`
  - pwd: `salcomweb1234`
- Login: en Swagger buscar (Ctrl+F) `login`.
- Config en `.env`: `PROVEEDOR_API_URL` y `PROVEEDOR_API_DOCS_URL` → `config/services.php` (`services.proveedor_api.*`).

### Cómo se une un proveedor del portal con Wiese
- El **código Wiese** del proveedor se captura A MANO en la columna `codigo` de `proveedores_users`.
- Ese código lo proporciona Alan (ej. `M213015002`, `213001001`, `213015002`).
- Sin ese código, el portal genera un código PROVISIONAL `'P'.$proveedor->id` (ej. `P55`) para que las facturas no queden sin dueño. Ver `PortalProveedorController` (~línea 2002).

### Endpoint: ListaDocumentosOCPorProveedorFechas
- En Swagger buscar (Ctrl+F) `ListaDocumentosOCPorProveedorFechas`.
- Recibe: código de proveedor Wiese + rango de fechas.
- Ejemplo de código de proveedor: `M213015002`.
- Devuelve las órdenes de compra / documentos del proveedor. Campos del response:

| Campo | Qué es | Ejemplo |
|-------|--------|---------|
| `cseriedocumento` | Serie | ALMP |
| `cfolio` | Número de folio (INTERNO Wiese) | 91773 |
| `cfecha` | Fecha de la factura | 2026-07-28T00:00:00 |
| `crazonsocial` | Razón social del proveedor | ORPACK DE MEXICO |
| `crfc` | RFC | OME0207015E8 |
| `ctotal` | Monto total | 8237.68 |
| `cpendiente` | Monto pendiente por pagar | 8237.682 |
| `ccancelado` | Si fue cancelada (0/1) | 0 |
| `cfechavencimiento` | Fecha de vencimiento | 2026-08-27T00:00:00 |
| `cfechaentregarecepcion` | Fecha entrega/recepción | 2026-07-28T00:00:00 |
| `creferencia` | Referencia (texto libre) | — |
| `cobservaciones` | Observaciones | null o texto |
| `cidmoneda` | Moneda (1=MXN) | 1 |
| `ctipocambio` | Tipo de cambio | 1 |
| `ctotalunidades` | Total unidades | 1935 |
| `cunidadespendientes` | Unidades pendientes | 1935 |
| `cusuario` | Usuario que registró | 625 |
| `ciddocumento` | ID interno del documento | 2366728 |
| `ciddocumentoorigen` | ID del documento origen | — |

> ⚠️ `cfolio` es el folio INTERNO de Wiese. Para mostrar al usuario conviene el folio de la
> factura original, no este. (Pendiente definir de dónde sale el folio original.)

### Regla de ESTATUS para KPIs (indicada por Alan) — YA IMPLEMENTADA
En `PortalProveedorController` (~líneas 388-411). Orden de evaluación:
1. `ccancelado == 1` → `cancelada` (NO se muestra / no cuenta como pendiente ni pagada).
2. `cpendiente > 0` → `pendiente` (tiene saldo).
3. en otro caso → `pagada`.

KPIs: `pendientes`, `pagadas`, `canceladas`, `totales`. Filtro opcional por query `wiese_estatus`.
Se muestran las primeras 100 (`take(100)`).

---

## 5. API RFC — BuscarClienteProveedorPorRFC

- Endpoint en Swagger: `BuscarClienteProveedorPorRFC`.
- Busca un cliente/proveedor por su RFC en el sistema Wiese.
- Ejemplo de código relacionado: `213001001`.
- Relacionado con `ClienteApiService` (`services.cliente_api.url`) y/o `ProveedorApiService`.
- Nota: `SatRfcService` (`services.sat.*`) es DISTINTO — ese valida el RFC ante el SAT (lista 69-B), no contra Wiese.

---

## 6. Órdenes de Compra de Wiese — RESUELTO (20-sep-2026)

> Historial: la API Wiese cambió respecto a las notas viejas. El endpoint con "Fechas"
> ya no existe. Tras pruebas en Swagger + tinker se encontró el endpoint y parámetros reales.

### Endpoint correcto
```
GET /api/Documento/ListaDocumentosOCPorProveedor
```
Requiere token Bearer (login de servicio web/salcomweb1234). Los 3 parámetros son OBLIGATORIOS:
- `codigoProveedor`: código Wiese del proveedor (ej. ORPACK = `M213015002`).
- `strIdConceptosOC`: ID del concepto de OC. **NO acepta 0 como "todos"** (devuelve []).
  Hay que pedir cada concepto. IDs de conceptos de Orden de Compra (de `/api/Concepto/Listar`):
  - `19`   = Orden de Compra M.P. (materia prima)  ← ORPACK usa este
  - `2004` = Orden de Compra P.T. (producto terminado)
  - `3015` = Orden de Compra Importación
  - `3151` = Orden de Compra Mantenimiento
  - `3130` = Orden de Compra EPP
- `fecha`: fecha de corte válida. SQL Server rechaza fechas vacías/año 0 (error "SqlDateTime overflow").
  Usar algo como `2020-01-01`.

### Solución implementada
`ProveedorApiService::listarDocumentosOCPorProveedorFechas()` ahora itera sobre TODOS los
conceptos de OC (`19, 2004, 3015, 3151, 3130`) y junta los resultados, para que funcione
con cualquier proveedor. Confirmado: ORPACK (`M213015002`, concepto 19) devuelve 6,329 OC reales.

### Detalle importante de códigos de proveedor (ORPACK)
- `BuscarPorCodigo` con `M213015002` → registro 936, ACTIVO (cestatus=1), con datos bancarios.
- `BuscarPorRFC` con `OME0207015E8` → registro 2510, código `103015031`, INACTIVO (cestatus=0).
- Las OC están ligadas al código `M213015002` (el activo), NO al `103015031`.

### Nuevo método útil (real, no simulado)
`ProveedorApiService::buscarProveedorWiese($valor, $porRfc)` → consulta real a
`/ClienteProveedor/BuscarPorCodigo` o `/BuscarPorRFC`. Reemplaza la simulación de
`buscarProveedorPorRFC` cuando se quiera usar datos reales.

### Pantalla que ya lo usa
`/admin/proveedores/{codigo}/facturas` (`AdminPanelController::proveedorFacturas`,
vista `admin.proveedor-facturas`) tiene la sección "Órdenes de compra (Wiese)" con filtro
de fechas. Poner "Desde" en 2020-01-01 para ver histórico.

### ⚠️ RED / PRODUCCIÓN
La API Wiese vive en IP INTERNA `172.16.1.250:7186` → solo se alcanza con VPN de la empresa.
El servidor de producción (SiteGround) NO está en esa VPN (curl a esa IP da timeout).
Por tanto, las consultas a Wiese solo funcionan desde local con VPN, o requieren que
producción tenga acceso a la red interna (VPN site-to-site / whitelist) — pendiente de infraestructura.
