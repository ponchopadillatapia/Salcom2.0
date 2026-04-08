<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_404_muestra_pagina_personalizada(): void
    {
        $response = $this->get('/ruta-que-no-existe-xyz');

        $response->assertStatus(404);
        $response->assertSee('Industrias Salcom');
        $response->assertSee('404');
        $response->assertSee('no encontrada');
        $response->assertSee('Volver al portal');
    }
}
