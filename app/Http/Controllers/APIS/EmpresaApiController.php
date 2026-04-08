<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;

class EmpresaApiController extends Controller
{
    public function validar(Request $request)
    {
        try {
            $request->validate([
                'cif_pdf'            => 'required|mimes:pdf|max:10240',
                'opinion_pdf'        => 'required|mimes:pdf|max:10240',
                'acta_pdf'           => 'required|mimes:pdf|max:10240',
                'rep_legal_pdf'      => 'required|mimes:pdf|max:10240',
                'contribuyente_pdf'  => 'required|mimes:pdf|max:10240',
                'caratula_banco_pdf' => 'required|mimes:pdf|max:10240',
            ]);

            $parser = new Parser();

            // ── Guardar y obtener rutas ──
            $archivos = [
                'cif'            => $request->file('cif_pdf')->store('cif', 'local'),
                'opinion'        => $request->file('opinion_pdf')->store('opiniones', 'local'),
                'acta'           => $request->file('acta_pdf')->store('actas', 'local'),
                'rep_legal'      => $request->file('rep_legal_pdf')->store('rep_legal', 'local'),
                'contribuyente'  => $request->file('contribuyente_pdf')->store('contribuyente', 'local'),
                'caratula_banco' => $request->file('caratula_banco_pdf')->store('caratula_banco', 'local'),
            ];

            $carpetas = [
                'cif'            => 'cif',
                'opinion'        => 'opiniones',
                'acta'           => 'actas',
                'rep_legal'      => 'rep_legal',
                'contribuyente'  => 'contribuyente',
                'caratula_banco' => 'caratula_banco',
            ];

            $textos = [];
            foreach ($archivos as $clave => $ruta) {
                $path = storage_path('app/private/' . $carpetas[$clave] . '/' . basename($ruta));
                try {
                    $texto = $parser->parseFile($path)->getText();
                    $textos[$clave] = strtoupper($this->normalizarTexto($texto));
                } catch (\Exception $e) {
                    $textos[$clave] = '';
                }
            }

            // ──────────────────────────────────────────
            // CIF — Constancia de Situación Fiscal
            // Documento real del SAT contiene estas frases
            // ──────────────────────────────────────────
            $textoCif   = $textos['cif'];
            $erroresCif = [];
            $escaneadoCif = strlen($textoCif) < 100;

            if ($escaneadoCif) {
                $erroresCif[] = '⚠ PDF escaneado — no se puede leer el texto automáticamente';
            } else {
                // Frases reales que aparecen en el CIF del SAT
                $clavesCif = [
                    'CONSTANCIA DE SITUACION FISCAL'         => 'No es una Constancia de Situación Fiscal del SAT',
                    'SERVICIO DE ADMINISTRACION TRIBUTARIA'  => 'No tiene sello del SAT (Servicio de Administración Tributaria)',
                    'REGIMEN'                                => 'No se encontró el Régimen Fiscal',
                    'DOMICILIO FISCAL'                       => 'No se encontró Domicilio Fiscal',
                    'RFC'                                    => 'No se encontró RFC en el documento',
                ];
                $erroresCif = $this->verificarClaves($textoCif, $clavesCif);
            }

            // Extraer RFC y nombre del CIF
            preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/u', $textoCif, $rfcMatch);
            $rfc = $rfcMatch[1] ?? null;

            // Detectar tipo de persona
            $esMoral = str_contains($textoCif, 'PERSONA MORAL') ||
                       str_contains($textoCif, 'SOCIEDAD') ||
                       str_contains($textoCif, 'S.A') ||
                       str_contains($textoCif, 'S DE RL') ||
                       str_contains($textoCif, 'S.A.S');

            // Extraer nombre — Persona Moral tiene RAZON SOCIAL, Física tiene NOMBRE
            if ($esMoral) {
                preg_match('/(?:RAZON SOCIAL|DENOMINACION)[:\s]*([A-ZÁÉÍÓÚÑ&\s,\.]+)/u', $textoCif, $nombreMatch);
            } else {
                preg_match('/(?:NOMBRE|CONTRIBUYENTE)[:\s]*([A-ZÁÉÍÓÚÑ\s]+)/u', $textoCif, $nombreMatch);
            }
            $nombre = isset($nombreMatch[1]) ? trim($nombreMatch[1]) : 'DESCONOCIDO';

            $esCifValido = empty($erroresCif);
            $rfcValido   = $this->validarRFC($rfc);

            // ──────────────────────────────────────────
            // OPINIÓN DE CUMPLIMIENTO — Art. 32-D SAT
            // ──────────────────────────────────────────
            $textoOp   = $textos['opinion'];
            $erroresOp = [];
            $escaneadoOp = strlen($textoOp) < 100;

            if ($escaneadoOp) {
                $erroresOp[] = '⚠ PDF escaneado — no se puede leer el texto automáticamente';
            } else {
                // Frases reales de la Opinión del SAT
                $clavesOp = [
                    'OPINION DE CUMPLIMIENTO'                => 'No es una Opinión de Cumplimiento del SAT',
                    'SERVICIO DE ADMINISTRACION TRIBUTARIA'  => 'No tiene sello oficial del SAT',
                    'POSITIVA'                               => 'La opinión NO es Positiva — el proveedor tiene adeudos fiscales',
                    'ARTICULO 32-D'                          => 'No corresponde al Art. 32-D del CFF requerido',
                ];
                $erroresOp = $this->verificarClaves($textoOp, $clavesOp);

                // El RFC del proveedor debe aparecer en la opinión
                if ($rfc && !str_contains($textoOp, $rfc)) {
                    $erroresOp[] = 'El RFC ' . $rfc . ' no coincide con el del CIF';
                }

                // Validar que sea del mes en curso
                $mesActual = strtoupper($this->mesEnEspanol(date('n')));
                $anioActual = date('Y');
                if (!str_contains($textoOp, $mesActual) || !str_contains($textoOp, $anioActual)) {
                    $erroresOp[] = 'La opinión no corresponde al mes en curso (' . $mesActual . ' ' . $anioActual . ')';
                }
            }

            // ──────────────────────────────────────────
            // ACTA CONSTITUTIVA
            // Solo aplica a Persona Moral
            // ──────────────────────────────────────────
            $textoActa   = $textos['acta'];
            $erroresActa = [];
            $escaneadoActa = strlen($textoActa) < 100;

            if ($escaneadoActa) {
                $erroresActa[] = '⚠ PDF escaneado — no se puede leer el texto automáticamente';
            } else {
                $clavesActa = [
                    'ESCRITURA'  => 'No se encontró número de Escritura Pública',
                    'NOTARIO'    => 'No se encontró referencia al Notario Público',
                    'SOCIEDAD'   => 'No se encontró el tipo de Sociedad',
                    'CONSTITUCI' => 'No se encontró cláusula de Constitución',
                ];

                // Solo Persona Moral tiene Acta Constitutiva
                if (!$esMoral) {
                    $erroresActa[] = 'El CIF indica Persona Física — no requiere Acta Constitutiva';
                } else {
                    $erroresActa = $this->verificarClaves($textoActa, $clavesActa);
                }
            }

            // ──────────────────────────────────────────
            // ID REPRESENTANTE LEGAL
            // ──────────────────────────────────────────
            $textoRepLegal   = $textos['rep_legal'];
            $erroresRepLegal = [];
            $escaneadoRep    = strlen($textoRepLegal) < 100;

            if ($escaneadoRep) {
                // PDF escaneado: solo advertencia, no bloquear
                $erroresRepLegal[] = '⚠ PDF escaneado — verificación manual requerida';
            } else {
                // INE tiene varias formas en el PDF
                $tieneIne = str_contains($textoRepLegal, 'INSTITUTO NACIONAL ELECTORAL') ||
                            str_contains($textoRepLegal, 'INE') ||
                            str_contains($textoRepLegal, 'IFE') ||
                            str_contains($textoRepLegal, 'CREDENCIAL PARA VOTAR');

                if (!$tieneIne)
                    $erroresRepLegal[] = 'No se detectó INE/IFE válido';

                if (!str_contains($textoRepLegal, 'CURP'))
                    $erroresRepLegal[] = 'No se encontró CURP';

                if (!str_contains($textoRepLegal, 'NOMBRE') &&
                    !str_contains($textoRepLegal, 'APELLIDO PATERNO'))
                    $erroresRepLegal[] = 'No se encontró nombre del representante';

                // Vigencia — el INE debe tener año de vigencia >= año actual
                preg_match('/VIGENCIA[:\s]*(\d{4})/u', $textoRepLegal, $vigMatch);
                if (isset($vigMatch[1]) && (int)$vigMatch[1] < (int)date('Y')) {
                    $erroresRepLegal[] = 'La INE está vencida (vigencia: ' . $vigMatch[1] . ')';
                }
            }

            // ──────────────────────────────────────────
            // ID CONTRIBUYENTE
            // ──────────────────────────────────────────
            $textoContribuyente   = $textos['contribuyente'];
            $erroresContribuyente = [];
            $escaneadoContrib     = strlen($textoContribuyente) < 100;

            if ($escaneadoContrib) {
                $erroresContribuyente[] = '⚠ PDF escaneado — verificación manual requerida';
            } else {
                $tieneIneContrib = str_contains($textoContribuyente, 'INSTITUTO NACIONAL ELECTORAL') ||
                                   str_contains($textoContribuyente, 'INE') ||
                                   str_contains($textoContribuyente, 'IFE') ||
                                   str_contains($textoContribuyente, 'CREDENCIAL PARA VOTAR');

                if (!$tieneIneContrib)
                    $erroresContribuyente[] = 'No se detectó INE/IFE válido';

                if (!str_contains($textoContribuyente, 'CURP'))
                    $erroresContribuyente[] = 'No se encontró CURP';

                if (!str_contains($textoContribuyente, 'NOMBRE') &&
                    !str_contains($textoContribuyente, 'APELLIDO PATERNO'))
                    $erroresContribuyente[] = 'No se encontró nombre del contribuyente';

                preg_match('/VIGENCIA[:\s]*(\d{4})/u', $textoContribuyente, $vigContribMatch);
                if (isset($vigContribMatch[1]) && (int)$vigContribMatch[1] < (int)date('Y')) {
                    $erroresContribuyente[] = 'La INE está vencida (vigencia: ' . $vigContribMatch[1] . ')';
                }
            }

            // ──────────────────────────────────────────
            // CARÁTULA DE BANCO
            // ──────────────────────────────────────────
            $textoCaratula   = $textos['caratula_banco'];
            $erroresCaratula = [];
            $escaneadoBanco  = strlen($textoCaratula) < 100;

            if ($escaneadoBanco) {
                $erroresCaratula[] = '⚠ PDF escaneado — verificación manual requerida';
            } else {
                // Bancos mexicanos más comunes
                $bancosMX = [
                    'BBVA', 'BANCOMER', 'BANAMEX', 'CITIBANAMEX', 'SANTANDER',
                    'BANORTE', 'HSBC', 'SCOTIABANK', 'INBURSA', 'BAJIO',
                    'AFIRME', 'MIFEL', 'BANCO', 'BANK',
                ];
                $tienebanco = false;
                foreach ($bancosMX as $b) {
                    if (str_contains($textoCaratula, $b)) { $tienebanco = true; break; }
                }
                if (!$tienebanco)
                    $erroresCaratula[] = 'No se detectó institución bancaria reconocida';

                // CLABE debe tener 18 dígitos
                preg_match('/CLABE[:\s\w]*(\d{18})/u', $textoCaratula, $clabeMatch);
                if (!isset($clabeMatch[1])) {
                    if (!str_contains($textoCaratula, 'CLABE'))
                        $erroresCaratula[] = 'No se encontró CLABE interbancaria (18 dígitos)';
                    else
                        $erroresCaratula[] = 'CLABE encontrada pero no tiene 18 dígitos';
                }

                if (!str_contains($textoCaratula, 'TITULAR') &&
                    !str_contains($textoCaratula, 'NOMBRE')   &&
                    !str_contains($textoCaratula, 'CUENTA'))
                    $erroresCaratula[] = 'No se encontró nombre del titular de la cuenta';
            }

            // ──────────────────────────────────────────
            // SEMÁFORO GLOBAL
            // ──────────────────────────────────────────
            $opinionOk       = empty($erroresOp);
            $actaOk          = empty($erroresActa);
            $repLegalOk      = empty($erroresRepLegal);
            $contribuyenteOk = empty($erroresContribuyente);
            $caratulaOk      = empty($erroresCaratula);

            $todoOk = $esCifValido && $opinionOk  && $actaOk
                   && $repLegalOk  && $contribuyenteOk && $caratulaOk;

            if ($todoOk) {
                $estado = 'verde';
            } elseif ($esCifValido && $opinionOk) {
                $estado = 'amarillo'; // Lo fiscal está bien, falta algo en identidad/banco
            } else {
                $estado = 'rojo';
            }

            return response()->json([
                'empresa' => [
                    'rfc'         => $rfc ?? 'No detectado',
                    'nombre'      => $nombre,
                    'tipo'        => $esMoral ? 'Persona Moral' : 'Persona Física',
                    'rfc_valido'  => $rfcValido   ? 'válido'  : 'inválido',
                    'cif_valido'  => $esCifValido ? 'SI'      : 'NO',
                    'estado'      => $estado,
                    'errores_cif' => $erroresCif,
                ],
                'opinion' => [
                    'valida'     => $opinionOk,
                    'escaneado'  => $escaneadoOp,
                    'errores'    => $erroresOp,
                ],
                'acta' => [
                    'valida'     => $actaOk,
                    'escaneado'  => $escaneadoActa,
                    'errores'    => $erroresActa,
                ],
                'rep_legal' => [
                    'valida'     => $repLegalOk,
                    'escaneado'  => $escaneadoRep,
                    'errores'    => $erroresRepLegal,
                ],
                'contribuyente' => [
                    'valida'     => $contribuyenteOk,
                    'escaneado'  => $escaneadoContrib,
                    'errores'    => $erroresContribuyente,
                ],
                'caratula_banco' => [
                    'valida'     => $caratulaOk,
                    'escaneado'  => $escaneadoBanco,
                    'errores'    => $erroresCaratula,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errores = collect($e->errors())->flatten()->implode(' | ');
            return response()->json(['mensaje' => 'Archivo inválido: ' . $errores], 422);

        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ── Normaliza texto: quita acentos y caracteres raros ──
    private function normalizarTexto(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $texto = preg_replace('/[^\x20-\x7E\n]/', ' ', $texto);
        return preg_replace('/\s+/', ' ', $texto);
    }

    // ── Verifica que TODAS las claves estén en el texto ──
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

    // ── Valida formato RFC mexicano (Moral y Física) ──
    private function validarRFC($rfc): bool
    {
        if (!$rfc) return false;
        // Persona Moral: 3 letras + 6 dígitos + 3 alfanum
        // Persona Física: 4 letras + 6 dígitos + 3 alfanum
        return (bool) preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc);
    }

    // ── Convierte número de mes a nombre en español ──
    private function mesEnEspanol(int $mes): string
    {
        $meses = [
            1  => 'ENERO',    2  => 'FEBRERO',   3  => 'MARZO',
            4  => 'ABRIL',    5  => 'MAYO',       6  => 'JUNIO',
            7  => 'JULIO',    8  => 'AGOSTO',     9  => 'SEPTIEMBRE',
            10 => 'OCTUBRE',  11 => 'NOVIEMBRE',  12 => 'DICIEMBRE',
        ];
        return $meses[$mes] ?? '';
    }
}