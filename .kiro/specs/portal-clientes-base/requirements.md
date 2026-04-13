# Requirements Document — Portal de Clientes Base

## Introduction

Portal de clientes para Industrias Salcom. Los clientes son empresas externas que Salcom da de alta manualmente (no hay registro público). El portal vive en el mismo codebase que el portal de proveedores pero en subdominio separado. Sigue exactamente los mismos patrones arquitectónicos del portal de proveedores (modelo Authenticatable, middleware de sesión, controllers dedicados, layout Blade con CSS puro). Los primeros pagos son de contado, el catálogo viene de la API de Alan (no disponible aún), CFDI lo genera la API de Alan, y el tracking es interno hasta que sale de planta.

## Glossary

- **Portal_Clientes**: Aplicación web para clientes de Industrias Salcom, accesible en subdominio separado del portal de proveedores
- **ClienteUser**: Modelo Eloquent que representa a un usuario cliente en la tabla `clientes_users`, extiende Authenticatable con SoftDeletes
- **Middleware_AutenticacionCliente**: Middleware que verifica `session('cliente_id')` para proteger rutas del portal de clientes
- **AuthClienteController**: Controlador que maneja login, validación de credenciales con rate limiting, y cierre de sesión de clientes
- **PortalClienteController**: Controlador que sirve las vistas del portal: inicio, dashboard, catálogo y pedidos
- **Layout_Cliente**: Layout Blade base (`layouts/cliente.blade.php`) con navbar, sidebar hover y hero band, adaptado para clientes
- **Seeder_Clientes**: Seeder que crea 2 clientes de prueba para desarrollo y testing
- **Tipo_Cliente**: Clasificación del cliente: mayorista, minorista o distribuidor
- **Codigo_Cliente**: Identificador único asignado por Salcom al dar de alta al cliente

## Requirements

### Requirement 1: Migración de la tabla clientes_users

**User Story:** Como desarrollador, quiero una tabla `clientes_users` con todos los campos necesarios para almacenar datos de clientes, para que el portal tenga persistencia independiente del portal de proveedores.

#### Acceptance Criteria

1. THE Portal_Clientes SHALL crear una migración que genere la tabla `clientes_users` con las columnas: id (autoincrement), nombre (string), correo (string), usuario (string unique), password (string), telefono (string nullable), rfc (string nullable), tipo_persona (string nullable), codigo_cliente (string nullable), tipo_cliente (string nullable con valores esperados: mayorista, minorista, distribuidor), credito_autorizado (boolean default false), limite_credito (decimal 12,2 nullable), activo (boolean default true), remember_token (string nullable), timestamps y softDeletes
2. WHEN la migración se ejecute, THE Portal_Clientes SHALL crear índices en las columnas `usuario`, `correo` y `codigo_cliente`
3. WHEN la migración se revierta, THE Portal_Clientes SHALL eliminar la tabla `clientes_users` por completo

### Requirement 2: Modelo ClienteUser

**User Story:** Como desarrollador, quiero un modelo Eloquent `ClienteUser` que represente a los clientes, para que la aplicación interactúe con la tabla `clientes_users` de forma consistente con el patrón del modelo ProveedorUser.

#### Acceptance Criteria

1. THE ClienteUser SHALL extender `Illuminate\Foundation\Auth\User as Authenticatable` y usar el trait `SoftDeletes`
2. THE ClienteUser SHALL declarar la propiedad `$table` apuntando a `clientes_users`
3. THE ClienteUser SHALL declarar como `$fillable` los campos: nombre, correo, usuario, password, telefono, rfc, tipo_persona, codigo_cliente, tipo_cliente, credito_autorizado, limite_credito, activo
4. THE ClienteUser SHALL ocultar el campo `password` en la propiedad `$hidden`
5. THE ClienteUser SHALL castear `activo` y `credito_autorizado` como `boolean` en la propiedad `$casts`

### Requirement 3: Middleware de autenticación de clientes

**User Story:** Como desarrollador, quiero un middleware que proteja las rutas del portal de clientes verificando la sesión activa, para que usuarios no autenticados sean redirigidos al login.

#### Acceptance Criteria

