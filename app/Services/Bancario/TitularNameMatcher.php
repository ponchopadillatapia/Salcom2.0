<?php

namespace App\Services\Bancario;

class TitularNameMatcher
{
    public function __construct(
        private TitularNameNormalizer $normalizer = new TitularNameNormalizer,
    ) {}

    /**
     * @param  array{
     *   nombres_candidato?: list<string|null>,
     *   rfc?: string|null,
     *   tipo_persona?: string|null,
     *   apellido_paterno?: string|null,
     *   apellido_materno?: string|null,
     *   nombres?: string|null,
     *   razon_social?: string|null
     * }  $declarado
     * @return array{
     *   decision: 'aceptado'|'revision'|'rechazado',
     *   motivo: string,
     *   score: int,
     *   mensaje: string,
     *   titular_normalizado: string,
     *   candidato_usado: ?string
     * }
     */
    public function comparar(?string $titular, array $declarado): array
    {
        $titular = trim((string) $titular);
        $rfcCaratula = strtoupper(preg_replace('/\s+/', '', (string) ($declarado['rfc_caratula'] ?? '')) ?? '');
        $rfcEsperado = strtoupper(preg_replace('/\s+/', '', (string) ($declarado['rfc'] ?? '')) ?? '');

        if ($rfcCaratula !== '' && $rfcEsperado !== '' && $rfcCaratula === $rfcEsperado) {
            return $this->resultado('aceptado', 'rfc', 100, 'RFC de la carátula coincide con el del proveedor', $titular, null);
        }

        if ($titular === '') {
            return $this->resultado(
                'revision',
                'titular_no_extraido',
                0,
                'No se pudo leer el nombre del titular en la carátula. Sube el PDF original del banco (no una foto ni un escaneo).',
                '',
                null
            );
        }

        $candidatos = $this->candidatos($declarado);
        if ($candidatos === []) {
            return $this->resultado(
                'revision',
                'sin_nombre_declarado',
                0,
                'No hay nombre o razón social del proveedor para comparar con el titular de la cuenta.',
                $this->normalizer->normalizar($titular),
                null
            );
        }

        $esFisica = $this->esPersonaFisica($declarado);
        $mejor = null;

        foreach ($candidatos as $candidato) {
            $eval = $esFisica
                ? $this->evaluarFisica($titular, $candidato, $declarado)
                : $this->evaluarMoral($titular, $candidato);

            if ($mejor === null || $eval['score'] > $mejor['score']) {
                $mejor = $eval;
                $mejor['candidato_usado'] = $candidato;
            }

            if ($eval['decision'] === 'aceptado') {
                $eval['candidato_usado'] = $candidato;

                return $eval;
            }
        }

        return $mejor ?? $this->resultado('rechazado', 'sin_coincidencia', 0, 'El titular no coincide con el proveedor.', $this->normalizer->normalizar($titular), null);
    }

    /**
     * @param  array<string, mixed>  $declarado
     * @return list<string>
     */
    private function candidatos(array $declarado): array
    {
        $lista = [];

        foreach ($declarado['nombres_candidato'] ?? [] as $nombre) {
            $nombre = trim((string) $nombre);
            if ($nombre !== '') {
                $lista[] = $nombre;
            }
        }

        foreach (['razon_social', 'nombre_esperado', 'nombre'] as $campo) {
            $valor = trim((string) ($declarado[$campo] ?? ''));
            if ($valor !== '') {
                $lista[] = $valor;
            }
        }

        $fisica = trim(implode(' ', array_filter([
            $declarado['apellido_paterno'] ?? '',
            $declarado['apellido_materno'] ?? '',
            $declarado['nombres'] ?? '',
        ])));
        if ($fisica !== '') {
            $lista[] = $fisica;
            $lista[] = trim(implode(' ', array_filter([
                $declarado['nombres'] ?? '',
                $declarado['apellido_paterno'] ?? '',
                $declarado['apellido_materno'] ?? '',
            ])));
        }

        $unicos = [];
        foreach ($lista as $item) {
            $clave = $this->normalizer->normalizar($item);
            if ($clave !== '' && ! isset($unicos[$clave])) {
                $unicos[$clave] = $item;
            }
        }

        return array_values($unicos);
    }

    /**
     * @param  array<string, mixed>  $declarado
     */
    private function esPersonaFisica(array $declarado): bool
    {
        $tipo = mb_strtolower(trim((string) ($declarado['tipo_persona'] ?? '')), 'UTF-8');

        return str_contains($tipo, 'fisic');
    }

