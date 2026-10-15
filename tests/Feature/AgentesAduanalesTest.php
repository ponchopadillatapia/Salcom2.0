<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AgenteAduanal;
use App\Models\AnticipoAgenteAduanal;
use App\Models\FacturaAgenteAduanal;
use App\Models\OperacionComercioExterior;
use App\Models\PagoAgenteAduanal;
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

    private function agente(array $extra = []): AgenteAduanal
    {
        return AgenteAduanal::create(array_merge([
            'nombre' => 'Mario Alfonso Sánchez',
            'rfc' => 'MASG030919844',
            'numero_patente' => '2223',
            'tipo_operacion' => 'importacion',
            'activo' => true,
        ], $extra));
    }

    private function datosFactura(AgenteAduanal $agente, array $extra = []): array
    {
        return array_merge([
            'agente_aduanal_id' => $agente->id,
            'tipo_operacion' => 'importacion',
            'fecha' => '2026-10-08',
            'serie' => 'A',
            'folio' => '1914',
            'moneda' => 'MXN',
            'importe' => '20000.00',
            'saldo_pendiente' => '15000.00',
            'honorarios' => '8000.00',
            'impuestos' => '1280.00',
            'retenciones' => '800.00',
            'estado_cancelacion' => 'vigente',
            'estado_pago' => 'pendiente',
            'sentido' => '',
            'archivo' => UploadedFile::fake()->createWithContent('factura.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"),
        ], $extra);
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

    public function test_listado_consulta_facturas_y_no_el_catalogo_de_agentes(): void
    {
        $agente = $this->agente();
        $sesion = $this->sesion();
        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($agente))
            ->assertRedirect();

        $this->withSession($sesion)
            ->get('/admin/agentes-aduanales')
            ->assertOk()
            ->assertSee('Facturas de agentes aduanales')
            ->assertSee('>Serie<', false)
            ->assertSee('>Folio<', false)
            ->assertDontSee('Serie y folio')
            ->assertSee('Saldo pendiente')
            ->assertSee('1914')
            ->assertSee('Mario Alfonso Sánchez')
            ->assertDontSee('Crear agente')
            ->assertDontSee('Desactivar');
    }

    public function test_el_total_positivo_no_define_quien_paga(): void
    {
        $agente = $this->agente();
        $sesion = $this->sesion();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($agente))
            ->assertRedirect();

        $factura = FacturaAgenteAduanal::first();
        $this->assertNotNull($factura);
        $this->assertSame('20000.00', $factura->importe);
        $this->assertSame('8000.00', $factura->honorarios);
        $this->assertSame('1280.00', $factura->impuestos);
        $this->assertNull($factura->retenciones);
        $this->assertSame('15000.00', $factura->saldo_pendiente);
        $this->assertNull($factura->sentido);
        $this->assertNull($factura->observaciones);
        $this->assertNotSame(
            number_format((float) $factura->honorarios + (float) $factura->impuestos - (float) $factura->retenciones, 2, '.', ''),
            $factura->importe
        );

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($agente, [
                'folio' => '2000',
                'importe' => '500.00',
                'sentido' => 'agente_paga_salcom',
                'observaciones' => 'no se guarda',
                'honorarios' => '',
                'impuestos' => '',
                'retenciones' => '',
                'saldo_pendiente' => '',
            ]))
            ->assertRedirect();

        $segunda = FacturaAgenteAduanal::where('folio', '2000')->first();
        $this->assertNull($segunda->sentido);
        $this->assertNull($segunda->observaciones);
        $this->assertNull($segunda->honorarios);
        $this->assertNull($segunda->retenciones);
    }

    public function test_filtros_de_agente_fecha_moneda_y_estado(): void
    {
        $agente = $this->agente();
        $otro = $this->agente([
            'nombre' => 'Alejandro Martínez',
            'rfc' => 'MARA030609844',
            'numero_patente' => '8969',
            'tipo_operacion' => 'exportacion',
        ]);
        $sesion = $this->sesion();

        $this->withSession($sesion)->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($agente));
        $this->withSession($sesion)->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($otro, [
            'folio' => '77',
            'serie' => 'B',
            'moneda' => 'USD',
            'fecha' => '2026-09-01',
            'tipo_operacion' => 'exportacion',
            'estado_pago' => 'pagado',
        ]));

        $this->withSession($sesion)
            ->get('/admin/agentes-aduanales?agente_id='.$otro->id.'&moneda=USD&estado=pagado&desde=2026-09-01&hasta=2026-09-30')
            ->assertOk()
            ->assertSee('77')
            ->assertDontSee('1914');
    }

    public function test_anticipo_se_liga_a_la_operacion_antes_de_la_factura_y_del_pago(): void
    {
        $agente = $this->agente();
        $sesion = $this->sesion();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.anticipos.guardar'), [
                'agente_aduanal_id' => $agente->id,
                'tipo_operacion' => 'exportacion',
                'fecha_anticipo' => '2026-10-01',
                'importe_anticipo' => '4000',
                'moneda_anticipo' => 'MXN',
                'referencia_anticipo' => 'ANT-1',
            ])
            ->assertRedirect(route('admin.agentes-aduanales'));

        $anticipo = AnticipoAgenteAduanal::first();
        $this->assertNotNull($anticipo);
        $this->assertNull($anticipo->factura_id);
        $this->assertNotNull($anticipo->operacion_id);
        $this->assertSame(0, PagoAgenteAduanal::count());

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($agente, [
                'operacion_id' => $anticipo->operacion_id,
                'tipo_operacion' => '',
                'folio' => '300',
            ]))
            ->assertRedirect();

        $anticipo->refresh();
        $factura = FacturaAgenteAduanal::where('folio', '300')->first();
        $this->assertSame($factura->id, $anticipo->factura_id);
        $this->assertSame($anticipo->operacion_id, $factura->operacion_id);
        $this->assertSame(0, PagoAgenteAduanal::count());
    }

    public function test_el_pago_exige_direccion_y_no_cambia_saldo_ni_sentido(): void
    {
        $agente = $this->agente();
        $sesion = $this->sesion();
        $this->withSession($sesion)->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($agente));
        $factura = FacturaAgenteAduanal::first();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.anticipos.ligar', $factura), [
                'fecha_anticipo' => '2026-10-02',
                'importe_anticipo' => '1000',
                'moneda_anticipo' => 'MXN',
            ])
            ->assertRedirect(route('admin.agentes-aduanales.ver', $factura));

        $anticipo = AnticipoAgenteAduanal::first();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.pagos.guardar', $factura), [
                'fecha_pago' => '2026-10-09',
                'importe_pago' => '5000',
                'moneda_pago' => 'MXN',
                'anticipo_id' => $anticipo->id,
            ])
            ->assertSessionHasErrors('direccion');

        $this->assertSame(0, PagoAgenteAduanal::count());

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.pagos.guardar', $factura), [
                'fecha_pago' => '2026-10-09',
                'importe_pago' => '5000',
                'moneda_pago' => 'MXN',
                'direccion' => 'salcom_paga_agente',
                'anticipo_id' => $anticipo->id,
            ])
            ->assertRedirect(route('admin.agentes-aduanales.ver', $factura));

        $factura->refresh();
        $this->assertSame('15000.00', $factura->saldo_pendiente);
        $this->assertNull($factura->sentido);
        $this->assertSame('pendiente', $factura->estado_pago);
        $this->assertSame('salcom_paga_agente', $factura->pagos()->first()->direccion);
        $this->assertSame($anticipo->id, $factura->pagos()->first()->anticipo_id);
    }

    public function test_el_pago_rechaza_un_anticipo_de_otra_factura(): void
    {
        $agente = $this->agente();
        $sesion = $this->sesion();
        $this->withSession($sesion)->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($agente));
        $this->withSession($sesion)->post(route('admin.agentes-aduanales.guardar'), $this->datosFactura($agente, ['folio' => '2']));

        $primera = FacturaAgenteAduanal::where('folio', '1914')->first();
        $segunda = FacturaAgenteAduanal::where('folio', '2')->first();

        $this->withSession($sesion)->post(route('admin.agentes-aduanales.anticipos.ligar', $segunda), [
            'fecha_anticipo' => '2026-10-02',
            'importe_anticipo' => '100',
            'moneda_anticipo' => 'MXN',
        ]);
        $anticipoAjeno = AnticipoAgenteAduanal::first();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.pagos.guardar', $primera), [
                'fecha_pago' => '2026-10-09',
                'importe_pago' => '100',
                'moneda_pago' => 'MXN',
                'direccion' => 'agente_paga_salcom',
                'anticipo_id' => $anticipoAjeno->id,
            ])
            ->assertSessionHasErrors('anticipo_id');

        $this->assertSame(0, PagoAgenteAduanal::count());
    }

    public function test_las_rutas_de_alta_y_baja_de_agentes_ya_no_existen(): void
    {
        $sesion = $this->sesion();

        $this->withSession($sesion)->get('/admin/agentes-aduanales/crear')->assertNotFound();
        $this->withSession($sesion)->post('/admin/agentes-aduanales', [
            'nombre' => 'Alguien',
            'rfc' => 'XAXX010101000',
            'numero_patente' => '1642',
        ])->assertStatus(405);
    }

    public function test_se_puede_descargar_el_archivo_de_la_factura(): void
    {
        Storage::fake('local');
        $agente = $this->agente();
        $sesion = $this->sesion();

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.guardar'), array_merge($this->datosFactura($agente), [
                'archivo' => UploadedFile::fake()->createWithContent('factura.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF"),
            ]))
            ->assertRedirect();

        $factura = FacturaAgenteAduanal::first();
        $this->assertNotNull($factura->ruta);
        $this->assertSame(1, OperacionComercioExterior::count());

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.archivo', $factura))
            ->assertOk();
    }

    public function test_el_pdf_llena_la_factura_y_no_decide_quien_paga(): void
    {
        $agente = $this->agente([
            'nombre' => 'Alejandro Martinez Ramos',
            'rfc' => 'MARA030609844',
            'numero_patente' => '8969',
        ]);
        $sesion = $this->sesion();
        $contenido = "%PDF-1.4\n".$this->xmlFacturaAgente();

        $this->withSession($sesion)
            ->get(route('admin.agentes-aduanales.crear'))
            ->assertOk()
            ->assertSee('PDF de la factura')
            ->assertSee('id="aa-datos" hidden', false)
            ->assertSee('Para completar')
            ->assertSee('leer-pdf', false);

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.facturas.leer'), [
                'archivo' => UploadedFile::fake()->createWithContent('agente.pdf', $contenido),
            ])
            ->assertOk()
            ->assertJsonPath('datos.folio', '1914')
            ->assertJsonPath('datos.serie', 'A')
            ->assertJsonPath('datos.importe', '8480.00')
            ->assertJsonPath('datos.honorarios', '8000.00')
            ->assertJsonPath('datos.impuestos', '1280.00')
            ->assertJsonPath('datos.agente_aduanal_id', $agente->id)
            ->assertJsonPath('datos.nombre_agente', 'Alejandro Martinez Ramos')
            ->assertJsonMissingPath('datos.sentido')
            ->assertJsonMissingPath('datos.saldo_pendiente');

        $this->withSession($sesion)
            ->post(route('admin.agentes-aduanales.guardar'), [
                'archivo' => UploadedFile::fake()->createWithContent('agente.pdf', $contenido),
            ])
            ->assertRedirect();

        $factura = FacturaAgenteAduanal::where('folio', '1914')->first();
        $this->assertNotNull($factura);
        $this->assertSame('8480.00', $factura->importe);
        $this->assertSame('8000.00', $factura->honorarios);
        $this->assertSame('1280.00', $factura->impuestos);
        $this->assertNull($factura->retenciones);
        $this->assertNull($factura->observaciones);
        $this->assertNull($factura->saldo_pendiente);
        $this->assertNull($factura->sentido);
        $this->assertSame('pendiente', $factura->estado_pago);
        $this->assertSame($agente->id, $factura->agente_aduanal_id);
        $this->assertSame('importacion', $factura->operacion->tipo_operacion);
        $this->assertSame(0, PagoAgenteAduanal::count());
    }

    private function xmlFacturaAgente(): string
    {
        return <<<'XML'
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" Version="4.0" Serie="A" Folio="1914" Fecha="2026-10-08T10:00:00" SubTotal="8000.00" Total="8480.00" Moneda="MXN" TipoDeComprobante="I">
  <cfdi:Emisor Rfc="MARA030609844" Nombre="ALEJANDRO MARTINEZ RAMOS" RegimenFiscal="612"/>
  <cfdi:Receptor Rfc="ISA951017A10" Nombre="INDUSTRIAS SALCOM" UsoCFDI="G03"/>
  <cfdi:Conceptos>
    <cfdi:Concepto ClaveProdServ="80151600" Cantidad="1" ClaveUnidad="E48" Descripcion="Honorarios por importación" ValorUnitario="8000.00" Importe="8000.00"/>
  </cfdi:Conceptos>
  <cfdi:Impuestos TotalImpuestosTrasladados="1280.00" TotalImpuestosRetenidos="800.00">
    <cfdi:Traslados>
      <cfdi:Traslado Base="8000.00" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="1280.00"/>
    </cfdi:Traslados>
    <cfdi:Retenciones>
      <cfdi:Retencion Impuesto="002" Importe="800.00"/>
    </cfdi:Retenciones>
  </cfdi:Impuestos>
  <cfdi:Complemento>
    <tfd:TimbreFiscalDigital UUID="12345678-1234-1234-1234-123456789012"/>
  </cfdi:Complemento>
</cfdi:Comprobante>
XML;
    }
}
