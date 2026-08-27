<?php

namespace Tests\Unit\Services;

use App\Models\DocumentoProveedor;
use App\Models\PagoProveedor;
use App\Models\ProveedorUser;
use App\Services\PagoProveedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PagoProveedorDocumentosFiscalesTest extends TestCase
{
    use RefreshDatabase;

    private function proveedor(): ProveedorUser
    {
        return ProveedorUser::create([
            'usuario' => 'PROVDOCS',
            'password' => Hash::make('secret123'),
            'nombre' => 'Proveedor Docs SA',
            'id_proveedor' => 'DOC001',
            'correo' => 'docs@test.com',
            'tipo_persona' => 'Persona Moral',
            'activo' => true,
        ]);
    }

    public function test_slots_toman_opinion_y_constancia_aprobadas_del_expediente(): void
    {
        Storage::fake('public');

        $proveedor = $this->proveedor();
        Storage::disk('public')->put('expediente_fiscal/opinion/ok.pdf', '%PDF-1.4 opinion');
        Storage::disk('public')->put('expediente_fiscal/cif/ok.pdf', '%PDF-1.4 cif');

        DocumentoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'opinion',
            'archivo' => 'expediente_fiscal/opinion/ok.pdf',
            'estatus' => 'aprobado',
            'revisado_at' => now(),
        ]);
        DocumentoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'cif',
            'archivo' => 'expediente_fiscal/cif/ok.pdf',
            'estatus' => 'aprobado',
            'revisado_at' => now(),
        ]);

        $pago = PagoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'codigo_proveedor' => 'DOC001',
            'tipo' => 'facturas',
            'estatus' => 'borrador',
            'num_facturas' => 1,
        ]);

        $slots = app(PagoProveedorService::class)->documentosFiscalesParaPago($pago->load('proveedor.documentos'));
        $porClave = collect($slots)->keyBy('clave');

        $this->assertTrue($porClave['opinion']['ok']);
        $this->assertSame('expediente', $porClave['opinion']['origen']);
        $this->assertSame('ok.pdf', $porClave['opinion']['nombre']);

        $this->assertTrue($porClave['constancia']['ok']);
        $this->assertSame('expediente', $porClave['constancia']['origen']);

        $this->assertTrue($porClave['formato_pago']['ok']);
        $this->assertSame('lote', $porClave['formato_pago']['origen']);
        $this->assertStringContainsString('Formato_pago_lote_'.$pago->id, (string) $porClave['formato_pago']['nombre']);
    }

    public function test_ignora_documentos_pendientes_y_usa_formato_del_expediente_si_existe(): void
    {
        Storage::fake('public');

        $proveedor = $this->proveedor();
        Storage::disk('public')->put('expediente_fiscal/opinion/pend.pdf', '%PDF-1.4');
        Storage::disk('public')->put('expediente_fiscal/formato/pago.pdf', '%PDF-1.4 formato');

        DocumentoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'opinion',
            'archivo' => 'expediente_fiscal/opinion/pend.pdf',
            'estatus' => 'pendiente',
        ]);
        DocumentoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'formato_pago',
            'archivo' => 'expediente_fiscal/formato/pago.pdf',
            'estatus' => 'aprobado',
            'revisado_at' => now(),
        ]);

        $pago = PagoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'codigo_proveedor' => 'DOC001',
            'tipo' => 'facturas',
            'estatus' => 'borrador',
        ]);

        $slots = app(PagoProveedorService::class)->documentosFiscalesParaPago($pago->load('proveedor.documentos'));
        $porClave = collect($slots)->keyBy('clave');

        $this->assertFalse($porClave['opinion']['ok']);
        $this->assertFalse($porClave['constancia']['ok']);
        $this->assertTrue($porClave['formato_pago']['ok']);
        $this->assertSame('expediente', $porClave['formato_pago']['origen']);
        $this->assertSame('pago.pdf', $porClave['formato_pago']['nombre']);
    }

    public function test_materializa_copiando_archivos_del_expediente_al_pago(): void
    {
        Storage::fake('public');

        $proveedor = $this->proveedor();
        Storage::disk('public')->put('expediente_fiscal/opinion/ok.pdf', '%PDF-1.4 opinion');
        Storage::disk('public')->put('expediente_fiscal/cif/ok.pdf', '%PDF-1.4 cif');

        $opinion = DocumentoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'opinion',
            'archivo' => 'expediente_fiscal/opinion/ok.pdf',
            'estatus' => 'aprobado',
            'revisado_at' => now(),
        ]);
        DocumentoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'cif',
            'archivo' => 'expediente_fiscal/cif/ok.pdf',
            'estatus' => 'aprobado',
            'revisado_at' => now(),
        ]);

        $pago = PagoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'codigo_proveedor' => 'DOC001',
            'tipo' => 'facturas',
            'estatus' => 'borrador',
        ]);

        $service = app(PagoProveedorService::class);
        $slots = $service->documentosFiscalesParaPago($pago->load('proveedor.documentos'));
        $resultado = $service->materializarDocumentosFiscales($pago, $slots);

        $this->assertContains('pagos_comprobantes/'.$pago->id.'/opinion.pdf', $resultado['paths']);
        $this->assertContains('pagos_comprobantes/'.$pago->id.'/constancia.pdf', $resultado['paths']);
        $this->assertTrue(Storage::disk('public')->exists('pagos_comprobantes/'.$pago->id.'/opinion.pdf'));
        $this->assertSame('%PDF-1.4 opinion', Storage::disk('public')->get('pagos_comprobantes/'.$pago->id.'/opinion.pdf'));
        $this->assertSame($opinion->id, $resultado['slots'][0]['documento_id']);
    }
}
