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

/**
 * Job que procesa un lote de productos (máx 50) de la migración masiva.
 * Llama a la IA para descomponer cada producto del sistema viejo en el formato nuevo:
 * NOMBRE_TIPO, NOMBRE_MARCA, NOMBRE_MODELO, NOMBRE_MEDIDA, NOMBRE_ESPECIFICACION
 */
class ProcesarMigracionLote implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de intentos antes de fallar.
     */
    public int $tries = 3;

    /**
     * Timeout en segundos (la IA puede tardar).
     */
    public int $timeout = 120;

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

        // Marcar como procesando si es el primer lote
        if ($migracion->estatus === 'pendiente') {
            $migracion->update(['estatus' => 'procesando']);
        }

        Log::info("[MigracionMasiva] Procesando lote {$this->loteNumero} de migración #{$this->migracionId} ({$this->cantidadProductos()} productos)");

        try {
            $iaService = new IaService();

            // TODO: prompt de IA para descomponer productos - pendiente de ver formato del Excel viejo
            // El prompt deberá:
            // 1. Recibir los productos del sistema viejo (probablemente un campo "nombre" o "descripcion" concatenado)
            // 2. Descomponer cada producto en: NOMBRE_TIPO, NOMBRE_MARCA, NOMBRE_MODELO, NOMBRE_MEDIDA, NOMBRE_ESPECIFICACION
            // 3. Asignar FAMILIA y TIPO_PRODUCTO según el contexto
            // 4. Devolver JSON con los productos transformados
            $prompt = $this->construirPrompt();

            $resultado = $iaService->llamarClaude($prompt);

            if ($resultado['success'] && $resultado['content']) {
                // Parsear respuesta de la IA
                $contenido = preg_replace('/```json\s*/', '', $resultado['content']);
                $contenido = preg_replace('/```\s*/', '', $contenido);
                $iaResult = json_decode(trim($contenido), true);

                if ($iaResult && isset($iaResult['productos'])) {
                    $procesados = count($iaResult['productos']);

                    // Actualizar contadores de la migración
                    $migracion->increment('productos_procesados', $procesados);
                    $migracion->increment('lotes_completados');

                    Log::info("[MigracionMasiva] Lote {$this->loteNumero} completado: {$procesados} productos procesados");
                } else {
                    // La IA respondió pero no se pudo parsear
                    $this->marcarLoteConError($migracion, 'Respuesta de IA no parseable');
                }
            } else {
                // Error en la llamada a la IA
                $this->marcarLoteConError($migracion, 'Error al llamar a la IA');
            }

        } catch (\Exception $e) {
            $this->marcarLoteConError($migracion, $e->getMessage());
        }

        // Verificar si la migración terminó
        $this->verificarFinalizacion($migracion);
    }

    /**
     * Construir el prompt para la IA.
     * TODO: Adaptar cuando se conozca el formato del Excel del sistema viejo.
     */
    private function construirPrompt(): string
    {
        // TODO: prompt de IA para descomponer productos - pendiente de ver formato del Excel viejo
        $productosJson = json_encode($this->productos, JSON_UNESCAPED_UNICODE);

        return "Eres un experto en catalogación de productos industriales. Tu trabajo es tomar productos del sistema viejo y descomponerlos en el formato nuevo de Salcom.

FORMATO NUEVO (cada producto debe tener):
- NOMBRE_TIPO: Qué ES el producto (ej: MOTOR ELECTRICO, RESINA EPOXICA)
- NOMBRE_MARCA: Quién lo fabrica (ej: WEG, SKF, 3M)
- NOMBRE_MODELO: Referencia del fabricante (ej: W22, IND-500)
- NOMBRE_MEDIDA: Tamaño/capacidad con números (ej: 500ML, 3HP)
- NOMBRE_ESPECIFICACION: Detalle adicional (ej: TRIFASICO, TRANSPARENTE)
- FAMILIA: Categoría (ej: MATERIA PRIMA, MANTENIMIENTO, QUIMICOS)
- TIPO_PRODUCTO: MPI (Materia Prima Import), ME (Material Empaque), MN (Mantenimiento)

Productos del sistema viejo a procesar:
{$productosJson}

Responde ÚNICAMENTE JSON válido:
{\"productos\": [{\"original\": \"...\", \"NOMBRE_TIPO\": \"...\", \"NOMBRE_MARCA\": \"...\", \"NOMBRE_MODELO\": \"...\", \"NOMBRE_MEDIDA\": \"...\", \"NOMBRE_ESPECIFICACION\": \"...\", \"FAMILIA\": \"...\", \"TIPO_PRODUCTO\": \"...\"}]}";
    }

    /**
     * Marcar un lote como fallido.
     */
    private function marcarLoteConError(MigracionMasiva $migracion, string $error): void
    {
        $migracion->increment('productos_error', $this->cantidadProductos());
        $migracion->increment('lotes_completados');

        Log::error("[MigracionMasiva] Error en lote {$this->loteNumero} de migración #{$this->migracionId}: {$error}");
    }

    /**
     * Verificar si todos los lotes terminaron para marcar la migración como completada.
     */
    private function verificarFinalizacion(MigracionMasiva $migracion): void
    {
        $migracion->refresh();

        if ($migracion->lotes_completados >= $migracion->lotes_total) {
            $nuevoEstatus = $migracion->productos_error > 0 ? 'error' : 'completado';
            $migracion->update(['estatus' => $nuevoEstatus]);

            Log::info("[MigracionMasiva] Migración #{$this->migracionId} finalizada con estatus: {$nuevoEstatus}");
        }
    }

    /**
     * Cantidad de productos en este lote.
     */
    private function cantidadProductos(): int
    {
        return count($this->productos);
    }

    /**
     * Manejar fallo del job (después de todos los reintentos).
     */
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