1. WHEN una petición llega a una ruta protegida por el Middleware_AutenticacionCliente, THE Middleware_AutenticacionCliente SHALL verificar que `session('cliente_id')` contenga un valor
2. IF `session('cliente_id')` no contiene un valor, THEN THE Middleware_AutenticacionCliente SHALL redirigir a `/login-cliente` con un mensaje flash de error "Debes iniciar sesión para acceder al portal"
3. WHEN `session('cliente_id')` contiene un valor válido, THE Middleware_AutenticacionCliente SHALL permitir que la petición continúe al siguiente handler
4. THE Portal_Clientes SHALL registrar el Middleware_AutenticacionCliente con el alias `auth.cliente` en `bootstrap/app.php`

### Requirement 4: Controlador de autenticación de clientes

**User Story:** Como cliente dado de alta por Salcom, quiero iniciar y cerrar sesión en el portal, para que pueda acceder a mis funcionalidades de forma segura.

#### Acceptance Criteria

1. WHEN un cliente accede a GET `/login-cliente`, THE AuthClienteController SHALL retornar la vista `clientes.login`
2. WHEN un cliente envía POST `/login-cliente` con campos `usuario` y `password`, THE AuthClienteController SHALL buscar al ClienteUser por el campo `usuario` y verificar el password con `Hash::check`
3. WHEN las credenciales son válidas y el ClienteUser tiene `activo` en true, THE AuthClienteController SHALL almacenar en sesión: `cliente_id`, `cliente_nombre`, `cliente_codigo`, `cliente_correo` y `cliente_tipo` y redirigir a `/portal-cliente`
4. IF las credenciales son inválidas, THEN THE AuthClienteController SHALL redirigir de vuelta a `/login-cliente` con un mensaje flash de error "Credenciales incorrectas" y conservar el input previo
5. IF el ClienteUser tiene `activo` en false, THEN THE AuthClienteController SHALL redirigir de vuelta a `/login-cliente` con un mensaje flash de error "Tu cuenta está desactivada. Contacta a Salcom."
6. THE AuthClienteController SHALL aplicar rate limiting de 5 intentos por minuto por IP usando `RateLimiter`, con la clave `login-cliente|{ip}`
7. IF el rate limiter detecta demasiados intentos, THEN THE AuthClienteController SHALL redirigir de vuelta con un mensaje indicando los segundos restantes para reintentar
8. WHEN un cliente envía POST `/logout-cliente`, THE AuthClienteController SHALL eliminar de la sesión las claves `cliente_id`, `cliente_nombre`, `cliente_codigo`, `cliente_correo` y `cliente_tipo`, y redirigir a `/login-cliente` con mensaje "Sesión cerrada correctamente"

### Requirement 5: Controlador del portal de clientes

**User Story:** Como cliente autenticado, quiero navegar por las secciones del portal (inicio, dashboard, catálogo, pedidos), para que pueda consultar información relevante de mi cuenta.

#### Acceptance Criteria

1. WHEN un cliente autenticado accede a GET `/portal-cliente`, THE PortalClienteController SHALL retornar la vista `clientes.portal`
2. WHEN un cliente autenticado accede a GET `/cliente/dashboard`, THE PortalClienteController SHALL retornar la vista `clientes.dashboard`
3. WHEN un cliente autenticado accede a GET `/cliente/catalogo`, THE PortalClienteController SHALL retornar la vista `clientes.catalogo`
4. WHEN un cliente autenticado accede a GET `/cliente/pedidos`, THE PortalClienteController SHALL retornar la vista `clientes.pedidos`

### Requirement 6: Definición de rutas del portal de clientes

**User Story:** Como desarrollador, quiero rutas definidas en `web.php` para el portal de clientes, para que las URLs sean accesibles y estén protegidas por el middleware correspondiente.

#### Acceptance Criteria

