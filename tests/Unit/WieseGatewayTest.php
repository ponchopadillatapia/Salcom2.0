<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WieseGateway\Gateway;

require_once dirname(__DIR__, 2).'/wiese-gateway/src/Gateway.php';

class WieseGatewayTest extends TestCase
{
    private string $token = 'token-de-prueba-siteground';

    private function puerta(?callable $forwarder = null): Gateway
    {
        return new Gateway(
            'http://172.16.1.250:7183/api',
            $this->token,
            ['35.215.67.229'],
            '/api',
            60,
            $forwarder,
        );
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    private function headers(array $extra = []): array
    {
        return array_merge([
            'X-Salcom-Gateway' => $this->token,
        ], $extra);
    }

    public function test_rechaza_ip_que_no_es_siteground(): void
    {
        $llamadas = 0;
        $puerta = $this->puerta(function () use (&$llamadas) {
            $llamadas++;

            return ['status' => 200, 'body' => '{}', 'content_type' => 'application/json'];
        });

        $respuesta = $puerta->handle('GET', '/api/Login/Login', $this->headers(), '', '8.8.8.8');

        $this->assertSame(403, $respuesta['status']);
        $this->assertSame(0, $llamadas);
    }

    public function test_rechaza_token_incorrecto(): void
    {
        $llamadas = 0;
        $puerta = $this->puerta(function () use (&$llamadas) {
            $llamadas++;

            return ['status' => 200, 'body' => '{}', 'content_type' => 'application/json'];
        });

        $respuesta = $puerta->handle(
            'GET',
            '/api/ClienteProveedor/BuscarPorCodigo?codigo=1',
            ['X-Salcom-Gateway' => 'otro-token'],
            '',
            '35.215.67.229',
        );

        $this->assertSame(401, $respuesta['status']);
        $this->assertSame(0, $llamadas);
    }

    public function test_rechaza_ruta_que_no_usa_salcom(): void
    {
        $llamadas = 0;
        $puerta = $this->puerta(function () use (&$llamadas) {
            $llamadas++;

            return ['status' => 200, 'body' => '{}', 'content_type' => 'application/json'];
        });

        $respuesta = $puerta->handle('GET', '/api/Documento/BorrarTodo', $this->headers(), '', '35.215.67.229');

        $this->assertSame(404, $respuesta['status']);
        $this->assertSame(0, $llamadas);
    }

    public function test_reenvia_solo_al_puerto_interno(): void
    {
        $urlVista = null;
        $puerta = $this->puerta(function (string $method, string $url) use (&$urlVista) {
            $urlVista = $url;

            return ['status' => 200, 'body' => '{"ok":true}', 'content_type' => 'application/json'];
        });

        $respuesta = $puerta->handle(
            'GET',
            '/api/DatoDocumentoProveedor/ListarDocumentosRFC?RFC=XAXX010101000&fechaInicial=2020-01-01T00:00:00',
            $this->headers(['Authorization' => 'Bearer wiese-token']),
            '',
            '::ffff:35.215.67.229',
        );

        $this->assertSame(200, $respuesta['status']);
        $this->assertSame(
            'http://172.16.1.250:7183/api/DatoDocumentoProveedor/ListarDocumentosRFC?RFC=XAXX010101000&fechaInicial=2020-01-01T00:00:00',
            $urlVista,
        );
    }

    public function test_sin_token_configurado_no_abre(): void
    {
        $puerta = new Gateway('http://172.16.1.250:7183/api', '', ['35.215.67.229']);

        $respuesta = $puerta->handle('GET', '/api/Login/Login', [], '', '35.215.67.229');

        $this->assertSame(500, $respuesta['status']);
    }
}
