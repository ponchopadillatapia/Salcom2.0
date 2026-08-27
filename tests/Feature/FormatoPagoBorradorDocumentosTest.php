<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\DocumentoProveedor;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\ProveedorUser;
use App\Services\PagoProveedorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormatoPagoBorradorDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private function sesionAdmin(): array
    {
        $admin = AdminUser::create([
            'nombre' => 'Admin Pagos',
            'correo' => 'pagos@test.com',
            'usuario' => 'ADMPAGOS',
            'password' => Hash::make('test1234'),
            'activo' => true,
            'rol' => 'admin',
        ]);

        return [
            'admin_id' => $admin->id,
            'admin_nombre' => $admin->nombre,
            'admin_correo' => $admin->correo,
            'admin_usuario' => $admin->usuario,
            'admin_rol' => $admin->rol,
        ];
    }

    private function proveedorConDocs(): ProveedorUser
    {
        $proveedor = ProveedorUser::create([
            'usuario' => 'PROVPAGO',
            'password' => Hash::make('secret123'),
            'nombre' => 'Proveedor Pago SA',
            'id_proveedor' => 'PAGO001',
            'correo' => 'pago@test.com',
            'tipo_persona' => 'Persona Moral',
            'activo' => true,
        ]);

        Storage::disk('public')->put('expediente_fiscal/opinion/opinion_ok.pdf', '%PDF-1.4 opinion');
        Storage::disk('public')->put('expediente_fiscal/cif/constancia_ok.pdf', '%PDF-1.4 cif');

        DocumentoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'opinion',
            'archivo' => 'expediente_fiscal/opinion/opinion_ok.pdf',
            'estatus' => 'aprobado',
            'revisado_at' => now(),
        ]);
        DocumentoProveedor::create([
            'proveedor_id' => $proveedor->id,
            'tipo' => 'cif',
            'archivo' => 'expediente_fiscal/cif/constancia_ok.pdf',
            'estatus' => 'aprobado',
            'revisado_at' => now(),
        ]);

        return $proveedor;
    }

    private function facturaPendiente(ProveedorUser $proveedor): Factura
    {
        return Factura::create([
            'folio_cfdi' => 'A-100',
            'uuid_cfdi' => '11111111-1111-1111-1111-111111111111',
            'codigo_proveedor' => $proveedor->id_proveedor,
            'regimen_fiscal' => '601',
            'monto' => 1000,
            'monto_iva' => 160,
            'total' => 1160,
            'estatus' => 'pendiente',
            'validacion_detalle' => [
                'forma_pago' => '99',
                'metodo_pago' => 'PPD',
                'uso_cfdi' => 'G03',
                'regimen_fiscal' => '601',
                'producto' => 'TERMOPAR',
            ],
        ]);
    }

    public function test_borrador_muestra_campos_de_documentos_fiscales_autoinsertados(): void
    {
        Storage::fake('public');

        $proveedor = $this->proveedorConDocs();
        $factura = $this->facturaPendiente($proveedor);
        $pago = app(PagoProveedorService::class)->crearLote(
            $proveedor,
            [$factura->id],
            now()->toDateString(),
            null,
            1
        );

        $response = $this->withSession($this->sesionAdmin())
            ->get(route('admin.pagos.show', $pago));

        $response->assertOk();
        $response->assertSee('Documentos fiscales');
        $response->assertSee('Opinión positiva');
        $response->assertSee('Formato de pago');
        $response->assertSee('Constancia de Situación Fiscal');
        $response->assertSee('opinion_ok.pdf');
        $response->assertSee('constancia_ok.pdf');
        $response->assertSee('Insertados del expediente validado');
        $response->assertSee('Del expediente fiscal');
    }

    public function test_confirmar_adjunta_documentos_del_expediente_al_pago(): void
    {
        Storage::fake('public');

        $proveedor = $this->proveedorConDocs();
        $factura = $this->facturaPendiente($proveedor);
        $pago = app(PagoProveedorService::class)->crearLote(
            $proveedor,
            [$factura->id],
            now()->toDateString(),
            null,
            1
        );

        $response = $this->withSession($this->sesionAdmin())
            ->post(route('admin.pagos.confirmar', $pago), [
                'fecha_pago' => now()->toDateString(),
            ]);

        $response->assertRedirect();

        $pago->refresh();
        $this->assertSame('confirmado', $pago->estatus);
        $this->assertContains('pagos_comprobantes/'.$pago->id.'/opinion.pdf', $pago->comprobantes);
        $this->assertContains('pagos_comprobantes/'.$pago->id.'/constancia.pdf', $pago->comprobantes);
        $this->assertTrue(Storage::disk('public')->exists('pagos_comprobantes/'.$pago->id.'/opinion.pdf'));
        $this->assertTrue(Storage::disk('public')->exists('pagos_comprobantes/'.$pago->id.'/constancia.pdf'));

        $docs = collect($pago->datos_confirmacion['documentos_fiscales'] ?? [])->keyBy('clave');
        $this->assertSame('expediente', $docs['opinion']['origen']);
        $this->assertSame('expediente', $docs['constancia']['origen']);
        $this->assertSame('lote', $docs['formato_pago']['origen']);
    }
}
