# Requirements Document

## Introduction

Blindar el `ProveedorApiService` del Portal de Proveedores de Industrias Salcom para que sea resiliente ante fallos de la API externa (.NET) desarrollada por Alan Osorio. Actualmente el servicio no tiene manejo de errores, timeouts ni reintentos, lo que causa que el portal truene sin mensajes útiles cuando la API falla. Se necesita un servicio robusto que permita a los controllers distinguir entre tipos de error (API caída, datos no encontrados, error de autenticación) y que reintente automáticamente en fallos transitorios.

## Glossary

- **ProveedorApiService**: Clase PHP en `app/Services/ProveedorApiService.php`, única capa de comunicación entre el portal Laravel y la API externa de Alan.
- **API_Externa**: API REST (.NET) en `http://54.210.85.103:7190/api` que expone endpoints de login, búsqueda y listado de clientes/proveedores.
- **ProveedorController**: Controller Laravel que consume el ProveedorApiService para servir las vistas del portal.
- **ProveedorApiException**: Excepción personalizada que encapsula errores de la API externa con tipo de error, mensaje y código HTTP.
- **ApiResponse**: Estructura de respuesta estandarizada que retorna el ProveedorApiService con campos de éxito, datos, mensaje y tipo de error.
- **Retry_Backoff**: Estrategia de reintento con espera exponencial entre intentos para fallos transitorios (timeouts, errores 5xx).
- **Token_API**: Token JWT retornado por el endpoint de login de la API externa, necesario para autenticar llamadas subsecuentes.
- **OC**: Orden de Compra, documento comercial que lista productos/servicios solicitados a un proveedor.

## Requirements

### Requirement 1: Excepción Personalizada para Errores de API

**User Story:** Como desarrollador del portal, quiero una excepción personalizada para errores de la API externa, para poder distinguir programáticamente entre tipos de fallo (API caída, no encontrado, autenticación fallida).

#### Acceptance Criteria

1. THE ProveedorApiException SHALL incluir las propiedades: tipo de error (string), mensaje descriptivo (string), código HTTP original (int) y datos de respuesta opcionales (array).
2. THE ProveedorApiException SHALL definir los tipos de error: `api_caida`, `timeout`, `autenticacion_fallida`, `no_encontrado`, `error_servidor`, `error_validacion` y `error_desconocido`.
3. THE ProveedorApiException SHALL proporcionar métodos estáticos de fábrica para crear instancias tipadas de cada tipo de error.

### Requirement 2: Respuesta Estandarizada del Servicio

**User Story:** Como desarrollador del portal, quiero que el ProveedorApiService retorne respuestas con estructura consistente, para que los controllers puedan procesar resultados y errores de forma uniforme.

#### Acceptance Criteria

1. THE ProveedorApiService SHALL retornar un array asociativo con las claves: `success` (bool), `data` (array|null), `message` (string) y `error_type` (string|null).
2. WHEN la API externa responde exitosamente, THE ProveedorApiService SHALL retornar `success` como `true`, los datos en `data`, y `error_type` como `null`.
3. WHEN la API externa falla, THE ProveedorApiService SHALL retornar `success` como `false`, `data` como `null`, un `message` descriptivo en español y el `error_type` correspondiente.

### Requirement 3: Timeouts Configurables

**User Story:** Como administrador del portal, quiero que los timeouts de conexión y respuesta sean configurables vía `.env`, para poder ajustarlos sin modificar código.

#### Acceptance Criteria

1. THE ProveedorApiService SHALL leer los valores de timeout de conexión y timeout de respuesta desde `config/services.php` con claves `proveedor_api.connect_timeout` y `proveedor_api.timeout`.
2. THE ProveedorApiService SHALL usar un timeout de conexión por defecto de 5 segundos y un timeout de respuesta por defecto de 15 segundos cuando las variables de entorno no estén definidas.
3. THE ProveedorApiService SHALL aplicar los timeouts configurados a todas las llamadas HTTP hacia la API externa.

### Requirement 4: Retry con Backoff Exponencial

**User Story:** Como desarrollador del portal, quiero que las llamadas a la API se reintenten automáticamente en fallos transitorios, para que errores temporales de red o del servidor no afecten la experiencia del usuario.

#### Acceptance Criteria

1. WHEN la API externa responde con código HTTP 500, 502, 503 o 504, THE ProveedorApiService SHALL reintentar la llamada hasta un máximo configurable de veces (por defecto 3 intentos).
2. WHEN la API externa no responde por timeout, THE ProveedorApiService SHALL reintentar la llamada con la misma política de reintentos.
3. THE ProveedorApiService SHALL aplicar un backoff exponencial entre reintentos con base de 100 milisegundos (100ms, 200ms, 400ms).
4. WHEN todos los reintentos se agotan, THE ProveedorApiService SHALL retornar una respuesta de error con tipo `api_caida` y un mensaje indicando que la API no está disponible.
5. THE ProveedorApiService SHALL registrar en el log cada intento fallido con el número de intento, el endpoint y el código de error.

### Requirement 5: Logging de Errores y Llamadas

**User Story:** Como administrador del portal, quiero que todas las llamadas fallidas a la API se registren en los logs de Laravel, para poder diagnosticar problemas de conectividad y rendimiento.

