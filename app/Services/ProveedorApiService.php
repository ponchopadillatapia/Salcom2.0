<?php

namespace App\Services;

use App\Exceptions\ProveedorApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
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
            $response = $this->http()
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
            $response = $this->http()
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
                $response = $this->http(max($this->timeout, 60))
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
     * GET /DatoDocumentoProveedor/ListarDocumentosRFC
     *
     * Trae las FACTURAS de un proveedor buscando por su RFC (endpoint que armó Alan).
     * A diferencia del de OC (por código), este devuelve directamente las facturas de compra
     * con su saldo pendiente, ideal para "Formato para pago".
     *
     * Regla de negocio (confirmada con Said): factura PENDIENTE = saldo > 0; PAGADA = saldo 0.
     * Cada item de Wiese trae, entre otros: codigo, folio, serie, fechaFactura, fechaVence,
     * total, saldo, idMoneda (1=MXN, 2=USD), tipoCambio, idDocumento.
     *
     * Se mapea a un formato limpio y estable para las vistas. Si $token es null, hace login solo.
     *
     * @param  string  $rfc  RFC del proveedor
     * @param  string  $fechaInicial  ISO date-time (ej. 2020-01-01T00:00:00)
     * @param  string  $fechaFinal    ISO date-time (ej. 2026-12-31T23:59:59)
     * @return array{success: bool, data?: array{items: list<array>, total: int}, message: string, error_type: ?string}
     */
    public function listarFacturasProveedorPorRFC(
        string $rfc,
        string $fechaInicial = '2020-01-01T00:00:00',
        string $fechaFinal = '2035-12-31T23:59:59',
        ?string $token = null
    ): array {
        $rfc = trim($rfc);
        if ($rfc === '') {
            return $this->buildErrorResponse('Falta el RFC del proveedor.', 'validation');
        }

        $configError = $this->validarDocsConfiguracion();
        if ($configError) {
            return $configError;
        }

        if ($token === null || $token === '') {
            $login = $this->loginServicio();
            if (! ($login['success'] ?? false)) {
                return $login;
            }
            $token = (string) ($login['data']['tokenCreado'] ?? '');
        }

        $endpoint = '/DatoDocumentoProveedor/ListarDocumentosRFC';

        try {
            $response = $this->http(max($this->timeout, 60))
                ->withToken($token)
                ->acceptJson()
                ->get($this->docsUrl.$endpoint, [
                    'RFC' => strtoupper($rfc),
                    'fechaInicial' => $fechaInicial,
                    'fechaFinal' => $fechaFinal,
                ]);

            if (! $response->successful()) {
                return $this->procesarRespuesta($response, $endpoint);
            }

            $body = $response->json();
            $lote = is_array($body) ? (array_is_list($body) ? $body : [$body]) : [];

            // Mapear cada factura a un formato limpio y estable para las vistas.
            $items = [];
            foreach ($lote as $f) {
                $saldo = (float) ($f['saldo'] ?? 0);
                $total = (float) ($f['total'] ?? 0);
                $items[] = [
                    'codigo' => (string) ($f['codigo'] ?? ''),
                    'folio' => $f['folio'] ?? null,
                    'serie' => (string) ($f['serie'] ?? ''),
                    'nombre' => (string) ($f['nombre'] ?? ''),
                    'fecha_factura' => $f['fechaFactura'] ?? null,
                    'fecha_vence' => $f['fechaVence'] ?? null,
                    'total' => $total,
                    'saldo' => $saldo,
                    'moneda' => ((string) ($f['idMoneda'] ?? '1')) === '2' ? 'USD' : 'MXN',
                    'tipo_cambio' => (float) ($f['tipoCambio'] ?? 1),
                    'id_documento' => $f['idDocumento'] ?? null,
                    'referencia' => (string) ($f['referencia'] ?? ''),
                    // Bandera de negocio: saldo > 0 = pendiente de pago.
                    'pendiente' => $saldo > 0,
                ];
            }

            return $this->buildSuccessResponse([
                'items' => $items,
                'total' => count($items),
                'rfc' => strtoupper($rfc),
            ]);
        } catch (ConnectionException $e) {
            Log::error('ProveedorAPI: conexión fallida (facturas por RFC)', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('No se pudo conectar con Wiese (¿VPN activa?).', ProveedorApiException::API_CAIDA);
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error facturas por RFC', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('Ocurrió un error al consultar facturas en Wiese.', ProveedorApiException::ERROR_DESCONOCIDO);
        }
    }

    /**
     * GET /DatoDocumentoProveedor/ListarDocumentosRFC CON RFC VACÍO = TODOS los proveedores.
     *
     * POR QUÉ: para la tabla global de "Formato para pago" (estilo reporte Wiese) necesitamos las
     * facturas pendientes de TODOS los proveedores de un jalón. Alan ajustó el MISMO endpoint para
     * que, si el RFC va vacío, no filtre por proveedor y devuelva todas. Es UNA sola llamada (no
     * 5,690) y Wiese sigue siendo la única fuente (no se precarga ni duplica nada en la BD local).
     *
     * Mapea los campos EXTRA que ya devuelve el endpoint (confirmados en Swagger): vencido8Dias,
     * vencido15Dias, vencidoMas15Dias, vencidoTotal, saldoDlls, importePago, pagar, ivaFacturaDLLS.
     * Así la tabla sale igual que el reporte de Wiese sin recalcular nada del lado de Salcom.
     *
     * @return array{success: bool, data?: array{items: list<array>, total: int}, message: string, error_type: ?string}
     */
    public function listarFacturasPendientesTodos(
        string $fechaInicial = '2020-01-01',
        string $fechaFinal = '2035-12-31',
        ?string $token = null,
        string $codigoFiltro = '' // si se da, devuelve SOLO las facturas de ese código de proveedor
    ): array {
        // DOS caminos según dónde corra la app:
        //  - PRODUCCIÓN (SiteGround): usa la API pública 5009 (PAGOS_API_URL), que hace de puente
        //    y reenvía al 250 interno. El endpoint ahí vive en el controlador PagoProveedor.
        //  - OFICINA/LOCAL (PAGOS_API_URL vacío): va directo al 250 (docs_url), controlador
        //    DatoDocumentoProveedor, como siempre.
        // POR QUÉ se decide ESTO ARRIBA: en SiteGround docsUrl va vacío (no alcanza el 250).
        // Si validáramos docsUrl o hiciéramos loginServicio() ANTES, el método moriría aquí
        // y la tabla saldría vacía aunque el 5009 funcione. El 5009 hace su propio login interno,
        // así que por ese camino NO exigimos docsUrl ni token del 250.
        $pagosUrl = rtrim((string) config('services.proveedor_api.pagos_url', ''), '/');

        if ($pagosUrl !== '') {
            // Camino 5009 (producción): sin docsUrl, sin loginServicio al 250.
            // POR QUÉ: desde sep-2026 el 5009 exige JWT. Pedimos el token de entrada (GATEWAY_TOKEN)
            // al propio 5009 vía /login y lo mandamos como Bearer. El 5009 hace su login interno al 250.
            $base = $pagosUrl;
            $endpoint = '/PagoProveedor/ListarDocumentos';     // ruta en la API 5009
            $token = $this->tokenGateway5009($pagosUrl);
        } else {
            // Camino 250 directo (oficina/local): sí requiere docsUrl y login al 250.
            $configError = $this->validarDocsConfiguracion();
            if ($configError) {
                return $configError;
            }

            if ($token === null || $token === '') {
                $login = $this->loginServicio();
                if (! ($login['success'] ?? false)) {
                    return $login;
                }
                $token = (string) ($login['data']['tokenCreado'] ?? '');
            }

            $base = $this->docsUrl;
            $endpoint = '/DatoDocumentoProveedor/ListarDocumentos'; // ruta en el 250 directo
        }

        try {
            // Timeout alto (180s): trae facturas de TODOS los proveedores, puede tardar.
            $peticion = $this->http(180)->acceptJson();

            // POR QUÉ: en el camino 5009 el token va vacío (el puente autentica solo),
            // así evitamos mandar un header "Authorization: Bearer " vacío sin sentido.
            if ($token !== '') {
                $peticion = $peticion->withToken($token);
            }

            // POR QUÉ CIDDOCUMENTODE=19: desde que Alan lo volvió obligatorio, ListarDocumentos
            // responde 400 ("CIDDOCUMENTODE field is required") si no se manda. Las facturas de
            // proveedor (compras) son tipo 19 en Wiese/Contpaqi (las OC son 17). Confirmado buscando
            // la factura real 93150 de ORPACK: aparece en tipo 19. Configurable por si cambia.
            $tipoFactura = (string) config('facturas.tipo_documento_compra', '19');
            $response = $peticion->get($base.$endpoint, [
                'fechaInicial' => $fechaInicial,
                'fechaFinal' => $fechaFinal,
                'CIDDOCUMENTODE' => $tipoFactura,
            ]);

            if (! $response->successful()) {
                return $this->procesarRespuesta($response, $endpoint);
            }

            $body = $response->json();
            $lote = is_array($body) ? (array_is_list($body) ? $body : [$body]) : [];

            $codigoFiltro = trim($codigoFiltro);
            $items = [];
            foreach ($lote as $f) {
                $saldo = (float) ($f['saldo'] ?? 0);
                if ($saldo <= 0) {
                    continue; // solo pendientes (saldo > 0)
                }
                // Si se pidió un proveedor específico, saltar los demás (filtro por código).
                if ($codigoFiltro !== '' && (string) ($f['codigo'] ?? '') !== $codigoFiltro) {
                    continue;
                }
                $items[] = [
                    'codigo' => (string) ($f['codigo'] ?? ''),
                    'nombre' => (string) ($f['nombre'] ?? ''),
                    'serie' => (string) ($f['serie'] ?? ''),
                    'folio' => $f['folio'] ?? null,
                    'fecha_factura' => $f['fechaFactura'] ?? null,
                    'fecha_vence' => $f['fechaVence'] ?? null,
                    'total' => (float) ($f['total'] ?? 0),
                    'saldo' => $saldo,
                    'moneda' => ((string) ($f['idMoneda'] ?? '1')) === '2' ? 'USD' : 'MXN',
                    'tipo_cambio' => (float) ($f['tipoCambio'] ?? 1),
                    'referencia' => (string) ($f['referencia'] ?? ''),
                    // Columnas calculadas que YA trae el endpoint (iguales al reporte de Wiese).
                    'vencido_8' => (float) ($f['vencido8Dias'] ?? 0),
                    'vencido_15' => (float) ($f['vencido15Dias'] ?? 0),
                    'vencido_mas15' => (float) ($f['vencidoMas15Dias'] ?? 0),
                    'vencido_total' => (float) ($f['vencidoTotal'] ?? 0),
                    'saldo_dlls' => (float) ($f['saldoDlls'] ?? 0),
                    'importe_pago' => (float) ($f['importePago'] ?? 0),
                    'iva_dlls' => (float) ($f['ivaFacturaDLLS'] ?? 0),
                    'id_documento' => $f['idDocumento'] ?? null,
                    // Bandera de negocio: ya filtramos saldo>0 arriba, así que siempre es true.
                    // Se incluye porque las vistas del detalle filtran por 'pendiente'.
                    'pendiente' => true,
                ];
            }

            return $this->buildSuccessResponse([
                'items' => $items,
                'total' => count($items),
            ]);
        } catch (ConnectionException $e) {
            Log::error('ProveedorAPI: conexión fallida (facturas pendientes de todos)', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('No se pudo conectar con Wiese (¿VPN activa?).', ProveedorApiException::API_CAIDA);
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error facturas pendientes de todos', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('Ocurrió un error al consultar facturas en Wiese.', ProveedorApiException::ERROR_DESCONOCIDO);
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

        $params = $porRfc ? ['rfc' => strtoupper($valor)] : ['codigo' => $valor];

        // DOS caminos (igual que el resto): 5009 en producción, 250 directo en oficina.
        $pagosUrl = rtrim((string) config('services.proveedor_api.pagos_url', ''), '/');

        if ($pagosUrl !== '') {
            // Producción: el 5009 hace su propio login al 250; aquí solo autenticamos CONTRA el 5009.
            // POR QUÉ: desde sep-2026 el 5009 exige JWT (antes era abierto). Token de entrada vía /login.
            $base = $pagosUrl;
            $endpoint = $porRfc
                ? '/PagoProveedor/BuscarPorRFC'
                : '/PagoProveedor/BuscarPorCodigo';
            $token = $this->tokenGateway5009($pagosUrl);
        } else {
            // Oficina/local: 250 directo, requiere docsUrl + login.
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

            $base = $this->docsUrl;
            $endpoint = $porRfc
                ? '/ClienteProveedor/BuscarPorRFC'
                : '/ClienteProveedor/BuscarPorCodigo';
        }

        try {
            $peticion = $this->http(max($this->timeout, 30))->acceptJson();
            if ($token !== '') {
                $peticion = $peticion->withToken($token);
            }
            $response = $peticion->get($base.$endpoint, $params);

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
     * Lista TODOS los proveedores desde la API de Wiese (endpoint que armó Alan).
     *
     * POR QUÉ EXISTE: hasta ahora solo podíamos traer proveedores de Wiese de UNO en UNO
     * (por código o RFC). Alan creó en su API de C#/.NET el endpoint
     * `/ClienteProveedor/ListarProveedorWeb`, que hace un SELECT a la base contable
     * (adSalcom18.dbo.admClientes) y devuelve la lista completa. Esto nos permite mostrar
     * en el portal admin los proveedores REALES de Wiese, no solo los que se registraron.
     *
     * Reutiliza loginServicio() (mismo usuario 'web' de servicio) para el token Bearer.
     * Solo LEE (GET): no modifica nada del sistema de Alan.
     *
     * @return array{success: bool, data?: array{items: list<mixed>, total: int}, message: string, error_type: ?string}
     */
    public function listarProveedoresWiese(?string $token = null): array
    {
        // DOS caminos (igual que listarFacturasPendientesTodos):
        //  - PRODUCCIÓN (SiteGround): 5009 (PAGOS_API_URL), ruta /PagoProveedor/ListarProveedores.
        //    El 5009 hace su propio login al 250, así que NO exigimos docsUrl ni token.
        //  - OFICINA/LOCAL (PAGOS_API_URL vacío): 250 directo (docsUrl), ruta ClienteProveedor.
        $pagosUrl = rtrim((string) config('services.proveedor_api.pagos_url', ''), '/');

        if ($pagosUrl !== '') {
            // POR QUÉ: desde sep-2026 el 5009 exige JWT. Autenticamos contra el 5009 vía /login;
            // el 5009 hace su propio login al 250 internamente.
            $base = $pagosUrl;
            $endpoint = '/PagoProveedor/ListarProveedores'; // puente en el 5009
            $token = $this->tokenGateway5009($pagosUrl);
        } else {
            // Si no nos pasan token, hacemos login de servicio para obtenerlo.
            if ($token === null || $token === '') {
                $login = $this->loginServicio();
                if (! ($login['success'] ?? false)) {
                    return $login;
                }
                $token = (string) ($login['data']['tokenCreado'] ?? '');
            }

            $configError = $this->validarDocsConfiguracion();
            if ($configError) {
                return $configError;
            }

            $base = $this->docsUrl;
            // Ruta confirmada con Alan: movió el endpoint a ClienteProveedor para que
            // apareciera en Swagger (antes no salía con su propia etiqueta).
            $endpoint = '/ClienteProveedor/ListarProveedorWeb';
        }

        try {
            $peticion = $this->http(max($this->timeout, 60))->acceptJson();
            // POR QUÉ: en el camino 5009 el token va vacío (el puente autentica solo).
            if ($token !== '') {
                $peticion = $peticion->withToken($token);
            }
            $response = $peticion->get($base.$endpoint);

            if (! $response->successful()) {
                return $this->procesarRespuesta($response, $endpoint);
            }

            $body = $response->json();
            // La API devuelve una lista JSON. Si viene un solo objeto, lo envolvemos.
            $items = [];
            if (is_array($body) && $body !== []) {
                $items = array_is_list($body) ? $body : [$body];
            }

            return $this->buildSuccessResponse([
                'items' => $items,
                'total' => count($items),
            ]);
        } catch (ConnectionException $e) {
            Log::error('ProveedorAPI: conexión fallida (listar proveedores Wiese)', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('No se pudo conectar con Wiese (¿VPN activa?).', ProveedorApiException::API_CAIDA);
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error listar proveedores Wiese', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('Ocurrió un error al listar proveedores de Wiese.', ProveedorApiException::ERROR_DESCONOCIDO);
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

    /**
     * Busca UNA Orden de Compra (OC) de un proveedor por su número de folio, y devuelve sus
     * movimientos (renglones con cantidades).
     *
     * POR QUÉ EXISTE (Fase 1 del cruce factura↔OC): cuando el proveedor sube su factura escribe
     * el número de OC (ej. 81686). Con ese folio y el código del proveedor, pedimos al API todas
     * sus OC (CIDDOCUMENTODE = 17, que ya vienen con Movimientos) y nos quedamos con la que tenga
     * ese folio. De ahí sacamos la cantidad pedida y lo recibido (cunidades − cunidadespendientes).
     *
     * @param  string  $codigoProveedor  código Wiese del proveedor
     * @param  string  $folioOc          número de OC que escribió el proveedor
     * @return array{success: bool, data?: array, message: string, error_type: ?string}
     */
    public function buscarOcPorFolio(string $codigoProveedor, string $folioOc, float $totalFactura = 0.0): array
    {
        $codigoProveedor = trim($codigoProveedor);
        $folioOc = trim($folioOc);
        if ($codigoProveedor === '' || $folioOc === '') {
            return $this->buildErrorResponse('Falta el código del proveedor o el número de OC.', 'validation');
        }

        // POR QUÉ ventana acotada (y NO 2020-2035): con CIDDOCUMENTODE=17 el 250 trae las OC CON
        // todos sus Movimientos. Pedir 15 años de historia para un proveedor con muchas OC hace que
        // la consulta tarde > límite de PHP y truene con timeout (lo vimos en pruebas: 2020-2035 =
        // cuelgue; solo 2026 = responde en ~8s con 1MB). Una OC siempre es anterior o cercana a la
        // factura, no de años atrás; con ~18 meses hacia atrás cubrimos de sobra los casos reales.
        $meses = (int) config('facturas.oc_ventana_meses', 18);
        $params = [
            'fechaInicial' => now()->subMonths($meses)->startOfMonth()->format('Y-m-d'),
            'fechaFinal' => now()->addMonth()->endOfMonth()->format('Y-m-d'),
            'codigo' => $codigoProveedor,
            'CIDDOCUMENTODE' => '17', // 17 = Órdenes de Compra (traen Movimientos con cantidades)
        ];

        // DOS caminos (igual que el resto): 5009 en producción, 250 directo en oficina.
        $pagosUrl = rtrim((string) config('services.proveedor_api.pagos_url', ''), '/');

        if ($pagosUrl !== '') {
            $base = $pagosUrl;
            $endpoint = '/PagoProveedor/ListarDocumentosCodigo'; // puente en el 5009
            $token = $this->tokenGateway5009($pagosUrl);
        } else {
            $configError = $this->validarDocsConfiguracion();
            if ($configError) {
                return $configError;
            }
            $login = $this->loginServicio();
            if (! ($login['success'] ?? false)) {
                return $login;
            }
            $token = (string) ($login['data']['tokenCreado'] ?? '');
            $base = $this->docsUrl;
            $endpoint = '/DatoDocumentoProveedor/ListarDocumentosCodigo';
        }

        try {
            // POR QUÉ 60s: el 250 NO sabe filtrar por folio (probado: ignora folio/Folio/cfolio y
            // siempre devuelve TODAS las OC del proveedor — ORPACK tiene 1864 — con sus movimientos).
            // Esa consulta tarda ~20-30s y a veces pico de 30s+. Con 25s se cortaba en demo (cURL 28).
            // Subimos a 60s de margen. El tope lo pone config para poder ajustarlo sin tocar código.
            // NOTA: la solución de fondo (que respondería en ms) es que Alan agregue filtro por folio
            // en el API del 250; mientras no exista, toca esperar a que traiga todo.
            $timeoutOc = (int) config('facturas.oc_timeout_seg', 60);
            $peticion = $this->http($timeoutOc)->acceptJson();
            if ($token !== '') {
                $peticion = $peticion->withToken($token);
            }
            $response = $peticion->get($base.$endpoint, $params);

            if (! $response->successful()) {
                return $this->procesarRespuesta($response, $endpoint);
            }

            $body = $response->json();
            $lista = is_array($body) ? (array_is_list($body) ? $body : [$body]) : [];

            // Buscar la OC cuyo folio coincida con el que escribió el proveedor.
            // El folio puede venir como 'folio', 'Folio' o 'cfolio' según el documento.
            //
            // POR QUÉ normalizamos a solo dígitos: en Wiese la OC a veces trae un prefijo de letra
            // (ej. "A81686" para parcialidades) o ceros a la izquierda ("081686"). El proveedor
            // escribe "81686" a secas. Dejando solo los números en AMBOS lados, "A81686", "081686"
            // y "81686" se consideran la misma OC y evitamos un falso "no encontrada".
            $soloDigitos = static fn (string $v): string => ltrim(preg_replace('/\D/', '', $v) ?? '', '0');
            $folioBuscado = $soloDigitos($folioOc);

            // POR QUÉ juntamos TODAS (y no la primera): en Wiese el folio NO es único. Puede haber
            // varias OC con el mismo folio pero distinta SERIE (ej. 81686 serie '' = 7200 pzas y
            // 81686 serie '-A' = 8754 pzas). Si nos quedábamos con la primera (break), agarrábamos
            // la equivocada. Las juntamos y abajo elegimos la correcta por el total de la factura.
            $candidatas = [];
            foreach ($lista as $doc) {
                $folioDoc = (string) ($doc['folio'] ?? $doc['Folio'] ?? $doc['cfolio'] ?? '');
                if ($folioDoc !== '' && $folioBuscado !== '' && $soloDigitos($folioDoc) === $folioBuscado) {
                    $candidatas[] = $doc;
                }
            }

            if (count($candidatas) === 0) {
                return $this->buildErrorResponse(
                    "No se encontró la OC {$folioOc} para el proveedor {$codigoProveedor} en Wiese. Verifica el número de OC.",
                    ProveedorApiException::NO_ENCONTRADO
                );
            }

            // DESEMPATE por total: si hay varias OC con el mismo folio, elegimos la que su total se
            // parezca más al de la factura. POR QUÉ el total: la factura tiene que cuadrar en dinero
            // con su OC, así que es el mejor dato para distinguir versiones del mismo folio sin
            // pedirle la serie al proveedor. Si solo hay una candidata, se usa esa sin más.
            // NOTA Fase 2: cuando haya parcialidades el total puede NO cuadrar exacto; ahí habrá que
            // usar la serie/prefijo. Por eso guardamos la serie en el resultado (ver 'serie_oc').
            if (count($candidatas) === 1 || $totalFactura <= 0) {
                $oc = $candidatas[0];
            } else {
                usort($candidatas, static function (array $a, array $b) use ($totalFactura): int {
                    $da = abs(((float) ($a['total'] ?? 0)) - $totalFactura);
                    $db = abs(((float) ($b['total'] ?? 0)) - $totalFactura);

                    return $da <=> $db; // la de menor diferencia contra el total de la factura va primero
                });
                $oc = $candidatas[0];
            }

            // Normalizar los movimientos (renglones con cantidades) a un formato limpio.
            $movs = $oc['movimientos'] ?? $oc['Movimientos'] ?? [];
            $items = [];
            $totalPedido = 0.0;
            $totalRecibido = 0.0;
            foreach ((is_array($movs) ? $movs : []) as $m) {
                $pedido = (float) ($m['cunidades'] ?? 0);
                $pendiente = (float) ($m['cunidadespendientes'] ?? 0);
                $recibido = max(0.0, $pedido - $pendiente); // lo que llegó a almacén (Acatlán)
                $totalPedido += $pedido;
                $totalRecibido += $recibido;
                $items[] = [
                    'producto' => (string) ($m['codigoProducto'] ?? ''),
                    'cantidad_pedida' => $pedido,
                    'cantidad_pendiente' => $pendiente,
                    'cantidad_recibida' => $recibido,
                    'precio' => (float) ($m['cprecio'] ?? 0),
                    'total' => (float) ($m['ctotal'] ?? 0),
                ];
            }

            return $this->buildSuccessResponse([
                'folio_oc' => $folioOc,
                // serie_oc: en Wiese es lo que distingue dos OC del mismo folio (ej. '-A'). Hoy no
                // decide nada, pero lo guardamos para Fase 2 (parcialidades/prefijos) y para auditoría.
                'serie_oc' => (string) ($oc['serie'] ?? $oc['Serie'] ?? ''),
                // candidatas: cuántas OC compartían el folio. Si es > 1, elegimos por total; sirve
                // para que el admin sepa que hubo desempate y revise si el total no cuadró claro.
                'candidatas' => count($candidatas),
                'id_documento' => $oc['idDocumento'] ?? $oc['ciddocumento'] ?? null,
                'total_oc' => (float) ($oc['total'] ?? 0),
                'cantidad_pedida_total' => $totalPedido,
                'cantidad_recibida_total' => $totalRecibido,
                'movimientos' => $items,
            ]);
        } catch (ConnectionException $e) {
            Log::error('ProveedorAPI: conexión fallida (buscar OC por folio)', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('No se pudo conectar con Wiese para buscar la OC.', ProveedorApiException::API_CAIDA);
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error buscar OC por folio', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return $this->buildErrorResponse('Ocurrió un error al buscar la OC en Wiese.', ProveedorApiException::ERROR_DESCONOCIDO);
        }
    }

    // ── Métodos privados ──

    /**
     * Obtiene un JWT del gateway 5009 (APIPortalWeb) para autenticar las llamadas.
     *
     * POR QUÉ EXISTE: desde sep-2026 el 5009 dejó de ser abierto. Ahora cada endpoint exige
     * un JWT en el header "Authorization: Bearer". El JWT se consigue mandando el GATEWAY_TOKEN
     * (contraseña de entrada) a POST /login; el 5009 responde con un token que dura 1 hora.
     *
     * Se CACHEA el JWT ~55 min para no pedir uno nuevo en cada llamada (el token dura 60 min;
     * dejamos 5 de margen por si el reloj va desfasado). Si no hay GATEWAY_TOKEN configurado
     * (ej. en oficina/local donde se va directo al 250), devuelve '' y no se autentica.
     *
     * @param  string  $baseUrl  URL del 5009 (pagos_url)
     * @return string  el JWT, o '' si no se pudo obtener / no aplica
     */
    private function tokenGateway5009(string $baseUrl): string
    {
        $gatewayToken = (string) config('services.proveedor_api.pagos_gateway_token', '');

        // Sin contraseña de entrada no hay forma de autenticarse; devolvemos vacío.
        if ($gatewayToken === '' || $baseUrl === '') {
            return '';
        }

        // Caché compartido entre requests: evita pegarle a /login en cada llamada.
        // La clave incluye un hash del token para que, si se rota el GATEWAY_TOKEN, no se
        // reuse por error un JWT viejo cacheado con la contraseña anterior.
        $cacheKey = 'pagos5009_jwt_'.substr(hash('sha256', $baseUrl.'|'.$gatewayToken), 0, 16);

        $jwtCacheado = \Illuminate\Support\Facades\Cache::get($cacheKey);
        if (is_string($jwtCacheado) && $jwtCacheado !== '') {
            return $jwtCacheado;
        }

        try {
            $response = $this->http(15)
                ->acceptJson()
                ->asJson()
                ->post(rtrim($baseUrl, '/').'/login', [
                    'token' => $gatewayToken,
                ]);

            if (! $response->successful()) {
                Log::error('ProveedorAPI: login al gateway 5009 falló', [
                    'status' => $response->status(),
                ]);

                return '';
            }

            $body = $response->json();
            $jwt = is_array($body) ? (string) ($body['token'] ?? '') : '';
            // El 5009 dice cuánto dura el token; restamos 5 min de margen de seguridad.
            $expira = is_array($body) ? (int) ($body['expira_en_segundos'] ?? 3600) : 3600;
            $ttl = max(60, $expira - 300);

            if ($jwt === '') {
                Log::error('ProveedorAPI: /login del 5009 no devolvió token');

                return '';
            }

            \Illuminate\Support\Facades\Cache::put($cacheKey, $jwt, $ttl);

            return $jwt;
        } catch (\Exception $e) {
            Log::error('ProveedorAPI: error al pedir JWT al gateway 5009', [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function http(?int $timeout = null): PendingRequest
    {
        $request = Http::connectTimeout($this->connectTimeout)
            ->timeout($timeout ?? $this->timeout);

        $token = (string) config('services.proveedor_api.gateway_token', '');
        if ($token !== '') {
            $request = $request->withHeaders(['X-Salcom-Gateway' => $token]);
        }

        return $request;
    }

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
                $response = $this->http()
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
