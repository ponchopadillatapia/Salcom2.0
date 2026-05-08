# Política de Seguridad — Industrias Salcom 2.0

## 1. Política de Contraseñas

| Regla | Valor |
|-------|-------|
| Longitud mínima | 8 caracteres |
| Algoritmo de hash | bcrypt |
| Rounds (producción) | 12 |
| Rounds (testing) | 4 |
| Reutilización | No se valida (pendiente) |
| Expiración | No implementada (pendiente) |

## 2. Control de Acceso

### Roles del sistema

| Rol | Acceso | Middleware |
|-----|--------|-----------|
| Admin | Panel administrativo, gestión de usuarios, reportes | `auth.admin` |
| Cliente | Portal de cliente, pedidos, encuestas | `auth.cliente` |
| Proveedor | Portal de proveedor, OC, documentos, muestras | `auth.proveedor` |
| API | Endpoints REST con Bearer token | `auth.api_token` |

### Reglas de acceso
- Cada rol tiene su propia tabla de usuarios (separación de concerns)
- Las sesiones se almacenan en el driver configurado (file/database/redis)
- Los portales agregan headers `Cache-Control: no-cache, no-store` para evitar que el navegador cachee páginas protegidas
- El timeout de sesión es de 120 minutos (configurable en `config/session.php`)

## 3. Rate Limiting

| Endpoint | Máximo intentos | Ventana | Acción |
|----------|----------------|---------|--------|
| Login proveedor | 5 | 60 segundos | Bloqueo temporal + mensaje |
| Login cliente | 5 | 60 segundos | Bloqueo temporal + mensaje |
| Login admin | 5 | 60 segundos | Bloqueo temporal + mensaje |
| API endpoints | Sin límite (pendiente) | — | — |

## 4. Headers de Seguridad (Producción)

| Header | Valor | Propósito |
|--------|-------|-----------|
| X-Content-Type-Options | nosniff | Previene MIME sniffing |
| X-Frame-Options | SAMEORIGIN | Previene clickjacking |
| X-XSS-Protection | 1; mode=block | Protección XSS del navegador |
| Referrer-Policy | strict-origin-when-cross-origin | Controla qué info se envía en Referer |
| Permissions-Policy | camera=(), microphone=(), geolocation=() | Desactiva APIs innecesarias |
| Strict-Transport-Security | max-age=31536000; includeSubDomains | Fuerza HTTPS (HSTS) |

## 5. Protección CSRF

- Todos los formularios POST/PUT/DELETE incluyen `@csrf`
- Laravel verifica automáticamente el token en cada request
- Requests sin token válido reciben HTTP 419

## 6. Manejo de Secretos

### Reglas
- NUNCA commitear `.env` al repositorio
- Todas las credenciales van en `.env` y se acceden via `config()`
- En producción, considerar AWS Secrets Manager o similar
- Rotar tokens de API periódicamente (manual por ahora)

### Secretos gestionados
- Database credentials
- AWS Bedrock keys (IA)
- Twilio API (WhatsApp)
- SAT/PAC credentials
- Salcom API token
- Mail SMTP credentials
- reCAPTCHA keys

## 7. Auditoría

### Eventos registrados (audit_log)
- Login exitoso/fallido
- Logout
- Rate limiting activado
- Creación de registros
- Edición de registros
- Eliminación de registros
- Eventos de seguridad críticos

### Datos capturados por evento
- Acción realizada
- Módulo afectado
- Tipo y ID de usuario
- IP del cliente
- User-Agent
- Datos antes/después (para ediciones)
- Timestamp

## 8. HTTPS

- Forzado en producción via `URL::forceScheme('https')` en AppServiceProvider
- HSTS header con max-age de 1 año
- Todas las cookies marcadas como `secure` en producción

## 9. Respuesta a Incidentes

### Nivel 1 — Bajo (intentos de login fallidos)
1. Se registra en audit_log con nivel `warning`
2. Rate limiting se activa automáticamente
3. Revisar logs semanalmente

### Nivel 2 — Medio (acceso no autorizado detectado)
1. Se registra en audit_log con nivel `error`
2. Notificar al administrador del sistema
3. Revisar logs de las últimas 24 horas
4. Verificar que no haya datos comprometidos

### Nivel 3 — Crítico (brecha de datos confirmada)
1. Se registra en audit_log con nivel `critical`
2. Desactivar acceso externo inmediatamente
3. Notificar a la dirección de Salcom
4. Rotar TODOS los secretos (.env)
5. Revisar audit_log completo del período afectado
6. Documentar el incidente y las acciones tomadas
7. Implementar correcciones antes de restaurar acceso

## 10. Checklist Pre-Producción

- [ ] `.env` NO está en el repositorio
- [ ] `APP_DEBUG=false` en producción
- [ ] `APP_ENV=production` en producción
- [ ] BCRYPT_ROUNDS=12 en producción
- [ ] HTTPS configurado y funcionando
- [ ] Headers de seguridad activos
- [ ] Rate limiting configurado
- [ ] Logs funcionando y rotando
- [ ] Backups de BD configurados
- [ ] Audit trail activo
- [ ] Composer audit sin vulnerabilidades conocidas
