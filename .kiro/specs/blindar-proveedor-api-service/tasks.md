# Tasks

## Task 1: Crear ProveedorApiException
- [x] 1.1 Crear `app/Exceptions/ProveedorApiException.php` con propiedades `errorType`, `httpCode`, `responseData`, constructor privado y getters
- [x] 1.2 Definir constantes de tipos de error: `API_CAIDA`, `TIMEOUT`, `AUTENTICACION_FALLIDA`, `NO_ENCONTRADO`, `ERROR_SERVIDOR`, `ERROR_VALIDACION`, `ERROR_DESCONOCIDO`
- [x] 1.3 Implementar factory methods estáticos: `apiCaida()`, `timeout()`, `autenticacionFallida()`, `noEncontrado()`, `errorServidor()`, `errorValidacion()`, `errorDesconocido()`
- [x] 1.4 Escribir tests unitarios para cada factory method en `tests/Unit/Services/ProveedorApiExceptionTest.php`
- [x] 1.5 [PBT] Escribir property test: Construcción round-trip de ProveedorApiException (Property 1)

## Task 2: Actualizar configuración en config/services.php
- [x] 2.1 Agregar claves `connect_timeout`, `timeout` y `max_retries` a `config/services.php` bajo `proveedor_api`
- [x] 2.2 Agregar variables `PROVEEDOR_API_CONNECT_TIMEOUT`, `PROVEEDOR_API_TIMEOUT`, `PROVEEDOR_API_MAX_RETRIES` al `.env.example`

## Task 3: Refactorizar ProveedorApiService - Constructor y helpers
- [x] 3.1 Actualizar constructor para leer `connect_timeout`, `timeout` y `max_retries` desde config con valores por defecto (5, 15, 3)
- [x] 3.2 Implementar método privado `validarConfiguracion()` que retorne error si URL está vacía sin hacer HTTP
- [x] 3.3 Implementar método privado `buildSuccessResponse(array $data): array`
- [x] 3.4 Implementar método privado `buildErrorResponse(string $message, string $errorType): array`
- [x] 3.5 Implementar método privado `procesarRespuesta(Response $response, string $endpoint): array` con mapeo de códigos HTTP a tipos de error
- [x] 3.6 [PBT] Escribir property test: Invariante de estructura de respuesta (Property 2)
- [x] 3.7 [PBT] Escribir property test: Mapeo de código HTTP a tipo de error (Property 3)
- [x] 3.8 [PBT] Escribir property test: URL vacía falla sin intentar llamada HTTP (Property 6)

## Task 4: Implementar método loginApi con timeouts (sin retry)
- [x] 4.1 Implementar `loginApi(string $codigo, string $pwd): array` con POST a `/Login/Login`, timeouts configurados, sin retry
- [x] 4.2 Manejar respuesta exitosa (extraer usuario y token) y errores (401 → autenticacion_fallida)
- [x] 4.3 Agregar logging de errores en login
- [x] 4.4 Escribir unit tests para loginApi: éxito, 401, timeout, URL vacía

## Task 5: Implementar método buscarPorCodigo con retry
- [x] 5.1 Implementar `buscarPorCodigo(string $codigo, string $token): array` con GET, Authorization header, retry con backoff exponencial
- [x] 5.2 Manejar 404/array vacío → no_encontrado, 401 → autenticacion_fallida
- [x] 5.3 Agregar logging de reintentos y errores
- [x] 5.4 Escribir unit tests para buscarPorCodigo: éxito, 404, vacío, 401, retry en 500, todos retries agotados

## Task 6: Implementar método listarPorCodigo con retry
- [x] 6.1 Implementar `listarPorCodigo(string $codigo, string $token): array` con GET, Authorization header, retry con backoff exponencial
- [x] 6.2 Manejar 404/array vacío → no_encontrado, 401 → autenticacion_fallida
- [x] 6.3 Agregar logging de reintentos y errores
- [x] 6.4 Escribir unit tests para listarPorCodigo: éxito, 404, vacío, 401, retry en 500

## Task 7: Property tests de retry y requests autenticados
- [x] 7.1 [PBT] Escribir property test: Retry en fallos transitorios respeta el máximo configurado (Property 4)
- [x] 7.2 [PBT] Escribir property test: Requests autenticados incluyen header y parámetros correctos (Property 5)

## Task 8: Logging y tests de integración
- [x] 8.1 Escribir tests que verifican logging: Log::error en fallo, Log::warning en éxito después de retry
- [x] 8.2 Verificar que login NO reintenta (solo 1 intento en error 500)
- [x] 8.3 Verificar valores por defecto de configuración (5s, 15s, 3 retries)
