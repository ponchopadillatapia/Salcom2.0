# Requirements Document — IA Proactiva: Sistema de Alertas

## Introduction

Sistema de inteligencia artificial proactiva para Industrias Salcom que monitorea continuamente el rendimiento de proveedores, niveles de inventario, pronósticos de demanda y vencimiento de documentos fiscales. El sistema genera alertas automáticas, sugerencias accionables y órdenes de compra para proveedores, clientes y administradores a través de los canales existentes (WhatsApp, email, notificaciones en portal).

## Glossary

- **Sistema_IA**: El servicio de inteligencia artificial proactiva basado en el IaService existente que conecta con AWS Bedrock/Claude
- **Motor_Alertas**: Componente que evalúa condiciones y genera alertas cuando se cumplen umbrales definidos
- **Proveedor**: Usuario registrado en la tabla `proveedores_users` que suministra materiales a Salcom
- **Cliente**: Usuario registrado en la tabla `clientes_users` que compra productos de Salcom
- **Administrador**: Usuario del panel administrativo con acceso completo al sistema
- **OTIF**: On Time In Full — métrica de desempeño de proveedor compuesta por score_entrega y score_puntualidad
- **OC**: Orden de Compra — documento generado para solicitar materiales a un proveedor
- **Nivel_Minimo**: Cantidad mínima de stock configurada por producto, debajo de la cual se debe reordenar
- **Nivel_Maximo**: Cantidad máxima de stock configurada por producto, que define el tope de reorden
- **Documento_Fiscal**: Registro en `documentos_proveedor` con tipos: cif, opinion, acta, rep_legal, contribuyente, caratula_banco
- **Canal_Notificacion**: Medio de envío de alertas: WhatsApp (Twilio), email, o notificación en portal
- **Score_Total**: Calificación del proveedor calculada como 50% score_entrega + 50% score_puntualidad
- **Umbral_Critico**: Valor de Score_Total por debajo del cual un proveedor se considera de bajo rendimiento (configurable, default 60%)
- **Periodo_Renovacion**: Ciclo mensual en el que los documentos fiscales deben ser actualizados
- **Pronostico_Demanda**: Análisis predictivo generado por el Sistema_IA basado en historial de pedidos

## Requirements

### Requirement 1: Detección de Proveedores con Bajo Rendimiento

**User Story:** Como Administrador, quiero que el Sistema_IA detecte automáticamente proveedores con bajo rendimiento, para tomar acciones correctivas antes de que afecten la operación.

#### Acceptance Criteria

1. WHEN el Score_Total de un Proveedor cae por debajo del Umbral_Critico, THE Motor_Alertas SHALL generar una alerta de tipo "proveedor_bajo_rendimiento" dirigida al Administrador
2. WHEN se genera una alerta de proveedor con bajo rendimiento, THE Sistema_IA SHALL incluir un análisis con las métricas OTIF del Proveedor, la tendencia de los últimos 3 meses y una recomendación de acción
3. THE Motor_Alertas SHALL evaluar el rendimiento de todos los Proveedores activos una vez al día a las 06:00 horas
4. WHEN un Proveedor tiene más de 2 entregas tardías consecutivas en los últimos 30 días, THE Motor_Alertas SHALL generar una alerta de tipo "patron_retraso_detectado" sin esperar a que el Score_Total caiga del Umbral_Critico
5. IF el Sistema_IA no puede conectarse con AWS Bedrock para generar el análisis, THEN THE Motor_Alertas SHALL enviar la alerta con las métricas numéricas sin el análisis de IA y registrar el error en el log de auditoría

### Requirement 2: Generación Automática de Órdenes de Compra por Inventario

**User Story:** Como Administrador, quiero que el Sistema_IA genere órdenes de compra automáticamente cuando el inventario alcance niveles mínimos, para evitar desabasto sin intervención manual.

#### Acceptance Criteria

1. WHEN el stock de un Producto activo cae por debajo de su Nivel_Minimo, THE Sistema_IA SHALL generar un borrador de OC con la cantidad necesaria para alcanzar el Nivel_Maximo
2. WHEN se genera un borrador de OC, THE Sistema_IA SHALL seleccionar al Proveedor con el Score_Total más alto entre los proveedores activos que suministran ese Producto
3. WHEN se genera un borrador de OC, THE Motor_Alertas SHALL notificar al Administrador por Canal_Notificacion con el detalle de la OC propuesta para su aprobación
4. WHILE un borrador de OC está pendiente de aprobación, THE Sistema_IA SHALL no generar otra OC para el mismo Producto
5. WHEN el Administrador aprueba una OC, THE Motor_Alertas SHALL notificar al Proveedor seleccionado por Canal_Notificacion con los detalles del pedido
6. THE Motor_Alertas SHALL verificar los niveles de inventario cada 4 horas durante horario laboral (08:00 a 18:00)
7. IF no existe un Proveedor activo con Score_Total mayor a cero para un Producto, THEN THE Sistema_IA SHALL generar la alerta de stock bajo sin OC y solicitar al Administrador asignación manual de proveedor

### Requirement 3: Visibilidad de Productos para Proveedores

