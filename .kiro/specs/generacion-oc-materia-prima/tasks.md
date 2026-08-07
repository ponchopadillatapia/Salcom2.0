# Plan de Implementación: Generación Automática de OC de Materia Prima

## Visión General

Implementación del módulo de generación automática de Órdenes de Compra para materia prima en Laravel. El sistema calcula puntos de reorden basándose en consumo de 2 meses, margen de seguridad del 50%, lead times de proveedores, agrupa por proveedor y genera borradores para aprobación.

## Tareas

- [x] 1. Migraciones de base de datos
  - [x] 1.1 Crear migración para agregar columnas `stock_minimo` y `lead_time_dias` a tabla `productos`
    - Agregar `stock_minimo` decimal(12,2) nullable después de `stock`
    - Agregar `lead_time_dias` unsignedSmallInteger nullable después de `stock_minimo`
    - _Requerimientos: 3.1_

  - [x] 1.2 Crear migración para agregar columna `historial_modificaciones` a tabla `oc_borradores`
    - Agregar `historial_modificaciones` json nullable después de `notas`
    - _Requerimientos: 10.3_

- [x] 2. Implementar ReordenCalculoService
  - [x] 2.1 Crear `app/Services/ReordenCalculoService.php` con método `calcularConsumoPromedio`
    - Obtener pedidos no cancelados de los últimos 2 meses
    - Sumar cantidades por producto por mes y dividir entre meses con consumo
    - Retornar 0 si no hay pedidos para el producto
    - _Requerimientos: 1.1, 1.2, 1.3, 1.4_

  - [x] 2.2 Implementar método `calcularCantidadSugerida` y `ajustarAMOQ`
    - Fórmula: `ceil(ConsumoPromedio + ConsumoPromedio×0.50 + (ConsumoPromedio/30)×LeadTime)`
    - Usar lead time por defecto de 15 días si no está configurado
    - Ajustar al múltiplo de MOQ inmediato superior cuando aplique
    - Redondear al entero superior
    - _Requerimientos: 2.1, 2.2, 2.3, 2.4_

  - [x] 2.3 Implementar métodos `calcularPuntoReorden`, `requiereReorden` y `obtenerPendienteRecibir`
    - Punto de reorden = stock_minimo + (consumo_diario × lead_time)
    - Evaluar si `(stock_actual + pendiente_recibir) ≤ punto_reorden`
    - Marcar como "urgente" si stock_actual == 0
    - Considerar OC aprobadas/en_proceso como pendientes de recibir
    - _Requerimientos: 4.1, 4.2, 4.3, 4.4_

  - [x] 2.4 Implementar método `seleccionarMejorProveedor`
    - Seleccionar proveedor con mayor `score_total` entre los activos vinculados
    - Excluir productos sin proveedor vinculado y registrar alerta `producto_sin_proveedor`
    - _Requerimientos: 5.2, 5.3_

  - [x] 2.5 Implementar método `generarOCBorradores`
    - Agrupar productos por proveedor seleccionado
    - Generar una OC borrador por proveedor con tipo `automatica_reorden` y estatus `pendiente`
    - Almacenar productos como JSON con: codigo, nombre, cantidad_sugerida, unidad, precio_unitario, subtotal, stock_actual, punto_reorden, urgente
    - Usar precio de `producto_proveedor_precios`; fallback a `producto.precio`
    - Calcular monto_estimado como suma de subtotales
    - _Requerimientos: 5.1, 5.4, 6.1, 6.2, 6.3, 6.4_

  - [x] 2.6 Implementar método `ejecutarProcesoReorden`
    - Excluir productos sin stock_minimo configurado (registrar advertencia)
    - Excluir productos que ya tienen OC pendiente/aprobada
    - Orquestar todo el flujo: consumo → cantidad → reorden → agrupación → OC
    - Registrar log con: productos evaluados, productos reorden, OC generadas, monto total
    - _Requerimientos: 3.4, 9.2, 9.3, 10.2_

  - [ ]* 2.7 Escribir test de propiedad para cálculo de consumo promedio
    - **Propiedad 1: Cálculo correcto de consumo promedio**
    - **Valida: Requerimientos 1.1, 1.2, 1.3, 1.4**

  - [ ]* 2.8 Escribir test de propiedad para fórmula de cantidad sugerida con ajuste MOQ
    - **Propiedad 2: Fórmula de cantidad sugerida con ajuste MOQ**
    - **Valida: Requerimientos 2.1, 2.2, 2.4**

  - [ ]* 2.9 Escribir test de propiedad para detección de reorden con pendientes
    - **Propiedad 5: Detección correcta de reorden con pendientes**
    - **Valida: Requerimientos 4.1, 4.2, 4.3, 4.4**

  - [ ]* 2.10 Escribir test de propiedad para agrupación por proveedor
    - **Propiedad 6: Agrupación correcta por proveedor con selección por score**
    - **Valida: Requerimientos 5.1, 5.2, 5.4**

  - [ ]* 2.11 Escribir test de propiedad para exclusión de productos sin proveedor
    - **Propiedad 7: Exclusión de productos sin proveedor**
    - **Valida: Requerimiento 5.3**

  - [ ]* 2.12 Escribir test de propiedad para cálculo de monto estimado
    - **Propiedad 8: Cálculo correcto de monto estimado con fallback de precio**
    - **Valida: Requerimientos 6.2, 6.3**

  - [ ]* 2.13 Escribir test de propiedad para no duplicación de OC
    - **Propiedad 11: No duplicación de OC para productos con OC pendiente**
    - **Valida: Requerimiento 9.2**

