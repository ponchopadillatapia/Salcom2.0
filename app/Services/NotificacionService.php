<?php

namespace App\Services;

use App\Jobs\EnviarNotificacionPedido;
use App\Mail\PedidoEstatusNotificacion;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificacionService
{
    public function __construct(
        private WhatsAppService $whatsapp,
    ) {}

    /**
     * Notifica cambio de estatus de pedido.
     * Despacha un Job a la cola para no bloquear el request.
     *
     * En testing (QUEUE_CONNECTION=sync) se ejecuta inmediatamente.
     * En producción (QUEUE_CONNECTION=database) se ejecuta en background.
     */
    public function notificarCambioPedido(array $cliente, string $folio, string $estatus, ?string $notas = null): void
    {
        EnviarNotificacionPedido::dispatch($cliente, $folio, $estatus, $notas);

        Log::info('Notificación despachada a cola', [
            'folio' => $folio,
            'estatus' => $estatus,
            'cliente' => $cliente['codigo_cliente'] ?? 'N/A',
        ]);
    }

    /**
     * Envío síncrono (para casos donde necesitas el resultado inmediato).
     * Útil para testing o procesos batch.
     */
    public function notificarSincrono(array $cliente, string $folio, string $estatus, ?string $notas = null): array
    {
        $resultados = ['bd' => false, 'email' => false, 'whatsapp' => false];

        try {
            Notificacion::create([
                'tipo_usuario' => 'cliente',
                'codigo_usuario' => $cliente['codigo_cliente'] ?? '',
                'titulo' => "Pedido {$folio} — {$estatus}",
                'mensaje' => "Tu pedido {$folio} cambió a: {$estatus}.".($notas ? " Notas: {$notas}" : ''),
                'leida' => false,
                'tipo' => 'pedido_estatus',
            ]);
            $resultados['bd'] = true;
        } catch (\Exception $e) {
            Log::error('Notificación BD: error', ['error' => $e->getMessage()]);
        }

        if (! empty($cliente['correo'])) {
            try {
                Mail::to($cliente['correo'])->send(
                    new PedidoEstatusNotificacion($folio, $estatus, $cliente['nombre'] ?? 'Cliente', $notas)
                );
                $resultados['email'] = true;
            } catch (\Exception $e) {
                Log::error('Notificación Email: error', ['error' => $e->getMessage()]);
            }
        }

        if (! empty($cliente['telefono'])) {
            $wa = $this->whatsapp->notificarCambioEstatus($cliente['telefono'], $folio, $estatus);
            $resultados['whatsapp'] = $wa['success'];
        }

        return $resultados;
    }
}
