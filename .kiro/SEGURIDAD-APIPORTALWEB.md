# Plan de seguridad — APIPortalWeb (5009) + servidor 251

> Guía para endurecer la API C# (APIPortalWeb) y el servidor 251. Los pasos 1-6 son tuyos
> (Said). Los pasos 7-11 son de Genaro (red/firewall/monitoreo), pero aquí están explicados
> para que puedas responder cualquier pregunta. Al final: lo que pidió Alan (HTTPS en Kestrel
> + certificado SAN + token/JWT) mapeado a estos pasos.

## Contexto actual (lo que hay HOY, para saber de dónde partimos)
- APIPortalWeb corre en el 251, puerto 5009, **HTTP plano** (sin cifrar), **sin autenticación**.
- SiteGround le pega vía NAT a 189.254.241.195:5009 → 172.16.1.251:5009 → reenvía al 250 Wiese (7183).
- PROBLEMA 1: cualquiera que conozca la IP puede llamar los endpoints (no hay candado).
- PROBLEMA 2: viaja sin HTTPS (se puede espiar en la red).
- PROBLEMA 3: hay un secreto HARDCODEADO en el código: ApiHelperLogin con codigo="CONSULTA",
  pwd="12345" dentro de PagoProveedorController.cs. Eso NO debe estar en el código.

---

## PASO 1 — Documentar endpoints y permisos (papeleo, no código)
Hacer una tabla con cada endpoint: ruta, método, qué recibe, quién puede llamarlo.

| Endpoint | Método | Entrada | ¿Modifica datos? | Quién |
|---|---|---|---|---|
| /api/PagoProveedor/ListarDocumentos | GET | fechaInicial, fechaFinal | No (solo lee) | SiteGround |
| /api/PagoProveedor/ListarProveedores | GET | — | No | SiteGround |
| /api/PagoProveedor/BuscarPorCodigo | GET | codigo | No | SiteGround |
| /api/PagoProveedor/BuscarPorRFC | GET | rfc | No | SiteGround |
| /api/PagoProveedor/CrearPago | POST | folioQuicken, codigoProveedor, importe | SÍ (crea pago en Contpaqi) | SiteGround |
| /api/PagoProveedor/CrearPagoYSaldar | POST | codigoProveedor, importe, serie, folio | SÍ | SiteGround |
| /api/PagoProveedor/FacturasProveedor | GET | codigo, concepto | No | SiteGround |

Regla: los GET solo leen; los POST (CrearPago*) SÍ mueven dinero en Contpaqi → son los más sensibles.
Todos deben exigir token (paso 3). Hoy TODOS están abiertos.

---

