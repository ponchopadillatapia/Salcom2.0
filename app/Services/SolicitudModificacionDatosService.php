<?php

namespace App\Services;

use App\Models\DocumentoProveedor;
use App\Models\ProveedorUser;
use App\Models\SolicitudModificacionDatos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

/**
 * Solicitud de cambio de nombre/razón social con docs (CIF + acta si moral) + validación IA/reglas.
 */
class SolicitudModificacionDatosService
{
    public function __construct(
        private readonly IaService $ia
    ) {}

    public function tienePendiente(int $proveedorId): bool
    {
        return SolicitudModificacionDatos::where('proveedor_id', $proveedorId)
            ->where('estatus', 'pendiente')
            ->exists();
    }

    /**
     * @return array{ok:bool, mensaje:string, solicitud?:SolicitudModificacionDatos}
     */
    public function crearYValidar(
        ProveedorUser $proveedor,
        string $valorPropuesto,
        ?string $motivo,
        UploadedFile $cif,
        ?UploadedFile $acta = null,
    ): array {
        $valorPropuesto = trim(preg_replace('/\s+/', ' ', $valorPropuesto) ?? $valorPropuesto);
        $actual = trim((string) $proveedor->nombre);

        if ($valorPropuesto === '') {
            return ['ok' => false, 'mensaje' => 'Indica el nuevo nombre o razón social.'];
        }

        if (mb_strtoupper($valorPropuesto) === mb_strtoupper($actual)) {
            return ['ok' => false, 'mensaje' => 'El valor propuesto es igual al actual. No hay cambio.'];
        }

        if ($this->tienePendiente($proveedor->id)) {
            return ['ok' => false, 'mensaje' => 'Ya tienes una solicitud pendiente. Espera el resultado antes de enviar otra.'];
        }

        $esMoral = str_contains(mb_strtolower((string) $proveedor->tipo_persona), 'moral');
        if ($esMoral && ! $acta instanceof UploadedFile) {
            return ['ok' => false, 'mensaje' => 'Como Persona Moral debes adjuntar el Acta Constitutiva actualizada además de la Constancia de Situación Fiscal.'];
        }

        $dir = 'solicitudes-modificacion/'.$proveedor->id.'/'.now()->format('YmdHis');
        $pathCif = $cif->storeAs($dir, 'cif_'.Str::random(8).'.pdf', 'local');
        $pathActa = $acta ? $acta->storeAs($dir, 'acta_'.Str::random(8).'.pdf', 'local') : null;

        $campo = $esMoral ? 'razon_social' : 'nombre';

        $solicitud = SolicitudModificacionDatos::create([
            'proveedor_id' => $proveedor->id,
            'campo' => $campo,
            'valor_actual' => $actual,
            'valor_propuesto' => $valorPropuesto,
            'tipo_persona' => $proveedor->tipoPersonaNormalizado(),
            'motivo' => $motivo,
            'estatus' => 'pendiente',
            'archivo_cif' => $pathCif,
            'archivo_acta' => $pathActa,
        ]);

        $resultado = $this->validarDocumentos($solicitud, $esMoral);
        $solicitud->update([
            'resultado_ia' => $resultado,
            'notas' => $resultado['mensaje'] ?? null,
            'revisado_at' => now(),
            'estatus' => ($resultado['aprobado'] ?? false) ? 'aprobada' : 'rechazada',
        ]);

        if ($resultado['aprobado'] ?? false) {
            $this->aplicarCambio($proveedor, $solicitud);

            return [
                'ok' => true,
                'mensaje' => 'Documentos validados. El nombre/razón social se actualizó correctamente.',
                'solicitud' => $solicitud->fresh(),
            ];
        }

        return [
            'ok' => false,
            'mensaje' => 'No se autorizó el cambio: '.($resultado['mensaje'] ?? 'la documentación no respalda el nuevo nombre.'),
            'solicitud' => $solicitud->fresh(),
        ];
    }

