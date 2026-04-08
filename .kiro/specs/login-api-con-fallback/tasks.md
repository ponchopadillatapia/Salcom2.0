# Tasks

## Task 1: Configuración del modo de login
- [x] 1.1 Agregar clave `login_mode` a `config/services.php` dentro de `proveedor_api` leyendo de `PROVEEDOR_LOGIN_MODE` con default `fallback`
- [x] 1.2 Agregar variable `PROVEEDOR_LOGIN_MODE=fallback` al `.env.example`

## Task 2: Inyección de dependencias y método getLoginMode
- [x] 2.1 Agregar constructor a `ProveedorController` que reciba `ProveedorApiService` y lo almacene como propiedad privada
- [x] 2.2 Implementar método privado `getLoginMode(): string` que lea config y normalice valores inválidos a `fallback`
- [x] 2.3 [PBT] Escribir property test: Modo de login inválido resuelve a fallback (Property 1)

## Task 3: Implementar métodos privados loginViaApi y loginViaLocal
- [x] 3.1 Implementar `loginViaApi(string $codigo, string $pwd): ?array` que invoque `loginApi()` y retorne array con datos de sesión o null
- [x] 3.2 Implementar `loginViaLocal(string $codigo, string $pwd): ?array` que busque en BD local con `Hash::check` y retorne array con datos o null
- [x] 3.3 Implementar `guardarSesion(array $datosProveedor, string $source, ?string $token): void` que guarde las 6 claves en sesión

## Task 4: Refactorizar procesarLogin con lógica de modos
- [x] 4.1 Refactorizar `procesarLogin` para leer el modo de login y delegar a `loginViaApi`, `loginViaLocal` o fallback según el modo
- [x] 4.2 Implementar lógica de fallback: si modo es `fallback` y API falla con error de disponibilidad, intentar BD local
- [x] 4.3 Implementar regla de no-fallback: si `error_type` es `autenticacion_fallida`, retornar error sin intentar BD local
- [x] 4.4 Agregar logging: `Log::info` en API exitosa, `Log::warning` en fallback, `Log::error` en fallo total

## Task 5: Actualizar cerrarSesion
- [x] 5.1 Agregar `proveedor_token` y `proveedor_login_source` a la lista de claves que se eliminan en `cerrarSesion()`

## Task 6: Unit tests del flujo de login
- [x] 6.1 Test: modo `api` con API exitosa → sesión con source=api y token
- [x] 6.2 Test: modo `api` con autenticacion_fallida → error sin fallback
- [x] 6.3 Test: modo `api` con api_caida → error sin fallback
- [x] 6.4 Test: modo `fallback` con API exitosa → sesión con source=api
- [x] 6.5 Test: modo `fallback` con api_caida → fallback a BD local exitoso → sesión con source=local y token=null
- [x] 6.6 Test: modo `fallback` con api_caida + BD local falla → error
- [x] 6.7 Test: modo `fallback` con autenticacion_fallida → error sin fallback
- [x] 6.8 Test: modo `local` → login exitoso sin llamar API
- [x] 6.9 Test: modo `local` con credenciales incorrectas → error
- [x] 6.10 Test: cerrarSesion limpia proveedor_token y proveedor_login_source
- [x] 6.11 Test: logging info en API exitosa, warning en fallback, error en fallo

## Task 7: Property tests del flujo de login
- [x] 7.1 [PBT] Escribir property test: Credenciales pasan intactas a loginApi (Property 2)
- [x] 7.2 [PBT] Escribir property test: Integridad de sesión tras login por API (Property 3)
- [x] 7.3 [PBT] Escribir property test: Invariante de sesión tras login local (Property 4)
- [x] 7.4 [PBT] Escribir property test: Modo API nunca hace fallback (Property 5)
- [x] 7.5 [PBT] Escribir property test: Modo local nunca invoca API (Property 6)
