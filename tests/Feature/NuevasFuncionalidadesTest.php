<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ClienteUser;
use App\Models\ContactoProveedor;
use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NuevasFuncionalidadesTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): array
    {
        $admin = AdminUser::create(['nombre' => 'Admin', 'correo' => 'a@a.com', 'usuario' => 'ADM1', 'password' => Hash::make('x'), 'activo' => true]);
        return ['admin_id' => $admin->id, 'admin_nombre' => $admin->nombre, 'admin_correo' => $admin->correo, 'admin_usuario' => $admin->usuario];
    }

    private function proveedorSession(): array
    {
        $p = ProveedorUser::create(['usuario' => 'P1', 'password' => Hash::make('x'), 'nombre' => 'Prov Test', 'correo' => 'p@p.com']);
        return ['proveedor_id' => $p->id, 'proveedor_nombre' => $p->nombre, 'proveedor_codigo' => $p->codigo_compras];
    }

    private function clienteSession(): array
    {
        $c = ClienteUser::create(['nombre' => 'Cli Test', 'correo' => 'c@c.com', 'usuario' => 'C1', 'password' => Hash::make('x'), 'codigo_cliente' => 'CLI-001']);
        return ['cliente_id' => $c->id, 'cliente_nombre' => $c->nombre, 'cliente_codigo' => $c->codigo_cliente];
    }

    // ── Admin Dashboard ──

    public function test_admin_dashboard_requiere_auth(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login-admin');
    }

    public function test_admin_dashboard_muestra_metricas(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Clientes');
        $response->assertSee('Proveedores');
    }

    public function test_admin_dashboard_tiene_tabs_departamento(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('General');
        $response->assertSee('Clientes');
        $response->assertSee('Proveedores');
    }

    // ── Admin Proveedores con Score ──

    public function test_admin_proveedores_muestra_score(): void
    {
        ProveedorUser::create(['usuario' => 'P1', 'password' => Hash::make('x'), 'nombre' => 'Test', 'correo' => 'p@p.com', 'score_total' => 85, 'score_entrega' => 90, 'score_puntualidad' => 80]);

        $response = $this->withSession($this->adminSession())->get('/admin/proveedores');
        $response->assertStatus(200);
        $response->assertSee('85%');
    }

    // ── Forecast Proveedor ──

    public function test_forecast_proveedor_requiere_auth(): void
    {
        $this->get('/forecast')->assertRedirect('/login-proveedor');
    }

    public function test_forecast_proveedor_muestra_tendencias(): void
    {
        $response = $this->withSession($this->proveedorSession())->get('/forecast');
        $response->assertStatus(200);
        $response->assertSee('Forecast');
        $response->assertSee('Productos al alza');
        $response->assertSee('Productos a la baja');
    }

    // ── Forecast Cliente ──

    public function test_forecast_cliente_requiere_auth(): void
    {
        $this->get('/cliente/forecast')->assertRedirect('/login-cliente');
    }

    public function test_forecast_cliente_muestra_tendencias(): void
    {
        $response = $this->withSession($this->clienteSession())->get('/cliente/forecast');
        $response->assertStatus(200);
        $response->assertSee('Forecast');
        $response->assertSee('Al alza');
    }

    // ── Contactos del proveedor ──

    public function test_proveedor_puede_agregar_contacto(): void
    {
        $session = $this->proveedorSession();

        $response = $this->withSession($session)->post('/proveedor/contactos', [
            'nombre' => 'Juan Pérez',
            'rol' => 'calidad',
            'telefono' => '3312345678',
            'correo' => 'juan@empresa.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('contactos_proveedor', ['nombre' => 'Juan Pérez', 'rol' => 'calidad']);
    }

    public function test_proveedor_puede_eliminar_contacto(): void
    {
        $session = $this->proveedorSession();
        $contacto = ContactoProveedor::create(['proveedor_id' => $session['proveedor_id'], 'nombre' => 'Test', 'rol' => 'ventas']);

        $response = $this->withSession($session)->delete("/proveedor/contactos/{$contacto->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('contactos_proveedor', ['id' => $contacto->id]);
    }

    // ── Aviso de privacidad ──

    public function test_aviso_privacidad_es_publico(): void
    {
        $response = $this->get('/aviso-privacidad');
        $response->assertStatus(200);
        $response->assertSee('Aviso de Privacidad');
    }

    public function test_proveedor_puede_aceptar_aviso(): void
    {
        $session = $this->proveedorSession();

        $response = $this->withSession($session)->post('/proveedor/aviso-privacidad');

        $response->assertRedirect();
        $this->assertTrue(ProveedorUser::find($session['proveedor_id'])->aviso_privacidad_aceptado);
    }

    // ── Login admin redirige a dashboard ──

    public function test_login_admin_redirige_a_dashboard(): void
    {
        AdminUser::create(['nombre' => 'Admin', 'correo' => 'a@a.com', 'usuario' => 'ADM1', 'password' => Hash::make('test123'), 'activo' => true]);

        $response = $this->post('/login-admin', ['usuario' => 'ADM1', 'password' => 'test123']);
        $response->assertRedirect('/admin/dashboard');
    }
}
