# Technical Design — IA Proactiva: Sistema de Alertas

## Architecture Overview

El sistema se compone de 4 capas principales:

1. **Motor de Alertas** — Laravel Scheduled Commands que corren en cron y evalúan condiciones
2. **Servicios de IA** — Conectan con AWS Bedrock/Claude para análisis inteligente
3. **Sistema de Notificaciones** — Envía alertas por WhatsApp, email y portal
4. **Dashboard de Alertas** — Vista admin para configurar y monitorear

```
┌─────────────────────────────────────────────────────┐
│                   CRON (Scheduler)                    │
│  06:00 EvalProveedores | 07:00 CheckDocs | etc.     │
└──────────────────────┬──────────────────────────────┘
                       │
              ┌────────▼────────┐
              │  AlertEngine    │ ← Evalúa condiciones
              │  (Service)      │
              └───┬────────┬───┘
                  │        │
         ┌────────▼──┐  ┌──▼────────┐
         │ IaService │  │Notificacion│
         │ (Claude)  │  │  Service   │
         └───────────┘  └────────────┘
                              │
                    ┌─────────┼─────────┐
                    │         │         │
               WhatsApp    Email    Portal
```

## Database Schema (New Tables)

### 1. `alertas` — Registro de todas las alertas generadas

```sql
CREATE TABLE alertas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,          -- proveedor_bajo_rendimiento, stock_bajo, documento_vencido, sugerencia_ia, pico_demanda, oc_generada, excel_validado
    modulo VARCHAR(30) NOT NULL,        -- proveedores, clientes, inventario, fiscal, productos
    destinatario_tipo VARCHAR(20),      -- admin, proveedor, cliente
    destinatario_id BIGINT UNSIGNED NULL,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT,
    datos JSON NULL,                    -- métricas, scores, productos afectados, etc.
    canal_enviado VARCHAR(20) NULL,     -- whatsapp, email, portal
    estatus VARCHAR(20) DEFAULT 'pendiente', -- pendiente, enviada, leida, accionada, cancelada
    nivel VARCHAR(10) DEFAULT 'info',   -- info, warning, critical
    leida_at TIMESTAMP NULL,
    accionada_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_destinatario (destinatario_tipo, destinatario_id),
    INDEX idx_estatus (estatus),
    INDEX idx_created (created_at)
);
```

### 2. `alerta_configuracion` — Umbrales configurables por admin

```sql
CREATE TABLE alerta_configuracion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL, -- umbral_critico_proveedor, ddi_dias, frecuencia_inventario, etc.
    valor VARCHAR(255) NOT NULL,
    descripcion VARCHAR(255),
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

Valores iniciales:
- `umbral_critico_proveedor` = 60
- `ddi_dias` = 90
- `dias_alerta_documento` = 7
- `dias_urgente_documento` = 3
- `frecuencia_oc_trimestral` = 90
- `pico_demanda_porcentaje` = 20

### 3. `pronosticos` — Pronósticos generados por la IA

```sql
CREATE TABLE pronosticos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(30) NOT NULL,          -- demanda_cliente, inventario, tendencia
    referencia_tipo VARCHAR(20),        -- cliente, producto, proveedor
    referencia_id BIGINT UNSIGNED NULL,
    codigo_referencia VARCHAR(50),      -- CLI-001, SAL-001, etc.
    resultado TEXT,                     -- Análisis de la IA
    datos JSON,                         -- Datos numéricos del pronóstico
    confianza VARCHAR(10) DEFAULT 'media', -- alta, media, baja
    generado_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_tipo_ref (tipo, referencia_tipo, referencia_id),
    INDEX idx_generado (generado_at)
);
```

### 4. `oc_borradores` — Borradores de OC generados por la IA

```sql
CREATE TABLE oc_borradores (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) NOT NULL,          -- automatica, trimestral, manual
    proveedor_id BIGINT UNSIGNED NOT NULL,
    productos JSON NOT NULL,            -- [{codigo, nombre, cantidad, unidad, precio_estimado}]
    monto_estimado DECIMAL(12,2) DEFAULT 0,
    motivo VARCHAR(255),                -- stock_bajo, trimestral, pico_demanda
    estatus VARCHAR(20) DEFAULT 'pendiente', -- pendiente, aprobada, rechazada, enviada
    aprobada_por BIGINT UNSIGNED NULL,
    aprobada_at TIMESTAMP NULL,
    notas TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_estatus (estatus)
);
```

### 5. `excel_validaciones` — Registro de Excels subidos para alta de producto

```sql
CREATE TABLE excel_validaciones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proveedor_id BIGINT UNSIGNED NOT NULL,
    archivo_path VARCHAR(255) NOT NULL,
    total_productos INT DEFAULT 0,
    productos_validos INT DEFAULT 0,
    productos_con_error INT DEFAULT 0,
    errores JSON NULL,                  -- [{fila, campo, error, valor_actual, valor_esperado}]
    estatus VARCHAR(20) DEFAULT 'procesando', -- procesando, validado, con_errores, aprobado, rechazado
    aprobado_por BIGINT UNSIGNED NULL,
    aprobado_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_proveedor (proveedor_id),
    INDEX idx_estatus (estatus)
);
```

## New Services

### 1. `AlertEngineService` — Motor principal de alertas

```php
// app/Services/AlertEngineService.php
class AlertEngineService
{
    // Evaluaciones programadas
    public function evaluarProveedores(): void;      // Req 1 - diario 06:00
    public function verificarInventario(): void;     // Req 2 - cada 4h
    public function verificarDocumentos(): void;     // Req 6 - diario 07:00
    public function generarPronosticos(): void;      // Req 4 - lunes 05:00
    public function generarSugerencias(): void;      // Req 5 - miércoles 08:00
    public function generarOCTrimestral(): void;     // Req 9 - cada 90 días

