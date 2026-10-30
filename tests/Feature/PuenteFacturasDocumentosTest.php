<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminPagosController;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

/**
 * PUENTE portal ↔ Wiese: el Formato para pago (que lee facturas de Wiese) debe MARCAR cuáles ya
 * tienen documentos subidos por el portal del proveedor, haciendo match por codigo+folio. Sin
 * duplicar. Estos tests cubren el método marcarFacturasConDocumentosPortal().
 */
class PuenteFacturasDocumentosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Invoca el método privado de match vía reflexión y devuelve la colección decorada.
     *
     * @param  array<int, array<string,mixed>>  $facturasWiese
     */
    private function marcar(array $facturasWiese): Collection
    {
        $controller = app(AdminPagosController::class);
        $m = new ReflectionMethod($controller, 'marcarFacturasConDocumentosPortal');
        $m->setAccessible(true);

        return $m->invoke($controller, collect($facturasWiese));
    }

    public function test_factura_de_wiese_con_documentos_locales_se_marca(): void
    {
        // Factura subida por el portal: folio_cfdi = serie+folio, con PDF/XML.
        Factura::create([
            'folio_cfdi' => 'A1205',
            'uuid_cfdi' => 'AAAA1111-BBBB-2222-CCCC-333344445555',
            'codigo_proveedor' => 'M213015002',
            'monto' => 1000,
            'monto_iva' => 0,
            'total' => 1000,
            'estatus' => 'pendiente',
            'archivo_pdf' => 'facturas-proveedor/1/pdf_x.pdf',
            'archivo_xml' => 'facturas-proveedor/1/xml_x.xml',
            'validacion_detalle' => ['folio' => '1205', 'serie' => 'A'],
        ]);

        // La misma factura, como la devuelve Wiese: serie + folio por separado.
        $out = $this->marcar([
            ['codigo' => 'M213015002', 'serie' => 'A', 'folio' => '1205', 'total' => 1000],
            ['codigo' => 'M213015002', 'serie' => 'B', 'folio' => '9999', 'total' => 500], // sin docs
        ]);

        $conDocs = $out->firstWhere('folio', '1205');
        $sinDocs = $out->firstWhere('folio', '9999');

        $this->assertTrue($conDocs['tiene_docs']);
        $this->assertNotNull($conDocs['factura_local_id']);
        $this->assertTrue($conDocs['docs_pdf']);
        $this->assertTrue($conDocs['docs_xml']);

        $this->assertFalse($sinDocs['tiene_docs']);
        $this->assertNull($sinDocs['factura_local_id']);
    }

    public function test_match_tolera_sufijo_de_colision_en_folio_local(): void
    {
        // El portal agrega "-xxxx" cuando el folio ya existía. El match debe ignorar ese sufijo.
        Factura::create([
            'folio_cfdi' => 'F93150-a1b2',
            'codigo_proveedor' => 'M213015002',
            'monto' => 77479.90,
            'monto_iva' => 0,
            'total' => 77479.90,
            'estatus' => 'pendiente',
            'archivo_pdf' => 'facturas-proveedor/1/pdf_y.pdf',
            'validacion_detalle' => ['folio' => '93150', 'serie' => 'F'],
        ]);

        $out = $this->marcar([
            ['codigo' => 'M213015002', 'serie' => 'F', 'folio' => '93150', 'total' => 77479.90],
        ]);

        $this->assertTrue($out->first()['tiene_docs']);
    }

    public function test_factura_de_otro_proveedor_no_matchea(): void
    {
        // Mismo folio pero DISTINTO proveedor → NO debe marcar (el match es por codigo+folio).
        Factura::create([
            'folio_cfdi' => 'A1205',
            'codigo_proveedor' => 'OTRO-PROV',
            'monto' => 1000,
            'monto_iva' => 0,
            'total' => 1000,
            'estatus' => 'pendiente',
            'archivo_pdf' => 'facturas-proveedor/9/pdf_z.pdf',
            'validacion_detalle' => ['folio' => '1205', 'serie' => 'A'],
        ]);

        $out = $this->marcar([
            ['codigo' => 'M213015002', 'serie' => 'A', 'folio' => '1205', 'total' => 1000],
        ]);

        $this->assertFalse($out->first()['tiene_docs']);
    }
}
