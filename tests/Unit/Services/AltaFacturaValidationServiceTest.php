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
        $fecha = $opts['fecha'] ?? now()->format('Y-m-d').'T10:00:00';
        $fechaTimbre = $opts['fecha_timbre'] ?? $fecha;

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
    Version="4.0" Serie="A" Folio="1" Fecha="{$fecha}"
    SubTotal="{$subtotal}" Total="{$total}" TipoDeComprobante="I" Moneda="{$moneda}"
    MetodoPago="{$metodo}" FormaPago="{$forma}">
  <cfdi:Emisor Rfc="{$rfcEmisor}" Nombre="PROVEEDOR DEMO" RegimenFiscal="{$regimen}"/>
  <cfdi:Receptor Rfc="{$rfcReceptor}" Nombre="INDUSTRIAS SALCOM" UsoCFDI="G03"/>
  <cfdi:Conceptos>
    <cfdi:Concepto ClaveProdServ="{$clave}" Cantidad="1" ClaveUnidad="E48" Descripcion="{$desc}" ValorUnitario="{$subtotal}" Importe="{$subtotal}">
      <cfdi:Impuestos>
        {$retenciones}
        <cfdi:Traslados>
          <cfdi:Traslado Base="{$subtotal}" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="{$iva}"/>
        </cfdi:Traslados>
      </cfdi:Impuestos>
    </cfdi:Concepto>
  </cfdi:Conceptos>
  <cfdi:Impuestos TotalImpuestosTrasladados="{$iva}" TotalImpuestosRetenidos="0">
    {$retenciones}
    <cfdi:Traslados>
      <cfdi:Traslado Base="{$subtotal}" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="{$iva}"/>
    </cfdi:Traslados>
  </cfdi:Impuestos>
  <cfdi:Complemento>
    <tfd:TimbreFiscalDigital UUID="{$uuid}" FechaTimbrado="{$fechaTimbre}" RfcProvCertif="SAT970701NN3" SelloCFD="x" NoCertificadoSAT="1" SelloSAT="y"/>
  </cfdi:Complemento>
