<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $sid;
    private string $token;
    private string $from;

    public function __construct()
    {
        $this->sid   = config('services.twilio.sid', '');
        $this->token = config('services.twilio.token', '');
        $this->from  = config('services.twilio.whatsapp_from', '');
    }

    /**
     * Envía un mensaje de WhatsApp vía Twilio API REST.
     * No requiere SDK — usa HTTP básico con Basic Auth.
     */
    public function enviar(string $telefono, string $mensaje): array
    {
        if (empty($this->sid) || empty($this->token) || empty($this->from)) {
            Log::warning('WhatsApp: credenciales de Twilio no configuradas');
            return ['success' => false, 'error' => 'Twilio no configurado'];
        }

        $to = $this->formatearNumero($telefono);

        try {
            $response = Http::withBasicAuth($this->sid, $this->token)
                ->asForm()
                ->timeout(15)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", [
                    'From' => $this->from,
                    'To'   => $to,
                    'Body' => $mensaje,
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp: mensaje enviado', ['to' => $to, 'sid' => $response->json('sid')]);
                return ['success' => true, 'sid' => $response->json('sid')];
            }

            Log::error('WhatsApp: error al enviar', [
                'to'     => $to,
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
            return ['success' => false, 'error' => $response->json('message') ?? 'Error desconocido'];

        } catch (\Exception $e) {
            Log::error('WhatsApp: excepción', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Notifica cambio de estatus de pedido por WhatsApp.
     */
    public function notificarCambioEstatus(string $telefono, string $folio, string $estatus): array
    {
        $mensaje = "🔔 *Salcom* — Tu pedido *{$folio}* cambió a: *{$estatus}*.\n"
                 . "Consulta el detalle en tu portal de cliente.";

        return $this->enviar($telefono, $mensaje);
    }

    /**
     * Formatea número a formato WhatsApp de Twilio: whatsapp:+521XXXXXXXXXX
     */
    private function formatearNumero(string $telefono): string
    {
        $limpio = preg_replace('/\D/', '', $telefono);

        // Si ya tiene código de país (52 para MX)
        if (str_starts_with($limpio, '52') && strlen($limpio) >= 12) {
            return 'whatsapp:+' . $limpio;
        }

        // Agregar código MX por defecto
        if (strlen($limpio) === 10) {
            return 'whatsapp:+52' . $limpio;
        }

        return 'whatsapp:+' . $limpio;
    }
}
