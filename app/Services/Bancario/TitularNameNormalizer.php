<?php

namespace App\Services\Bancario;

class TitularNameNormalizer
{
    /** Sufijos societarios, del más largo al más corto (ya sin puntos). */
    private const SUFIJOS = [
        'S DE R L DE C V',
        'S DE RL DE CV',
        'S A P I DE C V',
        'S A P I DE CV',
        'SAPI DE CV',
        'S A DE C V',
        'SA DE C V',
        'SA DE CV',
        'S P R DE R L',
        'S P R DE RL',
        'S DE R L',
        'S DE RL',
        'S EN C POR A',
        'S EN NC',
        'S A S',
        'S DE C V',
        'S DE CV',
        'SAPI',
        'SAS',
        'SPR',
        'S A',
        'S C',
        'A C',
    ];

    private const STOPWORDS = [
        'DE', 'DEL', 'LA', 'LAS', 'LOS', 'Y', 'THE', 'AND', 'EN', 'EL', 'E',
    ];

    private const ABREVIATURAS = [
        'CIA' => 'COMPANIA',
        'COMPANIA' => 'COMPANIA',
        'HNOS' => 'HERMANOS',
        'GPO' => 'GRUPO',
        'GRUPO' => 'GRUPO',
    ];

    public function normalizar(string $nombre): string
    {
        $nombre = mb_strtoupper(trim($nombre), 'UTF-8');
        $nombre = strtr($nombre, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U',
            'Ñ' => 'N', 'ñ' => 'N', '&' => ' Y ',
        ]);
        $nombre = str_replace(['.', ',', ';', ':', '/', '-', '_', '(', ')', '"', "'"], ' ', $nombre);

        foreach (self::SUFIJOS as $sufijo) {
            $nombre = preg_replace('/\b'.preg_quote($sufijo, '/').'\b/u', ' ', $nombre) ?? $nombre;
        }

        $nombre = preg_replace('/\s+/', ' ', $nombre) ?? $nombre;

        return trim($nombre);
    }

    /**
     * @return list<string>
     */
    public function tokens(string $nombre): array
    {
        $normalizado = $this->normalizar($nombre);
        if ($normalizado === '') {
            return [];
        }

        $partes = preg_split('/\s+/', $normalizado) ?: [];
        $tokens = [];
        foreach ($partes as $parte) {
            if (isset(self::ABREVIATURAS[$parte])) {
                $parte = self::ABREVIATURAS[$parte];
            }
            if (strlen($parte) < 3 || in_array($parte, self::STOPWORDS, true)) {
                continue;
            }
            $tokens[] = $parte;
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Tokens de 4+ letras: identifican mejor a la persona o empresa.
     *
     * @param  list<string>  $tokens
     * @return list<string>
     */
    public function tokensFuertes(array $tokens): array
    {
        return array_values(array_filter($tokens, fn (string $t) => strlen($t) >= 4));
    }
}
