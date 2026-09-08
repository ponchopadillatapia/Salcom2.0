<?php

namespace App\Services\Bancario;

/**
 * Validación matemática de CLABE (Banxico) y cruce con el catálogo SPEI.
 * No consulta al banco: no confirma que la cuenta exista ni el titular.
 */
class ClabeValidator
{
    /** @var list<int> */
    private const PESOS = [3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7];

    public function soloDigitos(string $clabe): string
    {
        return preg_replace('/\D/', '', $clabe) ?? '';
    }

    public function esFormatoValido(string $clabe): bool
    {
        return strlen($this->soloDigitos($clabe)) === 18;
    }

    public function tieneChecksumValido(string $clabe): bool
    {
        $digitos = $this->soloDigitos($clabe);
        if (strlen($digitos) !== 18) {
            return false;
        }

        return (int) $digitos[17] === $this->digitoVerificador(substr($digitos, 0, 17));
    }

    public function digitoVerificador(string $primeros17): int
    {
        $primeros17 = $this->soloDigitos($primeros17);
        if (strlen($primeros17) !== 17) {
            return -1;
        }

        $suma = 0;
        for ($i = 0; $i < 17; $i++) {
            $suma += ((int) $primeros17[$i] * self::PESOS[$i]) % 10;
        }

        return (10 - ($suma % 10)) % 10;
    }

    public function codigoBanco(string $clabe): ?string
    {
        $digitos = $this->soloDigitos($clabe);

        return strlen($digitos) >= 3 ? substr($digitos, 0, 3) : null;
    }

    public function nombreBanco(string $clabe): ?string
    {
        $codigo = $this->codigoBanco($clabe);
        if ($codigo === null) {
            return null;
        }

        $aliases = config('clabe_bancos.instituciones.'.$codigo);

        return is_array($aliases) && $aliases !== [] ? (string) $aliases[0] : null;
    }

    /**
     * Si el prefijo no está en el catálogo o no hay banco declarado, no bloquea.
     */
    public function bancoCoincideConClabe(string $clabe, string $bancoDeclarado): bool
    {
        $bancoDeclarado = trim($bancoDeclarado);
        if ($bancoDeclarado === '') {
            return true;
        }

        $codigo = $this->codigoBanco($clabe);
        if ($codigo === null) {
            return true;
        }

        $aliases = config('clabe_bancos.instituciones.'.$codigo);
        if (! is_array($aliases) || $aliases === []) {
            return true;
        }

        $declarado = $this->normalizarBanco($bancoDeclarado);
        foreach ($aliases as $alias) {
            $canon = $this->normalizarBanco((string) $alias);
            if ($canon === '' || $declarado === '') {
                continue;
            }
            if ($canon === $declarado || str_contains($declarado, $canon) || str_contains($canon, $declarado)) {
                return true;
            }
        }

        return false;
    }

    private function normalizarBanco(string $nombre): string
    {
        $nombre = mb_strtoupper(trim($nombre), 'UTF-8');
        $nombre = strtr($nombre, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
        $nombre = preg_replace('/[^A-Z0-9\s]/', ' ', $nombre) ?? $nombre;
        $nombre = preg_replace('/\s+/', ' ', $nombre) ?? $nombre;

        return trim($nombre);
    }
}
