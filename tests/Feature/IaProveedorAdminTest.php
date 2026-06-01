<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Producto;
use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaProveedorAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): array
    {
        $admin = AdminUser::create([
            'nombre' => 'Admin IA',
            'correo' => 'admin-ia@test.com',
            'usuario' => 'admin.ia',
            'password' => Hash::make('secret'),
            'activo' => true,
            'rol' => 'admin',
        ]);

        return [
            'admin_id' => $admin->id,
            'admin_nombre' => $admin->nombre,
            'admin_correo' => $admin->correo,
            'admin_usuario' => $admin->usuario,
        ];
    }

    private function proveedorSession(): array
    {
        $proveedor = ProveedorUser::create([
            'usuario' => 'PROV-IA',
            'password' => Hash::make('secret'),
            'nombre' => 'Proveedor IA Test',
            'correo' => 'prov-ia@test.com',
            'codigo_compras' => 'COD-IA-001',
        ]);

        return [
            'proveedor_id' => $proveedor->id,
            'proveedor_nombre' => $proveedor->nombre,
            'proveedor_codigo' => $proveedor->codigo_compras,
            'proveedor_correo' => $proveedor->correo,
        ];
    }

    private function fakeAnthropicSuccess(string $text): void
    {
        config(['services.ia.provider' => 'anthropic']);
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake([
            '*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ], 200),
        ]);
    }

    // ── Admin ──

    public function test_admin_ia_dashboard_carga(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/ia');

        $response->assertStatus(200);
        $response->assertSee('Módulo de Inteligencia Artificial');
        $response->assertSee('Pronóstico de demanda');
        $response->assertSee('Optimización de inventario');
    }

    public function test_admin_ia_pronostico_muestra_respuesta_ia(): void
    {
        $this->fakeAnthropicSuccess('Pronóstico generado para el cliente.');

        $response = $this->withSession($this->adminSession())->post('/admin/ia/pronostico', [
            'codigo_cliente' => 'CLI-TEST',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Pronóstico generado para el cliente.');
    }

    public function test_admin_ia_inventario_muestra_respuesta_ia(): void
    {
        Producto::create([
            'codigo' => 'SKU-IA-1',
            'nombre' => 'Resina test',
            'precio' => 100,
            'stock' => 3,
            'unidad_venta' => 'KG',
            'activo' => true,
        ]);

        $this->fakeAnthropicSuccess('Reordenar resina en 5 días.');

        $response = $this->withSession($this->adminSession())->post('/admin/ia/inventario');

        $response->assertStatus(200);
        $response->assertSee('Reordenar resina en 5 días.');
    }

    public function test_admin_ia_sin_credenciales_muestra_error(): void
    {
        config(['services.ia.provider' => 'bedrock']);
        config(['services.ia.aws_access_key' => '']);
        config(['services.ia.aws_secret_key' => '']);

        $response = $this->withSession($this->adminSession())->post('/admin/ia/inventario');

        $response->assertStatus(200);
        $response->assertSee('Credenciales de AWS no configuradas');
    }

    // ── Proveedor ──

    public function test_proveedor_ia_dashboard_carga(): void
    {
        $response = $this->withSession($this->proveedorSession())->get('/proveedor/ia');

        $response->assertStatus(200);
        $response->assertSee('Dashboard de Inteligencia Artificial');
        $response->assertSee('Generar pronóstico');
        $response->assertSee('Optimizar inventario');
    }

    public function test_proveedor_ia_requiere_sesion(): void
    {
        $this->get('/proveedor/ia')->assertRedirect('/login-proveedor');
    }

    public function test_proveedor_ia_pronostico_usa_codigo_sesion(): void
    {
        $this->fakeAnthropicSuccess('Demanda estable para COD-IA-001.');

        $response = $this->withSession($this->proveedorSession())->post('/proveedor/ia/pronostico');

        $response->assertStatus(200);
        $response->assertSee('Demanda estable para COD-IA-001.');
        $response->assertSee('COD-IA-001');
    }

    public function test_proveedor_ia_inventario_sin_credenciales_muestra_error(): void
    {
        config(['services.ia.provider' => 'bedrock']);
        config(['services.ia.aws_access_key' => '']);
        config(['services.ia.aws_secret_key' => '']);

        $response = $this->withSession($this->proveedorSession())->post('/proveedor/ia/inventario');

        $response->assertStatus(200);
        $response->assertSee('Credenciales de AWS no configuradas');
    }

    public function test_proveedor_ia_inventario_con_api_muestra_respuesta(): void
    {
        $this->fakeAnthropicSuccess('Revisar stock de pigmentos esta semana.');

        $response = $this->withSession($this->proveedorSession())->post('/proveedor/ia/inventario');

        $response->assertStatus(200);
        $response->assertSee('Revisar stock de pigmentos esta semana.');
    }
}
