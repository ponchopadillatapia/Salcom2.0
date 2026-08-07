# Documento de Requerimientos: Generación Automática de OC de Materia Prima

## Introducción

Sistema de generación automática de Órdenes de Compra (OC) para materia prima en Industrias Salcom. El sistema calcula las cantidades necesarias a comprar basándose en el consumo promedio de los últimos 2 meses, un margen de seguridad del 50%, y el tiempo de entrega del proveedor (lead time). Los productos se agrupan por proveedor y se presentan como borradores para aprobación del administrador antes de envío.

## Glosario

- **Sistema_OC**: Módulo de generación automática de Órdenes de Compra de materia prima dentro de la aplicación Laravel existente de Industrias Salcom.
- **Producto_MP**: Producto de tipo materia prima registrado en la tabla `productos` con campo `activo = true`.
- **Proveedor**: Usuario proveedor registrado en la tabla `proveedores_users` con relación a productos a través de `producto_proveedor_precios`.
- **Consumo_Promedio**: Promedio mensual de unidades consumidas de un Producto_MP calculado sobre los últimos 2 meses de pedidos.
- **Stock_Seguridad**: Margen adicional del 50% sobre el Consumo_Promedio para evitar desabasto.
- **Lead_Time**: Tiempo de entrega en días del Proveedor para un Producto_MP específico.
- **Stock_Minimo**: Cantidad mínima requerida de un Producto_MP para producir producto terminado, definida en una tabla de configuración (proporcionada por Brenda).
- **Punto_Reorden**: Nivel de stock por debajo del cual se debe generar una OC. Se calcula como Stock_Minimo más el consumo durante el Lead_Time.
- **Cantidad_Sugerida**: Cantidad calculada a pedir según la fórmula: Consumo_Promedio + Stock_Seguridad (50%) + cobertura de Lead_Time.
- **OC_Borrador**: Registro en la tabla `oc_borradores` con estatus "pendiente" que requiere aprobación del administrador.
- **Administrador**: Usuario con rol admin que aprueba, modifica o rechaza OC_Borrador.
- **MOQ**: Minimum Order Quantity, cantidad mínima de pedido definida por el Proveedor para un Producto_MP.

## Requerimientos

### Requerimiento 1: Cálculo de Consumo Promedio

**User Story:** Como administrador de compras, quiero que el sistema calcule automáticamente el consumo promedio de cada materia prima basándose en los últimos 2 meses, para tener una base confiable de reorden.

#### Criterios de Aceptación

1. WHEN el Sistema_OC inicia el cálculo de reorden, THE Sistema_OC SHALL obtener todos los pedidos no cancelados de los últimos 2 meses de la tabla `pedidos`.
2. WHEN el Sistema_OC procesa los pedidos, THE Sistema_OC SHALL sumar las cantidades de cada Producto_MP por mes y dividir entre la cantidad de meses con consumo para obtener el Consumo_Promedio.
3. IF un Producto_MP no tiene pedidos en los últimos 2 meses, THEN THE Sistema_OC SHALL asignar un Consumo_Promedio de cero para ese producto.
4. THE Sistema_OC SHALL excluir pedidos con estatus "cancelado" del cálculo de Consumo_Promedio.

### Requerimiento 2: Fórmula de Cantidad Sugerida

**User Story:** Como administrador de compras, quiero que el sistema aplique la fórmula de reorden (promedio 2 meses + 50% seguridad + lead time) para cada materia prima, para asegurar abastecimiento continuo.

#### Criterios de Aceptación

1. THE Sistema_OC SHALL calcular la Cantidad_Sugerida aplicando la fórmula: Consumo_Promedio + (Consumo_Promedio × 0.50) + (Consumo_Diario × Lead_Time en días), donde Consumo_Diario = Consumo_Promedio / 30.
2. WHEN un Producto_MP tiene un MOQ definido por el Proveedor, THE Sistema_OC SHALL ajustar la Cantidad_Sugerida al múltiplo del MOQ inmediato superior si la cantidad calculada es menor al MOQ.
3. IF el Lead_Time del Proveedor no está configurado para un Producto_MP, THEN THE Sistema_OC SHALL usar un Lead_Time por defecto de 15 días.
4. THE Sistema_OC SHALL redondear la Cantidad_Sugerida al entero superior más cercano.