**User Story:** Como Proveedor, quiero ver todos mis productos asignados con sus tendencias de demanda y rendimiento, para planificar mi producción de forma proactiva.

#### Acceptance Criteria

1. THE Sistema_IA SHALL presentar al Proveedor autenticado un dashboard con todos los Productos que le han sido asignados en órdenes de compra previas
2. WHEN un Proveedor accede a su dashboard de productos, THE Sistema_IA SHALL mostrar para cada Producto: stock actual, demanda mensual promedio, tendencia (creciente, estable, decreciente) y próxima fecha estimada de reorden
3. WHEN la tendencia de demanda de un Producto cambia de "estable" a "creciente", THE Motor_Alertas SHALL notificar al Proveedor correspondiente con una sugerencia de preparar inventario adicional
4. THE Sistema_IA SHALL actualizar las métricas de tendencia de productos una vez al día a las 07:00 horas
5. IF el Proveedor no tiene productos asignados en órdenes previas, THEN THE Sistema_IA SHALL mostrar un mensaje indicando que aún no tiene productos vinculados

### Requirement 4: Pronóstico de Demanda Proactivo con Alertas a Proveedores

**User Story:** Como Administrador, quiero que el Sistema_IA genere pronósticos de demanda automáticamente y alerte a los proveedores cuando necesiten preparar stock, para asegurar disponibilidad continua.

#### Acceptance Criteria

1. THE Sistema_IA SHALL generar un Pronostico_Demanda para cada Cliente activo con historial de pedidos una vez por semana (lunes a las 05:00 horas)
2. WHEN un Pronostico_Demanda indica que un Producto requerirá reabastecimiento en los próximos 14 días, THE Motor_Alertas SHALL notificar al Proveedor asignado con la cantidad estimada y la fecha límite de entrega
3. WHEN un Pronostico_Demanda indica un incremento de demanda mayor al 20% respecto al mes anterior, THE Motor_Alertas SHALL generar una alerta de tipo "pico_demanda" al Administrador y al Proveedor correspondiente
4. WHEN el Sistema_IA genera un Pronostico_Demanda, THE Sistema_IA SHALL almacenar el resultado con fecha de generación, nivel de confianza y productos clave identificados
5. IF el historial de pedidos de un Cliente tiene menos de 2 meses de datos, THEN THE Sistema_IA SHALL marcar el pronóstico como "confianza baja" e incluir una nota explicativa

### Requirement 5: Asistente IA Proactivo para Proveedores y Clientes

**User Story:** Como Proveedor o Cliente, quiero recibir sugerencias proactivas del Sistema_IA sobre acciones que puedo tomar, para optimizar mi relación comercial con Salcom.

#### Acceptance Criteria

1. THE Sistema_IA SHALL generar sugerencias personalizadas para cada Proveedor activo basadas en su rendimiento OTIF, patrones de entrega y oportunidades de mejora
2. THE Sistema_IA SHALL generar sugerencias personalizadas para cada Cliente activo basadas en su historial de compras, productos frecuentes y oportunidades de ahorro por volumen
3. WHEN el Sistema_IA genera una sugerencia, THE Motor_Alertas SHALL enviarla al usuario correspondiente por Canal_Notificacion con categoría "sugerencia_ia"
4. WHILE un Proveedor tiene un Score_Total por debajo de 80%, THE Sistema_IA SHALL incluir en sus sugerencias semanales acciones específicas para mejorar su calificación
5. WHEN un Cliente no ha realizado un pedido en los últimos 30 días, THE Sistema_IA SHALL generar una sugerencia de reorden basada en su patrón histórico de compras
6. THE Sistema_IA SHALL generar sugerencias una vez por semana (miércoles a las 08:00 horas)
7. WHEN un usuario marca una sugerencia como "no útil", THE Sistema_IA SHALL registrar la retroalimentación para ajustar futuras sugerencias de ese usuario

### Requirement 6: Recordatorio de Renovación de Documentos Fiscales

**User Story:** Como Administrador, quiero que el Sistema_IA alerte a los proveedores 7 días antes de que sus documentos fiscales venzan, para mantener el cumplimiento regulatorio sin seguimiento manual.

#### Acceptance Criteria

1. THE Motor_Alertas SHALL verificar diariamente (a las 07:00 horas) la fecha de vencimiento de todos los Documentos_Fiscales de Proveedores activos
2. WHEN un Documento_Fiscal vence en exactamente 7 días, THE Motor_Alertas SHALL enviar una notificación al Proveedor por Canal_Notificacion indicando el tipo de documento, la fecha de vencimiento y las instrucciones para renovarlo
3. WHEN un Documento_Fiscal vence en exactamente 3 días y no ha sido renovado, THE Motor_Alertas SHALL enviar un recordatorio urgente al Proveedor y una copia al Administrador
4. WHEN un Documento_Fiscal ha vencido (fecha de vencimiento pasada), THE Motor_Alertas SHALL notificar al Administrador con una alerta de tipo "documento_vencido" y marcar al Proveedor como "documentación incompleta"
5. THE Motor_Alertas SHALL calcular la fecha de vencimiento de cada Documento_Fiscal como la fecha de carga más el Periodo_Renovacion (30 días por defecto)
6. IF un Proveedor renueva un Documento_Fiscal antes de su vencimiento, THEN THE Motor_Alertas SHALL cancelar las alertas pendientes para ese documento y registrar la renovación exitosa en el log de auditoría
7. WHEN el Sistema_IA detecta que un Proveedor tiene 3 o más documentos por vencer en la misma semana, THE Motor_Alertas SHALL consolidar las alertas en una sola notificación con la lista completa de documentos pendientes

