<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PacCfdiService
{
    private string $driver;
    private string $url;
    private string $user;
    private string $password;
    private bool $sandbox;
    private int $timeout;

    public function __construct()
    {
        $this->driver   = config('services.pac.driver', 'facturama');
        $this->url      = rtrim(config('services.pac.url', ''), '/');
        $this->user     = config('services.pac.user', '');
        $this->password = config('services.pac.password', '');
        $this->sandbox  = (bool) config('services.pac.sandbox', true);
        $this->timeout  = config('services.pac.timeout', 30);
    }

    /**
     * Timbra un CFDI ante el PAC configurado.
     *
     * @param array $cfdiData Datos del CFDI (emisor, receptor, conceptos, etc.)
     * @return array ['success' => bool, 'uuid' => string|null, 'xml' => string|null, ...]
     */
    public function timbrar(array $cfdiData): array
    {
        if (empty($this->url) || empty($this->user)) {
            return $this->error('PAC no configurado — revisa las variables PAC_* en .env');
        }

        return match ($this->driver) {
            'facturama'  => $this->timbrarFacturama($cfdiData),
            'sw_sapien'  => $this->timbrarSwSapien($cfdiData),
            'diverza'    => $this->timbrarDiverza($cfdiData),
            default      => $this->error("Driver PAC no soportado: {$this->driver}"),
        };
    }

    /**
     * Cancela un CFDI timbrado.
     */
    public function cancelar(string $uuid, string $rfcEmisor, string $motivo = '02'): array
    {
        if (empty($this->url) || empty($this->user)) {
            return $this->error('PAC no configurado');
        }

        return match ($this->driver) {
            'facturama'  => $this->cancelarFacturama($uuid, $rfcEmisor, $motivo),
            'sw_sapien'  => $this->cancelarSwSapien($uuid, $rfcEmisor, $motivo),
            'diverza'    => $this->cancelarDiverza($uuid, $rfcEmisor, $motivo),
            default      => $this->error("Driver PAC no soportado: {$this->driver}"),
        };
    }

    /**
     * Consulta el estatus de un CFDI.
     */
    public function consultarEstatus(string $uuid): array
    {
        if (empty($this->url) || empty($this->user)) {
            return $this->error('PAC no configurado');
        }

        try {
            $response = $this->httpClient()
                ->get("{$this->url}/cfdi/{$uuid}");

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return $this->error('No se pudo consultar el CFDI: ' . $response->status());
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    // ── Facturama ──

    private function timbrarFacturama(array $data): array
    {
        try {
            $endpoint = $this->sandbox
                ? "{$this->url}/2/cfdis"
                : "{$this->url}/api/Cfdi";

            $response = $this->httpClient()
                ->post($endpoint, $data);

            if ($response->successful()) {
                $body = $response->json();
                return [
                    'success' => true,
                    'uuid'    => $body['Complement']['TaxStamp']['Uuid'] ?? $body['Id'] ?? null,
                    'xml'     => $body['Content'] ?? null,
                    'data'    => $body,
                ];
            }

            Log::error('PAC Facturama: error al timbrar', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
            return $this->error($response->json('Message') ?? 'Error al timbrar con Facturama');

        } catch (\Exception $e) {
            Log::error('PAC Facturama: excepción', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage());
        }
    }

    private function cancelarFacturama(string $uuid, string $rfc, string $motivo): array
    {
        try {
            $response = $this->httpClient()
                ->delete("{$this->url}/api/Cfdi/{$uuid}", [
                    'Rfc'    => $rfc,
                    'Motivo' => $motivo,
                ]);

            return $response->successful()
                ? ['success' => true, 'data' => $response->json()]
                : $this->error('Error al cancelar: ' . $response->status());
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    // ── SW Sapien ──

    private function timbrarSwSapien(array $data): array
    {
        try {
            // SW Sapien usa token auth
            $tokenResponse = Http::timeout($this->timeout)
                ->post("{$this->url}/security/authenticate", [
                    'user'     => $this->user,
                    'password' => $this->password,
                ]);

            if (!$tokenResponse->successful()) {
                return $this->error('No se pudo autenticar con SW Sapien');
            }

            $token = $tokenResponse->json('data.token');

            $response = Http::timeout($this->timeout)
                ->withHeaders(['Authorization' => "Bearer {$token}"])
                ->post("{$this->url}/cfdi33/issue/json/v4", $data);

            if ($response->successful()) {
                $body = $response->json('data');
                return [
                    'success' => true,
                    'uuid'    => $body['uuid'] ?? null,
                    'xml'     => $body['cfdi'] ?? null,
                    'data'    => $body,
                ];
            }

            return $this->error($response->json('message') ?? 'Error al timbrar con SW Sapien');

        } catch (\Exception $e) {
            Log::error('PAC SW Sapien: excepción', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage());
        }
    }

    private function cancelarSwSapien(string $uuid, string $rfc, string $motivo): array
    {
        try {
            $response = $this->httpClient()
                ->post("{$this->url}/cfdi33/cancel", [
                    'uuid'   => $uuid,
                    'rfc'    => $rfc,
                    'motivo' => $motivo,
                ]);

            return $response->successful()
                ? ['success' => true, 'data' => $response->json()]
                : $this->error('Error al cancelar: ' . $response->status());
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    // ── Diverza ──

    private function timbrarDiverza(array $data): array
    {
        try {
            $response = $this->httpClient()
                ->post("{$this->url}/v2/cfdi", $data);

            if ($response->successful()) {
                $body = $response->json();
                return [
                    'success' => true,
                    'uuid'    => $body['uuid'] ?? null,
                    'xml'     => $body['xml'] ?? null,
                    'data'    => $body,
                ];
            }

            return $this->error($response->json('error') ?? 'Error al timbrar con Diverza');

        } catch (\Exception $e) {
            Log::error('PAC Diverza: excepción', ['error' => $e->getMessage()]);
            return $this->error($e->getMessage());
        }
    }

    private function cancelarDiverza(string $uuid, string $rfc, string $motivo): array
    {
        try {
            $response = $this->httpClient()
                ->post("{$this->url}/v2/cfdi/cancel", [
                    'uuid'   => $uuid,
                    'rfc'    => $rfc,
                    'motivo' => $motivo,
                ]);

            return $response->successful()
                ? ['success' => true, 'data' => $response->json()]
                : $this->error('Error al cancelar: ' . $response->status());
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    // ── Helpers ──

    private function httpClient()
    {
        return Http::withBasicAuth($this->user, $this->password)
            ->timeout($this->timeout)
            ->acceptJson();
    }

    private function error(string $mensaje): array
    {
        return ['success' => false, 'uuid' => null, 'xml' => null, 'error' => $mensaje];
    }
}