- [x] 3. Checkpoint - Verificar que el servicio funciona correctamente
  - Asegurar que todos los tests pasan, preguntar al usuario si surgen dudas.

- [x] 4. Implementar comando Artisan `ia:reorden-mp`
  - [x] 4.1 Crear `app/Console/Commands/IaReordenMateriaPrima.php`
    - Signature: `ia:reorden-mp {--dry-run}`
    - Inyectar `ReordenCalculoService`
    - En modo `--dry-run`, mostrar resultados sin crear OC
    - En modo normal, ejecutar proceso completo y enviar alerta al admin con resumen
    - Registrar log informativo si no hay productos para reordenar
    - _Requerimientos: 9.1, 9.3, 9.4_

- [x] 5. Implementar ReordenOcController
  - [x] 5.1 Crear `app/Http/Controllers/ReordenOcController.php` con métodos `index` y `show`
    - `index`: Listar OC borradores tipo `automatica_reorden` pendientes, ordenadas por fecha desc
    - Mostrar resumen: total OC pendientes, monto total estimado, productos urgentes
    - `show`: Detalle de OC con lista de productos, cantidades, precios, subtotales
    - _Requerimientos: 7.1, 7.2, 7.3, 7.4_

  - [x] 5.2 Implementar métodos `aprobar` y `rechazar`
    - `aprobar`: Cambiar estatus a "aprobada", registrar `aprobada_por` y `aprobada_at`
    - `rechazar`: Cambiar estatus a "rechazada", registrar motivo en `notas`
    - Registrar auditoría en ambos casos
    - _Requerimientos: 8.1, 8.3, 10.1_

  - [x] 5.3 Implementar métodos `actualizarProductos`, `eliminarProducto` y `agregarProducto`
    - `actualizarProductos`: Modificar cantidades, recalcular monto, guardar historial
    - `eliminarProducto`: Quitar producto, recalcular monto; si OC queda vacía → estatus "rechazada"
    - `agregarProducto`: Validar que pertenezca al mismo proveedor, recalcular monto, guardar historial
    - _Requerimientos: 8.2, 8.4, 8.5, 10.3_

  - [x] 5.4 Implementar métodos `ejecutarReorden` e `importarStockMinimos`
    - `ejecutarReorden`: Ejecutar proceso manualmente desde dashboard
    - `importarStockMinimos`: Leer Excel/CSV, validar valores > 0, actualizar columna stock_minimo
    - _Requerimientos: 3.2, 3.3, 9.1_

  - [ ]* 5.5 Escribir test de propiedad para transición de estado en aprobación
    - **Propiedad 9: Transición de estado en aprobación**
    - **Valida: Requerimiento 8.1**

  - [ ]* 5.6 Escribir test de propiedad para recálculo de monto al modificar
    - **Propiedad 10: Recálculo de monto al modificar cantidades**
    - **Valida: Requerimientos 8.2, 8.5**

  - [ ]* 5.7 Escribir test de propiedad para preservación del historial
    - **Propiedad 12: Preservación del historial de modificaciones**
    - **Valida: Requerimiento 10.3**

  - [ ]* 5.8 Escribir test de propiedad para validación de stock mínimo positivo
    - **Propiedad 3: Validación de stock mínimo positivo**
    - **Valida: Requerimiento 3.2**

  - [ ]* 5.9 Escribir test de propiedad para exclusión de productos sin stock mínimo
    - **Propiedad 4: Exclusión de productos sin stock mínimo**
    - **Valida: Requerimiento 3.4**

