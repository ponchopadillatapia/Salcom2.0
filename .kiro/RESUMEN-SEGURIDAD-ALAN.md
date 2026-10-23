# Resumen de seguridad del API 5009 — para presentar a Alan

> Hoja de apoyo para explicar y DEMOSTRAR lo que se hizo. Úsala para responder dudas
> y para la demo en vivo en Swagger.

---

## 1. Qué decirle a Alan (resumen corto)

Ya quedó la seguridad del API 5009 (APIPortalWeb):

- **JWT**: el API tiene un `/login` que entrega un token de 1 hora. Todos los endpoints
  exigen ese token; sin él responden **401**. En Swagger aparece el candado **Authorize**.
- **Secretos fuera del código**: la contraseña `CONSULTA/12345` que estaba escrita en
  `PagoProveedorController.cs` ya NO está; vive en variables de entorno del servidor.
  Igual la llave del JWT y el token de entrada. Nada de eso está en el código ni en GitHub.
- **SiteGround** ya se autentica solo: pide el token a `/login` y lo manda en cada llamada.
- **Filtro por IP** listo (dormido); se activa cuando se reabra la conexión pública.
- Falta **HTTPS**, que depende del certificado que debe instalar Genaro.

---

## 2. Demo en vivo (Swagger del 251) — el contraste que lo prueba

1. Abrir Swagger en el 251. Mostrar el botón **Authorize** (candado) arriba.
2. `GET /ListarProveedores` SIN token → Execute → **401 Unauthorized** (ya está cerrado).
3. `POST /login` con el body:
   ```json
   { "token": "surdn5jWYCoPZ7lAJzxkETtgibmOMLS6pRQaB18h" }
   ```
   → devuelve el JWT (`eyJ...`). Copiar ese token.
4. Botón **Authorize** → pegar el `eyJ...` → confirmar.
5. `GET /ListarProveedores` otra vez, ahora CON token → **devuelve los datos**.

El contraste 401 (sin token) → datos (con token) es la prueba visual de que el candado funciona.

---

## 3. Tokens por entorno (OJO: cada máquina tiene el suyo)

| Entorno | Token de entrada (GATEWAY_TOKEN) para `/login` |
|---|---|
| **Laptop (local)** | `token-dev-local` (valor por defecto del código, sin variable configurada) |
| **Servidor 251** | `surdn5jWYCoPZ7lAJzxkETtgibmOMLS6pRQaB18h` (configurado con NSSM) |

El JSON del login es siempre: `{ "token": "<el que corresponda>" }`.
La respuesta trae: `{ "token": "eyJ...(JWT)", "expira_en_segundos": 3600 }`.

---

## 4. Datos técnicos (para responder lo que pregunten)

| Qué | Dato |
|---|---|
| Servidor | 251, IP interna `172.16.1.251` |
| IP pública (NAT) | `189.254.241.195` → redirige al 251 |
| Puerto del API | `5009` (HTTP; HTTPS sería 5443 con certificado) |
| Servicio Windows | `APIPortalWeb` (NSSM en `C:\nssm\nssm-2.24\win32\nssm.exe`) |
| Carpeta del API en el 251 | `C:\APIPortalWeb` |
| Dónde se guardan los secretos | Variables de entorno del servicio (NSSM `AppEnvironmentExtra`): `JWT_KEY`, `GATEWAY_TOKEN`, `WIESE_USER`, `WIESE_PWD` |
| A dónde reenvía | Wiese 250: `172.16.1.250:7183` |
| Cliente que lo consume | SiteGround (producción), IP `35.215.67.229` |
| Proyecto C# (código) | `C:\Users\IT\source\repos_git\APIPortalWeb\APIPortalWeb\` |

### Cómo se despliega al 251
1. En la laptop: `dotnet publish -c Release -r win-x86 --self-contained false -o C:\APIPortalWeb_publish`
2. `Stop-Service APIPortalWeb` en el 251.
3. Copiar `C:\APIPortalWeb_publish` → `C:\APIPortalWeb` (reemplazar).
4. `Start-Service APIPortalWeb`.

---

## 5. Qué falta (y de quién depende)

- **HTTPS + certificado** → GENARO: certificado SSL con DNS en el SAN (ej.
  `api-pagos.salcomlink.mx`) instalado en el almacén de Windows del 251, + permiso de
  lectura de la clave privada para la cuenta del servicio. El código ya está escrito
  (comentado en `Program.cs`); solo se descomenta cuando el certificado esté.
- **Activar el filtro por IP** → cuando se reabra la conexión pública (ver qué IP llega por el NAT).
- **Pasar a Production** (ocultar Swagger al público) → segundos antes de reabrir.
- **Regenerar los 2 tokens** (JWT_KEY y GATEWAY_TOKEN) → antes de reabrir (se vieron en notas/chat).
