<?php

namespace App\Services;

use App\Exceptions\ProveedorApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProveedorApiService
{
    private string $baseUrl;

    private string $docsUrl;

    private int $connectTimeout;

    private int $timeout;

    private int $maxRetries;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.proveedor_api.url', ''), '/');
        $docs = (string) config('services.proveedor_api.docs_url', '');
        $this->docsUrl = rtrim($docs !== '' ? $docs : $this->baseUrl, '/');
        $this->connectTimeout = config('services.proveedor_api.connect_timeout', 5);
        $this->timeout = config('services.proveedor_api.timeout', 15);
        $this->maxRetries = config('services.proveedor_api.max_retries', 3);
    }

    // ── Métodos públicos ──

    /**
     * Login contra API externa — SIN retry (no es idempotente).
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
                ->post($this->baseUrl.$endpoint, [
                    'codigo' => $codigo,
                    'pwd' => $pwd,
                ]);

            return $this->procesarRespuesta($response, $endpoint);
        } catch (ConnectionException $e) {
            Log::error('ProveedorAPI: conexión fallida', [
                'endpoint' => $endpoint,
                'method' => 'POST',
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse(
                'No se pudo conectar con la API del proveedor',
                ProveedorApiException::API_CAIDA
            );
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error inesperado en login', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse(
                'Ocurrió un error inesperado',
                ProveedorApiException::ERROR_DESCONOCIDO
            );
        }
    }

    /**
     * Buscar proveedor/OC por código — CON retry en fallos transitorios.
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
     * Listar documentos de cliente/proveedor por código — CON retry.
     */
    public function listarPorCodigo(string $codigo, string $token): array
    {
        return $this->getConRetry(
            '/ClienteProveedor/ListarClienteProvedorPorCodigo',
            ['codigo' => $codigo],
            $token
        );
    }

    /**
     * Login con usuario de servicio (ej. web) contra la API de docs/Wiese.
     * Body en minúsculas como exige el host 7186.
     */
    public function loginServicio(): array
    {
        $configError = $this->validarDocsConfiguracion();
        if ($configError) {
            return $configError;
        }

        $user = strtolower(trim((string) config('services.proveedor_api.service_user', '')));
        $pwd = (string) config('services.proveedor_api.service_password', '');

        if ($user === '' || $pwd === '') {
            return $this->buildErrorResponse(
                'Faltan PROVEEDOR_API_SERVICE_USER / PROVEEDOR_API_SERVICE_PASSWORD en .env',
                ProveedorApiException::API_CAIDA
            );
        }

        $endpoint = '/Login/Login';

        try {
            $response = Http::connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($this->docsUrl.$endpoint, [
                    'codigo' => $user,
                    'pwd' => $pwd,
                ]);

            $result = $this->procesarRespuesta($response, $endpoint);
            if (! ($result['success'] ?? false)) {
                return $result;
            }

            $payload = is_array($result['data'] ?? null) ? $result['data'] : [];
            $token = $this->extraerTokenLogin($payload);

            if ($token === null) {
                $claves = implode(', ', array_keys($payload));

                return $this->buildErrorResponse(
                    'Login OK pero no vino token. Claves recibidas: '.($claves !== '' ? $claves : '(ninguna)'),
                    ProveedorApiException::ERROR_DESCONOCIDO
                );
            }

            return $this->buildSuccessResponse([
                'tokenCreado' => $token,
                'usuario' => $payload['usuario'] ?? $payload['Usuario'] ?? null,
            ]);
        } catch (ConnectionException $e) {
            Log::error('ProveedorAPI: conexión fallida (login servicio)', [
                'endpoint' => $endpoint,
                'url' => $this->docsUrl,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse(
                'No se pudo conectar con la API Wiese (docs)',
                ProveedorApiException::API_CAIDA
            );
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error login servicio', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse(
                'Ocurrió un error inesperado',
                ProveedorApiException::ERROR_DESCONOCIDO
            );
        }
    }

    /**
     * GET /Documento/ListaDocumentosOCPorProveedorFechas
     * Si no pasas $token, hace loginServicio() automáticamente.
     *
     * @return array{success: bool, data?: array{items: list<mixed>, total: int}, message: string, error_type: ?string}
     */
    public function listarDocumentosOCPorProveedorFechas(
        string $codigoProveedor,
        string $fechaInicio,
        string $fechaFin,
        ?string $token = null
    ): array {
        if ($token === null || $token === '') {
            $login = $this->loginServicio();
            if (! ($login['success'] ?? false)) {
                return $login;
            }
            $token = (string) $login['data']['tokenCreado'];
        }

        $configError = $this->validarDocsConfiguracion();
        if ($configError) {
            return $configError;
        }

        $endpoint = '/Documento/ListaDocumentosOCPorProveedorFechas';
        $params = [
            'codigoProveedor' => $codigoProveedor,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
        ];

        try {
            $response = Http::connectTimeout($this->connectTimeout)
                ->timeout(max($this->timeout, 60))
                ->withToken($token)
                ->acceptJson()
                ->get($this->docsUrl.$endpoint, $params);

            if (! $response->successful()) {
                return $this->procesarRespuesta($response, $endpoint);
            }

            $body = $response->json();
            if (! is_array($body)) {
                $body = [];
            }

            // La API regresa un array raíz; [] es válido (sin OC en el rango).
            $items = array_is_list($body) ? $body : [$body];

            return $this->buildSuccessResponse([
                'items' => $items,
                'total' => count($items),
                'codigoProveedor' => $codigoProveedor,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
            ]);
        } catch (ConnectionException $e) {
            Log::error('ProveedorAPI: conexión fallida (listar OC)', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse(
                'No se pudo conectar con la API Wiese (docs)',
                ProveedorApiException::API_CAIDA
            );
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error listar OC', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse(
                'Ocurrió un error inesperado',
                ProveedorApiException::ERROR_DESCONOCIDO
            );
        }
    }

    /**
     * Buscar proveedor por RFC en AdSalcom18.
     * Devuelve las cuentas encontradas (puede ser 1 o 2: MXN y USD).
     *
     * PLACEHOLDER: cuando Alan pase el endpoint real, se conecta aquí.
     * Por ahora simula la respuesta esperada.
     */
    public function buscarProveedorPorRFC(string $rfc): array
    {
        $rfc = strtoupper(trim($rfc));

        if (empty($rfc)) {
            return $this->buildErrorResponse('RFC vacío', 'validation');
        }

        // TODO: Reemplazar con llamada real a la API cuando esté disponible
        // Endpoint esperado: GET /Proveedor/BuscarPorRFC?rfc={rfc}
        // Respuesta esperada: { success: true, data: { cuentas: [{codigo, razonSocial, moneda, fechaAlta}] } }

        // --- INICIO PLACEHOLDER (quitar cuando llegue API real) ---
        // Simular respuesta para ORPACK (RFC de prueba)
        if ($rfc === 'OME0207015E8') {
            return [
                'success' => true,
                'data' => [
                    'cuentas' => [
                        [
                            'codigo' => 'M213015002',
                            'razonSocial' => 'ORPACK DE MEXICO',
                            'moneda' => 'MXN',
                            'fechaAlta' => '2005-06-15',
                        ],
                    ],
                ],
            ];
        }

        // Para cualquier otro RFC: simular "no encontrado"
        return [
            'success' => true,
            'data' => ['cuentas' => []],
        ];
        // --- FIN PLACEHOLDER ---
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
                    ->withHeaders(['Authorization' => 'Bearer '.$token])
                    ->get($this->baseUrl.$endpoint, $params);

                // Si no es error de servidor retryable, procesar de inmediato
                if (! $this->esRetryable($response)) {
                    if ($intento > 1) {
                        Log::warning('ProveedorAPI: éxito después de reintentos', [
                            'endpoint' => $endpoint,
                            'intentos' => $intento,
                        ]);
                    }

                    return $this->procesarRespuesta($response, $endpoint);
                }

                // Error retryable — log y seguir
                Log::error('ProveedorAPI: intento fallido', [
                    'endpoint' => $endpoint,
                    'method' => 'GET',
                    'intento' => $intento,
                    'status' => $response->status(),
                ]);

            } catch (ConnectionException $e) {
                $lastException = $e;
                Log::error('ProveedorAPI: conexión fallida (intento)', [
                    'endpoint' => $endpoint,
                    'method' => 'GET',
                    'intento' => $intento,
                    'error' => $e->getMessage(),
                ]);
            }

            // Backoff exponencial: 100ms, 200ms, 400ms...
            if ($intento < $this->maxRetries) {
                usleep(100_000 * pow(2, $intento - 1));
            }
        }

        Log::error('ProveedorAPI: todos los reintentos agotados', [
            'endpoint' => $endpoint,
            'max_retries' => $this->maxRetries,
        ]);

        return $this->buildErrorResponse(
            'La API del proveedor no está disponible temporalmente',
            ProveedorApiException::API_CAIDA
        );
    }

    /**
     * Valida que la URL base esté configurada.
     * Retorna array de error si no está configurada, null si OK.
     */
    private function validarConfiguracion(): ?array
    {
        if (empty(trim($this->baseUrl))) {
            return $this->buildErrorResponse(
                'La API del proveedor no está configurada',
                ProveedorApiException::API_CAIDA
            );
        }

        return null;
    }

    private function validarDocsConfiguracion(): ?array
    {
        if (empty(trim($this->docsUrl))) {
            return $this->buildErrorResponse(
                'La API Wiese (docs) no está configurada (PROVEEDOR_API_DOCS_URL o PROVEEDOR_API_URL)',
                ProveedorApiException::API_CAIDA
            );
        }

        return null;
    }

    /**
     * La API a veces manda tokenCreado / tokencreado / TokenCreado / token.
     */
    private function extraerTokenLogin(array $payload): ?string
    {
        $mapa = [];
        foreach ($payload as $key => $valor) {
            if (is_string($key)) {
                $mapa[strtolower($key)] = $valor;
            }
        }

        foreach (['tokencreado', 'token', 'accesstoken', 'jwt', 'bearertoken'] as $key) {
            $valor = $mapa[$key] ?? null;
            if (is_string($valor) && $valor !== '') {
                return $valor;
            }
        }

        // A veces viene anidado
        foreach (['data', 'result', 'response'] as $wrap) {
            if (isset($mapa[$wrap]) && is_array($mapa[$wrap])) {
                $nested = $this->extraerTokenLogin($mapa[$wrap]);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    /**
     * Procesa la respuesta HTTP y mapea a estructura estandarizada.
     */
    private function procesarRespuesta(Response $response, string $endpoint): array
    {
        $status = $response->status();
        $body = $response->json() ?? [];

        if ($response->successful()) {
            // Respuesta vacía = no encontrado
            if (empty($body)) {
                return $this->buildErrorResponse(
                    'No se encontraron resultados',
                    ProveedorApiException::NO_ENCONTRADO
                );
            }

            return $this->buildSuccessResponse($body);
        }

        // Mapeo de códigos HTTP a tipos de error
        if ($status === 401) {
            Log::error('ProveedorAPI: autenticación fallida', ['endpoint' => $endpoint, 'status' => $status]);

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
            Log::error('ProveedorAPI: error de servidor', ['endpoint' => $endpoint, 'status' => $status]);

            return $this->buildErrorResponse(
                'La API del proveedor no está disponible temporalmente',
                ProveedorApiException::ERROR_SERVIDOR
            );
        }

        Log::error('ProveedorAPI: error desconocido', ['endpoint' => $endpoint, 'status' => $status]);

        return $this->buildErrorResponse(
            'Ocurrió un error inesperado',
            ProveedorApiException::ERROR_DESCONOCIDO
        );
    }

    private function buildSuccessResponse(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => 'OK',
            'error_type' => null,
        ];
    }

    private function buildErrorResponse(string $message, string $errorType): array
    {
        return [
            'success' => false,
            'data' => null,
            'message' => $message,
            'error_type' => $errorType,
        ];
    }

    /**
     * Determina si una respuesta HTTP es retryable (5xx).
     */
    private function esRetryable(Response $response): bool
    {
        return in_array($response->status(), [500, 502, 503, 504]);
    }
}
