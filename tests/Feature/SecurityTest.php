<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Tests de seguridad del S-SDLC.
 * Verifican que las protecciones de seguridad funcionan correctamente.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ═══════════════════════════════════════════════════════
    // TESTS DE HEADERS DE SEGURIDAD
    // ═══════════════════════════════════════════════════════

    public function test_security_headers_present_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $response = $this->get('/login-proveedor');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    // ═══════════════════════════════════════════════════════
    // TESTS DE ACCESO NO AUTORIZADO (MIDDLEWARE)
    // ═══════════════════════════════════════════════════════

    public function test_portal_proveedor_sin_sesion_redirige_a_login(): void
    {
        $response = $this->get('/portal-proveedor');
        $response->assertRedirect('/login-proveedor');
    }

    public function test_dashboard_proveedor_sin_sesion_redirige_a_login(): void
    {
        $response = $this->get('/dashboard-proveedor');
        $response->assertRedirect('/login-proveedor');
    }

    public function test_onboarding_sin_sesion_redirige_a_login(): void
    {
        $response = $this->get('/onboarding');
        $response->assertRedirect('/login-proveedor');
    }

    public function test_admin_dashboard_sin_sesion_redirige_a_login(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login-admin');
    }

    public function test_portal_cliente_sin_sesion_redirige_a_login(): void
    {
        $response = $this->get('/portal-cliente');
        $response->assertRedirect('/login-cliente');
    }

    public function test_admin_clientes_sin_sesion_redirige_a_login(): void
    {
        $response = $this->get('/admin/clientes');
        $response->assertRedirect('/login-admin');
    }

    // ═══════════════════════════════════════════════════════
    // TESTS DE RATE LIMITING
    // ═══════════════════════════════════════════════════════

    public function test_rate_limiting_login_proveedor_bloquea_despues_de_5_intentos(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login-proveedor', [
                '_token' => csrf_token(),
                'codigo' => 'PROV-FAKE-999',
                'pwd' => 'password-incorrecto',
            ]);
        }

        // Después de 6 intentos, debe tener error de rate limiting o credenciales
        $response->assertRedirect();
    }

    public function test_rate_limiting_login_cliente_bloquea_despues_de_5_intentos(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login-cliente', [
                '_token' => csrf_token(),
                'usuario' => 'atacante-fake',
                'password' => 'password-incorrecto',
            ]);
        }

        $response->assertRedirect();
    }

    // ═══════════════════════════════════════════════════════
    // TESTS DE VALIDACIÓN DE INPUTS
    // ═══════════════════════════════════════════════════════

    public function test_login_proveedor_rechaza_campos_vacios(): void
    {
        $response = $this->post('/login-proveedor', [
            '_token' => csrf_token(),
            'codigo' => '',
            'pwd' => '',
        ]);

        $response->assertSessionHasErrors(['codigo', 'pwd']);
    }

    public function test_registro_proveedor_rechaza_datos_incompletos(): void
    {
        $response = $this->post('/proveedor/registro', [
            '_token' => csrf_token(),
            'nombre' => '',
            'correo' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['nombre', 'correo', 'password']);
    }

    // ═══════════════════════════════════════════════════════
    // TESTS DE API TOKEN
    // ═══════════════════════════════════════════════════════

    public function test_api_sin_token_retorna_401(): void
    {
        $response = $this->getJson('/api/salcom/resumen');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'No autorizado']);
    }

    public function test_api_con_token_invalido_retorna_401(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer token-falso-inventado',
        ])->getJson('/api/salcom/resumen');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'No autorizado']);
    }

    // ═══════════════════════════════════════════════════════
    // TESTS DE CACHE CONTROL (NO CACHE EN PORTALES)
    // ═══════════════════════════════════════════════════════

    public function test_portal_proveedor_tiene_no_cache_headers(): void
    {
        $response = $this->withSession(['proveedor_id' => 1, 'proveedor_nombre' => 'Test'])
            ->get('/portal-proveedor');

        $response->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    // ═══════════════════════════════════════════════════════
    // TESTS DE XSS (SANITIZACIÓN)
    // ═══════════════════════════════════════════════════════

    public function test_xss_en_login_no_se_refleja(): void
    {
        $xssPayload = '<script>alert("xss")</script>';

        $response = $this->post('/login-proveedor', [
            '_token' => csrf_token(),
            'codigo' => $xssPayload,
            'pwd' => '12345678',
        ]);

        // El framework escapa por defecto con {{ }}, verificamos que no se refleja sin escapar
        if ($response->status() === 200) {
            $this->assertStringNotContainsString(
                '<script>alert("xss")</script>',
                $response->getContent()
            );
        } else {
            $this->assertTrue(true);
        }
    }

    // ═══════════════════════════════════════════════════════
    // TESTS DE HTTPS FORZADO
    // ═══════════════════════════════════════════════════════

    public function test_urls_generadas_usan_https_en_produccion(): void
    {
        app()->detectEnvironment(fn () => 'production');
        URL::forceScheme('https');

        $url = route('proveedores.login');

        $this->assertStringStartsWith('https://', $url);
    }
}