## PASO 2 — Validar todas las entradas
En cada endpoint, revisar que lo que llega sea válido ANTES de usarlo:
- Fechas: que sean fechas reales (ya se reciben como DateTime, bien).
- codigo/rfc: no vacíos, longitud razonable, sin caracteres raros.
- importe: > 0.
- Devolver SOLO los campos necesarios (no volcar objetos internos completos).
Esto evita inyección y errores. (En C# con parámetros tipados ya hay algo; reforzar los POST.)

---

## PASO 3 — Autenticación y autorización (EL TOKEN / JWT que dijo Alan) ⭐
Esto es lo que Alan llama "que la API funcione con token público" / "JWT como en Swagger".
JWT = un token firmado que el cliente manda en cada petición; la API lo verifica.

Hay 2 niveles posibles; para tu caso (SiteGround es el único cliente) basta el nivel A, pero
el B es lo "completo" que pide Alan:

- NIVEL A (token compartido simple): SiteGround manda un header `X-Salcom-Gateway: <token>`.
  La API revisa que coincida con el token guardado (en variable de entorno). Si no, 401.
  VENTAJA: Laravel YA está preparado (config services.proveedor_api.gateway_token manda ese header).
- NIVEL B (JWT, lo que pidió Alan): la API expone /login que devuelve un JWT firmado; cada
  endpoint exige `[Authorize]` y el header `Authorization: Bearer <jwt>`. En Swagger aparece
  el botón "Authorize" (candado) para pegar el token.

(En este documento implementamos AMBOS: el header simple como candado de entrada + JWT para
los endpoints, para cumplir lo de Alan. Ver sección CÓDIGO abajo.)

Regla OWASP importante: un usuario autenticado NO debe poder ver datos de otro cambiando un ID.
En tu API casi todo es consulta global a Wiese, pero igual: validar que el token tenga permiso.

---

## PASO 4 — Proteger los secretos (sacar CONSULTA/12345 del código) ⭐
Hoy en PagoProveedorController.cs está hardcodeado:  codigo="CONSULTA", pwd="12345".
Eso debe salir del código y del repo. Se mueve a appsettings/variables de entorno del 251.
- NUNCA secretos en el código ni en git.
- Credenciales de producción distintas a las de prueba.
- El token JWT y el token de gateway también van en variables de entorno, no en el código.

---

## PASO 5 — Servidor de producción endurecido (251)
- Mantener Windows y .NET actualizados.
- Desactivar modo desarrollo: ASPNETCORE_ENVIRONMENT=Production (hoy está en Development para
  ver Swagger; en producción Swagger se restringe — ver paso 9).
- El servicio (NSSM) ya tiene arranque automático y reinicio ante fallos. Bien.
- IDEAL: que el servicio NO corra como administrador, sino con un usuario sin privilegios.

---

## PASO 6 — Dominio, certificado y HTTPS ⭐ (lo que detalló Alan)
Lo que pidió Alan, traducido:
- Instalar un CERTIFICADO válido en el 251 con su clave privada y un nombre DNS en el SAN
  (Subject Alternative Name). O sea, la API deja de ser http:// y pasa a https://.
- Permitir que la cuenta que ejecuta el .exe pueda LEER la clave privada del certificado.
- Configurar Kestrel con UseHttps(), tomando el certificado del ALMACÉN DE CERTIFICADOS de
  Windows (así NO se guarda un archivo .pfx + contraseña en el código).
- DESHABILITAR los listeners HTTP accesibles por red (que ya no escuche en 5009 http plano
  hacia afuera; solo https, o http solo en localhost para el proxy).

DECISIÓN DE ARQUITECTURA (consultar con Genaro):
- Opción 1: Kestrel con HTTPS directo (certificado en el 251). Es lo que describe Alan.
- Opción 2 (recomendada OWASP paso 6): un PROXY INVERSO (IIS o Nginx) delante que tenga el
  certificado y reenvíe al Kestrel que escucha SOLO en localhost. Así el 5009 no se expone.
Ambas cumplen; la 2 es más estándar en producción. Lo platica Genaro.

Para el certificado necesitas un NOMBRE DNS (ej. api-pagos.salcomlink.mx) apuntando a la IP
pública. Eso lo configura Genaro (DNS) + el certificado (Let's Encrypt gratis, o uno comprado).

---

## PASOS 7-11 (Genaro) — resumen para que respondas
7. Abrir SOLO los puertos necesarios en el firewall del 251 Y en el del proveedor:
   - 443 (HTTPS) → desde internet o solo IPs autorizadas.
   - 80 (HTTP) → solo si se usa para redirigir a https o validar el certificado.
   - 5009 (la API) → SOLO desde el proxy / red privada, NO desde internet directo.
   - 250 Wiese (7183) → solo desde el 251.
   - RDP/SSH → solo por VPN o IPs de administración.
8. Límites: rate limiting (máx. peticiones), tamaño máximo del body, timeouts, CORS restringido
   solo al origen de SiteGround (salcomlink.mx). CORS NO reemplaza el token.
9. Errores y admin: no mostrar trazas ni secretos; restringir Swagger/métricas en producción.
10. Monitoreo: registrar accesos y errores SIN guardar tokens/contraseñas; alertas; respaldos
    con restauración probada; forma de volver a la versión anterior.
11. Verificar desde fuera: que solo los puertos previstos respondan, que HTTPS funcione, que sin
    token falle (401), que los límites apliquen.

---

## CÓDIGO — qué cambiar en APIPortalWeb (pasos 3, 4, 6)
Ver la conversación: se implementa paso a paso. Resumen de archivos a tocar:
- APIPortalWeb.csproj → agregar paquete Microsoft.AspNetCore.Authentication.JwtBearer.
- appsettings.json → sección Jwt (Issuer, Audience) + Gateway (SIN el secreto; el secreto va en
  variable de entorno del 251).
- Program.cs → AddAuthentication/JWT + middleware de token de gateway + UseHttps en Kestrel +
  UseAuthentication/UseAuthorization + Swagger con botón Authorize.
- PagoProveedorController.cs → [Authorize] en la clase; sacar CONSULTA/12345 a configuración.
- Variables de entorno en el 251 (NSSM AppEnvironmentExtra): JWT_KEY, GATEWAY_TOKEN,
  WIESE_USER, WIESE_PWD.