1. THE Portal_Clientes SHALL definir la ruta GET `/login-cliente` apuntando a `AuthClienteController@mostrarLogin` con nombre `clientes.login`
2. THE Portal_Clientes SHALL definir la ruta POST `/login-cliente` apuntando a `AuthClienteController@procesarLogin` con nombre `clientes.login.procesar`
3. THE Portal_Clientes SHALL definir la ruta POST `/logout-cliente` apuntando a `AuthClienteController@cerrarSesion` con nombre `clientes.logout`
4. THE Portal_Clientes SHALL definir la ruta GET `/portal-cliente` apuntando a `PortalClienteController@mostrarPortal` con nombre `clientes.portal` y middleware `auth.cliente`
5. THE Portal_Clientes SHALL definir la ruta GET `/cliente/dashboard` apuntando a `PortalClienteController@mostrarDashboard` con nombre `clientes.dashboard` y middleware `auth.cliente`
6. THE Portal_Clientes SHALL definir la ruta GET `/cliente/catalogo` apuntando a `PortalClienteController@mostrarCatalogo` con nombre `clientes.catalogo` y middleware `auth.cliente`
7. THE Portal_Clientes SHALL definir la ruta GET `/cliente/pedidos` apuntando a `PortalClienteController@mostrarPedidos` con nombre `clientes.pedidos` y middleware `auth.cliente`

### Requirement 7: Layout base del portal de clientes

**User Story:** Como cliente autenticado, quiero ver un layout profesional con navbar, sidebar y hero band, para que mi experiencia sea consistente con el diseño de Industrias Salcom.

#### Acceptance Criteria

1. THE Layout_Cliente SHALL incluir la fuente Inter de Google Fonts y las mismas variables CSS del layout de proveedores (paleta morada, bordes, sombras, radios)
2. THE Layout_Cliente SHALL mostrar un navbar blanco sticky con el texto "Industrias Salcom" y subtítulo "Portal de Clientes", el nombre del cliente desde `session('cliente_nombre')`, y un botón de cerrar sesión que envíe POST a la ruta `clientes.logout`
3. THE Layout_Cliente SHALL incluir un sidebar colapsable con secciones: Principal (Inicio, Dashboard), Operaciones (Catálogo, Pedidos) y Cuenta (Mi Perfil placeholder)
4. THE Layout_Cliente SHALL incluir un yield `hero` para la hero band y un yield `content` para el contenido principal
5. THE Layout_Cliente SHALL incluir un footer con "Industrias Salcom" y el año actual

### Requirement 8: Vista de login de clientes

**User Story:** Como cliente, quiero ver una pantalla de login con diseño glassmorphism morado, para que la experiencia visual sea profesional y consistente con la marca Salcom.

#### Acceptance Criteria

1. THE Portal_Clientes SHALL mostrar la vista `clientes.login` con fondo degradado morado, card glassmorphism (backdrop-filter blur), título "Industrias Salcom" y subtítulo "Portal de Clientes"
2. THE Portal_Clientes SHALL incluir en el formulario de login los campos: usuario (text, required) y contraseña (password, required), con un botón "Ingresar al portal"
3. WHEN existe un mensaje flash de error en la sesión, THE Portal_Clientes SHALL mostrar una alerta roja con el mensaje
4. WHEN existe un mensaje flash de éxito en la sesión, THE Portal_Clientes SHALL mostrar una alerta verde con el mensaje
5. THE Portal_Clientes SHALL NO incluir enlace de registro público, dado que los clientes son dados de alta por Salcom

### Requirement 9: Vista del portal de clientes (inicio)

**User Story:** Como cliente autenticado, quiero ver una página de inicio limpia con navbar horizontal y sidebar hover, para que pueda navegar fácilmente por el portal.

#### Acceptance Criteria

1. THE Portal_Clientes SHALL mostrar la vista `clientes.portal` con navbar horizontal (marca, menú, usuario, logout) y sidebar hover que aparece al acercar el cursor al borde izquierdo
2. THE Portal_Clientes SHALL mostrar un saludo personalizado con el nombre del cliente desde la sesión y un mensaje de bienvenida
3. THE Portal_Clientes SHALL mostrar cards de resumen con datos mockeados: Pedidos activos, Catálogo de productos, Estado de cuenta y Tipo de cliente
4. THE Portal_Clientes SHALL mostrar una sección de acceso rápido con enlaces a Catálogo, Pedidos y Dashboard

### Requirement 10: Vistas mockeadas de dashboard, catálogo y pedidos

**User Story:** Como cliente autenticado, quiero ver vistas placeholder para dashboard, catálogo y pedidos, para que la navegación del portal sea funcional mientras se integran las APIs.