- [x] 6. Checkpoint - Verificar controller y lógica de negocio
  - Asegurar que todos los tests pasan, preguntar al usuario si surgen dudas.

- [x] 7. Crear vistas Blade para dashboard de reorden
  - [x] 7.1 Crear vista `resources/views/admin/reorden-oc/index.blade.php`
    - Tabla de OC pendientes con: proveedor, cantidad productos, monto estimado, fecha, indicador urgencia
    - Resumen superior: total OC pendientes, monto total, productos urgentes
    - Botón para ejecutar proceso manualmente
    - Formulario para importar stock mínimos desde Excel
    - _Requerimientos: 7.1, 7.2, 7.4_

  - [x] 7.2 Crear vista `resources/views/admin/reorden-oc/show.blade.php`
    - Tabla de productos con: código, nombre, cantidad sugerida, unidad, precio, subtotal, stock actual
    - Formularios para: aprobar, rechazar (con campo motivo), modificar cantidades, eliminar producto, agregar producto
    - Indicadores de urgencia para productos con stock cero
    - _Requerimientos: 7.3, 8.1, 8.2, 8.3, 8.4, 8.5_

- [x] 8. Registrar rutas y configurar scheduling
  - [x] 8.1 Registrar rutas en `routes/web.php` con middleware `auth.admin`
    - Todas las rutas del ReordenOcController bajo prefijo `/admin/reorden-oc`
    - _Requerimientos: 7.1_

  - [x] 8.2 Configurar scheduling del comando `ia:reorden-mp` en `bootstrap/app.php`
    - Programar ejecución semanal: lunes a las 6:00 AM
    - _Requerimiento: 9.1_

- [x] 9. Actualizar modelo Producto con nuevos campos
  - [x] 9.1 Agregar `stock_minimo` y `lead_time_dias` al `$fillable` del modelo `Producto`
    - Agregar casts apropiados (decimal, integer)
    - _Requerimientos: 3.1_

- [x] 10. Checkpoint final - Verificar integración completa
  - Asegurar que todos los tests pasan, ejecutar `ia:reorden-mp --dry-run` para validar flujo completo, preguntar al usuario si surgen dudas.

## Notas

- Las tareas marcadas con `*` son opcionales y pueden omitirse para un MVP más rápido
- Cada tarea referencia requerimientos específicos para trazabilidad
- Los checkpoints aseguran validación incremental
- Los tests de propiedad utilizan la librería `innmind/black-box` con mínimo 100 iteraciones
- Los tests unitarios usan PHPUnit estándar de Laravel
