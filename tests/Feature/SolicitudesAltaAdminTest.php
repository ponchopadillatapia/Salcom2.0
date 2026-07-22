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
            ->assertSee('Ver')
            ->assertSee('Rechazar')
            ->assertSee('Aprobar');
    }

    public function test_ver_muestra_resultados_de_validacion_aprobados(): void
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
            'estatus' => 'aprobado',
            'archivo' => 'documentos-fiscales/demo.pdf',
            'notas_revision' => 'Validación automática aprobada',
            'resultado_validacion' => [
                'valida' => true,
                'hallazgos' => ['Sello del SAT detectado', 'RFC encontrado'],
                'errores' => [],
            ],
        ]);

        $this->get(route('admin.solicitudes-alta.ver', $p))
            ->assertOk()
            ->assertSee('DOCUMENTOS CORRECTOS')
            ->assertSee('Constancia de Situación Fiscal')
            ->assertSee('Sello del SAT detectado')
            ->assertSee('Clic para descargar PDF');
    }

    public function test_aprueba_manual_sin_onboarding_completo(): void
    {
        $this->withSession($this->sesionAdmin());
        $p = $this->proveedorPendiente();

        $this->post(route('admin.solicitudes-alta.aprobar'), [
            'proveedor_id' => $p->id,
        ])->assertRedirect();

        $this->assertFalse($p->fresh()->activo);
    }

    public function test_no_aprueba_sin_dos_contactos(): void
    {
        $this->withSession($this->sesionAdmin());
        $p = $this->proveedorPendiente([
            'datos_identificacion' => ['banco' => 'BBVA', 'clabe' => '012345678901234567'],
        ]);

        $this->post(route('admin.solicitudes-alta.aprobar'), [
            'proveedor_id' => $p->id,
        ])->assertRedirect();

        $this->assertFalse($p->fresh()->activo);
    }

    public function test_aprueba_con_bancarios_y_dos_contactos(): void
    {
        $this->withSession($this->sesionAdmin());
        $p = $this->proveedorPendiente([
            'datos_identificacion' => ['banco' => 'BBVA', 'clabe' => '012345678901234567'],
        ]);
        ContactoProveedor::create([
            'proveedor_id' => $p->id, 'nombre' => 'Uno', 'rol' => 'ventas',
            'telefono' => '3311111111', 'correo' => 'u1@t.com',
        ]);
        ContactoProveedor::create([
            'proveedor_id' => $p->id, 'nombre' => 'Dos', 'rol' => 'compras',
            'telefono' => '3322222222', 'correo' => 'u2@t.com',
        ]);

        $this->post(route('admin.solicitudes-alta.aprobar'), [
            'proveedor_id' => $p->id,
        ])->assertRedirect(route('admin.solicitudes-alta'));

        $this->assertTrue($p->fresh()->activo);
    }

    public function test_activo_sin_contactos_no_entra_a_operaciones(): void
    {
        $p = $this->proveedorPendiente([
            'activo' => true,
            'datos_identificacion' => ['banco' => 'BBVA', 'clabe' => '012345678901234567'],
        ]);

        $this->withSession([
            'proveedor_id' => $p->id,
            'proveedor_nombre' => $p->nombre,
            'proveedor_correo' => $p->correo,
        ]);

        $this->get(route('proveedores.oc'))
            ->assertRedirect(route('proveedores.perfil'));

        $this->get(route('proveedores.perfil'))->assertOk();
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
            'razon_social' => 'Empresa Demo SA de CV',
            'calle' => 'Calle 1',
            'num_exterior' => '100',
            'colonia' => 'Centro',
            'municipio' => 'Guadalajara',
            'estado' => 'Jalisco',
            'ciudad' => 'Guadalajara',
            'pais' => 'México',
            'cp' => '44100',
            'telefono' => '3312345678',
            'celular' => '3387654321',
            'correo' => 'solicitante@test.com',
            'banco' => 'Banorte',
            'clabe' => '012345678901234567',
            'cuenta' => '99887766',
            'nombre_firma' => 'Juan Perez',
        ])->assertRedirect(route('proveedores.onboarding'));

        $fresh = $p->fresh();
        $this->assertSame('Banorte', $fresh->datos_identificacion['banco'] ?? null);
        $this->assertTrue($fresh->tieneFormularioDatosBancarios());
    }

    public function test_rechaza_solicitud_y_sale_de_pendientes(): void
    {
        $this->withSession($this->sesionAdmin());
        $p = $this->proveedorPendiente();

        $this->post(route('admin.solicitudes-alta.rechazar'), [
            'proveedor_id' => $p->id,
        ])->assertRedirect(route('admin.solicitudes-alta'));

        $this->assertSoftDeleted('proveedores_users', ['id' => $p->id]);
    }

    public function test_ver_solo_muestra_documentos_aprobados(): void
    {
        $this->withSession($this->sesionAdmin());
        $p = $this->proveedorPendiente();

        DocumentoProveedor::create([
            'proveedor_id' => $p->id,
            'tipo' => 'cif',
            'archivo' => 'expediente_fiscal/cif/ok.pdf',
            'estatus' => 'aprobado',
            'notas_revision' => 'Validación automática aprobada',
        ]);
        DocumentoProveedor::create([
            'proveedor_id' => $p->id,
            'tipo' => 'opinion',
            'archivo' => 'expediente_fiscal/opinion/bad.pdf',
            'estatus' => 'pendiente',
            'notas_revision' => 'Pendiente',
        ]);

        $this->get(route('admin.solicitudes-alta.ver', $p))
            ->assertOk()
            ->assertSee('Constancia de Situación Fiscal')
            ->assertSee('Aprobado')
            ->assertDontSee('Opinión de Cumplimiento SAT');
    }
}
