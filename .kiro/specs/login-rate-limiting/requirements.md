# Requirements Document — Login Rate Limiting

## Introducción

Protección contra ataques de fuerza bruta en el login del Portal de Proveedores de Industrias Salcom. El sistema limitará los intentos fallidos de login por dirección IP, bloqueando temporalmente el acceso tras exceder el umbral configurado y mostrando al usuario un mensaje claro con el tiempo restante para reintentar.

## Glosario

- **Rate_Limiter**: Componente de Laravel (`Illuminate\Support\Facades\RateLimiter`) que controla la frecuencia de intentos de login por dirección IP.
- **Login_Controller**: Método `procesarLogin` del `ProveedorController` que procesa las solicitudes POST a `/login-proveedor`.
- **Vista_Login**: Template Blade `proveedores/login.blade.php` que muestra el formulario de inicio de sesión.
- **Ventana_De_Tiempo**: Período configurable (en segundos) durante el cual se acumulan los intentos fallidos. Valor por defecto: 60 segundos.
- **Máximo_De_Intentos**: Número máximo de intentos fallidos permitidos dentro de la Ventana_De_Tiempo. Valor por defecto: 5.
- **Clave_Rate_Limit**: Identificador único para el Rate_Limiter compuesto por un prefijo fijo y la dirección IP del cliente (formato: `login-proveedor|{ip}`).
- **Tiempo_Restante**: Cantidad de segundos que faltan para que el bloqueo expire y el usuario pueda intentar de nuevo.

## Requisitos

### Requisito 1: Configuración del Rate Limiting vía variables de entorno

**User Story:** Como administrador del sistema, quiero configurar el máximo de intentos y la ventana de tiempo mediante variables de entorno, para poder ajustar la protección sin modificar código.

#### Criterios de Aceptación

1. THE Rate_Limiter SHALL leer el valor de `LOGIN_MAX_ATTEMPTS` desde las variables de entorno para determinar el Máximo_De_Intentos.
2. THE Rate_Limiter SHALL leer el valor de `LOGIN_DECAY_SECONDS` desde las variables de entorno para determinar la Ventana_De_Tiempo.
3. WHEN la variable `LOGIN_MAX_ATTEMPTS` no está definida, THE Rate_Limiter SHALL usar 5 como valor por defecto para el Máximo_De_Intentos.
4. WHEN la variable `LOGIN_DECAY_SECONDS` no está definida, THE Rate_Limiter SHALL usar 60 como valor por defecto para la Ventana_De_Tiempo.

### Requisito 2: Conteo de intentos fallidos por IP

**User Story:** Como sistema de seguridad, quiero contar únicamente los intentos de login fallidos por dirección IP, para no penalizar a usuarios legítimos que ingresan correctamente.

#### Criterios de Aceptación

1. WHEN un intento de login falla, THE Rate_Limiter SHALL incrementar el contador de intentos asociado a la Clave_Rate_Limit del cliente.
2. WHEN un intento de login es exitoso, THE Rate_Limiter SHALL mantener el contador de intentos sin cambios (no incrementar).
3. THE Rate_Limiter SHALL usar la dirección IP del cliente como identificador único en la Clave_Rate_Limit.

### Requisito 3: Bloqueo tras exceder el límite de intentos

**User Story:** Como sistema de seguridad, quiero bloquear temporalmente los intentos de login desde una IP que exceda el máximo permitido, para mitigar ataques de fuerza bruta.

#### Criterios de Aceptación

1. WHEN el número de intentos fallidos desde una IP alcanza el Máximo_De_Intentos dentro de la Ventana_De_Tiempo, THE Login_Controller SHALL rechazar la solicitud de login sin procesar credenciales.
2. WHILE una IP está bloqueada, THE Login_Controller SHALL redirigir al usuario a la Vista_Login con un mensaje de error que incluya el Tiempo_Restante en segundos.
3. WHEN la Ventana_De_Tiempo expira, THE Rate_Limiter SHALL restablecer el contador de intentos para esa IP, permitiendo nuevos intentos.

### Requisito 4: Limpieza del contador tras login exitoso

**User Story:** Como usuario proveedor, quiero que mi contador de intentos fallidos se reinicie al iniciar sesión correctamente, para no arrastrar penalizaciones de intentos anteriores.

#### Criterios de Aceptación

1. WHEN un intento de login es exitoso, THE Rate_Limiter SHALL restablecer a cero el contador de intentos asociado a la Clave_Rate_Limit del cliente.

### Requisito 5: Mensaje de error claro al usuario bloqueado

**User Story:** Como usuario proveedor, quiero ver un mensaje claro cuando estoy bloqueado por demasiados intentos, para saber cuánto tiempo debo esperar antes de reintentar.

#### Criterios de Aceptación

1. WHILE una IP está bloqueada, THE Vista_Login SHALL mostrar un mensaje de error que indique que se han excedido los intentos permitidos.
2. WHILE una IP está bloqueada, THE Vista_Login SHALL mostrar el Tiempo_Restante en segundos para que el usuario sepa cuándo puede reintentar.
3. THE Vista_Login SHALL mostrar el mensaje de bloqueo usando el mismo estilo visual de alertas de error existente (clase `alert-error`).

### Requisito 6: Logging de intentos bloqueados

**User Story:** Como administrador del sistema, quiero que los intentos de login bloqueados se registren en los logs, para monitorear posibles ataques de fuerza bruta.

#### Criterios de Aceptación

1. WHEN un intento de login es bloqueado por el Rate_Limiter, THE Login_Controller SHALL registrar un mensaje de nivel `warning` en el log del sistema.
2. THE Login_Controller SHALL incluir en el registro de log la dirección IP del cliente y el Tiempo_Restante del bloqueo.
