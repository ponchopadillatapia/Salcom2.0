<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Aduana;
use App\Models\AgenteAduanal;
use App\Models\DocumentoAgenteAduanal;
use App\Models\EncargoConferido;
use App\Models\OperacionComercioExterior;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgentesAduanalesTest extends TestCase
{
    use RefreshDatabase;

    private function sesion(array $atributos = []): array
    {
        $admin = AdminUser::create(array_merge([
            'nombre' => 'Dirección Salcom',
            'correo' => 'direccion@salcom.test',
            'usuario' => 'direccion.test',
            'password' => Hash::make('secreto'),
            'activo' => true,
            'rol' => 'admin',
        ], $atributos));

        return [
            'admin_id' => $admin->id,
            'admin_nombre' => $admin->nombre,
            'admin_usuario' => $admin->usuario,
        ];
    }

    private function aduana(string $clave): Aduana
    {
        return Aduana::where('clave', $clave)->firstOrFail();
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'nombre' => 'Juan Pérez López',
            'rfc' => 'PELJ800101AB1',
            'numero_patente' => '1642',
            'agencia' => 'Agencia del Pacífico',
            'tipo_operacion' => 'ambas',
            'contacto_nombre' => 'Laura Méndez',
            'contacto_correo' => 'laura@ejemplo.com',
            'contacto_telefono' => '3141234567',
            'contacto_celular' => '3147654321',
            'activo' => '1',
            'estado_verificacion' => 'no_verificado',
            'fecha_ultima_verificacion' => '2026-09-30',
            'fuente_verificacion' => 'sat',
            'aduanas' => [$this->aduana('16')->id, $this->aduana('43')->id],
        ], $extra);
    }

    private function pdf(string $nombre = 'patente.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");
    }

    public function test_invitado_no_entra_al_modulo(): void
    {
        $this->get('/admin/agentes-aduanales')->assertRedirect('/login-admin');
    }

    public function test_usuario_restringido_no_entra(): void
    {
        $sesion = $this->sesion([
            'correo' => 'brenda@salcom.test',
            'usuario' => 'brenda.pliego',
            'rol' => 'compras_nacional',
        ]);

        $this->withSession($sesion)
            ->get('/admin/agentes-aduanales')
            ->assertRedirect('/admin/productos');
    }

    public function test_usuario_sin_permiso_recibe_403(): void
    {
        $sesion = $this->sesion([
            'correo' => 'consulta@salcom.test',
            'usuario' => 'consulta.interna',
            'rol' => 'consulta',
        ]);

        $this->withSession($sesion)
            ->get('/admin/agentes-aduanales')
            ->assertForbidden();
    }

    public function test_listado_muestra_catalogo_y_aviso(): void
    {
        $this->withSession($this->sesion())
            ->get('/admin/agentes-aduanales')
            ->assertOk()
            ->assertSee('Agentes Aduanales')
            ->assertSee('Manzanillo')
            ->assertSee('no comprueba que esté vigente')
            ->assertDontSee('Consultar en el SAT');
    }

    public function test_formularios_de_alta_y_edicion_cargan(): void
    {
        $sesion = $this->sesion();

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.crear'))
            ->assertOk()
            ->assertSee('Crear agente')
            ->assertSee('Número de patente aduanal')
            ->assertSee('No se consulta el padrón al guardarlo')
            ->assertSee('Manzanillo');

        $this->withSession($sesion)->post('/admin/agentes-aduanales', $this->datos());
        $agente = AgenteAduanal::first();

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.editar', $agente))
            ->assertOk()
            ->assertSee('Editar agente aduanal')
            ->assertSee('value="1642"', false)
            ->assertSee('Juan Pérez López');
    }

    public function test_patente_es_obligatoria_y_no_marca_verificado(): void
    {
        $sesion = $this->sesion();

        $this->withSession($sesion)
            ->post('/admin/agentes-aduanales', $this->datos(['numero_patente' => '']))
            ->assertSessionHasErrors('numero_patente');

        $this->withSession($sesion)
            ->post('/admin/agentes-aduanales', $this->datos(['numero_patente' => '16']))
            ->assertSessionHasErrors('numero_patente');

        $this->withSession($sesion)
            ->post('/admin/agentes-aduanales', $this->datos())
            ->assertRedirect();

        $agente = AgenteAduanal::where('numero_patente', '1642')->first();
        $this->assertNotNull($agente);
        $this->assertSame('no_verificado', $agente->estado_verificacion);
        $this->assertNull($agente->fecha_ultima_verificacion);
        $this->assertNull($agente->fuente_verificacion);
        $this->assertTrue($agente->activo);
        $this->assertCount(2, $agente->aduanas);
        $this->assertEqualsCanonicalizing(['16', '43'], $agente->aduanas->pluck('clave')->all());

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.ver', $agente))
            ->assertOk()
            ->assertSee('No verificado')
            ->assertSee('16 — Manzanillo, Colima')
            ->assertSee('43 — Veracruz, Veracruz');
    }

    public function test_verificado_exige_fecha_y_fuente_sin_consultar_al_sat(): void
    {
        $sesion = $this->sesion();

        $this->withSession($sesion)
            ->post('/admin/agentes-aduanales', $this->datos([
                'estado_verificacion' => 'verificado',
                'fecha_ultima_verificacion' => null,
                'fuente_verificacion' => null,
            ]))
            ->assertSessionHasErrors(['fecha_ultima_verificacion', 'fuente_verificacion']);

        $this->withSession($sesion)
            ->post('/admin/agentes-aduanales', $this->datos([
                'estado_verificacion' => 'verificado',
                'fecha_ultima_verificacion' => '2026-09-30',
                'fuente_verificacion' => 'sat',
            ]))
            ->assertRedirect()
            ->assertSessionHas('mensaje');

        $agente = AgenteAduanal::first();
        $this->assertSame('verificado', $agente->estado_verificacion);
        $this->assertSame('sat', $agente->fuente_verificacion);
        $this->assertSame('2026-09-30', $agente->fecha_ultima_verificacion->toDateString());

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.ver', $agente))
            ->assertSee('Verificado')
            ->assertSee('30/09/2026')
            ->assertSee('SAT — Padrón de Agentes y Apoderados Aduanales')
            ->assertSee('no la consultó');
    }

    public function test_desactivar_y_reactivar_no_cambia_la_verificacion(): void
    {
        $sesion = $this->sesion();
        $this->withSession($sesion)->post('/admin/agentes-aduanales', $this->datos([
            'estado_verificacion' => 'verificado',
            'fecha_ultima_verificacion' => '2026-09-30',
            'fuente_verificacion' => 'anam',
        ]));
        $agente = AgenteAduanal::first();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.desactivar', $agente))
            ->assertRedirect();

        $agente->refresh();
        $this->assertFalse($agente->activo);
        $this->assertSame('verificado', $agente->estado_verificacion);

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.reactivar', $agente))
            ->assertSessionHas('mensaje');

        $agente->refresh();
        $this->assertTrue($agente->activo);
        $this->assertSame('verificado', $agente->estado_verificacion);
    }

    public function test_documento_privado_rechaza_llaves_y_efirma(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $sesion = $this->sesion();
        $this->withSession($sesion)->post('/admin/agentes-aduanales', $this->datos());
        $agente = AgenteAduanal::first();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.documentos.guardar', $agente), [
                'tipo' => 'patente',
                'archivo' => UploadedFile::fake()->create('llave.key', 20, 'text/plain'),
            ])
            ->assertSessionHasErrors('archivo');

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.documentos.guardar', $agente), [
                'tipo' => 'patente',
                'archivo' => UploadedFile::fake()->create('certificado.cer', 20, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('archivo');

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.documentos.guardar', $agente), [
                'tipo' => 'patente',
                'archivo' => $this->pdf('mi_efirma.pdf'),
            ])
            ->assertSessionHasErrors('archivo');

        $this->assertSame(0, DocumentoAgenteAduanal::count());

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.documentos.guardar', $agente), [
                'tipo' => 'patente',
                'archivo' => $this->pdf('patente-1642.pdf'),
                'fecha_vencimiento' => '2027-01-15',
                'observaciones_documento' => 'Copia de la patente',
            ])
            ->assertRedirect();

        $documento = DocumentoAgenteAduanal::first();
        $this->assertNotNull($documento);
        $this->assertStringStartsWith('agentes-aduanales/'.$agente->id.'/documentos/', $documento->ruta);
        $this->assertStringEndsWith('.pdf', $documento->ruta);
        Storage::disk('local')->assertExists($documento->ruta);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->withSession(['admin_id' => null])
            ->get(route('admin.agentes-aduanales.documentos.descargar', [$agente, $documento]))
            ->assertRedirect('/login-admin');

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.documentos.descargar', [$agente, $documento]))
            ->assertOk()
            ->assertDownload('patente-1642.pdf');

        $otro = AgenteAduanal::create([
            'nombre' => 'Ana Ruiz',
            'rfc' => 'RUAA850202XY3',
            'numero_patente' => '2201',
            'tipo_operacion' => 'importacion',
            'activo' => true,
            'estado_verificacion' => 'no_verificado',
        ]);

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.documentos.descargar', [$otro, $documento]))
            ->assertNotFound();
    }

    public function test_encargo_es_interno_y_puede_mostrarse_vencido(): void
    {
        $sesion = $this->sesion();
        $this->withSession($sesion)->post('/admin/agentes-aduanales', $this->datos());
        $agente = AgenteAduanal::first();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.encargos.guardar', $agente), [
                'fecha_inicio' => now()->subDays(20)->toDateString(),
                'fecha_termino' => now()->subDay()->toDateString(),
                'estado' => 'aceptado',
                'numero_acuse' => 'ACUSE-INTERNO-01',
                'observaciones' => 'Capturado del acuse que entregó el agente',
            ])
            ->assertRedirect()
            ->assertSessionHas('mensaje');

        $encargo = EncargoConferido::first();
        $this->assertSame('1642', $encargo->numero_patente);
        $this->assertSame('aceptado', $encargo->estado);
        $this->assertSame('vencido', $encargo->estadoEfectivo());

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.ver', $agente))
            ->assertSee('No se envió ningún trámite al SAT')
            ->assertSee('Salcom no envía este trámite al SAT')
            ->assertSee('se muestra como Vencido')
            ->assertSee('ACUSE-INTERNO-01');
    }

    public function test_la_relacion_de_operaciones_queda_visible_sin_modulo_de_pedimentos(): void
    {
        $sesion = $this->sesion();
        $this->withSession($sesion)->post('/admin/agentes-aduanales', $this->datos());
        $agente = AgenteAduanal::first();
        $aduana = $this->aduana('16');

        OperacionComercioExterior::create([
            'agente_aduanal_id' => $agente->id,
            'aduana_id' => $aduana->id,
            'numero_patente' => '1642',
            'numero_pedimento' => '261634566000123',
            'fecha' => '2026-09-30',
            'contraparte' => 'Proveedor Demo',
            'mercancia' => 'Resina',
            'tipo_operacion' => 'importacion',
        ]);

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.ver', $agente))
            ->assertOk()
            ->assertSee('Aún no existe un módulo de pedimentos')
            ->assertSee('261634566000123')
            ->assertSee('Resina')
            ->assertDontSee('Registrar pedimento');
    }

    public function test_buscador_filtra_por_patente(): void
    {
        $sesion = $this->sesion();
        $this->withSession($sesion)->post('/admin/agentes-aduanales', $this->datos());
        $this->withSession($sesion)->post('/admin/agentes-aduanales', $this->datos([
            'nombre' => 'María Soto',
            'rfc' => 'SOTM900101CD2',
            'numero_patente' => '3088',
            'tipo_operacion' => 'exportacion',
            'aduanas' => [$this->aduana('40')->id],
        ]));

        $this->withSession($sesion)
            ->get('/admin/agentes-aduanales?q=3088')
            ->assertOk()
            ->assertSee('María Soto')
            ->assertDontSee('Juan Pérez López');
    }
}
