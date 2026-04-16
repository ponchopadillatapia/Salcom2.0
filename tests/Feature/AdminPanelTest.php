<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ClienteUser;
use App\Models\Encuesta;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): array
    {
        $admin = AdminUser::create([
            'nombre'   => 'Super Admin',
            'correo'   => 'admin@salcom.com',
            'usuario'  => 'ADMIN001',
            'password' => Hash::make('salcom2026'),
            'activo'   => true,
        ]);

        return [
            'admin_id'      => $admin->id,
            'admin_nombre'  => $admin->nombre,
            'admin_correo'  => $admin->correo,
            'admin_usuario' => $admin->usuario,
        ];
    }

    private function crearCliente(array $overrides = []): ClienteUser
    {
        static $counter = 0;
        $counter++;

        return ClienteUser::create(array_merge([
            'nombre'         => "Cliente Test {$counter}",
            'correo'         => "cliente{$counter}@test.com",
            'usuario'        => "CLI{$counter}",
            'password'       => Hash::make('test1234'),
            'codigo_cliente' => "CLI-2026-{$counter}",
            'tipo_cliente'   => 'mayorista',
            'activo'         => true,
        ], $overrides));
    }

    // ═══════════════════════════════════════════
    //  LISTA DE CLIENTES
    // ═══════════════════════════════════════════

    public function test_clientes_requiere_autenticacion(): void
    {
        $response = $this->get('/admin/clientes');
        $response->assertRedirect('/login-admin');
    }

    public function test_clientes_muestra_tabla(): void
    {
        $this->crearCliente(['nombre' => 'Acme Corp', 'correo' => 'acme@corp.com']);

        $response = $this->withSession($this->adminSession())->get('/admin/clientes');

        $response->assertStatus(200);
        $response->assertSee('Lista de Clientes');
        $response->assertSee('Acme Corp');
        $response->assertSee('acme@corp.com');
    }

    public function test_clientes_busqueda_por_nombre(): void
    {
        $this->crearCliente(['nombre' => 'Acme Corp']);
        $this->crearCliente(['nombre' => 'Otro Cliente']);

        $response = $this->withSession($this->adminSession())
            ->get('/admin/clientes?busqueda=Acme');

        $response->assertStatus(200);
        $response->assertSee('Acme Corp');
        $response->assertDontSee('Otro Cliente');
    }

    public function test_clientes_busqueda_por_correo(): void
    {
        $this->crearCliente(['nombre' => 'Acme Corp', 'correo' => 'acme@corp.com']);
        $this->crearCliente(['nombre' => 'Otro', 'correo' => 'otro@test.com']);

        $response = $this->withSession($this->adminSession())
            ->get('/admin/clientes?busqueda=acme@corp');

        $response->assertStatus(200);
        $response->assertSee('Acme Corp');
        $response->assertDontSee('>Otro<');
    }

    public function test_clientes_toggle_activar_desactivar(): void
    {
        $cliente = $this->crearCliente(['activo' => true]);
        $session = $this->adminSession();

        // Desactivar
        $response = $this->withSession($session)
            ->patch("/admin/clientes/{$cliente->id}/toggle");

        $response->assertRedirect();
        $response->assertSessionHas('mensaje');
        $this->assertFalse($cliente->fresh()->activo);

        // Activar
        $response = $this->withSession($session)
            ->patch("/admin/clientes/{$cliente->id}/toggle");

        $response->assertRedirect();
        $this->assertTrue($cliente->fresh()->activo);
    }

    public function test_clientes_toggle_requiere_autenticacion(): void
    {
        $cliente = $this->crearCliente();

        $response = $this->patch("/admin/clientes/{$cliente->id}/toggle");
        $response->assertRedirect('/login-admin');
    }

    public function test_clientes_muestra_estado_vacio(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/clientes');

        $response->assertStatus(200);
        $response->assertSee('No se encontraron clientes');
    }

    // ═══════════════════════════════════════════
    //  ENCUESTAS
    // ═══════════════════════════════════════════

    public function test_encuestas_requiere_autenticacion(): void
    {
        $response = $this->get('/admin/encuestas');
        $response->assertRedirect('/login-admin');
    }

    public function test_encuestas_muestra_tabla_y_promedios(): void
    {
        Encuesta::create([
            'codigo_cliente'   => 'CLI-001',
            'calificacion'     => 4,
            'tiempo_entrega'   => 5,
            'calidad_producto' => 3,
            'comentarios'      => 'Buen servicio',
        ]);
        Encuesta::create([
            'codigo_cliente'   => 'CLI-002',
            'calificacion'     => 2,
            'tiempo_entrega'   => 3,
            'calidad_producto' => 5,
            'comentarios'      => null,
        ]);

        $response = $this->withSession($this->adminSession())->get('/admin/encuestas');

        $response->assertStatus(200);
        $response->assertSee('Encuestas de Satisfacción');
        $response->assertSee('CLI-001');
        $response->assertSee('CLI-002');
        $response->assertSee('Buen servicio');
        // Promedio general: (4+2)/2 = 3.0
        $response->assertSee('3.0');
    }

    public function test_encuestas_muestra_estado_vacio(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/encuestas');

        $response->assertStatus(200);
        $response->assertSee('no hay encuestas registradas');
    }

    // ═══════════════════════════════════════════
    //  PEDIDOS
    // ═══════════════════════════════════════════

    public function test_pedidos_requiere_autenticacion(): void
    {
        $response = $this->get('/admin/pedidos');
        $response->assertRedirect('/login-admin');
    }

    public function test_pedidos_muestra_tabla(): void
    {
        Pedido::create([
            'folio'          => 'PED-001',
            'codigo_cliente' => 'CLI-001',
            'nombre_cliente' => 'Acme Corp',
            'productos'      => [['nombre' => 'Producto A', 'cantidad' => 10]],
            'total'          => 15000.50,
            'tipo_pago'      => 'credito',
            'estatus'        => 'procesando',
        ]);

        $response = $this->withSession($this->adminSession())->get('/admin/pedidos');

        $response->assertStatus(200);
        $response->assertSee('Pedidos');
        $response->assertSee('PED-001');
        $response->assertSee('Acme Corp');
        $response->assertSee('$15,000.50');
        $response->assertSee('Procesando');
    }

    public function test_pedidos_filtro_por_estatus(): void
    {
        Pedido::create([
            'folio' => 'PED-001', 'codigo_cliente' => 'CLI-001',
            'nombre_cliente' => 'Acme', 'productos' => [],
            'total' => 100, 'estatus' => 'procesando',
        ]);
        Pedido::create([
            'folio' => 'PED-002', 'codigo_cliente' => 'CLI-002',
            'nombre_cliente' => 'Beta', 'productos' => [],
            'total' => 200, 'estatus' => 'entregado',
        ]);

        $response = $this->withSession($this->adminSession())
            ->get('/admin/pedidos?estatus=procesando');

        $response->assertStatus(200);
        $response->assertSee('PED-001');
        $response->assertDontSee('PED-002');
    }

    public function test_pedidos_muestra_estado_vacio(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/pedidos');

        $response->assertStatus(200);
        $response->assertSee('No se encontraron pedidos');
    }

    public function test_pedidos_filtro_vacio_muestra_todos(): void
    {
        Pedido::create([
            'folio' => 'PED-001', 'codigo_cliente' => 'CLI-001',
            'nombre_cliente' => 'Acme', 'productos' => [],
            'total' => 100, 'estatus' => 'procesando',
        ]);
        Pedido::create([
            'folio' => 'PED-002', 'codigo_cliente' => 'CLI-002',
            'nombre_cliente' => 'Beta', 'productos' => [],
            'total' => 200, 'estatus' => 'entregado',
        ]);

        $response = $this->withSession($this->adminSession())
            ->get('/admin/pedidos?estatus=');

        $response->assertStatus(200);
        $response->assertSee('PED-001');
        $response->assertSee('PED-002');
    }

    // ═══════════════════════════════════════════
    //  SIDEBAR
    // ═══════════════════════════════════════════

    public function test_sidebar_muestra_links_nuevas_secciones(): void
    {
        $response = $this->withSession($this->adminSession())->get('/admin/clientes');

        $response->assertStatus(200);
        $response->assertSee('Lista de Clientes');
        $response->assertSee('Encuestas');
        $response->assertSee('Pedidos');
    }
}
