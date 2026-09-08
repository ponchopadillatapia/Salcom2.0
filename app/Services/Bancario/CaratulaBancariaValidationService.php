<?php

namespace App\Services\Bancario;

use App\Services\AuditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Smalot\PdfParser\Parser;
use Throwable;

class CaratulaBancariaValidationService
{
    public function __construct(
        private ClabeValidator $clabe = new ClabeValidator,
        private CaratulaCampoExtractor $extractor = new CaratulaCampoExtractor,
        private TitularNameMatcher $matcher = new TitularNameMatcher,
    ) {}

    /**
     * Lee el PDF, extrae campos y aplica CLABE + titular.
     *
     * @param  array<string, mixed>  $declarado
     * @return array{
     *   ok: bool,
     *   decision: 'aceptado'|'revision'|'rechazado',
     *   errores: list<string>,
     *   advertencias: list<string>,
     *   hallazgos: list<string>,
     *   datos: array<string, mixed>,
     *   texto: string
     * }
     */
    public function validarPdf(string $rutaPdf, array $declarado): array
    {
        $texto = $this->extraerTextoPdf($rutaPdf);
        if ($texto === null || trim($texto) === '') {
            return $this->respuesta(
                'rechazado',
                ['No se pudo leer la CLABE en la carátula. Sube el PDF original del banco (no una imagen escaneada).'],
                [],
                [],
                []
            );
        }

        $base = [
            'valida' => true,
            'datos' => [],
            'errores' => [],
            'hallazgos' => [],
        ];

        return $this->enriquecer($base, $texto, $declarado);
    }

