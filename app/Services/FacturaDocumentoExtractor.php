<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

/**
 * Extrae campos fiscales de PDF de factura u orden de compra (texto embebido).
 */
class FacturaDocumentoExtractor
{
    private Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? new Parser;
    }

    /**
     * @return array{texto: string, campos: array<string, mixed>, escaneado: bool}
     */
    public function extraerDesdeContenido(string $pdfBinary): array
    {
        $texto = '';
        $escaneado = false;

        try {
            $tmp = tempnam(sys_get_temp_dir(), 'facpdf_');
            if ($tmp === false) {
                return ['texto' => '', 'campos' => [], 'escaneado' => true];
            }
            file_put_contents($tmp, $pdfBinary);
            try {
                $texto = $this->parser->parseFile($tmp)->getText() ?? '';
            } finally {
                @unlink($tmp);
            }
        } catch (\Throwable) {
            $escaneado = true;
        }

        $texto = $this->normalizar($texto);
        if (mb_strlen(trim($texto)) < 40) {
            $escaneado = true;
        }

        return [
            'texto' => $texto,
            'campos' => $this->parsearCampos($texto),
            'escaneado' => $escaneado,
        ];
    }

    /**
     * @return array{texto: string, campos: array<string, mixed>, escaneado: bool}
     */
    public function extraerDesdeRuta(string $path): array
    {
        if (! is_readable($path)) {
            return ['texto' => '', 'campos' => [], 'escaneado' => true];
        }

        return $this->extraerDesdeContenido((string) file_get_contents($path));
    }

    /**
     * @return array<string, mixed>
     */
    public function parsearCampos(string $texto): array
    {
        $t = strtoupper($texto);
        $campos = [
            'rfc_emisor' => null,
            'rfc_receptor' => null,
            'uuid' => null,
            'fecha' => null,
            'regimen_fiscal' => null,
            'metodo_pago' => null,
            'forma_pago' => null,
            'moneda' => null,
            'subtotal' => null,
            'iva' => null,
            'retencion_iva' => null,
            'retencion_isr' => null,
            'total' => null,
            'conceptos' => [],
        ];

        // UUID
        if (preg_match('/\b([0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12})\b/', $t, $m)) {
            $campos['uuid'] = $m[1];
        }

        // RFCs — primero junto a etiquetas, luego genéricos
        $rfcs = [];
        if (preg_match_all('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/', $t, $all)) {
            $rfcs = array_values(array_unique($all[1]));
        }

        $rfcSalcom = strtoupper(trim((string) config('facturas.rfc_receptor', 'ISA951017A10')));

        if (preg_match('/(?:RFC\s*(?:EMISOR|EXPEDIDOR|PROVEEDOR)?\s*[:\.]?\s*)([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/', $t, $m)) {
            $campos['rfc_emisor'] = $m[1];
        }
        if (preg_match('/(?:RFC\s*(?:RECEPTOR|CLIENTE|RECEPTORA)\s*[:\.]?\s*)([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/', $t, $m)) {
            $campos['rfc_receptor'] = $m[1];
        }

        if (! $campos['rfc_receptor'] && $rfcSalcom && in_array($rfcSalcom, $rfcs, true)) {
            $campos['rfc_receptor'] = $rfcSalcom;
        }
        if (! $campos['rfc_emisor']) {
            foreach ($rfcs as $rfc) {
                if ($rfc !== $campos['rfc_receptor'] && $rfc !== $rfcSalcom) {
                    $campos['rfc_emisor'] = $rfc;
                    break;
                }
            }
        }

        // Fecha
        if (preg_match('/(?:FECHA(?:\s*DE\s*EMISI[OÓ]N)?|FECHA\s*EXPEDI?CI[OÓ]N)\s*[:\.]?\s*(\d{4}-\d{2}-\d{2})/', $t, $m)) {
            $campos['fecha'] = $m[1];
        } elseif (preg_match('/(?:FECHA(?:\s*DE\s*EMISI[OÓ]N)?)\s*[:\.]?\s*(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $t, $m)) {
            $campos['fecha'] = $this->normalizarFecha($m[1]);
        } elseif (preg_match('/\b(\d{4}-\d{2}-\d{2})T\d{2}:/', $t, $m)) {
            $campos['fecha'] = $m[1];
        }

        // Régimen
        if (preg_match('/R[EÉ]GIMEN\s*FISCAL\s*[:\.]?\s*(\d{3})/', $t, $m)) {
            $campos['regimen_fiscal'] = $m[1];
        } elseif (preg_match('/\b(60[1-9]|61[0-6]|62[0-6])\b/', $t, $m) && str_contains($t, 'REGIMEN')) {
            $campos['regimen_fiscal'] = $m[1];
        }

        // Método / forma de pago
        if (preg_match('/M[EÉ]TODO\s*(?:DE\s*)?PAGO\s*[:\.]?\s*(PUE|PPD)/', $t, $m)) {
            $campos['metodo_pago'] = $m[1];
        } elseif (preg_match('/\b(PUE|PPD)\b/', $t, $m)) {
            $campos['metodo_pago'] = $m[1];
        }

        if (preg_match('/FORMA\s*(?:DE\s*)?PAGO\s*[:\.]?\s*(\d{2})/', $t, $m)) {
            $campos['forma_pago'] = $m[1];
        }

        // Moneda
        if (preg_match('/MONEDA\s*[:\.]?\s*(MXN|USD|EUR|XXX)/', $t, $m)) {
            $campos['moneda'] = $m[1];
        } elseif (preg_match('/\b(MXN|USD)\b/', $t, $m)) {
            $campos['moneda'] = $m[1];
        }

        // Montos
        $campos['subtotal'] = $this->extraerMonto($t, ['SUBTOTAL', 'SUB TOTAL', 'SUB-TOTAL']);
        $campos['total'] = $this->extraerMonto($t, ['TOTAL']);
        $campos['iva'] = $this->extraerMonto($t, ['IVA TRASLADADO', 'IMPUESTO TRASLADADO', 'IVA 16', ' I\.V\.A\. ', ' IVA ']);
        $campos['retencion_iva'] = $this->extraerMonto($t, ['RETENCION IVA', 'RETENCI[OÓ]N.*IVA', 'IVA RETENIDO', 'RET\.?\s*IVA']);
        $campos['retencion_isr'] = $this->extraerMonto($t, ['RETENCION ISR', 'RETENCI[OÓ]N.*ISR', 'ISR RETENIDO', 'RET\.?\s*ISR']);

        // Conceptos (descripciones cercanas a líneas de importe)
        if (preg_match_all('/(?:DESCRIPCI[OÓ]N|CONCEPTO)\s*[:\.]?\s*([A-Z0-9ÁÉÍÓÚÜÑ ,\.\-\/]{8,120})/', $t, $m)) {
            $campos['conceptos'] = array_values(array_unique(array_map('trim', $m[1])));
        }

        return $campos;
    }

    /**
     * @param  list<string>  $etiquetas
     */
    private function extraerMonto(string $texto, array $etiquetas): ?float
    {
        foreach ($etiquetas as $eti) {
            $pattern = '/'.$eti.'\s*[:\.]?\s*\$?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{2})?|[0-9]+(?:\.[0-9]{2})?)/u';
            if (preg_match($pattern, $texto, $m)) {
                return $this->parseMonto($m[1]);
            }
        }

        return null;
    }

    private function parseMonto(string $raw): float
    {
        $raw = str_replace([',', ' '], ['', ''], trim($raw));

        return round((float) $raw, 2);
    }

    private function normalizarFecha(string $raw): string
    {
        $raw = str_replace('-', '/', $raw);
        $parts = explode('/', $raw);
        if (count($parts) !== 3) {
            return $raw;
        }
        [$a, $b, $c] = $parts;
        if (strlen($c) === 2) {
            $c = '20'.$c;
        }
        // dd/mm/yyyy vs yyyy/mm/dd
        if (strlen($a) === 4) {
            return sprintf('%04d-%02d-%02d', (int) $a, (int) $b, (int) $c);
        }

        return sprintf('%04d-%02d-%02d', (int) $c, (int) $b, (int) $a);
    }

    private function normalizar(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace('/[ \t]+/', ' ', $texto) ?? $texto;

        return trim($texto);
    }
}
