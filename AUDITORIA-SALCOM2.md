# Auditoría Profesional — Salcom 2.0
## Industrias Salcom · Portal B2B de Proveedores, Clientes y Administración
**Fecha**: 27 de abril de 2026
**Stack**: Laravel 12 · PHP 8.2 · MySQL · AWS Bedrock · Blade + CSS iOS

---

## PARTE 1: ANÁLISIS DEL PROYECTO

### Qué es Salcom 2.0
Plataforma B2B para Industrias Salcom (empresa manufacturera mexicana) con tres portales independientes:
- **Portal de Proveedores** — onboarding, OC, facturas, pagos, score, forecast
- **Portal de Clientes** — catálogo, pedidos, tracking, estado de cuenta, encuestas
- **Panel Administrativo** — dashboard KPIs, gestión de clientes/proveedores, pedidos, encuestas, IA

### Funcionalidades implementadas

| Módulo | Estado | Descripción |
|--------|--------|-------------|
| Autenticación triple | ✅ Completo | Login/registro para admin, cliente, proveedor con rate limiting y fallback API |
| Gestión de pedidos | ✅ Completo | CRUD + cambio de estatus + notificaciones multicanal (BD + email + WhatsApp) |
| Muestras de materiales | ✅ Completo | Flujo de 7 etapas (registro → recepción → laboratorio → piso → estabilidad → aprobado/rechazado) |
| Validación documental | ✅ Completo | CIF, Opinión SAT, Acta Constitutiva, INE, Carátula Banco con OCR |
| Módulo de IA | ✅ Completo | Pronóstico de demanda, optimización inventario, selección proveedor (Bedrock/Anthropic) |
| Notificaciones | ✅ Completo | BD + Email (Mailable) + WhatsApp (Twilio) |
| Encuestas de satisfacción | ✅ Completo | Formulario cliente + analytics admin |
| Score de proveedores | ✅ Completo | 50% entrega + 50% puntualidad, visible en admin y portal |
| API REST interna | ✅ Completo | 15 endpoints con Bearer token para MCP/Kiro |
| MCP Server | ✅ Completo | 15 herramientas para integración con Kiro AI |
| PAC CFDI | ✅ Código listo | 3 drivers (Facturama, SW Sapien, Diverza) — falta configurar credenciales |
| Paqueterías | ✅ Código listo | Estafeta, DHL, FedEx — falta API keys |
| Validación RFC SAT | ✅ Código listo | Formato offline + verificación online — falta API key |
| Catálogo de productos | ⚠️ Parcial | Modelo + migración + seeder, vista stub |
| Portal de clientes | ⚠️ Parcial | Vistas existen, datos dependen de API de Alan |
| Facturación completa | ⚠️ Parcial | Modelo existe, flujo de timbrado no conectado |

### Tecnologías

| Capa | Tecnología |
|------|-----------|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Blade templates, CSS puro (design system iOS) |
| Base de datos | SQLite (dev), MySQL (producción) |
| IA | Amazon Bedrock (Claude 3.5 Sonnet) via AWS SDK, fallback Anthropic directo |
| PDF | smalot/pdfparser + Tesseract OCR |
| Email | Laravel Mail (SMTP/Gmail) |
| WhatsApp | Twilio API REST |
| QR | simplesoftwareio/simple-qrcode |
| PDF Gen | barryvdh/laravel-dompdf |
| MCP | Node.js + @modelcontextprotocol/sdk |

---

## PARTE 2: ARQUITECTURA DEL SISTEMA

### Diagrama de capas

