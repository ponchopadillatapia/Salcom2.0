<?php

namespace App\Services;

use App\Models\FacturaAgenteAduanal;
use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;

/**
 * Lee la factura que manda el agente aduanal.
 * Toma los importes que vienen escritos en el PDF o en el CFDI.
 * No calcula impuestos ni decide quién paga.
 */
class FacturaAgentePdfExtractor
{
    public function __construct(
        private ?AltaFacturaValidationService $cfdi = null,
        private ?Parser $parser = null,
    ) {
        $this->cfdi ??= new AltaFacturaValidationService;
        $this->parser ??= new Parser;
    }

    /**
     * @return array<string, mixed>
     */
    public function leerArchivo(UploadedFile $archivo): array
    {
        $ruta = $archivo->getRealPath();
        if (! is_string($ruta) || ! is_readable($ruta)) {
            return $this->vacio(['No se pudo leer el archivo.']);
        }

        $raw = (string) file_get_contents($ruta);
        $texto = '';
        try {
            $texto = $this->parser->parseFile($ruta)->getText();
        } catch (\Throwable) {
            $texto = '';
        }

        return $this->interpretar($this->extraerXml($raw."\n".$texto), $texto);
    }

    /**
     * @return array<string, mixed>
     */
    public function interpretar(?string $xml, string $texto): array
    {
        $datos = $this->vacio();
        $avisos = [];
        $texto = $this->normalizarTexto($texto);

        if ($xml === null && $this->esOtroDocumento($texto, $avisos)) {
            return $this->vacio($avisos);
        }

        if ($xml) {
            $errores = [];
            $cfdi = $this->cfdi->extraerObjetoCfdiDesdeXml($xml, $errores);
            if (is_array($cfdi) && strtoupper((string) ($cfdi['tipo_comprobante'] ?? '')) === 'P') {
                $avisos[] = 'Este PDF es un complemento de pago, no la factura del agente.';

                return $this->vacio($avisos);
            }
            if (is_array($cfdi)) {
                $this->aplicarCfdi($datos, $cfdi);
                $avisos[] = 'Los importes se tomaron del CFDI que viene en el PDF. No se calcularon.';
            }
        }

        $this->aplicarTexto($datos, trim($texto."\n".(string) $xml));

        if ($datos['folio'] === null && $datos['importe'] === null) {
            $avisos[] = 'No se pudieron leer los datos del PDF. Captúralos a mano; el archivo sí se guarda.';
            $datos['leido'] = false;
        } else {
            $datos['leido'] = true;
            if ($datos['rfc_emisor']) {
                $quien = trim(($datos['nombre_emisor'] ? $datos['nombre_emisor'].' ' : '').'('.$datos['rfc_emisor'].')');
                $avisos[] = 'El emisor del PDF es '.$quien.'.';
            }
        }

        $datos['avisos'] = $avisos;

        return $datos;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<string, mixed>  $cfdi
     */
    private function aplicarCfdi(array &$datos, array $cfdi): void
    {
        $datos['serie'] = $this->serie($cfdi['serie'] ?? null);
        $datos['folio'] = $this->folio($cfdi['folio'] ?? null);
        $datos['fecha'] = $this->fecha((string) ($cfdi['fecha'] ?? ''));
        $datos['moneda'] = $this->moneda((string) ($cfdi['moneda'] ?? ''));
        $datos['importe'] = $this->dinero($cfdi['total'] ?? null);
        $datos['honorarios'] = $this->honorariosDeConceptos($cfdi['conceptos'] ?? []);
        $datos['impuestos'] = $this->dinero($cfdi['iva'] ?? null);
        $datos['rfc_emisor'] = $this->rfc($cfdi['rfc_emisor'] ?? null);
        $datos['nombre_emisor'] = $this->texto($cfdi['nombre_emisor'] ?? null, 180);
        $datos['uuid'] = $this->texto($cfdi['uuid'] ?? null, 36);
        $datos['leido'] = $datos['folio'] !== null || $datos['importe'] !== null;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function aplicarTexto(array &$datos, string $texto): void
    {
        if ($datos['tipo_operacion'] === null) {
            $datos['tipo_operacion'] = $this->tipoOperacion($texto);
        }
        if ($datos['estado_cancelacion'] === null && preg_match('/\bcancelad[oa]\b/iu', $texto)) {
            $datos['estado_cancelacion'] = 'cancelada';
        }

        if ($datos['leido'] && $datos['folio'] !== null && $datos['importe'] !== null) {
            return;
        }

        if ($datos['serie'] === null || $datos['folio'] === null) {
            if (preg_match('/serie\s+y\s+folio\s*[:\-]?\s*([A-Z0-9]{1,25})\s*[-–\/]\s*([A-Z0-9][A-Z0-9\-]{0,39})/iu', $texto, $m)) {
                $datos['serie'] ??= $this->serie($m[1]);
                $datos['folio'] ??= $this->folio($m[2]);
            }
        }
        if ($datos['serie'] === null && preg_match('/(?<![a-z])serie(?!\s+del\s+certificado)\s*[:\-]\s*([A-Z0-9]{1,25})/iu', $texto, $m)) {
            $datos['serie'] = $this->serie($m[1]);
        }
        if ($datos['folio'] === null && preg_match('/folio(?!\s+fiscal)\s*[:\-]\s*([A-Z0-9][A-Z0-9\-]{0,39})/iu', $texto, $m)) {
            $datos['folio'] = $this->folio($m[1]);
        }
        if ($datos['folio'] === null && preg_match('/(\d{6,12})\s+factura\b/iu', $texto, $m)) {
            $datos['folio'] = $this->folio($m[1]);
        }
        if ($datos['fecha'] === null) {
            $datos['fecha'] = $this->fechaEnTexto($texto);
        }
        if ($datos['moneda'] === null && preg_match('/moneda\s*[:\-]?\s*(MXN|USD)/iu', $texto, $m)) {
            $datos['moneda'] = strtoupper($m[1]);
        }
        if ($datos['moneda'] === null) {
            $datos['moneda'] = $this->monedaEnTexto($texto);
        }
        if ($datos['honorarios'] === null) {
            $datos['honorarios'] = $this->montoCerca($texto, 'honorarios');
        }
        if ($datos['honorarios'] === null) {
            $datos['honorarios'] = $this->montoDespues($texto, 'subtotal');
        }
        if ($datos['impuestos'] === null) {
            $datos['impuestos'] = $this->montoAntes($texto, 'iva');
        }
        if ($datos['impuestos'] === null) {
            $datos['impuestos'] = $this->montoDespues($texto, 'impuestos?\s+trasladados|iva(?:\s+16\s*%)?');
        }
        if ($datos['importe'] === null) {
            $datos['importe'] = $this->montoDespues($texto, '(?<!sub)(?<!sub )total');
        }
        if ($datos['rfc_emisor'] === null) {
            $datos['rfc_emisor'] = $this->rfcEmisorEnTexto($texto);
        }
        if ($datos['nombre_emisor'] === null) {
            $datos['nombre_emisor'] = $this->nombreEmisorEnTexto($texto);
        }
        if ($datos['uuid'] === null) {
            $datos['uuid'] = $this->uuidEnTexto($texto);
        }
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = str_replace("\r", "\n", $texto);
        $texto = preg_replace('/i\s*\.\s*v\s*\.\s*a\s*\.?/iu', 'IVA', $texto) ?? $texto;
        $texto = preg_replace('/sub\s+total/iu', 'subtotal', $texto) ?? $texto;

        return $texto;
    }

    /**
     * @param  list<string>  $avisos
     */
    private function esOtroDocumento(string $texto, array &$avisos): bool
    {
        $plano = mb_strtolower($texto, 'UTF-8');
        if (str_contains($plano, 'importe total a pagar por esta operacion') && ! str_contains($plano, 'representacion impresa') && ! str_contains($plano, 'representación impresa')) {
            $avisos[] = 'Este PDF es la relación de gastos, no la factura del agente. Sube el PDF de la factura.';

            return true;
        }
        if (str_contains($plano, 'expense bill')) {
            $avisos[] = 'Este PDF es la factura del corresponsal extranjero, no la factura de servicios aduanales.';

            return true;
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $conceptos
     */
    private function honorariosDeConceptos(array $conceptos): ?string
    {
        $suma = 0.0;
        $hay = false;
        foreach ($conceptos as $concepto) {
            $descripcion = mb_strtolower((string) ($concepto['descripcion'] ?? ''), 'UTF-8');
            if (! str_contains($descripcion, 'honorario')) {
                continue;
            }
            $suma += (float) ($concepto['importe'] ?? 0);
            $hay = true;
        }

        return $hay ? $this->dinero($suma) : null;
    }

    private function fechaEnTexto(string $texto): ?string
    {
        if (preg_match('/fecha\s*[:\-]?\s*(\d{4}-\d{2}-\d{2})/iu', $texto, $m)) {
            return $m[1];
        }
        if (preg_match('/fecha\s*[:\-]?\s*(\d{2})\/(\d{2})\/(\d{4})/iu', $texto, $m)) {
            return $m[3].'-'.$m[2].'-'.$m[1];
        }
        if (preg_match('/(\d{1,2})[-\/](ene|feb|mar|abr|may|jun|jul|ago|sep|set|oct|nov|dic|jan|apr|aug|dec)[a-z]*[-\/](\d{4})/iu', $texto, $m)) {
            $mes = $this->mes($m[2]);
            $dia = (int) $m[1];
            $anio = (int) $m[3];
            if ($mes !== null && checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }
        if (preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/', $texto, $m)) {
            return $m[1];
        }

        return null;
    }

    private function mes(string $nombre): ?int
    {
        $clave = strtolower(substr($nombre, 0, 3));

        return [
            'ene' => 1, 'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'abr' => 4, 'apr' => 4,
            'may' => 5,
            'jun' => 6,
            'jul' => 7,
            'ago' => 8, 'aug' => 8,
            'sep' => 9, 'set' => 9,
            'oct' => 10,
            'nov' => 11,
            'dic' => 12, 'dec' => 12,
        ][$clave] ?? null;
    }

    private function monedaEnTexto(string $texto): ?string
    {
        $usd = preg_match('/\b(USD|DOLARES|DÓLARES|DOLLARS)\b/iu', $texto) === 1;
        $mxn = preg_match('/\bMXN\b|\bPESOS\b|M\.N\./iu', $texto) === 1;
        if ($mxn && ! $usd) {
            return 'MXN';
        }
        if ($usd && ! $mxn) {
            return 'USD';
        }

        return null;
    }

    private function uuidEnTexto(string $texto): ?string
    {
        if (! preg_match_all('/\b[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\b/', mb_strtoupper($texto, 'UTF-8'), $coincidencias)) {
            return null;
        }
        $unicos = array_values(array_unique($coincidencias[0]));

        return count($unicos) === 1 ? $unicos[0] : null;
    }

    private function serie(mixed $valor): ?string
    {
        $serie = $this->texto($valor, 25);
        if ($serie === null || in_array(strtoupper($serie), ['N/A', 'NA', 'S/N', 'SN'], true)) {
            return null;
        }

        return $serie;
    }

    private function extraerXml(string $raw): ?string
    {
        if (preg_match('/<([A-Za-z0-9]+):Comprobante\b.*?<\/\1:Comprobante>/s', $raw, $m)) {
            return $m[0];
        }

        return null;
    }

    private function tipoOperacion(string $texto): ?string
    {
        $plano = mb_strtolower($texto, 'UTF-8');
        $importacion = str_contains($plano, 'importaci');
        $exportacion = str_contains($plano, 'exportaci');
        if ($importacion && ! $exportacion) {
            return 'importacion';
        }
        if ($exportacion && ! $importacion) {
            return 'exportacion';
        }

        return null;
    }

    private function montoDespues(string $texto, string $etiqueta): ?string
    {
        $patron = '/(?:'.$etiqueta.')\s*[:\$]?\s*\$?\s*([0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2}|[0-9]+\.[0-9]{2})/iu';
        if (! preg_match($patron, $texto, $m) || ! isset($m[1])) {
            return null;
        }

        return $this->dinero(str_replace(',', '', $m[1]));
    }

    private function montoCerca(string $texto, string $etiqueta): ?string
    {
        $patron = '/(?:'.$etiqueta.').{0,90}?([0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2}|[0-9]+\.[0-9]{2})/ius';
        if (! preg_match($patron, $texto, $m) || ! isset($m[1])) {
            return null;
        }

        return $this->dinero(str_replace(',', '', $m[1]));
    }

    private function montoAntes(string $texto, string $etiqueta): ?string
    {
        $patron = '/([0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2}|[0-9]+\.[0-9]{2})[ \t]*(?:'.$etiqueta.')\b/iu';
        if (! preg_match($patron, $texto, $m) || ! isset($m[1])) {
            return null;
        }

        return $this->dinero(str_replace(',', '', $m[1]));
    }

    private function rfcEmisorEnTexto(string $texto): ?string
    {
        $texto = preg_replace('/\b([A-ZÑ&]{3,4})-(\d{6})-([A-Z0-9]{3})\b/u', '$1$2$3', mb_strtoupper($texto, 'UTF-8')) ?? $texto;
        if (! preg_match_all('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/u', $texto, $coincidencias)) {
            return null;
        }

        $propios = array_map('strtoupper', array_keys((array) config('facturas.receptores', [])));
        $propios[] = strtoupper((string) config('facturas.rfc_receptor'));
        $candidatos = [];
        foreach ($coincidencias[1] as $rfc) {
            if (! in_array($rfc, $propios, true)) {
                $candidatos[$rfc] = true;
            }
        }
        $lista = array_keys($candidatos);

        return count($lista) === 1 ? $lista[0] : null;
    }

    private function nombreEmisorEnTexto(string $texto): ?string
    {
        if (! preg_match('/([A-ZÁÉÍÓÚÑ0-9][A-ZÁÉÍÓÚÑ0-9 .,&]{6,160})\s+RFC\s*:/u', mb_strtoupper($texto, 'UTF-8'), $m)) {
            return null;
        }
        $nombre = trim($m[1]);
        if (preg_match('/\d|CALLE|COLONIA|COL\.|MEXICO|PRIVADA|C\.P/', $nombre)) {
            return null;
        }

        return $this->texto($nombre, 180);
    }

    private function folio(mixed $valor): ?string
    {
        $folio = strtoupper(trim((string) $valor));
        $folio = trim($folio, '-');
        if ($folio === '' || in_array($folio, ['N/A', 'NA', 'S/N', 'SN'], true)) {
            return null;
        }

        return mb_substr($folio, 0, 40);
    }

    private function fecha(string $valor): ?string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $valor, $m)) {
            return $m[1];
        }

        return null;
    }

    private function moneda(string $valor): ?string
    {
        $valor = strtoupper(trim($valor));

        return array_key_exists($valor, FacturaAgenteAduanal::MONEDAS) ? $valor : null;
    }

    private function rfc(mixed $valor): ?string
    {
        $rfc = strtoupper(trim((string) $valor));

        return preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc) ? $rfc : null;
    }

    private function texto(mixed $valor, int $max): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : mb_substr($texto, 0, $max);
    }

    private function dinero(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        $numero = (float) $valor;
        if ($numero <= 0) {
            return null;
        }

        return number_format($numero, 2, '.', '');
    }

    /**
     * @param  list<string>  $avisos
     * @return array<string, mixed>
     */
    private function vacio(array $avisos = []): array
    {
        return [
            'leido' => false,
            'serie' => null,
            'folio' => null,
            'fecha' => null,
            'moneda' => null,
            'importe' => null,
            'honorarios' => null,
            'impuestos' => null,
            'retenciones' => null,
            'tipo_operacion' => null,
            'estado_cancelacion' => null,
            'rfc_emisor' => null,
            'nombre_emisor' => null,
            'uuid' => null,
            'avisos' => $avisos,
        ];
    }
}
