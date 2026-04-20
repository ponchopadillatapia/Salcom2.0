<?php

namespace Tests\Feature;

use App\Models\ClienteUser;
use App\Models\Encuesta;
use App\Models\Pedido;
use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SalcomApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-salcom-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.salcom_api.token' => $this->token]);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ── Autenticación ──

    public function test_api_rechaza_sin_token(): void
    {
        $response = $this->getJson('/api/salcom/resumen');
        $response->assertStatus(401);
    }

    public function test_api_rechaza_token_incorrecto(): void
    {
        $response = $this->getJson('/api/salcom/resumen', [
            'Authorization' => 'Bearer token-malo',
        ]);
        $response->assertStatus(401);
    }

    public function test_api_acepta_token_correcto(): void
    {
        $response = $this->getJson('/api/salcom/resumen', $this->authHeaders());
        $response->assertStatus(200);
    }

    // ── Resumen ──

    public function test_resumen_devuelve_estructura(): void
    {
        $response = $this->getJson('/api/salcom/resumen', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'clientes'    => ['total', 'activos'],
                'proveedores' => ['total', 'activos'],
                'pedidos'     => ['total', 'por_estatus'],
                'encuestas'   => ['total', 'calificacion_prom'],
                'productos'   => ['total', 'activos'],
            ]);
    }

    // ── Clientes ──

    public function test_clientes_lista(): void
    {
        ClienteUser::create([
            'nombre' => 'Acme', 'correo' => 'a@a.com', 'usuario' => 'CLI1',
            'password' => Hash::make('x'), 'activo' => true,
        ]);

        $response = $this->getJson('/api/salcom/clientes', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.nombre', 'Acme');
    }

    public function test_clientes_busqueda(): void
    {
        ClienteUser::create(['nombre' => 'Acme', 'correo' => 'a@a.com', 'usuario' => 'C1', 'password' => Hash::make('x')]);
        ClienteUser::create(['nombre' => 'Beta', 'correo' => 'b@b.com', 'usuario' => 'C2', 'password' => Hash::make('x')]);

        $response = $this->getJson('/api/salcom/clientes?busqueda=Acme', $this->authHeaders());

        $response->assertStatus(200)->assertJsonPath('total', 1);
    }

    // ── Proveedores ──

    public function test_proveedores_lista(): void
    {
        ProveedorUser::create([
            'usuario' => 'P1', 'password' => Hash::make('x'),
            'nombre' => 'Proveedor Test', 'correo' => 'p@p.com',
        ]);

        $response = $this->getJson('/api/salcom/proveedores', $this->authHeaders());

        $response->assertStatus(200)->assertJsonPath('total', 1);
    }

    // ── Pedidos ──

    public function test_pedidos_lista_y_filtro(): void
    {
        Pedido::create([
            'folio' => 'PED-001', 'codigo_cliente' => 'C1',
            'nombre_cliente' => 'Acme', 'productos' => [],
            'total' => 100, 'estatus' => 'procesando',
        ]);
        Pedido::create([
            'folio' => 'PED-002', 'codigo_cliente' => 'C2',
            'nombre_cliente' => 'Beta', 'productos' => [],
            'total' => 200, 'estatus' => 'entregado',
        ]);

        // Sin filtro
        $response = $this->getJson('/api/salcom/pedidos', $this->authHeaders());
        $response->assertStatus(200)->assertJsonPath('total', 2);

        // Con filtro
        $response = $this->getJson('/api/salcom/pedidos?estatus=procesando', $this->authHeaders());
        $response->assertStatus(200)->assertJsonPath('total', 1);
    }

    // ── Encuestas ──

    public function test_encuestas_con_promedios(): void
    {
        Encuesta::create([
            'codigo_cliente' => 'C1', 'calificacion' => 4,
            'tiempo_entrega' => 5, 'calidad_producto' => 3,
        ]);
        Encuesta::create([
            'codigo_cliente' => 'C2', 'calificacion' => 2,
            'tiempo_entrega' => 3, 'calidad_producto' => 5,
        ]);

        $response = $this->getJson('/api/salcom/encuestas', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('promedios.total_encuestas', 2)
            ->assertJsonPath('promedios.calificacion', 3);
    }

    // ── No expone passwords ──

    public function test_clientes_no_expone_password(): void
    {
        ClienteUser::create([
            'nombre' => 'Test', 'correo' => 't@t.com', 'usuario' => 'T1',
            'password' => Hash::make('secreto'),
        ]);

        $response = $this->getJson('/api/salcom/clientes', $this->authHeaders());

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('data.0'));
    }
}