</cfdi:Comprobante>
XML;
    }

    /**
     * Mock del extractor: texto crudo en minúsculas con UUID, RFCs y Total del XML.
     */
    private function extractorMatching(array $xmlOpts, bool $conOc = false): FacturaDocumentoExtractor
    {
        $texto = $this->textoPdfCoincidente($xmlOpts);

        $mock = $this->createMock(FacturaDocumentoExtractor::class);
        $mock->method('extraerDesdeContenido')->willReturnCallback(function () use ($texto) {
            return ['texto' => $texto, 'escaneado' => false];
        });

        return $mock;
    }

    private function textoPdfCoincidente(array $xmlOpts): string
    {
        $uuid = strtolower($xmlOpts['uuid'] ?? 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890');
        $rfcEmisor = strtolower($xmlOpts['rfc_emisor'] ?? 'XAXX010101000');
        $rfcReceptor = strtolower($xmlOpts['rfc_receptor'] ?? 'EKU9003173C9');
        $total = number_format((float) ($xmlOpts['total'] ?? 953.33), 2, '.', '');

        return "factura cfdi uuid {$uuid} rfc emisor {$rfcEmisor} rfc receptor {$rfcReceptor} total {$total} pesos mxn";
    }

    public function test_extrae_objeto_cfdi_estandarizado_desde_xml(): void
    {
        $opts = [
            'rfc_emisor' => 'CUPL6007224D1',
            'rfc_receptor' => 'ISA951017A10',
            'regimen' => '612',
            'subtotal' => '5833.69',
            'total' => '5833.69',
            'iva' => '0.00',
            'ret_iva' => null,
            'ret_isr' => null,
            'clave' => '78101800',
            'descripcion' => 'Flete de mercancia',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF12345678FF',
        ];

        $service = new AltaFacturaValidationService;
        $errores = [];
        $cfdi = $service->extraerObjetoCfdiDesdeXml($this->cfdiXml($opts), $errores);

        $this->assertNotNull($cfdi);
        $this->assertSame('A1B2C3D4-E5F6-7890-ABCD-EF12345678FF', $cfdi['uuid']);
        $this->assertSame('CUPL6007224D1', $cfdi['rfc_emisor']);
        $this->assertSame('ISA951017A10', $cfdi['rfc_receptor']);
        $this->assertSame('612', $cfdi['regimen_fiscal']);
        $this->assertSame(5833.69, $cfdi['subtotal']);
        $this->assertSame(5833.69, $cfdi['total']);
        $this->assertCount(1, $cfdi['conceptos']);
        $this->assertSame('78101800', $cfdi['conceptos'][0]['clave_prod_serv']);
        $this->assertSame(5833.69, $cfdi['conceptos'][0]['impuestos']['traslados'][0]['base']);
    }

    public function test_factura_de_mes_anterior_se_rechaza(): void
    {
        config(['facturas.rfc_receptor' => '', 'facturas.solo_mes_actual' => true]);

        $opts = [
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
            'fecha' => now()->subMonth()->format('Y-m-d').'T10:00:00',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF123456789A',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'XAXX010101000', 'PDF', null);

        $this->assertFalse($result['aprobado']);
        $this->assertFalse($result['checklist']['periodo']['ok']);
        $this->assertTrue(collect($result['errores'])->contains(
            fn ($e) => str_contains((string) $e, 'periodo ya cerró') || str_contains((string) $e, 'mes en curso')
        ));
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
        $this->assertSame('aprobada', $result['estatus']);
        $this->assertTrue($result['checklist']['regimen']['ok']);
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertTrue($result['checklist']['pago_cfdi']['ok']);
        $this->assertTrue($result['checklist']['producto']['ok']);
        $this->assertTrue($result['checklist']['pdf_xml']['ok']);
        $this->assertSame('612', $result['datos']['regimen_fiscal']);
        $this->assertSame('03', $result['datos']['forma_pago']);
        $this->assertSame('PUE', $result['datos']['metodo_pago']);
        $this->assertSame('G03', $result['datos']['uso_cfdi']);
        $this->assertSame('Servicio profesional', $result['datos']['producto']);
        $this->assertFalse($result['datos']['tiene_concepto_flete']);
        $this->assertSame(160.0, (float) $result['datos']['iva']);
        $this->assertSame(106.67, (float) $result['datos']['retencion_iva']);
        $this->assertSame(100.0, (float) $result['datos']['retencion_isr']);
        $this->assertFalse(collect($result['advertencias'])->contains(
            fn ($a) => str_contains((string) $a, 'No se adjuntó Orden de Compra')
        ));
    }

    public function test_impuestos_solo_del_nodo_global_no_duplican_conceptos(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
            'iva' => '160.00',
            'ret_iva' => '40.00',
            'ret_isr' => '12.50',
            'total' => '1107.50',
            'uuid' => 'D1B2C3D4-E5F6-7890-ABCD-EF1234567801',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'XAXX010101000', 'PDF', null);

        $this->assertSame(160.0, (float) $result['datos']['iva']);
        $this->assertSame(40.0, (float) $result['datos']['retencion_iva']);
        $this->assertSame(12.5, (float) $result['datos']['retencion_isr']);
        $this->assertNotSame(320.0, (float) $result['datos']['iva']);
        $this->assertNotSame(80.0, (float) $result['datos']['retencion_iva']);
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

    public function test_flete_601_solo_retencion_iva_sin_isr_aprueba(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'clave' => '78101800',
            'descripcion' => 'Servicio de flete',
            'ret_iva' => '40.00',
            'ret_isr' => '0',
            'total' => '1120.00',
            'uuid' => 'C1B2C3D4-E5F6-7890-ABCD-EF12345678A1',
            'iva' => '160.00',
            'subtotal' => '1000.00',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'AAA010101AAA', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertSame(0.0, (float) $result['datos']['retencion_esperada']['isr_tasa']);
        $this->assertSame(0.04, (float) $result['datos']['retencion_esperada']['iva_tasa']);
        $this->assertSame('flete', $result['datos']['retencion_esperada']['origen']);
        $this->assertSame(1000.0, (float) $result['datos']['retencion_esperada']['base_iva']);
    }

    public function test_retencion_iva_4_porciento_solo_sobre_conceptos_con_retencion_002(): void
    {
        config(['facturas.rfc_receptor' => '']);

        // SubTotal global 10000, pero solo el flete (1000) tiene Retencion 002.
        // 4% debe calcularse sobre 1000 (=40), no sobre 10000 (=400).
        $fecha = now()->format('Y-m-d').'T10:00:00';
        $uuid = 'C1B2C3D4-E5F6-7890-ABCD-EF12345678C1';
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital"
    Version="4.0" Serie="A" Folio="9" Fecha="{$fecha}"
    SubTotal="10000.00" Total="11560.00" TipoDeComprobante="I" Moneda="MXN"
    MetodoPago="PUE" FormaPago="03">
  <cfdi:Emisor Rfc="AAA010101AAA" Nombre="PROVEEDOR DEMO" RegimenFiscal="601"/>
  <cfdi:Receptor Rfc="EKU9003173C9" Nombre="INDUSTRIAS SALCOM" UsoCFDI="G03"/>
  <cfdi:Conceptos>
    <cfdi:Concepto ClaveProdServ="01010101" Cantidad="1" ClaveUnidad="H87" Descripcion="Mercancia sin retencion" ValorUnitario="9000.00" Importe="9000.00">
      <cfdi:Impuestos>
        <cfdi:Traslados>
          <cfdi:Traslado Base="9000.00" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="1440.00"/>
        </cfdi:Traslados>
      </cfdi:Impuestos>
    </cfdi:Concepto>
    <cfdi:Concepto ClaveProdServ="78101800" Cantidad="1" ClaveUnidad="E48" Descripcion="Servicio de flete" ValorUnitario="1000.00" Importe="1000.00">
      <cfdi:Impuestos>
        <cfdi:Retenciones>
          <cfdi:Retencion Base="1000.00" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.040000" Importe="40.00"/>
        </cfdi:Retenciones>
        <cfdi:Traslados>
          <cfdi:Traslado Base="1000.00" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="160.00"/>
        </cfdi:Traslados>
      </cfdi:Impuestos>
    </cfdi:Concepto>
  </cfdi:Conceptos>
  <cfdi:Impuestos TotalImpuestosTrasladados="1600.00" TotalImpuestosRetenidos="40.00">
    <cfdi:Retenciones>
      <cfdi:Retencion Impuesto="002" Importe="40.00"/>
    </cfdi:Retenciones>
    <cfdi:Traslados>
      <cfdi:Traslado Base="10000.00" Impuesto="002" TipoFactor="Tasa" TasaOCuota="0.160000" Importe="1600.00"/>
    </cfdi:Traslados>
  </cfdi:Impuestos>
  <cfdi:Complemento>
    <tfd:TimbreFiscalDigital UUID="{$uuid}" FechaTimbrado="{$fecha}" RfcProvCertif="SAT970701NN3" SelloCFD="x" NoCertificadoSAT="1" SelloSAT="y"/>
  </cfdi:Complemento>
</cfdi:Comprobante>
XML;

        $opts = [
            'rfc_emisor' => 'AAA010101AAA',
            'rfc_receptor' => 'EKU9003173C9',
            'total' => '11560.00',
            'uuid' => $uuid,
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($xml, false, 'AAA010101AAA', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['datos']['tiene_concepto_flete']);
        $this->assertSame(40.0, (float) $result['datos']['retencion_iva']);
        $this->assertSame(1000.0, (float) $result['datos']['retencion_esperada']['base_iva']);
        $this->assertSame(1000.0, (float) $result['datos']['retencion_esperada']['base_flete_retencion']);
        $this->assertNotSame(10000.0, (float) $result['datos']['retencion_esperada']['base_iva']);
        $this->assertTrue($result['checklist']['retenciones']['ok']);
    }

    public function test_flete_612_solo_retencion_iva_sin_isr_aprueba(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'CUPL6007224D1',
            'regimen' => '612',
            'clave' => '78101800',
            'descripcion' => 'Servicio de flete',
            'ret_iva' => '40.00',
            'ret_isr' => null,
            'total' => '1120.00',
            'uuid' => 'C1B2C3D4-E5F6-7890-ABCD-EF12345678A2',
            'iva' => '160.00',
            'subtotal' => '1000.00',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'CUPL6007224D1', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertSame(0.0, (float) $result['datos']['retencion_esperada']['isr_tasa']);
        $this->assertSame('flete', $result['datos']['retencion_esperada']['origen']);
    }

    public function test_flete_626_exige_iva_e_isr(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'CUPL6007224D1',
            'regimen' => '626',
            'clave' => '78101800',
            'descripcion' => 'Servicio de flete',
            'ret_iva' => '40.00',
            'ret_isr' => '12.50',
            'total' => '1107.50',
            'uuid' => 'C1B2C3D4-E5F6-7890-ABCD-EF12345678A3',
            'iva' => '160.00',
            'subtotal' => '1000.00',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'CUPL6007224D1', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['retenciones']['ok']);
        $this->assertSame(0.0125, (float) $result['datos']['retencion_esperada']['isr_tasa']);
        $this->assertSame(0.04, (float) $result['datos']['retencion_esperada']['iva_tasa']);
        $this->assertSame('flete_resico', $result['datos']['retencion_esperada']['origen']);
    }

    public function test_flete_626_sin_isr_rechaza(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'CUPL6007224D1',
            'regimen' => '626',
            'clave' => '78101800',
            'descripcion' => 'Servicio de flete',
            'ret_iva' => '40.00',
            'ret_isr' => null,
            'total' => '1120.00',
            'uuid' => 'C1B2C3D4-E5F6-7890-ABCD-EF12345678A4',
            'iva' => '160.00',
            'subtotal' => '1000.00',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'CUPL6007224D1', 'PDF', null);

        $this->assertFalse($result['aprobado']);
        $this->assertFalse($result['checklist']['retenciones']['ok']);
        $this->assertTrue(collect($result['errores'])->contains(
            fn ($e) => str_contains($e, 'Flete RESICO') && str_contains($e, 'ISR')
        ));
    }

    public function test_marca_fletera_sin_concepto_en_xml_se_trata_como_no_fletera(): void
    {
        config(['facturas.rfc_receptor' => '']);

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
        $this->assertStringContainsString('sin ClaveProdServ de flete', $result['checklist']['fletera']['label']);
        $this->assertSame('aprobada', $result['estatus']);
    }

    public function test_flete_se_detecta_por_clave_sat_sin_palabra_flete(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'rfc_emisor' => 'AAA010101AAA',
            'regimen' => '601',
            'clave' => '78101800',
            'descripcion' => 'Servicio logistico unidad 01',
            'ret_iva' => '40.00',
            'ret_isr' => '12.50',
            'total' => '1107.50',
            'uuid' => 'C1B2C3D4-E5F6-7890-ABCD-EF1234567899',
            'iva' => '160.00',
            'subtotal' => '1000.00',
        ];
        $service = new AltaFacturaValidationService($this->extractorMatching($opts));
        $result = $service->validar($this->cfdiXml($opts), false, 'AAA010101AAA', 'PDF', null);

        $this->assertTrue($result['datos']['tiene_concepto_flete']);
        $this->assertSame('clave', $result['datos']['deteccion_conceptos']['flete']);
        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
    }

    public function test_pdf_sin_total_rechaza_aunque_xml_sea_valido(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
            'total' => '5833.69',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF12345678B1',
        ];

        $textoSinTotal = strtolower(
            'uuid '.$opts['uuid']
            .' rfc '.($opts['rfc_emisor'] ?? 'XAXX010101000')
            .' receptor '.($opts['rfc_receptor'] ?? 'EKU9003173C9')
            .' sin el importe final'
        );

        $mock = $this->createMock(FacturaDocumentoExtractor::class);
        $mock->method('extraerDesdeContenido')->willReturn([
            'texto' => $textoSinTotal,
            'escaneado' => false,
        ]);

        $service = new AltaFacturaValidationService($mock);
        $result = $service->validar($this->cfdiXml($opts), false, 'XAXX010101000', 'PDF', null);

        $this->assertFalse($result['aprobado']);
        $this->assertFalse($result['checklist']['pdf_xml']['ok']);
        $this->assertTrue(collect($result['errores'])->contains(fn ($e) => str_contains($e, 'Total')));
    }

    public function test_pdf_acepta_total_con_comas(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
            'subtotal' => '5000.00',
            'iva' => '800.00',
            'ret_iva' => '533.34',
            'ret_isr' => '500.00',
            'total' => '4766.66',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF12345678B2',
        ];

        $texto = strtolower(
            'uuid '.$opts['uuid']
            .' emisor XAXX010101000 receptor EKU9003173C9 total 4,766.66'
        );

        $mock = $this->createMock(FacturaDocumentoExtractor::class);
        $mock->method('extraerDesdeContenido')->willReturn([
            'texto' => $texto,
            'escaneado' => false,
        ]);

        $service = new AltaFacturaValidationService($mock);
        $result = $service->validar($this->cfdiXml($opts), false, 'XAXX010101000', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['pdf_xml']['ok']);
        $this->assertTrue($result['datos']['pdf_coincidencias']['total']);
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
            'texto' => 'factura uuid a1b2c3d4-e5f6-7890-abcd-ef1234567890 rfc emisor bbb010101bbb rfc receptor eku9003173c9 total 953.33',
            'escaneado' => false,
        ]);

        $service = new AltaFacturaValidationService($bad);
        $result = $service->validar($this->cfdiXml($opts), false, 'XAXX010101000', 'PDF', null);

        $this->assertFalse($result['aprobado']);
        $this->assertSame('rechazada', $result['estatus']);
        $this->assertTrue(collect($result['errores'])->contains(fn ($e) => str_contains($e, 'RFC emisor')));
    }

    public function test_pdf_uuid_con_saltos_y_espacios_coincide(): void
    {
        config(['facturas.rfc_receptor' => '']);

        $opts = [
            'clave' => '01010101',
            'descripcion' => 'Servicio profesional',
            'uuid' => 'A1B2C3D4-E5F6-7890-ABCD-EF12345678D1',
        ];

        // Guiones unicode, zero-width space y saltos: la limpieza alfanumérica debe ignorarlos.
        $zwsp = "\u{200B}";
        $texto = "factura folio fiscal a1b2c3d4{$zwsp}—e5f6–7890‐\nabcd‐ef12 345678d1"
            ." rfc emisor xa{$zwsp}xx010101000 rfc receptor eku9003173c9 total 953.33";

        $mock = $this->createMock(FacturaDocumentoExtractor::class);
        $mock->method('extraerDesdeContenido')->willReturn([
            'texto' => $texto,
            'escaneado' => false,
        ]);

        $service = new AltaFacturaValidationService($mock);
        $result = $service->validar($this->cfdiXml($opts), false, 'XAXX010101000', 'PDF', null);

        $this->assertTrue($result['aprobado'], implode(' | ', $result['errores']));
        $this->assertTrue($result['checklist']['pdf_xml']['ok']);
        $this->assertTrue($result['datos']['pdf_coincidencias']['uuid']);
        $this->assertTrue($result['datos']['pdf_coincidencias']['rfc_emisor']);
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