```
┌─────────────────────────────────────────────────────┐
│                    FRONTEND                          │
│  Blade Templates + CSS iOS Design System             │
│  3 Layouts: admin.blade / cliente.blade / proveedor  │
│  1 CSS compartido: public/css/ios-theme.css          │
├─────────────────────────────────────────────────────┤
│                   CONTROLLERS                        │
│  Auth (3) · Portal (3) · Business (5) · API (2)     │
├─────────────────────────────────────────────────────┤
│                    SERVICES                          │
│  IaService · DocumentValidation · Notificacion       │
│  ProveedorApi · ClienteApi · PacCfdi · SatRfc        │
│  WhatsApp · Paqueteria                               │
├─────────────────────────────────────────────────────┤
│                     MODELS                           │
│  13 modelos con relaciones, casts, soft deletes      │
├─────────────────────────────────────────────────────┤
│                    DATABASE                          │
│  18 migraciones · 5 seeders · Índices optimizados    │
├─────────────────────────────────────────────────────┤
│               INTEGRACIONES EXTERNAS                 │
│  AWS Bedrock · Anthropic · Twilio · SAT · PAC        │
│  Paqueterías · API de Alan (ERP)                     │
└─────────────────────────────────────────────────────┘
```

### Flujo de conexión

```
Usuario → Blade View → Controller → Service → Model → BD
                                       ↓
                                  API Externa
                                  (Bedrock/Twilio/SAT/ERP)
```

### Base de datos — tablas principales

| Tabla | Campos clave | Relaciones |
|-------|-------------|------------|
| proveedores_users | usuario, código_compras, score_* | → contactos, documentos |
| clientes_users | usuario, código_cliente, RFC, límite_crédito | → pedidos, facturas, encuestas |
| admin_users | usuario, correo | — |
| pedidos | folio, código_cliente, productos[JSON], total, estatus | → tracking, facturas |
| productos | código, nombre, precio, stock, categoría | — |
| facturas | folio_cfdi, monto, IVA, total, estatus, vencimiento | → pedido, cliente, proveedor |
| muestras | lote, producto, proveedor, etapa (7 estados) | — |
| encuestas | calificación, tiempo_entrega, calidad_producto | → cliente, pedido |
| tracking_pedidos | estatus, descripción, fecha | → pedido |
| notificaciones | tipo_usuario, título, mensaje, leída | — |
| documentos_proveedor | tipo, archivo, estatus, resultado_validación | → proveedor |
| contactos_proveedor | nombre, rol, teléfono, correo | → proveedor |

---

## PARTE 3: AUTOMATIZACIONES IMPLEMENTADAS

### Formularios activos

| Formulario | Ubicación | Acción |
|-----------|-----------|--------|
| Login proveedor | /login-proveedor | Valida contra API → fallback local → crea sesión |
| Login cliente | /login-cliente | Mismo patrón con ClienteApiService |
| Login admin | /login-admin | Valida contra BD local |
| Registro proveedor | /proveedor/registro | Crea usuario + hash password + aviso privacidad |
| Encuesta satisfacción | /cliente/encuesta | Guarda calificación + tiempo + calidad + comentarios |
| Validación fiscal | /api/empresa | Sube 6 PDFs → extrae texto → valida campos → semáforo |
| Envío de muestras | /muestras | Registra muestra con lote, producto, proveedor |
| Alta de cliente | /admin/cliente/alta | Crea cliente + valida RFC (AJAX) |
| IA Pronóstico | /admin/ia/pronostico | Envía historial a Bedrock → respuesta estructurada |
| IA Inventario | /admin/ia/inventario | Envía stock + demanda a Bedrock |
| IA Proveedor | /admin/ia/proveedor | Compara proveedores con IA |

### Acciones automáticas

| Trigger | Acción | Canales |
|---------|--------|---------|
| Cambio de estatus de pedido | Notificación al cliente | BD + Email + WhatsApp |
| Login fallido (5 intentos) | Bloqueo temporal 60s | Rate limiter |
| Subida de documentos | Extracción PDF + validación automática | DocumentValidationService |
| Muestra aprobada/rechazada | Cambio de etapa automático | Modelo Muestra |

### Paneles de control

