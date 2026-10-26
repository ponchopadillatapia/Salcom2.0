# Instrucción para REDES (Daniel/Genaro) — publicar el API en el puerto 5443

> El servicio YA está corriendo y probado. Solo falta la configuración del FortiGate
> para que el puerto 5443 entre desde internet. Esto es puramente de red.

## Estado actual (confirmado, del lado del servidor — NO tocar)
- Servidor **172.16.1.251**, servicio `APIPortalWeb` **corriendo**.
- Puerto **5443** escuchando (netstat: LISTENING).
- Firewall de Windows del 251: regla "API HTTPS 5443" habilitada.
- Certificado HTTPS válido (Let's Encrypt, dominio api-pagos.salcomlink.mx).
- Probado DENTRO del 251: `https://localhost:5443/login` responde correctamente.
- CONCLUSIÓN: el servidor está 100% listo. Lo único que falta es el NAT/policy del fórti.

## Lo que falta en el FortiGate (FortiNave3, 172.16.1.1)

Objetivo: que `https://api-pagos.salcomlink.mx:5443` (IP pública 189.254.241.195)
llegue a `172.16.1.251:5443`.

### 1. VIP (Virtual IP) con PORT FORWARDING
- Crear un VIP **dedicado** para esto (NO reutilizar "chapalita web 2", que es de otro servicio
  y puede romperse).
- Interface: wan1
- External IP: **189.254.241.195**
- Mapped IP: **172.16.1.251**
- **Port Forwarding: ACTIVADO**
  - Protocolo: TCP
  - External Service Port: **5443**
  - Map to Port: **5443**

### 2. FIREWALL POLICY (esto es lo que seguramente falta)
Crear una política de entrada:
- Incoming interface: **wan1**
- Outgoing interface: la LAN interna donde vive el 251
- Source: all (o restringir a la IP de SiteGround 35.215.67.229)
- **Destination: el VIP del paso 1**
- **Service: TCP 5443** (o un servicio custom que use el puerto 5443)
- Action: **ACCEPT**
- NAT: según como manejen las demás políticas de entrada
- Que quede **habilitada**.

> NOTA: en FortiGate el VIP solo traduce la dirección. SIN una política que use ese VIP,
> el tráfico NO pasa aunque el port forwarding esté encendido. Por eso, aunque se probó
> con port forwarding activado, no entró: falta/está mal la POLICY.

## Cómo se verifica que quedó bien (lo hace el programador)
Desde fuera de la red de la oficina (ej. celular con datos), entrar a:
`https://api-pagos.salcomlink.mx:5443/login`
Debe responder (no "no se puede conectar"). Con eso queda confirmado.

## Importante
- NO urge: producción aún no está en uso (módulos en desarrollo).
- NO tocar el VIP "chapalita web 2" (es de otro servicio).
- El lado del servidor ya está listo; no hay que reinstalar ni reconfigurar nada ahí.
