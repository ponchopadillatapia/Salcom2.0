---
inclusion: manual
---

# Patrón de notificación "visto" (estilo WhatsApp)

Cuando el usuario pida "aplica el patrón de notificación 'visto'" (o "patrón visto estilo WhatsApp")
a un módulo, implementa SIEMPRE este comportamiento completo:

## Comportamiento

1. **Orden**: los items más recientes arriba (como van llegando).
2. **Punto azul** (pulsante) en el nivel padre (proveedor, empleado, cliente, etc.) cuando
   tiene items nuevos que aún NO se han visto.
3. **Punto rojo** (pulsante) en cada item individual que no se ha visto.
4. **Marcar como visto al abrir**: cuando el usuario abre el nivel padre (entra a su detalle),
   sus items se marcan como vistos.
5. **Los puntos se apagan solos** una vez visto. Mientras están activos, pulsan (animación blink).

## Cómo implementarlo

- **Persistencia del "visto"**: usar un flag booleano. Si el modelo ya tiene una columna JSON
  (ej. `datos_confirmacion`, `resultado_validacion`, `datos`), guardar ahí un flag tipo
  `visto_archivero` / `revision_vista` — SIN migración. Solo crear migración/columna si no hay
  ningún campo JSON disponible.
- **Marcar vistos**: en el controlador del detalle (nivel padre), antes de calcular los "no vistos"
  para el punto rojo, capturar los IDs no vistos; luego marcar todos como vistos con `saveQuietly()`
  (no disparar eventos). No cambiar estatus ni ninguna otra lógica de negocio.
- **Conteo del nivel padre**: contar cuántos items tienen el flag en false/ausente → si > 0, punto azul.

## Estilos CSS (reutilizar)

```css
/* Punto azul (nivel padre con items nuevos) */
.dot-azul{display:inline-block;width:9px;height:9px;border-radius:50%;background:#2563eb;margin-right:7px;vertical-align:middle;animation:dotBlink 1.3s ease-in-out infinite}
/* Punto rojo (item sin ver) */
.dot-rojo{display:inline-block;width:8px;height:8px;border-radius:50%;background:#dc2626;margin-right:7px;vertical-align:middle;animation:dotBlink 1.2s ease-in-out infinite}
@keyframes dotBlink{0%,100%{opacity:1}50%{opacity:.3}}
```

## Formato para pedirlo

> "Aplica el patrón de notificación 'visto' (estilo WhatsApp) a [módulo]: punto azul en
> [nivel padre] cuando tenga [items] nuevos sin ver, y punto rojo en [cada item] sin ver
> dentro. Al abrir [nivel padre], se marcan como vistos y los puntos se apagan."

## Dónde ya está implementado (referencia)

- Expediente Fiscal / documentos en revisión manual (`resultado_validacion['revision_vista']`).
- Archivero de Expedientes de Pago: punto azul en proveedor, punto rojo en expediente
  (`datos_confirmacion['visto_archivero']`).
