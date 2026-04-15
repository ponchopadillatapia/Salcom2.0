<?php

namespace App\Services;

use App\Exceptions\ProveedorApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClienteApiService
{
    private string $baseUrl;
    private int $connectTimeout;
    private int $timeout;
    private int $maxRetries;

    public function __construct()
    {
        $this->baseUrl        = config('services.cliente_api.url', '');
        $this->connectTimeout = config('services.cliente_api.connect_timeout', 5);
        $this->timeout        = config('services.cliente_api.timeout', 15);
        $this->maxRetries     = config('services.cliente_api.max_retries', 3);
    }

    // ── Métodos públicos ──

    /**
     * Login contra API externa — SIN retry (no es idempotente).
     * Clientes usan ctipocliente: 1 (proveedores usan 3).
     */
    public function loginApi(string $codigo, string $pwd): array
    {
        $configError = $this->validarConfiguracion();
        if ($configError) {
            return $configError;
        }

        $endpoint = '/Login/Login';

        try {
            $response = Http::connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->post($this->baseUrl . $endpoint, [
                    'codigo'       => $codigo,
                    'pwd'          => $pwd,
                    'ctipocliente' => 1,
                ]);

            return $this->procesarRespuesta($response, $endpoint);
        } catch (ConnectionException $e) {
            Log::error('ClienteAPI: conexión fallida', [
                'endpoint' => $endpoint,
                'method'   => 'POST',
                'error'    => $e->getMessage(),
            ]);
            return $this->buildErrorResponse(
                'No se pudo conectar con la API del cliente',
                ProveedorApiException::API_CAIDA
            );
        } catch (\Exception $e) {
            Log::error('ClienteAPI: error inesperado en login', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            return $this->buildErrorResponse(
                'Ocurrió un error inesperado',
                ProveedorApiException::ERROR_DESCONOCIDO
            );
        }
    }

    /**
     * Buscar cliente por código — CON retry en fallos transitorios.
     */
    public function buscarPorCodigo(string $codigo, string $token): array
    {
        return $this->getConRetry(
            '/ClienteProveedor/BuscarPorCodigo',
            ['codigo' => $codigo],
            $token
        );
    }

    /**
     * Listar documentos de cliente por código — CON retry.
     */
    public function listarPorCodigo(string $codigo, string $token): array
    {
        return $this->getConRetry(
            '/ClienteProveedor/ListarClienteProvedorPorCodigo',
            ['codigo' => $codigo],
            $token
        );
    }

    // ── Métodos privados ──

    /**
     * GET con retry y backoff exponencial.
     */
    private function getConRetry(string $endpoint, array $params, string $token): array
    {
        $configError = $this->validarConfiguracion();
        if ($configError) {
            return $configError;
        }

        $lastException = null;

        for ($intento = 1; $intento <= $this->maxRetries; $intento++) {
            try {
                $response = Http::connectTimeout($this->connectTimeout)
                    ->timeout($this->timeout)
                    ->withHeaders(['Authorization' => 'Bearer ' . $token])
                    ->get($this->baseUrl . $endpoint, $params);

                // Si no es error de servidor retryable, procesar de inmediato
                if (!$this->esRetryable($response)) {
                    if ($intento > 1) {
                        Log::warning('ClienteAPI: éxito después de reintentos', [
                            'endpoint'  => $endpoint,
                            'intentos'  => $intento,
                        ]);
                    }
                    return $this->procesarRespuesta($response, $endpoint);
                }

                // Error retryable — log y seguir
                Log::error('ClienteAPI: intento fallido', [
                    'endpoint' => $endpoint,
                    'method'   => 'GET',
                    'intento'  => $intento,
                    'status'   => $response->status(),
                ]);

            } catch (ConnectionException $e) {
                $lastException = $e;
                Log::error('ClienteAPI: conexión fallida (intento)', [
                    'endpoint' => $endpoint,
                    'method'   => 'GET',
                    'intento'  => $intento,
                    'error'    => $e->getMessage(),
                ]);
            }

            // Backoff exponencial: 100ms, 200ms, 400ms...
            if ($intento < $this->maxRetries) {
                usleep(100_000 * pow(2, $intento - 1));
            }
        }

        Log::error('ClienteAPI: todos los reintentos agotados', [
            'endpoint'    => $endpoint,
            'max_retries' => $this->maxRetries,
        ]);

        return $this->buildErrorResponse(
            'La API del cliente no está disponible temporalmente',
            ProveedorApiException::API_CAIDA
        );
    }

    /**
     * Valida que la URL base esté configurada.
     */
    private function validarConfiguracion(): ?array
    {
        if (empty(trim($this->baseUrl))) {
            return $this->buildErrorResponse(
                'La API del cliente no está configurada',
                ProveedorApiException::API_CAIDA
            );
        }
        return null;
    }

    /**
     * Procesa la respuesta HTTP y mapea a estructura estandarizada.
     */
    private function procesarRespuesta(Response $response, string $endpoint): array
    {
        $status = $response->status();
        $body   = $response->json() ?? [];

        if ($response->successful()) {
            if (empty($body)) {
                return $this->buildErrorResponse(
                    'No se encontraron resultados',
                    ProveedorApiException::NO_ENCONTRADO
                );
            }
            return $this->buildSuccessResponse($body);
        }

        if ($status === 401) {
            Log::error('ClienteAPI: autenticación fallida', ['endpoint' => $endpoint, 'status' => $status]);
            return $this->buildErrorResponse(
                'Credenciales inválidas o sesión expirada',
                ProveedorApiException::AUTENTICACION_FALLIDA
            );
        }

        if ($status === 404) {
            return $this->buildErrorResponse(
                'No se encontraron resultados',
                ProveedorApiException::NO_ENCONTRADO
            );
        }

        if ($status >= 500) {
            Log::error('ClienteAPI: error de servidor', ['endpoint' => $endpoint, 'status' => $status]);
            return $this->buildErrorResponse(
                'La API del cliente no está disponible temporalmente',
                ProveedorApiException::ERROR_SERVIDOR
            );
        }

        Log::error('ClienteAPI: error desconocido', ['endpoint' => $endpoint, 'status' => $status]);
        return $this->buildErrorResponse(
            'Ocurrió un error inesperado',
            ProveedorApiException::ERROR_DESCONOCIDO
        );
    }

    private function buildSuccessResponse(array $data): array
    {
        return [
            'success'    => true,
            'data'       => $data,
            'message'    => 'OK',
            'error_type' => null,
        ];
    }

    private function buildErrorResponse(string $message, string $errorType): array
    {
        return [
            'success'    => false,
            'data'       => null,
            'message'    => $message,
            'error_type' => $errorType,
        ];
    }

    private function esRetryable(Response $response): bool
    {
        return in_array($response->status(), [500, 502, 503, 504]);
    }
}
