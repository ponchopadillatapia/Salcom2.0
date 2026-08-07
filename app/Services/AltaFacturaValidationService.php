<?php

namespace App\Services;

use App\Models\Factura;
use Illuminate\Support\Str;
use SimpleXMLElement;

class AltaFacturaValidationService
{
    public function __construct(
        private ?FacturaDocumentoExtractor $extractor = null,
    ) {
        $this->extractor ??= new FacturaDocumentoExtractor;
    }

    /**
     * Valida XML CFDI + cruce con PDF/OC + reglas fiscales.
     *
     * @return array{
     *   aprobado: bool,
     *   estatus: string,
     *   mensaje: string,
     *   errores: string[],
     *   advertencias: string[],
     *   checklist: array,
     *   datos: array
     * }
     */
    public function validar(
        string $xmlContent,
        bool $esFletera,
        ?string $rfcProveedor = null,
        ?string $pdfContent = null,
        ?string $ocContent = null,
    ): array {
        $errores = [];
        $advertencias = [];
        $checklist = [
            'xml' => ['ok' => false, 'label' => 'XML CFDI válido'],
            'pdf_xml' => ['ok' => false, 'label' => 'Factura PDF ↔ XML'],
            'oc_xml' => ['ok' => true, 'label' => 'OC no adjunta (opcional)'],
            'emisor' => ['ok' => false, 'label' => 'RFC emisor'],
            'receptor' => ['ok' => false, 'label' => 'RFC receptor Salcom'],
            'uuid' => ['ok' => false, 'label' => 'UUID único'],
            'claves_sat' => ['ok' => false, 'label' => 'ClaveProdServ SAT'],
            'regimen' => ['ok' => false, 'label' => 'Régimen fiscal'],
            'pago_cfdi' => ['ok' => false, 'label' => 'Forma / método / uso CFDI'],
            'producto' => ['ok' => false, 'label' => 'Concepto / producto'],
            'fletera' => ['ok' => false, 'label' => 'Indicador fletera'],
            'retenciones' => ['ok' => false, 'label' => 'Retenciones'],
            'totales' => ['ok' => false, 'label' => 'Totales coherentes'],
        ];

        $datos = [
            'uuid' => null,
            'folio' => null,
            'serie' => null,
            'rfc_emisor' => null,
            'nombre_emisor' => null,
            'rfc_receptor' => null,
            'regimen_fiscal' => null,
            'regimen_nombre' => null,
            'forma_pago' => null,
            'metodo_pago' => null,
            'uso_cfdi' => null,
            'moneda' => null,
            'producto' => null,
            'subtotal' => 0.0,
            'iva' => 0.0,
            'retencion_iva' => 0.0,
            'retencion_isr' => 0.0,
            'total' => 0.0,
            'fecha' => null,
            'tipo_comprobante' => null,
            'conceptos' => [],
            'claves_prod_serv' => [],
            'deteccion_conceptos' => [],
            'es_fletera' => $esFletera,
            'tiene_concepto_flete' => false,
            'tiene_concepto_comision' => false,
            'es_persona_fisica' => null,
            'retencion_esperada' => null,
            'tiene_oc' => $ocContent !== null && $ocContent !== '',
            'pdf_campos' => [],
            'oc_campos' => [],
        ];

        $xml = $this->parseXml($xmlContent);
        if ($xml === null) {
            $errores[] = 'El archivo XML no es un CFDI válido o está corrupto.';

            return $this->resultado($errores, $advertencias, $checklist, $datos);
        }

        $checklist['xml']['ok'] = true;
        $this->cargarDatosXml($xml, $datos, $errores);

        // UUID
        $datos['uuid'] = $this->extraerUuid($xml);
        if (! $datos['uuid']) {
            $errores[] = 'No se encontró el UUID del Timbre Fiscal Digital.';
        } else {
            $checklist['uuid']['ok'] = true;
            try {
                if (Factura::where('uuid_cfdi', $datos['uuid'])->exists()) {
                    $errores[] = 'Esta factura (UUID) ya fue registrada anteriormente.';
                    $checklist['uuid']['ok'] = false;
                }
            } catch (\Throwable) {
                // Tabla aún no migrada
            }
        }

        // RFC emisor vs proveedor
        if ($datos['rfc_emisor']) {
            if ($rfcProveedor && strtoupper($rfcProveedor) !== $datos['rfc_emisor']) {
                $errores[] = "El RFC emisor del XML ({$datos['rfc_emisor']}) no coincide con el RFC del proveedor ({$rfcProveedor}).";
            } elseif (! preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $datos['rfc_emisor'])) {
                $errores[] = "El RFC emisor '{$datos['rfc_emisor']}' no tiene formato válido.";
            } else {
                $checklist['emisor']['ok'] = true;
            }
        }

        // RFC receptor Salcom
        $rfcSalcom = strtoupper(trim((string) config('facturas.rfc_receptor', '')));
        if ($rfcSalcom !== '') {
            if ($datos['rfc_receptor'] !== $rfcSalcom) {
                $errores[] = "El RFC receptor ({$datos['rfc_receptor']}) no corresponde a Industrias Salcom ({$rfcSalcom}).";
            } else {
                $checklist['receptor']['ok'] = true;
            }
        } elseif ($datos['rfc_receptor']) {
            $checklist['receptor']['ok'] = true;
            $advertencias[] = 'No está configurado SALCOM_RFC; se omitió la validación estricta del receptor.';
        }

