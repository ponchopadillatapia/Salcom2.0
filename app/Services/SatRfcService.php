<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SatRfcService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sat.rfc_url', ''), '/');
        $this->apiKey  = config('services.sat.api_key', '');
        $this->timeout = config('services.sat.timeout', 10);
    }

    /**
     * Valida formato de RFC (offline, sin API).
     * Persona Física: 4 letras + 6 dígitos + 3 homoclave = 13 chars
     * Persona Moral:  3 letras + 6 dígitos + 3 homoclave = 12 chars
     */
    public function validarFormato(string $rfc): array
    {
        $rfc = strtoupper(trim($rfc));

        $esFisica = (bool) preg_match('/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/u', $rfc);
        $esMoral  = (bool) preg_match('/^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/u', $rfc);

        if (!$esFisica && !$esMoral) {
            return [
                'valido'       => false,
                'tipo_persona' => null,
                'mensaje'      => 'El RFC no tiene un formato válido',
            ];
        }

        return [
            'valido'       => true,
            'tipo_persona' => $esFisica ? 'Persona Física' : 'Persona Moral',
            'mensaje'      => 'Formato de RFC válido',
        ];
    }

    /**
     * Consulta RFC ante el SAT vía API externa.
     * Verifica que el RFC esté registrado y activo.
     * Requiere SAT_API_KEY configurada.
     */
    public function validarConSat(string $rfc): array
    {
        $rfc = strtoupper(trim($rfc));

        // Primero validar formato
        $formato = $this->validarFormato($rfc);
        if (!$formato['valido']) {
            return $formato;
        }

        // Si no hay API configurada, solo validar formato
        if (empty($this->baseUrl) || empty($this->apiKey)) {
            Log::info('SAT RFC: API no configurada, solo se validó formato', ['rfc' => $rfc]);
            return array_merge($formato, [
                'sat_verificado' => false,
                'mensaje'        => 'Formato válido (verificación SAT no disponible — API no configurada)',
            ]);
        }

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept'        => 'application/json',
                ])
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/rfc/{$rfc}");

            if ($response->successful()) {
                $data = $response->json();
                $activo = $data['activo'] ?? $data['status'] === 'activo';

                return [
                    'valido'         => true,
                    'sat_verificado' => true,
                    'activo'         => $activo,
                    'tipo_persona'   => $formato['tipo_persona'],
                    'razon_social'   => $data['razon_social'] ?? $data['nombre'] ?? null,
                    'mensaje'        => $activo
                        ? 'RFC válido y activo ante el SAT'
                        : 'RFC registrado pero NO activo ante el SAT',
                ];
            }

            if ($response->status() === 404) {
                return [
                    'valido'         => false,
                    'sat_verificado' => true,
                    'mensaje'        => 'RFC no encontrado en el registro del SAT',
                ];
            }

            Log::error('SAT RFC: error en API', ['status' => $response->status(), 'rfc' => $rfc]);
            return array_merge($formato, [
                'sat_verificado' => false,
                'mensaje'        => 'Formato válido (no se pudo verificar con SAT — error en servicio)',
            ]);

        } catch (\Exception $e) {
            Log::error('SAT RFC: excepción', ['error' => $e->getMessage(), 'rfc' => $rfc]);
            return array_merge($formato, [
                'sat_verificado' => false,
                'mensaje'        => 'Formato válido (no se pudo verificar con SAT — servicio no disponible)',
            ]);
        }
    }

    /**
     * Verifica si un RFC está en la lista negra del SAT (Art. 69-B).
     */
    public function verificarLista69B(string $rfc): array
    {
        $rfc = strtoupper(trim($rfc));

        if (empty($this->baseUrl) || empty($this->apiKey)) {
            return ['verificado' => false, 'en_lista' => null, 'mensaje' => 'API SAT no configurada'];
        }

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept'        => 'application/json',
                ])
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/lista69b/{$rfc}");

            if ($response->successful()) {
                $enLista = $response->json('en_lista') ?? false;
                return [
                    'verificado' => true,
                    'en_lista'   => $enLista,
                    'mensaje'    => $enLista
                        ? '⚠ RFC aparece en la lista 69-B del SAT (operaciones simuladas)'
                        : 'RFC NO aparece en la lista 69-B',
                ];
            }

            return ['verificado' => false, 'en_lista' => null, 'mensaje' => 'No se pudo consultar lista 69-B'];

        } catch (\Exception $e) {
            Log::error('SAT 69-B: excepción', ['error' => $e->getMessage()]);
            return ['verificado' => false, 'en_lista' => null, 'mensaje' => 'Servicio no disponible'];
        }
    }
}
