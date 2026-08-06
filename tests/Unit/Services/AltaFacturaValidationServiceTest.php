<?php

namespace Tests\Unit\Services;

use App\Services\AltaFacturaValidationService;
use App\Services\FacturaDocumentoExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AltaFacturaValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function cfdiXml(array $opts = []): string
    {
        $rfcEmisor = $opts['rfc_emisor'] ?? 'XAXX010101000';
        $regimen = $opts['regimen'] ?? '612';
        $rfcReceptor = $opts['rfc_receptor'] ?? 'EKU9003173C9';
        $subtotal = $opts['subtotal'] ?? '1000.00';
        $iva = $opts['iva'] ?? '160.00';
        $retIva = array_key_exists('ret_iva', $opts) ? $opts['ret_iva'] : '106.67';
        $retIsr = array_key_exists('ret_isr', $opts) ? $opts['ret_isr'] : '100.00';
        $total = $opts['total'] ?? '953.33';
        $uuid = $opts['uuid'] ?? 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890';
        $clave = $opts['clave'] ?? '01010101';
        $desc = $opts['descripcion'] ?? 'Servicio general';
        $metodo = $opts['metodo_pago'] ?? 'PUE';
        $forma = $opts['forma_pago'] ?? '03';
        $moneda = $opts['moneda'] ?? 'MXN';

        $retenciones = '';
        if ($retIva !== null || $retIsr !== null) {
            $retenciones = '<cfdi:Retenciones>';
            if ($retIsr !== null) {
                $retenciones .= '<cfdi:Retencion Impuesto="001" Importe="'.$retIsr.'"/>';
            }
            if ($retIva !== null) {
                $retenciones .= '<cfdi:Retencion Impuesto="002" Importe="'.$retIva.'"/>';
            }
            $retenciones .= '</cfdi:Retenciones>';
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital"
    Version="4.0" Serie="A" Folio="1" Fecha="2026-07-17T10:00:00"
    SubTotal="{$subtotal}" Total="{$total}" TipoDeComprobante="I" Moneda="{$moneda}"
    MetodoPago="{$metodo}" FormaPago="{$forma}">
  <cfdi:Emisor Rfc="{$rfcEmisor}" Nombre="PROVEEDOR DEMO" RegimenFiscal="{$regimen}"/>
  <cfdi:Receptor Rfc="{$rfcReceptor}" Nombre="INDUSTRIAS SALCOM" UsoCFDI="G03"/>
  <cfdi:Conceptos>
    <cfdi:Concepto ClaveProdServ="{$clave}" Cantidad="1" ClaveUnidad="E48" Descripcion="{$desc}" ValorUnitario="{$subtotal}" Importe="{$subtotal}"/>
  </cfdi:Conceptos>
  <cfdi:Impuestos TotalImpuestosTrasladados="{$iva}" TotalImpuestosRetenidos="0">
    {$retenciones}
    <cfdi:Traslados>
      <cfdi:Traslado Base="{$subtotal}" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="{$iva}"/>
    </cfdi:Traslados>
  </cfdi:Impuestos>
  <cfdi:Complemento>
    <tfd:TimbreFiscalDigital UUID="{$uuid}" FechaTimbrado="2026-07-17T10:01:00" RfcProvCertif="SAT970701NN3" SelloCFD="x" NoCertificadoSAT="1" SelloSAT="y"/>
  </cfdi:Complemento>
</cfdi:Comprobante>
XML;
    }

    private function extractorMatching(array $xmlOpts, bool $conOc = false): FacturaDocumentoExtractor
    {
        $campos = [
            'rfc_emisor' => $xmlOpts['rfc_emisor'] ?? 'XAXX010101000',
            'rfc_receptor' => $xmlOpts['rfc_receptor'] ?? 'EKU9003173C9',
            'uuid' => strtoupper($xmlOpts['uuid'] ?? 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890'),
            'fecha' => '2026-07-17',
            'regimen_fiscal' => $xmlOpts['regimen'] ?? '612',
            'metodo_pago' => $xmlOpts['metodo_pago'] ?? 'PUE',
            'forma_pago' => $xmlOpts['forma_pago'] ?? '03',
            'moneda' => $xmlOpts['moneda'] ?? 'MXN',
            'subtotal' => (float) ($xmlOpts['subtotal'] ?? 1000),
            'iva' => (float) ($xmlOpts['iva'] ?? 160),
            'retencion_iva' => array_key_exists('ret_iva', $xmlOpts)
                ? (float) ($xmlOpts['ret_iva'] ?? 0)
                : 106.67,
            'retencion_isr' => array_key_exists('ret_isr', $xmlOpts)
                ? (float) ($xmlOpts['ret_isr'] ?? 0)
                : 100.00,
            'total' => (float) ($xmlOpts['total'] ?? 953.33),
            'conceptos' => [strtoupper($xmlOpts['descripcion'] ?? 'Servicio general')],
        ];

        $mock = $this->createMock(FacturaDocumentoExtractor::class);
        $mock->method('extraerDesdeContenido')->willReturnCallback(function () use ($campos) {
            return ['texto' => 'PDF MOCK', 'campos' => $campos, 'escaneado' => false];
        });

        return $mock;
    }

    public function test_valida_regimen_y_retenciones_persona_fisica(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'XAXX010101000', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertSame('aprobada_con_observaciones', $result['estatus']);
        $this->assertTrue($result['checklist']['regimen']['ok']);
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertSame('612', $result['datos']['regimen_fiscal']);
        $this->assertFalse($result['datos']['tiene_concepto_flete']);
    }

    public function test_flete_exige_retencion_iva_sin_importar_regimen(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'clave' => '78101800',
            'descripcion' => 'Flete de mercancía',
            'ret_iva' => '0',
            'ret_isr' => '0',
            'total' => '1160.00',
            'uuid' => 'B1B2C3D4-E5F6-7890-ABCD-EF1234567891',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'AAA010101AAA', 'PDF', null);

        $this->assertFalse($result['aprobado']);
        $this->assertSame('rechazada', $result['estatus']);
        $this->assertTrue($result['datos']['tiene_concepto_flete']);
        $this->assertFalse($result['checklist']['retenciones']['ok']);
    }

    public function test_fletera_con_retenciones_correctas(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'clave' => '78101800',
            'descripcion' => 'Servicio de flete',
            'ret_iva' => '40.00',
            'ret_isr' => '12.50',
            'total' => '1107.50',
            'uuid' => 'C1B2C3D4-E5F6-7890-ABCD-EF1234567892',
            'iva' => '160.00',
            'subtotal' => '1000.00',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), true, 'AAA010101AAA', 'PDF', 'OC');

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertSame('aprobada', $result['estatus']);
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertTrue($result['datos']['es_fletera']);
        $this->assertTrue($result['datos']['tiene_concepto_flete']);
    }

    public function test_marca_fletera_sin_concepto_en_xml_se_trata_como_no_fletera(): void
    {
        config(['facturas.rfc_receptor' => '']);

        // RESICO PF: requiere ISR 1.25%
        $opts = [
            'rfc_emisor' => 'CUPL6007224D1',
            'regimen' => '626',
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
            'subtotal' => '24500.00',
            'iva' => '3920.00',
            'ret_iva' => null,
            'ret_isr' => '306.25',
            'total' => '28113.75',
            'uuid' => 'E711959E-17B5-4BAD-A59D-AF17E53970E1',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), true, 'CUPL6007224D1', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertFalse($result['datos']['tiene_concepto_flete']);
        $this->assertFalse($result['datos']['es_fletera']);
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertStringContainsString('sin flete en XML', $result['checklist']['fletera']['label']);
        $this->assertSame('aprobada_con_observaciones', $result['estatus']);
    }

    public function test_resico_persona_moral_no_exige_isr_125(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '626',
            'clave' => '01010101',
            'descripcion' => 'Servicio RESICO moral',
            'ret_iva' => null,
            'ret_isr' => null,
            'total' => '1160.00',
            'uuid' => 'F1B2C3D4-E5F6-7890-ABCD-EF1234567894',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'AAA010101AAA', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertSame('resico_moral', $result['datos']['retencion_esperada']['origen']);
    }

    public function test_pdf_no_coincide_rfc_rechaza(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'XAXX010101000',
            'descripcion' => 'Servicio profesional',
        ];
        $bad = $this->createMock(FacturaDocumentoExtractor::class);
        $bad->method('extraerDesdeContenido')->willReturn([
            'texto' => 'PDF',
            'campos' => [
                'rfc_emisor' => 'BBB010101BBB',
                'rfc_receptor' => 'EKU9003173C9',
                'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890',
                'subtotal' => 1000.0,
                'iva' => 160.0,
                'retencion_iva' => 106.67,
                'retencion_isr' => 100.0,
                'total' => 953.33,
                'conceptos' => ['SERVICIO PROFESIONAL'],
            ],
            'escaneado' => false,
        ]);

        $service = new AltaFacturaValidationService($bad);
        $result = $service->validar($this->cfdiXml($opts), false, 'XAXX010101000', 'PDF', null);

        $this->assertFalse($result['aprobado']);
        $this->assertSame('rechazada', $result['estatus']);
        $this->assertTrue(collect($result['errores'])->contains(fn ($e) => str_contains($e, 'RFC emisor')));
    }

    public function test_comision_persona_moral_no_exige_retencion(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'clave' => '01010101',
            'descripcion' => 'Comision por venta',
            'ret_iva' => null,
            'ret_isr' => null,
            'total' => '1160.00',
            'uuid' => 'D1B2C3D4-E5F6-7890-ABCD-EF1234567893',
            'iva' => '160.00',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'AAA010101AAA', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['datos']['tiene_concepto_comision']);
        $this->assertFalse($result['datos']['es_persona_fisica']);
    }
}