| Panel | Métricas |
|-------|---------|
| Admin Dashboard | Total clientes/proveedores, pedidos pendientes/entregados, monto total, productos sin stock, facturas pendientes, encuestas promedio, muestras activas, docs pendientes |
| Proveedor Dashboard | Facturas (pendientes/revisión/aprobadas), pagos (programados/realizados/pendiente), estatus en tiempo real |
| Proveedor Portal | Mi Negocio (tareas), OC abiertas, facturas, onboarding %, calendario, score donut, actividad reciente, forecast productos |
| Proveedor Business | Documentos por vencer, facturas sin subir, pagos próximos, rendimiento productos (top 5 mejor/peor) |

---

## PARTE 4: SEGURIDAD

### Lo que está implementado

| Medida | Implementación | Archivo |
|--------|---------------|---------|
| Passwords hasheados | bcrypt con BCRYPT_ROUNDS=12 | AuthProveedorController |
| Rate limiting login | 5 intentos / 60 segundos | AuthProveedorController, AuthClienteController |
| CSRF protection | @csrf en todos los formularios | Blade templates |
| Soft deletes | No se borran datos, se marcan | Pedido, Producto, Factura, ClienteUser, ProveedorUser |
| Variables en .env | Credenciales nunca en código | config/services.php |
| Bearer token API | Token en header Authorization | ApiTokenAuth middleware |
| Security headers | X-Frame-Options, HSTS, nosniff, XSS | SecurityHeaders middleware |
| HTTPS forzado | URL::forceScheme('https') en producción | AppServiceProvider |
| Validación de inputs | Form Request classes | LoginProveedorRequest, RegisterProveedorRequest |
| RFC validation | Formato offline + SAT online | SatRfcService |
| Retry con backoff | Exponencial en APIs externas | ProveedorApiService |
| Error classification | Distingue auth vs transient errors | ProveedorApiService |

### Lo que falta para nivel empresarial

| Mejora | Prioridad | Descripción |
|--------|-----------|-------------|
| Tests automatizados | Alta | No hay tests. Necesita PHPUnit + feature tests |
| 2FA/MFA | Media | Segundo factor para admin al menos |
| Audit logging | Media | Registrar quién hizo qué y cuándo |
| Rate limiting en API | Media | Solo está en login, falta en /api/salcom/* |
| Session timeout configurable | Baja | Actualmente 120 min fijo |
| Content Security Policy | Baja | Header CSP para prevenir XSS avanzado |
| API versioning | Baja | /api/v1/salcom/* para compatibilidad futura |

---

## PARTE 5: DISEÑO UI/UX — ESTILO APPLE (iOS)

### Design System (`public/css/ios-theme.css`)

#### Paleta de colores
```css
--purple:       #6B3FA0   /* Primario — identidad Salcom */
--purple-dark:  #4A2070   /* Hover/active */
--purple-light: #F3EEFA   /* Fondos suaves */
--purple-mid:   #9C6DD0   /* Acentos */
--gray-text:    #1d1d1f   /* Texto principal (Apple black) */
--gray-muted:   #86868b   /* Texto secundario (Apple gray) */
--gray-soft:    #f5f5f7   /* Fondo (Apple background) */
--green:        #34c759   /* Éxito (iOS green) */
--amber:        #ff9f0a   /* Advertencia (iOS orange) */
--blue:         #007aff   /* Links/info (iOS blue) */
--red:          #ff3b30   /* Error/urgente (iOS red) */
```

#### Tipografía
```css
font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Display', sans-serif;
/* Pesos: 300 (light), 400 (regular), 500 (medium), 600 (semibold), 700 (bold) */
/* Títulos: font-weight: 700, letter-spacing: -0.4px */
/* Body: font-size: 14px, line-height: 1.5 */
/* Labels: font-size: 12px, font-weight: 600, text-transform: uppercase */
```

#### Bordes y sombras
```css
--radius:       12px      /* Cards, inputs */
--radius-lg:    16px      /* Cards grandes */
--radius-pill:  20px      /* Botones */
--shadow-sm:    0 1px 3px rgba(0,0,0,0.04)
--shadow-md:    0 4px 14px rgba(0,0,0,0.06)
--shadow-lg:    0 10px 40px rgba(0,0,0,0.08)
```

#### Transiciones
```css
--transition: all .25s cubic-bezier(.4,0,.2,1);  /* Curva Apple */
/* Hover: transform: scale(1.02) o translateY(-2px) */
/* Active: transform: scale(0.97) */
/* Sidebar links: transform: translateX(2px) */
```

#### Componentes estándar

**Botón primario (pill)**
```css
.btn-primary {
    padding: 14px 24px;
    background: var(--purple);
    color: #fff;
    border: none;
    border-radius: var(--radius-pill);  /* 20px */
    font-size: 16px;
    font-weight: 600;
    transition: var(--transition);
    box-shadow: 0 4px 16px rgba(107,63,160,0.35);
}
.btn-primary:hover { transform: scale(1.03); }
.btn-primary:active { transform: scale(0.97); }
```

**Input (iOS style)**
```css
.ios-field input {
    border: 1px solid var(--border-light);
    border-radius: var(--radius);  /* 12px */
    padding: 13px 16px;
    font-size: 15px;
    background: var(--gray-soft);
    transition: var(--transition);
}
.ios-field input:focus {
    border-color: var(--purple);
    box-shadow: 0 0 0 4px rgba(107,63,160,0.15);
}
```

**Card (frosted glass)**
```css
.card {
    background: var(--white);
    border-radius: var(--radius-lg);  /* 16px */
    border: none;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}
