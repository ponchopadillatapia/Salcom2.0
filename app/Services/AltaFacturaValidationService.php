<?php

namespace App\Services;

use App\Models\Factura;
use Carbon\Carbon;
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
     * Las reglas de negocio se ejecutan únicamente contra el objeto parseado del XML.
     * El PDF solo se cruza por full-text search de valores clave.
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
            'periodo' => ['ok' => false, 'label' => 'Periodo (mes en curso)'],
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
            'conceptos_detalle' => [],
            'claves_prod_serv' => [],
            'deteccion_conceptos' => [],
            'es_fletera' => $esFletera,
            'tiene_concepto_flete' => false,
            'tiene_concepto_comision' => false,
            'es_persona_fisica' => null,
            'retencion_esperada' => null,
            'tiene_oc' => $ocContent !== null && $ocContent !== '',
            'pdf_coincidencias' => [],
            'oc_coincidencias' => [],
        ];

        // Paso 1: XML = única fuente de verdad
        $cfdi = $this->extraerObjetoCfdiDesdeXml($xmlContent, $errores);
        if ($cfdi === null) {
            return $this->resultado($errores, $advertencias, $checklist, $datos);
        }

        $checklist['xml']['ok'] = true;
        $this->aplicarObjetoCfdiADatos($cfdi, $datos);
        $this->validarPeriodoMes($datos, $errores, $checklist);

        // UUID único en sistema
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

        // Totales internos del CFDI (solo XML)
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

        $conceptos = $this->clasificarConceptosDesdeObjeto($cfdi, $errores, $advertencias);
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
        $this->validarDatosPagoCfdi($datos, $errores, $checklist);

        // Fletera: ClaveProdServ SAT manda (flete) — solo XML
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
            $checklist['fletera']['ok'] = true;
            $checklist['fletera']['label'] = 'Flete detectado por '.$via;
        } else {
            $checklist['fletera']['ok'] = true;
            $checklist['fletera']['label'] = $esFletera ? 'Marcada como fletera' : 'No es fletera';
        }

        $this->validarRetenciones($datos, $esFletera, $errores, $advertencias, $checklist);

        // Paso 2–3: Cruce Factura PDF ↔ XML por full-text search
        if ($pdfContent !== null && $pdfContent !== '') {
            $this->cruzarPdfConXml($pdfContent, $datos, $errores, $advertencias, $checklist);
        } else {
            $errores[] = 'Falta el PDF de la factura para validar contra el XML.';
            $checklist['pdf_xml']['ok'] = false;
        }

        // Cruce OC ↔ XML (opcional; también por full-text)
        if ($datos['tiene_oc']) {
            $this->cruzarOcConXml($ocContent, $datos, $errores, $advertencias, $checklist);
        } else {
            $checklist['oc_xml']['ok'] = true;
            $checklist['oc_xml']['label'] = 'OC no adjunta (opcional)';
        }

        return $this->resultado($errores, $advertencias, $checklist, $datos);
    }

    /**
     * Paso 1: parsea el XML CFDI y devuelve el objeto estandarizado.
     * Todas las reglas de negocio deben ejecutarse contra este objeto.
     *
     * @param  string[]  $errores
     * @return array{
     *   uuid: ?string,
     *   rfc_emisor: ?string,
     *   nombre_emisor: ?string,
     *   rfc_receptor: ?string,
     *   regimen_fiscal: ?string,
     *   subtotal: float,
     *   total: float,
     *   iva: float,
     *   retencion_iva: float,
     *   retencion_isr: float,
     *   folio: string,
     *   serie: string,
     *   fecha: string,
     *   tipo_comprobante: string,
     *   moneda: string,
     *   forma_pago: ?string,
     *   metodo_pago: ?string,
     *   uso_cfdi: ?string,
     *   conceptos: list<array{
     *     clave_prod_serv: string,
     *     descripcion: string,
     *     cantidad: float,
     *     valor_unitario: float,
     *     importe: float,
     *     impuestos: array{
     *       traslados: list<array{base: float, impuesto: string, tipo_factor: string, tasa_o_cuota: ?float, importe: float}>,
     *       retenciones: list<array{base: float, impuesto: string, tipo_factor: string, tasa_o_cuota: ?float, importe: float}>
     *     }
     *   }>
     * }|null
     */
    public function extraerObjetoCfdiDesdeXml(string $xmlContent, array &$errores = []): ?array
    {
        $xml = $this->parseXml($xmlContent);
        if ($xml === null) {
            $errores[] = 'El archivo XML no es un CFDI válido o está corrupto.';

            return null;
        }

        $attrs = $xml->attributes();
        $objeto = [
            'uuid' => $this->extraerUuid($xml),
            'rfc_emisor' => null,
            'nombre_emisor' => null,
            'rfc_receptor' => null,
            'regimen_fiscal' => null,
            'subtotal' => (float) ($attrs['SubTotal'] ?? 0),
            'total' => (float) ($attrs['Total'] ?? 0),
            'iva' => 0.0,
            'retencion_iva' => 0.0,
            'retencion_isr' => 0.0,
            'folio' => (string) ($attrs['Folio'] ?? ''),
            'serie' => (string) ($attrs['Serie'] ?? ''),
            'fecha' => (string) ($attrs['Fecha'] ?? ''),
            'tipo_comprobante' => (string) ($attrs['TipoDeComprobante'] ?? ''),
            'moneda' => strtoupper((string) ($attrs['Moneda'] ?? 'MXN')),
            'forma_pago' => $this->normalizarCodigoPago((string) ($attrs['FormaPago'] ?? ''), 2),
            'metodo_pago' => strtoupper(trim((string) ($attrs['MetodoPago'] ?? ''))) ?: null,
            'uso_cfdi' => null,
            'conceptos' => [],
        ];

        if ($objeto['tipo_comprobante'] !== '' && strtoupper($objeto['tipo_comprobante']) !== 'I') {
            $errores[] = 'Solo se aceptan CFDI de tipo Ingreso (I). Tipo recibido: '.$objeto['tipo_comprobante'];
        }

        $emisor = $this->findChild($xml, 'Emisor');
        if ($emisor) {
            $eAttrs = $emisor->attributes();
            $objeto['rfc_emisor'] = strtoupper(trim((string) ($eAttrs['Rfc'] ?? '')));
            $objeto['nombre_emisor'] = (string) ($eAttrs['Nombre'] ?? '');
            $objeto['regimen_fiscal'] = str_pad(trim((string) ($eAttrs['RegimenFiscal'] ?? '')), 3, '0', STR_PAD_LEFT);
            if ($objeto['regimen_fiscal'] === '000') {
                $objeto['regimen_fiscal'] = null;
            }
        } else {
            $errores[] = 'No se encontró el nodo Emisor en el XML.';
        }

        $receptor = $this->findChild($xml, 'Receptor');
        if ($receptor) {
            $rAttrs = $receptor->attributes();
            $objeto['rfc_receptor'] = strtoupper(trim((string) ($rAttrs['Rfc'] ?? '')));
            $objeto['uso_cfdi'] = strtoupper(trim((string) ($rAttrs['UsoCFDI'] ?? ''))) ?: null;
        } else {
            $errores[] = 'No se encontró el nodo Receptor en el XML.';
        }

        $impuestos = $this->extraerImpuestos($xml);
        $objeto['iva'] = $impuestos['iva_trasladado'];
        $objeto['retencion_iva'] = $impuestos['retencion_iva'];
        $objeto['retencion_isr'] = $impuestos['retencion_isr'];

        $objeto['conceptos'] = $this->extraerConceptosConImpuestos($xml);

        return $objeto;
    }

    /**
     * @param  array<string, mixed>  $cfdi
     * @param  array<string, mixed>  $datos
     */
    private function aplicarObjetoCfdiADatos(array $cfdi, array &$datos): void
    {
        $datos['uuid'] = $cfdi['uuid'];
        $datos['folio'] = $cfdi['folio'];
        $datos['serie'] = $cfdi['serie'];
        $datos['rfc_emisor'] = $cfdi['rfc_emisor'];
        $datos['nombre_emisor'] = $cfdi['nombre_emisor'];
        $datos['rfc_receptor'] = $cfdi['rfc_receptor'];
        $datos['regimen_fiscal'] = $cfdi['regimen_fiscal'];
        $datos['forma_pago'] = $cfdi['forma_pago'];
        $datos['metodo_pago'] = $cfdi['metodo_pago'];
        $datos['uso_cfdi'] = $cfdi['uso_cfdi'];
        $datos['moneda'] = $cfdi['moneda'];
        $datos['subtotal'] = $cfdi['subtotal'];
        $datos['total'] = $cfdi['total'];
        $datos['iva'] = $cfdi['iva'];
        $datos['retencion_iva'] = $cfdi['retencion_iva'];
        $datos['retencion_isr'] = $cfdi['retencion_isr'];
        $datos['fecha'] = $cfdi['fecha'];
        $datos['tipo_comprobante'] = $cfdi['tipo_comprobante'];
        $datos['conceptos_detalle'] = $cfdi['conceptos'];
    }

    /**
     * @return list<array{
     *   clave_prod_serv: string,
     *   descripcion: string,
     *   cantidad: float,
     *   valor_unitario: float,
     *   importe: float,
     *   impuestos: array{
     *     traslados: list<array{base: float, impuesto: string, tipo_factor: string, tasa_o_cuota: ?float, importe: float}>,
     *     retenciones: list<array{base: float, impuesto: string, tipo_factor: string, tasa_o_cuota: ?float, importe: float}>
     *   }
     * }>
     */
    private function extraerConceptosConImpuestos(SimpleXMLElement $xml): array
    {
        $conceptos = [];
        $nodos = $xml->xpath("//*[local-name()='Concepto']") ?: [];

        foreach ($nodos as $nodo) {
            $attrs = $nodo->attributes();
            $traslados = [];
            $retenciones = [];

            foreach ($nodo->xpath(".//*[local-name()='Traslado']") ?: [] as $t) {
                $tAttrs = $t->attributes();
                $traslados[] = [
                    'base' => (float) ($tAttrs['Base'] ?? 0),
                    'impuesto' => (string) ($tAttrs['Impuesto'] ?? ''),
                    'tipo_factor' => (string) ($tAttrs['TipoFactor'] ?? ''),
                    'tasa_o_cuota' => isset($tAttrs['TasaOCuota']) ? (float) $tAttrs['TasaOCuota'] : null,
                    'importe' => (float) ($tAttrs['Importe'] ?? 0),
                ];
            }

            foreach ($nodo->xpath(".//*[local-name()='Retencion']") ?: [] as $r) {
                $rAttrs = $r->attributes();
                $retenciones[] = [
                    'base' => (float) ($rAttrs['Base'] ?? 0),
                    'impuesto' => (string) ($rAttrs['Impuesto'] ?? ''),
                    'tipo_factor' => (string) ($rAttrs['TipoFactor'] ?? ''),
                    'tasa_o_cuota' => isset($rAttrs['TasaOCuota']) ? (float) $rAttrs['TasaOCuota'] : null,
                    'importe' => (float) ($rAttrs['Importe'] ?? 0),
                ];
            }

            $conceptos[] = [
                'clave_prod_serv' => trim((string) ($attrs['ClaveProdServ'] ?? '')),
                'descripcion' => (string) ($attrs['Descripcion'] ?? ''),
                'cantidad' => (float) ($attrs['Cantidad'] ?? 0),
                'valor_unitario' => (float) ($attrs['ValorUnitario'] ?? 0),
                'importe' => (float) ($attrs['Importe'] ?? 0),
                'impuestos' => [
                    'traslados' => $traslados,
                    'retenciones' => $retenciones,
                ],
            ];
        }

        return $conceptos;
    }

    /**
     * Solo se aceptan facturas del mes en curso. Si la Fecha del CFDI es de
     * otro mes (periodo ya cerrado o futuro), no se puede validar ni subir.
     *
     * @param  array<string, mixed>  $datos
     * @param  string[]  $errores
     * @param  array<string, array{ok: bool, label: string}>  $checklist
     */
    private function validarPeriodoMes(array $datos, array &$errores, array &$checklist): void
    {
        if (! config('facturas.solo_mes_actual', true)) {
            $checklist['periodo'] = [
                'ok' => true,
                'label' => 'Periodo no restringido',
            ];

            return;
        }

        $fechaRaw = trim((string) ($datos['fecha'] ?? ''));
        if ($fechaRaw === '') {
            $errores[] = 'El CFDI no tiene fecha de emisión. No se puede verificar el periodo.';
            $checklist['periodo'] = [
                'ok' => false,
                'label' => 'Sin fecha de emisión',
            ];

            return;
        }

        try {
            $fecha = Carbon::parse(substr($fechaRaw, 0, 19));
        } catch (\Throwable) {
            $errores[] = "La fecha de emisión del CFDI («{$fechaRaw}») no es válida.";
            $checklist['periodo'] = [
                'ok' => false,
                'label' => 'Fecha inválida',
            ];

            return;
        }

        $ahora = now();
        $mesActualLabel = $ahora->locale('es')->translatedFormat('F Y');

        if ((int) $fecha->year !== (int) $ahora->year || (int) $fecha->month !== (int) $ahora->month) {
            $mesFacturaLabel = $fecha->locale('es')->translatedFormat('F Y');
            if ($fecha->lt($ahora->copy()->startOfMonth())) {
                $errores[] = "La factura es de {$mesFacturaLabel} y ese periodo ya cerró. Solo se aceptan facturas del mes en curso ({$mesActualLabel}).";
            } else {
                $errores[] = "La factura es de {$mesFacturaLabel}. Solo se aceptan facturas del mes en curso ({$mesActualLabel}).";
            }
            $checklist['periodo'] = [
                'ok' => false,
                'label' => 'Fuera del mes en curso',
            ];

            return;
        }

        $checklist['periodo'] = [
            'ok' => true,
            'label' => 'Mes en curso: '.$mesActualLabel,
        ];
    }

    /**
     * Paso 3: verifica que el texto crudo del PDF contenga UUID, RFCs y Total del XML.
     */
    private function cruzarPdfConXml(
        string $pdfContent,
        array &$datos,
        array &$errores,
        array &$advertencias,
        array &$checklist,
    ): void {
        $extraido = $this->extractor->extraerDesdeContenido($pdfContent);
        $texto = $extraido['texto'];

        if ($extraido['escaneado'] && mb_strlen(trim($texto)) < 40) {
            $errores[] = 'No se pudo leer el PDF de la factura (posible escaneo). No se pudo verificar coincidencia con el XML.';
            $checklist['pdf_xml']['ok'] = false;
            $checklist['pdf_xml']['label'] = 'PDF ilegible';
            $datos['pdf_coincidencias'] = [
                'uuid' => false,
                'rfc_emisor' => false,
                'rfc_receptor' => false,
                'total' => false,
            ];

            return;
        }

        $coincidencias = $this->buscarValoresClaveEnTexto($texto, $datos);
        $datos['pdf_coincidencias'] = $coincidencias;

        $faltantes = [];
        if (! $coincidencias['uuid']) {
            $faltantes[] = 'UUID / folio fiscal';
        }
        if (! $coincidencias['rfc_emisor']) {
            $faltantes[] = 'RFC emisor ('.$datos['rfc_emisor'].')';
        }
        if (! $coincidencias['rfc_receptor']) {
            $faltantes[] = 'RFC receptor ('.$datos['rfc_receptor'].')';
        }
        if (! $coincidencias['total']) {
            $faltantes[] = 'Total ('.$this->formatoMontoPlain((float) $datos['total']).')';
        }

        if ($faltantes !== []) {
            foreach ($faltantes as $campo) {
                $errores[] = 'Factura PDF ↔ XML: no se encontró «'.$campo.'» en el texto del PDF.';
            }
            $checklist['pdf_xml']['ok'] = false;
            $checklist['pdf_xml']['label'] = 'PDF no coincide con XML';
        } else {
            $checklist['pdf_xml']['ok'] = true;
            $checklist['pdf_xml']['label'] = 'Factura PDF ↔ XML coinciden (UUID, RFCs, Total)';
            if ($extraido['escaneado']) {
                $advertencias[] = 'El PDF tiene poco texto extraíble; la coincidencia se validó con el texto disponible.';
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
        $texto = $extraido['texto'];

        if ($extraido['escaneado'] && mb_strlen(trim($texto)) < 40) {
            $errores[] = 'No se pudo leer la Orden de Compra (posible escaneo). No se pudo verificar contra el XML.';
            $checklist['oc_xml']['ok'] = false;
            $checklist['oc_xml']['label'] = 'OC ilegible';
            $datos['oc_coincidencias'] = ['rfc_emisor' => false, 'total' => false];

            return;
        }

        $rfcOk = $this->textoContieneAlfanumerico(
            $this->normalizarAlfanumerico($texto),
            (string) ($datos['rfc_emisor'] ?? '')
        );
        $totalOk = $this->textoContieneMonto($texto, (float) ($datos['total'] ?? 0));
        $datos['oc_coincidencias'] = [
            'rfc_emisor' => $rfcOk,
            'total' => $totalOk,
        ];

        $mismatches = [];
        if ($datos['rfc_emisor'] && ! $rfcOk) {
            $mismatches[] = 'RFC del proveedor ('.$datos['rfc_emisor'].') no aparece en la OC.';
        }
        if ((float) $datos['total'] > 0 && ! $totalOk) {
            $mismatches[] = 'Total ('.$this->formatoMontoPlain((float) $datos['total']).') no aparece en la OC.';
        }

        if ($mismatches) {
            foreach ($mismatches as $m) {
                $errores[] = 'OC ↔ XML: '.$m;
            }
            $checklist['oc_xml']['ok'] = false;
            $checklist['oc_xml']['label'] = 'OC no coincide con XML';
        } else {
            $checklist['oc_xml']['ok'] = true;
            $checklist['oc_xml']['label'] = 'OC ↔ XML coinciden';
            if (! $rfcOk && ! $totalOk) {
                $advertencias[] = 'La OC se adjuntó, pero no se detectaron RFC ni total claros; revisa manualmente.';
            }
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{uuid: bool, rfc_emisor: bool, rfc_receptor: bool, total: bool}
     */
    private function buscarValoresClaveEnTexto(string $texto, array $datos): array
    {
        // Una sola limpieza alfanumérica del PDF para UUID y RFCs
        // (elimina guiones unicode, zero-width spaces, saltos, etc.).
        $textoAlfanumerico = $this->normalizarAlfanumerico($texto);

        return [
            'uuid' => $this->textoContieneAlfanumerico($textoAlfanumerico, (string) ($datos['uuid'] ?? '')),
            'rfc_emisor' => $this->textoContieneAlfanumerico($textoAlfanumerico, (string) ($datos['rfc_emisor'] ?? '')),
            'rfc_receptor' => $this->textoContieneAlfanumerico($textoAlfanumerico, (string) ($datos['rfc_receptor'] ?? '')),
            'total' => $this->textoContieneMonto($texto, (float) ($datos['total'] ?? 0)),
        ];
    }

    /**
     * Busca un valor (UUID/RFC) dentro de texto ya normalizado a [a-z0-9].
     */
    private function textoContieneAlfanumerico(string $textoAlfanumerico, string $valor): bool
    {
        $needle = $this->normalizarAlfanumerico($valor);
        if ($needle === '') {
            return false;
        }

        return str_contains($textoAlfanumerico, $needle);
    }

    /**
     * Limpieza alfanumérica estricta: solo letras y dígitos ASCII, en minúsculas.
     * '44BF7C76-A54F…' → '44bf7c76a54f…'
     */
    private function normalizarAlfanumerico(string $valor): string
    {
        $valor = preg_replace('/[^a-zA-Z0-9]+/', '', $valor) ?? '';

        return strtolower($valor);
    }

    private function textoContieneMonto(string $textoMinusculas, float $monto): bool
    {
        if ($monto <= 0) {
            return false;
        }

        foreach ($this->variantesMonto($monto) as $variante) {
            if (str_contains($textoMinusculas, $variante)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Variantes tipográficas del total: 5833.69 y 5,833.69
     *
     * @return list<string>
     */
    private function variantesMonto(float $monto): array
    {
        $plain = $this->formatoMontoPlain($monto);
        $conComas = number_format($monto, 2, '.', ',');

        return array_values(array_unique([$plain, $conComas]));
    }

    private function formatoMontoPlain(float $monto): string
    {
        return number_format($monto, 2, '.', '');
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
            $regimenFlete = (string) ($datos['regimen_fiscal'] ?? '');
            $porRegimenFlete = $cfg['flete_por_regimen'] ?? [];
            $regla = $porRegimenFlete[$regimenFlete]
                ?? ($cfg['flete'] ?? ['iva' => 0.04, 'isr' => 0.0, 'requiere_retencion' => true]);
            $origen = $regimenFlete === '626' ? 'flete_resico' : 'flete';
        } elseif ($aplicaComision) {
            $regla = $cfg['comision_fisica'] ?? ['iva' => 0.106667, 'isr' => 0.10, 'requiere_retencion' => true];
            $origen = 'comision_fisica';
        } elseif (! empty($datos['tiene_concepto_comision']) && $datos['es_persona_fisica'] === false) {
            $regla = ['iva' => 0.0, 'isr' => 0.0, 'requiere_retencion' => false];
            $origen = 'comision_moral_sin_retencion';
            $advertencias[] = 'Concepto de comisión en persona moral: según Contabilidad no aplica retención de IVA por comisión.';
        } elseif (($datos['regimen_fiscal'] ?? '') === '626') {
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

        // Base fiscal: en flete el 4% (e ISR si aplica) va solo sobre conceptos
        // que traen <Retencion Impuesto="002">, no sobre el SubTotal global.
        $baseGlobal = (float) $datos['subtotal'];
        $baseIva = $baseGlobal;
        $baseIsr = $baseGlobal;
        $baseFleteRetencion = 0.0;

        if ($aplicaFlete) {
            $baseFleteRetencion = $this->baseRetencionIvaDesdeConceptos($datos['conceptos_detalle'] ?? []);
            // Aunque sea 0 (sin nodos Retencion 002 en conceptos), no se usa el SubTotal global.
            $baseIva = $baseFleteRetencion;
            $baseIsr = $baseFleteRetencion;
        }

        $datos['retencion_esperada'] = [
            'iva_tasa' => $regla['iva'],
            'isr_tasa' => $regla['isr'],
            'requiere' => (bool) $regla['requiere_retencion'],
            'origen' => $origen,
            'base_iva' => $baseIva,
            'base_isr' => $baseIsr,
            'base_flete_retencion' => $baseFleteRetencion,
        ];

        $ivaEsp = round($baseIva * (float) $regla['iva'], 2);
        $isrEsp = round($baseIsr * (float) $regla['isr'], 2);
        $ivaXml = round((float) $datos['retencion_iva'], 2);
        $isrXml = round((float) $datos['retencion_isr'], 2);

        $erroresRet = [];

        if ($regla['requiere_retencion']) {
            if ($aplicaFlete && $ivaXml <= 0) {
                $erroresRet[] = 'Concepto flete / fletera: el XML debe incluir retención de IVA del 4% sobre la base de los conceptos con retención.';
            } elseif ($origen === 'flete_resico' && $isrXml <= 0) {
                $erroresRet[] = 'Flete RESICO (626): el XML debe incluir retención de ISR del 1.25% sobre la base de los conceptos con retención.';
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
                        'Retención IVA incorrecta: se esperaba $%s (%.4f%% sobre base $%s) y el XML trae $%s.',
                        number_format($ivaEsp, 2),
                        $regla['iva'] * 100,
                        number_format($baseIva, 2),
                        number_format($ivaXml, 2)
                    );
                }
            }

            if ($regla['isr'] > 0) {
                if ($isrXml <= 0 && in_array($origen, ['resico_fisica', 'flete_resico'], true)) {
                    // ya cubierto arriba
                } elseif ($isrXml <= 0) {
                    $erroresRet[] = sprintf(
                        'Retención ISR faltante: se esperaba $%s (%.4f%%) y el XML no la trae.',
                        number_format($isrEsp, 2),
                        $regla['isr'] * 100
                    );
                } elseif (abs($isrXml - $isrEsp) > $tol) {
                    $erroresRet[] = sprintf(
                        'Retención ISR incorrecta: se esperaba $%s (%.4f%% sobre base $%s) y el XML trae $%s.',
                        number_format($isrEsp, 2),
                        $regla['isr'] * 100,
                        number_format($baseIsr, 2),
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
     * Suma la base gravable solo de conceptos que incluyen
     * <cfdi:Retencion Impuesto="002"> (retención de IVA, p. ej. flete 4%).
     * Prefiere el atributo Base de la retención; si falta, usa Importe del concepto.
     *
     * @param  list<array<string, mixed>>  $conceptosDetalle
     */
    private function baseRetencionIvaDesdeConceptos(array $conceptosDetalle): float
    {
        $suma = 0.0;

        foreach ($conceptosDetalle as $concepto) {
            foreach ($concepto['impuestos']['retenciones'] ?? [] as $retencion) {
                if ((string) ($retencion['impuesto'] ?? '') !== '002') {
                    continue;
                }

                $base = (float) ($retencion['base'] ?? 0);
                if ($base <= 0) {
                    $base = (float) ($concepto['importe'] ?? 0);
                }
                $suma += $base;
                break;
            }
        }

        return round($suma, 2);
    }

    /**
     * Clasifica flete/comisión a partir del objeto CFDI (solo XML).
     *
     * @param  array<string, mixed>  $cfdi
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
    private function clasificarConceptosDesdeObjeto(array $cfdi, array &$errores, array &$advertencias): array
    {
        $cfg = config('facturas.conceptos', []);
        $flete = false;
        $comision = false;
        $descripciones = [];
        $claves = [];
        $clavesOk = true;
        $deteccion = ['flete' => null, 'comision' => null];

        $conceptos = $cfdi['conceptos'] ?? [];
        if ($conceptos === []) {
            $errores[] = 'El XML no trae nodos Concepto con ClaveProdServ.';

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

        foreach ($conceptos as $concepto) {
            $clave = trim((string) ($concepto['clave_prod_serv'] ?? ''));
            $desc = (string) ($concepto['descripcion'] ?? '');
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
