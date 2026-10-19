# Estructura del proyecto Salcom 2.0 — guía a fondo

> Explica las carpetas del hosting (SiteGround) y del proyecto Laravel, para qué sirve cada una,
> dónde se guardan los datos, y cómo viaja una petición (servicios, puertos, IPs).

---

## PARTE A — Carpetas de SiteGround (nivel HOSTING)

Lo que ves arriba en el gestor de archivos de SiteGround:

- **public_html** → carpeta que el servidor web publica a internet. Todo lo que entra por
  https://salcomlink.mx apunta aquí. DENTRO está el proyecto Laravel completo. El dominio
  normalmente se sirve desde la subcarpeta `public/` (donde está index.php); el resto del
  proyecto queda más adentro, protegido del navegador.
- **logs** → registros del servidor web (Apache/Nginx): accesos, errores HTTP (404/500).
  Distinto de los logs de Laravel (esos están en storage/logs). Útil si el sitio da error 500.
- **web_stats / awstats** → estadísticas de visitas que genera SiteGround solo. Informativo.

Resumen: tu proyecto vive en public_html. logs y web_stats los llena el hosting, no son tu código.

---

## PARTE B — Carpetas del proyecto LARAVEL

- **app/** → el cerebro (código PHP).
  - app/Http/Controllers/ → controladores (reciben peticiones y deciden qué hacer).
  - app/Models/ → modelos (representan tablas de la BD: PagoProveedor, Factura...).
  - app/Services/ → lógica reutilizable (ProveedorApiService llama al 5009).
  - app/Mail/ → plantillas de correo.
  - app/Jobs/ → tareas en segundo plano.
  - app/Console/Commands/ → comandos de artisan (ej. pagos:limpiar-formatos).
  - app/Http/Middleware/ → "porteros" (AutenticacionAdmin revisa sesión/permisos).
- **routes/web.php** → mapa de URLs: qué URL ejecuta qué controlador.
- **resources/views/** → vistas Blade (el HTML con {{ }}). La página que ve el usuario.
- **config/** → configuración (services.php, mail.php...). Lee valores del .env.
- **database/migrations/** → "plano" de las tablas (columnas, tipos). seeders/ = datos de ejemplo.
- **public/** → puerta pública. public/index.php es el punto de entrada de TODA petición.
  Única carpeta que el navegador toca directo (imágenes, css/js compilados).
- **storage/** → archivos y registros.
  - storage/logs/laravel.log → errores de la app (aquí miramos cuando algo falla).
  - storage/app/ → archivos subidos (PDFs de formatos, comprobantes).
  - storage/framework/ → cachés de vistas, sesiones.
- **vendor/** → librerías de terceros (Laravel, DomPDF...). NO se edita ni se sube a git;
  se regenera con `composer install`.
- **bootstrap/** → arranque de la app; bootstrap/cache guarda cachés de config/rutas.
- **.env** → secretos (BD, correo, Wiese, tokens). Único por entorno, NO se sube a git.
- **artisan** → herramienta de comandos (php artisan ...).

---

## PARTE C — Recorrido de una petición (servicios, puertos, IPs)

Ejemplo: alguien abre "Formato para pago" en salcomlink.mx

1. Navegador pide https://salcomlink.mx/admin/pagos  (HTTPS = puerto 443).
2. Llega a SiteGround → entra por public/index.php (punto de entrada).
3. routes/web.php: la URL /admin/pagos la maneja AdminPagosController@index.
4. Middleware AutenticacionAdmin revisa sesión y permisos.
5. AdminPagosController pide las facturas a...
6. ProveedorApiService (app/Services/) hace GET a:
   http://189.254.241.195:5009/api/PagoProveedor/ListarDocumentos   (el 5009 en el 251, vía NAT)
7. El 5009 (251) llama a la API Wiese 250: http://172.16.1.250:7183
8. El 250 devuelve el JSON de facturas → sube de regreso al controlador.
9. El controlador pasa los datos a la vista resources/views/admin/pagos/index.blade.php.
10. La vista genera el HTML y lo manda al navegador.
11. Si hay que guardar (ej. un formato), el controlador usa un Modelo (PagoProveedor) que
    escribe en la base de datos MySQL de SiteGround.

### Dónde se guarda cada cosa
- Datos (formatos, facturas, usuarios) → base de datos MySQL de SiteGround (puerto 3306 interno;
  credenciales en .env: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).
- Archivos subidos (PDFs) → storage/app/ en el disco de SiteGround.
- Facturas de Wiese → NO se guardan; se piden en vivo al 5009 cada vez.

### Mapa de IPs y puertos
- SiteGround (página) → HTTPS 443. Sale a internet por 35.215.x.x.
- Gateway 5009 (servidor 251) → 189.254.241.195:5009 (público, vía NAT) / 172.16.1.251:5009 (interno).
- API Wiese 250 → 172.16.1.250:7183 (interno, no se publica).
- Base de datos MySQL → en SiteGround, puerto 3306 (interno del hosting).
