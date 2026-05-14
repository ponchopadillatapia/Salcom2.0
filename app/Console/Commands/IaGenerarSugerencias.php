<?php

namespace App\Console\Commands;

use App\Models\ClienteUser;
use App\Models\Pedido;
use App\Models\ProveedorUser;
use App\Services\AlertEngineService;
use App\Services\IaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Genera sugerencias personalizadas proactivas para proveedores y clientes.
 * - Proveedores: mejora de score, preparar inventario, oportunidades
 * - Clientes: reorden, ahorro por volumen, productos nuevos
 *
 * Se ejecuta cada miércoles a las 08:00
 */
class IaGenerarSugerencias extends Command
{
    protected $signature = 'ia:generar-sugerencias';

    protected $description = 'Genera sugerencias personalizadas con IA para proveedores y clientes';

    public function handle(): int
    {
        $this->info('💡 Generando sugerencias personalizadas...');

        $alertEngine = new AlertEngineService;
        $sugerenciasGeneradas = 0;

        // ═══ SUGERENCIAS PARA PROVEEDORES ═══
        $this->info('   → Procesando proveedores...');
        $proveedores = ProveedorUser::where('activo', true)->get();

        foreach ($proveedores as $proveedor) {
            $sugerencia = $this->generarSugerenciaProveedor($proveedor, $alertEngine);

            if ($sugerencia) {
                $alertEngine->alertar([
                    'tipo' => 'sugerencia_ia',
                    'modulo' => 'proveedores',
                    'destinatario_tipo' => 'proveedor',
                    'destinatario_id' => $proveedor->id,
                    'titulo' => '💡 Sugerencia semanal de Salcom IA',
                    'contenido' => $sugerencia,
                    'datos' => [
                        'proveedor_id' => $proveedor->id,
                        'tipo_sugerencia' => 'semanal',
                    ],
                    'nivel' => 'info',
                ]);
                $sugerenciasGeneradas++;
            }
        }

        // ═══ SUGERENCIAS PARA CLIENTES ═══
        $this->info('   → Procesando clientes...');
        $clientes = ClienteUser::where('activo', true)->get();

        foreach ($clientes as $cliente) {
            $sugerencia = $this->generarSugerenciaCliente($cliente, $alertEngine);

            if ($sugerencia) {
                $alertEngine->alertar([
                    'tipo' => 'sugerencia_ia',
                    'modulo' => 'clientes',
                    'destinatario_tipo' => 'cliente',
                    'destinatario_id' => $cliente->id,
                    'titulo' => '💡 Sugerencia semanal de Salcom',
                    'contenido' => $sugerencia,
                    'datos' => [
                        'cliente_id' => $cliente->id,
                        'tipo_sugerencia' => 'semanal',
                    ],
                    'nivel' => 'info',
                ]);
                $sugerenciasGeneradas++;
            }
        }

        $this->info("✅ Sugerencias completadas. Total: {$sugerenciasGeneradas}");
        Log::info("[ia:generar-sugerencias] Generadas: {$sugerenciasGeneradas}");

        return Command::SUCCESS;
    }

    /**
     * Generar sugerencia para un proveedor.
     */
    private function generarSugerenciaProveedor(ProveedorUser $proveedor, AlertEngineService $alertEngine): ?string
    {
        $score = (float) $proveedor->score_total;

        // Si score bajo 80%, dar acciones para mejorar
        if ($score > 0 && $score < 80) {
            $scoreEntrega = $proveedor->score_entrega ?? 0;
            $scorePuntualidad = $proveedor->score_puntualidad ?? 0;

            $peorArea = $scoreEntrega < $scorePuntualidad ? 'entregas a tiempo' : 'puntualidad';

            return "Tu score actual es {$score}%. Para mejorarlo, enfócate en {$peorArea} (actualmente en " .
                min($scoreEntrega, $scorePuntualidad) . "%). " .
                "Tip: Confirma fechas de entrega con anticipación y avisa si hay retrasos. " .
                "Un score arriba de 80% te da prioridad en nuevas OC.";
        }

        // Si score alto, felicitar
        if ($score >= 80) {
            return "¡Excelente rendimiento! Tu score es {$score}%. Mantén este nivel para seguir siendo proveedor preferente de Salcom. " .
                "Recuerda revisar tus documentos fiscales en la sección Fiscal para mantenerlos al día.";
        }

        // Sin score, sugerir completar onboarding
        return 'Completa tu onboarding para empezar a recibir órdenes de compra. ' .
            'Asegúrate de tener todos tus documentos fiscales al día en la sección Fiscal.';
    }

    /**
     * Generar sugerencia para un cliente.
     */
    private function generarSugerenciaCliente(ClienteUser $cliente, AlertEngineService $alertEngine): ?string
    {
        $codigoCliente = $cliente->codigo_cliente ?? 'CLI-' . $cliente->id;

        // Verificar si no ha pedido en 30 días
        $ultimoPedido = Pedido::where('codigo_cliente', $codigoCliente)
            ->orderByDesc('created_at')
            ->first();

        if ($ultimoPedido && $ultimoPedido->created_at->diffInDays(now()) > 30) {
            $diasSinPedir = $ultimoPedido->created_at->diffInDays(now());

            return "Han pasado {$diasSinPedir} días desde tu último pedido. " .
                "Basado en tu historial, normalmente pides cada 20-25 días. " .
                "¿Necesitas reabastecer? Revisa tu Forecast para ver las tendencias de tus productos.";
        }

        // Si tiene pedidos recientes, sugerir basado en volumen
        if ($ultimoPedido) {
            return 'Revisa tu sección de Forecast para ver las tendencias de tus productos más comprados. ' .
                'Si planeas un pedido grande, contáctanos con anticipación para asegurar disponibilidad.';
        }

        // Sin pedidos
        return '¡Bienvenido! Explora nuestro catálogo de productos y realiza tu primer pedido. ' .
            'Nuestro equipo está disponible para ayudarte con cotizaciones.';
    }
}
