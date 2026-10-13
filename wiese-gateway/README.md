# Puerta (gateway) hacia la API de Wiese

Permite que el sitio de Salcom en **SiteGround** consulte la API de Wiese, que vive en una
IP **interna** de la oficina (`172.16.1.250:7183`) y que SiteGround no puede alcanzar directo.

```
Navegador del usuario
      ↓
Sitio en SiteGround (35.215.67.229)
      ↓  HTTPS, puerto 443, con un token
Puerta en el servidor de la oficina  (esta carpeta)
      ↓  solo por la red interna
API de Wiese, puerto 7183
```

La puerta deja pasar una consulta **solo si**:
1. La IP de quien llama es la de SiteGround (`35.215.67.229`).
2. Trae el header `X-Salcom-Gateway` con el token correcto.
3. La ruta está en la lista blanca (ver `config.example.php`).

---

## Parte 1 — En el servidor de la OFICINA (quien administra Wiese)

**Requisitos:** PHP 8.2+ con extensión cURL. El servidor debe poder abrir
`http://172.16.1.250:7183/api` desde esa misma máquina.

1. Copia la carpeta `wiese-gateway/` a ese servidor.
2. Duplica `config.example.php` → nómbralo `config.php` (queda FUERA de `public/`).
3. Edita `config.php`:
   - `upstream`: déjalo en `http://172.16.1.250:7183/api`.
   - `token`: una contraseña larga inventada (~40 caracteres). La misma irá en SiteGround.
   - `allowed_ips`: solo `35.215.67.229`.
   - `public_prefix`: `/api`.
4. Publica **solo** la carpeta `wiese-gateway/public` como sitio web, con **HTTPS** en el
   puerto 443. Ejemplo: `https://api.salcom.com`. El *document root* debe ser `public/`,
   así nadie puede descargar `config.php`.
5. En el firewall de la oficina:
   - El puerto 443 de este sitio **solo** acepta conexiones desde `35.215.67.229`.
   - El puerto 7183 **NO** se abre a internet (sigue solo en la red interna).

> Si `config.php` no existe o el token está vacío, la puerta responde "Puerta sin configurar"
> y no reenvía nada.

---

## Parte 2 — En SiteGround (.env de producción de Salcom)

Cuando ya exista la dirección HTTPS de la puerta:

```
PROVEEDOR_API_DOCS_URL=https://api.salcom.com/api
PROVEEDOR_API_URL=https://api.salcom.com/api
CLIENTE_API_URL=https://api.salcom.com/api
WIESE_GATEWAY_TOKEN=la-misma-contraseña-del-config.php
```

- Cambia `https://api.salcom.com` por el nombre real que quede en la oficina.
- El token debe ser **idéntico** al de `config.php`, carácter por carácter.
- `PROVEEDOR_API_SERVICE_USER` y `PROVEEDOR_API_SERVICE_PASSWORD` se quedan igual.

Luego, en SiteGround:
```
php artisan config:cache
```
Sin ese comando, Laravel puede seguir usando la dirección vieja.

---

## Parte 3 — En la oficina (para desarrollar)

Quien trabaja dentro de la red de Wiese puede llamar directo (sin puerta):

```
PROVEEDOR_API_DOCS_URL=http://172.16.1.250:7183/api
WIESE_GATEWAY_TOKEN=
```

Con el token vacío, Salcom NO manda el header y habla directo con el 7183. Eso solo funciona
dentro de la oficina. En SiteGround el token tiene que ir **lleno**.

---

## Comprobar que quedó bien

- Desde una IP que NO sea SiteGround, abrir `https://api.salcom.com/api/Login/Login` → debe
  rechazar (403).
- Desde SiteGround, abrir un proveedor en "Formato para pago" → las facturas de Wiese cargan.
- El puerto 7183, probado desde internet, no debe responder.

## Qué NO hacer
- No abrir el puerto 7183 a internet.
- No poner `http://172.16.1.250:7183/api` en el `.env` de SiteGround (esa IP no existe allá).
- No dejar el token vacío en producción.
- No subir `config.php` ni el token al repositorio.