### Requerimiento 3: Configuración de Stock Mínimo por Producto

**User Story:** Como administrador de compras, quiero poder configurar el stock mínimo de cada materia prima (según el documento de Brenda), para que el sistema identifique correctamente cuándo reordenar.

#### Criterios de Aceptación

1. THE Sistema_OC SHALL almacenar el Stock_Minimo de cada Producto_MP en una columna dedicada de la tabla `productos` o en una tabla de configuración relacionada.
2. WHEN un administrador actualiza el Stock_Minimo de un Producto_MP, THE Sistema_OC SHALL validar que el valor sea un número positivo mayor a cero.
3. THE Sistema_OC SHALL permitir la importación masiva de valores de Stock_Minimo desde un archivo Excel o CSV.
4. IF un Producto_MP no tiene Stock_Minimo configurado, THEN THE Sistema_OC SHALL excluir ese producto del cálculo automático de reorden y registrar una advertencia.

### Requerimiento 4: Detección de Productos que Requieren Reorden

**User Story:** Como administrador de compras, quiero que el sistema identifique automáticamente los productos cuyo stock actual está por debajo del punto de reorden, para actuar antes de que se agoten.

#### Criterios de Aceptación

1. THE Sistema_OC SHALL calcular el Punto_Reorden de cada Producto_MP como: Stock_Minimo + (Consumo_Diario × Lead_Time).
2. WHEN el stock actual de un Producto_MP es menor o igual al Punto_Reorden, THE Sistema_OC SHALL marcar ese producto como "requiere reorden".
3. THE Sistema_OC SHALL considerar las cantidades pendientes de recibir (OC_Borrador con estatus "aprobada" o "en_proceso") al evaluar si un producto requiere reorden.
4. IF el stock actual de un Producto_MP es cero, THEN THE Sistema_OC SHALL marcar ese producto como "urgente" además de "requiere reorden".

### Requerimiento 5: Agrupación de Productos por Proveedor

**User Story:** Como administrador de compras, quiero que los productos a reordenar se agrupen automáticamente por proveedor, para generar una sola OC por proveedor y simplificar la gestión.

#### Criterios de Aceptación

1. WHEN el Sistema_OC genera OC_Borrador, THE Sistema_OC SHALL agrupar los productos que requieren reorden por Proveedor, usando la relación `producto_proveedor_precios`.
2. IF un Producto_MP tiene múltiples proveedores, THEN THE Sistema_OC SHALL asignar el producto al Proveedor con mayor score_total entre los proveedores activos vinculados.
3. IF un Producto_MP no tiene ningún Proveedor vinculado, THEN THE Sistema_OC SHALL excluir ese producto de la generación automática y registrar una alerta de tipo "producto_sin_proveedor".
4. THE Sistema_OC SHALL generar exactamente una OC_Borrador por cada Proveedor que tenga al menos un producto que requiere reorden.

### Requerimiento 6: Generación de OC Borrador

**User Story:** Como administrador de compras, quiero que el sistema genere borradores de OC con los productos agrupados por proveedor y las cantidades calculadas, para poder revisarlos antes de enviarlos.

#### Criterios de Aceptación

1. WHEN el Sistema_OC genera una OC_Borrador, THE Sistema_OC SHALL almacenar: proveedor_id, lista de productos (código, nombre, cantidad_sugerida, unidad, precio_estimado), monto_estimado total, motivo de generación, y estatus "pendiente".
2. THE Sistema_OC SHALL calcular el monto_estimado como la suma de (Cantidad_Sugerida × precio unitario del Proveedor) para todos los productos de la OC_Borrador.
3. WHEN una OC_Borrador se crea, THE Sistema_OC SHALL usar el precio del proveedor desde `producto_proveedor_precios`; si no existe, usar el precio base del producto.
4. THE Sistema_OC SHALL asignar el tipo "automatica_reorden" a las OC_Borrador generadas por este proceso.

