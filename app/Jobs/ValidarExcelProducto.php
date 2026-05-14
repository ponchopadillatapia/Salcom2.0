<?php

namespace App\Jobs;

use App\Models\ExcelValidacion;
use App\Models\Producto;
use App\Services\AlertEngineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Job que procesa y valida un Excel de alta de producto subido por un proveedor.
 *
 * Validaciones:
 * - Campos obligatorios completos
 * - Formato de nomenclatura: [TIPO]+[MARCA]+[MODELO]+[MEDIDA]+[ESPECIFICACIÓN]
 * - Unidades de medida válidas
 * - Sin duplicados en catálogo existente
 */
class ValidarExcelProducto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $validacionId
    ) {}

    public function handle(): void
    {
        $validacion = ExcelValidacion::find($this->validacionId);
        if (! $validacion) {
            return;
        }

        Log::info("[ValidarExcelProducto] Procesando validación #{$this->validacionId}");

        $alertEngine = new AlertEngineService;
        $errores = [];
        $productosValidos = 0;
        $productosConError = 0;

        // Leer archivo (por ahora simulado — cuando se implemente usar PhpSpreadsheet)
        $productos = $this->leerExcel($validacion->archivo_path);

        if (empty($productos)) {
            $validacion->update([
                'estatus' => 'con_errores',
                'errores' => [['fila' => 0, 'campo' => 'archivo', 'error' => 'No se pudieron leer productos del archivo']],
            ]);
            return;
        }

        $validacion->update(['total_productos' => count($productos)]);

        foreach ($productos as $index => $producto) {
            $fila = $index + 2; // +2 porque fila 1 es header
            $erroresFila = [];

            // 1. Campos obligatorios
            $camposObligatorios = ['nombre', 'familia', 'unidad_medida', 'precio'];
            foreach ($camposObligatorios as $campo) {
                if (empty($producto[$campo] ?? '')) {
                    $erroresFila[] = [
                        'fila' => $fila,
                        'campo' => $campo,
                        'error' => "Campo obligatorio vacío",
                        'valor_actual' => '',
                        'valor_esperado' => 'Requerido',
                    ];
                }
            }

            // 2. Validar nomenclatura (debe tener al menos 3 partes separadas por espacio)
            $nombre = $producto['nombre'] ?? '';
            if ($nombre && count(explode(' ', trim($nombre))) < 3) {
                $erroresFila[] = [
                    'fila' => $fila,
                    'campo' => 'nombre',
                    'error' => 'Nomenclatura incompleta. Formato: [TIPO] [MARCA] [MODELO] [MEDIDA] [ESPECIFICACIÓN]',
                    'valor_actual' => $nombre,
                    'valor_esperado' => 'Ej: RESINA EPOXICA SKF 500ML INDUSTRIAL',
                ];
            }

            // 3. Validar unidad de medida
            $unidadesValidas = ['KG', 'LT', 'PZA', 'MT', 'ML', 'GR', 'GAL', 'TON', 'ROLLO', 'CAJA'];
            $unidad = strtoupper($producto['unidad_medida'] ?? '');
            if ($unidad && ! in_array($unidad, $unidadesValidas)) {
                $erroresFila[] = [
                    'fila' => $fila,
                    'campo' => 'unidad_medida',
                    'error' => 'Unidad de medida no válida',
                    'valor_actual' => $unidad,
                    'valor_esperado' => implode(', ', $unidadesValidas),
                ];
            }

            // 4. Validar precio numérico
            $precio = $producto['precio'] ?? '';
            if ($precio && ! is_numeric($precio)) {
                $erroresFila[] = [
                    'fila' => $fila,
                    'campo' => 'precio',
                    'error' => 'El precio debe ser numérico',
                    'valor_actual' => $precio,
                    'valor_esperado' => 'Ej: 150.50',
                ];
            }

            // 5. Verificar duplicados
            if ($nombre) {
                $nombreNormalizado = $this->normalizarNombre($nombre);
                $duplicado = Producto::whereRaw('UPPER(REPLACE(nombre, " ", "")) = ?', [strtoupper(str_replace(' ', '', $nombreNormalizado))])->exists();

                if ($duplicado) {
                    $erroresFila[] = [
                        'fila' => $fila,
                        'campo' => 'nombre',
                        'error' => 'Producto duplicado en catálogo',
                        'valor_actual' => $nombre,
                        'valor_esperado' => 'Nombre único',
                    ];
                }
            }

            if (! empty($erroresFila)) {
                $errores = array_merge($errores, $erroresFila);
                $productosConError++;
            } else {
                $productosValidos++;
            }
        }

        // Actualizar validación
        $estatus = $productosConError === 0 ? 'validado' : 'con_errores';
        $validacion->update([
            'productos_validos' => $productosValidos,
            'productos_con_error' => $productosConError,
            'errores' => $errores ?: null,
            'estatus' => $estatus,
        ]);

        // Generar alerta según resultado
        if ($estatus === 'validado') {
            $alertEngine->alertar([
                'tipo' => 'excel_validado',
                'modulo' => 'productos',
                'destinatario_tipo' => 'admin',
                'destinatario_id' => 1,
                'titulo' => "✅ Excel validado: {$productosValidos} productos listos para aprobar",
                'contenido' => "Un proveedor subió un Excel con {$productosValidos} productos. Todos pasaron la validación. Requiere tu aprobación para dar de alta.",
                'datos' => [
                    'validacion_id' => $validacion->id,
                    'proveedor_id' => $validacion->proveedor_id,
                    'productos_validos' => $productosValidos,
                ],
                'nivel' => 'info',
            ]);
        } else {
            $alertEngine->alertar([
                'tipo' => 'excel_con_errores',
                'modulo' => 'productos',
                'destinatario_tipo' => 'proveedor',
                'destinatario_id' => $validacion->proveedor_id,
                'titulo' => "❌ Excel con errores: {$productosConError} productos necesitan corrección",
                'contenido' => "Tu Excel tiene {$productosConError} productos con errores. Revisa el reporte de errores y vuelve a subir el archivo corregido.",
                'datos' => [
                    'validacion_id' => $validacion->id,
                    'total_errores' => count($errores),
                    'productos_con_error' => $productosConError,
                    'productos_validos' => $productosValidos,
                ],
                'nivel' => 'warning',
            ]);
        }

        Log::info("[ValidarExcelProducto] Completado. Válidos: {$productosValidos}, Errores: {$productosConError}");
    }

    /**
     * Leer productos del Excel.
     * TODO: Implementar con PhpSpreadsheet cuando se tenga el template real.
     */
    private function leerExcel(string $path): array
    {
        // Por ahora retornar array vacío — se implementará con la guía de alta
        // En producción: usar PhpSpreadsheet para leer el .xlsx
        return [];
    }

    /**
     * Normalizar nombre para comparación de duplicados.
     */
    private function normalizarNombre(string $nombre): string
    {
        $nombre = Str::upper($nombre);
        $nombre = Str::ascii($nombre); // Quitar acentos
        $nombre = preg_replace('/\s+/', ' ', $nombre); // Espacios múltiples → uno
        return trim($nombre);
    }
}