    // Helpers
    public function crearAlerta(array $data): Alerta;
    public function enviarAlerta(Alerta $alerta): void;
    public function getConfig(string $clave, $default = null): mixed;
}
```

### 2. `ExcelValidationService` (actualizar existente)

```php
// app/Services/ExcelValidationService.php — agregar métodos
class ExcelValidationService
{
    public function validarAltaProducto(string $filePath, int $proveedorId): array;
    public function verificarDuplicados(array $productos): array;
    public function verificarNomenclatura(string $nombre): array;
    public function generarSKU(array $producto): string;
    public function generarReporteErrores(array $errores): array;
}
```

### 3. `InventarioCalculoService` — Fórmula de mínimos y máximos

```php
// app/Services/InventarioCalculoService.php
class InventarioCalculoService
{
    private int $ddi = 90; // Días de inventario

    public function calcularMinimo(float $consumoDiario, int $diasEntrega): float;
    public function calcularMaximo(float $consumoDiario): float;
    public function calcularCantidadAPedir(float $maximo, float $existencia, float $pendiente): float;
    public function calcularConsumoDiario(float $consumoMensual): float;
    public function evaluarEstado(float $existencia, float $minimo, float $maximo): string;
    public function generarReporteCompleto(): array; // Para toda la BD
}
```

## Laravel Scheduled Commands

```php
// app/Console/Kernel.php (o routes/console.php en Laravel 12)

// Diario 06:00 — Evaluar proveedores
Schedule::command('ia:evaluar-proveedores')->dailyAt('06:00');

// Diario 07:00 — Verificar documentos fiscales
Schedule::command('ia:verificar-documentos')->dailyAt('07:00');

// Cada 4 horas (horario laboral) — Verificar inventario
Schedule::command('ia:verificar-inventario')->everyFourHours()->between('8:00', '18:00');

// Lunes 05:00 — Generar pronósticos
Schedule::command('ia:generar-pronosticos')->weeklyOn(1, '05:00');

// Miércoles 08:00 — Generar sugerencias
Schedule::command('ia:generar-sugerencias')->weeklyOn(3, '08:00');

// Trimestral — Generar OC masiva (cada 90 días)
Schedule::command('ia:oc-trimestral')->quarterly();
```

### Artisan Commands:

| Comando | Descripción | Frecuencia |
|---|---|---|
| `ia:evaluar-proveedores` | Evalúa OTIF de todos los proveedores activos | Diario 06:00 |
| `ia:verificar-documentos` | Revisa vencimiento de documentos fiscales | Diario 07:00 |
| `ia:verificar-inventario` | Compara stock vs mínimos, genera OC si necesario | Cada 4h |
| `ia:generar-pronosticos` | Genera pronóstico de demanda por cliente | Lunes 05:00 |
| `ia:generar-sugerencias` | Genera sugerencias personalizadas | Miércoles 08:00 |
| `ia:oc-trimestral` | Genera OC masiva basada en fórmula mín/máx | Cada 90 días |
| `ia:validar-excel {id}` | Valida un Excel de alta de producto | On-demand (queue) |

## Queue Jobs

```php
// Jobs que se despachan a la cola para no bloquear
App\Jobs\ValidarExcelProducto::class      // Procesa Excel subido
App\Jobs\GenerarAnalisisIA::class         // Llama a Claude para análisis
App\Jobs\EnviarAlertaMasiva::class        // Envía notificaciones en lote
App\Jobs\GenerarOCBorrador::class         // Crea borrador de OC
```

## Routes (Admin Dashboard de Alertas)

```php
// routes/web.php — Admin
Route::prefix('admin/alertas')->middleware('auth.admin')->group(function () {
    Route::get('/', [AlertaController::class, 'index'])->name('admin.alertas');
    Route::get('/configuracion', [AlertaController::class, 'configuracion'])->name('admin.alertas.config');
    Route::post('/configuracion', [AlertaController::class, 'guardarConfig'])->name('admin.alertas.config.guardar');
    Route::get('/oc-borradores', [AlertaController::class, 'ocBorradores'])->name('admin.alertas.oc');
    Route::post('/oc-borradores/{id}/aprobar', [AlertaController::class, 'aprobarOC'])->name('admin.alertas.oc.aprobar');
    Route::post('/oc-borradores/{id}/rechazar', [AlertaController::class, 'rechazarOC'])->name('admin.alertas.oc.rechazar');
});

