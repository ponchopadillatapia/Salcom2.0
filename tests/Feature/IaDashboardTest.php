<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaDashboardTest extends TestCase
{
    private string $groqUrl = 'https://api.groq.com/openai/v1/chat/completions';

    private function fakeGroqSuccess(string $text): void
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

    public function test_pronostico_demanda_con_api_groq_exitosa(): void
    {
        $this->fakeGroqSuccess('Análisis de pronóstico generado por IA');
        config(['services.groq.api_key' => 'test-key']);

        $response = $this->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Análisis de pronóstico generado por IA');
        $response->assertSee('CLI-001');
    }

    public function test_pronostico_demanda_sin_api_key_muestra_error(): void
    {
        config(['services.groq.api_key' => '']);

        $response = $this->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
        $response->assertSee('API key de Groq no está configurada');
    }

    public function test_optimizacion_inventario_con_api_exitosa(): void
    {
        $this->fakeGroqSuccess('Recomendaciones de inventario generadas');
        config(['services.groq.api_key' => 'test-key']);

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
        $this->fakeGroqSuccess('Recomendación: Químicos del Norte es el mejor proveedor');
        config(['services.groq.api_key' => 'test-key']);

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

    public function test_api_groq_error_500_muestra_mensaje(): void
    {
        Http::fake([
            $this->groqUrl => Http::response([], 500),
        ]);
        config(['services.groq.api_key' => 'test-key']);

        $response = $this->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Error de la API de Groq');
    }
}
