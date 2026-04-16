<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(array $overrides = []): AdminUser
    {
        return AdminUser::create(array_merge([
            'nombre'   => 'Super Administrador',
            'correo'   => 'admin@salcom.com',
            'usuario'  => 'ADMIN001',
            'password' => Hash::make('salcom2026'),
            'activo'   => true,
        ], $overrides));
    }

    // ── Vista de login ──

    public function test_login_admin_muestra_formulario(): void
    {
        $response = $this->get('/login-admin');

        $response->assertStatus(200);
        $response->assertSee('Panel Administrativo');
        $response->assertSee('Iniciar sesión');
    }

    // ── Login exitoso ──

    public function test_login_exitoso_redirige_a_dashboard(): void
    {
        $this->crearAdmin();

        $response = $this->post('/login-admin', [
            'usuario'  => 'ADMIN001',
            'password' => 'salcom2026',
        ]);

        $response->assertRedirect('/admin/ia');
        $response->assertSessionHas('admin_id');
        $response->assertSessionHas('admin_nombre', 'Super Administrador');
        $response->assertSessionHas('admin_usuario', 'ADMIN001');
    }

    // ── Credenciales incorrectas ──

    public function test_login_con_credenciales_incorrectas(): void
    {
        $this->crearAdmin();

        $response = $this->post('/login-admin', [
            'usuario'  => 'ADMIN001',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Credenciales incorrectas');
        $response->assertSessionMissing('admin_id');
    }

    public function test_login_con_usuario_inexistente(): void
    {
        $response = $this->post('/login-admin', [
            'usuario'  => 'NOEXISTE',
            'password' => 'salcom2026',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Credenciales incorrectas');
    }

    // ── Cuenta inactiva ──

    public function test_login_con_cuenta_inactiva(): void
    {
        $this->crearAdmin(['activo' => false]);

        $response = $this->post('/login-admin', [
            'usuario'  => 'ADMIN001',
            'password' => 'salcom2026',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Tu cuenta está desactivada. Contacta al administrador.');
        $response->assertSessionMissing('admin_id');
    }

    // ── Validación de campos ──

    public function test_login_requiere_usuario_y_password(): void
    {
        $response = $this->post('/login-admin', []);

        $response->assertSessionHasErrors(['usuario', 'password']);
    }

    // ── Logout ──

    public function test_logout_limpia_sesion_y_redirige(): void
    {
        $this->crearAdmin();

        // Login primero
        $this->post('/login-admin', [
            'usuario'  => 'ADMIN001',
            'password' => 'salcom2026',
        ]);

        $response = $this->post('/logout-admin');

        $response->assertRedirect('/login-admin');
        $response->assertSessionHas('mensaje', 'Sesión cerrada correctamente');
        $response->assertSessionMissing('admin_id');
    }

    // ── Middleware protege rutas ──

    public function test_middleware_redirige_sin_sesion(): void
    {
        $response = $this->get('/admin/ia');

        $response->assertRedirect('/login-admin');
    }

    public function test_middleware_permite_con_sesion(): void
    {
        $admin = $this->crearAdmin();

        $response = $this->withSession([
            'admin_id'      => $admin->id,
            'admin_nombre'  => $admin->nombre,
            'admin_correo'  => $admin->correo,
            'admin_usuario' => $admin->usuario,
        ])->get('/admin/ia');

        $response->assertStatus(200);
    }

    public function test_middleware_protege_alta_cliente(): void
    {
        $response = $this->get('/admin/cliente/alta');

        $response->assertRedirect('/login-admin');
    }

    // ── Rate limiting ──

    public function test_rate_limiting_bloquea_despues_de_muchos_intentos(): void
    {
        RateLimiter::clear('login-admin|127.0.0.1');

        $this->crearAdmin();

        // Agotar intentos
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login-admin', [
                'usuario'  => 'ADMIN001',
                'password' => 'wrongpassword',
            ]);
        }

        // El siguiente intento debe ser bloqueado
        $response = $this->post('/login-admin', [
            'usuario'  => 'ADMIN001',
            'password' => 'salcom2026',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Demasiados intentos', session('error'));
    }

    public function test_rate_limiting_se_limpia_con_login_exitoso(): void
    {
        RateLimiter::clear('login-admin|127.0.0.1');

        $this->crearAdmin();

        // Algunos intentos fallidos
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login-admin', [
                'usuario'  => 'ADMIN001',
                'password' => 'wrongpassword',
            ]);
        }

        // Login exitoso limpia el rate limiter
        $response = $this->post('/login-admin', [
            'usuario'  => 'ADMIN001',
            'password' => 'salcom2026',
        ]);

        $response->assertRedirect('/admin/ia');
        $response->assertSessionHas('admin_id');
    }

    // ── Seeder ──

    public function test_seeder_crea_admin_de_prueba(): void
    {
        $this->seed(\Database\Seeders\AdminUserSeeder::class);

        $admin = AdminUser::where('usuario', 'ADMIN001')->first();

        $this->assertNotNull($admin);
        $this->assertEquals('Super Administrador', $admin->nombre);
        $this->assertEquals('admin@salcom.com', $admin->correo);
        $this->assertTrue($admin->activo);
        $this->assertTrue(Hash::check('salcom2026', $admin->password));
    }

    // ── Modelo ──

    public function test_modelo_soft_deletes(): void
    {
        $admin = $this->crearAdmin();

        $admin->delete();

        $this->assertSoftDeleted('admin_users', ['usuario' => 'ADMIN001']);
        $this->assertNotNull(AdminUser::withTrashed()->where('usuario', 'ADMIN001')->first());
    }

    public function test_modelo_oculta_password(): void
    {
        $admin = $this->crearAdmin();

        $array = $admin->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    // ── Flujo completo ──

    public function test_flujo_completo_login_navegar_logout(): void
    {
        $this->crearAdmin();

        // 1. Login
        $loginResponse = $this->post('/login-admin', [
            'usuario'  => 'ADMIN001',
            'password' => 'salcom2026',
        ]);
        $loginResponse->assertRedirect('/admin/ia');

        // 2. Acceder a Dashboard IA
        $iaResponse = $this->get('/admin/ia');
        $iaResponse->assertStatus(200);

        // 3. Acceder a Alta de Cliente
        $altaResponse = $this->get('/admin/cliente/alta');
        $altaResponse->assertStatus(200);

        // 4. Logout
        $logoutResponse = $this->post('/logout-admin');
        $logoutResponse->assertRedirect('/login-admin');

        // 5. Ya no puede acceder
        $protectedResponse = $this->get('/admin/ia');
        $protectedResponse->assertRedirect('/login-admin');
    }
}
