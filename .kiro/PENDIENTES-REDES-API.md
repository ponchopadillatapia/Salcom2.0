# Pendientes de RED para el API de pagos (251) — para el equipo de redes/sistemas

> Contexto: el API de pagos (proyecto APIPortalWeb) corre en el servidor **172.16.1.251**.
> Ya tiene autenticación (JWT) y HTTPS con certificado válido. Falta SOLO la parte de red
> (firewall / NAT) para reabrirlo de forma segura. Esto es lo que necesito de ustedes.

## Lo que YA está hecho (del lado de la app, no tocar)
- **Autenticación JWT:** sin token válido, los endpoints responden 401.
- **HTTPS:** certificado real de Let's Encrypt para `api-pagos.salcomlink.mx`, instalado en el
  almacén de Windows del 251 (LocalMachine\My). El API escucha cifrado en el puerto **5443**.
- **Secretos:** fuera del código, en variables de entorno del servicio (NSSM).
- **DNS:** `api-pagos.salcomlink.mx` ya apunta a la IP pública `189.254.241.195` (registro A en SiteGround).

## Lo que necesito de REDES (cuando se reabra la conexión pública)

### 1. Firewall / NAT del 251
- **ABRIR** el puerto **5443 (HTTPS)** desde internet → al 172.16.1.251:5443.
  (Es el nuevo puerto cifrado por donde debe entrar SiteGround.)
- **CERRAR** el puerto **5009 (HTTP plano)** al público → ya no debe ser accesible desde internet.
  (Se dejó temporalmente para pruebas internas; en producción no debe exponerse sin cifrar.)
- **Ideal:** permitir el 5443 SOLO desde la IP de SiteGround (**35.215.67.229**), no desde todo internet.

### 2. Wiese (servidor 250)
- El puerto de Wiese (**172.16.1.250:7183**) debe ser accesible SOLO desde el 251, nunca desde internet.

### 3. Accesos de administración
- **RDP / escritorio remoto** del 251: solo por VPN o IPs de administración, no abierto a internet.

## Datos técnicos de referencia

| Qué | Dato |
|---|---|
| Servidor | 251 — IP interna 172.16.1.251 |
| IP pública (NAT) | 189.254.241.195 |
| Dominio del API | api-pagos.salcomlink.mx |
| Puerto HTTPS (nuevo, abrir) | 5443 |
| Puerto HTTP viejo (cerrar al público) | 5009 |
| Wiese (solo desde el 251) | 172.16.1.250:7183 |
| Cliente autorizado (SiteGround) | 35.215.67.229 |

## Cómo verificar que quedó bien (desde fuera, al final)
- `https://api-pagos.salcomlink.mx:5443` responde (con candado válido).
- El 5009 ya NO responde desde internet.
- Sin token → 401. Con token → responde datos.
- El 250 (Wiese) no responde desde internet.
