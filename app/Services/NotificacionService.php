<?php

namespace App\Services;

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
     * Notifica cambio de estatus de pedido por todos los canales disponibles.
     *
     * @param array $cliente  ['nombre', 'correo', 'telefono', 'codigo_cliente']
     * @param string $folio   Folio del pedido
     * @param string $estatus Nuevo estatus
     * @param string|null $notas
     */
    public function notificarCambioPedido(array $cliente, string $folio, string $estatus, ?string $notas = null): array
    {
        $resultados = ['bd' => false, 'email' => false, 'whatsapp' => false];

        // 1. Guardar notificación en BD (siempre)
        try {
            Notificacion::create([
                'tipo_usuario'   => 'cliente',
                'codigo_usuario' => $cliente['codigo_cliente'] ?? '',
                'titulo'         => "Pedido {$folio} — {$estatus}",
                'mensaje'        => "Tu pedido {$folio} cambió a: {$estatus}." . ($notas ? " Notas: {$notas}" : ''),
                'leida'          => false,
                'tipo'           => 'pedido_estatus',
            ]);
            $resultados['bd'] = true;
        } catch (\Exception $e) {
            Log::error('Notificación BD: error', ['error' => $e->getMessage()]);
        }

        // 2. Email (si hay correo configurado)
        if (!empty($cliente['correo'])) {
            try {
                Mail::to($cliente['correo'])->send(
                    new PedidoEstatusNotificacion($folio, $estatus, $cliente['nombre'] ?? 'Cliente', $notas)
                );
                $resultados['email'] = true;
            } catch (\Exception $e) {
                Log::error('Notificación Email: error', ['error' => $e->getMessage(), 'correo' => $cliente['correo']]);
            }
        }

        // 3. WhatsApp (si hay teléfono)
        if (!empty($cliente['telefono'])) {
            $wa = $this->whatsapp->notificarCambioEstatus($cliente['telefono'], $folio, $estatus);
            $resultados['whatsapp'] = $wa['success'];
        }

        return $resultados;
    }
}
