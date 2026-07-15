<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function sesionAdmin(array $overrides = []): array
    {
        $admin = AdminUser::create(array_merge([
            'nombre' => 'Admin Test',
            'correo' => 'admin@test.com',
            'usuario' => 'ADMTEST',
            'password' => Hash::make('test1234'),
            'activo' => true,
            'rol' => 'admin',
        ], $overrides));

        return [
            'admin_id' => $admin->id,
            'admin_nombre' => $admin->nombre,
            'admin_correo' => $admin->correo,
            'admin_usuario' => $admin->usuario,
            'admin_rol' => $admin->rol,
        ];
    }

    /** @return array<int, string> */
    private function rutasPrincipales(): array
    {
        return [
            '/admin/dashboard',
            '/admin/perfil',
            '/admin/clientes',
            '/admin/encuestas',
            '/admin/pedidos',
            '/admin/proveedores',
            '/admin/solicitudes-alta',
            '/admin/productos',
            '/admin/facturas',
            '/admin/documentos',
            '/admin/negocio',
            '/admin/otif',
            '/admin/inventario',
            '/admin/fiscal',
            '/admin/gestion-compras',
            '/admin/reporte-proveedores',
            '/admin/reporte-proveedores/corte',
            '/admin/opinion-positiva',
            '/admin/alta-producto',
            '/admin/migracion-masiva',
            '/admin/ia',
            '/admin/administradores',
            '/admin/cliente/alta',
        ];
    }

    public function test_paginas_admin_cargan_con_sesion(): void
    {
        $this->withSession($this->sesionAdmin());

        foreach ($this->rutasPrincipales() as $uri) {
            $response = $this->get($uri);
            $response->assertOk("Falló al cargar: {$uri}");
        }
    }

    public function test_perfil_muestra_datos_y_foto(): void
    {
        $this->withSession($this->sesionAdmin());

        $response = $this->get('/admin/perfil');

        $response->assertOk();
        $response->assertSee('Admin Test');
        $response->assertSee('Información General');
        $response->assertSee('Estado de Cuenta');
        $response->assertSee('Agregar administradores');
    }

    public function test_perfil_sin_rol_admin_no_muestra_boton_administradores(): void
    {
        $this->withSession($this->sesionAdmin(['rol' => 'gerente']));

        $response = $this->get('/admin/perfil');

        $response->assertOk();
        $response->assertDontSee('Agregar administradores');
    }

    public function test_validar_rfc_requiere_autenticacion(): void
    {
        $response = $this->postJson('/admin/cliente/validar-rfc', ['rfc' => 'XAXX010101000']);

        $response->assertRedirect('/login-admin');
    }

    public function test_rol_admin_accede_a_materia_prima(): void
    {
        $this->withSession($this->sesionAdmin(['rol' => 'admin']));

        $this->get('/admin/materia-prima')->assertOk();
    }

    public function test_rol_sin_permiso_no_accede_a_materia_prima(): void
    {
        $this->withSession($this->sesionAdmin(['rol' => 'material_empaque']));

        $this->get('/admin/materia-prima')->assertRedirect('/admin/dashboard');
    }

    public function test_materia_prima_accesible_con_rol_materia_prima(): void
    {
        $this->withSession($this->sesionAdmin(['rol' => 'materia_prima']));

        $this->get('/admin/materia-prima')->assertOk();
    }

    public function test_gerente_accede_a_materia_prima(): void
    {
        $this->withSession($this->sesionAdmin(['rol' => 'gerente']));

        $this->get('/admin/materia-prima')->assertOk();
    }
}