    private function validarDocumentos(SolicitudModificacionDatos $solicitud, bool $esMoral): array
    {
        $textoCif = $this->extraerTextoPdf(Storage::disk('local')->path($solicitud->archivo_cif));
        $chequeoCif = $this->validarTextoEsCif($textoCif);

        if (! ($chequeoCif['ok'] ?? false)) {
            return [
                'aprobado' => false,
                'fuente' => 'reglas',
                'mensaje' => $chequeoCif['mensaje'] ?? 'La Constancia de Situación Fiscal no es válida o no se pudo leer.',
                'cif' => $chequeoCif,
            ];
        }

        $textos = ['CIF' => $textoCif];
        if ($esMoral && $solicitud->archivo_acta) {
            $textoActa = $this->extraerTextoPdf(Storage::disk('local')->path($solicitud->archivo_acta));
            $textos['ACTA'] = $textoActa;
            if (strlen(trim($textoActa)) < 40) {
                return [
                    'aprobado' => false,
                    'fuente' => 'reglas',
                    'mensaje' => 'No se pudo leer el Acta Constitutiva (PDF vacío o escaneado sin texto). Sube un PDF legible.',
                    'cif' => $chequeoCif,
                ];
            }
        }

        // El nombre propuesto debe aparecer con similitud suficiente en CIF y/o ACTA
        $matchDocs = $this->documentoContieneNombre($textos, $solicitud->valor_propuesto);
        if (! ($matchDocs['ok'] ?? false)) {
            // Segunda opinión con IA
            $ia = $this->validarConIa($solicitud, $textos, $esMoral);
            if ($ia['aprobado'] ?? false) {
                return array_merge($ia, ['match_heuristico' => $matchDocs, 'cif' => $chequeoCif]);
            }

            return [
                'aprobado' => false,
                'fuente' => 'reglas+ia',
                'mensaje' => $ia['mensaje'] ?? ($matchDocs['mensaje'] ?? 'El nombre propuesto no coincide con los documentos.'),
                'match_heuristico' => $matchDocs,
                'ia' => $ia,
                'cif' => $chequeoCif,
            ];
        }

        $ia = $this->validarConIa($solicitud, $textos, $esMoral);
        // Heurística OK: aprueba salvo que la IA rechace con alta confianza.
        if (($ia['disponible'] ?? false) && ! ($ia['aprobado'] ?? false) && (int) ($ia['confianza'] ?? 0) >= 70) {
            return array_merge($ia, [
                'match_heuristico' => $matchDocs,
                'cif' => $chequeoCif,
                'mensaje' => $ia['mensaje'] ?? 'La IA no confirmó el cambio con la documentación.',
            ]);
        }

        return [
            'aprobado' => true,
            'fuente' => ($ia['disponible'] ?? false) ? 'reglas+ia' : 'reglas',
            'mensaje' => 'Documentos válidos y el nombre/razón social propuesto coincide.',
            'match_heuristico' => $matchDocs,
            'ia' => $ia,
            'cif' => $chequeoCif,
        ];
    }

