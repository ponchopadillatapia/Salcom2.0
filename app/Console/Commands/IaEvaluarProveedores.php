<?php

namespace App\Console\Commands;

use App\Models\ProveedorUser;
use App\Services\AlertEngineService;
use App\Services\IaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Evalúa el rendimiento de todos los proveedores activos.
 * - Score por debajo del umbral crítico → alerta al admin
 * - Patrón de retrasos consecutivos → alerta inmediata
 * - Genera análisis con IA (Claude) si está disponible
 *
 * Se ejecuta diariamente a las 06:00
 */
class IaEvaluarProveedores extends Command
{
    protected $signature = 'ia:evaluar-proveedores';

    protected $description = 'Evalúa rendimiento OTIF de proveedores y detecta bajo rendimiento';

    public function handle(): int
    {
        $this->info('🔍 Evaluando proveedores...');

        $alertEngine = new AlertEngineService;
        $umbralCritico = $alertEngine->getUmbralCritico();

        $proveedores = ProveedorUser::where('activo', true)
            ->whereNotNull('score_total')
            ->where('score_total', '>', 0)
            ->get();

        $alertasGeneradas = 0;
        $proveedoresBajos = [];

        foreach ($proveedores as $proveedor) {
            $scoreTotalNum = (float) $proveedor->score_total;

            // Verificar si está por debajo del umbral crítico
            if ($scoreTotalNum < $umbralCritico) {
                $proveedoresBajos[] = $proveedor;

                if (! $alertEngine->existeAlertaActiva('proveedor_bajo_rendimiento', 'admin', 1)) {
                    // Intentar generar análisis con IA
                    $analisis = $this->generarAnalisisIA($proveedor);

                    $alertEngine->alertar([
                        'tipo' => 'proveedor_bajo_rendimiento',
                        'modulo' => 'proveedores',
                        'destinatario_tipo' => 'admin',
                        'destinatario_id' => 1,
                        'titulo' => "⚠️ Proveedor bajo rendimiento: {$proveedor->nombre}",
                        'contenido' => $analisis ?? "El proveedor {$proveedor->nombre} tiene un score de {$scoreTotalNum}% (umbral: {$umbralCritico}%). Score entrega: {$proveedor->score_entrega}%, Score puntualidad: {$proveedor->score_puntualidad}%.",
                        'datos' => [
                            'proveedor_id' => $proveedor->id,
                            'proveedor_nombre' => $proveedor->nombre,
                            'score_total' => $scoreTotalNum,
                            'score_entrega' => $proveedor->score_entrega,
                            'score_puntualidad' => $proveedor->score_puntualidad,
                            'umbral' => $umbralCritico,
                            'analisis_ia' => $analisis ? true : false,
                        ],
                        'nivel' => $scoreTotalNum < 40 ? 'critical' : 'warning',
                    ]);
                    $alertasGeneradas++;
                }
            }

            // Verificar patrón de retrasos consecutivos
            // TODO: Implementar cuando tengamos tracking real de entregas
            // Por ahora verificamos si score_entrega es muy bajo
            if ($proveedor->score_entrega > 0 && $proveedor->score_entrega < 50) {
                if (! $alertEngine->existeAlertaActiva('patron_retraso_detectado', 'admin', 1)) {
                    $alertEngine->alertar([
                        'tipo' => 'patron_retraso_detectado',
                        'modulo' => 'proveedores',
                        'destinatario_tipo' => 'admin',
                        'destinatario_id' => 1,
                        'titulo' => "🚨 Patrón de retraso: {$proveedor->nombre}",
                        'contenido' => "El proveedor {$proveedor->nombre} tiene un score de entrega de solo {$proveedor->score_entrega}%, lo que indica un patrón de retrasos frecuentes.",
                        'datos' => [
                            'proveedor_id' => $proveedor->id,
                            'proveedor_nombre' => $proveedor->nombre,
                            'score_entrega' => $proveedor->score_entrega,
                        ],
                        'nivel' => 'critical',
                    ]);
                    $alertasGeneradas++;
                }
            }
        }

        $this->info("✅ Evaluación completada. {$proveedores->count()} proveedores evaluados, {$alertasGeneradas} alertas generadas.");
        $this->info("   Proveedores bajo umbral ({$umbralCritico}%): " . count($proveedoresBajos));

        Log::info("[ia:evaluar-proveedores] Completado. Evaluados: {$proveedores->count()}, Alertas: {$alertasGeneradas}, Bajo umbral: " . count($proveedoresBajos));

        return Command::SUCCESS;
    }

    /**
     * Generar análisis con IA (Claude) sobre el proveedor.
     */
    private function generarAnalisisIA(ProveedorUser $proveedor): ?string
    {
        try {
            $iaService = new IaService;

            $prompt = "Analiza el rendimiento de este proveedor de Industrias Salcom y da recomendaciones breves (máximo 3 puntos):

Proveedor: {$proveedor->nombre}
Score Total: {$proveedor->score_total}%
Score Entrega: {$proveedor->score_entrega}%
Score Puntualidad: {$proveedor->score_puntualidad}%

Responde en español, máximo 100 palabras. Incluye:
1. Diagnóstico breve
2. Riesgo principal
3. Acción recomendada";

            $resultado = $iaService->llamarClaude($prompt);

            if ($resultado['success'] && $resultado['content']) {
                return $resultado['content'];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("[ia:evaluar-proveedores] No se pudo generar análisis IA: {$e->getMessage()}");

            return null;
        }
    }
}
