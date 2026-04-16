<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaqueteriaService
{
    /**
     * Consulta tracking de un envío según la paquetería.
     *
     * @param string $carrier  estafeta|dhl|fedex
     * @param string $guia     Número de guía / tracking
     */
    public function rastrear(string $carrier, string $guia): array
    {
        return match (strtolower($carrier)) {
            'estafeta' => $this->rastrearEstafeta($guia),
            'dhl'      => $this->rastrearDhl($guia),
            'fedex'    => $this->rastrearFedex($guia),
            default    => ['success' => false, 'error' => "Paquetería no soportada: {$carrier}"],
        };
    }

    private function rastrearEstafeta(string $guia): array
    {
        $cfg = config('services.paqueterias.estafeta');
        if (empty($cfg['url']) || empty($cfg['api_key'])) {
            return ['success' => false, 'error' => 'Estafeta API no configurada'];
        }

        try {
            $response = Http::withHeaders(['apiKey' => $cfg['api_key']])
                ->timeout(15)
                ->get("{$cfg['url']}/tracking/{$guia}");

            return $response->successful()
                ? ['success' => true, 'carrier' => 'Estafeta', 'data' => $response->json()]
                : ['success' => false, 'error' => 'Estafeta: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('Paquetería Estafeta: error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function rastrearDhl(string $guia): array
    {
        $cfg = config('services.paqueterias.dhl');
        if (empty($cfg['api_key'])) {
            return ['success' => false, 'error' => 'DHL API no configurada'];
        }

        try {
            $response = Http::withHeaders(['DHL-API-Key' => $cfg['api_key']])
                ->timeout(15)
                ->get($cfg['url'], ['trackingNumber' => $guia]);

            if ($response->successful()) {
                $shipments = $response->json('shipments') ?? [];
                return ['success' => true, 'carrier' => 'DHL', 'data' => $shipments];
            }

            return ['success' => false, 'error' => 'DHL: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('Paquetería DHL: error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function rastrearFedex(string $guia): array
    {
        $cfg = config('services.paqueterias.fedex');
        if (empty($cfg['api_key']) || empty($cfg['secret_key'])) {
            return ['success' => false, 'error' => 'FedEx API no configurada'];
        }

        try {
            // FedEx requiere OAuth2 token
            $tokenResponse = Http::asForm()->post("{$cfg['url']}/oauth/token", [
                'grant_type'    => 'client_credentials',
                'client_id'     => $cfg['api_key'],
                'client_secret' => $cfg['secret_key'],
            ]);

            if (!$tokenResponse->successful()) {
                return ['success' => false, 'error' => 'FedEx: no se pudo obtener token'];
            }

            $token = $tokenResponse->json('access_token');

            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$cfg['url']}/track/v1/trackingnumbers", [
                    'trackingInfo' => [['trackingNumberInfo' => ['trackingNumber' => $guia]]],
                    'includeDetailedScans' => true,
                ]);

            return $response->successful()
                ? ['success' => true, 'carrier' => 'FedEx', 'data' => $response->json()]
                : ['success' => false, 'error' => 'FedEx: ' . $response->status()];

        } catch (\Exception $e) {
            Log::error('Paquetería FedEx: error', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
