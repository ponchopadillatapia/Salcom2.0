<?php

namespace App\Services;

/**
 * Servicio de validación cruzada entre CIF e INE.
 * Verifica que ambos documentos pertenezcan al mismo proveedor.
 */
class DocumentCrossCheckService
{
    /** Umbral mínimo de similitud para nombres (0-100) */
    private const NOMBRE_UMBRAL = 90;

    /**
     * Ejecuta la validación cruzada completa entre CIF e INE.
     *
     * @param array $datosCif  Datos extraídos del CIF ['rfc', 'nombre', 'codigo_postal', 'tipo_persona']
     * @param array $datosIne  Datos extraídos de la INE ['curp', 'nombre', 'clave_elector', 'fecha_nacimiento']
     * @return array ['valido' => bool, 'score' => int, 'checks' => [...], 'alertas' => [...]]
     */
    public function validar(array $datosCif, array $datosIne): array
    {
        $checks = [];
        $alertas = [];
        $errores = [];

        // ─── 1. Comparación RFC ↔ CURP (primeros 10 caracteres) ───
        $checks['rfc_curp'] = $this->compararRfcCurp(
            $datosCif['rfc'] ?? null,
            $datosIne['curp'] ?? null
        );

        // ─── 2. Comparación CURP exacta (si ambos lo tienen) ───
        $checks['curp'] = $this->compararCurpExacta(
            $datosCif['curp'] ?? null,
            $datosIne['curp'] ?? null
        );

        // ─── 3. Comparación de Nombre Completo (Fuzzy Matching) ───
        $checks['nombre'] = $this->compararNombres(
            $datosCif['nombre'] ?? null,
            $datosIne['nombre'] ?? null
        );

        // ─── 4. Código Postal (solo alerta, no bloquea) ───
        $checkCp = $this->compararCodigoPostal(
            $datosCif['codigo_postal'] ?? null,
            $datosIne['codigo_postal'] ?? null
        );
        if ($checkCp['postal_code_mismatch']) {
            $alertas[] = $checkCp['mensaje'];
        }
        $checks['codigo_postal'] = $checkCp;

        // ─── Calcular resultado final ───
        $criticos = ['rfc_curp', 'nombre'];
        $todoCriticoOk = true;
        foreach ($criticos as $campo) {
            if (!$checks[$campo]['coincide']) {
                $todoCriticoOk = false;
                $errores[] = $checks[$campo]['mensaje'];
            }
        }

        // Score general (0-100)
        $score = $this->calcularScore($checks);

        return [
            'valido' => $todoCriticoOk && $score >= 80,
            'score' => $score,
            'checks' => $checks,
            'errores' => $errores,
            'alertas' => $alertas,
        ];
    }

    // ═══════════════════════════════════════════
    // COMPARACIONES INDIVIDUALES
    // ═══════════════════════════════════════════

    /**
     * Compara RFC del CIF con los primeros 10 caracteres del CURP.
     * RFC persona física = 4 letras + 6 dígitos (10 chars) = primeros 10 del CURP.
     */
    private function compararRfcCurp(?string $rfc, ?string $curp): array
    {
        if (!$rfc || !$curp) {
            return [
                'coincide' => false,
                'mensaje' => 'No se pudo comparar RFC con CURP — dato faltante',
                'rfc' => $rfc,
                'curp' => $curp,
            ];
        }

        $rfcNorm = $this->normalizar($rfc);
        $curpNorm = $this->normalizar($curp);

        // Los primeros 10 caracteres del RFC deben coincidir con los primeros 10 del CURP
        $rfcBase = substr($rfcNorm, 0, 10);
        $curpBase = substr($curpNorm, 0, 10);

        $coincide = $rfcBase === $curpBase;

        return [
            'coincide' => $coincide,
            'mensaje' => $coincide
                ? "RFC coincide con CURP ✓ ({$rfcBase})"
                : "RFC ({$rfcBase}) NO coincide con CURP ({$curpBase})",
            'rfc_base' => $rfcBase,
            'curp_base' => $curpBase,
        ];
    }

    /**
     * Comparación exacta de CURP (18 caracteres).
     */
    private function compararCurpExacta(?string $curpCif, ?string $curpIne): array
    {
        if (!$curpCif || !$curpIne) {
            return [
                'coincide' => true, // No bloquear si no hay CURP en el CIF
                'mensaje' => 'CURP no disponible en ambos documentos — no comparado',
                'disponible' => false,
            ];
        }

        $a = $this->normalizar($curpCif);
        $b = $this->normalizar($curpIne);

        $coincide = $a === $b && strlen($a) === 18;

        return [
            'coincide' => $coincide,
            'mensaje' => $coincide
                ? "CURP coincide exactamente ✓ ({$a})"
                : "CURP NO coincide: CIF={$a} vs INE={$b}",
            'disponible' => true,
        ];
    }

