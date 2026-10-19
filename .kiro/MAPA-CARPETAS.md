# Mapa de carpetas y archivos de Salcom 2.0 (para control)

> Qué hay en cada carpeta/archivo del proyecto Laravel. ⭐ = importante en el día a día.
> 🔒 = no tocar.

## Niveles en SiteGround
salcomlink.mx → { logs (servidor web), public_html (TU proyecto), webstats (estadísticas) }
Todo el proyecto vive en public_html.

## CARPETAS

- **.git** 🔒 — historial de Git (versiones). Hace funcionar push/pull. No tocar.
- **.github** — workflows de GitHub (seguridad automática).
- **.kiro** ⭐ — memoria del proyecto: DECISIONES.md, COMO-SUBIR-CAMBIOS.md, ESTRUCTURA-PROYECTO.md, MAPA-CARPETAS.md.
- **app** ⭐ — el CEREBRO (código PHP):
  - Console/ → comandos artisan (ej. pagos:limpiar-formatos)
  - Exceptions/ → manejo de errores
  - Http/Controllers/ → la lógica (recibe peticiones, decide)
  - Http/Middleware/ → porteros (AutenticacionAdmin revisa sesión/permisos)
  - Http/Requests/ → validaciones de formularios
  - Jobs/ → tareas en segundo plano
  - Mail/ → plantillas de correo
  - Models/ → representan tablas de la BD (Factura, PagoProveedor)
  - Providers/ → arranque de Laravel
  - Services/ → lógica reutilizable (ProveedorApiService, AltaFacturaValidationService)
- **bootstrap** — arranque + bootstrap/cache (config cacheada). No editar a mano.
- **config** — configuración (services.php, mail.php, filesystems.php, facturas.php). Lee del .env.
- **database** — migrations/ (plano de tablas), seeders/ (datos ejemplo), factories/.
- **docs** — documentación.
- **mcp-salcom** — módulo aparte (integración MCP/Kiro), no es del flujo web normal.
- **public** ⭐ — puerta pública (única que el navegador toca directo):
  - index.php → punto de entrada de TODA petición
  - storage/ → symlink (atajo) a los archivos públicos
  - css/js compilados, imágenes
- **resources** ⭐ — lo que ve el usuario:
  - views/ → pantallas Blade (HTML). Aquí editas cómo se ve cada página.
  - css/, js/ → estilos y scripts fuente
- **routes** — web.php = mapa de URLs (qué URL → qué controlador).
- **storage** ⭐ — archivos y logs:
  - app/public/ → archivos subidos VISIBLES (facturas, formatos, comprobantes, fotos)
  - app/private/ → archivos SENSIBLES (no visibles al navegador)
  - logs/laravel.log → errores de la app (mirar aquí cuando algo falla)
  - framework/ → cachés de vistas y sesiones
  - backups/ → respaldos
  - OJO: hay muchos scripts sueltos de pruebas viejas en storage/app/ (fix_*, check_*, peek_*,
    auditoria_*). No afectan, pero ensucian. Se pueden limpiar con cuidado.
- **tests** — pruebas automáticas. No afecta producción.
- **vendor** 🔒 — librerías de terceros (Laravel, DomPDF...). Se regenera con composer install. No tocar.

## ARCHIVOS DE LA RAÍZ

Configuración:
- .editorconfig, .gitattributes, .gitignore (evita subir .env y vendor), .htaccess (reglas Apache)
- composer.json/.lock → dependencias PHP
- package.json/.lock → dependencias frontend
- vite.config.js → compilar CSS/JS
- phpstan.neon, phpunit.xml → calidad y pruebas

Secretos (.env) ⭐:
- .env → secretos REALES (BD, correo, Wiese, tokens). El que usa producción.
- .env.backup → respaldo del .env
- .env.example / .env.mail.example / .env.production.example → plantillas (sin valores reales)

Herramienta:
- artisan ⭐ → se ejecuta con `php artisan ...`

Documentación (.md):
- README.md, AUDITORIA-SALCOM2.md, DEPLOY-SERVIDOR.md, PREGUNTAS-PARA-PLANTA.md, RESPUESTAS-AWS.md

Archivos de PRUEBAS que ensucian (NO son del sistema, se pueden limpiar con cuidado):
- analizar_prefijos.py, analizar_prefijos_v2.py, fix_e4.py, fix_mpi.py
- test-output.txt, test_200_alta.php, clasificaciones_opciones.json, salcom20
- deploy.sh (este sí puede ser útil: script de despliegue)

## Reglas de oro
- Los SECRETOS van en .env (no se sube a git, no sale al HTML).
- Los ARCHIVOS subidos van en storage/app/public (o /private los sensibles). La BD guarda solo la RUTA.
- vendor/ y .git NO se tocan.
- Si algo falla, el primer lugar a mirar es storage/logs/laravel.log.
