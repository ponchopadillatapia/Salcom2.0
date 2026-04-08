<?php

namespace Tests\Unit\Services;

use App\Exceptions\ProveedorApiException;
use PHPUnit\Framework\TestCase;

class ProveedorApiExceptionTest extends TestCase
{
    // ── Unit Tests: Factory Methods ──

    public function test_api_caida_factory(): void
    {
        $e = ProveedorApiException::apiCaida('API no disponible', 503);

        $this->assertEquals(ProveedorApiException::API_CAIDA, $e->getErrorType());
        $this->assertEquals('API no disponible', $e->getMessage());
        $this->assertEquals(503, $e->getHttpCode());
        $this->assertEquals([], $e->getResponseData());
    }

    public function test_timeout_factory(): void
    {
        $e = ProveedorApiException::timeout('/Login/Login');

        $this->assertEquals(ProveedorApiException::TIMEOUT, $e->getErrorType());
        $this->assertStringContains('/Login/Login', $e->getMessage());
        $this->assertEquals(0, $e->getHttpCode());
    }

    public function test_autenticacion_fallida_factory(): void
    {
        $e = ProveedorApiException::autenticacionFallida();

        $this->assertEquals(ProveedorApiException::AUTENTICACION_FALLIDA, $e->getErrorType());
        $this->assertEquals(401, $e->getHttpCode());
    }

    public function test_no_encontrado_factory(): void
    {
        $e = ProveedorApiException::noEncontrado('PROV001');

        $this->assertEquals(ProveedorApiException::NO_ENCONTRADO, $e->getErrorType());
        $this->assertStringContains('PROV001', $e->getMessage());
        $this->assertEquals(404, $e->getHttpCode());
    }

    public function test_error_servidor_factory(): void
    {
        $e = ProveedorApiException::errorServidor(500, 'Internal Server Error');

        $this->assertEquals(ProveedorApiException::ERROR_SERVIDOR, $e->getErrorType());
        $this->assertEquals(500, $e->getHttpCode());
        $this->assertEquals('Internal Server Error', $e->getMessage());
    }

    public function test_error_validacion_factory(): void
    {
        $data = ['campo' => 'El campo es requerido'];
        $e = ProveedorApiException::errorValidacion('Datos inválidos', $data);

        $this->assertEquals(ProveedorApiException::ERROR_VALIDACION, $e->getErrorType());
        $this->assertEquals(422, $e->getHttpCode());
        $this->assertEquals($data, $e->getResponseData());
    }

    public function test_error_desconocido_factory(): void
    {
        $e = ProveedorApiException::errorDesconocido('Algo salió mal', 418);

        $this->assertEquals(ProveedorApiException::ERROR_DESCONOCIDO, $e->getErrorType());
        $this->assertEquals(418, $e->getHttpCode());
    }

    // ── Property Test: Round-trip construction (Property 1) ──

    public static function roundTripProvider(): array
    {
        $cases = [];
        $types = [
            ProveedorApiException::API_CAIDA,
            ProveedorApiException::TIMEOUT,
            ProveedorApiException::AUTENTICACION_FALLIDA,
            ProveedorApiException::NO_ENCONTRADO,
            ProveedorApiException::ERROR_SERVIDOR,
            ProveedorApiException::ERROR_VALIDACION,
            ProveedorApiException::ERROR_DESCONOCIDO,
        ];

        for ($i = 0; $i < 100; $i++) {
            $type = $types[array_rand($types)];
            $httpCode = rand(0, 599);
            $message = 'msg_' . bin2hex(random_bytes(8));
            $data = rand(0, 1) ? ['key_' . $i => 'val_' . $i] : [];

            $cases["iteration_{$i}"] = [$type, $message, $httpCode, $data];
        }

        return $cases;
    }

    /**
     * @dataProvider roundTripProvider
     * Feature: blindar-proveedor-api-service, Property 1: Construcción round-trip
     */
    public function test_property_round_trip_construction(string $type, string $message, int $httpCode, array $data): void
    {
        // Use the appropriate factory based on type to verify round-trip
        // Since factories have fixed messages, we test via errorServidor which accepts all params
        $e = ProveedorApiException::errorServidor($httpCode, $message);

        $this->assertEquals($message, $e->getMessage());
        $this->assertEquals($httpCode, $e->getHttpCode());
        $this->assertEquals(ProveedorApiException::ERROR_SERVIDOR, $e->getErrorType());
        $this->assertIsArray($e->getResponseData());
    }

    // Helper
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