    /**
     * Añade checksum, banco vs prefijo y titular sobre un resultado ya extraído.
     * No quita el cruce de CLABE que ya hizo el llamador.
     *
     * @param  array{valida?: bool, datos?: array, errores?: list<string>, hallazgos?: list<string>}  $resultado
     * @param  array<string, mixed>  $declarado
     * @return array{
     *   ok: bool,
     *   valida: bool,
     *   decision: 'aceptado'|'revision'|'rechazado',
     *   errores: list<string>,
     *   advertencias: list<string>,
     *   hallazgos: list<string>,
     *   datos: array<string, mixed>,
     *   texto: string
     * }
     */
    public function enriquecer(array $resultado, string $texto, array $declarado, bool $compararClabe = true): array
    {
        $resultado['datos'] = is_array($resultado['datos'] ?? null) ? $resultado['datos'] : [];
        $resultado['errores'] = array_values($resultado['errores'] ?? []);
        $resultado['hallazgos'] = array_values($resultado['hallazgos'] ?? []);
        $advertencias = [];

        $campos = $this->extractor->extraer($texto);

        if (empty($resultado['datos']['clabe']) && $campos['clabe']) {
            $resultado['datos']['clabe'] = $campos['clabe'];
        }

        $titular = $campos['titular'];
        if (! is_string($titular) || trim($titular) === '') {
            $viejo = trim((string) ($resultado['datos']['titular'] ?? ''));
            $titular = $this->extractor->pareceTitular($viejo) ? $viejo : null;
        }
        if (is_string($titular) && trim($titular) !== '') {
            $resultado['datos']['titular'] = trim($titular);
        } else {
            $resultado['datos']['titular'] = null;
        }

        if ($campos['rfc']) {
            $resultado['datos']['rfc_caratula'] = $campos['rfc'];
        }

        $clabePdf = $this->clabe->soloDigitos((string) ($resultado['datos']['clabe'] ?? $campos['clabe'] ?? ''));
        $clabeDeclarada = $this->clabe->soloDigitos((string) ($declarado['clabe'] ?? ''));

        if ($compararClabe && ($clabePdf === '' || strlen($clabePdf) !== 18)) {
            $resultado['errores'][] = 'No se pudo leer la CLABE en la carátula. Sube el PDF original del banco (no una imagen escaneada).';
        } elseif (strlen($clabePdf) === 18) {
            $resultado['datos']['clabe'] = $clabePdf;
            $resultado['datos']['banco_prefijo'] = $this->clabe->codigoBanco($clabePdf);
            $resultado['datos']['banco_catalogo'] = $this->clabe->nombreBanco($clabePdf);

            if (! $this->clabe->tieneChecksumValido($clabePdf)) {
                $resultado['errores'][] = 'La CLABE de la carátula no es válida (dígito verificador incorrecto). Verifica que el PDF no esté recortado ni editado.';
                $resultado['datos']['clabe_checksum_ok'] = false;
            } else {
                $resultado['datos']['clabe_checksum_ok'] = true;
                $resultado['hallazgos'][] = 'CLABE con dígito verificador válido';
            }

            if ($compararClabe) {
                if ($clabeDeclarada !== '' && strlen($clabeDeclarada) === 18 && $clabePdf !== $clabeDeclarada) {
                    $resultado['errores'][] = 'La CLABE de la carátula ('.$clabePdf.') no coincide con la CLABE que registraste ('.$clabeDeclarada.'). La carátula debe ser de la misma cuenta que declaraste. Documento rechazado.';
                } elseif ($clabeDeclarada !== '' && $clabePdf === $clabeDeclarada) {
                    $resultado['hallazgos'][] = 'CLABE coincide con la declarada en el formulario';
                }
            }

            $bancoDeclarado = trim((string) ($declarado['banco'] ?? ''));
            if ($bancoDeclarado !== '' && ! $this->clabe->bancoCoincideConClabe($clabePdf, $bancoDeclarado)) {
                $bancoCatalogo = $this->clabe->nombreBanco($clabePdf) ?: 'otra institución';
                $resultado['errores'][] = 'El banco declarado ("'.$bancoDeclarado.'") no corresponde al de la CLABE ('.$bancoCatalogo.', código '.$this->clabe->codigoBanco($clabePdf).').';
            }
        }

        $match = $this->matcher->comparar(
            $resultado['datos']['titular'] ?? null,
            array_merge($declarado, [
                'rfc_caratula' => $resultado['datos']['rfc_caratula'] ?? $campos['rfc'],
            ])
        );

        $resultado['datos']['titular_score'] = $match['score'];
        $resultado['datos']['titular_decision'] = $match['decision'];
        $resultado['datos']['titular_motivo'] = $match['motivo'];
        $resultado['datos']['validacion_titular'] = $match;

        if ($match['decision'] === 'aceptado') {
            $resultado['hallazgos'][] = $match['mensaje'];
            $resultado['errores'] = array_values(array_filter(
                $resultado['errores'],
                fn (string $e) => ! str_contains(mb_strtolower($e), 'titular')
            ));
        } elseif ($match['motivo'] === 'titular_no_extraido' || $match['motivo'] === 'sin_nombre_declarado') {
            $resultado['errores'][] = $match['mensaje'];
        } elseif ($match['decision'] === 'revision') {
            $resultado['errores'][] = $match['mensaje'];
            $advertencias[] = $match['mensaje'];
        } else {
            $resultado['errores'][] = $match['mensaje'];
        }

        $resultado['errores'] = array_values(array_unique($resultado['errores']));
        $resultado['hallazgos'] = array_values(array_unique($resultado['hallazgos']));
        $resultado['valida'] = $resultado['errores'] === [];

        $decision = 'aceptado';
        if ($resultado['errores'] !== []) {
            $decision = $match['decision'] === 'revision' && $this->soloErroresDeRevision($resultado['errores'], $match)
                ? 'revision'
                : 'rechazado';
            if ($clabePdf !== '' && $clabeDeclarada !== '' && $clabePdf !== $clabeDeclarada) {
                $decision = 'rechazado';
            }
            if (($resultado['datos']['clabe_checksum_ok'] ?? true) === false) {
                $decision = 'rechazado';
            }
            if ($match['decision'] === 'rechazado') {
                $decision = 'rechazado';
            }
        }

        $respuesta = $this->respuesta(
            $decision,
            $resultado['errores'],
            $advertencias,
            $resultado['hallazgos'],
            $resultado['datos'],
            $texto
        );
        $respuesta['valida'] = $resultado['valida'];

        $this->auditar($respuesta, $declarado);

        return $respuesta;
    }

