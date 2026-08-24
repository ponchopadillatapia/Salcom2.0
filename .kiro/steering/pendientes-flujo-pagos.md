# Pendientes Flujo de Pagos — 20 agosto 2026

## Preguntas para resolver con Karen mañana:

### 1. Campo Folio / Nº Póliza en "Abono al proveedor"
- ¿Se autogenera desde el sistema (usando el folio del pago hecho en "Pago a proveedor")?
- ¿O se escribe manual (referencia de Contpaqi externo)?
- ¿O es simplemente el número consecutivo de pago?
- **Contexto:** Folio = número de póliza = número de pago. Si se autogenera, precargarlo al seleccionar proveedor.

### 2. Validación de secuencia
- ¿El sistema debe impedir saltarse pasos? (Ej: no poder pagar una factura "Pendiente" sin pasar por Formato)
- ¿Actualmente está validado o se puede brincar?

### 3. Cancelaciones — camino de vuelta
- Si cancelan en "Pago a proveedor", ¿la factura regresa a "Programada" o a "Pendiente"?
- Si cancelan en "Abono al proveedor", ¿regresa a "Pagada"?

### 4. Portal del proveedor
- ¿Ve todos los estatus correctamente? (Pendiente, Programada, Pagada, Liquidada)
- ¿Le llegan notificaciones en cada cambio de estatus?
- El polling de recarga automática — ¿molesta si el proveedor está escribiendo?

### 5. Deploy a producción
- Falta subir todo este batch de cambios (abono al proveedor, historial, KPIs, colores vencimiento, etc.)

### 6. Pagos parciales
- ¿Se permite pagar una fracción de la factura?
- Si sí, ¿qué estatus tiene una factura parcialmente pagada?

### 7. Historial de abonos
- El modal de detalle al hacer click en un registro — ¿qué info adicional debería mostrar?
- ¿Se necesita botón de exportar Excel?

### 8. Anticipos (PENDIENTE — preguntar a Karen/contabilidad)
- ✅ Módulo creado y funcionando (formulario + historial)
- ✅ Aviso en Formato para pago cuando proveedor tiene anticipos activos
- ❓ ¿Cómo se da de baja/aplica el anticipo? Preguntar a la muchacha cómo lo hace en Excel
  - Opción: Botón "Aplicar anticipo" que marca como cerrado y ya no aparece como activo
  - Pendiente confirmar con Karen/la muchacha para evitar duplicados
- ¿Cómo funciona un anticipo? (pago antes de que exista la factura) ✅ Ya definido
- Alan no sabe cómo funcionan — ✅ Ya resuelto

---

## Lo que se hizo hoy (20 agosto):
- KPIs funcionales en Abono al proveedor (eliminados después, flujo simplificado)
- Formulario estilo Contpaqi para Abono al proveedor con 4 cuentas
- Sidebar: 4 cuentas dentro de "Abono al proveedor" expandible
- Módulo "Historial de abonos" nuevo con KPIs, filtros por fecha, modal detalle
- Pago a proveedor: KPIs muestran proveedores agrupados (no tabla plana), estatus "pagado" corregido
- Quitada columna Vencimiento de listas de proveedores (Formato y Pago)
- Colores de vencimiento actualizados: verde >30, amarillo 11-30, rojo 1-10, rojo tinto ≤0
- Barra lila en vez de azul
- Texto "Abono Prov" → "Pago a proveedor"
- Botón "Guardar abono" en Pago → "Guardar pago"
- Proveedor no se autoselecciona en Abono
