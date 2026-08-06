<?php

namespace Tests\Unit\Services;

use App\Services\AltaFacturaValidationService;
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
    SubTotal="{$subtotal}" Total="{$total}" TipoDeComprobante="I" Moneda="MXN"
    FormaPago="03" MetodoPago="PUE">
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

    public function test_valida_regimen_y_retenciones_persona_fisica(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $service = new AltaFacturaValidationService;
        $result = $service->validar($this->cfdiXml([
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
        ]), false, 'XAXX010101000');

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['regimen']['ok']);
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertTrue($result['checklist']['pago_cfdi']['ok']);
        $this->assertTrue($result['checklist']['producto']['ok']);
        $this->assertSame('612', $result['datos']['regimen_fiscal']);
        $this->assertSame('03', $result['datos']['forma_pago']);
        $this->assertSame('PUE', $result['datos']['metodo_pago']);
        $this->assertSame('G03', $result['datos']['uso_cfdi']);
        $this->assertSame('Servicio profesional', $result['datos']['producto']);
        $this->assertFalse($result['datos']['tiene_concepto_flete']);
    }

    public function test_flete_exige_retencion_iva_sin_importar_regimen(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $service = new AltaFacturaValidationService;
        $xml = $this->cfdiXml([
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'clave' => '78101800',
            'descripcion' => 'Flete de mercancía',
            'ret_iva' => '0',
            'ret_isr' => '0',
            'total' => '1160.00',
            'uuid' => 'B1B2C3D4-E5F6-7890-ABCD-EF1234567891',
        ]);

        // Aunque no marque fletera, la clave de flete obliga retención IVA
        $result = $service->validar($xml, false, 'AAA010101AAA');

        $this->assertFalse($result['aprobado']);
        $this->assertTrue($result['datos']['tiene_concepto_flete']);
        $this->assertFalse($result['checklist']['retenciones']['ok']);
    }

    public function test_fletera_con_retenciones_correctas(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $service = new AltaFacturaValidationService;
        $xml = $this->cfdiXml([
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'clave' => '78101800',
            'descripcion' => 'Servicio de flete',
            'ret_iva' => '40.00',
            'ret_isr' => '12.50',
            'total' => '1107.50',
            'uuid' => 'C1B2C3D4-E5F6-7890-ABCD-EF1234567892',
        ]);

        $result = $service->validar($xml, true, 'AAA010101AAA');

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertTrue($result['datos']['es_fletera']);
        $this->assertTrue($result['datos']['tiene_concepto_flete']);
    }

    public function test_marca_fletera_sin_concepto_en_xml_se_trata_como_no_fletera(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $service = new AltaFacturaValidationService;
        // Régimen 626 (RESICO): sin retención requerida si NO es fletera
        $xml = $this->cfdiXml([
            'rfc_emisor' => 'CUPL6007224D1',
            'regimen' => '626',
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
            'subtotal' => '24500.00',
            'iva' => '3920.00',
            'ret_iva' => '5223.40',
            'ret_isr' => '612.50',
            'total' => '25502.05',
            'uuid' => 'E711959E-17B5-4BAD-A59D-AF17E53970E1',
        ]);

        // Marcó fletera en el formulario, pero el XML no trae flete
        $result = $service->validar($xml, true, 'CUPL6007224D1');

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertFalse($result['datos']['tiene_concepto_flete']);
        $this->assertFalse($result['datos']['es_fletera']);
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertStringContainsString('sin flete en XML', $result['checklist']['fletera']['label']);
    }

    public function test_comision_persona_moral_no_exige_retencion(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $service = new AltaFacturaValidationService;
        $xml = $this->cfdiXml([
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'clave' => '01010101',
            'descripcion' => 'Comision por venta',
            'ret_iva' => null,
            'ret_isr' => null,
            'total' => '1160.00',
            'uuid' => 'D1B2C3D4-E5F6-7890-ABCD-EF1234567893',
        ]);

        $result = $service->validar($xml, false, 'AAA010101AAA');

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['datos']['tiene_concepto_comision']);
        $this->assertFalse($result['datos']['es_persona_fisica']);
    }
}