.card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}
```

**Navbar (frosted glass)**
```css
nav.top-nav {
    background: rgba(255,255,255,0.72);
    -webkit-backdrop-filter: saturate(180%) blur(20px);
    backdrop-filter: saturate(180%) blur(20px);
    border-bottom: 1px solid var(--border-light);
}
```

---

## PARTE 6: RESPONSIVE Y EXPERIENCIA

### Breakpoints

| Dispositivo | Breakpoint | Adaptación |
|------------|-----------|------------|
| Desktop | > 900px | Layout completo: sidebar + contenido |
| Tablet | 768px - 900px | Sidebar colapsable, grid 2 columnas |
| Móvil | < 768px | Sidebar oculta, contenido full-width, padding reducido |

### Adaptaciones móvil
- Sidebar se oculta completamente (`display: none`)
- Padding del contenido: 20px 16px (vs 28px 32px en desktop)
- Navbar se comprime a 16px de padding lateral
- Grids de métricas pasan a 1 columna
- Login: padding dinámico con `clamp(20px, 4vh, 48px)`
- Tablas con scroll horizontal implícito

### Portal del proveedor (vista especial)
- Tiene sidebar hover (aparece al pasar el mouse por el borde izquierdo)
- Overlay semi-transparente al abrir
- Cierre automático con delay de 400ms

---

## PARTE 7: ESCALABILIDAD

### Estado actual
- **Usuarios concurrentes**: ~50-100 (SQLite limita escrituras concurrentes)
- **Datos**: ~1,000 registros por tabla (seeders de prueba)
- **APIs externas**: Llamadas síncronas (bloquean el request)

### Para escalar a 500+ usuarios

| Área | Actual | Recomendado |
|------|--------|-------------|
| BD | SQLite | MySQL en AWS RDS |
| Sesiones | Database | Redis |
| Caché | Database | Redis |
| Cola de trabajos | Database | Redis + Horizon |
| Archivos | Local | AWS S3 |
| Email/WhatsApp | Síncrono | Queue (async) |
| IA calls | Síncrono | Queue + cache de resultados |
| Monitoreo | Ninguno | Sentry + Laravel Telescope |

### Para escalar a 5,000+ usuarios
- Load balancer (AWS ALB)
- Múltiples instancias EC2
- Read replicas en RDS
- CDN para assets estáticos (CloudFront)
- Cache de queries frecuentes (Redis)
- Rate limiting por usuario en API

### Modularización
El proyecto ya está bien separado en services. Para reutilizar:
1. Los services son independientes (IaService, DocumentValidationService, etc.)
2. El design system (`ios-theme.css`) es portable
3. Los layouts son plantillas reutilizables
4. El MCP server es un módulo Node.js independiente

---

## PARTE 8: ESTÁNDAR REUTILIZABLE

### Estructura recomendada de proyecto Laravel

```
app/
├── Exceptions/          # Excepciones custom (ProveedorApiException)
├── Http/
│   ├── Controllers/     # Agrupados por módulo (Auth*, Portal*, Admin*)
│   ├── Middleware/       # Auth por rol + SecurityHeaders
│   └── Requests/        # Form Request validation
├── Mail/                # Mailables
├── Models/              # Eloquent con relaciones, casts, soft deletes
├── Providers/           # AppServiceProvider (HTTPS, bindings)
└── Services/            # Lógica de negocio (NUNCA en controllers)

