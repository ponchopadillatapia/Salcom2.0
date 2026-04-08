<?php

namespace App\Http\Controllers\APIS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use thiagoalessio\TesseractOCR\TesseractOCR;

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

            $archivos = [
                'cif'            => $request->file('cif_pdf')->store('cif', 'local'),
                'opinion'        => $request->file('opinion_pdf')->store('opiniones', 'local'),
                'acta'           => $request->file('acta_pdf')->store('actas', 'local'),
                'rep_legal'      => $request->file('rep_legal_pdf')->store('rep_legal', 'local'),
                'contribuyente'  => $request->file('contribuyente_pdf')->store('contribuyente', 'local'),
                'caratula_banco' => $request->file('caratula_banco_pdf')->store('caratula_banco', 'local'),
            ];

            $textos = [];
            foreach ($archivos as $clave => $ruta) {
                $textos[$clave] = $this->extraerTexto($parser, storage_path('app/private/' . $ruta));
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
            // ACTA CONSTITUTIVA
            // ════════════════════════════════════════
            $acta = $this->validarActa($textos['acta'], $cif['datos']['es_moral']);

            // ════════════════════════════════════════
            // ID REPRESENTANTE LEGAL
            // ════════════════════════════════════════
            $repLegal = $this->validarINE($textos['rep_legal'], 'Representante Legal');

            // ════════════════════════════════════════
            // ID CONTRIBUYENTE
            // ════════════════════════════════════════
            $contribuyente = $this->validarINE($textos['contribuyente'], 'Contribuyente');

            // ════════════════════════════════════════
            // CARÁTULA DE BANCO
            // ════════════════════════════════════════
            $banco = $this->validarCaratulaBanco($textos['caratula_banco']);

            // ════════════════════════════════════════
            // SEMÁFORO
            // ════════════════════════════════════════
            $cifOk   = $cif['valida'];
            $opOk    = $opinion['valida'];
            $actaOk  = $acta['valida'];
            $repOk   = $repLegal['valida'];
            $contOk  = $contribuyente['valida'];
            $bancoOk = $banco['valida'];

            $todoOk = $cifOk && $opOk && $actaOk && $repOk && $contOk && $bancoOk;

            if ($todoOk) {
                $estado = 'verde';
            } elseif ($cifOk && $opOk) {
                $estado = 'amarillo';
            } else {
                $estado = 'rojo';
            }

            return response()->json([
                'estado'        => $estado,
                'cif'           => $cif,
                'opinion'       => $opinion,
                'acta'          => $acta,
                'rep_legal'     => $repLegal,
                'contribuyente' => $contribuyente,
                'caratula_banco'=> $banco,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errores = collect($e->errors())->flatten()->implode(' | ');
            return response()->json(['mensaje' => 'Archivo inválido: ' . $errores], 422);
        } catch (\Exception $e) {
            return response()->json(['mensaje' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────
    // VALIDADORES POR DOCUMENTO
    // ──────────────────────────────────────────────────

    private function validarCIF(string $texto): array
    {
        $datos   = [
            'rfc'              => null,
            'nombre'           => null,
            'tipo_persona'     => null,
            'es_moral'         => false,
            'regimen'          => null,
            'domicilio_fiscal' => null,
            'codigo_postal'    => null,
            'fecha_inicio'     => null,
            'caracteres_leidos'=> strlen($texto),
        ];
        $errores  = [];
        $hallazgos = []; // lo que SÍ encontró

        if (strlen($texto) < 50) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';
            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // ¿Es realmente un CIF?
        if (str_contains($texto, 'CONSTANCIA DE SITUACION FISCAL')) {
            $hallazgos[] = 'Documento identificado como Constancia de Situación Fiscal';
        } else {
            $errores[] = 'No es una Constancia de Situación Fiscal del SAT';
        }

        // Sello SAT
        if (str_contains($texto, 'SERVICIO DE ADMINISTRACION TRIBUTARIA')) {
            $hallazgos[] = 'Tiene sello del SAT';
        } else {
            $errores[] = 'No tiene sello del SAT';
        }

        // RFC
        if (preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/u', $texto, $m)) {
            $datos['rfc'] = $m[1];
            $hallazgos[] = 'RFC encontrado: ' . $m[1];
        } else {
            $errores[] = 'No se encontró RFC';
        }

        // Tipo persona
        $esMoral = str_contains($texto, 'PERSONA MORAL')
                || str_contains($texto, 'SOCIEDAD')
                || str_contains($texto, 'S.A')
                || str_contains($texto, 'S DE RL')
                || str_contains($texto, 'S.A.S');
        $datos['es_moral']     = $esMoral;
        $datos['tipo_persona'] = $esMoral ? 'Persona Moral' : 'Persona Física';
        $hallazgos[] = 'Tipo: ' . $datos['tipo_persona'];

        // Nombre / Razón social
        if ($esMoral) {
            preg_match('/(?:RAZON SOCIAL|DENOMINACION)[:\s]*([A-Z0-9ÑÁÉÍÓÚü&\s,\.\-]+)/ui', $texto, $nm);
        } else {
            preg_match('/(?:NOMBRE\s*(?:\(S\))?|CONTRIBUYENTE)[:\s]*([A-ZÁÉÍÓÚÑ\s]+)/u', $texto, $nm);
        }
        if (!empty($nm[1]) && strlen(trim($nm[1])) > 2) {
            $nombreRaw = trim($nm[1]);
            // Quitar palabras sueltas de 1 letra al inicio (residuos del PDF)
            $nombreRaw = preg_replace('/^[A-Z]\s+/', '', $nombreRaw);
            // Quitar etiquetas que se colaron al final (NOMBRE, RFC, CURP, DOMICILIO, etc.)
            $palabrasCorte = ['NOMBRE','RFC','CURP','DOMICILIO','REGIMEN','CODIGO','FECHA','CLAVE','TIPO','ESTADO','MUNICIPIO','COLONIA','CALLE','NUMERO','LOCALIDAD','ENTRE','TELEFONO','CORREO','SITUACION','OBLIGACIONES'];
            foreach ($palabrasCorte as $pc) {
                $pos = strpos($nombreRaw, $pc);
                if ($pos !== false && $pos > 3) {
                    $nombreRaw = trim(substr($nombreRaw, 0, $pos));
                }
            }
            $datos['nombre'] = trim($nombreRaw);
            $hallazgos[] = 'Nombre: ' . $datos['nombre'];
        } else {
            $datos['nombre'] = 'NO DETECTADO';
        }

        // Régimen fiscal
        if (preg_match('/REGIMEN[:\s]*([\w\s,\.]+?)(?:\n|FECHA|DOMICILIO|OBLIGACIONES)/u', $texto, $reg)) {
            $datos['regimen'] = trim($reg[1]);
            $hallazgos[] = 'Régimen: ' . $datos['regimen'];
        } elseif (str_contains($texto, 'REGIMEN')) {
            $hallazgos[] = 'Se detectó mención de Régimen Fiscal';
        } else {
            $errores[] = 'No se encontró Régimen Fiscal';
        }

        // Domicilio fiscal
        if (str_contains($texto, 'DOMICILIO FISCAL')) {
            $hallazgos[] = 'Contiene Domicilio Fiscal';
        } else {
            $errores[] = 'No se encontró Domicilio Fiscal';
        }

        // Código postal
        if (preg_match('/CODIGO POSTAL[:\s]*(\d{5})/', $texto, $cp)) {
            $datos['codigo_postal'] = $cp[1];
            $hallazgos[] = 'C.P.: ' . $cp[1];
        } elseif (preg_match('/C\.?P\.?[:\s]*(\d{5})/', $texto, $cp)) {
            $datos['codigo_postal'] = $cp[1];
            $hallazgos[] = 'C.P.: ' . $cp[1];
        }

        // Fecha inicio operaciones
        if (preg_match('/FECHA\s*(?:DE\s*)?INICIO\s*(?:DE\s*)?OPERACIONES[:\s]*([\d\/\-]+)/', $texto, $fi)) {
            $datos['fecha_inicio'] = $fi[1];
            $hallazgos[] = 'Inicio operaciones: ' . $fi[1];
        }

        // RFC válido formato
        $rfcValido = $this->validarRFC($datos['rfc']);
        if ($datos['rfc'] && !$rfcValido) {
            $errores[] = 'El RFC "' . $datos['rfc'] . '" no tiene formato válido';
        }

        return [
            'valida'    => empty($errores),
            'datos'     => $datos,
            'errores'   => $errores,
            'hallazgos' => $hallazgos,
        ];
    }

    private function validarOpinion(string $texto, ?string $rfcCif): array
    {
        $datos = [
            'rfc_encontrado' => null,
            'sentido'        => null,
            'fecha'          => null,
            'articulo'       => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores   = [];
        $hallazgos = [];

        if (strlen($texto) < 50) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';
            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Identificar documento
        if (str_contains($texto, 'OPINION') && str_contains($texto, 'CUMPLIMIENTO')) {
            $hallazgos[] = 'Documento identificado como Opinión de Cumplimiento';
        } else {
            $errores[] = 'No parece ser una Opinión de Cumplimiento del SAT';
        }

        // Sello SAT
        if (str_contains($texto, 'SERVICIO DE ADMINISTRACION TRIBUTARIA')) {
            $hallazgos[] = 'Tiene sello del SAT';
        } else {
            $errores[] = 'No tiene sello oficial del SAT';
        }

        // Sentido (POSITIVA / NEGATIVA)
        if (str_contains($texto, 'POSITIVA')) {
            $datos['sentido'] = 'POSITIVA';
            $hallazgos[] = 'Sentido: POSITIVA ✓';
        } elseif (str_contains($texto, 'NEGATIVA')) {
            $datos['sentido'] = 'NEGATIVA';
            $errores[] = 'La opinión es NEGATIVA — el proveedor tiene adeudos fiscales';
        } else {
            $errores[] = 'No se detectó si la opinión es Positiva o Negativa';
        }

        // Artículo 32-D
        if (str_contains($texto, 'ARTICULO 32-D') || str_contains($texto, '32-D')) {
            $datos['articulo'] = '32-D CFF';
            $hallazgos[] = 'Referencia al Art. 32-D del CFF';
        }

        // RFC en la opinión
        if (preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/u', $texto, $rfcOp)) {
            $datos['rfc_encontrado'] = $rfcOp[1];
            $hallazgos[] = 'RFC en opinión: ' . $rfcOp[1];
        }

        // Cruzar RFC con CIF
        if ($rfcCif && $datos['rfc_encontrado'] && $datos['rfc_encontrado'] !== $rfcCif) {
            $errores[] = 'RFC no coincide: CIF=' . $rfcCif . ' vs Opinión=' . $datos['rfc_encontrado'];
        } elseif ($rfcCif && !$datos['rfc_encontrado'] && !str_contains($texto, $rfcCif)) {
            $errores[] = 'El RFC del CIF (' . $rfcCif . ') no aparece en la Opinión';
        }

        // Mes y año en curso
        $mesActual  = strtoupper($this->mesEnEspanol((int) date('n')));
        $anioActual = date('Y');
        $mesBien    = str_contains($texto, $mesActual);
        $anioBien   = str_contains($texto, $anioActual);

        if ($mesBien && $anioBien) {
            $hallazgos[] = 'Fecha vigente: ' . $mesActual . ' ' . $anioActual;
        } else {
            $errores[] = 'No corresponde al mes en curso (' . $mesActual . ' ' . $anioActual . ')';
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    private function validarActa(string $texto, bool $esMoral): array
    {
        $datos = [
            'notario'       => null,
            'escritura'     => null,
            'tipo_sociedad' => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores   = [];
        $hallazgos = [];

        if (!$esMoral) {
            $hallazgos[] = 'Persona Física — Acta Constitutiva no requerida';
            return ['valida' => true, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        if (strlen($texto) < 50) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';
            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Escritura
        if (preg_match('/ESCRITURA\s*(?:PUBLICA\s*)?(?:NUMERO\s*)?[:\s#]*(\d+)/u', $texto, $esc)) {
            $datos['escritura'] = $esc[1];
            $hallazgos[] = 'Escritura Pública No. ' . $esc[1];
        } elseif (str_contains($texto, 'ESCRITURA')) {
            $hallazgos[] = 'Se menciona Escritura Pública';
        }

        // Notario
        if (preg_match('/NOTARIO\s*(?:PUBLICO\s*)?(?:NUMERO\s*)?[:\s#]*(\d+)?/u', $texto, $not)) {
            $datos['notario'] = isset($not[1]) ? 'Notaría #' . $not[1] : 'Sí';
            $hallazgos[] = 'Notario Público: ' . ($not[1] ?? 'detectado');
        } elseif (str_contains($texto, 'NOTARIO') || str_contains($texto, 'NOTARIA')) {
            $hallazgos[] = 'Se menciona Notario Público';
        } else {
            $errores[] = 'No se encontró referencia al Notario Público';
        }

        // Tipo sociedad
        $sociedades = ['S.A. DE C.V.','S.A.S.','S. DE R.L.','S.A.','S.C.','A.C.','S.A.P.I.'];
        foreach ($sociedades as $s) {
            if (str_contains($texto, str_replace('.', '', str_replace(' ', '', $s)))
                || str_contains($texto, $s)) {
                $datos['tipo_sociedad'] = $s;
                $hallazgos[] = 'Tipo de sociedad: ' . $s;
                break;
            }
        }
        if (!$datos['tipo_sociedad']) {
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
            'nombre'    => null,
            'curp'      => null,
            'clave_elector' => null,
            'vigencia'  => null,
            'seccion'   => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores   = [];
        $hallazgos = [];

        if (strlen($texto) < 30) {
            $hallazgos[] = 'PDF sin texto extraíble — probablemente es imagen escaneada de INE';
            // No bloquear, las INE escaneadas son normales
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
            $errores[] = 'No se detectó que sea una INE/IFE de ' . $etiqueta;
        }

        // CURP
        if (preg_match('/([A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d)/', $texto, $curpM)) {
            $datos['curp'] = $curpM[1];
            $hallazgos[] = 'CURP: ' . $curpM[1];
        } elseif (str_contains($texto, 'CURP')) {
            $hallazgos[] = 'Se menciona CURP (no se pudo extraer el valor)';
        }

        // Clave de elector
        if (preg_match('/CLAVE\s*(?:DE\s*)?ELECTOR[:\s]*([A-Z0-9]+)/', $texto, $ce)) {
            $datos['clave_elector'] = $ce[1];
            $hallazgos[] = 'Clave de elector: ' . $ce[1];
        } elseif (preg_match('/([A-Z]{6}\d{8}[HM]\d{3})/', $texto, $ce2)) {
            $datos['clave_elector'] = $ce2[1];
            $hallazgos[] = 'Clave de elector: ' . $ce2[1];
        }

        // Nombre
        if (preg_match('/NOMBRE[:\s]*([A-ZÁÉÍÓÚÑ\s]+)/u', $texto, $nomM)) {
            $datos['nombre'] = trim($nomM[1]);
            $hallazgos[] = 'Nombre: ' . $datos['nombre'];
        } elseif (preg_match('/APELLIDO\s*PATERNO[:\s]*([A-ZÁÉÍÓÚÑ]+)/u', $texto, $apM)) {
            $datos['nombre'] = trim($apM[1]);
            $hallazgos[] = 'Apellido detectado: ' . $datos['nombre'];
        }

        // Vigencia
        if (preg_match('/VIGENCIA[:\s]*(\d{4})/', $texto, $vigM)) {
            $datos['vigencia'] = $vigM[1];
            if ((int) $vigM[1] < (int) date('Y')) {
                $errores[] = 'INE vencida (vigencia: ' . $vigM[1] . ')';
                $hallazgos[] = 'Vigencia: ' . $vigM[1] . ' (VENCIDA)';
            } else {
                $hallazgos[] = 'Vigencia: ' . $vigM[1] . ' (vigente)';
            }
        }

        // Sección
        if (preg_match('/SECCION[:\s]*(\d+)/', $texto, $secM)) {
            $datos['seccion'] = $secM[1];
            $hallazgos[] = 'Sección: ' . $secM[1];
        }

        return ['valida' => empty($errores), 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
    }

    private function validarCaratulaBanco(string $texto): array
    {
        $datos = [
            'banco'    => null,
            'clabe'    => null,
            'cuenta'   => null,
            'titular'  => null,
            'caracteres_leidos' => strlen($texto),
        ];
        $errores   = [];
        $hallazgos = [];

        if (strlen($texto) < 50) {
            $errores[] = 'No se pudo leer el contenido del PDF — puede ser imagen escaneada';
            return ['valida' => false, 'datos' => $datos, 'errores' => $errores, 'hallazgos' => $hallazgos];
        }

        // Banco
        $bancos = [
            'BBVA','BANCOMER','BANAMEX','CITIBANAMEX','SANTANDER',
            'BANORTE','HSBC','SCOTIABANK','INBURSA','BAJIO',
            'AFIRME','MIFEL','BANREGIO','AZTECA','MULTIVA','BANCO',
        ];
        foreach ($bancos as $b) {
            if (str_contains($texto, $b)) {
                $datos['banco'] = $b;
                $hallazgos[] = 'Banco detectado: ' . $b;
                break;
            }
        }
        if (!$datos['banco']) {
            $errores[] = 'No se detectó institución bancaria reconocida';
        }

        // CLABE (18 dígitos)
        if (preg_match('/(\d{18})/', $texto, $clabeM)) {
            $datos['clabe'] = $clabeM[1];
            $hallazgos[] = 'CLABE: ' . $clabeM[1];
        } else {
            $errores[] = 'No se encontró CLABE interbancaria (18 dígitos)';
        }

        // Número de cuenta
        if (preg_match('/(?:CUENTA|NO\.\s*CUENTA)[:\s]*(\d{8,12})/', $texto, $ctaM)) {
            $datos['cuenta'] = $ctaM[1];
            $hallazgos[] = 'No. Cuenta: ' . $ctaM[1];
        } elseif (preg_match('/\b(\d{10,11})\b/', $texto, $ctaM2)) {
            $datos['cuenta'] = $ctaM2[1];
            $hallazgos[] = 'Posible No. Cuenta: ' . $ctaM2[1];
        }

        // Titular
        if (preg_match('/(?:TITULAR|BENEFICIARIO|NOMBRE)[:\s]*([A-ZÁÉÍÓÚÑ&\s,\.]+)/u', $texto, $titM)) {
            $datos['titular'] = trim($titM[1]);
            $hallazgos[] = 'Titular: ' . $datos['titular'];
        } elseif (str_contains($texto, 'TITULAR') || str_contains($texto, 'NOMBRE')
               || str_contains($texto, 'CUENTA')  || str_contains($texto, 'BENEFICIARIO')) {
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
            $texto = $parser->parseFile($path)->getText();
            $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            $texto = preg_replace('/[^\x20-\x7E\n]/', ' ', $texto);
            $texto = preg_replace('/\s+/', ' ', $texto);
            $texto = strtoupper(trim($texto));
        } catch (\Exception $e) {
            $texto = '';
        }

        // Si hay suficiente texto, no necesitamos OCR
        if (strlen($texto) >= 50) {
            return $texto;
        }

        // Intentar OCR: extraer imágenes del PDF y pasarlas a Tesseract
        $textoOcr = $this->ocrDesdePdf($parser, $path);
        return strlen($textoOcr) > strlen($texto) ? $textoOcr : $texto;
    }

    /**
     * Extrae imágenes del PDF con pdfparser, las reconstruye con GD,
     * y las pasa a Tesseract OCR para leer el texto.
     */
    private function ocrDesdePdf(Parser $parser, string $pdfPath): string
    {
        $tesseractPath = 'C:\\Users\\IT 2\\AppData\\Local\\Programs\\Tesseract-OCR\\tesseract.exe';
        if (!file_exists($tesseractPath)) return '';

        $textoTotal = '';
        $tmpDir = sys_get_temp_dir();

        try {
            $pdf = $parser->parseFile($pdfPath);
            $imgCount = 0;

            foreach ($pdf->getObjects() as $obj) {
                $header = $obj->getHeader();
                $subtype = $header->get('Subtype');
                if (!$subtype || $subtype->getContent() !== 'Image') continue;

                $content = $obj->getContent();
                if (strlen($content) < 1000) continue;

                $filter = $header->get('Filter');
                $filterName = $filter ? $filter->getContent() : '';
                $width  = (int) ($header->get('Width')  ? $header->get('Width')->getContent()  : 0);
                $height = (int) ($header->get('Height') ? $header->get('Height')->getContent() : 0);

                $tmpImage = $tmpDir . '/salcom_ocr_' . uniqid();
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
                            $textoTotal .= $resultado . "\n";
                        }
                    } catch (\Exception $e) {
                        // OCR falló, continuar
                    } finally {
                        @unlink($tmpImage);
                    }
                }

                if (++$imgCount >= 5) break;
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
                        if ($pos + 2 >= $len) break 2;
                        $r = ord($content[$pos++]);
                        $g = ord($content[$pos++]);
                        $b = ord($content[$pos++]);
                        imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, $g, $b));
                    }
                }
            } elseif ($cs === 'DeviceGray') {
                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        if ($pos >= $len) break 2;
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
        if (!$rfc) return false;
        return (bool) preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc);
    }

    private function mesEnEspanol(int $mes): string
    {
        return [
            1 => 'ENERO',    2 => 'FEBRERO',   3 => 'MARZO',
            4 => 'ABRIL',    5 => 'MAYO',      6 => 'JUNIO',
            7 => 'JULIO',    8 => 'AGOSTO',    9 => 'SEPTIEMBRE',
           10 => 'OCTUBRE', 11 => 'NOVIEMBRE',12 => 'DICIEMBRE',
        ][$mes] ?? '';
    }
}
