<?php

namespace Tests\Feature;

use App\Services\IaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaDashboardTest extends TestCase
{
    public function test_dashboard_ia_carga_correctamente(): void
    {
        $response = $this->get('/admin/ia');

        $response->assertStatus(200);
        $response->assertSee('Módulo de Inteligencia Artificial');
        $response->assertSee('Pronóstico de demanda');
        $response->assertSee('Optimización de inventario');
        $response->assertSee('Selección de proveedor');
    }

    public function test_dashboard_muestra_clientes_y_productos(): void
    {
        $response = $this->get('/admin/ia');

        $response->assertStatus(200);
        $response->assertSee('CLI-001');
        $response->assertSee('Manufacturas del Pacífico');
        $response->assertSee('SAL-001');
        $response->assertSee('Resina epóxica industrial');
    }

    public function test_pronostico_demanda_requiere_cliente(): void
    {
        $response = $this->post('/admin/ia/pronostico', []);

        $response->assertSessionHasErrors('codigo_cliente');
    }

    public function test_pronostico_demanda_con_api_claude_exitosa(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Análisis de pronóstico generado por IA']],
            ], 200),
        ]);

        config(['services.claude.api_key' => 'test-key']);

        $response = $this->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Análisis de pronóstico generado por IA');
        $response->assertSee('CLI-001');
    }

    public function test_pronostico_demanda_sin_api_key_muestra_error(): void
    {
        config(['services.claude.api_key' => '']);

        $response = $this->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
        $response->assertSee('API key de Claude no está configurada');
    }

    public function test_optimizacion_inventario_con_api_exitosa(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Recomendaciones de inventario generadas']],
            ], 200),
        ]);

        config(['services.claude.api_key' => 'test-key']);

        $response = $this->post('/admin/ia/inventario');

        $response->assertStatus(200);
        $response->assertSee('Recomendaciones de inventario generadas');
    }

    public function test_seleccion_proveedor_requiere_producto(): void
    {
        $response = $this->post('/admin/ia/proveedor', []);

        $response->assertSessionHasErrors('producto_id');
    }

    public function test_seleccion_proveedor_con_api_exitosa(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Recomendación: Químicos del Norte es el mejor proveedor']],
            ], 200),
        ]);

        config(['services.claude.api_key' => 'test-key']);

        $response = $this->post('/admin/ia/proveedor', [
            'producto_id' => 'SAL-001',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Químicos del Norte es el mejor proveedor');
        $response->assertSee('Resina epóxica industrial');
    }

    public function test_tabla_inventario_muestra_datos_mockeados(): void
    {
        $response = $this->get('/admin/ia');

        $response->assertStatus(200);
        $response->assertSee('SAL-001');
        $response->assertSee('Catalizador rápido');
    }

    public function test_api_claude_error_500_muestra_mensaje(): void
    {
        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([], 500),
        ]);

        config(['services.claude.api_key' => 'test-key']);

        $response = $this->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Error de la API de Claude');
    }
}
