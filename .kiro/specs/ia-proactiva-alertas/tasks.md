# Tasks — IA Proactiva: Sistema de Alertas

## Task 1: Crear migraciones de base de datos [done]
- 1.1 Crear migración `create_alertas_table`
- 1.2 Crear migración `create_alerta_configuracion_table` con valores iniciales (seeder)
- 1.3 Crear migración `create_pronosticos_table`
- 1.4 Crear migración `create_oc_borradores_table`
- 1.5 Crear migración `create_excel_validaciones_table`

## Task 2: Crear modelos Eloquent [depends on: 1]
- 2.1 Crear modelo `Alerta`
- 2.2 Crear modelo `AlertaConfiguracion`
- 2.3 Crear modelo `Pronostico`
- 2.4 Crear modelo `OcBorrador`
- 2.5 Crear modelo `ExcelValidacion`

## Task 3: Crear AlertEngineService base [depends on: 2]
- 3.1 Crear `AlertEngineService` con métodos: crearAlerta, enviarAlerta, getConfig
- 3.2 Integrar con NotificacionService existente
- 3.3 Integrar con AuditService existente

## Task 4: Crear InventarioCalculoService [depends on: 2]
- 4.1 Implementar fórmula: calcularMinimo, calcularMaximo, calcularCantidadAPedir
- 4.2 Implementar evaluarEstado y generarReporteCompleto

## Task 5: Comando ia:verificar-documentos (Req 6) [depends on: 3]
- 5.1 Crear comando artisan `IaVerificarDocumentos`
- 5.2 Lógica: alertar 7 días antes, urgente 3 días antes, notificar vencidos
- 5.3 Registrar en scheduler

## Task 6: Comando ia:evaluar-proveedores (Req 1) [depends on: 3]
- 6.1 Crear comando artisan `IaEvaluarProveedores`
- 6.2 Lógica: evaluar score, detectar patrones de retraso, generar análisis IA
- 6.3 Registrar en scheduler

## Task 7: Comando ia:verificar-inventario (Req 2) [depends on: 3, 4]
- 7.1 Crear comando artisan `IaVerificarInventario`
- 7.2 Lógica: comparar stock vs mínimos, generar OC borrador, alertar admin
- 7.3 Registrar en scheduler

## Task 8: Comando ia:generar-pronosticos (Req 4) [depends on: 3]
- 8.1 Crear comando artisan `IaGenerarPronosticos`
- 8.2 Lógica: pronóstico semanal por cliente, alertar proveedores si reabastecimiento en 14 días
- 8.3 Registrar en scheduler

## Task 9: Comando ia:generar-sugerencias (Req 5) [depends on: 3]
- 9.1 Crear comando artisan `IaGenerarSugerencias`
- 9.2 Lógica: sugerencias personalizadas para proveedores y clientes
- 9.3 Registrar en scheduler

## Task 10: Validación de Excel para alta de producto (Req 8) [depends on: 3]
- 10.1 Crear Job `ValidarExcelProducto`
- 10.2 Crear ruta y controlador `ProductoProveedorController`
- 10.3 Crear vista `mis-productos.blade.php` con upload de Excel
- 10.4 Lógica de validación: nomenclatura, duplicados, campos obligatorios

## Task 11: Comando ia:oc-trimestral (Req 9) [depends on: 4, 7]
- 11.1 Crear comando artisan `IaOCTrimestral`
- 11.2 Lógica: cálculo masivo, agrupar por proveedor, generar OC consolidadas
- 11.3 Registrar en scheduler

## Task 12: Dashboard admin de alertas (Req 7) [depends on: 3]
- 12.1 Crear `AlertaController` con index, configuracion, ocBorradores
- 12.2 Crear vista `admin/alertas/index.blade.php`
- 12.3 Crear vista `admin/alertas/configuracion.blade.php`
- 12.4 Crear vista `admin/alertas/oc-borradores.blade.php`
- 12.5 Agregar rutas y link en sidebar admin
