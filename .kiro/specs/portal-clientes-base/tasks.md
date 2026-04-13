# Tasks — Portal de Clientes Base

## Task 1: Migración y modelo de datos

- [x] 1.1 Crear migración `database/migrations/YYYY_MM_DD_000000_create_clientes_users_table.php` con todas las columnas: id, nombre, correo, usuario (unique), password, telefono, rfc, tipo_persona, codigo_cliente, tipo_cliente, credito_autorizado (boolean default false), limite_credito (decimal 12,2), activo (boolean default true), remember_token, timestamps, softDeletes. Agregar índices en `correo` y `codigo_cliente`. Método `down()` debe hacer `dropIfExists`.
- [x] 1.2 Crear modelo `app/Models/ClienteUser.php` extendiendo Authenticatable con SoftDeletes, $table='clientes_users', $fillable con todos los campos, $hidden=['password'], $casts para activo y credito_autorizado como boolean. Seguir patrón exacto de ProveedorUser.

## Task 2: Middleware de autenticación

- [x] 2.1 Crear `app/Http/Middleware/AutenticacionCliente.php` que verifique `session('cliente_id')`. Si no existe, redirigir a `/login-cliente` con mensaje flash "Debes iniciar sesión para acceder al portal". Seguir patrón exacto de AutenticacionProveedor.
- [x] 2.2 Registrar middleware con alias `auth.cliente` en `bootstrap/app.php` junto al alias existente `auth.proveedor`.

## Task 3: Form Request y controlador de autenticación

- [x] 3.1 Crear `app/Http/Requests/LoginClienteRequest.php` con reglas: `usuario` required, `password` required. Seguir patrón de LoginProveedorRequest.
- [x] 3.2 Crear `app/Http/Controllers/AuthClienteController.php` con métodos: `mostrarLogin()` retorna vista `clientes.login`; `procesarLogin(LoginClienteRequest)` con rate limiting (5 intentos/min por IP, clave `login-cliente|{ip}`), busca ClienteUser por `usuario`, verifica password con Hash::check, verifica `activo`, guarda sesión (cliente_id, cliente_nombre, cliente_codigo, cliente_correo, cliente_tipo), redirige a `/portal-cliente`; `cerrarSesion()` limpia sesión y redirige a `/login-cliente`. Sin API externa, solo autenticación local.

## Task 4: Controlador del portal

- [x] 4.1 Crear `app/Http/Controllers/PortalClienteController.php` con métodos: `mostrarPortal()` retorna `clientes.portal`, `mostrarDashboard()` retorna `clientes.dashboard`, `mostrarCatalogo()` retorna `clientes.catalogo`, `mostrarPedidos()` retorna `clientes.pedidos`. Seguir patrón de PortalProveedorController.

## Task 5: Rutas

- [x] 5.1 Agregar bloque de rutas de clientes en `routes/web.php` con comentario `// ── Portal de Clientes ──`. Rutas: GET/POST `/login-cliente` (AuthClienteController), POST `/logout-cliente`, GET `/portal-cliente` (middleware auth.cliente), GET `/cliente/dashboard` (middleware auth.cliente), GET `/cliente/catalogo` (middleware auth.cliente), GET `/cliente/pedidos` (middleware auth.cliente). Nombres: `clientes.login`, `clientes.login.procesar`, `clientes.logout`, `clientes.portal`, `clientes.dashboard`, `clientes.catalogo`, `clientes.pedidos`. Agregar `use App\Http\Controllers\AuthClienteController` y `use App\Http\Controllers\PortalClienteController` al inicio del archivo.

## Task 6: Vista de login

- [x] 6.1 Crear `resources/views/clientes/login.blade.php` con diseño glassmorphism morado (copiar estructura de proveedores/login.blade.php). Cambiar: subtítulo a "Portal de Clientes", form action a `/login-cliente`, campos `usuario` y `password` (no `codigo` ni `pwd`), botón "Ingresar al portal". Mostrar alertas de error/éxito desde sesión. NO incluir enlace de registro. NO incluir campo código de compras.