#### Acceptance Criteria

1. THE Portal_Clientes SHALL mostrar la vista `clientes.dashboard` extendiendo el Layout_Cliente, con hero band mostrando nombre del cliente y fecha actual, y secciones mockeadas de métricas (pedidos, facturas CFDI, estado de cuenta) con texto "Pendiente de API"
2. THE Portal_Clientes SHALL mostrar la vista `clientes.catalogo` extendiendo el Layout_Cliente, con hero band "Catálogo de Productos" y un mensaje indicando que el catálogo se conectará a la API de Alan cuando esté disponible
3. THE Portal_Clientes SHALL mostrar la vista `clientes.pedidos` extendiendo el Layout_Cliente, con hero band "Mis Pedidos" y una tabla vacía con columnas: Folio, Fecha, Productos, Total, Estatus, con texto "Pendiente de API"

### Requirement 11: Seeder de clientes de prueba

**User Story:** Como desarrollador, quiero un seeder con 2 clientes de prueba, para que pueda probar el flujo completo de login y navegación del portal sin depender de datos externos.

#### Acceptance Criteria

1. THE Seeder_Clientes SHALL crear un cliente mayorista con datos: usuario "CLI001", password hasheado "salcom2026", nombre "Comercializadora del Norte SA de CV", tipo_persona "Persona Moral", tipo_cliente "mayorista", codigo_cliente "CLI-2026-001", correo "compras@comnorte.com", telefono "8112345678", activo true, credito_autorizado false
2. THE Seeder_Clientes SHALL crear un cliente minorista con datos: usuario "CLI002", password hasheado "salcom2026", nombre "Ferretería López", tipo_persona "Persona Física", tipo_cliente "minorista", codigo_cliente "CLI-2026-002", correo "contacto@ferrelopez.com", telefono "3387654321", activo true, credito_autorizado false
3. THE Seeder_Clientes SHALL usar `updateOrCreate` con la clave `usuario` para evitar duplicados al ejecutar el seeder múltiples veces
4. THE Portal_Clientes SHALL registrar el Seeder_Clientes en `DatabaseSeeder` para que se ejecute con `php artisan db:seed`

### Requirement 12: Tests de integración del flujo completo

**User Story:** Como desarrollador, quiero tests de integración que validen el flujo completo login → portal → dashboard → catálogo → pedidos, para que pueda verificar que toda la base del portal funciona correctamente.

#### Acceptance Criteria

1. WHEN se ejecutan los tests, THE Portal_Clientes SHALL verificar que GET `/login-cliente` retorna status 200 y contiene el texto "Portal de Clientes"
2. WHEN se envía POST `/login-cliente` con credenciales válidas de un ClienteUser activo, THE Portal_Clientes SHALL verificar que la respuesta redirige a `/portal-cliente` y la sesión contiene `cliente_id`
3. WHEN se envía POST `/login-cliente` con credenciales inválidas, THE Portal_Clientes SHALL verificar que la respuesta redirige de vuelta con mensaje de error
4. WHEN se envía POST `/login-cliente` con un ClienteUser inactivo, THE Portal_Clientes SHALL verificar que la respuesta redirige de vuelta con mensaje "Tu cuenta está desactivada"
5. WHEN un cliente autenticado accede a GET `/portal-cliente`, THE Portal_Clientes SHALL verificar que retorna status 200
6. WHEN un cliente autenticado accede a GET `/cliente/dashboard`, THE Portal_Clientes SHALL verificar que retorna status 200
7. WHEN un cliente autenticado accede a GET `/cliente/catalogo`, THE Portal_Clientes SHALL verificar que retorna status 200
8. WHEN un cliente autenticado accede a GET `/cliente/pedidos`, THE Portal_Clientes SHALL verificar que retorna status 200
9. WHEN un usuario no autenticado accede a GET `/portal-cliente`, THE Portal_Clientes SHALL verificar que la respuesta redirige a `/login-cliente`
10. WHEN un cliente autenticado envía POST `/logout-cliente`, THE Portal_Clientes SHALL verificar que la sesión ya no contiene `cliente_id` y la respuesta redirige a `/login-cliente`
