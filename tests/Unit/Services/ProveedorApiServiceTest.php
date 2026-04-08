<?php

namespace Tests\Unit\Services;

use App\Exceptions\ProveedorApiException;
use App\Services\ProveedorApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProveedorApiServiceTest extends TestCase
{
    private ProveedorApiService $service;
    private string $baseUrl = 'http://fake-api.test/api';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.proveedor_api.url' => $this->baseUrl]);
        config(['services.proveedor_api.connect_timeout' => 5]);
        config(['services.proveedor_api.timeout' => 15]);
        config(['services.proveedor_api.max_retries' => 3]);
        $this->service = new ProveedorApiService();
    }

    // ── Helper: assert standard response structure ──
    private function assertStandardResponse(array $result): void
    {
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('error_type', $result);
        $this->assertIsBool($result['success']);
        $this->assertIsString($result['message']);

        if ($result['success']) {
            $this->assertNotNull($result['data']);
            $this->assertNull($result['error_type']);
        } else {
            $this->assertNull($result['data']);
            $this->assertIsString($result['error_type']);
            $this->assertNotEmpty($result['error_type']);
        }
    }

    // ═══════════════════════════════════════════
    // Task 4.4: loginApi unit tests
    // ═══════════════════════════════════════════

    public function test_login_api_success(): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([
                'usuario' => 'PROV001',
                'tokencreado' => 'jwt-token-123',
            ], 200),
        ]);

        $result = $this->service->loginApi('PROV001', 'secret');

        $this->assertStandardResponse($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('PROV001', $result['data']['usuario']);
        $this->assertEquals('jwt-token-123', $result['data']['tokencreado']);
    }

    public function test_login_api_401_returns_autenticacion_fallida(): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([], 401),
        ]);

        $result = $this->service->loginApi('PROV001', 'wrong');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::AUTENTICACION_FALLIDA, $result['error_type']);
    }

    public function test_login_api_url_vacia_retorna_api_caida(): void
    {
        config(['services.proveedor_api.url' => '']);
        $service = new ProveedorApiService();

        Http::fake(); // Should NOT be called

        $result = $service->loginApi('PROV001', 'secret');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::API_CAIDA, $result['error_type']);
        Http::assertNothingSent();
    }

    public function test_login_api_empty_response_returns_no_encontrado(): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([], 200),
        ]);

        $result = $this->service->loginApi('PROV001', 'secret');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::NO_ENCONTRADO, $result['error_type']);
    }

    // ═══════════════════════════════════════════
    // Task 5.4: buscarPorCodigo unit tests
    // ═══════════════════════════════════════════

    public function test_buscar_por_codigo_success(): void
    {
        Http::fake([
            $this->baseUrl . '/ClienteProveedor/BuscarPorCodigo*' => Http::response([
                'IdDocumento' => 1,
                'CodigoCteProv' => 'PROV001',
                'Folio' => 'OC-2026-001',
            ], 200),
        ]);

        $result = $this->service->buscarPorCodigo('PROV001', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('OC-2026-001', $result['data']['Folio']);
    }

    public function test_buscar_por_codigo_404(): void
    {
        Http::fake([
            $this->baseUrl . '/ClienteProveedor/BuscarPorCodigo*' => Http::response([], 404),
        ]);

        $result = $this->service->buscarPorCodigo('NOEXISTE', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::NO_ENCONTRADO, $result['error_type']);
    }

    public function test_buscar_por_codigo_empty_body(): void
    {
        Http::fake([
            $this->baseUrl . '/ClienteProveedor/BuscarPorCodigo*' => Http::response([], 200),
        ]);

        $result = $this->service->buscarPorCodigo('PROV001', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::NO_ENCONTRADO, $result['error_type']);
    }

    public function test_buscar_por_codigo_401(): void
    {
        Http::fake([
            $this->baseUrl . '/ClienteProveedor/BuscarPorCodigo*' => Http::response([], 401),
        ]);

        $result = $this->service->buscarPorCodigo('PROV001', 'bad-token');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::AUTENTICACION_FALLIDA, $result['error_type']);
    }

    public function test_buscar_por_codigo_retries_on_500_then_succeeds(): void
    {
        Http::fakeSequence()
            ->push([], 500)
            ->push(['IdDocumento' => 1, 'Folio' => 'OC-001'], 200);

        config(['services.proveedor_api.max_retries' => 3]);
        $service = new ProveedorApiService();

        $result = $service->buscarPorCodigo('PROV001', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertTrue($result['success']);
    }

    public function test_buscar_por_codigo_all_retries_exhausted(): void
    {
        Http::fakeSequence()
            ->push([], 500)
            ->push([], 502)
            ->push([], 503);

        config(['services.proveedor_api.max_retries' => 3]);
        $service = new ProveedorApiService();

        $result = $service->buscarPorCodigo('PROV001', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::API_CAIDA, $result['error_type']);
    }

    // ═══════════════════════════════════════════
    // Task 6.4: listarPorCodigo unit tests
    // ═══════════════════════════════════════════

    public function test_listar_por_codigo_success(): void
    {
        Http::fake([
            $this->baseUrl . '/ClienteProveedor/ListarClienteProvedorPorCodigo*' => Http::response([
                ['IdDocumento' => 1, 'Folio' => 'OC-001'],
                ['IdDocumento' => 2, 'Folio' => 'OC-002'],
            ], 200),
        ]);

        $result = $this->service->listarPorCodigo('PROV001', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['data']);
    }

    public function test_listar_por_codigo_404(): void
    {
        Http::fake([
            $this->baseUrl . '/ClienteProveedor/ListarClienteProvedorPorCodigo*' => Http::response([], 404),
        ]);

        $result = $this->service->listarPorCodigo('NOEXISTE', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::NO_ENCONTRADO, $result['error_type']);
    }

    public function test_listar_por_codigo_empty_body(): void
    {
        Http::fake([
            $this->baseUrl . '/ClienteProveedor/ListarClienteProvedorPorCodigo*' => Http::response([], 200),
        ]);

        $result = $this->service->listarPorCodigo('PROV001', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::NO_ENCONTRADO, $result['error_type']);
    }

    public function test_listar_por_codigo_401(): void
    {
        Http::fake([
            $this->baseUrl . '/ClienteProveedor/ListarClienteProvedorPorCodigo*' => Http::response([], 401),
        ]);

        $result = $this->service->listarPorCodigo('PROV001', 'bad-token');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::AUTENTICACION_FALLIDA, $result['error_type']);
    }

    public function test_listar_por_codigo_retries_on_500(): void
    {
        Http::fakeSequence()
            ->push([], 503)
            ->push([['IdDocumento' => 1, 'Folio' => 'OC-001']], 200);

        config(['services.proveedor_api.max_retries' => 3]);
        $service = new ProveedorApiService();

        $result = $service->listarPorCodigo('PROV001', 'token-123');

        $this->assertStandardResponse($result);
        $this->assertTrue($result['success']);
    }

    // ═══════════════════════════════════════════
    // Task 8.1: Logging tests
    // ═══════════════════════════════════════════

    public function test_log_error_on_login_failure(): void
    {
        Log::shouldReceive('error')
            ->atLeast()->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'ProveedorAPI'));

        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([], 401),
        ]);

        $this->service->loginApi('PROV001', 'wrong');
    }

    public function test_log_warning_on_success_after_retry(): void
    {
        Log::shouldReceive('error')->atLeast()->once();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'éxito después de reintentos'));

        Http::fakeSequence()
            ->push([], 500)
            ->push(['IdDocumento' => 1, 'Folio' => 'OC-001'], 200);

        config(['services.proveedor_api.max_retries' => 3]);
        $service = new ProveedorApiService();

        $service->buscarPorCodigo('PROV001', 'token-123');
    }

    // ═══════════════════════════════════════════
    // Task 8.2: Login does NOT retry on 500
    // ═══════════════════════════════════════════

    public function test_login_does_not_retry_on_500(): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([], 500),
        ]);

        $result = $this->service->loginApi('PROV001', 'secret');

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::ERROR_SERVIDOR, $result['error_type']);

        // Only 1 request should have been made (no retry)
        Http::assertSentCount(1);
    }

    // ═══════════════════════════════════════════
    // Task 8.3: Default config values
    // ═══════════════════════════════════════════

    public function test_default_config_values(): void
    {
        // Clear all config
        config(['services.proveedor_api' => []]);

        $service = new ProveedorApiService();

        // URL vacía should trigger api_caida
        Http::fake();
        $result = $service->loginApi('test', 'test');

        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::API_CAIDA, $result['error_type']);
        Http::assertNothingSent();
    }

    // ═══════════════════════════════════════════
    // Property Tests
    // ═══════════════════════════════════════════

    // Property 2: Response structure invariant
    public static function responseInvariantProvider(): array
    {
        $cases = [];
        $statuses = [200, 201, 401, 404, 500, 502, 503, 504, 418, 422, 301];
        for ($i = 0; $i < 100; $i++) {
            $status = $statuses[array_rand($statuses)];
            $body = $status >= 200 && $status < 300 && rand(0, 1)
                ? ['key_' . $i => 'val_' . $i]
                : [];
            $cases["status_{$status}_iter_{$i}"] = [$status, $body];
        }
        return $cases;
    }

    /**
     * Feature: blindar-proveedor-api-service, Property 2: Invariante de estructura
     */
    #[DataProvider('responseInvariantProvider')]
    public function test_property_response_structure_invariant(int $status, array $body): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response($body, $status),
        ]);

        $result = $this->service->loginApi('test', 'test');
        $this->assertStandardResponse($result);
    }

    // Property 3: HTTP code to error type mapping
    public static function httpCodeMappingProvider(): array
    {
        $cases = [];
        // 2xx with data = success
        for ($i = 0; $i < 20; $i++) {
            $cases["2xx_with_data_{$i}"] = [rand(200, 299), ['data' => true], true, null];
        }
        // 2xx empty = no_encontrado
        for ($i = 0; $i < 20; $i++) {
            $cases["2xx_empty_{$i}"] = [rand(200, 299), [], false, ProveedorApiException::NO_ENCONTRADO];
        }
        // 401 = autenticacion_fallida
        for ($i = 0; $i < 20; $i++) {
            $cases["401_{$i}"] = [401, [], false, ProveedorApiException::AUTENTICACION_FALLIDA];
        }
        // 404 = no_encontrado
        for ($i = 0; $i < 20; $i++) {
            $cases["404_{$i}"] = [404, [], false, ProveedorApiException::NO_ENCONTRADO];
        }
        // 5xx = error_servidor
        $serverCodes = [500, 502, 503, 504];
        for ($i = 0; $i < 20; $i++) {
            $code = $serverCodes[array_rand($serverCodes)];
            $cases["5xx_{$code}_{$i}"] = [$code, [], false, ProveedorApiException::ERROR_SERVIDOR];
        }
        return $cases;
    }

    /**
     * Feature: blindar-proveedor-api-service, Property 3: Mapeo HTTP a error_type
     */
    #[DataProvider('httpCodeMappingProvider')]
    public function test_property_http_code_to_error_type(int $status, array $body, bool $expectedSuccess, ?string $expectedErrorType): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response($body, $status),
        ]);

        $result = $this->service->loginApi('test', 'test');

        $this->assertEquals($expectedSuccess, $result['success']);
        $this->assertEquals($expectedErrorType, $result['error_type']);
    }

    // Property 6: Empty URL fails without HTTP
    public static function emptyUrlProvider(): array
    {
        $cases = [];
        $emptyValues = ['', ' ', '  ', '   '];
        $methods = ['loginApi', 'buscarPorCodigo', 'listarPorCodigo'];

        for ($i = 0; $i < 100; $i++) {
            $url = $emptyValues[array_rand($emptyValues)];
            $method = $methods[array_rand($methods)];
            $cases["empty_url_{$method}_{$i}"] = [$url, $method];
        }
        return $cases;
    }

    /**
     * Feature: blindar-proveedor-api-service, Property 6: URL vacía falla sin HTTP
     */
    #[DataProvider('emptyUrlProvider')]
    public function test_property_empty_url_fails_without_http(string $url, string $method): void
    {
        config(['services.proveedor_api.url' => $url]);
        $service = new ProveedorApiService();

        Http::fake();

        if ($method === 'loginApi') {
            $result = $service->loginApi('test', 'test');
        } elseif ($method === 'buscarPorCodigo') {
            $result = $service->buscarPorCodigo('test', 'token');
        } else {
            $result = $service->listarPorCodigo('test', 'token');
        }

        $this->assertStandardResponse($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(ProveedorApiException::API_CAIDA, $result['error_type']);
        Http::assertNothingSent();
    }

    // Property 5: Authenticated requests include correct headers
    public function test_property_authenticated_requests_include_headers(): void
    {
        for ($i = 0; $i < 50; $i++) {
            Http::fake([
                $this->baseUrl . '/*' => Http::response(['data' => true], 200),
            ]);

            $token = 'token_' . bin2hex(random_bytes(16));
            $codigo = 'PROV' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $this->service->buscarPorCodigo($codigo, $token);

            Http::assertSent(function ($request) use ($token, $codigo) {
                return $request->hasHeader('Authorization', 'Bearer ' . $token)
                    && str_contains($request->url(), 'codigo=' . $codigo);
            });
        }
    }
}
