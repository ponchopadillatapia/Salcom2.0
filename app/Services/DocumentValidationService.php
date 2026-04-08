<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class DocumentValidationService
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Valida todos los documentos y retorna el resultado completo.
     */
    public function validarTodos(array $archivos): array
    {
        $textos = $this->extraerTextos($archivos);

        $cif          = $this->validarCIF($textos['cif']);
        $opinion      = $this->validarOpinion($textos['opinion'], $cif['rfc']);
        $acta         = $this->validarActa($textos['acta'], $cif['es_moral']);
        $repLegal     = $this->validarINE($textos['rep_legal'], 'representante');
        $contribuyente = $this->validarINE($textos['contribuyente'], 'contribuyente');
        $caratula     = $this->validarCaratulaBanco($textos['caratula_banco']);

        $todoOk = $cif['valido'] && $opinion['valida'] && $acta['valida']
               && $repLegal['valida'] && $contribuyente['valida'] && $caratula['valida'];

        if ($todoOk) {
            $estado = 'verde';
        } elseif ($cif['valido'] && $opinion['valida']) {
            $estado = 'amarillo';
        } else {
            $estado = 'rojo';
        }

        return [
            'empresa' => [
                'rfc'         => $cif['rfc'] ?? 'No detectado',
                'nombre'      => $cif['nombre'],
                'tipo'        => $cif['es_moral'] ? 'Persona Moral' : 'Persona Física',
                'rfc_valido'  => $cif['rfc_valido'] ? 'válido' : 'inválido',
                'cif_valido'  => $cif['valido'] ? 'SI' : 'NO',
                'estado'      => $estado,
                'errores_cif' => $cif['errores'],
            ],
            'opinion'        => ['valida' => $opinion['valida'], 'escaneado' => $opinion['escaneado'], 'errores' => $opinion['errores']],
            'acta'           => ['valida' => $acta['valida'], 'escaneado' => $acta['escaneado'], 'errores' => $acta['errores']],
            'rep_legal'      => ['valida' => $repLegal['valida'], 'escaneado' => $repLegal['escaneado'], 'errores' => $repLegal['errores']],
            'contribuyente'  => ['valida' => $contribuyente['valida'], 'escaneado' => $contribuyente['escaneado'], 'errores' => $contribuyente['errores']],
            'caratula_banco' => ['valida' => $caratula['valida'], 'escaneado' => $caratula['escaneado'], 'errores' => $caratula['errores']],
        ];
    }

    // ── Extracción de texto de PDFs ──

    public function extraerTextos(array $archivos): array
    {
        $carpetas = [
            'cif' => 'cif', 'opinion' => 'opiniones', 'acta' => 'actas',
            'rep_legal' => 'rep_legal', 'contribuyente' => 'contribuyente', 'caratula_banco' => 'caratula_banco',
        ];

        $textos = [];
        foreach ($archivos as $clave => $ruta) {
            $path = storage_path('app/private/' . $carpetas[$clave] . '/' . basename($ruta));
            try {
                $texto = $this->parser->parseFile($path)->getText();
                $textos[$clave] = strtoupper($this->normalizarTexto($texto));
            } catch (\Exception $e) {
                $textos[$clave] = '';
            }
        }
        return $textos;
    }

    // ── Validación CIF ──

    public function validarCIF(string $texto): array
    {
        $errores = [];
        $escaneado = strlen($texto) < 100;

        if ($escaneado) {
            $errores[] = '⚠ PDF escaneado — no se puede leer el texto automáticamente';
        } else {
            $claves = [
                'CONSTANCIA DE SITUACION FISCAL'        => 'No es una Constancia de Situación Fiscal del SAT',
                'SERVICIO DE ADMINISTRACION TRIBUTARIA' => 'No tiene sello del SAT (Servicio de Administración Tributaria)',
                'REGIMEN'                               => 'No se encontró el Régimen Fiscal',
                'DOMICILIO FISCAL'                      => 'No se encontró Domicilio Fiscal',
                'RFC'                                   => 'No se encontró RFC en el documento',
            ];
            $errores = $this->verificarClaves($texto, $claves);
        }

        preg_match('/RFC[:\s]*([A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3})/u', $texto, $rfcMatch);
        $rfc = $rfcMatch[1] ?? null;

        $esMoral = str_contains($texto, 'PERSONA MORAL') || str_contains($texto, 'SOCIEDAD')
                || str_contains($texto, 'S.A') || str_contains($texto, 'S DE RL') || str_contains($texto, 'S.A.S');

        if ($esMoral) {
            preg_match('/(?:RAZON SOCIAL|DENOMINACION)[:\s]*([A-ZÁÉÍÓÚÑ&\s,\.]+)/u', $texto, $nombreMatch);
        } else {
            preg_match('/(?:NOMBRE|CONTRIBUYENTE)[:\s]*([A-ZÁÉÍÓÚÑ\s]+)/u', $texto, $nombreMatch);
        }
        $nombre = isset($nombreMatch[1]) ? trim($nombreMatch[1]) : 'DESCONOCIDO';

        return [
            'valido'     => empty($errores),
            'escaneado'  => $escaneado,
            'errores'    => $errores,
            'rfc'        => $rfc,
            'rfc_valido' => $this->validarRFC($rfc),
            'nombre'     => $nombre,
            'es_moral'   => $esMoral,
        ];
    }

    // ── Validación Opinión de Cumplimiento ──

    public function validarOpinion(string $texto, ?string $rfc): array
    {
        $errores = [];
        $escaneado = strlen($texto) < 100;

        if ($escaneado) {
            $errores[] = '⚠ PDF escaneado — no se puede leer el texto automáticamente';
        } else {
            $claves = [
                'OPINION DE CUMPLIMIENTO'               => 'No es una Opinión de Cumplimiento del SAT',
                'SERVICIO DE ADMINISTRACION TRIBUTARIA' => 'No tiene sello oficial del SAT',
                'POSITIVA'                              => 'La opinión NO es Positiva — el proveedor tiene adeudos fiscales',
                'ARTICULO 32-D'                         => 'No corresponde al Art. 32-D del CFF requerido',
            ];
            $errores = $this->verificarClaves($texto, $claves);

            if ($rfc && !str_contains($texto, $rfc)) {
                $errores[] = 'El RFC ' . $rfc . ' no coincide con el del CIF';
            }

            $mesActual  = strtoupper($this->mesEnEspanol(date('n')));
            $anioActual = date('Y');
            if (!str_contains($texto, $mesActual) || !str_contains($texto, $anioActual)) {
                $errores[] = 'La opinión no corresponde al mes en curso (' . $mesActual . ' ' . $anioActual . ')';
            }
        }

        return ['valida' => empty($errores), 'escaneado' => $escaneado, 'errores' => $errores];
    }

    // ── Validación Acta Constitutiva ──

    public function validarActa(string $texto, bool $esMoral): array
    {
        $errores = [];
        $escaneado = strlen($texto) < 100;

        if ($escaneado) {
            $errores[] = '⚠ PDF escaneado — no se puede leer el texto automáticamente';
        } elseif (!$esMoral) {
            $errores[] = 'El CIF indica Persona Física — no requiere Acta Constitutiva';
        } else {
            $claves = [
                'ESCRITURA'  => 'No se encontró número de Escritura Pública',
                'NOTARIO'    => 'No se encontró referencia al Notario Público',
                'SOCIEDAD'   => 'No se encontró el tipo de Sociedad',
                'CONSTITUCI' => 'No se encontró cláusula de Constitución',
            ];
            $errores = $this->verificarClaves($texto, $claves);
        }

        return ['valida' => empty($errores), 'escaneado' => $escaneado, 'errores' => $errores];
    }

    // ── Validación INE (rep legal o contribuyente) ──

    public function validarINE(string $texto, string $tipo): array
    {
        $errores = [];
        $escaneado = strlen($texto) < 100;

        if ($escaneado) {
            $errores[] = '⚠ PDF escaneado — verificación manual requerida';
        } else {
            $tieneIne = str_contains($texto, 'INSTITUTO NACIONAL ELECTORAL')
                     || str_contains($texto, 'INE') || str_contains($texto, 'IFE')
                     || str_contains($texto, 'CREDENCIAL PARA VOTAR');

            if (!$tieneIne) $errores[] = 'No se detectó INE/IFE válido';
            if (!str_contains($texto, 'CURP')) $errores[] = 'No se encontró CURP';
            if (!str_contains($texto, 'NOMBRE') && !str_contains($texto, 'APELLIDO PATERNO')) {
                $errores[] = "No se encontró nombre del {$tipo}";
            }

            preg_match('/VIGENCIA[:\s]*(\d{4})/u', $texto, $vigMatch);
            if (isset($vigMatch[1]) && (int)$vigMatch[1] < (int)date('Y')) {
                $errores[] = 'La INE está vencida (vigencia: ' . $vigMatch[1] . ')';
            }
        }

        return ['valida' => empty($errores), 'escaneado' => $escaneado, 'errores' => $errores];
    }

    // ── Validación Carátula de Banco ──

    public function validarCaratulaBanco(string $texto): array
    {
        $errores = [];
        $escaneado = strlen($texto) < 100;

        if ($escaneado) {
            $errores[] = '⚠ PDF escaneado — verificación manual requerida';
        } else {
            $bancosMX = ['BBVA','BANCOMER','BANAMEX','CITIBANAMEX','SANTANDER','BANORTE','HSBC','SCOTIABANK','INBURSA','BAJIO','AFIRME','MIFEL','BANCO','BANK'];
            $tieneBanco = false;
            foreach ($bancosMX as $b) {
                if (str_contains($texto, $b)) { $tieneBanco = true; break; }
            }
            if (!$tieneBanco) $errores[] = 'No se detectó institución bancaria reconocida';

            preg_match('/CLABE[:\s\w]*(\d{18})/u', $texto, $clabeMatch);
            if (!isset($clabeMatch[1])) {
                $errores[] = !str_contains($texto, 'CLABE')
                    ? 'No se encontró CLABE interbancaria (18 dígitos)'
                    : 'CLABE encontrada pero no tiene 18 dígitos';
            }

            if (!str_contains($texto, 'TITULAR') && !str_contains($texto, 'NOMBRE') && !str_contains($texto, 'CUENTA')) {
                $errores[] = 'No se encontró nombre del titular de la cuenta';
            }
        }

        return ['valida' => empty($errores), 'escaneado' => $escaneado, 'errores' => $errores];
    }

    // ── Helpers ──

    public function normalizarTexto(string $texto): string
    {
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $texto = preg_replace('/[^\x20-\x7E\n]/', ' ', $texto);
        return preg_replace('/\s+/', ' ', $texto);
    }

    public function verificarClaves(string $texto, array $claves): array
    {
        $errores = [];
        foreach ($claves as $clave => $mensajeError) {
            if (!str_contains($texto, strtoupper($clave))) {
                $errores[] = $mensajeError;
            }
        }
        return $errores;
    }

    public function validarRFC(?string $rfc): bool
    {
        if (!$rfc) return false;
        return (bool) preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/u', $rfc);
    }

    public function mesEnEspanol(int $mes): string
    {
        $meses = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];
        return $meses[$mes] ?? '';
    }
}