    /**
     * @return array{decision: string, motivo: string, score: int, mensaje: string, titular_normalizado: string, candidato_usado: ?string}
     */
    private function evaluarMoral(string $titular, string $esperado): array
    {
        $normT = $this->normalizer->normalizar($titular);
        $normE = $this->normalizer->normalizar($esperado);
        $titularNorm = $normT;

        if ($normT === '' || $normE === '') {
            return $this->resultado('revision', 'nombre_vacio', 0, 'No hay suficiente texto para comparar el titular.', $titularNorm, $esperado);
        }

        if ($normT === $normE) {
            return $this->resultado('aceptado', 'exacto', 100, 'El titular coincide con la razón social del proveedor.', $titularNorm, $esperado);
        }

        $tokT = $this->normalizer->tokens($titular);
        $tokE = $this->normalizer->tokens($esperado);
        $fuertesE = $this->normalizer->tokensFuertes($tokE);
        $fuertesT = $this->normalizer->tokensFuertes($tokT);

        if ($fuertesE !== [] && $this->todosPresentes($fuertesE, $fuertesT !== [] ? $fuertesT : $tokT)) {
            return $this->resultado('aceptado', 'tokens_fuertes', 95, 'El titular coincide con la razón social del proveedor.', $titularNorm, $esperado);
        }

        $jaccard = $this->jaccard($tokE, $tokT);
        similar_text($normE, $normT, $pct);

        if ($jaccard >= 0.85 || ($pct >= 90 && $jaccard >= 0.5)) {
            return $this->resultado('aceptado', 'similitud_alta', (int) round(max($jaccard * 100, $pct)), 'El titular coincide con la razón social del proveedor.', $titularNorm, $esperado);
        }

        if ($jaccard >= 0.65 || $pct >= 80) {
            return $this->resultado(
                'revision',
                'similitud_parcial',
                (int) round(max($jaccard * 100, $pct)),
                "El titular de la carátula (\"{$titular}\") se parece a la razón social (\"{$esperado}\"), pero no coincide con claridad. Revisión manual.",
                $titularNorm,
                $esperado
            );
        }

        return $this->resultado(
            'rechazado',
            'no_coincide',
            (int) round($jaccard * 100),
            "El titular de la cuenta (\"{$titular}\") no corresponde al proveedor (\"{$esperado}\"). La cuenta debe estar a nombre del proveedor.",
            $titularNorm,
            $esperado
        );
    }

    /**
     * @param  array<string, mixed>  $declarado
     * @return array{decision: string, motivo: string, score: int, mensaje: string, titular_normalizado: string, candidato_usado: ?string}
     */
    private function evaluarFisica(string $titular, string $esperado, array $declarado): array
    {
        $tokT = $this->normalizer->tokens($titular);
        $paterno = $this->normalizer->tokens((string) ($declarado['apellido_paterno'] ?? ''));
        $materno = $this->normalizer->tokens((string) ($declarado['apellido_materno'] ?? ''));
        $nombres = $this->normalizer->tokens((string) ($declarado['nombres'] ?? ''));

        $tienePartes = $paterno !== [] || $nombres !== [];
        if ($tienePartes) {
            $paternoOk = $paterno === [] || $this->todosPresentes($paterno, $tokT);
            $maternoOk = $materno === [] || $this->todosPresentes($materno, $tokT);
            $nombreOk = $nombres === [] || count(array_intersect($nombres, $tokT)) >= 1;

            if ($paterno !== [] && ! $paternoOk) {
                return $this->resultado(
                    'rechazado',
                    'apellido_distinto',
                    20,
                    "El titular de la cuenta (\"{$titular}\") no corresponde al proveedor (\"{$esperado}\"). La cuenta debe estar a nombre del proveedor.",
                    $this->normalizer->normalizar($titular),
                    $esperado
                );
            }

            if ($materno !== [] && ! $maternoOk) {
                return $this->resultado(
                    'rechazado',
                    'apellido_materno_distinto',
                    35,
                    "El titular de la cuenta (\"{$titular}\") no corresponde al proveedor (\"{$esperado}\"). La cuenta debe estar a nombre del proveedor.",
                    $this->normalizer->normalizar($titular),
                    $esperado
                );
            }

            if ($paternoOk && $maternoOk && $nombreOk) {
                return $this->resultado('aceptado', 'apellidos_y_nombre', 95, 'El titular coincide con el nombre del proveedor.', $this->normalizer->normalizar($titular), $esperado);
            }
        }

        return $this->evaluarMoral($titular, $esperado);
    }

    /**
     * @param  list<string>  $buscados
     * @param  list<string>  $en
     */
    private function todosPresentes(array $buscados, array $en): bool
    {
        foreach ($buscados as $token) {
            if (! in_array($token, $en, true)) {
                return false;
            }
        }

        return $buscados !== [];
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    private function jaccard(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }

        $inter = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));

        return $union > 0 ? $inter / $union : 0.0;
    }

    /**
     * @return array{decision: string, motivo: string, score: int, mensaje: string, titular_normalizado: string, candidato_usado: ?string}
     */
    private function resultado(
        string $decision,
        string $motivo,
        int $score,
        string $mensaje,
        string $titularNorm,
        ?string $candidato
    ): array {
        return [
            'decision' => $decision,
            'motivo' => $motivo,
            'score' => $score,
            'mensaje' => $mensaje,
            'titular_normalizado' => $titularNorm,
            'candidato_usado' => $candidato,
        ];
    }
}
