# Cómo subir cambios a producción (guía fija)

> Guía de despliegue para Salcom. Hay DOS sistemas separados, cada uno con su flujo.
> NO los mezcles. Casi siempre usarás el FLUJO A (la página).

---

## Los dos sistemas

| Sistema | Qué es | Lenguaje | Producción |
|---|---|---|---|
| **Laravel (Salcom2.0)** | La página salcomlink.mx | PHP | **SiteGround** |
| **APIPortalWeb (5009)** | Gateway/puente a Wiese | C# | **Servidor 251** |

Regla de oro de git: **push sube** (desde la laptop), **pull baja** (en el server).
Nunca editar directo en el servidor. Siempre: laptop → GitHub → servidor.

---

## FLUJO A — Cambios en la PÁGINA (Laravel → SiteGround)  [el de siempre]

### En la LAPTOP
1. Cambiar el código (en Kiro).
2. Probar en local (XAMPP, abrir la página en el navegador).
3. Subir a GitHub:
   ```
   git add <archivos que cambiaste>
   git commit -m "descripcion corta"
   git push
   ```

### En SITEGROUND (por SSH)
`ssh u2166-zdajwthr5gfb@ssh.salcomlink.mx -p 18765`  (clave siteground_key, pass SALCOMbicho007)

4. Traer el cambio:
   ```
   cd ~/www/salcomlink.mx/public_html
   git pull
   ```
5. Limpiar caché SEGÚN lo que cambiaste:
   - Vistas (.blade.php) → `php artisan view:clear`
   - Rutas (web.php)     → `php artisan route:clear`
   - Config / .env       → `php artisan config:cache`
6. Recargar la página y verificar.

PHP NO se compila: solo subir, bajar y limpiar caché.

---

## FLUJO B — Cambios en el GATEWAY (C# APIPortalWeb → Servidor 251)  [raro]

Solo cuando haya que tocar endpoints del puente a Wiese. C# SÍ se compila.

### En la LAPTOP
1. Cambiar el código C#.
2. Compilar (publish):
   ```
   cd C:\Users\IT\source\repos_git\APIPortalWeb\APIPortalWeb
   dotnet publish -c Release -r win-x86 --self-contained false -o C:\APIPortalWeb_publish
   ```

### En el SERVIDOR 251 (ORDEN IMPORTANTE: detener ANTES de copiar)
3. Detener el servicio (si no, Windows no deja reemplazar el .exe "en uso"):
   ```
   Stop-Service APIPortalWeb
   ```
4. Copiar C:\APIPortalWeb_publish (laptop) → C:\APIPortalWeb (251), REEMPLAZANDO.
5. Arrancar de nuevo el servicio (ya con el código nuevo):
   ```
   Start-Service APIPortalWeb
   ```
6. Verificar:
   ```
   Get-Service APIPortalWeb        # debe decir Running
   ```
   y abrir http://localhost:5009/swagger

NO hay git pull en el 251: el C# se compila en la laptop y se copia el resultado.
(Opcional: git push del código C# solo como respaldo/historial.)

OJO: `Restart-Service` NO sirve aquí porque no te da el hueco para copiar los
archivos (el .exe sigue en uso). Por eso es Stop → copiar → Start.

### Comandos de manejo del servicio (día a día, en el 251)
```
Get-Service APIPortalWeb       # ver estado (Running / Stopped)
Start-Service APIPortalWeb     # arrancar
Stop-Service APIPortalWeb      # detener
Restart-Service APIPortalWeb   # reiniciar (solo si NO cambiaste archivos)
```

---

## En una frase
- **Página (Laravel):** editar → push → `git pull` en SiteGround → limpiar caché. (No se compila.)
- **Gateway (C#):** editar → `dotnet publish` → copiar al 251 → `Restart-Service`. (Se compila.)
