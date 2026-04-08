# Requirements Document

## Introduction

Conectar el `ProveedorApiService` (ya blindado con timeouts, retry y respuestas estandarizadas) al flujo de login del `ProveedorController` para que la autenticación de proveedores use primero la API externa de Alan Osorio (`loginApi()`), con fallback automático a la base de datos local cuando la API no esté disponible. El modo de login es configurable vía `.env` para permitir transición gradual entre autenticación local y por API.

## Glossary

- **ProveedorController**: Controller Laravel en `app/Http/Controllers/ProveedorController.php` que maneja el flujo de login, registro y vistas del portal de proveedores.
- **ProveedorApiService**: Servicio en `app/Services/ProveedorApiService.php`, única capa de comunicación con la API externa. Ya implementa `loginApi()` con timeouts, respuesta estandarizada `{success, data, message, error_type}` y sin retry (login no es idempotente).
- **ProveedorApiException**: Excepción personalizada con constantes de tipo de error: `API_CAIDA`, `TIMEOUT`, `AUTENTICACION_FALLIDA`, `NO_ENCONTRADO`, `ERROR_SERVIDOR`, `ERROR_DESCONOCIDO`.
- **Login_Mode**: Variable de entorno `PROVEEDOR_LOGIN_MODE` que controla la estrategia de autenticación: `api` (solo API), `local` (solo BD), `fallback` (API primero, BD si API caída).
- **Token_API**: Token JWT retornado por `/Login/Login` de la API externa, necesario para llamadas subsecuentes a `buscarPorCodigo` y `listarPorCodigo`.
- **Login_Source**: Indicador en sesión (`proveedor_login_source`) que registra si el login fue por `api` o por `local`, para que otros flujos del portal sepan qué datos usar.
- **BD_Local**: Base de datos MySQL del portal con tabla `proveedores_users` que almacena usuarios registrados localmente.
- **Fallback**: Estrategia donde si la API externa no está disponible (caída, timeout, no configurada), el sistema recurre a la autenticación contra la BD local.

## Requirements

### Requirement 1: Inyección del ProveedorApiService en el Controller

**User Story:** Como desarrollador del portal, quiero que el ProveedorController reciba el ProveedorApiService por inyección de dependencias, para que el login pueda usar la API externa sin acoplar el controller a la instanciación del servicio.

#### Acceptance Criteria

1. THE ProveedorController SHALL recibir una instancia de ProveedorApiService vía inyección de dependencias en el constructor.
2. THE ProveedorController SHALL almacenar la instancia de ProveedorApiService como propiedad privada para uso en el método `procesarLogin`.
3. THE ProveedorController SHALL mantener todos los métodos existentes (mostrarLogin, guardar, cerrarSesion, etc.) sin modificar su comportamiento actual.

### Requirement 2: Configuración del Modo de Login

**User Story:** Como administrador del portal, quiero poder configurar el modo de autenticación vía `.env`, para controlar si el login usa la API, la BD local o fallback sin modificar código.

#### Acceptance Criteria

1. THE ProveedorController SHALL leer el modo de login desde `config('services.proveedor_api.login_mode')` con valor por defecto `fallback`.
2. THE ProveedorController SHALL aceptar los valores `api`, `local` y `fallback` como modos de login válidos.
3. IF el modo de login tiene un valor no reconocido, THEN THE ProveedorController SHALL comportarse como si el modo fuera `fallback`.
4. THE config/services.php SHALL incluir la clave `login_mode` dentro de `proveedor_api` leyendo de la variable de entorno `PROVEEDOR_LOGIN_MODE`.

### Requirement 3: Login por API Externa

**User Story:** Como proveedor, quiero autenticarme contra la API de Alan para que mis credenciales se validen contra el sistema central de Industrias Salcom.

#### Acceptance Criteria

1. WHEN el modo de login es `api` o `fallback`, THE ProveedorController SHALL invocar `ProveedorApiService::loginApi()` con el código y contraseña proporcionados por el usuario.
2. WHEN `loginApi()` retorna `success` como `true`, THE ProveedorController SHALL extraer el token del campo `data.tokencreado` y los datos del usuario del campo `data.usuario` de la respuesta.
3. WHEN `loginApi()` retorna `success` como `true`, THE ProveedorController SHALL guardar en sesión: `proveedor_id`, `proveedor_nombre`, `proveedor_codigo`, `proveedor_correo`, `proveedor_token` y `proveedor_login_source` con valor `api`.
4. WHEN `loginApi()` retorna `success` como `true`, THE ProveedorController SHALL redirigir al usuario a `/portal-proveedor` con un mensaje de bienvenida.

### Requirement 4: Fallback a BD Local cuando la API no está Disponible

**User Story:** Como proveedor, quiero poder iniciar sesión aunque la API de Alan no esté disponible, para que el portal siga funcionando con la base de datos local como respaldo.

#### Acceptance Criteria