    public function extraerTextoPdf(string $rutaPdf): ?string
    {
        try {
            $parser = new Parser;
            $texto = $parser->parseFile($rutaPdf)->getText();
        } catch (Throwable $e) {
            Log::warning('[Caratula] No se pudo leer el PDF: '.$e->getMessage());

            return null;
        }

        return is_string($texto) ? $texto : null;
    }

    /**
     * @param  list<string>  $errores
     * @param  array{mensaje: string, decision: string}  $match
     */
    private function soloErroresDeRevision(array $errores, array $match): bool
    {
        if ($match['decision'] !== 'revision') {
            return false;
        }

        foreach ($errores as $error) {
            if ($error !== $match['mensaje']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $errores
     * @param  list<string>  $advertencias
     * @param  list<string>  $hallazgos
     * @param  array<string, mixed>  $datos
     * @return array{
     *   ok: bool,
     *   valida: bool,
     *   decision: 'aceptado'|'revision'|'rechazado',
     *   errores: list<string>,
     *   advertencias: list<string>,
     *   hallazgos: list<string>,
     *   datos: array<string, mixed>,
     *   texto: string
     * }
     */
    private function respuesta(
        string $decision,
        array $errores,
        array $advertencias,
        array $hallazgos,
        array $datos,
        string $texto = ''
    ): array {
        return [
            'ok' => $decision === 'aceptado',
            'valida' => $errores === [],
            'decision' => $decision,
            'errores' => $errores,
            'advertencias' => $advertencias,
            'hallazgos' => $hallazgos,
            'datos' => $datos,
            'texto' => $texto,
        ];
    }

    /**
     * @param  array<string, mixed>  $respuesta
     * @param  array<string, mixed>  $declarado
     */
    private function auditar(array $respuesta, array $declarado): void
    {
        $accion = match ($respuesta['decision']) {
            'aceptado' => 'caratula_aceptada',
            'revision' => 'caratula_revision',
            default => 'caratula_rechazada',
        };

        $nivel = $respuesta['decision'] === 'aceptado' ? 'info' : 'warning';
        $descripcion = $respuesta['errores'][0]
            ?? $respuesta['hallazgos'][0]
            ?? 'Validación de carátula bancaria';

        $payload = [
            'decision' => $respuesta['decision'],
            'clabe_pdf' => $respuesta['datos']['clabe'] ?? null,
            'clabe_declarada' => $this->clabe->soloDigitos((string) ($declarado['clabe'] ?? '')),
            'clabe_checksum_ok' => $respuesta['datos']['clabe_checksum_ok'] ?? null,
            'banco_prefijo' => $respuesta['datos']['banco_prefijo'] ?? null,
            'titular_extraido' => $respuesta['datos']['titular'] ?? null,
            'score' => $respuesta['datos']['titular_score'] ?? null,
            'motivo' => $respuesta['datos']['titular_motivo'] ?? null,
            'rfc_caratula' => $respuesta['datos']['rfc_caratula'] ?? null,
        ];

        try {
            if (! Schema::hasTable('audit_log')) {
                Log::log($nivel === 'warning' ? 'warning' : 'info', '[Caratula] '.$descripcion, $payload);

                return;
            }

            AuditService::registrar(
                $accion,
                'caratula_banco',
                $descripcion,
                datosAfter: $payload,
                nivel: $nivel
            );
        } catch (Throwable $e) {
            Log::warning('[Caratula] No se pudo guardar auditoría: '.$e->getMessage(), $payload);
        }
    }
}
