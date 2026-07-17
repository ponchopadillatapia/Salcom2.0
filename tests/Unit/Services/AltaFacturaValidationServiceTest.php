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
        $retIva = $opts['ret_iva'] ?? '106.67';
        $retIsr = $opts['ret_isr'] ?? '100.00';
        $total = $opts['total'] ?? '953.33';
        $uuid = $opts['uuid'] ?? 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890';

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
    SubTotal="{$subtotal}" Total="{$total}" TipoDeComprobante="I" Moneda="MXN">
  <cfdi:Emisor Rfc="{$rfcEmisor}" Nombre="PROVEEDOR DEMO" RegimenFiscal="{$regimen}"/>
  <cfdi:Receptor Rfc="{$rfcReceptor}" Nombre="INDUSTRIAS SALCOM" UsoCFDI="G03"/>
  <cfdi:Conceptos>
    <cfdi:Concepto ClaveProdServ="78101800" Cantidad="1" ClaveUnidad="E48" Descripcion="Servicio" ValorUnitario="{$subtotal}" Importe="{$subtotal}"/>
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
        $result = $service->validar($this->cfdiXml(), false, 'XAXX010101000');

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['regimen']['ok']);
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertSame('612', $result['datos']['regimen_fiscal']);
    }

    public function test_fletera_exige_retenciones_4_y_1_25(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $service = new AltaFacturaValidationService;
        $xml = $this->cfdiXml([
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'ret_iva' => '0',
            'ret_isr' => '0',
            'total' => '1160.00',
            'uuid' => 'B1B2C3D4-E5F6-7890-ABCD-EF1234567891',
        ]);

        $result = $service->validar($xml, true, 'AAA010101AAA');

        $this->assertFalse($result['aprobado']);
        $this->assertNotEmpty($result['errores']);
        $this->assertFalse($result['checklist']['retenciones']['ok']);
    }

    public function test_fletera_con_retenciones_correctas(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $service = new AltaFacturaValidationService;
        $xml = $this->cfdiXml([
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'ret_iva' => '40.00',
            'ret_isr' => '12.50',
            'total' => '1107.50',
            'uuid' => 'C1B2C3D4-E5F6-7890-ABCD-EF1234567892',
        ]);

        $result = $service->validar($xml, true, 'AAA010101AAA');

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertTrue($result['datos']['es_fletera']);
    }
}