1. WHEN el modo de login es `fallback` y `loginApi()` retorna `error_type` igual a `api_caida`, THE ProveedorController SHALL intentar autenticar al usuario contra la BD local usando el flujo actual (buscar por `usuario` y verificar con `Hash::check`).
2. WHEN el modo de login es `fallback` y `loginApi()` retorna `error_type` igual a `timeout`, THE ProveedorController SHALL intentar autenticar al usuario contra la BD local.
3. WHEN el modo de login es `fallback` y `loginApi()` retorna `error_type` igual a `error_servidor`, THE ProveedorController SHALL intentar autenticar al usuario contra la BD local.
4. WHEN el modo de login es `fallback` y `loginApi()` retorna `error_type` igual a `error_desconocido`, THE ProveedorController SHALL intentar autenticar al usuario contra la BD local.
5. WHEN el fallback a BD local autentica exitosamente, THE ProveedorController SHALL guardar en sesión: `proveedor_id`, `proveedor_nombre`, `proveedor_codigo`, `proveedor_correo` y `proveedor_login_source` con valor `local`.
6. WHEN el fallback a BD local autentica exitosamente, THE ProveedorController SHALL guardar `proveedor_token` como `null` en la sesión.

### Requirement 5: No Hacer Fallback en Autenticación Fallida

**User Story:** Como administrador del portal, quiero que cuando la API rechace las credenciales del proveedor, el sistema NO intente autenticar contra la BD local, para respetar la validación del sistema central.

#### Acceptance Criteria

1. WHEN el modo de login es `fallback` y `loginApi()` retorna `error_type` igual a `autenticacion_fallida`, THE ProveedorController SHALL retornar al formulario de login con el mensaje de error "Credenciales incorrectas" sin intentar autenticación contra la BD local.
2. WHEN el modo de login es `api` y `loginApi()` retorna `error_type` igual a `autenticacion_fallida`, THE ProveedorController SHALL retornar al formulario de login con el mensaje de error "Credenciales incorrectas".

### Requirement 6: Modo Solo API

**User Story:** Como administrador del portal, quiero poder forzar que el login use exclusivamente la API externa, para cuando la API de Alan esté estable y se quiera desactivar el fallback local.

#### Acceptance Criteria

1. WHEN el modo de login es `api` y `loginApi()` retorna `success` como `true`, THE ProveedorController SHALL autenticar al usuario con los datos de la API y guardar `proveedor_login_source` como `api`.
2. WHEN el modo de login es `api` y `loginApi()` retorna `success` como `false`, THE ProveedorController SHALL retornar al formulario de login con el mensaje de error de la respuesta de la API sin intentar autenticación contra la BD local.

### Requirement 7: Modo Solo Local

**User Story:** Como administrador del portal, quiero poder forzar que el login use exclusivamente la BD local, para desactivar la API externa si presenta problemas persistentes.

#### Acceptance Criteria

1. WHEN el modo de login es `local`, THE ProveedorController SHALL autenticar al usuario contra la BD local sin invocar `loginApi()`.
2. WHEN el modo de login es `local` y la autenticación local es exitosa, THE ProveedorController SHALL guardar `proveedor_login_source` como `local` y `proveedor_token` como `null` en la sesión.
3. WHEN el modo de login es `local` y la autenticación local falla, THE ProveedorController SHALL retornar al formulario de login con el mensaje "Credenciales incorrectas".

### Requirement 8: Token de API en Sesión

**User Story:** Como desarrollador del portal, quiero que el token de la API se guarde en sesión después del login, para poder usarlo en llamadas subsecuentes a `buscarPorCodigo` y `listarPorCodigo`.

#### Acceptance Criteria

1. WHEN el login por API es exitoso, THE ProveedorController SHALL guardar el valor de `data.tokencreado` de la respuesta de `loginApi()` en la sesión bajo la clave `proveedor_token`.
2. WHEN el login es por BD local, THE ProveedorController SHALL guardar `null` en la sesión bajo la clave `proveedor_token`.
3. THE ProveedorController SHALL incluir `proveedor_token` en la lista de claves que se eliminan al cerrar sesión (`cerrarSesion`).

### Requirement 9: Indicador de Fuente de Login en Sesión

**User Story:** Como desarrollador del portal, quiero saber si el login fue por API o por BD local, para que otros flujos del portal puedan decidir qué datos usar (API con token vs BD local).

#### Acceptance Criteria

1. WHEN el login es exitoso por API, THE ProveedorController SHALL guardar `api` en la sesión bajo la clave `proveedor_login_source`.
2. WHEN el login es exitoso por BD local, THE ProveedorController SHALL guardar `local` en la sesión bajo la clave `proveedor_login_source`.
3. THE ProveedorController SHALL incluir `proveedor_login_source` en la lista de claves que se eliminan al cerrar sesión (`cerrarSesion`).

### Requirement 10: Logging del Flujo de Login

**User Story:** Como administrador del portal, quiero que el flujo de login registre en logs cuándo se usa la API, cuándo se hace fallback y cuándo falla, para poder monitorear la transición entre autenticación local y por API.

#### Acceptance Criteria

1. WHEN el login por API es exitoso, THE ProveedorController SHALL registrar un log de nivel `info` indicando el código del proveedor y que el login fue por API.
2. WHEN se ejecuta fallback a BD local, THE ProveedorController SHALL registrar un log de nivel `warning` indicando el código del proveedor, el error_type de la API y que se está usando fallback local.
3. WHEN el login falla (tanto API como fallback), THE ProveedorController SHALL registrar un log de nivel `error` indicando el código del proveedor y el motivo del fallo.
