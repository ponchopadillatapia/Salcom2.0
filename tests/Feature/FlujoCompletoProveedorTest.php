<?php

namespace Tests\Feature;

use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlujoCompletoProveedorTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'http://fake-api.test/api';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.proveedor_api.url' => $this->baseUrl]);
        config(['services.proveedor_api.login_mode' => 'fallback']);
        config(['services.proveedor_api.max_retries' => 1]);
    }

    private function crearProveedor(): ProveedorUser
    {
        return ProveedorUser::create([
            'usuario'        => 'PROV001',
            'password'       => Hash::make('secret123'),
            'nombre'         => 'Proveedor Test SA',
            'codigo_compras' => 'COD001',
            'correo'         => 'prov@test.com',
            'tipo_persona'   => 'Moral',
            'telefono'       => '5551234567',
        ]);
    }

    // ═══════════════════════════════════════════
    // Flujo completo: Login fallback → Portal → Dashboard → OC
    // ═══════════════════════════════════════════

    public function test_flujo_completo_fallback_login_portal_dashboard_oc(): void
    {
        $this->crearProveedor();

        // API caída → fallback a BD local
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([], 500),
        ]);

        // 1. Login con fallback
        $response = $this->post('/login-proveedor', [
            'codigo' => 'PROV001',
            'pwd'    => 'secret123',
        ]);

        $response->assertRedirect('/portal-proveedor');
        $this->assertEquals('local', session('proveedor_login_source'));
        $this->assertNull(session('proveedor_token'));
        $this->assertEquals('Proveedor Test SA', session('proveedor_nombre'));
        $this->assertEquals('COD001', session('proveedor_codigo'));

        // 2. Portal — debe cargar con sesión activa
        $response = $this->get('/portal-proveedor');
        $response->assertStatus(200);
        $response->assertSee('Proveedor Test SA');
        $response->assertSee('Consultar OC');
        $response->assertSee('Dashboard');

        // 3. Dashboard — debe cargar con datos de sesión
        $response = $this->get('/dashboard-proveedor');
        $response->assertStatus(200);
        $response->assertSee('Proveedor Test SA');
        $response->assertSee('COD001');
        $response->assertSee('Facturas');
        $response->assertSee('Pagos');

        // 4. Consultar OC — debe cargar con datos mockeados
        $response = $this->get('/consultar-oc');
        $response->assertStatus(200);
        $response->assertSee('Consultar');
        $response->assertSee('#10045');
    }

    public function test_flujo_completo_api_login_portal_dashboard_oc(): void
    {
        config(['services.proveedor_api.login_mode' => 'api']);

        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([
                'usuario'     => 'PROV-API-001',
                'tokencreado' => 'jwt-real-token',
            ], 200),
        ]);

        // 1. Login por API
        $response = $this->post('/login-proveedor', [
            'codigo' => 'PROV001',
            'pwd'    => 'secret',
        ]);

        $response->assertRedirect('/portal-proveedor');
        $this->assertEquals('api', session('proveedor_login_source'));
        $this->assertEquals('jwt-real-token', session('proveedor_token'));

        // 2. Portal
        $response = $this->get('/portal-proveedor');
        $response->assertStatus(200);

        // 3. Dashboard
        $response = $this->get('/dashboard-proveedor');
        $response->assertStatus(200);

        // 4. Consultar OC
        $response = $this->get('/consultar-oc');
        $response->assertStatus(200);
    }

    // ═══════════════════════════════════════════
    // Middleware: rutas protegidas sin sesión
    // ═══════════════════════════════════════════

    public function test_rutas_protegidas_redirigen_a_login_sin_sesion(): void
    {
        $rutas = [
            '/portal-proveedor',
            '/dashboard-proveedor',
            '/consultar-oc',
            '/onboarding',
            '/business',
            '/alta-producto',
        ];

        foreach ($rutas as $ruta) {
            $response = $this->get($ruta);
            $response->assertRedirect('/login-proveedor');
        }
    }

    // ═══════════════════════════════════════════
    // Logout limpia sesión y redirige
    // ═══════════════════════════════════════════

    public function test_logout_limpia_sesion_y_redirige(): void
    {
        $this->crearProveedor();
        Http::fake([$this->baseUrl . '/Login/Login' => Http::response([], 500)]);

        // Login
        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret123']);
        $this->assertNotNull(session('proveedor_id'));

        // Logout
        $response = $this->post('/logout-proveedor');
        $response->assertRedirect('/login-proveedor');
        $this->assertNull(session('proveedor_id'));
        $this->assertNull(session('proveedor_token'));
        $this->assertNull(session('proveedor_login_source'));

        // Intentar acceder a portal después de logout
        $response = $this->get('/portal-proveedor');
        $response->assertRedirect('/login-proveedor');
    }

    // ═══════════════════════════════════════════
    // Login con credenciales incorrectas
    // ═══════════════════════════════════════════

    public function test_login_credenciales_incorrectas_no_accede_portal(): void
    {
        $this->crearProveedor();
        config(['services.proveedor_api.login_mode' => 'local']);

        $response = $this->post('/login-proveedor', [
            'codigo' => 'PROV001',
            'pwd'    => 'wrong_password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull(session('proveedor_id'));

        // No puede acceder al portal
        $response = $this->get('/portal-proveedor');
        $response->assertRedirect('/login-proveedor');
    }

    // ═══════════════════════════════════════════
    // Navbar muestra nombre del proveedor
    // ═══════════════════════════════════════════

    public function test_navbar_muestra_nombre_proveedor_en_todas_las_vistas(): void
    {
        $this->crearProveedor();
        config(['services.proveedor_api.login_mode' => 'local']);

        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret123']);

        $vistas = ['/portal-proveedor', '/dashboard-proveedor', '/consultar-oc'];

        foreach ($vistas as $vista) {
            $response = $this->get($vista);
            $response->assertStatus(200);
            $response->assertSee('Proveedor Test SA');
        }
    }
}
