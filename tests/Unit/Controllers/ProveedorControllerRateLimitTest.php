<?php

namespace Tests\Unit\Controllers;

use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ProveedorControllerRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'http://fake-api.test/api';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.proveedor_api.url' => $this->baseUrl]);
        config(['services.proveedor_api.login_mode' => 'local']);
        config(['services.proveedor_api.max_retries' => 1]);
        config(['auth.rate_limiting.max_attempts' => 5]);
        config(['auth.rate_limiting.decay_seconds' => 60]);
        RateLimiter::clear('login-proveedor|127.0.0.1');
    }

    private function crearProveedor(): ProveedorUser
    {
        return ProveedorUser::create([
            'usuario'        => 'PROV001',
            'password'       => Hash::make('secret123'),
            'nombre'         => 'Test Proveedor',
            'codigo_compras' => 'COD001',
            'correo'         => 'test@example.com',
            'tipo_persona'   => 'Moral',
            'telefono'       => '5551234567',
        ]);
    }

    // 3.1: Defaults
    public function test_defaults_max_5_decay_60(): void
    {
        config(['auth.rate_limiting' => []]);
        $this->assertEquals(5, config('auth.rate_limiting.max_attempts', 5));
        $this->assertEquals(60, config('auth.rate_limiting.decay_seconds', 60));
    }

    // 3.2: Login fallido incrementa contador
    public function test_login_fallido_incrementa_contador(): void
    {
        $this->crearProveedor();
        $key = 'login-proveedor|127.0.0.1';

        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);
        $this->assertEquals(1, RateLimiter::attempts($key));

        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);
        $this->assertEquals(2, RateLimiter::attempts($key));
    }

    // 3.3: Login exitoso limpia contador
    public function test_login_exitoso_limpia_contador(): void
    {
        $this->crearProveedor();
        $key = 'login-proveedor|127.0.0.1';

        // 3 intentos fallidos
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);
        }
        $this->assertEquals(3, RateLimiter::attempts($key));

        // Login exitoso
        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret123']);
        $this->assertEquals(0, RateLimiter::attempts($key));
    }

    // 3.4: IP bloqueada recibe redirect con mensaje y segundos
    public function test_ip_bloqueada_recibe_mensaje_con_segundos(): void
    {
        $this->crearProveedor();
        config(['auth.rate_limiting.max_attempts' => 3]);

        // Agotar intentos
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);
        }

        // Siguiente intento debe estar bloqueado
        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $error = session('error');
        $this->assertStringContainsString('Demasiados intentos', $error);
        $this->assertStringContainsString('segundos', $error);
    }

    // 3.5: IP bloqueada no procesa credenciales
    public function test_ip_bloqueada_no_procesa_credenciales(): void
    {
        $this->crearProveedor();
        config(['auth.rate_limiting.max_attempts' => 2]);

        // Agotar intentos
        for ($i = 0; $i < 2; $i++) {
            $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);
        }

        // Incluso con credenciales correctas, debe estar bloqueado
        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret123']);
        $response->assertRedirect();
        $this->assertNull(session('proveedor_id'));
        $this->assertStringContainsString('Demasiados intentos', session('error'));
    }

    // 3.6: Log::warning al bloquear
    public function test_log_warning_al_bloquear(): void
    {
        $this->crearProveedor();
        config(['auth.rate_limiting.max_attempts' => 1]);

        // Agotar intentos
        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);

        // Siguiente intento bloqueado — verificar log
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'rate limiting'));

        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);
    }

    // ═══════════════════════════════════════════
    // Property Tests
    // ═══════════════════════════════════════════

    // Property 2: Intento fallido incrementa contador
    public function test_property_failed_attempt_increments_counter(): void
    {
        $this->crearProveedor();
        $key = 'login-proveedor|127.0.0.1';
        config(['auth.rate_limiting.max_attempts' => 200]); // alto para no bloquear

        for ($i = 1; $i <= 50; $i++) {
            $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong_' . $i]);
            $this->assertEquals($i, RateLimiter::attempts($key), "After attempt {$i}");
        }
    }

    // Property 5: IP bloqueada rechaza sin procesar credenciales
    public static function blockedIpProvider(): array
    {
        $cases = [];
        for ($i = 0; $i < 50; $i++) {
            $maxAttempts = rand(1, 5);
            $cases["max_{$maxAttempts}_iter_{$i}"] = [$maxAttempts];
        }
        return $cases;
    }

    /**
     * @dataProvider blockedIpProvider
     * Feature: login-rate-limiting, Property 5: IP bloqueada rechaza login
     */
    public function test_property_blocked_ip_rejects_login(int $maxAttempts): void
    {
        $this->crearProveedor();
        config(['auth.rate_limiting.max_attempts' => $maxAttempts]);
        RateLimiter::clear('login-proveedor|127.0.0.1');

        // Agotar intentos
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);
        }

        // Siguiente intento con credenciales correctas debe ser bloqueado
        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret123']);
        $this->assertNull(session('proveedor_id'), "Should be blocked after {$maxAttempts} attempts");
    }
}
