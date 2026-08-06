<?php

namespace App\Services;

use App\Models\Factura;
use SimpleXMLElement;

class AltaFacturaValidationService
{
    /**
     * Valida XML CFDI + reglas de régimen, fletera y retenciones.
     *
     * @return array{aprobado: bool, errores: string[], advertencias: string[], checklist: array, datos: array}
     */
    public function validar(string $xmlContent, bool $esFletera, ?string $rfcProveedor = null): array
    {
        $errores = [];
        $advertencias = [];
        $checklist = [
            'xml' => ['ok' => false, 'label' => 'XML CFDI válido'],
            'emisor' => ['ok' => false, 'label' => 'RFC emisor'],
            'receptor' => ['ok' => false, 'label' => 'RFC receptor Salcom'],
            'uuid' => ['ok' => false, 'label' => 'UUID único'],
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
            'producto' => null,
            'subtotal' => 0.0,
            'iva' => 0.0,
            'retencion_iva' => 0.0,
            'retencion_isr' => 0.0,
            'total' => 0.0,
            'fecha' => null,
            'tipo_comprobante' => null,
            'es_fletera' => $esFletera,
            'tiene_concepto_flete' => false,
            'tiene_concepto_comision' => false,
            'es_persona_fisica' => null,
            'retencion_esperada' => null,
        ];

        $xml = $this->parseXml($xmlContent);
        if ($xml === null) {
            $errores[] = 'El archivo XML no es un CFDI válido o está corrupto.';

            return $this->resultado(false, $errores, $advertencias, $checklist, $datos);
        }

        $checklist['xml']['ok'] = true;

        $attrs = $xml->attributes();
        $datos['subtotal'] = (float) ($attrs['SubTotal'] ?? 0);
        $datos['total'] = (float) ($attrs['Total'] ?? 0);
        $datos['folio'] = (string) ($attrs['Folio'] ?? '');
        $datos['serie'] = (string) ($attrs['Serie'] ?? '');
        $datos['fecha'] = (string) ($attrs['Fecha'] ?? '');
        $datos['tipo_comprobante'] = (string) ($attrs['TipoDeComprobante'] ?? '');
        $datos['forma_pago'] = $this->normalizarCodigoPago((string) ($attrs['FormaPago'] ?? ''), 2);
        $datos['metodo_pago'] = strtoupper(trim((string) ($attrs['MetodoPago'] ?? ''))) ?: null;

        if ($datos['tipo_comprobante'] !== '' && strtoupper($datos['tipo_comprobante']) !== 'I') {
            $errores[] = 'Solo se aceptan CFDI de tipo Ingreso (I). Tipo recibido: '.$datos['tipo_comprobante'];
        }

        // Emisor
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

        // Receptor
        $receptor = $this->findChild($xml, 'Receptor');
        if ($receptor) {
            $rAttrs = $receptor->attributes();
            $datos['rfc_receptor'] = strtoupper(trim((string) ($rAttrs['Rfc'] ?? '')));
            $datos['uso_cfdi'] = strtoupper(trim((string) ($rAttrs['UsoCFDI'] ?? ''))) ?: null;
        } else {
            $errores[] = 'No se encontró el nodo Receptor en el XML.';
        }

        // UUID del timbre
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

        // Impuestos (traslados y retenciones)
        $impuestos = $this->extraerImpuestos($xml);
        $datos['iva'] = $impuestos['iva_trasladado'];
        $datos['retencion_iva'] = $impuestos['retencion_iva'];
        $datos['retencion_isr'] = $impuestos['retencion_isr'];

        // Totales
        $esperado = round($datos['subtotal'] + $datos['iva'] - $datos['retencion_iva'] - $datos['retencion_isr'], 2);
        $tol = (float) config('facturas.tolerancia_monto', 1);
        if (abs($esperado - $datos['total']) > $tol && $datos['total'] > 0) {
            // Algunos CFDIs ya traen el total neto; solo advertir si hay gran diferencia
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

        // Conceptos: flete / comisión (ClaveProdServ o descripción) + texto producto
        $conceptos = $this->detectarConceptos($xml);
        $datos['tiene_concepto_flete'] = $conceptos['flete'];
        $datos['tiene_concepto_comision'] = $conceptos['comision'];
        $datos['producto'] = $conceptos['producto'] !== '' ? $conceptos['producto'] : null;

        // Régimen
        $this->validarRegimen($datos, $errores, $checklist);

        // Forma / método / uso CFDI + concepto (requeridos para pago automático)
        $this->validarDatosPagoCfdi($datos, $errores, $checklist);

        // Fletera: el XML manda. Si marcaron Sí pero no hay flete en el CFDI, se trata como No.
        if ($esFletera && ! $datos['tiene_concepto_flete']) {
            $esFletera = false;
            $datos['es_fletera'] = false;
            $advertencias[] = 'Marcó fletera, pero el XML no trae clave/descripción de flete. Se validó como no fletera (según régimen).';
            $checklist['fletera']['ok'] = true;
            $checklist['fletera']['label'] = 'No es fletera (corregido: sin flete en XML)';
        } elseif (! $esFletera && $datos['tiene_concepto_flete']) {
            $esFletera = true;
            $datos['es_fletera'] = true;
            $advertencias[] = 'El XML trae concepto de flete: se aplican reglas de fletera aunque no lo haya marcado.';
            $checklist['fletera']['ok'] = true;
            $checklist['fletera']['label'] = 'Concepto flete detectado en XML';
        } else {
            $checklist['fletera']['ok'] = true;
            $checklist['fletera']['label'] = $esFletera ? 'Marcada como fletera' : 'No es fletera';
        }

        // Retenciones: flete siempre IVA; comisión solo persona física; resto por régimen
        $this->validarRetenciones($datos, $esFletera, $errores, $advertencias, $checklist);

        $aprobado = empty($errores);

        return $this->resultado($aprobado, $errores, $advertencias, $checklist, $datos);
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
            $regla = $cfg['flete'] ?? ($cfg['fletera'] ?? ['iva' => 0.04, 'isr' => 0.0125, 'requiere_retencion' => true]);
            $origen = 'flete';
        } elseif ($aplicaComision) {
            $regla = $cfg['comision_fisica'] ?? ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true];
            $origen = 'comision_fisica';
        } elseif (! empty($datos['tiene_concepto_comision']) && $datos['es_persona_fisica'] === false) {
            // Comisión en persona moral: Contabilidad indica que NO aplica retención
            $regla = ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false];
            $origen = 'comision_moral_sin_retencion';
            $advertencias[] = 'Concepto de comisión en persona moral: según Contabilidad no aplica retención de IVA por comisión.';
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
                // Regla Contabilidad: flete siempre retención IVA
                $erroresRet[] = 'Concepto flete / fletera: el XML debe incluir retención de IVA (no importa el régimen).';
            } elseif ($ivaXml <= 0 && $isrXml <= 0) {
                $erroresRet[] = $origen === 'comision_fisica'
                    ? 'Comisión en persona física: el XML debe incluir retenciones.'
                    : "El régimen {$datos['regimen_fiscal']} requiere retenciones y el XML no las trae.";
            }

            if ($ivaXml > 0 && abs($ivaXml - $ivaEsp) > $tol && $regla['iva'] > 0) {
                $erroresRet[] = sprintf(
                    'Retención IVA incorrecta: se esperaba $%s (%.4f%%) y el XML trae $%s.',
                    number_format($ivaEsp, 2),
                    $regla['iva'] * 100,
                    number_format($ivaXml, 2)
                );
            }
            if ($isrXml > 0 && abs($isrXml - $isrEsp) > $tol && $regla['isr'] > 0) {
                $erroresRet[] = sprintf(
                    'Retención ISR incorrecta: se esperaba $%s (%.4f%%) y el XML trae $%s.',
                    number_format($isrEsp, 2),
                    $regla['isr'] * 100,
                    number_format($isrXml, 2)
                );
            } elseif ($aplicaFlete && $regla['isr'] > 0 && $isrXml <= 0) {
                $advertencias[] = 'Flete: se esperaba también ISR retenido ('.($regla['isr'] * 100).'%); el XML no lo trae.';
            }
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
     * @return array{flete: bool, comision: bool, producto: string}
     */
    private function detectarConceptos(SimpleXMLElement $xml): array
    {
        $cfg = config('facturas.conceptos', []);
        $flete = false;
        $comision = false;
        $descripciones = [];

        $nodos = $xml->xpath("//*[local-name()='Concepto']") ?: [];
        foreach ($nodos as $nodo) {
            $attrs = $nodo->attributes();
            $clave = trim((string) ($attrs['ClaveProdServ'] ?? ''));
            $descRaw = trim((string) ($attrs['Descripcion'] ?? ''));
            $desc = mb_strtolower($descRaw, 'UTF-8');
            $descAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $desc) ?: $desc;

            if ($descRaw !== '') {
                $descripciones[] = $descRaw;
            }

            if ($this->conceptoCoincide($clave, $descAscii, $cfg['flete'] ?? [])) {
                $flete = true;
            }
            if ($this->conceptoCoincide($clave, $descAscii, $cfg['comision'] ?? [])) {
                $comision = true;
            }
        }

        $producto = implode(' · ', array_slice(array_unique($descripciones), 0, 5));
        if (mb_strlen($producto) > 255) {
            $producto = mb_substr($producto, 0, 252).'…';
        }

        return ['flete' => $flete, 'comision' => $comision, 'producto' => $producto];
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
            $checklist['producto']['label'] = 'Concepto: '.\Illuminate\Support\Str::limit($datos['producto'], 60);
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

    private function conceptoCoincide(string $clave, string $descAscii, array $cfg): bool
    {
        foreach ($cfg['claves'] ?? [] as $c) {
            if ($clave !== '' && $clave === (string) $c) {
                return true;
            }
        }
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

        // Registrar namespaces comunes
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
     * Si no existe, cae a traslados/retenciones solo bajo Conceptos (nunca ambos:
     * en CFDI 4.0 se repiten y duplican el importe).
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

    private function resultado(bool $aprobado, array $errores, array $advertencias, array $checklist, array $datos): array
    {
        return [
            'aprobado' => $aprobado,
            'errores' => $errores,
            'advertencias' => $advertencias,
            'checklist' => $checklist,
            'datos' => $datos,
        ];
    }
}
