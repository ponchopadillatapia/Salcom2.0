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

        // Endpoint real (confirmado en Swagger Wiese): ListaDocumentosOCPorProveedor.
        // Los 3 parámetros son OBLIGATORIOS:
        //   - codigoProveedor: código Wiese del proveedor.
        //   - strIdConceptosOC: ID del concepto de OC. NO acepta 0 como "todos"; hay que
        //       pedir cada concepto de Orden de Compra. IDs confirmados en Wiese:
        //       19 = OC Materia Prima, 2004 = OC Producto Terminado, 3015 = OC Importación,
        //       3151 = OC Mantenimiento, 3130 = OC EPP.
        //   - fecha: fecha de corte válida (SQL Server rechaza fechas vacías/año 0).
        // Iteramos sobre todos los conceptos de OC y juntamos los resultados, para que
        // funcione con cualquier proveedor (sea de M.P., P.T., etc.).
        $fechaCorte = trim($fechaInicio) !== '' ? $fechaInicio : '2000-01-01';
        $conceptosOC = ['19', '2004', '3015', '3151', '3130'];

        $endpoint = '/Documento/ListaDocumentosOCPorProveedor';
        $items = [];

        try {
            foreach ($conceptosOC as $idConcepto) {
                $response = Http::connectTimeout($this->connectTimeout)
                    ->timeout(max($this->timeout, 60))
                    ->withToken($token)
                    ->acceptJson()
                    ->get($this->docsUrl.$endpoint, [
                        'codigoProveedor' => $codigoProveedor,
                        'strIdConceptosOC' => $idConcepto,
                        'fecha' => $fechaCorte,
                    ]);

                if (! $response->successful()) {
                    continue; // este concepto fallo; seguimos con los demas
                }

                $body = $response->json();
                if (is_array($body) && $body !== []) {
                    $lote = array_is_list($body) ? $body : [$body];
                    foreach ($lote as $oc) {
                        $items[] = $oc;
                    }
                }
            }

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
     * Consulta REAL a Wiese: busca un proveedor por su CÓDIGO o por su RFC.
     * Usa el host de docs (172.16.1.250) que es donde vive ClienteProveedor.
     * Confirmado funcionando con ORPACK (BuscarPorCodigo / BuscarPorRFC).
     *
     * @param  string  $valor  código Wiese o RFC del proveedor
     * @param  bool  $porRfc  true = buscar por RFC; false = por código
     * @return array{success: bool, data?: array, message: string, error_type: ?string}
     */
    public function buscarProveedorWiese(string $valor, bool $porRfc = false): array
    {
        $valor = trim($valor);
        if ($valor === '') {
            return $this->buildErrorResponse('Escribe un código o RFC.', 'validation');
        }

        $configError = $this->validarDocsConfiguracion();
        if ($configError) {
            return $configError;
        }

        // Login de servicio para obtener el token de Wiese.
        $login = $this->loginServicio();
        if (! ($login['success'] ?? false)) {
            return $login;
        }
        $token = (string) ($login['data']['tokenCreado'] ?? '');

        $endpoint = $porRfc
            ? '/ClienteProveedor/BuscarPorRFC'
            : '/ClienteProveedor/BuscarPorCodigo';
        $params = $porRfc ? ['rfc' => strtoupper($valor)] : ['codigo' => $valor];

        try {
            $response = Http::connectTimeout($this->connectTimeout)
                ->timeout(max($this->timeout, 30))
                ->withToken($token)
                ->acceptJson()
                ->get($this->docsUrl.$endpoint, $params);

            if (! $response->successful()) {
                return $this->procesarRespuesta($response, $endpoint);
            }

            $body = $response->json();
            if (! is_array($body) || empty($body)) {
                return $this->buildErrorResponse('No se encontró el proveedor en Wiese.', ProveedorApiException::NO_ENCONTRADO);
            }

            return $this->buildSuccessResponse($body);
        } catch (ConnectionException $e) {
            Log::error('ProveedorAPI: conexión fallida (buscar proveedor Wiese)', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('No se pudo conectar con Wiese (¿VPN activa?).', ProveedorApiException::API_CAIDA);
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error buscar proveedor Wiese', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('Ocurrió un error al consultar Wiese.', ProveedorApiException::ERROR_DESCONOCIDO);
        }
    }

    /**
     * Buscar proveedor por RFC en Wiese y devolver sus cuentas (1 o 2: MXN y USD).
     *
     * POR QUÉ EXISTE: lo usa el onboarding (paso "Confirmación de cuenta") para preguntarle
     * al proveedor recién registrado si la cuenta que Wiese tiene con su RFC es suya. Al
     * confirmar, se guarda el código Wiese (id_proveedor) y quedan ligados portal <-> Wiese.
     *
     * ANTES estaba SIMULADO (hardcodeado solo para ORPACK). Ahora hace la llamada REAL al
     * endpoint /ClienteProveedor/BuscarPorRFC vía buscarProveedorWiese(), y adapta la
     * respuesta cruda de Wiese al formato { cuentas: [{codigo, razonSocial, moneda, fechaAlta}] }
     * que espera el onboarding.
     */
    public function buscarProveedorPorRFC(string $rfc): array
    {
        $rfc = strtoupper(trim($rfc));

        if (empty($rfc)) {
            return $this->buildErrorResponse('RFC vacío', 'validation');
        }

        // Llamada REAL a Wiese (login + /ClienteProveedor/BuscarPorRFC).
        $res = $this->buscarProveedorWiese($rfc, true);

        // Si no se encontró el proveedor en Wiese, devolvemos lista vacía (no es error:
        // simplemente ese RFC no está dado de alta en el sistema contable todavía).
        if (! ($res['success'] ?? false)) {
            $tipo = $res['error_type'] ?? null;
            if ($tipo === ProveedorApiException::NO_ENCONTRADO) {
                return ['success' => true, 'data' => ['cuentas' => []]];
            }

            return $res; // error real (conexión / VPN) — se propaga tal cual
        }

        // Wiese devuelve UN objeto de proveedor. Lo adaptamos al formato de "cuentas".
        $prov = $res['data'] ?? [];
        $codigo = trim((string) ($prov['codigo'] ?? ''));

        if ($codigo === '') {
            return ['success' => true, 'data' => ['cuentas' => []]];
        }

        $cuentas = [[
            'codigo' => $codigo,
            'razonSocial' => $prov['crazonsocial'] ?? ($prov['nombre'] ?? ''),
            'moneda' => (int) ($prov['cidmoneda'] ?? 1) === 1 ? 'MXN' : 'USD',
            'fechaAlta' => $prov['fechaCreacion'] ?? null,
            'rfc' => $prov['crfc'] ?? $rfc,
        ]];

        return ['success' => true, 'data' => ['cuentas' => $cuentas]];
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
