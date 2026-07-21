<?php

namespace Tests\Feature;

use App\Mail\BienvenidaProveedor;
use App\Models\Alerta;
use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OnboardingLockTest extends TestCase
{
    use RefreshDatabase;

    private function crearInactivo(): ProveedorUser
    {
        return ProveedorUser::create([
            'usuario' => 'nuevo@test.com',
            'password' => Hash::make('secret123'),
            'nombre' => 'Proveedor Nuevo',
            'correo' => 'nuevo@test.com',
            'tipo_persona' => 'Persona Moral',
            'telefono' => '5551234567',
            'activo' => false,
        ]);
    }

    private function sesion(ProveedorUser $p): void
    {
        session([
            'proveedor_id' => $p->id,
            'proveedor_nombre' => $p->nombre,
            'proveedor_codigo' => $p->id_proveedor ?? $p->usuario,
            'proveedor_correo' => $p->correo,
        ]);
    }

    public function test_registro_crea_cuenta_inactiva(): void
    {
        Mail::fake();
        config(['services.recaptcha.secret_key' => null]);

        $this->post('/proveedor/registro', [
            'nombre' => 'Demo SA',
            'tipo_persona' => 'Persona Moral',
            'telefono' => '5559998877',
            'correo' => 'demo.lock@test.com',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect('/login-proveedor');

        $this->assertDatabaseHas('proveedores_users', [
            'correo' => 'demo.lock@test.com',
            'activo' => 0,
        ]);

        $proveedor = ProveedorUser::where('correo', 'demo.lock@test.com')->first();
        $this->assertNotNull($proveedor);

        Mail::assertSent(BienvenidaProveedor::class, function (BienvenidaProveedor $mail) {
            return $mail->correo === 'demo.lock@test.com'
                && $mail->nombreProveedor === 'Demo SA'
                && $mail->hasTo('demo.lock@test.com');
        });

        $this->assertDatabaseHas('alertas', [
            'tipo' => 'bienvenida',
            'destinatario_tipo' => 'proveedor',
            'destinatario_id' => $proveedor->id,
            'titulo' => '¡Bienvenido al Portal de Proveedores!',
        ]);

        $this->assertTrue(
            Alerta::where('destinatario_tipo', 'proveedor')
                ->where('destinatario_id', $proveedor->id)
                ->where('tipo', 'bienvenida')
                ->exists()
        );
    }

    public function test_inactivo_puede_ver_onboarding(): void
    {
        $p = $this->crearInactivo();
        $this->sesion($p);

        $this->get(route('proveedores.onboarding'))
            ->assertOk()
            ->assertSee('onboarding', false);
    }

    public function test_inactivo_puede_ver_onboarding_docs_y_perfil(): void
    {
        $p = $this->crearInactivo();
        $this->sesion($p);

        // Sin bancarios: docs bloqueados; identificación y perfil sí
        $this->get(route('proveedores.identificacion'))->assertOk();
        $this->get(route('proveedores.validacion-fiscal'))
            ->assertRedirect(route('proveedores.onboarding'));
        $this->get(route('proveedores.perfil'))->assertOk();

        $this->get(route('proveedores.onboarding'))
            ->assertOk()
            ->assertSee('Bloqueado');
    }

    public function test_con_bancarios_puede_abrir_validacion_docs(): void
    {
        $p = $this->crearInactivo();
        $p->update([
            'datos_identificacion' => [
                'banco' => 'BBVA',
                'clabe' => '012345678901234567',
            ],
        ]);
        $this->sesion($p->fresh());

        $this->get(route('proveedores.validacion-fiscal'))->assertOk();
        $this->get(route('proveedores.onboarding'))
            ->assertOk()
            ->assertSee('Validar');
    }

    public function test_inactivo_no_puede_acceder_operaciones(): void
    {
        $p = $this->crearInactivo();
        $this->sesion($p);

        $this->get(route('proveedores.oc'))
            ->assertRedirect(route('proveedores.onboarding'));

        $this->get(route('proveedores.portal'))
            ->assertRedirect(route('proveedores.onboarding'));

        $this->get(route('proveedores.alta-producto'))
            ->assertRedirect(route('proveedores.onboarding'));

        $this->get(route('proveedores.fiscal'))
            ->assertRedirect(route('proveedores.onboarding'));
    }

    public function test_activo_si_accede_operaciones(): void
    {
        $p = $this->crearInactivo();
        $p->update(['activo' => true]);
        $this->sesion($p->fresh());

        $this->get(route('proveedores.portal'))->assertOk();
        $this->get(route('proveedores.oc'))->assertOk();
    }

    public function test_login_inactivo_va_a_onboarding(): void
    {
        config(['services.proveedor_api.login_mode' => 'local']);
        $this->crearInactivo();

        $this->post('/login-proveedor', [
            'codigo' => 'nuevo@test.com',
            'pwd' => 'secret123',
        ])->assertRedirect(route('proveedores.onboarding'));
    }
}