        // Impuestos
        $impuestos = $this->extraerImpuestos($xml);
        $datos['iva'] = $impuestos['iva_trasladado'];
        $datos['retencion_iva'] = $impuestos['retencion_iva'];
        $datos['retencion_isr'] = $impuestos['retencion_isr'];

        // Totales internos del CFDI
        $tol = (float) config('facturas.tolerancia_monto', 1);
        $esperado = round($datos['subtotal'] + $datos['iva'] - $datos['retencion_iva'] - $datos['retencion_isr'], 2);
        if (abs($esperado - $datos['total']) > $tol && $datos['total'] > 0) {
            if (abs($datos['subtotal'] + $datos['iva'] - $datos['total']) > $tol
                && abs($esperado - $datos['total']) > $tol) {
                $advertencias[] = 'Los totales del CFDI no cuadran exactamente con subtotal + IVA − retenciones (puede ser redondeo).';
            }
        }
        if ($datos['subtotal'] <= 0 && $datos['total'] <= 0) {
            $errores[] = 'El CFDI no tiene montos válidos (SubTotal/Total).';
        } else {
            $checklist['totales']['ok'] = true;
        }

        $conceptos = $this->detectarConceptos($xml, $errores, $advertencias);
        $datos['tiene_concepto_flete'] = $conceptos['flete'];
        $datos['tiene_concepto_comision'] = $conceptos['comision'];
        $datos['producto'] = ($conceptos['producto'] ?? '') !== '' ? $conceptos['producto'] : null;
        $datos['conceptos'] = $conceptos['descripciones'];
        $datos['claves_prod_serv'] = $conceptos['claves'];
        $datos['deteccion_conceptos'] = $conceptos['deteccion'];
        $checklist['claves_sat']['ok'] = $conceptos['claves_ok'];
        $checklist['claves_sat']['label'] = $conceptos['claves_ok']
            ? ('ClaveProdServ: '.implode(', ', $conceptos['claves'] ?: ['—']))
            : 'ClaveProdServ faltante o inválida';

        $this->validarRegimen($datos, $errores, $checklist);

        // Forma / método / uso CFDI + concepto (requeridos para pago automático)
        $this->validarDatosPagoCfdi($datos, $errores, $checklist);

        // Fletera: ClaveProdServ SAT manda (flete)
        if ($esFletera && ! $datos['tiene_concepto_flete']) {
            $esFletera = false;
            $datos['es_fletera'] = false;
            $advertencias[] = 'Marcó fletera, pero el XML no trae ClaveProdServ de flete. Se validó como no fletera (según régimen).';
            $checklist['fletera']['ok'] = true;
            $checklist['fletera']['label'] = 'No es fletera (sin ClaveProdServ de flete)';
        } elseif (! $esFletera && $datos['tiene_concepto_flete']) {
            $esFletera = true;
            $datos['es_fletera'] = true;
            $via = ($conceptos['deteccion']['flete'] ?? '') === 'clave' ? 'ClaveProdServ' : 'descripción';
            $advertencias[] = "El XML trae concepto de flete (detectado por {$via}): se aplican reglas de fletera.";
            $checklist['fletera']['ok'] = true;
            $checklist['fletera']['label'] = 'Flete detectado por '.$via;
        } else {
            $checklist['fletera']['ok'] = true;
            $checklist['fletera']['label'] = $esFletera ? 'Marcada como fletera' : 'No es fletera';
        }

        $this->validarRetenciones($datos, $esFletera, $errores, $advertencias, $checklist);

        // Cruce Factura PDF ↔ XML
        if ($pdfContent !== null && $pdfContent !== '') {
            $this->cruzarPdfConXml($pdfContent, $datos, $errores, $advertencias, $checklist);
        } else {
            $errores[] = 'Falta el PDF de la factura para validar contra el XML.';
            $checklist['pdf_xml']['ok'] = false;
        }

        // Cruce OC ↔ XML (solo si hay OC; sin OC no es observación ni error)
        if ($datos['tiene_oc']) {
            $this->cruzarOcConXml($ocContent, $datos, $errores, $advertencias, $checklist);
        } else {
            $checklist['oc_xml']['ok'] = true;
            $checklist['oc_xml']['label'] = 'OC no adjunta (opcional)';
        }

