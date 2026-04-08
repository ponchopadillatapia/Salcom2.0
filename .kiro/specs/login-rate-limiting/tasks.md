# Tasks

## Task 1: Configuración de rate limiting
- [x] 1.1 Agregar sección `rate_limiting` a `config/auth.php` con `max_attempts` y `decay_seconds` leyendo de variables de entorno con defaults 5 y 60
- [x] 1.2 Agregar variables `LOGIN_MAX_ATTEMPTS=5` y `LOGIN_DECAY_SECONDS=60` al `.env.example`

## Task 2: Implementar rate limiting en procesarLogin
- [x] 2.1 Agregar import de `RateLimiter` facade en `ProveedorController`
- [x] 2.2 Agregar verificación de rate limit al inicio de `procesarLogin()` que rechace IPs bloqueadas con redirect y mensaje incluyendo segundos restantes
- [x] 2.3 Agregar `RateLimiter::hit()` en cada punto de fallo del login existente
- [x] 2.4 Agregar `RateLimiter::clear()` en cada punto de éxito del login existente
- [x] 2.5 Agregar `Log::warning` cuando un intento es bloqueado, incluyendo IP y segundos restantes

## Task 3: Unit tests del rate limiting
- [x] 3.1 Test: defaults sin variables de entorno (max_attempts=5, decay_seconds=60)
- [x] 3.2 Test: login fallido incrementa contador de intentos
- [x] 3.3 Test: login exitoso limpia contador de intentos
- [x] 3.4 Test: IP bloqueada recibe redirect con mensaje y segundos restantes
- [x] 3.5 Test: IP bloqueada no procesa credenciales (API/BD no se invocan)
- [x] 3.6 Test: Log::warning se registra al bloquear con IP y segundos

## Task 4: Property tests del rate limiting
- [x] 4.1 [PBT] Property test: Configuración de rate limiting es respetada (Property 1)
- [x] 4.2 [PBT] Property test: Intento fallido incrementa el contador (Property 2)
- [x] 4.3 [PBT] Property test: Login exitoso limpia el contador (Property 3)
- [x] 4.4 [PBT] Property test: Clave de rate limit usa la IP del cliente (Property 4)
- [x] 4.5 [PBT] Property test: IP bloqueada rechaza login sin procesar credenciales (Property 5)
- [x] 4.6 [PBT] Property test: Log de bloqueo contiene IP y tiempo restante (Property 6)
