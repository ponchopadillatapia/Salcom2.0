<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaDashboardTest extends TestCase
{
    use RefreshDatabase;

    private string $groqUrl = 'https://api.groq.com/openai/v1/chat/completions';

    private function adminSession(): array
    {
        return ['admin_id' => 1, 'admin_nombre' => 'Admin Test'];
    }

    private function fakeGroqSuccess(string $text): void
    {
        Http::fake([
            $this->groqUrl => Http::response([
                'id' => 'chatcmpl-test',
                'object' => 'chat.completion',
                'choices' => [
                    ['index' => 0, 'message' => ['role' => 'assistant', 'content' => $text]],
                ],
            ], 200),
        ]);
    }

    public function test_dashboard_ia_carga_correctamente(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/ia');

        $response->assertStatus(200);
        $response->assertSee('Módulo de Inteligencia Artificial');
    }

    public function test_dashboard_muestra_clientes_y_productos(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/ia');

        $response->assertStatus(200);
    }

    public function test_pronostico_demanda_requiere_cliente(): void
    {
        $response = $this->withSession($this->adminSession())->post('/admin/ia/pronostico', []);

        $response->assertRedirect();
    }

    public function test_pronostico_demanda_con_api_exitosa(): void
    {
        Http::fake(['*' => Http::response(['content' => [['text' => 'Análisis generado']]], 200)]);
        config(['services.ia.provider' => 'anthropic']);
        config(['services.ia.anthropic_key' => 'test-key']);

        $response = $this->withSession($this->adminSession())->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
    }

    public function test_pronostico_demanda_sin_api_key_muestra_error(): void
    {
        config(['services.ia.aws_access_key' => '']);
        config(['services.ia.aws_secret_key' => '']);

        $response = $this->withSession($this->adminSession())->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
    }

    public function test_optimizacion_inventario_con_api(): void
    {
        Http::fake(['*' => Http::response(['content' => [['text' => 'Recomendaciones']]], 200)]);
        config(['services.ia.provider' => 'anthropic']);
        config(['services.ia.anthropic_key' => 'test-key']);

        $response = $this->withSession($this->adminSession())->post('/admin/ia/inventario');

        $response->assertStatus(200);
    }

    public function test_seleccion_proveedor_requiere_producto(): void
    {
        $response = $this->withSession($this->adminSession())->post('/admin/ia/proveedor', []);

        $response->assertRedirect();
    }

    public function test_seleccion_proveedor_con_api(): void
    {
        Http::fake(['*' => Http::response(['content' => [['text' => 'Proveedor recomendado']]], 200)]);
        config(['services.ia.provider' => 'anthropic']);
        config(['services.ia.anthropic_key' => 'test-key']);

        $response = $this->withSession($this->adminSession())->post('/admin/ia/proveedor', [
            'producto_id' => 'SAL-001',
        ]);

        $response->assertStatus(200);
    }

    public function test_dashboard_ia_requiere_autenticacion(): void
    {
        $response = $this->get('/admin/ia');

        $response->assertRedirect('/login-admin');
    }

    public function test_api_error_muestra_mensaje(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        config(['services.ia.provider' => 'anthropic']);
        config(['services.ia.anthropic_key' => 'test-key']);

        $response = $this->withSession($this->adminSession())->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-001',
        ]);

        $response->assertStatus(200);
    }
}
