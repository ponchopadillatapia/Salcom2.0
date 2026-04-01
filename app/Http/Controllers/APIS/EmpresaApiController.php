<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

class EmpresaApiController extends Controller
{
    public function validar(Request $request)
    {
        try {
            $request->validate([
                'cif_pdf'             => 'required|mimes:pdf',
                'opinion_pdf'         => 'required|mimes:pdf',
                'acta_pdf'            => 'required|mimes:pdf',
                'rep_legal_pdf'       => 'required|mimes:pdf',
                'contribuyente_pdf'   => 'required|mimes:pdf',
                'caratula_banco_pdf'  => 'required|mimes:pdf',
            ]);

            $parser = new Parser();

            // ── Guardar archivos ──
            $cifRuta           = $request->file('cif_pdf')->store('cif', 'local');
            $opinionRuta       = $request->file('opinion_pdf')->store('opiniones', 'local');
            $actaRuta          = $request->file('acta_pdf')->store('actas', 'local');
            $repLegalRuta      = $request->file('rep_legal_pdf')->store('rep_legal', 'local');
            $contribuyenteRuta = $request->file('contribuyente_pdf')->store('contribuyente', 'local');
            $caratulaRuta      = $request->file('caratula_banco_pdf')->store('caratula_banco', 'local');

            // ── Rutas absolutas ──
            $pathCif           = storage_path('app/private/cif/'            . basename($cifRuta));
            $pathOp            = storage_path('app/private/opiniones/'      . basename($opinionRuta));
            $pathActa          = storage_path('app/private/actas/'          . basename($actaRuta));
            $pathRepLegal      = storage_path('app/private/rep_legal/'      . basename($repLegalRuta));
            $pathContribuyente = storage_path('app/private/contribuyente/'  . basename($contribuyenteRuta));
            $pathCaratula      = storage_path('app/private/caratula_banco/' . basename($caratulaRuta));

            // ──────────────────────────────────────────
            // CIF
            // ──────────────────────────────────────────
            $textoCif = strtoupper($parser->parseFile($pathCif)->getText());

            preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/', $textoCif, $rfcMatch);
            $rfc    = $rfcMatch[1] ?? null;
            preg_match('/NOMBRE.?:\s([A-Z\s]+)/', $textoCif, $nombreMatch);
            $nombre = isset($nombreMatch[1]) ? trim($nombreMatch[1]) : 'DESCONOCIDO';

            // Palabras clave CIF — deben estar TODAS
            $clavesCif = [
                'CONSTANCIA DE SITUACION FISCAL' => 'No es Constancia de Situación Fiscal',
                'SAT'                            => 'No se encontró sello del SAT',
                'RFC'                            => 'No se encontró RFC en el documento',
                'CONSTANCIA'                     => 'El documento no parece una constancia válida',
            ];
            $erroresCif = $this->verificarClaves($textoCif, $clavesCif);
            $esCifValido = empty($erroresCif);

            $rfcValido = $this->validarRFC($rfc);

            // ──────────────────────────────────────────
            // OPINIÓN DE CUMPLIMIENTO
            // ──────────────────────────────────────────
            $textoOp = strtoupper($parser->parseFile($pathOp)->getText());

            // Palabras clave Opinión — deben estar TODAS
            $clavesOp = [
                'CUMPLIMIENTO DE OBLIGACIONES FISCALES' => 'No es documento SAT de cumplimiento',
                'POSITIVA'                              => 'La opinión no es Positiva',
                'OPINION'                               => 'No se encontró sección de Opinión',
                'CUMPLIMIENTO'                          => 'No se encontró sección de Cumplimiento',
            ];
            $erroresOp = $this->verificarClaves($textoOp, $clavesOp);

            // Validación adicional: RFC debe coincidir
            if ($rfc && !str_contains($textoOp, $rfc)) {
                $erroresOp[] = 'RFC no coincide con el CIF';
            }

            // ──────────────────────────────────────────
            // ACTA CONSTITUTIVA
            // ──────────────────────────────────────────
            $textoActa = strtoupper($parser->parseFile($pathActa)->getText());

            // Palabras clave Acta — deben estar TODAS
            $clavesActa = [
                'ACTA'      => 'No se encontró la palabra ACTA en el documento',
                'NOTARIO'   => 'No se encontró firma o referencia de Notario',
                'ESCRITURA' => 'No se encontró número de Escritura',
                'SOCIEDAD'  => 'No se encontró referencia a la Sociedad',
            ];
            $erroresActa = $this->verificarClaves($textoActa, $clavesActa);

            // Validación adicional: RFC debe estar en el acta
            if ($rfc && !str_contains($textoActa, $rfc)) {
                $erroresActa[] = 'RFC no encontrado en el Acta';
            }

            // ──────────────────────────────────────────
            // ID REPRESENTANTE LEGAL
            // ──────────────────────────────────────────
            $textoRepLegal   = strtoupper($parser->parseFile($pathRepLegal)->getText());
            $erroresRepLegal = [];

            if (!str_contains($textoRepLegal, 'INE')    &&
                !str_contains($textoRepLegal, 'IFE')    &&
                !str_contains($textoRepLegal, 'INSTITUTO NACIONAL ELECTORAL'))
                $erroresRepLegal[] = 'No se detectó INE/IFE';
            if (!str_contains($textoRepLegal, 'CURP'))
                $erroresRepLegal[] = 'No se encontró CURP';
            if (!str_contains($textoRepLegal, 'NOMBRE') &&
                !str_contains($textoRepLegal, 'APELLIDO'))
                $erroresRepLegal[] = 'No se encontró nombre del representante';

            // ──────────────────────────────────────────
            // ID CONTRIBUYENTE
            // ──────────────────────────────────────────
            $textoContribuyente   = strtoupper($parser->parseFile($pathContribuyente)->getText());
            $erroresContribuyente = [];

            if (!str_contains($textoContribuyente, 'INE')    &&
                !str_contains($textoContribuyente, 'IFE')    &&
                !str_contains($textoContribuyente, 'INSTITUTO NACIONAL ELECTORAL'))
                $erroresContribuyente[] = 'No se detectó INE/IFE';
            if (!str_contains($textoContribuyente, 'CURP'))
                $erroresContribuyente[] = 'No se encontró CURP';
            if (!str_contains($textoContribuyente, 'NOMBRE') &&
                !str_contains($textoContribuyente, 'APELLIDO'))
                $erroresContribuyente[] = 'No se encontró nombre del contribuyente';

            // ──────────────────────────────────────────
            // CARÁTULA DE BANCO
            // ──────────────────────────────────────────
            $textoCaratula   = strtoupper($parser->parseFile($pathCaratula)->getText());
            $erroresCaratula = [];

            if (!str_contains($textoCaratula, 'BANCO')     &&
                !str_contains($textoCaratula, 'BANK')      &&
                !str_contains($textoCaratula, 'BANCOMER')  &&
                !str_contains($textoCaratula, 'BANAMEX')   &&
                !str_contains($textoCaratula, 'SANTANDER') &&
                !str_contains($textoCaratula, 'HSBC')      &&
                !str_contains($textoCaratula, 'BANORTE'))
                $erroresCaratula[] = 'No se detectó institución bancaria';
            if (!str_contains($textoCaratula, 'CLABE'))
                $erroresCaratula[] = 'No se encontró CLABE interbancaria';
            if (!str_contains($textoCaratula, 'TITULAR') &&
                !str_contains($textoCaratula, 'NOMBRE'))
                $erroresCaratula[] = 'No se encontró nombre del titular';

            // ──────────────────────────────────────────
            // SEMÁFORO GLOBAL
            // ──────────────────────────────────────────
            $opinionOk       = empty($erroresOp);
            $actaOk          = empty($erroresActa);
            $repLegalOk      = empty($erroresRepLegal);
            $contribuyenteOk = empty($erroresContribuyente);
            $caratulaOk      = empty($erroresCaratula);

            $todoOk = $esCifValido   && $opinionOk  && $actaOk
                   && $repLegalOk    && $contribuyenteOk && $caratulaOk;

            if ($todoOk) {
                $estado = 'verde';
            } elseif ($opinionOk && $repLegalOk && $caratulaOk) {
                $estado = 'amarillo';
            } else {
                $estado = 'rojo';
            }

            return response()->json([
                'empresa' => [
                    'rfc'        => $rfc,
                    'nombre'     => $nombre,
                    'rfc_valido' => $rfcValido   ? 'válido' : 'inválido',
                    'cif_valido' => $esCifValido ? 'SI'     : 'NO',
                    'estado'     => $estado,
                ],
                'opinion' => [
                    'valida'  => $opinionOk,
                    'errores' => $erroresOp,
                ],
                'acta' => [
                    'valida'  => $actaOk,
                    'errores' => $erroresActa,
                ],
                'rep_legal' => [
                    'valida'  => $repLegalOk,
                    'errores' => $erroresRepLegal,
                ],
                'contribuyente' => [
                    'valida'  => $contribuyenteOk,
                    'errores' => $erroresContribuyente,
                ],
                'caratula_banco' => [
                    'valida'  => $caratulaOk,
                    'errores' => $erroresCaratula,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errores = collect($e->errors())->flatten()->implode(' | ');
            return response()->json(['mensaje' => 'Archivo inválido: ' . $errores], 422);

        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────
    // Verifica que TODAS las claves estén en el texto
    // Devuelve array de errores (vacío = documento válido)
    // ──────────────────────────────────────────
    private function verificarClaves(string $texto, array $claves): array
    {
        $errores = [];
        foreach ($claves as $clave => $mensajeError) {
            if (!str_contains($texto, strtoupper($clave))) {
                $errores[] = $mensajeError;
            }
        }
        return $errores;
    }

    private function validarRFC($rfc): bool
    {
        if (!$rfc) return false;
        return (bool) preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc);
    }
}