<?php

namespace Tests\Unit\Services;

use App\Services\IaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaServiceTest extends TestCase
{
    use RefreshDatabase;

    private IaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.ia.provider' => 'anthropic']);
        config(['services.ia.anthropic_key' => 'test-api-key']);
        config(['services.ia.model' => 'claude-3-5-sonnet']);
        config(['services.ia.timeout' => 30]);
        config(['services.ia.aws_access_key' => 'test-key']);
        config(['services.ia.aws_secret_key' => 'test-secret']);
        $this->service = new IaService;
    }

    /**
     * Helper: respuesta exitosa de Anthropic.
     */
    private function fakeClaudeSuccess(string $text = 'Respuesta de prueba'): void
    {
        Http::fake([
            '*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ], 200),
        ]);
    }

    // ══════════════════════════════════════════════
    // llamarClaude
    // ══════════════════════════════════════════════

    public function test_llamar_claude_exitoso(): void
    {
        $this->fakeClaudeSuccess('Respuesta de prueba');

        $result = $this->service->llamarClaude('Hola');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['content']);
    }

    public function test_llamar_claude_sin_api_key_retorna_error(): void
    {
        config(['services.ia.aws_access_key' => '']);
        config(['services.ia.aws_secret_key' => '']);
        config(['services.ia.provider' => 'bedrock']);
        $service = new IaService;

        $result = $service->llamarClaude('Test');

        $this->assertFalse($result['success']);
        $this->assertNull($result['content']);
        $this->assertNotEmpty($result['error']);
    }

    public function test_llamar_claude_error_http(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Rate limit']], 429),
        ]);

        $result = $this->service->llamarClaude('Test');

        $this->assertFalse($result['success']);
        $this->assertNull($result['content']);
    }

    public function test_llamar_claude_log_en_error(): void
    {
        // Cuando la API falla, el servicio debe manejar el error gracefully
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $result = $this->service->llamarClaude('Test');

        $this->assertFalse($result['success']);
    }

    // ══════════════════════════════════════════════
    // pronosticoDemanda
    // ══════════════════════════════════════════════

    public function test_pronostico_demanda_estructura(): void
    {
        $this->fakeClaudeSuccess('Pronóstico generado');

        $result = $this->service->pronosticoDemanda('CLI-001');

        $this->assertArrayHasKey('cliente', $result);
        $this->assertArrayHasKey('historial', $result);
        $this->assertArrayHasKey('analisis', $result);
        $this->assertArrayHasKey('generado', $result);
        $this->assertEquals('CLI-001', $result['cliente']);
    }

    // ══════════════════════════════════════════════
    // optimizacionInventario
    // ══════════════════════════════════════════════

    public function test_optimizacion_inventario_estructura(): void
    {
        $this->fakeClaudeSuccess('Optimización generada');

        $result = $this->service->optimizacionInventario();

        $this->assertArrayHasKey('inventario', $result);
        $this->assertArrayHasKey('demanda', $result);
        $this->assertArrayHasKey('analisis', $result);
    }

    // ══════════════════════════════════════════════
    // seleccionProveedor
    // ══════════════════════════════════════════════

    public function test_seleccion_proveedor_estructura(): void
    {
        $this->fakeClaudeSuccess('Proveedor recomendado');

        $result = $this->service->seleccionProveedor('SAL-001');

        $this->assertArrayHasKey('producto', $result);
        $this->assertArrayHasKey('proveedores', $result);
        $this->assertArrayHasKey('analisis', $result);
    }

    public function test_seleccion_proveedor_producto_no_existente(): void
    {
        $this->fakeClaudeSuccess('OK');

        $result = $this->service->seleccionProveedor('NO-EXISTE');

        // Debe usar un producto default o manejar el caso
        $this->assertArrayHasKey('producto', $result);
    }

    // ══════════════════════════════════════════════
    // Datos de BD
    // ══════════════════════════════════════════════

    public function test_historial_pedidos_retorna_array(): void
    {
        $historial = $this->service->obtenerHistorialPedidos('CLI-001');

        $this->assertIsArray($historial);
    }

    public function test_inventario_actual_retorna_array(): void
    {
        $inventario = $this->service->obtenerInventarioActual();

        $this->assertIsArray($inventario);
    }

    public function test_demanda_proyectada_retorna_array(): void
    {
        $demanda = $this->service->obtenerDemandaProyectada();

        $this->assertIsArray($demanda);
    }

    public function test_proveedores_producto_retorna_array(): void
    {
        $proveedores = $this->service->obtenerProveedoresProducto('SAL-001');

        $this->assertIsArray($proveedores);
    }

    public function test_listar_clientes_retorna_array(): void
    {
        $clientes = $this->service->listarClientes();

        $this->assertIsArray($clientes);
    }

    public function test_listar_productos_retorna_array(): void
    {
        $productos = $this->service->listarProductos();

        $this->assertIsArray($productos);
    }
}
