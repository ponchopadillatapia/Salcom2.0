<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegistroCaptchaTest extends TestCase
{
    use RefreshDatabase;

    private array $datosRegistro = [
        'nombre'                => 'Proveedor Test',
        'tipo_persona'          => 'Moral',
        'telefono'              => '5551234567',
        'correo'                => 'nuevo@test.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
        'g-recaptcha-response'  => 'fake-token',
    ];

    public function test_registro_exitoso_con_captcha_valido(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true], 200),
        ]);

        $response = $this->post('/proveedor/registro', $this->datosRegistro);

        $response->assertRedirect('/login-proveedor');
        $this->assertDatabaseHas('proveedores_users', ['correo' => 'nuevo@test.com']);
    }

    public function test_registro_falla_con_captcha_invalido(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
        ]);

        $response = $this->post('/proveedor/registro', $this->datosRegistro);

        $response->assertRedirect();
        $response->assertSessionHasErrors('g-recaptcha-response');
        $this->assertDatabaseMissing('proveedores_users', ['correo' => 'nuevo@test.com']);
    }

    public function test_registro_funciona_sin_secret_key_configurada(): void
    {
        config(['services.recaptcha.secret_key' => null]);

        $response = $this->post('/proveedor/registro', $this->datosRegistro);

        $response->assertRedirect('/login-proveedor');
        $this->assertDatabaseHas('proveedores_users', ['correo' => 'nuevo@test.com']);
    }

    public function test_registro_falla_sin_captcha_token(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false], 200),
        ]);

        $datos = $this->datosRegistro;
        unset($datos['g-recaptcha-response']);

        $response = $this->post('/proveedor/registro', $datos);

        $response->assertRedirect();
        $this->assertDatabaseMissing('proveedores_users', ['correo' => 'nuevo@test.com']);
    }
}
