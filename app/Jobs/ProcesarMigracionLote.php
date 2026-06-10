<?php

namespace App\Jobs;

use App\Models\MigracionMasiva;
use App\Services\IaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Job que procesa un lote de productos (máx 50) de la migración masiva.
 * Llama a la IA para descomponer cada producto del sistema viejo (SAP) en el formato nuevo:
 * NOMBRE_TIPO, NOMBRE_MARCA, NOMBRE_MODELO, NOMBRE_MEDIDA, NOMBRE_ESPECIFICACION
 */
class ProcesarMigracionLote implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        private int $migracionId,
        private int $loteNumero,
        private array $productos
    ) {
        $this->onQueue('migraciones');
    }

    public function handle(): void
    {
        $migracion = MigracionMasiva::find($this->migracionId);
        if (!$migracion) {
            Log::error("[MigracionMasiva] Migración #{$this->migracionId} no encontrada");
            return;
        }

        if ($migracion->estatus === 'pendiente') {
            $migracion->update(['estatus' => 'procesando']);
        }

        Log::info("[MigracionMasiva] Procesando lote {$this->loteNumero} de migración #{$this->migracionId} (" . count($this->productos) . " productos)");

        try {
            $iaService = new IaService();
            $prompt = $this->construirPrompt();
            $resultado = $iaService->llamarClaude($prompt);

            if ($resultado['success'] && $resultado['content']) {
                $contenido = preg_replace('/```json\s*/', '', $resultado['content']);
                $contenido = preg_replace('/```\s*/', '', $contenido);
                $iaResult = json_decode(trim($contenido), true);

                if ($iaResult && isset($iaResult['productos'])) {
                    $procesados = count($iaResult['productos']);

                    // Guardar resultados en archivo JSON acumulativo
                    $this->guardarResultados($iaResult['productos']);

                    $migracion->increment('productos_procesados', $procesados);
                    $migracion->increment('lotes_completados');

                    Log::info("[MigracionMasiva] Lote {$this->loteNumero} completado: {$procesados} productos procesados");
                } else {
                    $this->marcarLoteConError($migracion, 'Respuesta de IA no parseable');
                }
            } else {
                $this->marcarLoteConError($migracion, 'Error al llamar a la IA: ' . ($resultado['error'] ?? 'desconocido'));
            }

        } catch (\Exception $e) {
            $this->marcarLoteConError($migracion, $e->getMessage());
        }

        $this->verificarFinalizacion($migracion);
    }

    /**
     * Prompt adaptado al formato real del sistema viejo (SAP Business One).
     * Cada producto viene con: codigo, nombre (todo junto), grupo.
     */
    private function construirPrompt(): string
    {
        $productosTexto = "";
        foreach ($this->productos as $i => $prod) {
            $productosTexto .= ($i + 1) . ". CODIGO: {$prod['codigo']} | NOMBRE: {$prod['nombre']} | GRUPO: {$prod['grupo']}\n";
        }

        return "Eres un experto en catalogacion de productos de limpieza, aerosoles y quimicos industriales.

Tu trabajo: Tomar el NOMBRE completo de cada producto (que viene del sistema viejo TODO JUNTO) y SEPARARLO en los campos del formato nuevo.

CAMPOS A LLENAR:
- NOMBRE_TIPO: Que ES el producto (ej: ABRILLANTADOR DE MUEBLES, LUSTRADOR DE MUEBLES, AIR FRESHENER, LIMPIADOR MULTIUSOS)
- NOMBRE_MARCA: La MARCA (ej: WIESE, SELECTO, GREAT VALUE, JAZZEE, SURE SCENTS, GV, GLAMOUROSO, VECTAIR, ANGEL OF MINE)
- NOMBRE_MODELO: Se llena con el GRUPO del sistema viejo (la parte despues del guion, en mayusculas). Ejemplos: Si GRUPO es '32-Abrillantador 8oz' -> NOMBRE_MODELO = 'ABRILLANTADOR 8OZ'. Si GRUPO es '59-Accesorios Plásticos' -> NOMBRE_MODELO = 'ACCESORIOS PLASTICOS'. Si GRUPO es '53-Aerosol 10oz' -> NOMBRE_MODELO = 'AEROSOL 10OZ'. Si ademas el nombre tiene un codigo numerico (ej: 20012420), ponerlo ANTES: '20012420 ABRILLANTADOR 8OZ'. Si el nombre tiene codigo pero no hay grupo, solo el codigo: '20012420'.
- NOMBRE_MEDIDA: Peso/volumen/tamano con NUMEROS (ej: 323G, 226G, 400ML, 10OZ, 355G, 102.9G). Incluir C/12, C/6 si es presentacion. Si dice 12pieces o 12pcs, convertir a C/12. Si dice 6 pcs, convertir a C/6.
- NOMBRE_ESPECIFICACION: Aroma, color o detalle adicional (ej: NARANJA, LIMON, COTTON BREEZE, BABY, LIGHT GREY)
- FAMILIA: DEBE ser una de estas opciones EXACTAS: QUIMICOS, ELECTRICO, FERRETERIA, MANTENIMIENTO, SEGURIDAD, EMPAQUE, MATERIA PRIMA, CONSUMIBLE, LUBRICANTES, ADHESIVOS, PINTURAS, SOLVENTES, RESINAS, PIGMENTOS, ADITIVOS, AEROSOLES, INSECTICIDAS, LIMPIEZA, HERRAMIENTAS, REFACCIONES, MOTORES, BOMBAS, VALVULAS, TUBERIAS, TORNILLERIA, MATERIAL EMPAQUE, PRODUCTO TERMINADO, INSUMOS. Elige la mas cercana al producto. Para aerosoles/abrillantadores/air freshener usa AEROSOLES. Para accesorios plasticos/envases usa EMPAQUE. NO copiar el grupo viejo tal cual.
- TIPO_PRODUCTO: MPI (Materia Prima Importacion), ME (Material Empaque), MN (Mantenimiento). Si es limpieza/aerosol/quimico = MN, si es empaque/envase/accesorio = ME, si es materia prima = MPI. Por default MN.

REGLAS:
- Todo en MAYUSCULAS
- Si el nombre empieza con un numero largo (ej: 20012420), ese codigo va al INICIO de NOMBRE_MODELO seguido del grupo
- C/12, C/6 Pzas, etc. van en NOMBRE_MEDIDA (es la presentacion)
- NOMBRE_MODELO SIEMPRE se llena con el grupo del sistema viejo. Nunca dejarlo vacio.
- Si no hay especificacion clara, dejar NOMBRE_ESPECIFICACION vacio
- NO incluir UNIDAD_MEDIDA, CLAVE_SAT ni LOTE en tu respuesta (el sistema los llena automaticamente)
- Solo responde: codigo, NOMBRE_TIPO, NOMBRE_MARCA, NOMBRE_MODELO, NOMBRE_MEDIDA, NOMBRE_ESPECIFICACION, FAMILIA, TIPO_PRODUCTO

PRODUCTOS A PROCESAR:
{$productosTexto}

Responde UNICAMENTE JSON valido sin markdown:
{\"productos\": [{\"codigo\": \"MAEHO17\", \"NOMBRE_TIPO\": \"PROTECTOR DE MUEBLES\", \"NOMBRE_MARCA\": \"SELECTO\", \"NOMBRE_MODELO\": \"ABRILLANTADOR 400ML\", \"NOMBRE_MEDIDA\": \"323G C/12\", \"NOMBRE_ESPECIFICACION\": \"NARANJA\", \"FAMILIA\": \"AEROSOLES\", \"TIPO_PRODUCTO\": \"MN\"}, {\"codigo\": \"EAEHO237\", \"NOMBRE_TIPO\": \"FURNITURE POLISHER\", \"NOMBRE_MARCA\": \"JAZZEE\", \"NOMBRE_MODELO\": \"20012420 ABRILLANTADOR 400ML\", \"NOMBRE_MEDIDA\": \"323G C/12\", \"NOMBRE_ESPECIFICACION\": \"LEMON\", \"FAMILIA\": \"AEROSOLES\", \"TIPO_PRODUCTO\": \"MN\"}]}";
    }

    /**
     * Guardar resultados del lote en archivo JSON acumulativo.
     * Combina lo que la IA devuelve con los datos directos del Excel (CLAVE_SAT, LOTE, UNIDAD_MEDIDA).
     */
    private function guardarResultados(array $productosIA): void
    {
        $jsonPath = storage_path('app/public/migraciones-masivas/resultado_' . $this->migracionId . '.json');

        // Leer resultados previos
        $existentes = [];
        if (file_exists($jsonPath)) {
            $contenido = file_get_contents($jsonPath);
            $existentes = json_decode($contenido, true) ?? [];
        }

        // Indexar productos originales por código para buscar datos directos
        $originalesPorCodigo = [];
        foreach ($this->productos as $prod) {
            $originalesPorCodigo[$prod['codigo']] = $prod;
        }

        // Agregar nuevos resultados combinando IA + datos directos
        foreach ($productosIA as $prod) {
            $codigo = $prod['codigo'] ?? '';
            $original = $originalesPorCodigo[$codigo] ?? [];

            // Datos directos del Excel viejo (no dependen de la IA)
            $prod['CLAVE_SAT'] = $original['clave_sat'] ?? '';
            $prod['LOTE'] = $original['lote'] ?? 'NO';
            $prod['UNIDAD_MEDIDA'] = $original['unidad_medida'] ?? 'CAJA';
            $prod['PEDIMENTO'] = ($prod['TIPO_PRODUCTO'] ?? '') === 'MPI' ? 'SI' : 'NO';

            $existentes[] = $prod;
        }

        // Guardar actualizado
        file_put_contents($jsonPath, json_encode($existentes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function marcarLoteConError(MigracionMasiva $migracion, string $error): void
    {
        $migracion->increment('productos_error', count($this->productos));
        $migracion->increment('lotes_completados');
        Log::error("[MigracionMasiva] Error en lote {$this->loteNumero} de migración #{$this->migracionId}: {$error}");
    }

    /**
     * Si todos los lotes terminaron, generar Excel de resultado y marcar completada.
     */
    private function verificarFinalizacion(MigracionMasiva $migracion): void
    {
        $migracion->refresh();

        if ($migracion->lotes_completados >= $migracion->lotes_total) {
            $nuevoEstatus = $migracion->productos_error > 0 ? 'completado' : 'completado';
            $migracion->update(['estatus' => $nuevoEstatus]);

            // Generar Excel de resultado
            try {
                $excelPath = $this->generarExcelResultado($migracion);
                $migracion->update(['resultado_path' => $excelPath]);
                Log::info("[MigracionMasiva] Excel de resultado generado: {$excelPath}");
            } catch (\Exception $e) {
                Log::error("[MigracionMasiva] Error generando Excel de resultado: " . $e->getMessage());
            }

            Log::info("[MigracionMasiva] Migración #{$this->migracionId} finalizada. Procesados: {$migracion->productos_procesados}, Errores: {$migracion->productos_error}");
        }
    }

    /**
     * Generar Excel con los productos ya en formato nuevo (descargable).
     * Marca en ROJO las celdas obligatorias que quedaron vacías.
     */
    private function generarExcelResultado(MigracionMasiva $migracion): string
    {
        $jsonPath = storage_path('app/public/migraciones-masivas/resultado_' . $migracion->id . '.json');
        $productos = [];
        if (file_exists($jsonPath)) {
            $productos = json_decode(file_get_contents($jsonPath), true) ?? [];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos Migrados');

        // Headers del formato nuevo completo (igual que el template de alta)
        $headers = ['CODIGO', 'NOMBRE_TIPO', 'NOMBRE_MARCA', 'NOMBRE_MODELO', 'NOMBRE_MEDIDA', 'NOMBRE_ESPECIFICACION', 'FAMILIA', 'TIPO_PRODUCTO', 'UNIDAD_MEDIDA', 'PRECIO', 'CLAVE_SAT', 'LOTE', 'PEDIMENTO', 'VOLTAJE'];
        $obligatorios = ['CODIGO' => true, 'NOMBRE_TIPO' => true, 'NOMBRE_MARCA' => true, 'NOMBRE_MODELO' => true, 'NOMBRE_MEDIDA' => true, 'NOMBRE_ESPECIFICACION' => true, 'FAMILIA' => true, 'TIPO_PRODUCTO' => true, 'UNIDAD_MEDIDA' => false, 'PRECIO' => false, 'CLAVE_SAT' => false, 'LOTE' => false, 'PEDIMENTO' => false, 'VOLTAJE' => false];

        // Mapeo de header a columna letra
        $headerToCol = [];
        $col = 'A';
        foreach ($headers as $header) {
            $headerToCol[$header] = $col;
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            if ($obligatorios[$header]) {
                $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6B3FA0');
            } else {
                $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('9B7BC7');
            }
            $sheet->getStyle($col . '1')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Llenar con datos y marcar en rojo celdas obligatorias vacías
        $row = 2;
        foreach ($productos as $prod) {
            $valores = [
                'CODIGO' => $prod['codigo'] ?? '',
                'NOMBRE_TIPO' => $prod['NOMBRE_TIPO'] ?? '',
                'NOMBRE_MARCA' => $prod['NOMBRE_MARCA'] ?? '',
                'NOMBRE_MODELO' => $prod['NOMBRE_MODELO'] ?? '',
                'NOMBRE_MEDIDA' => $prod['NOMBRE_MEDIDA'] ?? '',
                'NOMBRE_ESPECIFICACION' => $prod['NOMBRE_ESPECIFICACION'] ?? '',
                'FAMILIA' => $prod['FAMILIA'] ?? '',
                'TIPO_PRODUCTO' => $prod['TIPO_PRODUCTO'] ?? '',
                'UNIDAD_MEDIDA' => $prod['UNIDAD_MEDIDA'] ?? '',
                'PRECIO' => $prod['PRECIO'] ?? '',
                'CLAVE_SAT' => $prod['CLAVE_SAT'] ?? '',
                'LOTE' => $prod['LOTE'] ?? '',
                'PEDIMENTO' => $prod['PEDIMENTO'] ?? '',
                'VOLTAJE' => $prod['VOLTAJE'] ?? '',
            ];

            foreach ($valores as $header => $valor) {
                $celda = $headerToCol[$header] . $row;
                $sheet->setCellValue($celda, $valor);

                // Si es obligatorio y está vacío, pintar en rojo
                if ($obligatorios[$header] && empty(trim($valor))) {
                    $sheet->getStyle($celda)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
                    $sheet->getStyle($celda)->getFont()->getColor()->setRGB('991B1B');
                }
            }

            $row++;
        }

        // Guardar
        $excelPath = 'migraciones-masivas/resultado_' . $migracion->id . '.xlsx';
        $fullPath = storage_path('app/public/' . $excelPath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        // Limpiar JSON temporal
        if (file_exists($jsonPath)) {
            unlink($jsonPath);
        }

        return $excelPath;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[MigracionMasiva] Job falló permanentemente para lote {$this->loteNumero} de migración #{$this->migracionId}: {$exception->getMessage()}");

        $migracion = MigracionMasiva::find($this->migracionId);
        if ($migracion) {
            $this->marcarLoteConError($migracion, 'Job falló permanentemente: ' . $exception->getMessage());
            $this->verificarFinalizacion($migracion);
        }
    }
}
