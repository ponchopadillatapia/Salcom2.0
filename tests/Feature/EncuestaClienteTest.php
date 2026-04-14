<?php

namespace Tests\Feature;

use App\Models\Encuesta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncuestaClienteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simula una sesión de cliente autenticado.
     */
    private function sesionCliente(array $extra = []): array
    {
        return array_merge([
            'cliente_id'     => 1,
            'cliente_codigo' => 'CLI-001',
        ], $extra);
    }

    public function test_guardar_encuesta_completa_en_bd(): void
    {
        $response = $this->withSession($this->sesionCliente())
            ->post('/cliente/encuesta', [
                'calificacion'     => 4,
                'tiempo_entrega'   => 'rapido',
                'calidad_producto' => 'excelente',
                'comentarios'      => 'Muy buen servicio',
            ]);

        $response->assertRedirect(route('clientes.encuesta'));
        $response->assertSessionHas('encuesta_guardada', true);

        $this->assertDatabaseHas('encuestas', [
            'codigo_cliente'   => 'CLI-001',
            'calificacion'     => 4,
            'tiempo_entrega'   => 1, // rapido => 1
            'calidad_producto' => 1, // excelente => 1
            'comentarios'      => 'Muy buen servicio',
        ]);
    }

    public function test_guardar_encuesta_con_pedido_id(): void
    {
        $response = $this->withSession($this->sesionCliente())
            ->post('/cliente/encuesta', [
                'calificacion'     => 5,
                'tiempo_entrega'   => 'normal',
                'calidad_producto' => 'buena',
                'comentarios'      => null,
                'pedido_id'        => 42,
            ]);

        $response->assertRedirect(route('clientes.encuesta'));

        $this->assertDatabaseHas('encuestas', [
            'codigo_cliente'   => 'CLI-001',
            'pedido_id'        => 42,
            'calificacion'     => 5,
            'tiempo_entrega'   => 2, // normal => 2
            'calidad_producto' => 2, // buena => 2
        ]);
    }

    public function test_guardar_encuesta_sin_comentarios(): void
    {
        $response = $this->withSession($this->sesionCliente())
            ->post('/cliente/encuesta', [
                'calificacion'     => 3,
                'tiempo_entrega'   => 'lento',
                'calidad_producto' => 'regular',
            ]);

        $response->assertRedirect(route('clientes.encuesta'));

        $encuesta = Encuesta::first();
        $this->assertNotNull($encuesta);
        $this->assertEquals('CLI-001', $encuesta->codigo_cliente);
        $this->assertEquals(3, $encuesta->calificacion);
        $this->assertEquals(3, $encuesta->tiempo_entrega);   // lento => 3
        $this->assertEquals(3, $encuesta->calidad_producto);  // regular => 3
        $this->assertNull($encuesta->comentarios);
    }

    public function test_validacion_calificacion_requerida(): void
    {
        $response = $this->withSession($this->sesionCliente())
            ->post('/cliente/encuesta', [
                'tiempo_entrega'   => 'normal',
                'calidad_producto' => 'buena',
            ]);

        $response->assertSessionHasErrors('calificacion');
        $this->assertDatabaseCount('encuestas', 0);
    }

    public function test_validacion_tiempo_entrega_invalido(): void
    {
        $response = $this->withSession($this->sesionCliente())
            ->post('/cliente/encuesta', [
                'calificacion'     => 4,
                'tiempo_entrega'   => 'invalido',
                'calidad_producto' => 'buena',
            ]);

        $response->assertSessionHasErrors('tiempo_entrega');
        $this->assertDatabaseCount('encuestas', 0);
    }

    public function test_validacion_calidad_producto_invalida(): void
    {
        $response = $this->withSession($this->sesionCliente())
            ->post('/cliente/encuesta', [
                'calificacion'     => 4,
                'tiempo_entrega'   => 'normal',
                'calidad_producto' => 'invalido',
            ]);

        $response->assertSessionHasErrors('calidad_producto');
        $this->assertDatabaseCount('encuestas', 0);
    }

    public function test_redirige_sin_sesion_cliente(): void
    {
        $response = $this->post('/cliente/encuesta', [
            'calificacion'     => 5,
            'tiempo_entrega'   => 'normal',
            'calidad_producto' => 'excelente',
        ]);

        $response->assertRedirect('/login-cliente');
        $this->assertDatabaseCount('encuestas', 0);
    }

    public function test_mensaje_exito_visible_despues_de_guardar(): void
    {
        $response = $this->withSession($this->sesionCliente())
            ->post('/cliente/encuesta', [
                'calificacion'     => 5,
                'tiempo_entrega'   => 'rapido',
                'calidad_producto' => 'excelente',
                'comentarios'      => 'Perfecto',
            ]);

        // Seguir la redirección para verificar que se muestra el mensaje de éxito
        $followUp = $this->withSession($this->sesionCliente())
            ->withSession(['encuesta_guardada' => true])
            ->get('/cliente/encuesta');

        $followUp->assertSee('¡Gracias por tu opinión!');
        $followUp->assertSee('Tu retroalimentación nos ayuda a mejorar');
    }
}
