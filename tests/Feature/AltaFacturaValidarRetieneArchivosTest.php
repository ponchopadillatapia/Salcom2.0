<?php

namespace Tests\Feature;

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
}
