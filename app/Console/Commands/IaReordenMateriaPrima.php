<?php

namespace App\Console\Commands;

use App\Services\AlertEngineService;
use App\Services\ReordenCalculoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Genera OC automáticas de materia prima basadas en punto de reorden.
 *
 * En modo --dry-run simula el proceso mostrando los resultados sin crear OC.
 * En modo normal ejecuta el proceso completo y envía alerta al admin con resumen.
 */
class IaReordenMateriaPrima extends Command
{
    protected $signature = 'ia:reorden-mp {--dry-run : Simular sin crear OC}';

    protected $description = 'Genera OC automáticas de materia prima basadas en punto de reorden';

    public function __construct(
        private ReordenCalculoService $reordenService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            return $this->ejecutarDryRun();
        }

        return $this->ejecutarProcesoCompleto();
    }

    /**
     * Modo dry-run: evalúa productos y muestra tabla con resultados sin crear OC.
     */
    private function ejecutarDryRun(): int
    {
        $this->info('🔍 Modo dry-run: simulando proceso de reorden sin crear OC...');
        $this->newLine();

        $productos = \App\Models\Producto::where('activo', true)
            ->whereNotNull('stock_minimo')
            ->get();

        if ($productos->isEmpty()) {
            $this->info('ℹ️  No hay productos con stock mínimo configurado.');

            return Command::SUCCESS;
        }

        $productosConOcExistente = $this->obtenerProductosConOcExistente();
        $resultados = [];

        foreach ($productos as $producto) {
            if (in_array($producto->codigo, $productosConOcExistente, true)) {
                continue;
            }

            $consumoPromedio = $this->reordenService->calcularConsumoPromedio($producto->codigo);
            $leadTimeDias = $producto->lead_time_dias ?? 15;
            $cantidadSugerida = $this->reordenService->calcularCantidadSugerida($consumoPromedio, $leadTimeDias);

            $consumoDiario = $consumoPromedio / 30;
            $puntoReorden = $this->reordenService->calcularPuntoReorden(
                (float) $producto->stock_minimo,
                $consumoDiario,
                $leadTimeDias
            );

            $pendienteRecibir = $this->reordenService->obtenerPendienteRecibir($producto->codigo);

            if (! $this->reordenService->requiereReorden($producto, $puntoReorden, $pendienteRecibir)) {
                continue;
            }

            $proveedor = $this->reordenService->seleccionarMejorProveedor($producto);

            if ($proveedor === null) {
                continue;
            }

            $moq = $producto->moqParaProveedor($proveedor->id);
            $cantidadAjustada = $this->reordenService->ajustarAMOQ($cantidadSugerida, $moq);
            $urgente = ((float) ($producto->stock ?? 0)) == 0;

            $resultados[] = [
                $producto->codigo,
                $producto->nombre,
                $cantidadAjustada,
                $proveedor->nombre ?? $proveedor->empresa ?? "Prov #{$proveedor->id}",
                $urgente ? '🔴 URGENTE' : '🟢 Normal',
            ];
        }

        if (empty($resultados)) {
            $this->info('✅ No hay productos que requieran reorden en este momento.');
            Log::info('[ia:reorden-mp] Dry-run: No hay productos que requieran reorden.');

            return Command::SUCCESS;
        }

        $this->table(
            ['Código', 'Nombre', 'Cantidad Sugerida', 'Proveedor', 'Urgencia'],
            $resultados
        );

        $this->newLine();
        $totalProductos = count($resultados);
        $urgentes = collect($resultados)->filter(fn ($r) => str_contains($r[4], 'URGENTE'))->count();
        $this->info("📊 Resumen: {$totalProductos} productos requieren reorden ({$urgentes} urgentes)");

        return Command::SUCCESS;
    }

    /**
     * Modo normal: ejecuta el proceso completo y envía alerta al admin.
     */
    private function ejecutarProcesoCompleto(): int
    {
        $this->info('📋 Ejecutando proceso de reorden de materia prima...');

        $resultado = $this->reordenService->ejecutarProcesoReorden();

        if ($resultado['productos_reorden'] === 0) {
            $this->info('ℹ️  No hay productos que requieran reorden en este momento.');
            Log::info('[ia:reorden-mp] Proceso completado: no hay productos que requieran reorden.');

            return Command::SUCCESS;
        }

        // Mostrar resumen en consola
        $this->newLine();
        $this->info('✅ Proceso de reorden completado:');
        $this->info("   Productos evaluados: {$resultado['productos_evaluados']}");
        $this->info("   Productos con reorden: {$resultado['productos_reorden']}");
        $this->info("   OC generadas: {$resultado['oc_generadas']}");
        $this->info('   Monto total estimado: $' . number_format($resultado['monto_total'], 2));

        // Enviar alerta al admin con resumen
        $this->enviarAlertaAdmin($resultado);

        return Command::SUCCESS;
    }

    /**
     * Envía alerta al administrador con el resumen de OC generadas.
     */
    private function enviarAlertaAdmin(array $resultado): void
    {
        $alertEngine = app(AlertEngineService::class);

        $alertEngine->alertar([
            'tipo' => 'reorden_oc_generada',
            'modulo' => 'reorden',
            'destinatario_tipo' => 'admin',
            'destinatario_id' => 1,
            'titulo' => "📋 Reorden MP: {$resultado['oc_generadas']} OC generadas",
            'contenido' => "Se generaron {$resultado['oc_generadas']} borradores de OC por punto de reorden para {$resultado['productos_reorden']} productos. "
                . 'Monto total estimado: $' . number_format($resultado['monto_total'], 2) . '. Requieren tu aprobación.',
            'datos' => [
                'productos_evaluados' => $resultado['productos_evaluados'],
                'productos_reorden' => $resultado['productos_reorden'],
                'oc_generadas' => $resultado['oc_generadas'],
                'monto_total' => $resultado['monto_total'],
            ],
            'nivel' => 'info',
        ]);
    }

    /**
     * Obtiene los códigos de productos que ya tienen una OC borrador pendiente o aprobada.
     */
    private function obtenerProductosConOcExistente(): array
    {
        $ocExistentes = \App\Models\OcBorrador::whereIn('estatus', ['pendiente', 'aprobada'])
            ->get(['productos']);

        $codigosExistentes = [];

        foreach ($ocExistentes as $oc) {
            foreach ($oc->productos ?? [] as $item) {
                $codigo = (string) ($item['codigo'] ?? '');
                if ($codigo !== '') {
                    $codigosExistentes[] = $codigo;
                }
            }
        }

        return array_unique($codigosExistentes);
    }
}
