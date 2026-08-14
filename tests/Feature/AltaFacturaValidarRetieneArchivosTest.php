<?php

namespace Tests\Feature;

use App\Models\Factura;
use App\Models\ProveedorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AltaFacturaValidarRetieneArchivosTest extends TestCase
{
    use RefreshDatabase;

    private function crearProveedor(): ProveedorUser
    {
        return ProveedorUser::create([
            'usuario' => 'PROVXML',
            'password' => Hash::make('secret123'),
            'nombre' => 'Proveedor XML SA',
            'id_proveedor' => 'ADMIN-CODXML',
            'correo' => 'xml@test.com',
            'tipo_persona' => 'Fisica',
            'telefono' => '5551234567',
            'activo' => true,
            'datos_identificacion' => ['rfc' => 'XAXX010101000'],
        ]);
    }

    public function test_validar_conserva_archivos_en_sesion_aunque_rechace(): void
    {
        Storage::fake('local');

        $proveedor = $this->crearProveedor();
        $pdf = UploadedFile::fake()->createWithContent('factura.pdf', '%PDF-1.4 demo');
        $xml = UploadedFile::fake()->createWithContent('factura.xml', '<?xml version="1.0"?><not-a-cfdi/>');

        $response = $this->withSession(['proveedor_id' => $proveedor->id])
            ->post(route('proveedores.fiscal.validar'), [
                'archivo' => $pdf,
                'archivo_xml' => $xml,
                'es_fletera' => '0',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('fiscal_resultado');
        $response->assertSessionHas('fiscal_pendiente');

        $pendiente = session('fiscal_pendiente');
        $this->assertIsArray($pendiente);
        $this->assertFalse((bool) ($pendiente['aprobado'] ?? true));
        $this->assertSame('factura.pdf', $pendiente['nombre_pdf']);
        $this->assertSame('factura.xml', $pendiente['nombre_xml']);
        $this->assertTrue(Storage::disk('local')->exists($pendiente['path_pdf']));
        $this->assertTrue(Storage::disk('local')->exists($pendiente['path_xml']));
    }

    public function test_revalidar_sin_readjuntar_usa_temporales(): void
    {
        Storage::fake('local');

        $proveedor = $this->crearProveedor();
        $pdf = UploadedFile::fake()->createWithContent('factura.pdf', '%PDF-1.4 demo');
        $xml = UploadedFile::fake()->createWithContent('factura.xml', '<?xml version="1.0"?><not-a-cfdi/>');

        $this->withSession(['proveedor_id' => $proveedor->id])
            ->post(route('proveedores.fiscal.validar'), [
                'archivo' => $pdf,
                'archivo_xml' => $xml,
                'es_fletera' => '0',
            ]);

        $this->assertNotNull(session('fiscal_pendiente'));

        $response = $this->withSession([
            'proveedor_id' => $proveedor->id,
            'fiscal_pendiente' => session('fiscal_pendiente'),
        ])->post(route('proveedores.fiscal.validar'), [
            'es_fletera' => '0',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('fiscal_pendiente');
        $this->assertSame('factura.pdf', session('fiscal_pendiente.nombre_pdf'));
    }

    public function test_get_inmediato_tras_validar_sigue_mostrando_archivos(): void
    {
        Storage::fake('local');

        $proveedor = $this->crearProveedor();
        $pdf = UploadedFile::fake()->createWithContent('factura.pdf', '%PDF-1.4 demo');
        $xml = UploadedFile::fake()->createWithContent('factura.xml', '<?xml version="1.0"?><not-a-cfdi/>');

        $this->withSession(['proveedor_id' => $proveedor->id])
            ->post(route('proveedores.fiscal.validar'), [
                'archivo' => $pdf,
                'archivo_xml' => $xml,
                'es_fletera' => '0',
            ])
            ->assertRedirect();

        $this->get(route('proveedores.fiscal'))
            ->assertOk()
            ->assertViewHas('tieneArchivosPendientes', true)
            ->assertSee('factura.pdf')
            ->assertSee('factura.xml');

        $this->assertNotNull(session('fiscal_pendiente'));
    }

    public function test_recargar_o_volver_a_entrar_limpia_archivos_temporales(): void
    {
        Storage::fake('local');

        $proveedor = $this->crearProveedor();
        $pdf = UploadedFile::fake()->createWithContent('factura.pdf', '%PDF-1.4 demo');
        $xml = UploadedFile::fake()->createWithContent('factura.xml', '<?xml version="1.0"?><not-a-cfdi/>');

        $this->withSession(['proveedor_id' => $proveedor->id])
            ->post(route('proveedores.fiscal.validar'), [
                'archivo' => $pdf,
                'archivo_xml' => $xml,
                'es_fletera' => '0',
            ])
            ->assertRedirect();

        $this->get(route('proveedores.fiscal'))
            ->assertOk()
            ->assertViewHas('tieneArchivosPendientes', true);

        $pendiente = session('fiscal_pendiente');
        $this->assertIsArray($pendiente);

        $this->get(route('proveedores.fiscal'))
            ->assertOk()
            ->assertViewHas('tieneArchivosPendientes', false)
            ->assertViewHas('puedeSubir', false)
            ->assertDontSee('factura.pdf')
            ->assertDontSee('En servidor — opcional reemplazar');

        $this->assertNull(session('fiscal_pendiente'));
        $this->assertFalse(Storage::disk('local')->exists($pendiente['path_pdf']));
        $this->assertFalse(Storage::disk('local')->exists($pendiente['path_xml']));
    }

    public function test_subir_exige_plazo_de_dias(): void
    {
        Storage::fake('local');

        $proveedor = $this->crearProveedor();
        $this->withSession([
            'proveedor_id' => $proveedor->id,
            'fiscal_pendiente' => $this->pendienteAprobado($proveedor),
        ])->from(route('proveedores.fiscal'))
            ->post(route('proveedores.fiscal.subir'))
            ->assertRedirect(route('proveedores.fiscal'))
            ->assertSessionHasErrors('dias_plazo');
    }

    public function test_subir_con_plazo_120_guarda_vencimiento(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $proveedor = $this->crearProveedor();
        $this->withSession([
            'proveedor_id' => $proveedor->id,
            'fiscal_pendiente' => $this->pendienteAprobado($proveedor),
        ])->post(route('proveedores.fiscal.subir'), [
            'dias_plazo' => 120,
        ])->assertRedirect();

        $factura = Factura::first();
        $this->assertNotNull($factura);
        $this->assertSame(120, (int) $factura->dias_plazo);
        $this->assertSame(now()->addDays(120)->toDateString(), $factura->fecha_vencimiento?->toDateString());
        $this->assertSame(120, (int) ($factura->validacion_detalle['dias_plazo'] ?? 0));
    }

    public function test_vista_muestra_plazos_cuando_esta_aprobada(): void
    {
        Storage::fake('local');

        $proveedor = $this->crearProveedor();
        $this->withSession([
            'proveedor_id' => $proveedor->id,
            'fiscal_pendiente' => $this->pendienteAprobado($proveedor),
            'fiscal_resultado' => ['aprobado' => true, 'mensaje' => 'ok'],
        ])->get(route('proveedores.fiscal'))
            ->assertOk()
            ->assertViewHas('puedeSubir', true)
            ->assertSee('Plazo de pago')
            ->assertSee('60 días')
            ->assertSee('120 días')
            ->assertSee('320 días');
    }

    /**
     * @return array<string, mixed>
     */
    private function pendienteAprobado(ProveedorUser $proveedor): array
    {
        $token = bin2hex(random_bytes(8));
        $dir = 'temp-fiscal/'.$proveedor->id.'/'.$token;
        Storage::disk('local')->put($dir.'/factura.pdf', '%PDF-1.4 demo');
        Storage::disk('local')->put($dir.'/factura.xml', '<?xml version="1.0"?><cfdi:Comprobante/>');

        return [
            'token' => $token,
            'proveedor_id' => $proveedor->id,
            'aprobado' => true,
            'path_pdf' => $dir.'/factura.pdf',
            'path_xml' => $dir.'/factura.xml',
            'path_oc' => null,
            'nombre_pdf' => 'factura.pdf',
            'nombre_xml' => 'factura.xml',
            'es_fletera' => false,
            'resultado' => [
                'aprobado' => true,
                'estatus' => 'aprobada',
                'datos' => [
                    'uuid' => '11111111-1111-1111-1111-111111111111',
                    'serie' => 'A',
                    'folio' => '99',
                    'total' => 1500,
                    'subtotal' => 1293.10,
                    'iva' => 206.90,
                ],
            ],
            'expires_at' => now()->addMinutes(30)->timestamp,
        ];
    }
}