    private function validarConIa(SolicitudModificacionDatos $solicitud, array $textos, bool $esMoral): array
    {
        $excerpt = '';
        foreach ($textos as $etiqueta => $t) {
            $excerpt .= "--- {$etiqueta} ---\n".mb_substr(preg_replace('/\s+/', ' ', $t) ?? $t, 0, 3500)."\n\n";
        }

        $prompt = "Eres el validador fiscal de Industrias Salcom. Un proveedor solicita cambiar "
            .($esMoral ? 'su razón social' : 'su nombre').".\n"
            ."Nombre actual: {$solicitud->valor_actual}\n"
            ."Nombre propuesto: {$solicitud->valor_propuesto}\n"
            .'Tipo persona: '.($solicitud->tipo_persona ?? '')."\n"
            ."Motivo del proveedor: ".($solicitud->motivo ?: '(sin motivo)')."\n\n"
            ."Documentos (extractos OCR):\n{$excerpt}\n"
            ."Responde SOLO un JSON válido con: {\"aprobado\": true|false, \"confianza\": 0-100, \"motivo\": \"...\"}.\n"
            ."Aprueba SOLO si los documentos SAT respaldan el nombre propuesto (no el anterior) y no hay indicios de fraude. "
            .'Si el OCR no es claro, rechaza.';

        try {
            $resp = $this->ia->llamarClaude($prompt);
            $texto = is_array($resp)
                ? (string) ($resp['content'] ?? $resp['contenido'] ?? $resp['text'] ?? '')
                : (string) $resp;
            $success = is_array($resp) ? (bool) ($resp['success'] ?? ($texto !== '')) : $texto !== '';
            if (! $success || $texto === '' || str_contains(mb_strtolower((string) ($resp['error'] ?? '')), 'no configur')) {
                return ['disponible' => false, 'aprobado' => false, 'mensaje' => $resp['error'] ?? 'IA no disponible'];
            }

            if (preg_match('/\{.*\}/s', $texto, $m)) {
                $json = json_decode($m[0], true);
                if (is_array($json)) {
                    return [
                        'disponible' => true,
                        'aprobado' => (bool) ($json['aprobado'] ?? false),
                        'confianza' => (int) ($json['confianza'] ?? 0),
                        'mensaje' => (string) ($json['motivo'] ?? ''),
                        'raw' => $texto,
                    ];
                }
            }

            return ['disponible' => true, 'aprobado' => false, 'mensaje' => 'La IA no devolvió un veredicto claro.', 'raw' => $texto];
        } catch (\Throwable $e) {
            Log::warning('[SolicitudModificacion] IA falló: '.$e->getMessage());

            return ['disponible' => false, 'aprobado' => false, 'mensaje' => 'IA falló: '.$e->getMessage()];
        }
    }