config/
├── services.php         # TODAS las credenciales externas

database/
├── migrations/          # Prefijo fecha, nombres descriptivos
└── seeders/             # Datos de prueba realistas

public/
└── css/
    └── ios-theme.css    # Design system compartido

resources/views/
├── layouts/             # 1 layout por rol (admin, cliente, proveedor)
├── partials/            # Componentes reutilizables (logo, etc.)
├── admin/               # Vistas del admin
├── clientes/            # Vistas del cliente
└── proveedores/         # Vistas del proveedor

routes/
├── web.php              # Rutas con sesión
└── api.php              # Rutas con token
```

### Reglas obligatorias

1. **Lógica de negocio en Services**, nunca en controllers
2. **Validación en Form Requests**, nunca inline en controllers
3. **Credenciales en .env**, referenciadas via `config('services.xxx')`
4. **Soft deletes** en toda tabla de negocio
5. **Índices** en columnas de búsqueda y foreign keys
6. **Rate limiting** en todo endpoint de autenticación
7. **CSRF** en todo formulario POST/PUT/PATCH/DELETE
8. **Security headers** en producción
9. **HTTPS forzado** en producción
10. **Un CSS compartido** para el design system, importado en todos los layouts

### Stack recomendado

| Capa | Tecnología | Por qué |
|------|-----------|---------|
| Backend | Laravel 12+ | Ecosistema maduro, Eloquent, Blade, Queue |
| PHP | 8.2+ | Typed properties, enums, fibers |
| BD | MySQL 8 (RDS) | Confiable, escalable, soporte AWS |
| Cache/Queue | Redis | Rápido, versátil |
| IA | AWS Bedrock | Integración nativa con AWS, Claude |
| Email | SMTP (Gmail/SES) | Simple, confiable |
| WhatsApp | Twilio | API REST, sin SDK pesado |
| Hosting | AWS EC2 + RDS | Mismo ecosistema que el ERP |
| CDN | CloudFront | Assets estáticos |
| Monitoreo | Sentry | Errores en tiempo real |
| CI/CD | GitHub Actions | Automatizar deploy |

---

## PARTE 9: CHECKLIST PROFESIONAL

### Antes de entregar a producción

**Código**
- [ ] Todos los controllers usan Form Request validation
- [ ] Toda lógica de negocio está en Services
- [ ] No hay credenciales hardcodeadas
- [ ] No hay `dd()`, `dump()`, `var_dump()` en el código
- [ ] `APP_DEBUG=false` en producción
- [ ] `APP_ENV=production` en producción

**Seguridad**
- [x] Passwords con bcrypt (BCRYPT_ROUNDS=12)
- [x] Rate limiting en login
- [x] CSRF en formularios
- [x] Security headers (SecurityHeaders middleware)
- [x] HTTPS forzado (AppServiceProvider)
- [x] Bearer token en API
- [ ] Tests de seguridad (penetration testing básico)
- [ ] 2FA para admin (opcional pero recomendado)

**Base de datos**
- [x] Migraciones completas (18 tablas)
- [x] Seeders con datos de prueba
- [x] Soft deletes en tablas de negocio
- [x] Índices en columnas de búsqueda
- [ ] Migrar de SQLite a MySQL
- [ ] Backups automáticos configurados

**UI/UX**
- [x] Design system iOS consistente
- [x] Responsive (móvil, tablet, desktop)
- [x] Frosted glass navbar y sidebar
- [x] Transiciones suaves (cubic-bezier)
- [x] Estados hover/active/disabled en botones
- [x] Formularios con focus ring
- [ ] Testing en Safari, Chrome, Firefox, Edge
- [ ] Testing en iPhone real

**Funcionalidad**
- [x] Login/registro funcionando (3 portales)
- [x] Dashboard admin con KPIs
- [x] Gestión de pedidos con notificaciones
- [x] Validación documental
- [x] Módulo de IA (Bedrock)
- [x] Encuestas de satisfacción
- [x] Score de proveedores
- [ ] Conectar API de Alan (OC, facturas, pagos reales)
- [ ] Conectar credenciales AWS (Bedrock)
- [ ] Probar flujo completo end-to-end

**Infraestructura**
- [ ] Servidor configurado (EC2)
- [ ] BD en la nube (RDS MySQL)
- [ ] Dominio comprado y conectado
- [ ] HTTPS con Let's Encrypt / ACM
- [ ] Firewall (Security Groups)
- [ ] Monitoreo (Sentry o Telescope)
- [ ] Backups automáticos
- [ ] CI/CD pipeline

---

## PARTE 10: MEJORAS RECOMENDADAS

### Prioridad alta (antes de producción)

1. **Tests automatizados** — No hay ni un test. Mínimo: tests de login, tests de API, tests de validación documental. PHPUnit ya está instalado.

2. **Migrar a MySQL** — SQLite no soporta escrituras concurrentes. En producción con múltiples usuarios va a fallar.

3. **Queue para tareas pesadas** — Email, WhatsApp, llamadas a Bedrock y parsing de PDFs deberían ir a una cola (Laravel Queue + Redis).

4. **Conectar datos reales** — Cuando llegue la API de Alan, reemplazar los datos mock en las vistas del portal de clientes y proveedor.

### Prioridad media (después de lanzar)

5. **Audit logging** — Registrar quién cambió qué y cuándo. Útil para compliance y debugging.

6. **Cache de queries** — Las métricas del dashboard admin se calculan en cada request. Cachear con Redis (TTL 5 min).

7. **API documentation** — Swagger/OpenAPI para los 15 endpoints de /api/salcom.

8. **Reportes exportables** — PDF/Excel de pedidos, facturas, encuestas para el admin.

9. **2FA para admin** — Google Authenticator o similar para el panel administrativo.

### Prioridad baja (mejora continua)

10. **PWA** — Convertir el portal en Progressive Web App para que los proveedores lo instalen en su celular.

11. **Dark mode** — Las variables CSS ya están centralizadas, agregar dark mode es cambiar los valores.

12. **Webhooks** — Notificar a sistemas externos cuando cambia el estatus de un pedido.

13. **Dashboard con Chart.js** — Gráficas de ventas por mes, top clientes, tendencias. Preparación para QuickSight.

14. **Internacionalización** — El proyecto está en español. Si Salcom crece a otros países, agregar i18n.

---

## Resumen ejecutivo

Salcom 2.0 es un proyecto **bien arquitectado** con separación clara de responsabilidades (controllers → services → models), un design system consistente estilo iOS, y una base sólida de seguridad. Los principales gaps son: falta de tests, dependencia de APIs externas pendientes (Alan, AWS), y optimizaciones de escalabilidad (MySQL, Redis, Queue).

El proyecto está **listo para demo y staging**. Para producción necesita: MySQL, tests básicos, y las credenciales de AWS/Alan.

**Archivos clave del estándar:**
- `public/css/ios-theme.css` — Design system reutilizable
- `app/Services/` — Patrón de servicios replicable
- `app/Http/Middleware/SecurityHeaders.php` — Seguridad portable
- `bootstrap/app.php` — Registro de middleware como referencia
- `.env.example` — Template de configuración completo
