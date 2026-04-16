<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InicioYErroresTest extends TestCase
{
    use RefreshDatabase;

    // ═══════════════════════════════════════════
    //  PÁGINA DE INICIO
    // ═══════════════════════════════════════════

    public function test_inicio_muestra_pagina_principal(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Industrias Salcom');
        $response->assertSee('Selecciona tu portal');
    }

    public function test_inicio_tiene_link_a_proveedores(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Portal Proveedores');
        $response->assertSee('/login-proveedor');
    }

    public function test_inicio_tiene_link_a_clientes(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Portal Clientes');
        $response->assertSee('/login-cliente');
    }

    public function test_inicio_tiene_link_a_admin(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Acceso administrador');
        $response->assertSee('/login-admin');
    }

    // ═══════════════════════════════════════════
    //  PÁGINAS DE ERROR 404
    // ═══════════════════════════════════════════

    public function test_404_sin_sesion_redirige_a_inicio(): void
    {
        $response = $this->get('/ruta-que-no-existe');

        $response->assertStatus(404);
        $response->assertSee('Página no encontrada', false);
        $response->assertSee('Industrias Salcom');
        $response->assertSee('href="/"', false);
    }

    public function test_404_con_sesion_proveedor_redirige_a_portal_proveedor(): void
    {
        $response = $this->withSession(['proveedor_id' => 1])
            ->get('/ruta-que-no-existe');

        $response->assertStatus(404);
        $response->assertSee('Portal de Proveedores');
        $response->assertSee('href="/portal-proveedor"', false);
    }

    public function test_404_con_sesion_cliente_redirige_a_portal_cliente(): void
    {
        $response = $this->withSession(['cliente_id' => 1])
            ->get('/ruta-que-no-existe');

        $response->assertStatus(404);
        $response->assertSee('Portal de Clientes');
        $response->assertSee('href="/portal-cliente"', false);
    }

    public function test_404_con_sesion_admin_redirige_a_panel_admin(): void
    {
        $response = $this->withSession(['admin_id' => 1])
            ->get('/ruta-que-no-existe');

        $response->assertStatus(404);
        $response->assertSee('Panel Administrativo');
        $response->assertSee('href="/admin/ia"', false);
    }

    // ═══════════════════════════════════════════
    //  ADMIN SOFT DELETES (verificación)
    // ═══════════════════════════════════════════

    public function test_admin_user_soft_delete_funciona(): void
    {
        $admin = AdminUser::create([
            'nombre'   => 'Test Admin',
            'correo'   => 'test@salcom.com',
            'usuario'  => 'TESTADMIN',
            'password' => Hash::make('test1234'),
            'activo'   => true,
        ]);

        $admin->delete();

        // No aparece en queries normales
        $this->assertNull(AdminUser::find($admin->id));

        // Sí aparece con withTrashed
        $this->assertNotNull(AdminUser::withTrashed()->find($admin->id));

        // La columna deleted_at tiene valor
        $this->assertSoftDeleted('admin_users', ['usuario' => 'TESTADMIN']);
    }

    public function test_admin_user_soft_delete_se_puede_restaurar(): void
    {
        $admin = AdminUser::create([
            'nombre'   => 'Test Admin',
            'correo'   => 'test@salcom.com',
            'usuario'  => 'TESTADMIN',
            'password' => Hash::make('test1234'),
            'activo'   => true,
        ]);

        $admin->delete();
        $this->assertSoftDeleted('admin_users', ['usuario' => 'TESTADMIN']);

        // Restaurar
        $admin->restore();
        $this->assertNotNull(AdminUser::find($admin->id));
        $this->assertNull($admin->fresh()->deleted_at);
    }

    public function test_admin_user_soft_deleted_no_puede_hacer_login(): void
    {
        $admin = AdminUser::create([
            'nombre'   => 'Test Admin',
            'correo'   => 'test@salcom.com',
            'usuario'  => 'TESTADMIN',
            'password' => Hash::make('test1234'),
            'activo'   => true,
        ]);

        $admin->delete();

        $response = $this->post('/login-admin', [
            'usuario'  => 'TESTADMIN',
            'password' => 'test1234',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Credenciales incorrectas');
        $response->assertSessionMissing('admin_id');
    }
}