### Requirement 7: Configuración y Auditoría del Sistema de Alertas

**User Story:** Como Administrador, quiero configurar los umbrales y frecuencias del sistema de alertas y tener visibilidad completa de todas las alertas generadas, para mantener control sobre el comportamiento del Sistema_IA.

#### Acceptance Criteria

1. THE Sistema_IA SHALL permitir al Administrador configurar: Umbral_Critico de proveedor, Nivel_Minimo y Nivel_Maximo por producto, frecuencia de evaluación y canales de notificación preferidos
2. WHEN el Motor_Alertas genera cualquier alerta, THE Sistema_IA SHALL registrar en el log de auditoría: tipo de alerta, destinatario, canal utilizado, contenido resumido y timestamp
3. THE Sistema_IA SHALL presentar al Administrador un dashboard con el historial de alertas generadas, filtrable por tipo, fecha, destinatario y estatus (enviada, leída, accionada)
4. WHEN un Administrador modifica un umbral de configuración, THE Sistema_IA SHALL registrar el cambio en el log de auditoría con el valor anterior y el nuevo valor
5. IF el Motor_Alertas no puede enviar una notificación por un Canal_Notificacion, THEN THE Motor_Alertas SHALL reintentar por un canal alternativo y registrar la falla en el log de auditoría

### Requirement 8: Validación de Alta de Producto por IA mediante Excel Estandarizado

**User Story:** Como Proveedor, quiero dar de alta productos nuevos llenando un Excel estandarizado que la IA valide automáticamente, para que el proceso sea rápido y sin errores de formato.

#### Acceptance Criteria

1. THE Sistema_IA SHALL proporcionar al Proveedor un template Excel descargable con los campos estandarizados: código, nombre (formato [TIPO]+[MARCA]+[MODELO]+[MEDIDA]+[ESPECIFICACIÓN]), familia, subfamilia, unidad de medida, precio, marca, especificaciones técnicas
2. WHEN un Proveedor sube un Excel de alta de producto, THE Sistema_IA SHALL validar automáticamente: formato de nomenclatura correcto, campos obligatorios completos, unidades de medida válidas, categorías existentes y ausencia de duplicados en el catálogo
3. WHEN la validación del Excel es exitosa, THE Sistema_IA SHALL generar una solicitud de alta dirigida al Administrador con el resumen de productos validados para su aprobación
4. WHEN la validación del Excel detecta errores, THE Sistema_IA SHALL devolver al Proveedor un reporte detallado indicando fila por fila qué campo tiene error y cuál es el formato correcto esperado
5. THE Sistema_IA SHALL verificar duplicados comparando nombre normalizado (sin acentos, mayúsculas, sin espacios extra) contra el catálogo maestro existente
6. WHEN el Administrador aprueba el alta, THE Sistema_IA SHALL asignar SKU automático según la clasificación del producto y registrarlo en el catálogo maestro
7. IF el Excel contiene más de 50 productos, THEN THE Sistema_IA SHALL procesarlo en segundo plano y notificar al Proveedor cuando la validación esté completa

### Requirement 9: Generación Automática de OC Trimestral

**User Story:** Como Administrador, quiero que el Sistema_IA genere automáticamente órdenes de compra cada 3 meses basadas en el consumo promedio y la fórmula de mínimos y máximos, para mantener el inventario sin intervención manual periódica.

#### Acceptance Criteria

1. THE Sistema_IA SHALL ejecutar cada 90 días (trimestralmente) un cálculo masivo de reorden usando la fórmula: Cantidad a Pedir = (Consumo Diario × DDI) − Existencia Actual − Pendiente de Recibir, donde DDI = 90 días
2. WHEN el cálculo trimestral genera cantidades a pedir mayores a cero, THE Sistema_IA SHALL agrupar los productos por Proveedor y generar un borrador de OC consolidada por cada Proveedor
3. WHEN se genera una OC trimestral, THE Motor_Alertas SHALL notificar al Administrador con el resumen de todas las OC propuestas para revisión y aprobación masiva
4. THE Sistema_IA SHALL calcular el Stock Mínimo de cada producto como: Consumo Diario × Días promedio de entrega del Proveedor
5. THE Sistema_IA SHALL calcular el Stock Máximo de cada producto como: Consumo Diario × 90 (DDI política Salcom)
6. WHEN el Administrador aprueba las OC trimestrales, THE Motor_Alertas SHALL notificar a cada Proveedor con su OC correspondiente incluyendo: productos, cantidades, fecha límite de entrega y condiciones
