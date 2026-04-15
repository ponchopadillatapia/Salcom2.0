<?php

namespace Tests\Unit\Services;

use App\Services\IaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class IaServiceTest extends TestCase
{
    private IaService $service;
    private string $groqUrl = 'https://api.groq.com/openai/v1/chat/completions';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.groq.url' => $this->groqUrl]);
        config(['services.groq.api_key' => 'test-api-key']);
        config(['services.groq.model' => 'llama-3.3-70b-versatile']);
        config(['services.groq.timeout' => 30]);
        $this->service = new IaService();
    }

    /**
     * Helper: respuesta exitosa de Groq (formato OpenAI).
     */
    private function fakeGroqSuccess(string $text = 'Respuesta de prueba'): void
    {
        Http::fake([
            $this->groqUrl => Http::response([
                'id'      => 'chatcmpl-test',
                'object'  => 'chat.completion',
                'choices' => [
                    ['index' => 0, 'message' => ['role' => 'assistant', 'content' => $text]],
                ],
            ], 200),
        ]);
    }

    // ══════════════════════════════════════════════
    // llamarGroq
    // ══════════════════════════════════════════════

    public function test_llamar_groq_exitoso(): void
    {
        $this->fakeGroqSuccess('Respuesta de prueba');

        $result = $this->service->llamarGroq('Hola');

        $this->assertTrue($result['success']);
        $this->assertEquals('Respuesta de prueba', $result['content']);
        $this->assertNull($result['error']);
    }

    public function test_llamar_groq_envia_headers_correctos(): void
    {
        $this->fakeGroqSuccess('OK');

        $this->service->llamarGroq('Test');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test-api-key')
                && $request->url() === $this->groqUrl
                && $request['model'] === 'llama-3.3-70b-versatile'
                && $request['messages'][0]['role'] === 'user'
                && $request['messages'][0]['content'] === 'Test';
        });
    }

    public function test_llamar_groq_sin_api_key(): void
    {
        config(['services.groq.api_key' => '']);
        $service = new IaService();

        Http::fake();

        $result = $service->llamarGroq('Test');

        $this->assertFalse($result['success']);
        $this->assertNull($result['content']);
        $this->assertStringContains('no está configurada', $result['error']);
        Http::assertNothingSent();
    }

    public function test_llamar_groq_error_http(): void
    {
        Http::fake([
            $this->groqUrl => Http::response([
                'error' => ['message' => 'Rate limit exceeded', 'type' => 'rate_limit_error'],
            ], 429),
        ]);

        $result = $this->service->llamarGroq('Test');

        $this->assertFalse($result['success']);
        $this->assertNull($result['content']);
        $this->assertStringContains('Rate limit exceeded', $result['error']);
    }

    public function test_llamar_groq_error_sin_mensaje_detallado(): void
    {
        Http::fake([
            $this->groqUrl => Http::response([], 500),
        ]);

        $result = $this->service->llamarGroq('Test');

        $this->assertFalse($result['success']);
        $this->assertStringContains('HTTP 500', $result['error']);
    }

    public function test_llamar_groq_log_en_error(): void
    {
        Log::shouldReceive('error')
            ->atLeast()->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'IaService'));

        Http::fake([
            $this->groqUrl => Http::response([], 500),
        ]);

        $this->service->llamarGroq('Test');
    }

    // ══════════════════════════════════════════════
    // pronosticoDemanda
    // ══════════════════════════════════════════════

    public function test_pronostico_demanda_estructura(): void
    {
        $this->fakeGroqSuccess('Pronóstico generado');

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
        $this->fakeGroqSuccess('OK');

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
        $this->fakeGroqSuccess('Optimización generada');

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
        $this->fakeGroqSuccess('Proveedor recomendado');

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
        $this->fakeGroqSuccess('OK');

        $result = $this->service->seleccionProveedor('NO-EXISTE');

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
