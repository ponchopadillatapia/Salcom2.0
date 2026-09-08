<?php

namespace App\Services\Bancario;

class CaratulaCampoExtractor
{
    public function __construct(
        private ClabeValidator $clabe = new ClabeValidator,
    ) {}

    /**
     * @return array{clabe: ?string, titular: ?string, rfc: ?string}
     */
    public function extraer(string $texto): array
    {
        $texto = $this->preparar($texto);

        return [
            'clabe' => $this->extraerClabe($texto),
            'titular' => $this->extraerTitular($texto),
            'rfc' => $this->extraerRfc($texto),
        ];
    }

    public function extraerClabe(string $texto): ?string
    {
        $texto = $this->preparar($texto);

        if (preg_match('/CLABE[^0-9]{0,20}((?:\d[\s-]?){18})/u', $texto, $m)) {
            $clabe = $this->clabe->soloDigitos($m[1]);
            if (strlen($clabe) === 18) {
                return $clabe;
            }
        }

        $compacto = preg_replace('/\s+/', '', $texto) ?? $texto;
        if (preg_match('/(?:CUENTACLABE|CLABE)(\d{18})/u', $compacto, $m2)) {
            return $m2[1];
        }
        if (preg_match('/\b(\d{18})\b/', $compacto, $m3)) {
            return $m3[1];
        }

        return null;
    }

    public function extraerTitular(string $texto): ?string
    {
        $texto = $this->preparar($texto);
        $etiquetas = [
            'TITULAR DE LA CUENTA',
            'NOMBRE DEL TITULAR',
            'NOMBRE DEL CLIENTE',
            'RAZON SOCIAL',
            'DENOMINACION SOCIAL',
            'DENOMINACION',
            'BENEFICIARIO',
            'TITULAR',
            'CLIENTE',
        ];

        foreach ($etiquetas as $etiqueta) {
            $patron = '/'.$this->patronEtiqueta($etiqueta).'[:\s]+([A-Z0-9Ñ&.,\-\s]{5,90})/u';
            if (! preg_match($patron, $texto, $m)) {
                continue;
            }
            $nombre = $this->limpiarNombre($m[1]);
            if ($this->esNombreUtil($nombre)) {
                return $nombre;
            }
        }

        return null;
    }

    public function pareceTitular(?string $nombre): bool
    {
        if ($nombre === null) {
            return false;
        }

        return $this->esNombreUtil($this->limpiarNombre($this->preparar($nombre)));
    }

    public function extraerRfc(string $texto): ?string
    {
        $texto = $this->preparar($texto);
        if (preg_match('/\bRFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/u', $texto, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/u', $texto, $m2)) {
            return strtoupper($m2[1]);
        }

        return null;
    }

    private function preparar(string $texto): string
    {
        $texto = strtr($texto, [
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'Ñ',
        ]);
        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = preg_replace('/\s+/', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    private function patronEtiqueta(string $etiqueta): string
    {
        $partes = explode(' ', $etiqueta);
        $escapadas = array_map(fn (string $p) => preg_quote($p, '/'), $partes);

        return implode('\s+', $escapadas);
    }

    private function limpiarNombre(string $nombre): string
    {
        $nombre = preg_replace('/\s+(CLABE|RFC|CURP|CUENTA|NO\.?\s*CUENTA|SUCURSAL|BANCO|NUMERO|N[UÚ]MERO).*/u', '', $nombre) ?? $nombre;
        $nombre = trim($nombre, " \t:-.,;");

        return trim($nombre);
    }

    private function esNombreUtil(string $nombre): bool
    {
        if (mb_strlen($nombre) < 5) {
            return false;
        }

        $prohibidos = [
            'DEL BANCO',
            'DE LA CUENTA',
            'DEL CLIENTE',
            'INTERBANCARIA',
            'CLABE',
        ];
        foreach ($prohibidos as $malo) {
            if ($nombre === $malo || str_starts_with($nombre, $malo.' ')) {
                return false;
            }
        }

        return (bool) preg_match('/[A-ZÑ]{3,}/u', $nombre);
    }
}