### Requerimiento 7: Dashboard de OC Sugeridas para Administrador

**User Story:** Como administrador de compras, quiero ver un panel con todas las OC sugeridas pendientes de aprobación, para poder revisarlas, modificarlas o aprobarlas eficientemente.

#### Criterios de Aceptación

1. THE Sistema_OC SHALL mostrar en la vista `/admin/gestion-compras` una sección de OC_Borrador pendientes ordenadas por fecha de creación descendente.
2. THE Sistema_OC SHALL mostrar por cada OC_Borrador: nombre del proveedor, cantidad de productos, monto estimado total, fecha de generación, y un indicador de urgencia si contiene productos con stock cero.
3. WHEN el administrador selecciona una OC_Borrador, THE Sistema_OC SHALL mostrar el detalle con la lista completa de productos, cantidades sugeridas, precios unitarios y subtotales.
4. THE Sistema_OC SHALL mostrar un resumen con: total de OC pendientes, monto total estimado de todas las OC pendientes, y cantidad de productos urgentes (stock cero).

### Requerimiento 8: Aprobación y Modificación de OC

**User Story:** Como administrador de compras, quiero poder aprobar, modificar cantidades, o rechazar una OC borrador antes de enviarla al proveedor, para mantener control sobre las compras.

#### Criterios de Aceptación

1. WHEN el administrador aprueba una OC_Borrador, THE Sistema_OC SHALL cambiar el estatus a "aprobada", registrar el usuario que aprobó en `aprobada_por`, y la fecha en `aprobada_at`.
2. WHEN el administrador modifica cantidades en una OC_Borrador, THE Sistema_OC SHALL recalcular el monto_estimado y guardar las notas de modificación.
3. WHEN el administrador rechaza una OC_Borrador, THE Sistema_OC SHALL cambiar el estatus a "rechazada" y registrar el motivo en el campo `notas`.
4. WHEN el administrador elimina un producto de una OC_Borrador, THE Sistema_OC SHALL recalcular el monto_estimado; si no quedan productos, cambiar estatus a "rechazada".
5. WHEN el administrador agrega un producto manualmente a una OC_Borrador, THE Sistema_OC SHALL recalcular el monto_estimado y validar que el producto pertenezca al mismo Proveedor.

### Requerimiento 9: Ejecución Automática Programada

**User Story:** Como administrador de compras, quiero que el cálculo de reorden se ejecute automáticamente de forma periódica, para no depender de ejecutarlo manualmente.

#### Criterios de Aceptación

1. THE Sistema_OC SHALL ejecutar el proceso de cálculo de reorden mediante un comando Artisan programable (schedule).
2. WHEN el proceso automático se ejecuta, THE Sistema_OC SHALL generar OC_Borrador solo para productos que no tengan ya una OC_Borrador pendiente o aprobada con el mismo producto.
3. IF el proceso automático no encuentra productos que requieran reorden, THEN THE Sistema_OC SHALL registrar un log informativo sin generar alertas innecesarias.
4. WHEN el proceso automático genera nuevas OC_Borrador, THE Sistema_OC SHALL enviar una alerta al Administrador con el resumen de OC generadas.

### Requerimiento 10: Auditoría y Trazabilidad

**User Story:** Como administrador de compras, quiero que todas las acciones sobre las OC queden registradas, para mantener trazabilidad de decisiones de compra.

#### Criterios de Aceptación

1. WHEN una OC_Borrador cambia de estatus, THE Sistema_OC SHALL registrar la acción en el sistema de auditoría con: usuario, fecha, estatus anterior, estatus nuevo, y notas.
2. WHEN el proceso automático genera OC_Borrador, THE Sistema_OC SHALL registrar en logs: cantidad de productos evaluados, cantidad de productos que requieren reorden, cantidad de OC generadas, y monto total estimado.
3. THE Sistema_OC SHALL preservar el historial de modificaciones de cantidades en una OC_Borrador antes de su aprobación.
