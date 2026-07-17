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
            'subtotal' => 0.0,
            'iva' => 0.0,
            'retencion_iva' => 0.0,
            'retencion_isr' => 0.0,
            'total' => 0.0,
            'fecha' => null,
            'tipo_comprobante' => null,
            'es_fletera' => $esFletera,
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

        // Régimen
        $this->validarRegimen($datos, $errores, $checklist);

        // Fletera (indicador del formulario)
        $checklist['fletera']['ok'] = true;
        $checklist['fletera']['label'] = $esFletera ? 'Marcada como fletera' : 'No es fletera';

        // Retenciones según régimen + fletera
        $this->validarRetenciones($datos, $esFletera, $errores, $advertencias, $checklist);

        $aprobado = empty($errores);

        return $this->resultado($aprobado, $errores, $advertencias, $checklist, $datos);
    }

    private function validarRegimen(array &$datos, array &$errores, array &$checklist): void
    {
        $regimenes = config('facturas.regimenes_aceptados', []);
        $codigo = $datos['regimen_fiscal'];

        if (! $codigo) {
            $errores[] = 'El XML no indica el Régimen Fiscal del emisor.';

            return;
        }

        if (! isset($regimenes[$codigo])) {
            $errores[] = "El régimen fiscal {$codigo} no está en el catálogo SAT reconocido por el portal.";

            return;
        }

        $datos['regimen_nombre'] = $regimenes[$codigo];

        // Coherencia persona moral (12) vs física (13)
        $rfc = $datos['rfc_emisor'] ?? '';
        $len = strlen($rfc);
        $regimenesMoral = ['601', '603', '620', '622', '623', '624'];
        $regimenesFisica = ['605', '606', '608', '611', '612', '614', '615', '616', '621', '625'];

        if ($len === 12 && in_array($codigo, $regimenesFisica, true)) {
            $errores[] = "El régimen {$codigo} ({$regimenes[$codigo]}) es de persona física, pero el RFC emisor es de persona moral.";

            return;
        }
        if ($len === 13 && in_array($codigo, $regimenesMoral, true)) {
            $errores[] = "El régimen {$codigo} ({$regimenes[$codigo]}) es de persona moral, pero el RFC emisor es de persona física.";

            return;
        }

        $checklist['regimen']['ok'] = true;
        $checklist['regimen']['label'] = "Régimen {$codigo} — {$regimenes[$codigo]}";
    }

    private function validarRetenciones(array &$datos, bool $esFletera, array &$errores, array &$advertencias, array &$checklist): void
    {
        $cfg = config('facturas.retenciones', []);
        $tol = (float) config('facturas.tolerancia_monto', 1);

        if ($esFletera) {
            $regla = $cfg['fletera'] ?? ['iva' => 0.04, 'isr' => 0.0125, 'requiere_retencion' => true];
        } else {
            $porRegimen = $cfg['por_regimen'] ?? [];
            $codigo = $datos['regimen_fiscal'] ?? '_default';
            $regla = $porRegimen[$codigo] ?? ($porRegimen['_default'] ?? ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false]);
        }

        $datos['retencion_esperada'] = [
            'iva_tasa' => $regla['iva'],
            'isr_tasa' => $regla['isr'],
            'requiere' => (bool) $regla['requiere_retencion'],
            'origen' => $esFletera ? 'fletera' : 'regimen_'.$datos['regimen_fiscal'],
        ];

        $base = (float) $datos['subtotal'];
        $ivaEsp = round($base * (float) $regla['iva'], 2);
        $isrEsp = round($base * (float) $regla['isr'], 2);
        $ivaXml = round((float) $datos['retencion_iva'], 2);
        $isrXml = round((float) $datos['retencion_isr'], 2);

        $erroresRet = [];

        if ($regla['requiere_retencion']) {
            if ($ivaXml <= 0 && $isrXml <= 0) {
                $erroresRet[] = $esFletera
                    ? 'Al marcar fletera, el XML debe incluir retenciones de IVA (4%) e ISR (1.25%).'
                    : "El régimen {$datos['regimen_fiscal']} requiere retenciones y el XML no las trae.";
            } else {
                if (abs($ivaXml - $ivaEsp) > $tol && $regla['iva'] > 0) {
                    $erroresRet[] = sprintf(
                        'Retención IVA incorrecta: se esperaba $%s (%.4f%%) y el XML trae $%s.',
                        number_format($ivaEsp, 2),
                        $regla['iva'] * 100,
                        number_format($ivaXml, 2)
                    );
                }
                if (abs($isrXml - $isrEsp) > $tol && $regla['isr'] > 0) {
                    $erroresRet[] = sprintf(
                        'Retención ISR incorrecta: se esperaba $%s (%.4f%%) y el XML trae $%s.',
                        number_format($isrEsp, 2),
                        $regla['isr'] * 100,
                        number_format($isrXml, 2)
                    );
                }
            }
        } elseif ($ivaXml > $tol || $isrXml > $tol) {
            $advertencias[] = sprintf(
                'El régimen no exige retenciones, pero el XML trae IVA ret. $%s / ISR ret. $%s. Se registrarán tal cual.',
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
                ? sprintf('Retenciones OK (IVA %.2f%% / ISR %.2f%%)', $pctIva, $pctIsr)
                : 'Sin retención requerida';
        }
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
     * @return array{iva_trasladado: float, retencion_iva: float, retencion_isr: float}
     */
    private function extraerImpuestos(SimpleXMLElement $xml): array
    {
        $ivaTrasladado = 0.0;
        $retIva = 0.0;
        $retIsr = 0.0;

        // Traslados IVA (002)
        $traslados = $xml->xpath("//*[local-name()='Traslado']");
        if ($traslados) {
            foreach ($traslados as $t) {
                $imp = (string) ($t->attributes()['Impuesto'] ?? '');
                $importe = (float) ($t->attributes()['Importe'] ?? 0);
                if ($imp === '002') {
                    $ivaTrasladado += $importe;
                }
            }
        }

        // Retenciones (Impuesto 001 = ISR, 002 = IVA)
        $retenciones = $xml->xpath("//*[local-name()='Retenciones']/*[local-name()='Retencion']");
        if (! $retenciones) {
            $retenciones = $xml->xpath("//*[local-name()='Retencion']");
        }
        if ($retenciones) {
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
