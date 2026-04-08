<?php

namespace Tests\Unit\Controllers;

use App\Exceptions\ProveedorApiException;
use App\Http\Controllers\ProveedorController;
use App\Models\ProveedorUser;
use App\Services\ProveedorApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProveedorControllerLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = 'http://fake-api.test/api';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.proveedor_api.url' => $this->baseUrl]);
        config(['services.proveedor_api.connect_timeout' => 5]);
        config(['services.proveedor_api.timeout' => 15]);
        config(['services.proveedor_api.max_retries' => 1]);
    }

    private function crearProveedorLocal(string $usuario = 'PROV001', string $pwd = 'secret123'): ProveedorUser
    {
        return ProveedorUser::create([
            'usuario'        => $usuario,
            'password'       => Hash::make($pwd),
            'nombre'         => 'Test Proveedor',
            'codigo_compras' => 'COD001',
            'correo'         => 'test@example.com',
            'tipo_persona'   => 'Moral',
            'telefono'       => '5551234567',
        ]);
    }

    private function fakeApiSuccess(): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([
                'usuario'      => 'PROV001',
                'tokencreado'  => 'jwt-token-abc',
            ], 200),
        ]);
    }

    private function fakeApiAuthFailed(): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([], 401),
        ]);
    }

    private function fakeApiDown(): void
    {
        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([], 500),
        ]);
    }

    // ═══════════════════════════════════════════
    // 6.1: Modo api con API exitosa
    // ═══════════════════════════════════════════

    public function test_modo_api_login_exitoso(): void
    {
        config(['services.proveedor_api.login_mode' => 'api']);
        $this->fakeApiSuccess();

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        $response->assertRedirect('/portal-proveedor');
        $this->assertEquals('api', session('proveedor_login_source'));
        $this->assertEquals('jwt-token-abc', session('proveedor_token'));
    }

    // ═══════════════════════════════════════════
    // 6.2: Modo api con autenticacion_fallida
    // ═══════════════════════════════════════════

    public function test_modo_api_autenticacion_fallida_sin_fallback(): void
    {
        config(['services.proveedor_api.login_mode' => 'api']);
        $this->crearProveedorLocal('PROV001', 'secret');
        $this->fakeApiAuthFailed();

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull(session('proveedor_id'));
    }

    // ═══════════════════════════════════════════
    // 6.3: Modo api con api_caida → error sin fallback
    // ═══════════════════════════════════════════

    public function test_modo_api_caida_sin_fallback(): void
    {
        config(['services.proveedor_api.login_mode' => 'api']);
        $this->crearProveedorLocal('PROV001', 'secret');
        $this->fakeApiDown();

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull(session('proveedor_id'));
    }

    // ═══════════════════════════════════════════
    // 6.4: Modo fallback con API exitosa
    // ═══════════════════════════════════════════

    public function test_modo_fallback_api_exitosa(): void
    {
        config(['services.proveedor_api.login_mode' => 'fallback']);
        $this->fakeApiSuccess();

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        $response->assertRedirect('/portal-proveedor');
        $this->assertEquals('api', session('proveedor_login_source'));
        $this->assertEquals('jwt-token-abc', session('proveedor_token'));
    }

    // ═══════════════════════════════════════════
    // 6.5: Modo fallback con api_caida → BD local exitoso
    // ═══════════════════════════════════════════

    public function test_modo_fallback_api_caida_bd_local_exitoso(): void
    {
        config(['services.proveedor_api.login_mode' => 'fallback']);
        $this->crearProveedorLocal('PROV001', 'secret');
        $this->fakeApiDown();

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        $response->assertRedirect('/portal-proveedor');
        $this->assertEquals('local', session('proveedor_login_source'));
        $this->assertNull(session('proveedor_token'));
    }

    // ═══════════════════════════════════════════
    // 6.6: Modo fallback con api_caida + BD local falla
    // ═══════════════════════════════════════════

    public function test_modo_fallback_api_caida_bd_local_falla(): void
    {
        config(['services.proveedor_api.login_mode' => 'fallback']);
        $this->fakeApiDown();

        $response = $this->post('/login-proveedor', ['codigo' => 'NOEXISTE', 'pwd' => 'wrong']);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Credenciales incorrectas');
        $this->assertNull(session('proveedor_id'));
    }

    // ═══════════════════════════════════════════
    // 6.7: Modo fallback con autenticacion_fallida → NO fallback
    // ═══════════════════════════════════════════

    public function test_modo_fallback_autenticacion_fallida_sin_fallback(): void
    {
        config(['services.proveedor_api.login_mode' => 'fallback']);
        $this->crearProveedorLocal('PROV001', 'secret');
        $this->fakeApiAuthFailed();

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Credenciales incorrectas');
        $this->assertNull(session('proveedor_id'));
    }

    // ═══════════════════════════════════════════
    // 6.8: Modo local → login exitoso sin llamar API
    // ═══════════════════════════════════════════

    public function test_modo_local_login_exitoso(): void
    {
        config(['services.proveedor_api.login_mode' => 'local']);
        $this->crearProveedorLocal('PROV001', 'secret');
        Http::fake(); // Should NOT be called

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        $response->assertRedirect('/portal-proveedor');
        $this->assertEquals('local', session('proveedor_login_source'));
        $this->assertNull(session('proveedor_token'));
        Http::assertNothingSent();
    }

    // ═══════════════════════════════════════════
    // 6.9: Modo local con credenciales incorrectas
    // ═══════════════════════════════════════════

    public function test_modo_local_credenciales_incorrectas(): void
    {
        config(['services.proveedor_api.login_mode' => 'local']);
        $this->crearProveedorLocal('PROV001', 'secret');

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'wrong']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull(session('proveedor_id'));
    }

    // ═══════════════════════════════════════════
    // 6.10: cerrarSesion limpia nuevas claves
    // ═══════════════════════════════════════════

    public function test_cerrar_sesion_limpia_token_y_source(): void
    {
        session([
            'proveedor_id'           => 1,
            'proveedor_nombre'       => 'Test',
            'proveedor_codigo'       => 'COD001',
            'proveedor_correo'       => 'test@example.com',
            'proveedor_token'        => 'jwt-token',
            'proveedor_login_source' => 'api',
        ]);

        $response = $this->post('/logout-proveedor');

        $response->assertRedirect('/login-proveedor');
        $this->assertNull(session('proveedor_token'));
        $this->assertNull(session('proveedor_login_source'));
        $this->assertNull(session('proveedor_id'));
    }

    // ═══════════════════════════════════════════
    // 6.11: Logging
    // ═══════════════════════════════════════════

    public function test_logging_api_exitosa(): void
    {
        config(['services.proveedor_api.login_mode' => 'api']);
        $this->fakeApiSuccess();

        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'Login: exitoso por API'));

        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);
    }

    public function test_logging_fallback_activado(): void
    {
        config(['services.proveedor_api.login_mode' => 'fallback']);
        $this->crearProveedorLocal('PROV001', 'secret');
        $this->fakeApiDown();

        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'fallback'));

        $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);
    }

    // ═══════════════════════════════════════════
    // Property Tests
    // ═══════════════════════════════════════════

    // Property 1: Modo inválido → fallback
    public static function invalidModeProvider(): array
    {
        $cases = [];
        $invalids = ['', 'invalid', 'API', 'LOCAL', 'FALLBACK', 'both', 'none', '123', 'api ', ' local'];
        for ($i = 0; $i < 100; $i++) {
            $mode = $invalids[array_rand($invalids)] . bin2hex(random_bytes(2));
            $cases["mode_{$i}"] = [$mode];
        }
        return $cases;
    }

    /**
     * Feature: login-api-con-fallback, Property 1: Modo inválido resuelve a fallback
     */
    #[DataProvider('invalidModeProvider')]
    public function test_property_invalid_mode_resolves_to_fallback(string $mode): void
    {
        config(['services.proveedor_api.login_mode' => $mode]);

        // With invalid mode, it should behave as fallback (try API first)
        $this->fakeApiSuccess();

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        // Fallback mode tries API first — if API succeeds, login succeeds
        $response->assertRedirect('/portal-proveedor');
        $this->assertEquals('api', session('proveedor_login_source'));
    }

    // Property 5: Modo API nunca hace fallback
    public static function apiModeErrorProvider(): array
    {
        $cases = [];
        $statuses = [401, 500, 502, 503, 504];
        for ($i = 0; $i < 100; $i++) {
            $status = $statuses[array_rand($statuses)];
            $cases["api_error_{$status}_{$i}"] = [$status];
        }
        return $cases;
    }

    /**
     * Feature: login-api-con-fallback, Property 5: Modo API nunca hace fallback
     */
    #[DataProvider('apiModeErrorProvider')]
    public function test_property_api_mode_never_falls_back(int $status): void
    {
        config(['services.proveedor_api.login_mode' => 'api']);
        $this->crearProveedorLocal('PROV001', 'secret');

        Http::fake([
            $this->baseUrl . '/Login/Login' => Http::response([], $status),
        ]);

        $response = $this->post('/login-proveedor', ['codigo' => 'PROV001', 'pwd' => 'secret']);

        // Should never login via local in api mode
        $response->assertRedirect();
        $this->assertNull(session('proveedor_id'));
        $this->assertNull(session('proveedor_login_source'));
    }

    // Property 6: Modo local nunca invoca API
    public function test_property_local_mode_never_calls_api(): void
    {
        for ($i = 0; $i < 50; $i++) {
            config(['services.proveedor_api.login_mode' => 'local']);
            Http::fake();

            $codigo = 'PROV' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $this->post('/login-proveedor', ['codigo' => $codigo, 'pwd' => 'any_password']);

            Http::assertNothingSent();
        }
    }
}
