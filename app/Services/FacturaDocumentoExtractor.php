<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

/**
 * Extrae el texto completo de un PDF (factura u OC) para cruce por full-text search.
 * No intenta mapear campos fiscales con expresiones regulares.
 */
class FacturaDocumentoExtractor
{
    private Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        $this->parser = $parser ?? new Parser;
    }

    /**
     * @return array{texto: string, escaneado: bool}
     */
    public function extraerDesdeContenido(string $pdfBinary): array
    {
        $texto = '';
        $escaneado = false;

        try {
            $tmp = tempnam(sys_get_temp_dir(), 'facpdf_');
            if ($tmp === false) {
                return ['texto' => '', 'escaneado' => true];
            }
            file_put_contents($tmp, $pdfBinary);
            try {
                $texto = $this->parser->parseFile($tmp)->getText();
            } finally {
                @unlink($tmp);
            }
        } catch (\Throwable) {
            $escaneado = true;
        }

        $texto = $this->normalizarAMinusculas($texto);
        if (mb_strlen(trim($texto)) < 40) {
            $escaneado = true;
        }

        return [
            'texto' => $texto,
            'escaneado' => $escaneado,
        ];
    }

    /**
     * @return array{texto: string, escaneado: bool}
     */
    public function extraerDesdeRuta(string $path): array
    {
        if (! is_readable($path)) {
            return ['texto' => '', 'escaneado' => true];
        }

        return $this->extraerDesdeContenido((string) file_get_contents($path));
    }

    private function normalizarAMinusculas(string $texto): string
    {
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace('/[ \t]+/', ' ', $texto) ?? $texto;

        return mb_strtolower(trim($texto), 'UTF-8');
    }
}