    private function aplicarCambio(ProveedorUser $proveedor, SolicitudModificacionDatos $solicitud): void
    {
        $nuevo = $solicitud->valor_propuesto;
        $update = ['nombre' => $nuevo];

        if (Schema::hasColumn('proveedores_users', 'datos_identificacion')) {
            $datos = is_array($proveedor->datos_identificacion) ? $proveedor->datos_identificacion : [];
            $esMoral = str_contains(mb_strtolower((string) $proveedor->tipo_persona), 'moral');
            if ($esMoral) {
                $datos['razon_social'] = $nuevo;
                $datos['nombre_esperado'] = $nuevo;
            } else {
                $datos['nombre_esperado'] = $nuevo;
                // Intentar partir en apellidos/nombres si hay 3+ tokens
                $partes = preg_split('/\s+/', $nuevo) ?: [];
                if (count($partes) >= 3) {
                    $datos['apellido_paterno'] = $partes[0];
                    $datos['apellido_materno'] = $partes[1];
                    $datos['nombres'] = implode(' ', array_slice($partes, 2));
                }
            }
            $update['datos_identificacion'] = $datos;
        }

        $proveedor->update($update);

        // Sustituir constancia en expediente con la validada
        if ($solicitud->archivo_cif && Storage::disk('local')->exists($solicitud->archivo_cif)) {
            $dest = 'expediente_fiscal/'.$proveedor->id.'/constancia_fiscal_'.Str::random(8).'.pdf';
            Storage::disk('public')->put($dest, Storage::disk('local')->get($solicitud->archivo_cif));
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $proveedor->id, 'tipo' => 'constancia_fiscal'],
                [
                    'archivo' => $dest,
                    'estatus' => 'aprobado',
                    'notas_revision' => 'Actualizado por solicitud de modificación #'.$solicitud->id,
                    'revisado_at' => now(),
                    'resultado_validacion' => $solicitud->resultado_ia,
                ]
            );
        }

        if ($solicitud->archivo_acta && Storage::disk('local')->exists($solicitud->archivo_acta)) {
            $dest = 'expediente_fiscal/'.$proveedor->id.'/acta_constitutiva_'.Str::random(8).'.pdf';
            Storage::disk('public')->put($dest, Storage::disk('local')->get($solicitud->archivo_acta));
            DocumentoProveedor::updateOrCreate(
                ['proveedor_id' => $proveedor->id, 'tipo' => 'acta_constitutiva'],
                [
                    'archivo' => $dest,
                    'estatus' => 'aprobado',
                    'notas_revision' => 'Actualizado por solicitud de modificación #'.$solicitud->id,
                    'revisado_at' => now(),
                    'resultado_validacion' => $solicitud->resultado_ia,
                ]
            );
        }
    }

    private function extraerTextoPdf(string $absolutePath): string
    {
        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($absolutePath);

            return trim((string) $pdf->getText());
        } catch (\Throwable $e) {
            Log::warning('[SolicitudModificacion] PDF parse: '.$e->getMessage());

            return '';
        }
    }

    private function validarTextoEsCif(string $texto): array
    {
        if (strlen($texto) < 40) {
            return ['ok' => false, 'mensaje' => 'No se pudo leer la Constancia (PDF vacío o escaneado). Usa un PDF con texto.'];
        }

        $upper = mb_strtoupper(str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú'],
            ['A', 'E', 'I', 'O', 'U'],
            $texto
        ));

        $esCif = str_contains($upper, 'CONSTANCIA')
            || str_contains($upper, 'SITUACION FISCAL')
            || str_contains($upper, 'CEDULA DE IDENTIFICACION FISCAL');

        if (! $esCif) {
            return ['ok' => false, 'mensaje' => 'El archivo no parece una Constancia de Situación Fiscal del SAT.'];
        }

        $rfc = null;
        if (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/u', $upper, $m)) {
            $rfc = $m[1];
        }

        return ['ok' => true, 'rfc' => $rfc, 'mensaje' => 'CIF legible'];
    }

    /** @param  array<string,string>  $textos */
    private function documentoContieneNombre(array $textos, string $propuesto): array
    {
        $normP = $this->normalizarNombre($propuesto);
        if ($normP === '') {
            return ['ok' => false, 'mensaje' => 'Nombre propuesto vacío'];
        }

        $mejor = 0;
        $donde = null;
        foreach ($textos as $etiq => $texto) {
            $normT = $this->normalizarNombre($texto);
            if ($normT === '') {
                continue;
            }
            if (str_contains($normT, $normP) || str_contains($normP, $normT)) {
                return ['ok' => true, 'score' => 100, 'documento' => $etiq];
            }
            similar_text($normP, mb_substr($normT, 0, max(strlen($normP) * 3, 80)), $pct);
            // token overlap
            $tokP = array_values(array_filter(explode(' ', $normP), fn ($t) => strlen($t) > 1));
            $inter = 0;
            foreach ($tokP as $t) {
                if (str_contains($normT, $t)) {
                    $inter++;
                }
            }
            $scoreTok = count($tokP) ? (100 * $inter / count($tokP)) : 0;
            $score = max($pct, $scoreTok);
            if ($score > $mejor) {
                $mejor = $score;
                $donde = $etiq;
            }
        }

        if ($mejor >= 70) {
            return ['ok' => true, 'score' => round($mejor, 1), 'documento' => $donde];
        }

        return [
            'ok' => false,
            'score' => round($mejor, 1),
            'documento' => $donde,
            'mensaje' => 'El nombre propuesto no aparece con suficiente claridad en CIF/Acta (similitud '.$mejor.'%).',
        ];
    }

    private function normalizarNombre(string $s): string
    {
        $s = mb_strtoupper($s);
        $s = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ', '.', ',', ';', '"', "'"],
            ['A', 'E', 'I', 'O', 'U', 'U', 'N', ' ', ' ', ' ', ' ', ' '],
            $s
        );
        $s = preg_replace('/\b(SA|S A|DE|CV|S DE RL|SAPI|SAS|SC|AC)\b/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        return trim($s);
    }
}