    /**
     * Comparación de nombres con Fuzzy Matching.
     * Usa Levenshtein normalizado + comparación de tokens.
     */
    private function compararNombres(?string $nombreCif, ?string $nombreIne): array
    {
        if (!$nombreCif || !$nombreIne) {
            return [
                'coincide' => false,
                'mensaje' => 'Nombre no disponible en alguno de los documentos',
                'similitud' => 0,
            ];
        }

        $a = $this->normalizar($nombreCif);
        $b = $this->normalizar($nombreIne);

        if ($a === $b) {
            return ['coincide' => true, 'mensaje' => "Nombre coincide exactamente ✓", 'similitud' => 100];
        }

        // Método 1: similar_text (porcentaje)
        similar_text($a, $b, $pctSimilar);

        // Método 2: Levenshtein normalizado
        $maxLen = max(strlen($a), strlen($b));
        $levenshtein = $maxLen > 0 ? (1 - levenshtein($a, $b) / $maxLen) * 100 : 0;

        // Método 3: Comparación por tokens (ignora orden)
        $tokensA = array_filter(explode(' ', $a), fn($t) => strlen($t) > 1);
        $tokensB = array_filter(explode(' ', $b), fn($t) => strlen($t) > 1);
        $tokenScore = 0;
        if (count($tokensA) > 0 && count($tokensB) > 0) {
            $intersect = 0;
            foreach ($tokensA as $ta) {
                foreach ($tokensB as $tb) {
                    if ($ta === $tb || str_contains($ta, $tb) || str_contains($tb, $ta)) {
                        $intersect++;
                        break;
                    }
                }
            }
            $tokenScore = ($intersect / max(count($tokensA), count($tokensB))) * 100;
        }

        // Score final: promedio ponderado (tokens tienen más peso por ser resistentes al orden)
        $similitud = round(($pctSimilar * 0.3) + ($levenshtein * 0.3) + ($tokenScore * 0.4));

        $coincide = $similitud >= self::NOMBRE_UMBRAL;

        return [
            'coincide' => $coincide,
            'mensaje' => $coincide
                ? "Nombre coincide ({$similitud}% similitud) ✓"
                : "Nombre NO coincide: similitud {$similitud}% (mínimo requerido: " . self::NOMBRE_UMBRAL . "%)",
            'similitud' => $similitud,
            'detalle' => [
                'similar_text' => round($pctSimilar),
                'levenshtein' => round($levenshtein),
                'token_match' => round($tokenScore),
            ],
            'nombre_cif' => $a,
            'nombre_ine' => $b,
        ];
    }

    /**
     * Compara código postal — solo genera alerta, no bloquea.
     */
    private function compararCodigoPostal(?string $cpCif, ?string $cpIne): array
    {
        if (!$cpCif || !$cpIne) {
            return [
                'postal_code_mismatch' => false,
                'mensaje' => 'Código postal no disponible en ambos documentos',
            ];
        }

        $cpA = preg_replace('/\D/', '', $cpCif);
        $cpB = preg_replace('/\D/', '', $cpIne);

        $coincide = $cpA === $cpB;

        return [
            'postal_code_mismatch' => !$coincide,
            'mensaje' => $coincide
                ? "Código postal coincide ✓ ({$cpA})"
                : "⚠ Código postal difiere: CIF={$cpA} vs INE={$cpB} (revisión manual recomendada)",
            'cp_cif' => $cpA,
            'cp_ine' => $cpB,
        ];
    }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    /**
     * Normaliza una cadena: mayúsculas, sin acentos, sin caracteres especiales.
     */
    public function normalizar(?string $texto): string
    {
        if (!$texto) return '';

        // Mayúsculas
        $texto = mb_strtoupper($texto, 'UTF-8');

        // Remover acentos/diacríticos
        $texto = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'Ü'],
            ['A', 'E', 'I', 'O', 'U', 'N', 'U'],
            $texto
        );

        // Remover caracteres especiales (dejar solo letras, números y espacios)
        $texto = preg_replace('/[^A-Z0-9\s]/', '', $texto);

        // Eliminar espacios extra
        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim($texto);
    }

    /**
     * Calcula un score general (0-100) basado en todos los checks.
     */
    private function calcularScore(array $checks): int
    {
        $pesos = [
            'rfc_curp' => 40,
            'nombre' => 35,
            'curp' => 15,
            'codigo_postal' => 10,
        ];

        $score = 0;
        foreach ($pesos as $campo => $peso) {
            if (isset($checks[$campo]['coincide']) && $checks[$campo]['coincide']) {
                $score += $peso;
            } elseif ($campo === 'nombre' && isset($checks[$campo]['similitud'])) {
                // Score parcial para nombre basado en similitud
                $score += round($peso * $checks[$campo]['similitud'] / 100);
            } elseif ($campo === 'codigo_postal' && !($checks[$campo]['postal_code_mismatch'] ?? false)) {
                $score += $peso;
            }
        }

        return min(100, $score);
    }
}