#### Acceptance Criteria

1. WHEN una llamada a la API externa falla, THE ProveedorApiService SHALL registrar un log de nivel `error` con: endpoint llamado, método HTTP, código de respuesta, mensaje de error y timestamp.
2. WHEN una llamada a la API externa tiene éxito después de reintentos, THE ProveedorApiService SHALL registrar un log de nivel `warning` indicando el número de reintentos necesarios.
3. THE ProveedorApiService SHALL usar el canal de log configurado en Laravel sin crear canales adicionales.

### Requirement 6: Método de Login contra API Externa

**User Story:** Como proveedor, quiero autenticarme contra la API de Alan para obtener un token que permita consultar mis órdenes de compra.

#### Acceptance Criteria

1. WHEN se invoca el método `loginApi` con código y contraseña válidos, THE ProveedorApiService SHALL enviar un POST a `/Login/Login` con los campos `codigo` y `pwd`, y retornar los datos del usuario y el token.
2. WHEN la API externa responde con credenciales inválidas (código HTTP 401 o respuesta sin token), THE ProveedorApiService SHALL retornar una respuesta de error con tipo `autenticacion_fallida`.
3. THE ProveedorApiService SHALL aplicar los timeouts configurados al endpoint de login sin aplicar retry (el login no es idempotente en cuanto a intentos de contraseña).

### Requirement 7: Método de Búsqueda de OC por Código

**User Story:** Como proveedor, quiero buscar mis órdenes de compra por código, para consultar el detalle de documentos específicos.

#### Acceptance Criteria

1. WHEN se invoca el método `buscarPorCodigo` con un código y un token válido, THE ProveedorApiService SHALL enviar un GET a `/ClienteProveedor/BuscarPorCodigo` con el parámetro `codigo` y el header `Authorization: Bearer {token}`.
2. WHEN la API externa responde con código HTTP 404 o un array vacío, THE ProveedorApiService SHALL retornar una respuesta de error con tipo `no_encontrado` y un mensaje indicando que no se encontraron resultados para ese código.
3. WHEN la API externa responde con código HTTP 401, THE ProveedorApiService SHALL retornar una respuesta de error con tipo `autenticacion_fallida`.

### Requirement 8: Método de Listado de OC por Código de Proveedor

**User Story:** Como proveedor, quiero listar todas mis órdenes de compra asociadas a mi código, para tener visibilidad de mis documentos pendientes y completados.

#### Acceptance Criteria

1. WHEN se invoca el método `listarPorCodigo` con un código y un token válido, THE ProveedorApiService SHALL enviar un GET a `/ClienteProveedor/ListarClienteProvedorPorCodigo` con el parámetro `codigo` y el header `Authorization: Bearer {token}`.
2. WHEN la API externa responde con código HTTP 404 o un array vacío, THE ProveedorApiService SHALL retornar una respuesta de error con tipo `no_encontrado`.
3. WHEN la API externa responde con código HTTP 401, THE ProveedorApiService SHALL retornar una respuesta de error con tipo `autenticacion_fallida`.

### Requirement 9: Manejo Centralizado de Respuestas HTTP

**User Story:** Como desarrollador del portal, quiero que el procesamiento de respuestas HTTP esté centralizado en un método privado, para evitar duplicación de lógica de manejo de errores en cada método público.

#### Acceptance Criteria

1. THE ProveedorApiService SHALL implementar un método privado `procesarRespuesta` que reciba la respuesta HTTP y el nombre del endpoint, y retorne la estructura estandarizada de respuesta.
2. WHEN el método `procesarRespuesta` recibe una respuesta con código HTTP 2xx, THE ProveedorApiService SHALL retornar una respuesta exitosa con los datos del body.
3. WHEN el método `procesarRespuesta` recibe una respuesta con código HTTP 401, THE ProveedorApiService SHALL retornar una respuesta de error con tipo `autenticacion_fallida`.
4. WHEN el método `procesarRespuesta` recibe una respuesta con código HTTP 404, THE ProveedorApiService SHALL retornar una respuesta de error con tipo `no_encontrado`.
5. WHEN el método `procesarRespuesta` recibe una respuesta con código HTTP 5xx, THE ProveedorApiService SHALL retornar una respuesta de error con tipo `error_servidor`.

### Requirement 10: Configuración en config/services.php y .env

**User Story:** Como administrador del portal, quiero que todos los parámetros del servicio de API (URL, timeouts, reintentos) estén centralizados en la configuración de Laravel, para facilitar el despliegue en diferentes ambientes.

#### Acceptance Criteria

1. THE ProveedorApiService SHALL leer la URL base, timeout de conexión, timeout de respuesta y número máximo de reintentos desde `config/services.php` bajo la clave `proveedor_api`.
2. THE ProveedorApiService SHALL funcionar con valores por defecto razonables cuando las variables de entorno no estén definidas: URL vacía, connect_timeout 5s, timeout 15s, max_retries 3.
3. IF la URL base de la API no está configurada o está vacía, THEN THE ProveedorApiService SHALL retornar una respuesta de error con tipo `api_caida` y mensaje indicando que la API no está configurada, sin intentar la llamada HTTP.
