<?php

namespace Tests\Feature;

use App\Models\ClienteUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FlujoCompletoClienteTest extends TestCase
{
    use RefreshDatabase;

    private function crearCliente(bool $activo = true): ClienteUser
    {
        return ClienteUser::create([
            'usuario'        => 'CLI001',
            'password'       => Hash::make('secret123'),
            'nombre'         => 'Cliente Test SA',
            'codigo_cliente' => 'CLI-TEST-001',
            'correo'         => 'cli@test.com',
            'tipo_persona'   => 'Moral',
            'tipo_cliente'   => 'mayorista',
            'telefono'       => '8112345678',
            'activo'         => $activo,
        ]);
    }

    public function test_login_view_returns_200(): void
    {
        $response = $this->get('/login-cliente');
        $response->assertStatus(200);
        $response->assertSee('Portal de Clientes');
    }

    public function test_login_exitoso(): void
    {
        $this->crearCliente();
        $response = $this->post('/login-cliente', ['usuario' => 'CLI001', 'password' => 'secret123']);
        $response->assertRedirect('/portal-cliente');
        $this->assertEquals('Cliente Test SA', session('cliente_nombre'));
        $this->assertEquals('CLI-TEST-001', session('cliente_codigo'));
        $this->assertEquals('mayorista', session('cliente_tipo'));
    }

    public function test_credenciales_invalidas(): void
    {
        $this->crearCliente();
        $response = $this->post('/login-cliente', ['usuario' => 'CLI001', 'password' => 'wrong']);
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Credenciales incorrectas');
        $this->assertNull(session('cliente_id'));
    }

    public function test_cuenta_inactiva(): void
    {
        $this->crearCliente(activo: false);
        $response = $this->post('/login-cliente', ['usuario' => 'CLI001', 'password' => 'secret123']);
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Tu cuenta está desactivada. Contacta a Salcom.');
    }

    public function test_flujo_completo(): void
    {
        $this->crearCliente();
        $this->post('/login-cliente', ['usuario' => 'CLI001', 'password' => 'secret123']);

        $this->get('/portal-cliente')->assertStatus(200);
        $this->get('/cliente/dashboard')->assertStatus(200);
        $this->get('/cliente/catalogo')->assertStatus(200);
        $this->get('/cliente/pedidos')->assertStatus(200);
    }

    public function test_rutas_protegidas_redirigen_sin_sesion(): void
    {
        foreach (['/portal-cliente', '/cliente/dashboard', '/cliente/catalogo', '/cliente/pedidos'] as $ruta) {
            $this->get($ruta)->assertRedirect('/login-cliente');
        }
    }

    public function test_logout_limpia_sesion(): void
    {
        $this->crearCliente();
        $this->post('/login-cliente', ['usuario' => 'CLI001', 'password' => 'secret123']);
        $this->assertNotNull(session('cliente_id'));

        $this->post('/logout-cliente')->assertRedirect('/login-cliente');
        $this->assertNull(session('cliente_id'));
        $this->assertNull(session('cliente_nombre'));
        $this->assertNull(session('cliente_tipo'));
    }
}