        return $this->resultado($errores, $advertencias, $checklist, $datos);
    }

    private function cargarDatosXml(SimpleXMLElement $xml, array &$datos, array &$errores): void
    {
        $attrs = $xml->attributes();
        $datos['subtotal'] = (float) ($attrs['SubTotal'] ?? 0);
        $datos['total'] = (float) ($attrs['Total'] ?? 0);
        $datos['folio'] = (string) ($attrs['Folio'] ?? '');
        $datos['serie'] = (string) ($attrs['Serie'] ?? '');
        $datos['fecha'] = (string) ($attrs['Fecha'] ?? '');
        $datos['tipo_comprobante'] = (string) ($attrs['TipoDeComprobante'] ?? '');
        $datos['moneda'] = strtoupper((string) ($attrs['Moneda'] ?? 'MXN'));
        $datos['forma_pago'] = $this->normalizarCodigoPago((string) ($attrs['FormaPago'] ?? ''), 2);
        $datos['metodo_pago'] = strtoupper(trim((string) ($attrs['MetodoPago'] ?? ''))) ?: null;

        if ($datos['tipo_comprobante'] !== '' && strtoupper($datos['tipo_comprobante']) !== 'I') {
            $errores[] = 'Solo se aceptan CFDI de tipo Ingreso (I). Tipo recibido: '.$datos['tipo_comprobante'];
        }

        $emisor = $this->findChild($xml, 'Emisor');
        if ($emisor) {
            $eAttrs = $emisor->attributes();
            $datos['rfc_emisor'] = strtoupper(trim((string) ($eAttrs['Rfc'] ?? '')));
            $datos['nombre_emisor'] = (string) ($eAttrs['Nombre'] ?? '');
            $datos['regimen_fiscal'] = str_pad(trim((string) ($eAttrs['RegimenFiscal'] ?? '')), 3, '0', STR_PAD_LEFT);
            if ($datos['regimen_fiscal'] === '000') {
                $datos['regimen_fiscal'] = null;
            }
        } else {
            $errores[] = 'No se encontró el nodo Emisor en el XML.';
        }

        $receptor = $this->findChild($xml, 'Receptor');
        if ($receptor) {
            $rAttrs = $receptor->attributes();
            $datos['rfc_receptor'] = strtoupper(trim((string) ($rAttrs['Rfc'] ?? '')));
            $datos['uso_cfdi'] = strtoupper(trim((string) ($rAttrs['UsoCFDI'] ?? ''))) ?: null;
        } else {
            $errores[] = 'No se encontró el nodo Receptor en el XML.';
        }
    }

    private function cruzarPdfConXml(
        string $pdfContent,
        array &$datos,
        array &$errores,
        array &$advertencias,
        array &$checklist,
    ): void {
        $extraido = $this->extractor->extraerDesdeContenido($pdfContent);
        $pdf = $extraido['campos'];
        $datos['pdf_campos'] = $pdf;

        if ($extraido['escaneado'] && empty(array_filter($pdf))) {
            $errores[] = 'No se pudo leer el PDF de la factura (posible escaneo). No se pudo verificar coincidencia con el XML.';
            $checklist['pdf_xml']['ok'] = false;
            $checklist['pdf_xml']['label'] = 'PDF ilegible';

            return;
        }

        $mismatches = [];
        $tol = (float) config('facturas.tolerancia_monto', 1);

        $this->compararTexto('RFC emisor', $datos['rfc_emisor'], $pdf['rfc_emisor'] ?? null, $mismatches, true);
        $this->compararTexto('RFC receptor', $datos['rfc_receptor'], $pdf['rfc_receptor'] ?? null, $mismatches, true);

        if (! empty($pdf['uuid'])) {
            $this->compararTexto('UUID / folio fiscal', $datos['uuid'], $pdf['uuid'], $mismatches, true);
        }

        if (! empty($pdf['fecha']) && ! empty($datos['fecha'])) {
            $fechaXml = substr((string) $datos['fecha'], 0, 10);
            if ($fechaXml !== $pdf['fecha']) {
                $mismatches[] = "Fecha de emisión: PDF ({$pdf['fecha']}) ≠ XML ({$fechaXml}).";
            }
        }

        if (! empty($pdf['regimen_fiscal']) && ! empty($datos['regimen_fiscal'])) {
            $this->compararTexto('Régimen fiscal', $datos['regimen_fiscal'], $pdf['regimen_fiscal'], $mismatches, true);
        }

        if (! empty($pdf['metodo_pago']) && ! empty($datos['metodo_pago'])) {
            $this->compararTexto('Método de pago', $datos['metodo_pago'], $pdf['metodo_pago'], $mismatches, true);
        }

        if (! empty($pdf['forma_pago']) && ! empty($datos['forma_pago'])) {
            $this->compararTexto('Forma de pago', $datos['forma_pago'], $pdf['forma_pago'], $mismatches, true);
        }

        if (! empty($pdf['moneda']) && ! empty($datos['moneda'])) {
            $this->compararTexto('Moneda', $datos['moneda'], $pdf['moneda'], $mismatches, true);
        }

        $this->compararMonto('Subtotal', $datos['subtotal'], $pdf['subtotal'] ?? null, $tol, $mismatches);
        $this->compararMonto('IVA', $datos['iva'], $pdf['iva'] ?? null, $tol, $mismatches);
        $this->compararMonto('Retención IVA', $datos['retencion_iva'], $pdf['retencion_iva'] ?? null, $tol, $mismatches, false);
        $this->compararMonto('Retención ISR', $datos['retencion_isr'], $pdf['retencion_isr'] ?? null, $tol, $mismatches, false);
        $this->compararMonto('Total', $datos['total'], $pdf['total'] ?? null, $tol, $mismatches);

        // Conceptos: descripción y ClaveProdServ SAT
        $pdfConceptos = $pdf['conceptos'] ?? [];
        if ($pdfConceptos && $datos['conceptos']) {
            $xmlBlob = mb_strtolower(implode(' | ', $datos['conceptos']), 'UTF-8');
            $hit = false;
            foreach ($pdfConceptos as $desc) {
                $needle = mb_strtolower(mb_substr($desc, 0, 40), 'UTF-8');
                if ($needle !== '' && str_contains($xmlBlob, $needle)) {
                    $hit = true;
                    break;
                }
            }
            if (! $hit) {
                $mismatches[] = 'Conceptos / descripción: no se encontró coincidencia entre la factura PDF y el XML.';
            }
        }

        $pdfClaves = array_values(array_filter(array_map('strval', $pdf['claves_prod_serv'] ?? [])));
        $xmlClaves = array_values(array_filter(array_map('strval', $datos['claves_prod_serv'] ?? [])));
        if ($pdfClaves !== [] && $xmlClaves !== []) {
            $faltanEnXml = array_values(array_diff($pdfClaves, $xmlClaves));
            if ($faltanEnXml !== []) {
                $mismatches[] = 'ClaveProdServ SAT: en PDF ('.implode(', ', $faltanEnXml).') no aparecen en el XML.';
            }
            $faltanEnPdf = array_values(array_diff($xmlClaves, $pdfClaves));
            if ($faltanEnPdf !== []) {
                $advertencias[] = 'ClaveProdServ SAT del XML ('.implode(', ', $faltanEnPdf).') no se detectó en el PDF; se usó el XML como fuente oficial.';
            }
        } elseif ($xmlClaves !== [] && $pdfClaves === [] && ! $extraido['escaneado']) {
            $advertencias[] = 'No se detectó ClaveProdServ en el PDF; se validó solo con las claves del XML ('.implode(', ', $xmlClaves).').';
        }

        if ($mismatches) {
            foreach ($mismatches as $m) {
                $errores[] = 'Factura PDF ↔ XML: '.$m;
            }
            $checklist['pdf_xml']['ok'] = false;
            $checklist['pdf_xml']['label'] = 'PDF no coincide con XML';
        } else {
            $checklist['pdf_xml']['ok'] = true;
            $checklist['pdf_xml']['label'] = 'Factura PDF ↔ XML coinciden';
            if ($extraido['escaneado']) {
                $advertencias[] = 'El PDF parece escaneado o con poco texto; la comparación se hizo con los campos detectados.';
            }
        }
    }

    private function cruzarOcConXml(
        string $ocContent,
        array &$datos,
        array &$errores,
        array &$advertencias,
        array &$checklist,
    ): void {
        $extraido = $this->extractor->extraerDesdeContenido($ocContent);
        $oc = $extraido['campos'];
        $datos['oc_campos'] = $oc;

        if ($extraido['escaneado'] && empty(array_filter($oc))) {
            $errores[] = 'No se pudo leer la Orden de Compra (posible escaneo). No se pudo verificar contra el XML.';
            $checklist['oc_xml']['ok'] = false;
            $checklist['oc_xml']['label'] = 'OC ilegible';

            return;
        }

        $mismatches = [];
        $tol = (float) config('facturas.tolerancia_monto', 1);

        // RFC proveedor = emisor del XML
        if (! empty($oc['rfc_emisor']) || ! empty($oc['rfc_receptor'])) {
            $rfcOc = $oc['rfc_emisor'] ?? null;
            // En OC a veces el proveedor es el único RFC no-Salcom
            $rfcSalcom = strtoupper(trim((string) config('facturas.rfc_receptor', '')));
            if (! $rfcOc && ! empty($oc['rfc_receptor']) && $oc['rfc_receptor'] !== $rfcSalcom) {
                $rfcOc = $oc['rfc_receptor'];
            }
            if ($rfcOc && $datos['rfc_emisor'] && strtoupper($rfcOc) !== $datos['rfc_emisor']) {
                $mismatches[] = "RFC del proveedor: OC ({$rfcOc}) ≠ XML ({$datos['rfc_emisor']}).";
            }
        }

        if (! empty($oc['metodo_pago']) && ! empty($datos['metodo_pago'])) {
            $this->compararTexto('Método de pago', $datos['metodo_pago'], $oc['metodo_pago'], $mismatches, true);
        }
        if (! empty($oc['forma_pago']) && ! empty($datos['forma_pago'])) {
            $this->compararTexto('Forma de pago', $datos['forma_pago'], $oc['forma_pago'], $mismatches, true);
        }
        if (! empty($oc['moneda']) && ! empty($datos['moneda'])) {
            $this->compararTexto('Moneda', $datos['moneda'], $oc['moneda'], $mismatches, true);
        }

        $this->compararMonto('Total autorizado', $datos['total'], $oc['total'] ?? null, $tol, $mismatches);
        $this->compararMonto('Subtotal / importe', $datos['subtotal'], $oc['subtotal'] ?? null, $tol, $mismatches, false);

        if ($mismatches) {
            foreach ($mismatches as $m) {
                $errores[] = 'OC ↔ XML: '.$m;
            }
            $checklist['oc_xml']['ok'] = false;
            $checklist['oc_xml']['label'] = 'OC no coincide con XML';
        } else {
            $checklist['oc_xml']['ok'] = true;
            $checklist['oc_xml']['label'] = 'OC ↔ XML coinciden';
            if (($oc['total'] ?? null) === null && ($oc['subtotal'] ?? null) === null) {
                $advertencias[] = 'La OC se adjuntó, pero no se detectaron importes claros; revisa manualmente.';
            }
        }
    }

    private function compararTexto(string $campo, ?string $xmlVal, ?string $docVal, array &$mismatches, bool $requeridoEnDoc): void
    {
        if ($docVal === null || $docVal === '') {
            if ($requeridoEnDoc && $xmlVal) {
                // No forzar error si el PDF no trae el campo — solo cuando ambos existen y difieren,
                // salvo RFCs críticos que sí pedimos si el PDF es legible.
                return;
            }

            return;
        }
        if ($xmlVal === null || $xmlVal === '') {
            return;
        }
        if (strtoupper(trim($xmlVal)) !== strtoupper(trim($docVal))) {
            $mismatches[] = "{$campo}: documento ({$docVal}) ≠ XML ({$xmlVal}).";
        }
    }

    private function compararMonto(
        string $campo,
        float $xmlVal,
        ?float $docVal,
        float $tol,
        array &$mismatches,
        bool $requerido = true,
    ): void {
        if ($docVal === null) {
            return;
        }
        if (! $requerido && $docVal <= 0 && $xmlVal <= 0) {
            return;
        }
        if (abs($xmlVal - $docVal) > $tol) {
            $mismatches[] = sprintf(
                '%s: documento ($%s) ≠ XML ($%s).',
                $campo,
                number_format($docVal, 2),
                number_format($xmlVal, 2)
            );
        }
    }

    private function validarRegimen(array &$datos, array &$errores, array &$checklist): void
    {
        $regimenes = config('facturas.regimenes', []);
        $codigo = $datos['regimen_fiscal'];

        if (! $codigo) {
            $errores[] = 'El XML no indica el Régimen Fiscal del emisor.';

            return;
        }

        if (! isset($regimenes[$codigo])) {
            $errores[] = "El régimen fiscal {$codigo} no está en el catálogo SAT reconocido por el portal.";

            return;
        }

        $meta = $regimenes[$codigo];
        $datos['regimen_nombre'] = $meta['nombre'];

        $rfc = $datos['rfc_emisor'] ?? '';
        $len = strlen($rfc);
        $esFisica = $len === 13;
        $esMoral = $len === 12;
        $datos['es_persona_fisica'] = $esFisica ?: ($esMoral ? false : null);

        if ($esMoral && empty($meta['moral'])) {
            $errores[] = "El régimen {$codigo} ({$meta['nombre']}) no aplica a persona moral, pero el RFC emisor es moral (12 caracteres).";

            return;
        }
        if ($esFisica && empty($meta['fisica'])) {
            $errores[] = "El régimen {$codigo} ({$meta['nombre']}) no aplica a persona física, pero el RFC emisor es física (13 caracteres).";

            return;
        }

        $checklist['regimen']['ok'] = true;
        $checklist['regimen']['label'] = "Régimen {$codigo} — {$meta['nombre']}";
    }

    private function validarRetenciones(array &$datos, bool $esFletera, array &$errores, array &$advertencias, array &$checklist): void
    {
        $cfg = config('facturas.retenciones', []);
        $tol = (float) config('facturas.tolerancia_monto', 1);

        $aplicaFlete = $esFletera || ! empty($datos['tiene_concepto_flete']);
        $aplicaComision = ! empty($datos['tiene_concepto_comision']) && ($datos['es_persona_fisica'] === true);

        if ($aplicaFlete) {
            $regla = $cfg['flete'] ?? ['iva' => 0.04, 'isr' => 0.0125, 'requiere_retencion' => true];
            $origen = 'flete';
        } elseif ($aplicaComision) {
            $regla = $cfg['comision_fisica'] ?? ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true];
            $origen = 'comision_fisica';
        } elseif (! empty($datos['tiene_concepto_comision']) && $datos['es_persona_fisica'] === false) {
            $regla = ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false];
            $origen = 'comision_moral_sin_retencion';
            $advertencias[] = 'Concepto de comisión en persona moral: según Contabilidad no aplica retención de IVA por comisión.';
        } elseif (($datos['regimen_fiscal'] ?? '') === '626') {
            // RESICO: PF → ISR 1.25%; PM → sin esa retención
            if ($datos['es_persona_fisica'] === true) {
                $regla = $cfg['resico_fisica'] ?? ['iva' => 0.0, 'isr' => 0.0125, 'requiere_retencion' => true];
                $origen = 'resico_fisica';
            } else {
                $regla = $cfg['resico_moral'] ?? ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false];
                $origen = 'resico_moral';
            }
        } else {
            $porRegimen = $cfg['por_regimen'] ?? [];
            $codigo = $datos['regimen_fiscal'] ?? '_default';
            $regla = $porRegimen[$codigo] ?? ($porRegimen['_default'] ?? ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false]);
            $origen = 'regimen_'.$datos['regimen_fiscal'];
        }

        $datos['retencion_esperada'] = [
            'iva_tasa' => $regla['iva'],
            'isr_tasa' => $regla['isr'],
            'requiere' => (bool) $regla['requiere_retencion'],
            'origen' => $origen,
        ];

        $base = (float) $datos['subtotal'];
        $ivaEsp = round($base * (float) $regla['iva'], 2);
        $isrEsp = round($base * (float) $regla['isr'], 2);
        $ivaXml = round((float) $datos['retencion_iva'], 2);
        $isrXml = round((float) $datos['retencion_isr'], 2);

        $erroresRet = [];

        if ($regla['requiere_retencion']) {
            if ($aplicaFlete && $ivaXml <= 0) {
                $erroresRet[] = 'Concepto flete / fletera: el XML debe incluir retención de IVA del 4% sobre el subtotal.';
            } elseif ($origen === 'resico_fisica' && $isrXml <= 0) {
                $erroresRet[] = 'RESICO persona física: el XML debe incluir retención de ISR del 1.25% sobre el subtotal.';
            } elseif ($ivaXml <= 0 && $isrXml <= 0) {
                $erroresRet[] = $origen === 'comision_fisica'
                    ? 'Comisión en persona física: el XML debe incluir retenciones.'
                    : "El régimen {$datos['regimen_fiscal']} requiere retenciones y el XML no las trae.";
            }

            if ($regla['iva'] > 0) {
                if ($ivaXml <= 0) {
                    // ya cubierto arriba para flete
                } elseif (abs($ivaXml - $ivaEsp) > $tol) {
                    $erroresRet[] = sprintf(
                        'Retención IVA incorrecta: se esperaba $%s (%.4f%%) y el XML trae $%s.',
                        number_format($ivaEsp, 2),
                        $regla['iva'] * 100,
                        number_format($ivaXml, 2)
                    );
                }
            }

            if ($regla['isr'] > 0) {
                if ($isrXml <= 0 && $origen === 'resico_fisica') {
                    // ya cubierto
                } elseif ($isrXml > 0 && abs($isrXml - $isrEsp) > $tol) {
                    $erroresRet[] = sprintf(
                        'Retención ISR incorrecta: se esperaba $%s (%.4f%%) y el XML trae $%s.',
                        number_format($isrEsp, 2),
                        $regla['isr'] * 100,
                        number_format($isrXml, 2)
                    );
                } elseif ($aplicaFlete && $isrXml <= 0) {
                    $advertencias[] = 'Flete: se esperaba también ISR retenido ('.($regla['isr'] * 100).'%); el XML no lo trae.';
                } elseif ($origen === 'resico_fisica' && $isrXml > 0 && abs($isrXml - $isrEsp) > $tol) {
                    $erroresRet[] = sprintf(
                        'Retención ISR RESICO incorrecta: se esperaba $%s (1.25%%) y el XML trae $%s.',
                        number_format($isrEsp, 2),
                        number_format($isrXml, 2)
                    );
                }
            }
        } elseif ($origen === 'resico_moral' && $isrXml > $tol) {
            $advertencias[] = sprintf(
                'RESICO persona moral: no debe aplicarse retención ISR 1.25%%, pero el XML trae $%s. Se registrará tal cual.',
                number_format($isrXml, 2)
            );
        } elseif ($ivaXml > $tol || $isrXml > $tol) {
            $advertencias[] = sprintf(
                'No se exige retención para este caso, pero el XML trae IVA ret. $%s / ISR ret. $%s. Se registrarán tal cual.',
                number_format($ivaXml, 2),
                number_format($isrXml, 2)
            );
        }

        foreach ($erroresRet as $e) {
            $errores[] = $e;
        }

        $checklist['retenciones']['ok'] = empty($erroresRet);
        if ($checklist['retenciones']['ok']) {
            $pctIva = $regla['iva'] * 100;
            $pctIsr = $regla['isr'] * 100;
            $checklist['retenciones']['label'] = $regla['requiere_retencion']
                ? sprintf('Retenciones OK (IVA %.2f%% / ISR %.2f%% · %s)', $pctIva, $pctIsr, $origen)
                : 'Sin retención requerida';
        }
    }

    /**
     * Detecta flete/comisión priorizando ClaveProdServ SAT; la descripción es respaldo.
     *
     * @return array{
     *   flete: bool,
     *   comision: bool,
     *   producto: string,
     *   descripciones: list<string>,
     *   claves: list<string>,
     *   claves_ok: bool,
     *   deteccion: array{flete: ?string, comision: ?string}
     * }
     */
    private function detectarConceptos(SimpleXMLElement $xml, array &$errores, array &$advertencias): array
    {
        $cfg = config('facturas.conceptos', []);
        $flete = false;
        $comision = false;
        $descripciones = [];
        $claves = [];
        $clavesOk = true;
        $deteccion = ['flete' => null, 'comision' => null];

        $nodos = $xml->xpath("//*[local-name()='Concepto']") ?: [];
        if (! $nodos) {
            $errores[] = 'El XML no trae nodos Concepto con ClaveProdServ.';
            $clavesOk = false;

            return [
                'flete' => false,
                'comision' => false,
                'producto' => '',
                'descripciones' => [],
                'claves' => [],
                'claves_ok' => false,
                'deteccion' => $deteccion,
            ];
        }

        foreach ($nodos as $nodo) {
            $attrs = $nodo->attributes();
            $clave = trim((string) ($attrs['ClaveProdServ'] ?? ''));
            $desc = (string) ($attrs['Descripcion'] ?? '');
            if ($desc !== '') {
                $descripciones[] = $desc;
            }

            if ($clave === '' || ! preg_match('/^\d{8}$/', $clave)) {
                $errores[] = 'Hay un concepto en el XML sin ClaveProdServ SAT válida (8 dígitos). Es obligatoria para clasificar el bien o servicio.';
                $clavesOk = false;

                continue;
            }

            $claves[] = $clave;
            $descAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower($desc, 'UTF-8')) ?: mb_strtolower($desc, 'UTF-8');

            if (! $flete) {
                if ($this->coincidePorClave($clave, $cfg['flete'] ?? [])) {
                    $flete = true;
                    $deteccion['flete'] = 'clave';
                } elseif ($this->coincidePorDescripcion($descAscii, $cfg['flete'] ?? [])) {
                    $flete = true;
                    $deteccion['flete'] = 'descripcion';
                    $advertencias[] = "Flete detectado solo por descripción («{$desc}»), no por ClaveProdServ ({$clave}). Prefiere claves SAT de flete.";
                }
            }

            if (! $comision) {
                if ($this->coincidePorClave($clave, $cfg['comision'] ?? [])) {
                    $comision = true;
                    $deteccion['comision'] = 'clave';
                } elseif ($this->coincidePorDescripcion($descAscii, $cfg['comision'] ?? [])) {
                    $comision = true;
                    $deteccion['comision'] = 'descripcion';
                    $advertencias[] = "Comisión detectada solo por descripción («{$desc}»), no por ClaveProdServ ({$clave}). Prefiere claves SAT de comisión.";
                }
            }
        }

        $claves = array_values(array_unique($claves));

        $producto = implode(' · ', array_slice(array_unique($descripciones), 0, 5));
        if (mb_strlen($producto) > 255) {
            $producto = mb_substr($producto, 0, 252).'…';
        }

        return [
            'flete' => $flete,
            'comision' => $comision,
            'producto' => $producto,
            'descripciones' => $descripciones,
            'claves' => $claves,
            'claves_ok' => $clavesOk && $claves !== [],
            'deteccion' => $deteccion,
        ];
    }

    private function validarDatosPagoCfdi(array &$datos, array &$errores, array &$checklist): void
    {
        $formas = config('facturas.formas_pago', []);
        $metodos = config('facturas.metodos_pago', []);
        $usos = config('facturas.usos_cfdi', []);
        $okPago = true;

        if (! $datos['forma_pago']) {
            $errores[] = 'El XML no indica FormaPago (requerida para alta y pago automático).';
            $okPago = false;
        } elseif (! array_key_exists($datos['forma_pago'], $formas)) {
            $errores[] = "FormaPago '{$datos['forma_pago']}' del XML no está en el catálogo aceptado.";
            $okPago = false;
        }

        if (! $datos['metodo_pago']) {
            $errores[] = 'El XML no indica MetodoPago (PUE/PPD).';
            $okPago = false;
        } elseif (! array_key_exists($datos['metodo_pago'], $metodos)) {
            $errores[] = "MetodoPago '{$datos['metodo_pago']}' del XML no está en el catálogo aceptado.";
            $okPago = false;
        }

        if (! $datos['uso_cfdi']) {
            $errores[] = 'El XML no indica UsoCFDI del receptor.';
            $okPago = false;
        } elseif (! array_key_exists($datos['uso_cfdi'], $usos)) {
            $errores[] = "UsoCFDI '{$datos['uso_cfdi']}' del XML no está en el catálogo aceptado.";
            $okPago = false;
        }

        $checklist['pago_cfdi']['ok'] = $okPago;
        if ($okPago) {
            $checklist['pago_cfdi']['label'] = "Forma {$datos['forma_pago']} · {$datos['metodo_pago']} · Uso {$datos['uso_cfdi']}";
        }

        if (! $datos['producto']) {
            $errores[] = 'El XML no trae descripción en Conceptos (producto/concepto requerido).';
            $checklist['producto']['ok'] = false;
        } else {
            $checklist['producto']['ok'] = true;
            $checklist['producto']['label'] = 'Concepto: '.Str::limit($datos['producto'], 60);
        }
    }

    private function normalizarCodigoPago(string $valor, int $pad): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        if (ctype_digit($valor)) {
            return str_pad($valor, $pad, '0', STR_PAD_LEFT);
        }

        return $valor;
    }

    private function coincidePorClave(string $clave, array $cfg): bool
    {
        foreach ($cfg['claves'] ?? [] as $c) {
            if ($clave !== '' && $clave === (string) $c) {
                return true;
            }
        }

        return false;
    }

    private function coincidePorDescripcion(string $descAscii, array $cfg): bool
    {
        foreach ($cfg['palabras'] ?? [] as $p) {
            $pAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower((string) $p, 'UTF-8')) ?: $p;
            if ($pAscii !== '' && str_contains($descAscii, $pAscii)) {
                return true;
            }
        }

        return false;
    }

    private function parseXml(string $content): ?SimpleXMLElement
    {
        $content = trim($content);
        if ($content === '' || ! str_contains($content, '<')) {
            return null;
        }

        libxml_use_internal_errors(true);
        try {
            $xml = new SimpleXMLElement($content);
        } catch (\Throwable) {
            return null;
        }

        $namespaces = $xml->getDocNamespaces(true);
        foreach ($namespaces as $prefix => $ns) {
            if ($prefix !== '') {
                $xml->registerXPathNamespace($prefix, $ns);
            }
        }
        if (isset($namespaces[''])) {
            $xml->registerXPathNamespace('cfdi', $namespaces['']);
        }

        return $xml;
    }

    private function findChild(SimpleXMLElement $xml, string $localName): ?SimpleXMLElement
    {
        $nodes = $xml->xpath("./*[local-name()='{$localName}']");
        if ($nodes && isset($nodes[0])) {
            return $nodes[0];
        }

        $nodes = $xml->xpath("//*[local-name()='{$localName}']");
        if ($nodes && isset($nodes[0])) {
            return $nodes[0];
        }

        return null;
    }

    private function extraerUuid(SimpleXMLElement $xml): ?string
    {
        $nodes = $xml->xpath("//*[local-name()='TimbreFiscalDigital']");
        if ($nodes && isset($nodes[0])) {
            $uuid = (string) ($nodes[0]->attributes()['UUID'] ?? '');

            return $uuid !== '' ? strtoupper($uuid) : null;
        }

        return null;
    }

    /**
     * Lee IVA/retenciones del nodo Impuestos del Comprobante (resumen SAT).
     * Ignora Impuestos anidados en Conceptos (evita duplicar importes en CFDI 4.0).
     * Si no existe el nodo global, cae a traslados/retenciones bajo Conceptos.
     *
     * @return array{iva_trasladado: float, retencion_iva: float, retencion_isr: float}
     */
    private function extraerImpuestos(SimpleXMLElement $xml): array
    {
        $ivaTrasladado = 0.0;
        $retIva = 0.0;
        $retIsr = 0.0;

        $impRoot = $xml->xpath("/*[local-name()='Comprobante']/*[local-name()='Impuestos']");
        $nodo = ($impRoot && isset($impRoot[0])) ? $impRoot[0] : null;

        if ($nodo !== null) {
            $totTras = (float) ($nodo->attributes()['TotalImpuestosTrasladados'] ?? 0);
            $traslados = $nodo->xpath("./*[local-name()='Traslados']/*[local-name()='Traslado']") ?: [];
            foreach ($traslados as $t) {
                $imp = (string) ($t->attributes()['Impuesto'] ?? '');
                $importe = (float) ($t->attributes()['Importe'] ?? 0);
                if ($imp === '002') {
                    $ivaTrasladado += $importe;
                }
            }
            if ($ivaTrasladado <= 0 && $totTras > 0) {
                $ivaTrasladado = $totTras;
            }

            $retenciones = $nodo->xpath("./*[local-name()='Retenciones']/*[local-name()='Retencion']") ?: [];
            foreach ($retenciones as $r) {
                $imp = (string) ($r->attributes()['Impuesto'] ?? '');
                $importe = (float) ($r->attributes()['Importe'] ?? 0);
                if ($imp === '002') {
                    $retIva += $importe;
                } elseif ($imp === '001') {
                    $retIsr += $importe;
                }
            }
        } else {
            // Fallback: solo conceptos (XMLs incompletos / pruebas)
            $traslados = $xml->xpath("/*[local-name()='Comprobante']/*[local-name()='Conceptos']//*[local-name()='Traslado']") ?: [];
            foreach ($traslados as $t) {
                $imp = (string) ($t->attributes()['Impuesto'] ?? '');
                $importe = (float) ($t->attributes()['Importe'] ?? 0);
                if ($imp === '002') {
                    $ivaTrasladado += $importe;
                }
            }
            $retenciones = $xml->xpath("/*[local-name()='Comprobante']/*[local-name()='Conceptos']//*[local-name()='Retencion']") ?: [];
            foreach ($retenciones as $r) {
                $imp = (string) ($r->attributes()['Impuesto'] ?? '');
                $importe = (float) ($r->attributes()['Importe'] ?? 0);
                if ($imp === '002') {
                    $retIva += $importe;
                } elseif ($imp === '001') {
                    $retIsr += $importe;
                }
            }
        }

        return [
            'iva_trasladado' => round($ivaTrasladado, 2),
            'retencion_iva' => round($retIva, 2),
            'retencion_isr' => round($retIsr, 2),
        ];
    }

    private function resultado(array $errores, array $advertencias, array $checklist, array $datos): array
    {
        $aprobado = empty($errores);

        if (! $aprobado) {
            $estatus = 'rechazada';
            $mensaje = 'La factura fue rechazada: hay diferencias entre documentos o no cumple las reglas fiscales.';
        } else {
            $estatus = 'aprobada';
            $mensaje = 'Aprobada: los documentos coinciden y cumplen las reglas fiscales.';
        }

        return [
            'aprobado' => $aprobado,
            'estatus' => $estatus,
            'mensaje' => $mensaje,
            'errores' => $errores,
            'advertencias' => $advertencias,
            'checklist' => $checklist,
            'datos' => $datos,
        ];
    }
}
