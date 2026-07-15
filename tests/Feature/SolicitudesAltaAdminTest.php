<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ContactoProveedor;
use App\Models\DocumentoProveedor;
use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SolicitudesAltaAdminTest extends TestCase
{
    use RefreshDatabase;

    private function sesionAdmin(): array
    {
        $admin = AdminUser::create([
            'nombre' => 'Dir Test',
            'correo' => 'dir@test.com',
            'usuario' => 'DIRTEST',
            'password' => Hash::make('test1234'),
            'activo' => true,
            'rol' => 'admin',
        ]);

        return [
            'admin_id' => $admin->id,
            'admin_nombre' => $admin->nombre,
            'admin_correo' => $admin->correo,
            'admin_usuario' => $admin->usuario,
            'admin_rol' => $admin->rol,
        ];
    }

    private function proveedorPendiente(array $extra = []): ProveedorUser
    {
        return ProveedorUser::create(array_merge([
            'usuario' => 'solicitante@test.com',
            'password' => Hash::make('secret123'),
            'nombre' => 'Solicitante SA',
            'correo' => 'solicitante@test.com',
            'tipo_persona' => 'Persona Moral',
            'telefono' => '5551112233',
            'activo' => false,
        ], $extra));
    }

    public function test_pagina_solicitudes_carga(): void
    {
        $this->withSession($this->sesionAdmin());
        $this->proveedorPendiente();

        $this->get(route('admin.solicitudes-alta'))
            ->assertOk()
            ->assertSee('Solicitudes de alta')
            ->assertSee('Solicitante SA')
            ->assertSee('Revisar');
    }

    public function test_detalle_muestra_formulario_y_bancarios(): void
    {
        $this->withSession($this->sesionAdmin());
        $p = $this->proveedorPendiente([
            'datos_identificacion' => [
                'tipo_persona' => 'Persona Moral',
                'razon_social' => 'Solicitante SA de CV',
                'banco' => 'BBVA',
                'clabe' => '012345678901234567',
                'cuenta' => '123456',
                'calle' => 'Av Test',
            ],
        ]);

        DocumentoProveedor::create([
            'proveedor_id' => $p->id,
            'tipo' => 'cif',
            'estatus' => 'pendiente',
            'archivo' => 'documentos-fiscales/demo.pdf',
        ]);

        $this->get(route('admin.solicitudes-alta.detalle', $p))
            ->assertOk()
            ->assertSee('BBVA')
            ->assertSee('012345678901234567')
            ->assertSee('Av Test')
            ->assertSee('Aprobar y activar');
    }

    public function test_aprueba_manual_sin_onboarding_completo(): void
    {
        $this->withSession($this->sesionAdmin());
        $p = $this->proveedorPendiente();

        $this->post(route('admin.solicitudes-alta.aprobar'), [
            'proveedor_id' => $p->id,
        ])->assertRedirect(route('admin.solicitudes-alta'));

        $this->assertTrue($p->fresh()->activo);
    }

    public function test_proveedor_guarda_identificacion_en_bd(): void
    {
        $p = $this->proveedorPendiente();

        $this->withSession([
            'proveedor_id' => $p->id,
            'proveedor_nombre' => $p->nombre,
            'proveedor_correo' => $p->correo,
        ])->post(route('proveedores.identificacion.guardar'), [
            'fecha' => '2026-07-15',
            'tipo_persona' => 'Persona Moral',
            'razon_social' => 'Empresa Demo',
            'banco' => 'Banorte',
            'clabe' => '012345678901234567',
            'cuenta' => '998877',
            'calle' => 'Calle 1',
            'correo' => 'solicitante@test.com',
        ])->assertRedirect(route('proveedores.validacion-fiscal'));

        $fresh = $p->fresh();
        $this->assertSame('Banorte', $fresh->datos_identificacion['banco'] ?? null);
        $this->assertTrue($fresh->tieneFormularioDatosBancarios());
    }
}
