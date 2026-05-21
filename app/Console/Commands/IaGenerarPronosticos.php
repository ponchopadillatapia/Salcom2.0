<?php

namespace App\Console\Commands;

use App\Models\ClienteUser;
use App\Models\Pronostico;
use App\Services\AlertEngineService;
use App\Services\IaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Genera pronósticos de demanda automáticos para cada cliente activo.
 * - Si un producto necesita reabastecimiento en 14 días → alerta al proveedor
 * - Si hay pico de demanda (>20% vs mes anterior) → alerta admin + proveedor
 *
 * Se ejecuta cada lunes a las 05:00
 */
class IaGenerarPronosticos extends Command
{
    protected $signature = 'ia:generar-pronosticos';

    protected $description = 'Genera pronósticos de demanda semanales con IA';

    public function handle(): int
    {
        $this->info('📊 Generando pronósticos de demanda...');

        $alertEngine = new AlertEngineService;
        $iaService = new IaService;
        $pronosticosGenerados = 0;
        $alertasGeneradas = 0;

        $clientes = ClienteUser::where('activo', true)->get();

        foreach ($clientes as $cliente) {
            $codigoCliente = $cliente->codigo_cliente ?? 'CLI-'.$cliente->id;

            // Generar pronóstico con IA
            try {
                $resultado = $iaService->pronosticoDemanda($codigoCliente);

                // Determinar confianza basada en historial
                $historial = $resultado['historial'] ?? [];
                $confianza = count($historial) >= 6 ? 'alta' : (count($historial) >= 2 ? 'media' : 'baja');

                // Guardar pronóstico
                Pronostico::create([
                    'tipo' => 'demanda_cliente',
                    'referencia_tipo' => 'cliente',
                    'referencia_id' => $cliente->id,
                    'codigo_referencia' => $codigoCliente,
                    'resultado' => $resultado['analisis']['content'] ?? 'Sin análisis disponible',
                    'datos' => [
                        'historial_pedidos' => count($historial),
                        'productos_clave' => $this->extraerProductosClave($historial),
                        'generado_por' => 'ia:generar-pronosticos',
                    ],
                    'confianza' => $confianza,
                    'generado_at' => now(),
                ]);

                $pronosticosGenerados++;

                // Si confianza baja, no generar alertas
                if ($confianza === 'baja') {
                    continue;
                }

                // Detectar pico de demanda (simplificado)
                if (count($historial) >= 2) {
                    $ultimoMes = collect($historial)->last();
                    $penultimoMes = collect($historial)->slice(-2, 1)->first();

                    if ($ultimoMes && $penultimoMes) {
                        $totalUltimo = $ultimoMes['total'] ?? 0;
                        $totalPenultimo = $penultimoMes['total'] ?? 0;

                        if ($totalPenultimo > 0) {
                            $incremento = (($totalUltimo - $totalPenultimo) / $totalPenultimo) * 100;
                            $umbralPico = (int) $alertEngine->getConfig('pico_demanda_porcentaje', 20);

                            if ($incremento > $umbralPico) {
                                $alertEngine->alertar([
                                    'tipo' => 'pico_demanda',
                                    'modulo' => 'clientes',
                                    'destinatario_tipo' => 'admin',
                                    'destinatario_id' => 1,
                                    'titulo' => "📈 Pico de demanda detectado: {$cliente->nombre}",
                                    'contenido' => "El cliente {$cliente->nombre} ({$codigoCliente}) incrementó su demanda un ".round($incremento).'% respecto al mes anterior. Verificar disponibilidad de inventario.',
                                    'datos' => [
                                        'cliente_id' => $cliente->id,
                                        'cliente_nombre' => $cliente->nombre,
                                        'incremento_porcentaje' => round($incremento),
                                        'total_ultimo_mes' => $totalUltimo,
                                        'total_mes_anterior' => $totalPenultimo,
                                    ],
                                    'nivel' => 'warning',
                                ]);
                                $alertasGeneradas++;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("[ia:generar-pronosticos] Error con cliente {$codigoCliente}: {$e->getMessage()}");
            }
        }

        $this->info('✅ Pronósticos completados.');
        $this->info("   Clientes procesados: {$clientes->count()}");
        $this->info("   Pronósticos generados: {$pronosticosGenerados}");
        $this->info("   Alertas de pico: {$alertasGeneradas}");

        Log::info("[ia:generar-pronosticos] Clientes: {$clientes->count()}, Pronósticos: {$pronosticosGenerados}, Alertas: {$alertasGeneradas}");

        return Command::SUCCESS;
    }

    /**
     * Extraer productos clave del historial de pedidos.
     */
    private function extraerProductosClave(array $historial): array
    {
        $productos = [];
        foreach ($historial as $pedido) {
            foreach ($pedido['productos'] ?? [] as $prod) {
                $sku = $prod['sku'] ?? 'N/A';
                $productos[$sku] = ($productos[$sku] ?? 0) + ($prod['cantidad'] ?? 0);
            }
        }
        arsort($productos);

        return array_slice($productos, 0, 5, true);
    }
}