// Proveedor — Alta de producto con Excel
Route::prefix('proveedor/productos')->middleware('auth.proveedor')->group(function () {
    Route::get('/mis-productos', [ProductoProveedorController::class, 'index'])->name('proveedores.mis-productos');
    Route::post('/subir-excel', [ProductoProveedorController::class, 'subirExcel'])->name('proveedores.productos.excel');
    Route::get('/excel/{id}/resultado', [ProductoProveedorController::class, 'resultadoExcel'])->name('proveedores.productos.excel.resultado');
});
```

## Integration Points

### Con IaService existente:
- `AlertEngineService` llama a `IaService::llamarClaude()` para generar análisis de proveedores, pronósticos y sugerencias
- Si Claude no responde, se envía la alerta sin análisis (solo métricas numéricas)

### Con NotificacionService existente:
- `AlertEngineService` usa `NotificacionService` para enviar por WhatsApp/email
- Se agrega método `notificarAlerta(Alerta $alerta)` al NotificacionService

### Con AuditService existente:
- Cada alerta generada se registra en audit_log
- Cambios de configuración se registran en audit_log

## Flow Diagrams

### Flujo de Verificación de Inventario (cada 4h):
```
Cron trigger → ia:verificar-inventario
    → Para cada producto activo:
        → Calcular consumo diario (promedio últimos 3 meses)
        → Calcular stock mínimo (consumo_diario × días_entrega_proveedor)
        → IF existencia < stock_mínimo:
            → Calcular cantidad a pedir (stock_máximo - existencia - pendiente)
            → Buscar mejor proveedor (mayor score_total)
            → Crear oc_borrador
            → Crear alerta tipo "stock_bajo" → Admin
            → IF borrador aprobado por admin:
                → Crear alerta tipo "oc_generada" → Proveedor
```

### Flujo de Validación de Excel:
```
Proveedor sube Excel → POST /proveedor/productos/subir-excel
    → Guardar archivo en storage
    → Crear registro en excel_validaciones (estatus: procesando)
    → Dispatch Job: ValidarExcelProducto
        → Leer Excel fila por fila
        → Validar nomenclatura ([TIPO]+[MARCA]+[MODELO]+[MEDIDA]+[ESPECIFICACIÓN])
        → Validar campos obligatorios
        → Verificar duplicados vs catálogo
        → Llamar IaService para validación inteligente (opcional)
        → Actualizar excel_validaciones con resultado
        → IF todo válido:
            → Crear alerta tipo "excel_validado" → Admin (para aprobar)
        → ELSE:
            → Crear alerta tipo "excel_con_errores" → Proveedor (con reporte)
```

## File Structure (New Files)

```
app/
├── Console/Commands/
│   ├── IaEvaluarProveedores.php
│   ├── IaVerificarDocumentos.php
│   ├── IaVerificarInventario.php
│   ├── IaGenerarPronosticos.php
│   ├── IaGenerarSugerencias.php
│   ├── IaOCTrimestral.php
│   └── IaValidarExcel.php
├── Http/Controllers/
│   ├── AlertaController.php
│   └── ProductoProveedorController.php
├── Jobs/
│   ├── ValidarExcelProducto.php
│   ├── GenerarAnalisisIA.php
│   ├── EnviarAlertaMasiva.php
│   └── GenerarOCBorrador.php
├── Models/
│   ├── Alerta.php
│   ├── AlertaConfiguracion.php
│   ├── Pronostico.php
│   ├── OcBorrador.php
│   └── ExcelValidacion.php
├── Services/
│   ├── AlertEngineService.php
│   └── InventarioCalculoService.php
database/migrations/
│   ├── 2026_05_12_000001_create_alertas_table.php
│   ├── 2026_05_12_000002_create_alerta_configuracion_table.php
│   ├── 2026_05_12_000003_create_pronosticos_table.php
│   ├── 2026_05_12_000004_create_oc_borradores_table.php
│   └── 2026_05_12_000005_create_excel_validaciones_table.php
resources/views/
│   ├── admin/alertas/
│   │   ├── index.blade.php
│   │   ├── configuracion.blade.php
│   │   └── oc-borradores.blade.php
│   └── proveedores/
│       └── mis-productos.blade.php
```

## Implementation Priority

| Fase | Qué se implementa | Dependencias |
|---|---|---|
| 1 | Migraciones + Modelos + AlertEngineService base | Ninguna |
| 2 | Comando ia:verificar-documentos (Req 6) | Fase 1 |
| 3 | Comando ia:evaluar-proveedores (Req 1) | Fase 1 |
| 4 | InventarioCalculoService + ia:verificar-inventario (Req 2) | Fase 1 + datos de Alan |
| 5 | ia:generar-pronosticos + ia:generar-sugerencias (Req 4,5) | Fase 1 + IaService |
| 6 | ExcelValidationService + flujo de alta (Req 8) | Fase 1 + guía de producto |
| 7 | ia:oc-trimestral (Req 9) | Fase 4 |
| 8 | Dashboard admin de alertas + configuración (Req 7) | Fase 1-7 |
