<?php

namespace Tests\Unit\Services;

use App\Services\IaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class IaServiceTest extends TestCase
{
    private IaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.claude.api_key' => 'test-api-key']);
        config(['services.claude.model' => 'claude-sonnet-4-20250514']);
        config(['services.claude.timeout' => 30]);
        $this->service = new IaService();
    }

    // ══════════════════════════════════════════════
    // llamarClaude
    // ══════════════════════════════════════════════

    public function test_llamar_claude_exitoso(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Respuesta de prueba']],
            ], 200),
        ]);

        $result = $this->service->llamarClaude('Hola');

        $this->assertTrue($result['success']);
        $this->assertEquals('Respuesta de prueba', $result['content']);
        $this->assertNull($result['error']);
    }

    public function test_llamar_claude_envia_headers_correctos(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'OK']],
            ], 200),
        ]);

        $this->service->llamarClaude('Test');

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-key', 'test-api-key')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request['model'] === 'claude-sonnet-4-20250514'
                && $request['messages'][0]['content'] === 'Test';
        });
    }

    public function test_llamar_claude_sin_api_key(): void
    {
        config(['services.claude.api_key' => '']);
        $service = new IaService();

        Http::fake();

        $result = $service->llamarClaude('Test');

        $this->assertFalse($result['success']);
        $this->assertNull($result['content']);
        $this->assertStringContains('no está configurada', $result['error']);
        Http::assertNothingSent();
    }

    public function test_llamar_claude_error_http(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'type' => 'error',
                'error' => ['type' => 'rate_limit_error', 'message' => 'Rate limited'],
            ], 429),
        ]);

        $result = $this->service->llamarClaude('Test');

        $this->assertFalse($result['success']);
        $this->assertNull($result['content']);
        $this->assertStringContains('Rate limited', $result['error']);
    }

    public function test_llamar_claude_log_en_error(): void
    {
        Log::shouldReceive('error')
            ->atLeast()->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'IaService'));

        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([], 500),
        ]);

        $this->service->llamarClaude('Test');
    }

    // ══════════════════════════════════════════════
    // pronosticoDemanda
    // ══════════════════════════════════════════════

    public function test_pronostico_demanda_estructura(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Pronóstico generado']],
            ], 200),
        ]);

        $result = $this->service->pronosticoDemanda('CLI-001');

        $this->assertArrayHasKey('cliente', $result);
        $this->assertArrayHasKey('historial', $result);
        $this->assertArrayHasKey('analisis', $result);
        $this->assertArrayHasKey('generado', $result);
        $this->assertEquals('CLI-001', $result['cliente']);
        $this->assertNotEmpty($result['historial']);
        $this->assertTrue($result['analisis']['success']);
    }

    public function test_pronostico_demanda_envia_historial_en_prompt(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'OK']],
            ], 200),
        ]);

        $this->service->pronosticoDemanda('CLI-002');

        Http::assertSent(function ($request) {
            $prompt = $request['messages'][0]['content'];
            return str_contains($prompt, 'CLI-002')
                && str_contains($prompt, 'historial de pedidos')
                && str_contains($prompt, 'pronóstico');
        });
    }

    // ══════════════════════════════════════════════
    // optimizacionInventario
    // ══════════════════════════════════════════════

    public function test_optimizacion_inventario_estructura(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Optimización generada']],
            ], 200),
        ]);

        $result = $this->service->optimizacionInventario();

        $this->assertArrayHasKey('inventario', $result);
        $this->assertArrayHasKey('demanda', $result);
        $this->assertArrayHasKey('analisis', $result);
        $this->assertNotEmpty($result['inventario']);
        $this->assertNotEmpty($result['demanda']);
        $this->assertTrue($result['analisis']['success']);
    }

    // ══════════════════════════════════════════════
    // seleccionProveedor
    // ══════════════════════════════════════════════

    public function test_seleccion_proveedor_estructura(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Proveedor recomendado']],
            ], 200),
        ]);

        $result = $this->service->seleccionProveedor('SAL-001');

        $this->assertArrayHasKey('producto', $result);
        $this->assertArrayHasKey('proveedores', $result);
        $this->assertArrayHasKey('analisis', $result);
        $this->assertEquals('SAL-001', $result['producto']['sku']);
        $this->assertNotEmpty($result['proveedores']);
        $this->assertTrue($result['analisis']['success']);
    }

    public function test_seleccion_proveedor_producto_no_existente_usa_default(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'OK']],
            ], 200),
        ]);

        $result = $this->service->seleccionProveedor('NO-EXISTE');

        // Debe usar SAL-001 como default
        $this->assertEquals('SAL-001', $result['producto']['sku']);
    }

    // ══════════════════════════════════════════════
    // Datos mockeados
    // ══════════════════════════════════════════════

    public function test_historial_pedidos_tiene_datos(): void
    {
        $historial = $this->service->obtenerHistorialPedidos('CLI-001');

        $this->assertNotEmpty($historial);
        $this->assertArrayHasKey('pedido', $historial[0]);
        $this->assertArrayHasKey('fecha', $historial[0]);
        $this->assertArrayHasKey('productos', $historial[0]);
        $this->assertArrayHasKey('total', $historial[0]);
    }

    public function test_inventario_actual_tiene_datos(): void
    {
        $inventario = $this->service->obtenerInventarioActual();

        $this->assertNotEmpty($inventario);
        $this->assertArrayHasKey('sku', $inventario[0]);
        $this->assertArrayHasKey('stock_actual', $inventario[0]);
    }

    public function test_demanda_proyectada_tiene_datos(): void
    {
        $demanda = $this->service->obtenerDemandaProyectada();

        $this->assertNotEmpty($demanda);
        $this->assertArrayHasKey('demanda_mensual', $demanda[0]);
        $this->assertArrayHasKey('tendencia', $demanda[0]);
    }

    public function test_proveedores_producto_tiene_datos(): void
    {
        $proveedores = $this->service->obtenerProveedoresProducto('SAL-001');

        $this->assertNotEmpty($proveedores);
        $this->assertArrayHasKey('codigo', $proveedores[0]);
        $this->assertArrayHasKey('precio_unitario', $proveedores[0]);
        $this->assertArrayHasKey('tiempo_entrega_dias', $proveedores[0]);
        $this->assertArrayHasKey('calificacion', $proveedores[0]);
    }

    public function test_listar_clientes(): void
    {
        $clientes = $this->service->listarClientes();

        $this->assertNotEmpty($clientes);
        $this->assertArrayHasKey('codigo', $clientes[0]);
        $this->assertArrayHasKey('nombre', $clientes[0]);
    }

    public function test_listar_productos(): void
    {
        $productos = $this->service->listarProductos();

        $this->assertNotEmpty($productos);
        $this->assertArrayHasKey('sku', $productos[0]);
        $this->assertArrayHasKey('nombre', $productos[0]);
    }

    // ── Helper ──
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Se esperaba que '{$haystack}' contuviera '{$needle}'"
        );
    }
}
