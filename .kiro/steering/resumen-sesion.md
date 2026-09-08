---
inclusion: manual
---

# Formato de resumen de sesión

Cuando el usuario pida un resumen, recap o "qué hicimos", entrega un resumen
de los cambios realizados en la sesión siguiendo EXACTAMENTE este formato.

## Reglas de formato

1. Título: `## Resumen de lo que hicimos` (o el área principal si aplica).
2. Lista **numerada**, un punto por cada cambio o tema distinto.
3. Cada punto lleva un **título en negrita** que describe el cambio.
4. Debajo del título, explica en 1-3 líneas:
   - Qué se cambió o arregló (en lenguaje claro, no técnico de más).
   - En qué archivo(s) principal(es) vive el cambio.
   - Si aplica: la causa raíz del bug o la razón del cambio.
5. Al final, una línea con el **estado de despliegue**: si está en producción,
   el último commit, y cualquier pendiente (ej. bloques temporales por quitar).
6. Español, tono profesional pero directo. Nada de relleno.
7. Ordena los puntos por relevancia o por orden cronológico de la sesión.
8. Si hay pendientes o cosas temporales (feature flags, código de prueba,
   recordatorios), márcalos claramente al final para no olvidarlos.

## Ejemplo de estructura

```
## Resumen de lo que hicimos

1. **[Título del cambio]**
   Qué se hizo, en qué archivo, y por qué.

2. **[Título del cambio]**
   ...

Estado: desplegado en producción (commit XXXXXXX). Pendiente: [si aplica].
```
