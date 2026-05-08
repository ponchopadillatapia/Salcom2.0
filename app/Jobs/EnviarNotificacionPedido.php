<?php

namespace App\Jobs;

use App\Mail\PedidoEstatusNotificacion;
use App\Models\Notificacion;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job para enviar notificaciones de cambio de estatus de pedido.
 * Se ejecuta en background (queue) para no bloquear el request del usuario.
 *
 * Canales: BD (siempre) + Email (si hay correo) + WhatsApp (si hay teléfono)
 */
class EnviarNotificacionPedido implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private array $cliente,
        private string $folio,
        private string $estatus,
        private ?string $notas = null,
    ) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        // 1. Guardar notificación en BD (siempre)
        try {
            Notificacion::create([
                'tipo_usuario' => 'cliente',
                'codigo_usuario' => $this->cliente['codigo_cliente'] ?? '',
                'titulo' => "Pedido {$this->folio} — {$this->estatus}",
                'mensaje' => "Tu pedido {$this->folio} cambió a: {$this->estatus}."
                    .($this->notas ? " Notas: {$this->notas}" : ''),
                'leida' => false,
                'tipo' => 'pedido_estatus',
            ]);
        } catch (\Exception $e) {
            Log::error('Job Notificación BD: error', ['error' => $e->getMessage()]);
        }

        // 2. Email
        if (! empty($this->cliente['correo'])) {
            try {
                Mail::to($this->cliente['correo'])->send(
                    new PedidoEstatusNotificacion(
                        $this->folio,
                        $this->estatus,
                        $this->cliente['nombre'] ?? 'Cliente',
                        $this->notas,
                    )
                );
            } catch (\Exception $e) {
                Log::error('Job Notificación Email: error', [
                    'error' => $e->getMessage(),
                    'correo' => $this->cliente['correo'],
                ]);
            }
        }

        // 3. WhatsApp
        if (! empty($this->cliente['telefono'])) {
            try {
                $whatsapp->notificarCambioEstatus(
                    $this->cliente['telefono'],
                    $this->folio,
                    $this->estatus,
                );
            } catch (\Exception $e) {
                Log::error('Job Notificación WhatsApp: error', ['error' => $e->getMessage()]);
            }
        }

        Log::info('Job Notificación completado', [
            'folio' => $this->folio,
            'estatus' => $this->estatus,
            'cliente' => $this->cliente['codigo_cliente'] ?? 'N/A',
        ]);
    }
}
