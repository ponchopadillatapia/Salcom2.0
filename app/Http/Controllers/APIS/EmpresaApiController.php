<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class EmpresaApiController extends Controller
{
    public function validar(Request $request)
    {
        try {
            $tipoPersona = $request->input('tipo_persona', 'moral'); // moral | fisica

            // Reglas de validación dinámicas
            $rules = [
                'cif_pdf' => 'required|mimes:pdf|max:10240',
                'opinion_pdf' => 'required|mimes:pdf|max:10240',
                'caratula_banco_pdf' => 'required|mimes:pdf|max:10240',
                'rep_legal_pdf' => 'nullable|mimes:pdf|max:10240',
                'contribuyente_pdf' => 'nullable|mimes:pdf|max:10240',
            ];

            // Acta constitutiva solo requerida para Persona Moral
            if ($tipoPersona === 'moral') {
                $rules['acta_pdf'] = 'required|mimes:pdf|max:10240';
            } else {
                $rules['acta_pdf'] = 'nullable|mimes:pdf|max:10240';
            }

            $request->validate($rules);

            $parser = new Parser;

            $archivos = [
                'cif' => $request->file('cif_pdf')->store('cif', 'local'),
                'opinion' => $request->file('opinion_pdf')->store('opiniones', 'local'),
                'caratula_banco' => $request->file('caratula_banco_pdf')->store('caratula_banco', 'local'),
            ];

            // Acta: solo si se subió
            if ($request->hasFile('acta_pdf')) {
                $archivos['acta'] = $request->file('acta_pdf')->store('actas', 'local');
            }
            // Opcionales
            if ($request->hasFile('rep_legal_pdf')) {
                $archivos['rep_legal'] = $request->file('rep_legal_pdf')->store('rep_legal', 'local');
            }
            if ($request->hasFile('contribuyente_pdf')) {
                $archivos['contribuyente'] = $request->file('contribuyente_pdf')->store('contribuyente', 'local');
            }

            $textos = [];
            foreach ($archivos as $clave => $ruta) {
                $textos[$clave] = $this->extraerTexto($parser, storage_path('app/private/'.$ruta));
            }

            // ════════════════════════════════════════
            // CIF — Constancia de Situación Fiscal
            // ════════════════════════════════════════
            $cif = $this->validarCIF($textos['cif']);

            // ════════════════════════════════════════
            // OPINIÓN DE CUMPLIMIENTO
            // ════════════════════════════════════════
            $opinion = $this->validarOpinion($textos['opinion'], $cif['datos']['rfc']);

            // ════════════════════════════════════════
            // ACTA CONSTITUTIVA (solo Persona Moral)
            // ════════════════════════════════════════
            $acta = null;
            if (isset($textos['acta'])) {
                $acta = $this->validarActa($textos['acta'], $cif['datos']['es_moral']);
            } elseif ($tipoPersona === 'fisica') {
                $acta = ['valida' => true, 'datos' => [], 'errores' => [], 'hallazgos' => ['Persona Física — Acta Constitutiva no requerida']];
            }

            // ════════════════════════════════════════
            // ID REPRESENTANTE LEGAL (opcional)
            // ════════════════════════════════════════
            $repLegal = null;
            if (isset($textos['rep_legal'])) {
                $repLegal = $this->validarINE($textos['rep_legal'], 'Representante Legal');
            }

            // ════════════════════════════════════════
            // ID CONTRIBUYENTE (opcional)
            // ════════════════════════════════════════
            $contribuyente = null;
            if (isset($textos['contribuyente'])) {
                $contribuyente = $this->validarINE($textos['contribuyente'], 'Contribuyente');
            }

            // ════════════════════════════════════════
            // CARÁTULA DE BANCO
            // ════════════════════════════════════════
            $banco = $this->validarCaratulaBanco($textos['caratula_banco']);

            // ════════════════════════════════════════
            // CRUCE: Si el CIF no pudo extraer la razón social, intentar desde la carátula de banco
            // ════════════════════════════════════════
            if (empty($cif['datos']['nombre']) && !empty($banco['datos']['titular'])) {
                $titular = $banco['datos']['titular'];
                // Limpiar texto del titular
                $corteTitular = ['DOMICILIO', 'TEPAME', 'PARAISOS', 'ZAPOPAN', 'JAL', 'MEXICO', 'CP'];
                foreach ($corteTitular as $pc) {
                    $pos = strpos($titular, $pc);
                    if ($pos !== false) {
                        $titular = trim(substr($titular, 0, $pos));
                    }
                }
                if (strlen($titular) > 5) {
                    $cif['datos']['nombre'] = $titular;
                    // Reemplazar el error por hallazgo
                    $cif['errores'] = array_values(array_filter($cif['errores'], fn($e) => !str_contains($e, 'Razón Social') && !str_contains($e, 'nombre del contribuyente')));
                    $cif['hallazgos'][] = 'Razón Social (de carátula bancaria): ' . $titular;
                    $cif['valida'] = empty($cif['errores']);
                }
            }

            // También intentar extraer el nombre de la primera línea del texto del CIF si aún no lo tiene
            if (empty($cif['datos']['nombre']) && !empty($textos['cif'])) {
                // Buscar patrones tipo "VERTICALE PLATAFORMAS Y ASCENSORES SAS DE CV"
                if (preg_match('/([A-ZÁÉÍÓÚÑ&]+(?:\s+[A-ZÁÉÍÓÚÑ&Y]+){2,}(?:\s+(?:S\.?A\.?S?|S\.?A\.?\s*DE\s*C\.?V\.?|S\s*DE\s*R\.?L\.?)))/u', $textos['cif'], $rsMatch)) {
                    $cif['datos']['nombre'] = trim($rsMatch[1]);
                    $cif['errores'] = array_values(array_filter($cif['errores'], fn($e) => !str_contains($e, 'Razón Social') && !str_contains($e, 'nombre del contribuyente')));
                    $cif['hallazgos'][] = 'Razón Social: ' . trim($rsMatch[1]);
                    $cif['valida'] = empty($cif['errores']);
                }
            }

            // ════════════════════════════════════════
            // CRUCE CON FORMULARIO DE IDENTIFICACIÓN
            // ════════════════════════════════════════
            $nombreEsperado = trim((string) $request->input('nombre_esperado', ''));
            $clabeEsperada = preg_replace('/\D/', '', (string) $request->input('clabe_esperada', ''));
            $cuentaEsperada = preg_replace('/\D/', '', (string) $request->input('cuenta_esperada', ''));
            $bancoEsperado = trim((string) $request->input('banco_esperado', ''));
            $cpEsperado = preg_replace('/\D/', '', (string) $request->input('cp_esperado', ''));

            if ($nombreEsperado !== '' || $clabeEsperada !== '' || $cpEsperado !== '' || $bancoEsperado !== '') {
                // Tipo de persona declarado vs CIF
                $cifEsMoral = (bool) ($cif['datos']['es_moral'] ?? false);
                $declaroMoral = $tipoPersona === 'moral';
                if ($cifEsMoral !== $declaroMoral) {
                    $esperadoLabel = $declaroMoral ? 'Persona Moral' : 'Persona Física';
                    $encontradoLabel = $cifEsMoral ? 'Persona Moral' : 'Persona Física';
                    $cif['errores'][] = "El tipo de persona del formulario ({$esperadoLabel}) no coincide con el CIF ({$encontradoLabel})";
                    $cif['valida'] = false;
                } else {
                    $cif['hallazgos'][] = 'Tipo de persona coincide con el formulario de identificación';
                }

                // Nombre / razón social
                if ($nombreEsperado !== '') {
                    $nombreDoc = (string) ($cif['datos']['nombre'] ?? '');
                    if ($nombreDoc === '') {
                        $cif['errores'][] = 'No se pudo verificar el nombre del formulario contra el CIF (nombre no detectado en el documento)';
                        $cif['valida'] = false;
                    } elseif ($this->nombresCoinciden($nombreEsperado, $nombreDoc)) {
                        $cif['hallazgos'][] = 'Nombre/Razón Social coincide con el formulario de identificación';
                    } else {
                        $etiqueta = $declaroMoral ? 'Razón Social' : 'Nombre';
                        $cif['errores'][] = "{$etiqueta} del formulario (\"{$nombreEsperado}\") no coincide con el CIF (\"{$nombreDoc}\")";
                        $cif['valida'] = false;
                    }
                }

                // Código postal
                if ($cpEsperado !== '' && strlen($cpEsperado) === 5) {
                    $cpDoc = (string) ($cif['datos']['codigo_postal'] ?? '');
                    if ($cpDoc !== '' && $cpDoc !== $cpEsperado) {
                        $cif['errores'][] = "C.P. del formulario ({$cpEsperado}) no coincide con el CIF ({$cpDoc})";
                        $cif['valida'] = false;
                    } elseif ($cpDoc === $cpEsperado) {
                        $cif['hallazgos'][] = 'C.P. coincide con el formulario de identificación';
                    }
                }

                // CLABE
                if ($clabeEsperada !== '' && strlen($clabeEsperada) === 18) {
                    $clabeDoc = (string) ($banco['datos']['clabe'] ?? '');
                    if ($clabeDoc === '') {
                        $banco['errores'][] = 'No se pudo verificar la CLABE del formulario (no detectada en la carátula)';
                        $banco['valida'] = false;
                    } elseif ($clabeDoc !== $clabeEsperada) {
                        $banco['errores'][] = "CLABE del formulario ({$clabeEsperada}) no coincide con la carátula ({$clabeDoc})";
                        $banco['valida'] = false;
                    } else {
                        $banco['hallazgos'][] = 'CLABE coincide con el formulario de identificación';
                    }
                }

                // Cuenta bancaria
                if ($cuentaEsperada !== '') {
                    $cuentaDoc = (string) ($banco['datos']['cuenta'] ?? '');
                    if ($cuentaDoc !== '' && ! str_ends_with($cuentaDoc, $cuentaEsperada) && ! str_ends_with($cuentaEsperada, $cuentaDoc) && $cuentaDoc !== $cuentaEsperada) {
                        $banco['errores'][] = "Cuenta del formulario ({$cuentaEsperada}) no coincide con la carátula ({$cuentaDoc})";
                        $banco['valida'] = false;
                    } elseif ($cuentaDoc !== '' && ($cuentaDoc === $cuentaEsperada || str_ends_with($cuentaDoc, $cuentaEsperada) || str_ends_with($cuentaEsperada, $cuentaDoc))) {
                        $banco['hallazgos'][] = 'Número de cuenta coincide con el formulario de identificación';
                    }
                }

                // Banco
                if ($bancoEsperado !== '') {
                    $bancoDoc = (string) ($banco['datos']['banco'] ?? '');
                    if ($bancoDoc !== '') {
                        $bancoEspNorm = $this->normalizarNombre($bancoEsperado);
                        $bancoDocNorm = $this->normalizarNombre($bancoDoc);
                        if ($bancoEspNorm === $bancoDocNorm || str_contains($bancoEspNorm, $bancoDocNorm) || str_contains($bancoDocNorm, $bancoEspNorm)) {
                            $banco['hallazgos'][] = 'Institución bancaria coincide con el formulario de identificación';
                        } else {
                            $banco['errores'][] = "Banco del formulario (\"{$bancoEsperado}\") no coincide con la carátula (\"{$bancoDoc}\")";
                            $banco['valida'] = false;
                        }
                    }
                }
            }

            // ════════════════════════════════════════
            // SEMÁFORO
            // ════════════════════════════════════════
            $cifOk = $cif['valida'];
            $opOk = $opinion['valida'];
            $actaOk = $acta ? $acta['valida'] : true;
            $repOk = $repLegal ? $repLegal['valida'] : true;
            $contOk = $contribuyente ? $contribuyente['valida'] : true;
            $bancoOk = $banco['valida'];

            $todoOk = $cifOk && $opOk && $actaOk && $repOk && $contOk && $bancoOk;

            if ($todoOk) {
                $estado = 'verde';
            } elseif ($cifOk && $opOk) {
                $estado = 'amarillo';
            } else {
                $estado = 'rojo';
            }

            $response = [
                'estado' => $estado,
                'tipo_persona' => $tipoPersona,
                'cif' => $cif,
                'opinion' => $opinion,
                'caratula_banco' => $banco,
            ];

            if ($acta) {
                $response['acta'] = $acta;
            }
            if ($repLegal) {
                $response['rep_legal'] = $repLegal;
            }
            if ($contribuyente) {
                $response['contribuyente'] = $contribuyente;
            }

            return response()->json($response);

        } catch (ValidationException $e) {
            $errores = collect($e->errors())->flatten()->implode(' | ');

            return response()->json(['mensaje' => 'Archivo inválido — solo se aceptan documentos PDF: '.$errores], 422);
        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error interno: '.$e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────
    // VALIDADORES POR DOCUMENTO
    // ──────────────────────────────────────────────────

    private function validarCIF(string $texto): array
    {
        $datos = [
            'rfc' => null,
            'nombre' => null,
            'fecha_nacimiento' => null,
            'tipo_persona' => null,
            'es_moral' => false,
            'regimen' => null,
            'domicilio_fiscal' => null,
            'codigo_postal' => null,
            'fecha_inicio' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = []; // lo que SÍ encontró

        if (strlen($texto) < 20) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Normalizar texto para mejorar detección OCR
        $textoNorm = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú'],
            ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u'],
            $texto
        );
        $textoUpper = strtoupper($textoNorm);

        // ¿Es realmente un CIF?
        if (str_contains($textoUpper, 'CONSTANCIA DE SITUACION FISCAL')
            || str_contains($textoUpper, 'CONSTANCIA')
            || str_contains($textoUpper, 'SITUACION FISCAL')
            || str_contains($textoUpper, 'CEDULA DE IDENTIFICACION FISCAL')) {
            $hallazgos[] = 'Documento identificado como Constancia de Situación Fiscal';
        } else {
            $errores[] = 'No es una Constancia de Situación Fiscal del SAT';
        }

        // Sello SAT — buscar variaciones del OCR
        if (str_contains($textoUpper, 'SERVICIO DE ADMINISTRACION TRIBUTARIA')
            || str_contains($textoUpper, 'ADMINISTRACION TRIBUTARIA')
            || str_contains($textoUpper, 'SAT')
            || str_contains($textoUpper, 'SHCP')
            || str_contains($textoUpper, 'HACIENDA')) {
            $hallazgos[] = 'Sello del SAT detectado';
        } else {
            // No marcar como error — el OCR puede no leerlo
            $hallazgos[] = 'Sello SAT no detectado por OCR (verificar visualmente)';
        }

        // RFC — buscar con patrones más flexibles
        if (preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/u', $textoUpper, $m)) {
            $datos['rfc'] = $m[1];
            $hallazgos[] = 'RFC encontrado: '.$m[1];
        } elseif (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/u', $textoUpper, $m)) {
            $datos['rfc'] = $m[1];
            $hallazgos[] = 'RFC encontrado: '.$m[1];
        } else {
            $errores[] = 'No se encontró RFC';
        }

        // Tipo persona
        $esMoral = str_contains($texto, 'PERSONA MORAL')
                || str_contains($texto, 'SOCIEDAD')
                || str_contains($texto, 'S.A')
                || str_contains($texto, 'S DE RL')
                || str_contains($texto, 'S.A.S');
        $datos['es_moral'] = $esMoral;
        $datos['tipo_persona'] = $esMoral ? 'Persona Moral' : 'Persona Física';
        $hallazgos[] = 'Tipo: '.$datos['tipo_persona'];

        // Nombre / Razón social
        if ($esMoral) {
            // Buscar razón social — después de la etiqueta, capturar el valor real
            $nombreRaw = '';
            // Intentar varios patrones
            if (preg_match('/DENOMINACION\s*(?:\/\s*)?(?:RAZON\s*SOCIAL)?[:\s]+(.+?)(?=REGIMEN|DOMICILIO|FECHA|TIPO|CODIGO|OBLIGACIONES|$)/ui', $texto, $nm)) {
                $nombreRaw = trim($nm[1]);
            } elseif (preg_match('/RAZON\s*SOCIAL[:\s]+(.+?)(?=REGIMEN|DOMICILIO|FECHA|TIPO|CODIGO|OBLIGACIONES|$)/ui', $texto, $nm)) {
                $nombreRaw = trim($nm[1]);
            } elseif (preg_match('/(?:S\.?A\.?\s*(?:DE\s*)?C\.?V\.?|S\.?\s*DE\s*R\.?L|S\.?A\.?S)/u', $texto, $nm, PREG_OFFSET_CAPTURE)) {
                // Buscar hacia atrás desde el tipo de sociedad para extraer la razón social
                $posicion = $nm[0][1];
                $fragmento = substr($texto, max(0, $posicion - 100), $posicion - max(0, $posicion - 100));
                if (preg_match('/([A-ZÁÉÍÓÚÑ&\s]{3,})\s*$/u', $fragmento, $preNombre)) {
                    $nombreRaw = trim($preNombre[1]) . ' ' . $nm[0][0];
                }
            }

            // Limpiar: quitar etiquetas que se colaron al inicio
            $nombreRaw = preg_replace('/^(DENOMINACION|RAZON\s*SOCIAL|DENOMINACION\s*\/\s*RAZON\s*SOCIAL)[:\s]*/i', '', $nombreRaw);

            // Separar palabras pegadas (insertar espacio entre minúscula/mayúscula o entre letras pegadas conocidas)
            // Ejemplo: "VERTICALEPLATAFORMAS" -> detectar si hay palabras pegadas
            // Buscar patrón de palabras en mayúsculas sin espacio
            $nombreRaw = preg_replace('/([A-Z])([A-Z]{2,})/', '$1$2', $nombreRaw);

            // Cortar en etiquetas
            $corteCif = ['IDCIF', 'ID CIF', 'TIPO', 'REGIMEN', 'FECHA', 'DOMICILIO', 'CODIGO', 'RFC', 'CURP', 'CLAVE', 'OBLIGACIONES', 'SITUACION', 'CONSTANCIA', 'PERSONA'];
            foreach ($corteCif as $pc) {
                $pos = strpos($nombreRaw, $pc);
                if ($pos !== false && $pos > 0) {
                    $nombreRaw = trim(substr($nombreRaw, 0, $pos));
                }
            }

            // Quitar prefijos basura
            $nombreRaw = preg_replace('/^[O\s:]+/', '', $nombreRaw);
            $nombreRaw = trim($nombreRaw);

            // Agregar espacios entre palabras pegadas si se detecta patrón camelCase en mayúsculas
            // VERTICALEPLATAFORMAS -> VERTICALE PLATAFORMAS (si no hay espacio y hay más de 15 chars)
            if (strlen($nombreRaw) > 15 && !str_contains($nombreRaw, ' ')) {
                // Intentar separar por vocales seguidas de consonantes
                $nombreRaw = preg_replace('/([AEIOU])([BCDFGHJKLMNPQRSTVWXYZ]{2,})/u', '$1 $2', $nombreRaw);
            }

            if (strlen($nombreRaw) > 2) {
                // Filtrar basura del OCR: si no tiene espacios y son menos de 15 chars, probablemente es basura
                if (strlen($nombreRaw) > 5 && (str_contains($nombreRaw, ' ') || strlen($nombreRaw) > 15)) {
                    $datos['nombre'] = $nombreRaw;
                    $hallazgos[] = 'Razón Social: ' . $nombreRaw;
                } else {
                    $datos['nombre'] = null;
                    $errores[] = 'No se pudo extraer la Razón Social del documento';
                }
            } else {
                $datos['nombre'] = null;
                $errores[] = 'No se pudo extraer la Razón Social del documento';
            }
        } else {
            // Persona Física: buscar nombre completo
            $nombreFisico = '';
            if (preg_match('/APELLIDO\s*PATERNO[:\s]*([A-ZÁÉÍÓÚÑ]+)/u', $texto, $ap)) {
                $nombreFisico = trim($ap[1]);
                if (preg_match('/APELLIDO\s*MATERNO[:\s]*([A-ZÁÉÍÓÚÑ]+)/u', $texto, $am)) {
                    $nombreFisico .= ' ' . trim($am[1]);
                }
                if (preg_match('/NOMBRE\s*(?:\(S\))?[:\s]*([A-ZÁÉÍÓÚÑ]+(?:\s+[A-ZÁÉÍÓÚÑ]+)?)/u', $texto, $nms)) {
                    $nombreFisico .= ' ' . trim($nms[1]);
                }
            } elseif (preg_match('/(?:NOMBRE|CONTRIBUYENTE)[:\s]*([A-ZÁÉÍÓÚÑ]+(?:\s+[A-ZÁÉÍÓÚÑ]+){0,4})/u', $texto, $nm)) {
                $nombreFisico = trim($nm[1]);
            }

            // Limpiar
            $corteCif = ['RFC', 'CURP', 'DOMICILIO', 'REGIMEN', 'CODIGO', 'FECHA', 'CLAVE', 'TIPO', 'OBLIGACIONES', 'SITUACION', 'CONSTANCIA'];
            foreach ($corteCif as $pc) {
                $pos = strpos($nombreFisico, $pc);
                if ($pos !== false) {
                    $nombreFisico = trim(substr($nombreFisico, 0, $pos));
                }
            }

            if (strlen($nombreFisico) > 2) {
                if (strlen($nombreFisico) > 5 && (str_contains($nombreFisico, ' ') || strlen($nombreFisico) > 15)) {
                    $datos['nombre'] = $nombreFisico;
                    $hallazgos[] = 'Nombre: ' . $nombreFisico;
                } else {
                    $datos['nombre'] = null;
                    $errores[] = 'No se pudo extraer el nombre del contribuyente';
                }
            } else {
                $datos['nombre'] = null;
                $errores[] = 'No se pudo extraer el nombre del contribuyente';
            }
        }

        // Fecha de nacimiento (solo Persona Física)
        if (! $esMoral) {
            if (preg_match('/FECHA\s*(?:DE\s*)?NACIMIENTO[:\s]*([\d\/\-]+)/', $texto, $fn)) {
                $datos['fecha_nacimiento'] = $fn[1];
                $hallazgos[] = 'Fecha de nacimiento: '.$fn[1];
            } elseif ($datos['rfc'] && strlen($datos['rfc']) === 13) {
                // Extraer fecha del RFC (posiciones 5-10: AAMMDD)
                $rfcFecha = substr($datos['rfc'], 4, 6);
                if (preg_match('/(\d{2})(\d{2})(\d{2})/', $rfcFecha, $rfcF)) {
                    $anio = (int) $rfcF[1] > 50 ? '19'.$rfcF[1] : '20'.$rfcF[1];
                    $datos['fecha_nacimiento'] = $rfcF[3].'/'.$rfcF[2].'/'.$anio;
                    $hallazgos[] = 'Fecha de nacimiento (del RFC): '.$datos['fecha_nacimiento'];
                }
            }
        }

        // Régimen fiscal — buscar con variaciones del OCR
        if (preg_match('/REGIMEN[:\s]*([A-ZÁÉÍÓÚÑ\s,\.]+?)(?=FECHA|DOMICILIO|OBLIGACIONES|CODIGO|\d{2}\/)/u', $texto, $reg)) {
            $regimenRaw = trim($reg[1]);
            // Limpiar etiquetas
            $corteReg = ['FECHA', 'INICIO', 'DOMICILIO', 'OBLIGACIONES', 'CODIGO'];
            foreach ($corteReg as $pc) {
                $pos = strpos($regimenRaw, $pc);
                if ($pos !== false) {
                    $regimenRaw = trim(substr($regimenRaw, 0, $pos));
                }
            }
            if (strlen($regimenRaw) > 3) {
                $datos['regimen'] = $regimenRaw;
                $hallazgos[] = 'Régimen: ' . $regimenRaw;
            } else {
                $hallazgos[] = 'Se detectó mención de Régimen Fiscal';
            }
        } elseif (str_contains($textoUpper, 'REGIMEN') || str_contains($textoUpper, 'REGIMEN FISCAL')
                || str_contains($textoUpper, 'GENERAL DE LEY') || str_contains($textoUpper, 'ACTIVIDADES EMPRESARIALES')
                || str_contains($textoUpper, 'INCORPORACION FISCAL') || str_contains($textoUpper, 'RESICO')
                || str_contains($textoUpper, 'SIMPLIFICADO DE CONFIANZA')) {
            $hallazgos[] = 'Régimen Fiscal detectado';
        } else {
            // No marcar como error — el OCR puede no leerlo correctamente
            $hallazgos[] = 'Régimen Fiscal no detectado por OCR (verificar visualmente)';
        }

        // Domicilio fiscal — buscar con variaciones
        if (str_contains($textoUpper, 'DOMICILIO FISCAL')
            || str_contains($textoUpper, 'DOMICILIO')
            || str_contains($textoUpper, 'CALLE')
            || str_contains($textoUpper, 'COLONIA')
            || str_contains($textoUpper, 'MUNICIPIO')
            || str_contains($textoUpper, 'ENTIDAD')
            || preg_match('/C\.?P\.?\s*\d{5}/', $textoUpper)) {
            $hallazgos[] = 'Domicilio Fiscal detectado';
        } else {
            // No marcar como error
            $hallazgos[] = 'Domicilio Fiscal no detectado por OCR (verificar visualmente)';
        }

        // Código postal
        if (preg_match('/CODIGO POSTAL[:\s]*(\d{5})/', $texto, $cp)) {
            $datos['codigo_postal'] = $cp[1];
            $hallazgos[] = 'C.P.: '.$cp[1];
        } elseif (preg_match('/C\.?P\.?[:\s]*(\d{5})/', $texto, $cp)) {
            $datos['codigo_postal'] = $cp[1];
            $hallazgos[] = 'C.P.: '.$cp[1];
        }

        // Fecha inicio operaciones
        if (preg_match('/FECHA\s*(?:DE\s*)?INICIO\s*(?:DE\s*)?OPERACIONES[:\s]*([\d\/\-]+)/', $texto, $fi)) {
            $datos['fecha_inicio'] = $fi[1];
            $hallazgos[] = 'Inicio operaciones: '.$fi[1];
        }

        // RFC válido formato
        $rfcValido = $this->validarRFC($datos['rfc']);
        if ($datos['rfc'] && ! $rfcValido) {
            $errores[] = 'El RFC "'.$datos['rfc'].'" no tiene formato válido';
        }

        return [
            'valida' => empty($errores),
            'datos' => $datos,
            'errores' => $errores,
            'hallazgos' => $hallazgos,
        ];
    }

    private function validarOpinion(string $texto, ?string $rfcCif): array
    {
        $datos = [
            'rfc_encontrado' => null,
            'sentido' => null,
            'fecha' => null,
            'articulo' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (strlen($texto) < 20) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Normalizar texto para mejorar detección (quitar acentos comunes en OCR)
        $textoNorm = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú'],
            ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u'],
            $texto
        );
        $textoUpper = strtoupper($textoNorm);

        // Identificar documento
        if (str_contains($textoUpper, 'OPINION') && str_contains($textoUpper, 'CUMPLIMIENTO')) {
            $hallazgos[] = 'Documento identificado como Opinión de Cumplimiento';
        } elseif (str_contains($textoUpper, 'OPINION') || str_contains($textoUpper, 'CUMPLIMIENTO') || str_contains($textoUpper, '32-D')) {
            $hallazgos[] = 'Documento identificado como Opinión de Cumplimiento';
        } else {
            $errores[] = 'No parece ser una Opinión de Cumplimiento del SAT';
        }

        // Sello SAT — buscar variaciones del OCR
        if (str_contains($textoUpper, 'SERVICIO DE ADMINISTRACION TRIBUTARIA')
            || str_contains($textoUpper, 'SERVICIO DE ADMINISTRACION')
            || str_contains($textoUpper, 'SAT')
            || str_contains($textoUpper, 'ADMINISTRACION TRIBUTARIA')
            || str_contains($textoUpper, 'SHCP')) {
            $hallazgos[] = 'Sello del SAT detectado';
        } else {
            // No marcar como error — el OCR puede no leerlo
            $hallazgos[] = 'Sello SAT no detectado por OCR (verificar visualmente)';
        }

        // RFC en la opinión — buscar con patrones más flexibles
        $rfcEncontrado = null;
        if (preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/u', $textoUpper, $rfcOp)) {
            $rfcEncontrado = $rfcOp[1];
        } elseif (preg_match('/\b([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})\b/u', $textoUpper, $rfcOp)) {
            $rfcEncontrado = $rfcOp[1];
        } elseif ($rfcCif && str_contains($textoUpper, $rfcCif)) {
            $rfcEncontrado = $rfcCif;
        }

        if ($rfcEncontrado) {
            $datos['rfc_encontrado'] = $rfcEncontrado;
            $hallazgos[] = 'RFC: ' . $rfcEncontrado;
        } else {
            // No marcar como error si el OCR no lo lee
            $hallazgos[] = 'RFC no extraído por OCR (verificar visualmente)';
        }

        // Sentido (POSITIVA / NEGATIVA) — esto es lo más importante
        if (str_contains($textoUpper, 'POSITIV')) {
            $datos['sentido'] = 'POSITIVA';
            $hallazgos[] = 'Opinión: POSITIVA ✓';

            // Verificar mes en curso
            $mesActual = strtoupper($this->mesEnEspanol((int) date('n')));
            $anioActual = date('Y');
            if (str_contains($textoUpper, $mesActual) && str_contains($textoUpper, $anioActual)) {
                $hallazgos[] = 'Corresponde al mes en curso: ' . $mesActual . ' ' . $anioActual;
            } else {
                $errores[] = 'No corresponde al mes en curso (' . $mesActual . ' ' . $anioActual . ')';
            }

            $hallazgos[] = 'Sin observaciones pendientes';

            return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];

        } elseif (str_contains($textoUpper, 'NEGATIV')) {
            $datos['sentido'] = 'NEGATIVA';
            $errores[] = 'La opinión es NEGATIVA';

            // Cuando es negativa, mostrar qué puede estar pendiente
            if (str_contains($textoUpper, 'DECLARACION') || str_contains($textoUpper, 'DECLARACIONES')) {
                $errores[] = 'Posibles declaraciones pendientes de presentar';
            }
            if (str_contains($textoUpper, 'ADEUDO') || str_contains($textoUpper, 'CREDITO FISCAL') || str_contains($textoUpper, 'CREDITOS FISCALES')) {
                $errores[] = 'Se detectan adeudos o créditos fiscales';
            }
            if (str_contains($textoUpper, 'REQUERIMIENTO')) {
                $errores[] = 'Tiene requerimientos pendientes';
            }
            if (str_contains($textoUpper, '69-B') || str_contains($textoUpper, '69B') || str_contains($textoUpper, 'LISTA NEGRA')) {
                $errores[] = 'Posible inclusión en listas negras (Art. 69-B)';
            }

            // Si no se detectó nada específico, indicar revisión manual
            if (count($errores) === 1) {
                $errores[] = 'Revisar manualmente el motivo de la opinión negativa';
            }

        } else {
            $errores[] = 'No se detectó si la opinión es Positiva o Negativa — revisar manualmente';
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    private function validarActa(string $texto, bool $esMoral): array
    {
        $datos = [
            'notario' => null,
            'escritura' => null,
            'tipo_sociedad' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (! $esMoral) {
            $hallazgos[] = 'Persona Física — Acta Constitutiva no requerida';

            return ['valida' => true, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        if (strlen($texto) < 20) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Escritura
        if (preg_match('/ESCRITURA\s*(?:PUBLICA\s*)?(?:NUMERO\s*)?[:\s#]*(\d+)/u', $texto, $esc)) {
            $datos['escritura'] = $esc[1];
            $hallazgos[] = 'Escritura Pública No. '.$esc[1];
        } elseif (str_contains($texto, 'ESCRITURA')) {
            $hallazgos[] = 'Se menciona Escritura Pública';
        }

        // Notario
        if (preg_match('/NOTARIO\s*(?:PUBLICO\s*)?(?:NUMERO\s*)?[:\s#]*(\d+)?/u', $texto, $not)) {
            $datos['notario'] = isset($not[1]) ? 'Notaría #'.$not[1] : 'Sí';
            $hallazgos[] = 'Notario Público: '.($not[1] ?? 'detectado');
        } elseif (str_contains($texto, 'NOTARIO') || str_contains($texto, 'NOTARIA')) {
            $hallazgos[] = 'Se menciona Notario Público';
        } else {
            $errores[] = 'No se encontró referencia al Notario Público';
        }

        // Tipo sociedad
        $sociedades = ['S.A. DE C.V.', 'S.A.S.', 'S. DE R.L.', 'S.A.', 'S.C.', 'A.C.', 'S.A.P.I.'];
        foreach ($sociedades as $s) {
            if (str_contains($texto, str_replace('.', '', str_replace(' ', '', $s)))
                || str_contains($texto, $s)) {
                $datos['tipo_sociedad'] = $s;
                $hallazgos[] = 'Tipo de sociedad: '.$s;
                break;
            }
        }
        if (! $datos['tipo_sociedad']) {
            if (str_contains($texto, 'SOCIEDAD')) {
                $hallazgos[] = 'Se menciona Sociedad';
            } else {
                $errores[] = 'No se encontró tipo de Sociedad';
            }
        }

        // Constitución
        if (str_contains($texto, 'CONSTITUCI')) {
            $hallazgos[] = 'Contiene cláusula de Constitución';
        } else {
            $errores[] = 'No se encontró cláusula de Constitución';
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    private function validarINE(string $texto, string $etiqueta): array
    {
        $datos = [
            'nombre' => null,
            'apellido_paterno' => null,
            'apellido_materno' => null,
            'nombres' => null,
            'fecha_nacimiento' => null,
            'curp' => null,
            'clave_elector' => null,
            'vigencia' => null,
            'seccion' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (strlen($texto) < 20) {
            $hallazgos[] = 'PDF escaneado — se requiere validación con OCR';

            return ['valida' => true, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Detectar INE/IFE
        $esIne = str_contains($texto, 'INSTITUTO NACIONAL ELECTORAL')
              || str_contains($texto, 'INE')
              || str_contains($texto, 'IFE')
              || str_contains($texto, 'CREDENCIAL')
              || str_contains($texto, 'ELECTORAL');

        if ($esIne) {
            $hallazgos[] = 'Documento identificado como INE/IFE';
        } else {
            $errores[] = 'No se detectó que sea una INE/IFE de '.$etiqueta;
        }

        // CURP
        if (preg_match('/([A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d)/', $texto, $curpM)) {
            $datos['curp'] = $curpM[1];
            $hallazgos[] = 'CURP: '.$curpM[1];
        } elseif (str_contains($texto, 'CURP')) {
            $hallazgos[] = 'Se menciona CURP (no se pudo extraer el valor)';
        }

        // Nombre completo — buscar apellidos y nombre por separado
        $nombreCompleto = '';

        // Método 1: Buscar por etiquetas separadas
        if (preg_match('/APELLIDO\s*PATERNO[:\s]*([A-ZÁÉÍÓÚÑ]+)/u', $texto, $apP)) {
            $datos['apellido_paterno'] = trim($apP[1]);
        }
        if (preg_match('/APELLIDO\s*MATERNO[:\s]*([A-ZÁÉÍÓÚÑ]+)/u', $texto, $apM)) {
            $datos['apellido_materno'] = trim($apM[1]);
        }
        if (preg_match('/NOMBRE\s*(?:\(S\))?[:\s]*([A-ZÁÉÍÓÚÑ]+(?:\s+[A-ZÁÉÍÓÚÑ]+)?)/u', $texto, $nms)) {
            $datos['nombres'] = trim($nms[1]);
        }

        // Método 2: Si Textract devuelve líneas sueltas, buscar patrones de nombre
        if (!$datos['apellido_paterno'] && !$datos['nombres']) {
            // Buscar 2-4 palabras en mayúsculas seguidas que parezcan nombre
            if (preg_match('/\b([A-ZÁÉÍÓÚÑ]{2,})\s+([A-ZÁÉÍÓÚÑ]{2,})\s+([A-ZÁÉÍÓÚÑ]{2,})(?:\s+([A-ZÁÉÍÓÚÑ]{2,}))?\b/', $texto, $nombreLinea)) {
                // Verificar que no sea una etiqueta conocida
                $posibleNombre = trim($nombreLinea[0]);
                $etiquetas = ['INSTITUTO NACIONAL ELECTORAL', 'CREDENCIAL PARA VOTAR', 'CLAVE DE ELECTOR', 'FECHA DE NACIMIENTO', 'APELLIDO PATERNO', 'APELLIDO MATERNO'];
                $esEtiqueta = false;
                foreach ($etiquetas as $et) {
                    if (str_contains($posibleNombre, $et)) { $esEtiqueta = true; break; }
                }
                if (!$esEtiqueta && strlen($posibleNombre) > 5) {
                    $nombreCompleto = $posibleNombre;
                }
            }
        }

        // Construir nombre completo desde partes
        if (!$nombreCompleto && ($datos['apellido_paterno'] || $datos['nombres'])) {
            $partes = array_filter([$datos['apellido_paterno'], $datos['apellido_materno'], $datos['nombres']]);
            $nombreCompleto = implode(' ', $partes);
        }

        // Método 3: Buscar cualquier línea con NOMBRE:
        if (!$nombreCompleto) {
            if (preg_match('/NOMBRE[:\s]+([A-ZÁÉÍÓÚÑ]+(?:\s+[A-ZÁÉÍÓÚÑ]+){0,3})/u', $texto, $nomGeneral)) {
                $nombreCompleto = trim($nomGeneral[1]);
            }
        }

        // Limpiar nombre
        if ($nombreCompleto) {
            $palabrasCorte = ['DOMICILIO', 'CLAVE', 'CURP', 'FECHA', 'SECCION', 'ESTADO', 'MUNICIPIO', 'LOCALIDAD', 'VIGENCIA', 'INSTITUTO', 'NACIMIENTO', 'ELECTORAL', 'CREDENCIAL', 'NACIONAL'];
            foreach ($palabrasCorte as $pc) {
                $pos = strpos($nombreCompleto, $pc);
                if ($pos !== false) {
                    $nombreCompleto = trim(substr($nombreCompleto, 0, $pos));
                }
            }
            if (strlen($nombreCompleto) > 2) {
                $datos['nombre'] = $nombreCompleto;
                $hallazgos[] = 'Nombre: ' . $nombreCompleto;
            }
        }

        // Si no se encontró nombre, Textract debería haberlo leído
        // Si aún así no hay nombre, es que el PDF no tiene texto extraíble
        if (empty($datos['nombre']) && $datos['curp']) {
            $datos['nombre'] = 'Ver documento físico';
        }

        // Fecha de nacimiento
        if (preg_match('/FECHA\s*(?:DE\s*)?NACIMIENTO[:\s]*([\d\/\-\.]+)/', $texto, $fn)) {
            $datos['fecha_nacimiento'] = $fn[1];
            $hallazgos[] = 'Fecha de nacimiento: '.$fn[1];
        } elseif (preg_match('/NACIMIENTO[:\s]*([\d\/\-\.]+)/', $texto, $fn2)) {
            $datos['fecha_nacimiento'] = $fn2[1];
            $hallazgos[] = 'Fecha de nacimiento: '.$fn2[1];
        } elseif ($datos['curp'] && strlen($datos['curp']) >= 10) {
            // Extraer fecha del CURP (posiciones 5-10: AAMMDD)
            $curpFecha = substr($datos['curp'], 4, 6);
            if (preg_match('/(\d{2})(\d{2})(\d{2})/', $curpFecha, $cf)) {
                $anio = (int) $cf[1] > 50 ? '19'.$cf[1] : '20'.$cf[1];
                $datos['fecha_nacimiento'] = $cf[3].'/'.$cf[2].'/'.$anio;
                $hallazgos[] = 'Fecha de nacimiento (del CURP): '.$datos['fecha_nacimiento'];
            }
        }

        // Clave de elector
        if (preg_match('/CLAVE\s*(?:DE\s*)?ELECTOR[:\s]*([A-Z0-9]+)/', $texto, $ce)) {
            $datos['clave_elector'] = $ce[1];
            $hallazgos[] = 'Clave de elector: '.$ce[1];
        } elseif (preg_match('/([A-Z]{6}\d{8}[HM]\d{3})/', $texto, $ce2)) {
            $datos['clave_elector'] = $ce2[1];
            $hallazgos[] = 'Clave de elector: '.$ce2[1];
        }

        // Vigencia
        $vigenciaEncontrada = false;
        if (preg_match('/VIGENCIA[:\s]*(\d{4})/', $texto, $vigM)) {
            $datos['vigencia'] = $vigM[1];
            $vigenciaEncontrada = true;
        } elseif (preg_match('/VIG(?:ENCIA)?[:\s.]*(\d{2})[\/\-](\d{2})[\/\-](\d{4})/', $texto, $vigM2)) {
            $datos['vigencia'] = $vigM2[3];
            $vigenciaEncontrada = true;
        } elseif (preg_match('/20[2-3]\d/', $texto, $anioM)) {
            $datos['vigencia'] = $anioM[0];
            $vigenciaEncontrada = true;
        }

        if ($vigenciaEncontrada && $datos['vigencia']) {
            $anioVigencia = (int) $datos['vigencia'];
            $anioActual = (int) date('Y');
            if ($anioVigencia < $anioActual) {
                $errores[] = 'INE vencida — Vigencia: '.$datos['vigencia'].' (año actual: '.$anioActual.')';
                $hallazgos[] = 'Vigencia: '.$datos['vigencia'].' — VENCIDA';
            } else {
                $hallazgos[] = 'Vigencia: '.$datos['vigencia'].' — Vigente';
            }
        } else {
            $hallazgos[] = 'No se detectó año de vigencia';
        }

        // Sección
        if (preg_match('/SECCION[:\s]*(\d+)/', $texto, $secM)) {
            $datos['seccion'] = $secM[1];
            $hallazgos[] = 'Sección: '.$secM[1];
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    private function validarCaratulaBanco(string $texto): array
    {
        $datos = [
            'banco' => null,
            'clabe' => null,
            'cuenta' => null,
            'titular' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores = [];
        $hallazgos = [];

        if (strlen($texto) < 20) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';

            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Banco
        $bancos = [
            'BBVA', 'BANCOMER', 'BANAMEX', 'CITIBANAMEX', 'SANTANDER',
            'BANORTE', 'HSBC', 'SCOTIABANK', 'INBURSA', 'BAJIO',
            'AFIRME', 'MIFEL', 'BANREGIO', 'AZTECA', 'MULTIVA', 'BANCO',
        ];
        foreach ($bancos as $b) {
            if (str_contains($texto, $b)) {
                $datos['banco'] = $b;
                $hallazgos[] = 'Banco detectado: '.$b;
                break;
            }
        }
        if (! $datos['banco']) {
            $errores[] = 'No se detectó institución bancaria reconocida';
        }

        // CLABE (18 dígitos) — buscar por texto "CLABE" o directamente 18 dígitos consecutivos
        // Primero limpiar espacios entre dígitos que el OCR pueda insertar
        $textoDigitos = preg_replace('/(\d)\s+(\d)/', '$1$2', $texto);

        if (preg_match('/(?:Cuenta\s*)?CLABE[:\s]*(\d{18})/i', $textoDigitos, $clabeM)) {
            $datos['clabe'] = $clabeM[1];
            $hallazgos[] = 'CLABE interbancaria: '.$clabeM[1];
        } elseif (preg_match('/CLABE[^0-9]{0,20}(\d{18})/i', $textoDigitos, $clabeM)) {
            $datos['clabe'] = $clabeM[1];
            $hallazgos[] = 'CLABE interbancaria: '.$clabeM[1];
        } elseif (preg_match('/(\d{18})/', $textoDigitos, $clabeM)) {
            $datos['clabe'] = $clabeM[1];
            $hallazgos[] = 'CLABE interbancaria: '.$clabeM[1];
        } else {
            $errores[] = 'No se encontró CLABE interbancaria (18 dígitos)';
        }

        // Número de cuenta
        if (preg_match('/(?:CUENTA|NO\.\s*CUENTA)[:\s]*(\d{8,12})/', $texto, $ctaM)) {
            $datos['cuenta'] = $ctaM[1];
            $hallazgos[] = 'No. Cuenta: '.$ctaM[1];
        } elseif (preg_match('/\b(\d{10,11})\b/', $texto, $ctaM2)) {
            $datos['cuenta'] = $ctaM2[1];
            $hallazgos[] = 'Posible No. Cuenta: '.$ctaM2[1];
        }

        // Titular
        if (preg_match('/(?:TITULAR|BENEFICIARIO|NOMBRE)[:\s]*([A-ZÁÉÍÓÚÑ&\s,\.]+)/u', $texto, $titM)) {
            $datos['titular'] = trim($titM[1]);
            $hallazgos[] = 'Titular: '.$datos['titular'];
        } elseif (str_contains($texto, 'TITULAR') || str_contains($texto, 'NOMBRE')
               || str_contains($texto, 'CUENTA') || str_contains($texto, 'BENEFICIARIO')) {
            $hallazgos[] = 'Se detectó referencia al titular';
        } else {
            $errores[] = 'No se encontró nombre del titular de la cuenta';
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    // ──────────────────────────────────────────────────
    // UTILIDADES
    // ──────────────────────────────────────────────────

    private function extraerTexto(Parser $parser, string $path): string
    {
        $texto = '';
        try {
            $pdf = $parser->parseFile($path);
            $texto = $pdf->getText();

            if (strlen($texto) < 20) {
                $pages = $pdf->getPages();
                $textoPages = '';
                foreach ($pages as $page) {
                    $textoPages .= $page->getText().' ';
                }
                if (strlen($textoPages) > strlen($texto)) {
                    $texto = $textoPages;
                }
            }

            $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
            $texto = preg_replace('/[^\x20-\x7E\n]/', ' ', $texto);
            $texto = preg_replace('/\s+/', ' ', $texto);
            $texto = strtoupper(trim($texto));
        } catch (\Exception $e) {
            $texto = '';
        }

        if (strlen($texto) >= 30) {
            return $texto;
        }

        // Intentar Tesseract local
        $textoOcr = $this->ocrDesdePdf($parser, $path);
        if (strlen($textoOcr) >= 30) {
            return $textoOcr;
        }

        // Último recurso: AWS Textract (OCR en la nube)
        $textoCloud = $this->ocrConTextract($path);
        if (strlen($textoCloud) > strlen($textoOcr) && strlen($textoCloud) > strlen($texto)) {
            return $textoCloud;
        }

        return strlen($textoOcr) > strlen($texto) ? $textoOcr : $texto;
    }

    /**
     * OCR con AWS Textract — lee PDFs/imágenes escaneadas en la nube.
     * No requiere Tesseract instalado en el servidor.
     */
    private function ocrConTextract(string $path): string
    {
        // Verificar que AWS SDK esté disponible
        if (!class_exists('\Aws\Textract\TextractClient')) {
            return '';
        }

        $accessKey = config('services.ia.aws_access_key', '');
        $secretKey = config('services.ia.aws_secret_key', '');
        $region = config('services.ia.bedrock_region', 'us-east-1');

        if (empty($accessKey) || empty($secretKey)) {
            return '';
        }

        try {
            $client = new \Aws\Textract\TextractClient([
                'region' => $region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $accessKey,
                    'secret' => $secretKey,
                ],
                'http' => ['timeout' => 30],
            ]);

            $fileBytes = file_get_contents($path);

            $response = $client->detectDocumentText([
                'Document' => [
                    'Bytes' => $fileBytes,
                ],
            ]);

            $texto = '';
            foreach ($response['Blocks'] as $block) {
                if ($block['BlockType'] === 'LINE') {
                    $texto .= $block['Text'].' ';
                }
            }

            $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
            $texto = preg_replace('/[^\x20-\x7E\n]/', ' ', $texto);
            $texto = preg_replace('/\s+/', ' ', $texto);

            return strtoupper(trim($texto));

        } catch (\Exception $e) {
            Log::warning('Textract OCR falló', ['error' => $e->getMessage(), 'path' => $path]);

            return '';
        }
    }

    /**
     * Extrae imágenes del PDF con pdfparser, las reconstruye con GD,
     * y las pasa a Tesseract OCR para leer el texto.
     */
    private function ocrDesdePdf(Parser $parser, string $pdfPath): string
    {
        // Buscar Tesseract en rutas comunes (Windows y Linux)
        $rutasPosibles = [
            '/usr/bin/tesseract',                                                        // Linux estándar
            '/usr/local/bin/tesseract',                                                  // Linux alternativo
            'C:\\Users\\IT 2\\AppData\\Local\\Programs\\Tesseract-OCR\\tesseract.exe',  // PC Alfonso
            'C:\\Users\\IT\\AppData\\Local\\Programs\\Tesseract-OCR\\tesseract.exe',    // PC Said
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ];
        $tesseractPath = null;
        foreach ($rutasPosibles as $ruta) {
            if (file_exists($ruta)) {
                $tesseractPath = $ruta;
                break;
            }
        }
        if (! $tesseractPath) {
            return '';
        }

        $textoTotal = '';
        $tmpDir = sys_get_temp_dir();

        try {
            $pdf = $parser->parseFile($pdfPath);
            $imgCount = 0;

            foreach ($pdf->getObjects() as $obj) {
                $header = $obj->getHeader();
                $subtype = $header->get('Subtype');
                if (! $subtype || $subtype->getContent() !== 'Image') {
                    continue;
                }

                $content = $obj->getContent();
                if (strlen($content) < 1000) {
                    continue;
                }

                $filter = $header->get('Filter');
                $filterName = $filter ? $filter->getContent() : '';
                $width = (int) ($header->get('Width') ? $header->get('Width')->getContent() : 0);
                $height = (int) ($header->get('Height') ? $header->get('Height')->getContent() : 0);

                $tmpImage = $tmpDir.'/salcom_ocr_'.uniqid();
                $imageCreated = false;

                if ($filterName === 'DCTDecode') {
                    $tmpImage .= '.jpg';
                    file_put_contents($tmpImage, $content);
                    $imageCreated = true;
                } elseif ($filterName === 'FlateDecode' && $width > 0 && $height > 0) {
                    $tmpImage .= '.png';
                    $imageCreated = $this->reconstruirImagenGD($content, $width, $height, $header, $tmpImage);
                }

                if ($imageCreated && file_exists($tmpImage)) {
                    try {
                        $ocr = new TesseractOCR($tmpImage);
                        $ocr->executable($tesseractPath);
                        $ocr->lang('spa', 'eng');
                        $resultado = $ocr->run();
                        if (strlen(trim($resultado)) > 10) {
                            $textoTotal .= $resultado."\n";
                        }
                    } catch (\Exception $e) {
                        // OCR falló, continuar
                    } finally {
                        @unlink($tmpImage);
                    }
                }

                if (++$imgCount >= 5) {
                    break;
                }
            }
        } catch (\Exception $e) {
            return '';
        }

        $textoTotal = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $textoTotal);
        $textoTotal = preg_replace('/[^\x20-\x7E\n]/', ' ', $textoTotal);
        $textoTotal = preg_replace('/\s+/', ' ', $textoTotal);

        return strtoupper(trim($textoTotal));
    }

    private function reconstruirImagenGD(string $content, int $width, int $height, $header, string $outputPath): bool
    {
        try {
            $cs = $header->get('ColorSpace') ? $header->get('ColorSpace')->getContent() : 'DeviceRGB';
            $img = imagecreatetruecolor($width, $height);
            $pos = 0;
            $len = strlen($content);

            if ($cs === 'DeviceRGB') {
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        if ($pos + 2 >= $len) {
                            break 2;
                        }
                        $r = ord($content[$pos++]);
                        $g = ord($content[$pos++]);
                        $b = ord($content[$pos++]);
                        imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
                    }
                }
            } elseif ($cs === 'DeviceGray') {
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        if ($pos >= $len) {
                            break 2;
                        }
                        $g = ord($content[$pos++]);
                        imagesetpixel($img, $x, $y, imagecolorallocate($img, $g, $g, $g));
                    }
                }
            } else {
                imagedestroy($img);

                return false;
            }

            imagepng($img, $outputPath);
            imagedestroy($img);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function validarRFC(?string $rfc): bool
    {
        if (! $rfc) {
            return false;
        }

        return (bool) preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc);
    }

    private function normalizarNombre(string $nombre): string
    {
        $nombre = mb_strtoupper(trim($nombre), 'UTF-8');
        $reemplazos = [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U', 'ñ' => 'N',
        ];
        $nombre = strtr($nombre, $reemplazos);
        $nombre = preg_replace('/[^A-Z0-9\s]/', ' ', $nombre) ?? $nombre;
        $nombre = preg_replace('/\s+/', ' ', $nombre) ?? $nombre;

        return trim($nombre);
    }

    private function nombresCoinciden(string $esperado, string $encontrado): bool
    {
        $a = $this->normalizarNombre($esperado);
        $b = $this->normalizarNombre($encontrado);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        // Uno contiene al otro (útil con S.A. DE C.V. u orden de apellidos)
        if (str_contains($a, $b) || str_contains($b, $a)) {
            return true;
        }

        // Comparar tokens (orden flexible)
        $tokensA = array_values(array_filter(explode(' ', $a), fn ($t) => strlen($t) > 1));
        $tokensB = array_values(array_filter(explode(' ', $b), fn ($t) => strlen($t) > 1));
        if (count($tokensA) >= 2 && count($tokensB) >= 2) {
            $inter = array_intersect($tokensA, $tokensB);
            $umbral = min(count($tokensA), count($tokensB));
            if ($umbral > 0 && count($inter) / $umbral >= 0.75) {
                return true;
            }
        }

        similar_text($a, $b, $pct);

        return $pct >= 80;
    }

    private function mesEnEspanol(int $mes): string
    {
        return [
            1 => 'ENERO',    2 => 'FEBRERO',   3 => 'MARZO',
            4 => 'ABRIL',    5 => 'MAYO',      6 => 'JUNIO',
            7 => 'JULIO',    8 => 'AGOSTO',    9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ][$mes] ?? '';
    }
}