## Task 7: Layout base del portal

- [x] 7.1 Crear `resources/views/layouts/cliente.blade.php` copiando estructura de `layouts/proveedor.blade.php`. Cambiar: subtítulo navbar a "Portal de Clientes", session keys a `cliente_nombre`, logout action a ruta `clientes.logout`. Sidebar con secciones: Principal (Inicio → `clientes.portal`, Dashboard → `clientes.dashboard`), Operaciones (Catálogo → `clientes.catalogo`, Pedidos → `clientes.pedidos`), Cuenta (Mi Perfil placeholder). Mantener mismas variables CSS, Inter font, hero band yield, content yield, footer.

## Task 8: Vista del portal (inicio)

- [x] 8.1 Crear `resources/views/clientes/portal.blade.php` como vista standalone (no extiende layout, igual que proveedores/portal.blade.php). Navbar horizontal con marca, menú (Inicio activo), nombre del cliente desde sesión, botón logout. Sidebar hover que aparece al acercar cursor al borde izquierdo. Saludo personalizado "Hola, {nombre}". Cards de resumen mockeadas: Pedidos activos, Catálogo de productos, Estado de cuenta, Tipo de cliente. Sección de acceso rápido con enlaces a Catálogo, Pedidos y Dashboard. Footer con marca y año.

## Task 9: Vistas mockeadas (dashboard, catálogo, pedidos)

- [x] 9.1 Crear `resources/views/clientes/dashboard.blade.php` extendiendo `layouts.cliente`. Hero band con nombre del cliente y fecha actual. Secciones mockeadas: métricas (pedidos, facturas CFDI, estado de cuenta) con texto "Pendiente de API".
- [x] 9.2 Crear `resources/views/clientes/catalogo.blade.php` extendiendo `layouts.cliente`. Hero band "Catálogo de Productos". Mensaje indicando que el catálogo se conectará a la API de Alan cuando esté disponible.
- [x] 9.3 Crear `resources/views/clientes/pedidos.blade.php` extendiendo `layouts.cliente`. Hero band "Mis Pedidos". Tabla vacía con columnas: Folio, Fecha, Productos, Total, Estatus. Texto "Pendiente de API".

## Task 10: Seeder de clientes de prueba

- [x] 10.1 Crear `database/seeders/ClienteUserSeeder.php` con 2 clientes: CLI001 (mayorista, "Comercializadora del Norte SA de CV", password "salcom2026" hasheado) y CLI002 (minorista, "Ferretería López", password "salcom2026" hasheado). Usar `updateOrCreate` con clave `usuario`. Incluir todos los campos del requisito 11.
- [x] 10.2 Registrar `ClienteUserSeeder` en `database/seeders/DatabaseSeeder.php` agregando `$this->call(ClienteUserSeeder::class)` después del call a ProveedorUserSeeder.

## Task 11: Tests de integración

- [x] 11.1 Crear `tests/Feature/FlujoCompletoClienteTest.php` siguiendo patrón de FlujoCompletoProveedorTest. Incluir: test login exitoso (POST /login-cliente con credenciales válidas → redirect /portal-cliente, sesión contiene cliente_id); test credenciales inválidas (redirect con error); test cuenta inactiva (redirect con "Tu cuenta está desactivada"); test flujo completo (login → portal 200 → dashboard 200 → catálogo 200 → pedidos 200); test middleware (rutas protegidas redirigen a /login-cliente sin sesión); test logout (limpia sesión, redirige); test login view (GET /login-cliente → 200, contiene "Portal de Clientes").
- [x] 11.2 Crear `tests/Feature/ClientePropertyTest.php` con property-based tests usando Faker en loops de 100 iteraciones. Property 1: login round-trip (genera ClienteUsers aleatorios, verifica sesión). Property 2: credenciales inválidas (genera passwords incorrectos, verifica rechazo). Property 3: rate limiting (6+ intentos → bloqueo). Property 4: logout cleanup (genera sesiones, verifica limpieza).
