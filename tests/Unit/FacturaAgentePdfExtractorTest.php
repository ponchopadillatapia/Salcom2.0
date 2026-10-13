<?php

namespace Tests\Unit;

use App\Services\FacturaAgentePdfExtractor;
use Tests\TestCase;

class FacturaAgentePdfExtractorTest extends TestCase
{
    public function test_lee_importes_del_texto_sin_armarlos(): void
    {
        $lectura = (new FacturaAgentePdfExtractor)->interpretar(null, <<<'TXT'
Serie: B
Folio: 77
Fecha: 08/10/2026
Moneda: USD
Subtotal: 1,500.00
IVA: 240.00
Retenciones: 150.00
Total: 1,700.00
RFC MARA030609844
TXT);

        $this->assertSame('B', $lectura['serie']);
        $this->assertSame('77', $lectura['folio']);
        $this->assertSame('2026-10-08', $lectura['fecha']);
        $this->assertSame('USD', $lectura['moneda']);
        $this->assertSame('1500.00', $lectura['honorarios']);
        $this->assertSame('240.00', $lectura['impuestos']);
        $this->assertNull($lectura['retenciones']);
        $this->assertSame('1700.00', $lectura['importe']);
        $this->assertNotSame(
            number_format(1500 + 240 - 150, 2, '.', ''),
            $lectura['importe']
        );
    }

    public function test_lee_la_representacion_impresa_sin_xml_embebido(): void
    {
        $lectura = (new FacturaAgentePdfExtractor)->interpretar(null, <<<'TXT'
LUGAR FECHA HORA:
REFERENCIA:
PEDIMENTO:
RELACIÓN DE GASTOS:
NVO. LAREDO,TAMPS 01-Oct-2026 10:47:16 a. m.
NL-2603198 11124832
6003470,
00120386
FACTURA
:
R.F.C:
INDUSTRIAS SALCOM
ISA951017A10
Folio Fiscal:
76CD8D54-B211-B64E-91D7-3636314BDAA2
RFC: KAA-991021-4Q6
1HONORARIOS SERVICIOS ADUANALES: $1,200.00 1.00 $1,200.00
VALIDADOR CAAAREM $100.00
PROCESO VUCEM $550.00
CONTRAPRESTACION POR LA PREVALIDACION $35.00
ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI
Total: $2,186.60
$301.60 I.V.A:
$1,885.00Sub Total:
SON: DOS MIL CIENTO OCHENTA Y SEIS PESOS 60/100 M.N.
TXT);

        $this->assertNull($lectura['serie']);
        $this->assertSame('00120386', $lectura['folio']);
        $this->assertSame('2026-10-01', $lectura['fecha']);
        $this->assertSame('MXN', $lectura['moneda']);
        $this->assertSame('1200.00', $lectura['honorarios']);
        $this->assertSame('301.60', $lectura['impuestos']);
        $this->assertNull($lectura['retenciones']);
        $this->assertSame('2186.60', $lectura['importe']);
        $this->assertSame('KAA9910214Q6', $lectura['rfc_emisor']);
        $this->assertSame('76CD8D54-B211-B64E-91D7-3636314BDAA2', $lectura['uuid']);
        $this->assertNull($lectura['tipo_operacion']);
    }

    public function test_la_relacion_de_gastos_no_se_registra_como_factura(): void
    {
        $lectura = (new FacturaAgentePdfExtractor)->interpretar(null, 'RELACION DE GASTOS IMPORTE TOTAL A PAGAR POR ESTA OPERACION: $145,726.60');

        $this->assertFalse($lectura['leido']);
        $this->assertNull($lectura['importe']);
    }

    public function test_un_complemento_de_pago_no_se_toma_como_factura(): void
    {
        $xml = '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Folio="9" Fecha="2026-10-08T10:00:00" SubTotal="0" Total="500.00" Moneda="MXN" TipoDeComprobante="P"><cfdi:Emisor Rfc="MARA030609844" Nombre="AGENTE" RegimenFiscal="612"/><cfdi:Receptor Rfc="ISA951017A10" Nombre="SALCOM" UsoCFDI="CP01"/></cfdi:Comprobante>';

        $lectura = (new FacturaAgentePdfExtractor)->interpretar($xml, '');

        $this->assertFalse($lectura['leido']);
        $this->assertNull($lectura['importe']);
        $this->assertNull($lectura['folio']);
    }
}
