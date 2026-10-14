<?php

namespace App\Services;

use App\Models\PagoProveedor;
use App\Models\ProveedorUser;

/**
 * Genera el LAYOUT de pagos para BBVA NetCash a partir de un pago/expediente.
 *
 * POR QUÉ EXISTE: Alexa (tesorería) subía los pagos al banco llenando a mano un Excel macro
 * ("Pagos Mixtos FLETES SALCOM.xlsm") con 11 columnas. Como el sistema YA tiene todos esos
 * datos (proveedor, CLABE, importe, RFC), aquí los exportamos en el MISMO formato que espera
 * BBVA NetCash, para que solo descargue y suba. Evita el llenado manual y los errores.
 *
 * Reglas del banco aplicadas (de la pestaña 'consideraciones' del Excel):
 *  - Texto en MAYÚSCULAS y SIN caracteres especiales/acentos.
 *  - CLABE de 18 dígitos para interbancarios (sin guiones).
 *  - Máximo 400 operaciones por archivo.
 * Las reglas de "cómo subir" (marcar lote mixto, cuentas dadas de alta en el banco) son
 * responsabilidad de quien sube en NetCash; se le recuerdan en pantalla.
 */
class LayoutBancarioService
{
    /** Cuenta de CARGO de Salcom (de dónde sale el dinero). Confirmado con Alexa: BBVA. */
    public const CUENTA_CARGO = '132458969';

    /** Tipo de cuenta de abono: 40 = CLABE interbancaria (lo exige el layout). */
    private const TIPO_CUENTA_CLABE = '40';

    /** Límite de NetCash. */
    public const MAX_OPERACIONES = 400;

    /** Encabezados del layout, en el orden exacto que espera BBVA NetCash. */
    public const ENCABEZADOS = [
        'CUENTA DE CARGO',
        'CUENTA DE ABONO',
        'DIVISA',
        'IMPORTE',
        'MOTIVO DE PAGO',
        'TITULAR DEL BENEFICIARIO',
        'TIPO DE CUENTA',
        'REFERENCIA NUMERICA',
        'DISPONIBILIDAD',
        'RFC',
        'IVA',
    ];

    /**
     * Construye las filas del layout para un pago (normal o multi-proveedor).
     * Una fila por FACTURA (cada factura es una transferencia).
     *
     * @return array{filas: list<list<string>>, avisos: list<string>, total: float, operaciones: int}
     */
    public function construir(PagoProveedor $pago): array
    {
        $pago->loadMissing(['lineas.factura', 'proveedor']);

        $filas = [];
        $avisos = [];
        $total = 0.0;

        foreach ($pago->lineas as $linea) {
            // Proveedor de ESTA línea (en multi-proveedor cada línea tiene el suyo).
            $codigo = (string) ($linea->codigo_proveedor ?: $pago->codigo_proveedor);
            $prov = ProveedorUser::porCualquierCodigo($codigo)->first();
            $di = is_array($prov?->datos_identificacion) ? $prov->datos_identificacion : [];

            $nombreProv = (string) ($linea->nombre_proveedor ?: ($prov->nombre ?? $codigo));
            $clabe = $this->soloDigitos((string) ($di['clabe'] ?? ''));
            $rfc = $this->limpiar((string) ($di['rfc'] ?? ''));
            $folio = (string) ($linea->folio_cfdi ?: $linea->factura?->folio_cfdi ?: '');
            $importe = (float) ($linea->total ?: $linea->neto);
            $iva = (float) ($linea->monto_iva ?? 0);

            $total += $importe;

            // Avisos por fila problemática (NO rompen; se exportan igual para que Alexa decida).
            if ($clabe === '') {
                $avisos[] = "La factura {$folio} ({$nombreProv}) no tiene CLABE capturada; revísala antes de subir.";
            } elseif (strlen($clabe) !== 18) {
                $avisos[] = "La CLABE de {$nombreProv} no tiene 18 dígitos ({$clabe}); el banco podría rechazarla.";
            }

            $filas[] = [
                self::CUENTA_CARGO,                      // A — cuenta de cargo (Salcom)
                $clabe,                                  // B — cuenta de abono (CLABE proveedor)
                'MXP',                                   // C — divisa (por ahora MXN; USD se verá luego)
                number_format($importe, 2, '.', ''),     // D — importe
                $this->limpiar($nombreProv, 40),         // E — motivo de pago (nombre del proveedor)
                $this->limpiar($nombreProv),             // F — titular del beneficiario
                self::TIPO_CUENTA_CLABE,                 // G — tipo de cuenta (40 = CLABE)
                $this->soloDigitos($folio) ?: '1',       // H — referencia numérica
                'H',                                     // I — disponibilidad (H = hoy)
                $rfc,                                    // J — RFC
                number_format($iva, 2, '.', ''),         // K — IVA
            ];
        }

        $operaciones = count($filas);
        if ($operaciones > self::MAX_OPERACIONES) {
            $avisos[] = "Este pago tiene {$operaciones} operaciones; NetCash soporta máximo "
                .self::MAX_OPERACIONES.". Hay que dividirlo en varios archivos.";
        }

        return [
            'filas' => $filas,
            'avisos' => $avisos,
            'total' => round($total, 2),
            'operaciones' => $operaciones,
        ];
    }

    /**
     * Genera el .txt EXACTO que espera BBVA NetCash (ANCHO FIJO), replicando el que produce
     * la macro del Excel "Pagos Mixtos". NO es CSV: cada campo ocupa un número fijo de
     * caracteres, rellenado con ceros o espacios. Formato de la línea interbancaria (PSC):
     *
     *   PSC + CLABE(18) + ctaCargo(18 ceros-izq) + divisa(3) + importe(16 ceros-izq, 2 dec)
     *   + motivo(30 espacios-der) + tipoCuenta(2) + bancoReceptor(3) + titular(30 espacios-der)
     *   + referencia(7 ceros-izq) + disponibilidad(1) + "0" + RFC(18 espacios) + iva(15)
     *
     * Confirmado contra el pagosmix.txt real que generó la macro de Alexa.
     */
    public function generarTxt(PagoProveedor $pago): string
    {
        $data = $this->construir($pago);
        $lineas = [];

        foreach ($data['filas'] as $fila) {
            // $fila = [cuentaCargo, clabe, divisa, importe, motivo, titular, tipoCta, ref, disp, rfc, iva]
            [$ctaCargo, $clabe, $divisa, $importe, $motivo, $titular, $tipoCta, $ref, $disp, $rfc, $iva] = $fila;

            // Banco receptor = los 3 primeros dígitos de la CLABE (ej. 072 = Banorte).
            $bancoRec = substr($clabe, 0, 3);

            $linea =
                'PSC'
                . str_pad($clabe, 18, '0', STR_PAD_RIGHT)              // CLABE (18)
                . str_pad($ctaCargo, 18, '0', STR_PAD_LEFT)            // cuenta cargo (18, ceros izq)
                . str_pad($divisa, 3, ' ', STR_PAD_RIGHT)              // divisa (3)
                . $this->importeFijo((float) $importe)                 // importe (16: ceros izq + .00)
                . $this->campoTexto($motivo, 30)                       // motivo (30 espacios der)
                . str_pad($tipoCta, 2, '0', STR_PAD_LEFT)              // tipo cuenta (2) = 40
                . str_pad($bancoRec, 3, '0', STR_PAD_LEFT)             // banco receptor (3)
                . $this->campoTexto($titular, 30)                      // titular (30 espacios der)
                . str_pad($this->soloDigitos($ref) ?: '1', 7, '0', STR_PAD_LEFT) // referencia (7)
                . str_pad($disp ?: 'H', 1, 'H')                        // disponibilidad (1)
                . '0'                                                  // separador fijo
                . str_pad('', 18, ' ')                                 // RFC (18, vacío por ahora)
                . '000000000000.00';                                   // IVA (15, cero fijo)

            $lineas[] = $linea;
        }

        // Fin de línea Windows (CRLF): el banco espera formato de Windows.
        return implode("\r\n", $lineas)."\r\n";
    }

    /** Ruta de la plantilla .xlsm (con macros + formato azul) dentro del proyecto. */
    public const PLANTILLA_XLSM = 'plantillas/pagos-mixtos.xlsm';

    /** Primera fila de DATOS en la hoja 'pagosmix' (fila 1 y 2 son títulos/encabezados). */
    private const XLSM_FILA_INICIO = 3;

    /**
     * Genera una COPIA del Excel macro "Pagos Mixtos" con las facturas del pago ya escritas en
     * la hoja 'pagosmix'. Alexa lo descarga, revisa, y con el botón "Generar archivo" (macro VBA
     * de la plantilla) produce el .txt para BBVA NetCash.
     *
     * POR QUÉ partir de la plantilla y NO crear el Excel de cero: así se conservan el formato
     * (encabezados azules, letra Courier, anchos de columna), la hoja 'consideraciones' y, lo
     * más importante, las MACROS (el botón que genera el .txt). Solo escribimos VALORES en las
     * celdas que ya existen; nunca tocamos estilos ni el VBA.
     *
     * @return string  Ruta absoluta de un archivo .xlsm temporal (quien llama debe borrarlo).
     */
    public function generarXlsm(PagoProveedor $pago): string
    {
        $data = $this->construir($pago);

        $rutaPlantilla = storage_path('app/'.self::PLANTILLA_XLSM);
        if (! is_file($rutaPlantilla)) {
            throw new \RuntimeException('No se encontró la plantilla del Excel: '.$rutaPlantilla);
        }

        // IMPORTANTE: el reader DEBE conservar el VBA (macros). Sin setReadDataOnly, PhpSpreadsheet
        // mantiene estilos; y el writer con setIncludeCharts/VBA conserva el botón "Generar archivo".
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load($rutaPlantilla);

        $hoja = $spreadsheet->getSheetByName('pagosmix') ?? $spreadsheet->getSheet(0);

        // Limpiar SOLO los valores de las filas de ejemplo (de la fila 3 hacia abajo), sin tocar
        // estilos. Recorremos hasta la última fila con datos de la plantilla.
        $ultimaFila = $hoja->getHighestDataRow();
        for ($r = self::XLSM_FILA_INICIO; $r <= $ultimaFila; $r++) {
            for ($c = 1; $c <= 11; $c++) { // columnas A..K
                $hoja->getCell([$c, $r])->setValue(null);
            }
        }

        // Escribir una fila por factura. $fila trae exactamente las 11 columnas A..K que ya usa
        // construir(), en el mismo orden del encabezado del Excel.
        $fila = self::XLSM_FILA_INICIO;
        foreach ($data['filas'] as $f) {
            // [cuentaCargo, clabe, divisa, importe, motivo, titular, tipoCta, ref, disp, rfc, iva]
            $hoja->setCellValue([1, $fila], $f[0]);                 // A cuenta de cargo
            // B — CUENTA DE ABONO (CLABE): como TEXTO, para conservar los 18 dígitos EXACTOS,
            // incluido el 0 inicial (ej. 072320006470108493). POR QUÉ texto y no número: un número
            // perdería el 0 de la izquierda (quedaría en 17 dígitos) y el banco la rechazaría.
            // El "####" que se veía NO era un error del dato, solo que la columna era angosta;
            // más abajo ensanchamos la columna B para que se vea completa.
            $hoja->setCellValueExplicit([2, $fila], $this->soloDigitos((string) $f[1]), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $hoja->setCellValue([3, $fila], $f[2]);                 // C divisa
            $hoja->setCellValue([4, $fila], (float) $f[3]);         // D importe (número para que la macro sume)
            // E — MOTIVO DE PAGO: SIEMPRE "SALCOM" (petición Brenda), no el nombre del proveedor.
            $hoja->setCellValue([5, $fila], 'SALCOM');
            $hoja->setCellValue([6, $fila], $f[5]);                 // F titular
            $hoja->setCellValue([7, $fila], $f[6]);                 // G tipo de cuenta
            // H — REFERENCIA NUMÉRICA: los ÚLTIMOS 7 dígitos de la referencia (petición Brenda).
            $refDigitos = $this->soloDigitos((string) $f[7]);
            $ref7 = $refDigitos !== '' ? substr($refDigitos, -7) : '';
            $hoja->setCellValueExplicit([8, $fila], $ref7, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $hoja->setCellValue([9, $fila], $f[8]);                 // I disponibilidad
            $hoja->setCellValueExplicit([10, $fila], $f[9], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING); // J RFC
            $hoja->setCellValue([11, $fila], (float) $f[10]);       // K IVA
            $fila++;
        }

        // Ensanchar la columna B (CUENTA DE ABONO/CLABE) para que los 18 dígitos quepan y no se
        // vea "####". 22 de ancho alcanza para los 18 dígitos con holgura.
        $hoja->getColumnDimension('B')->setWidth(22);

        // Guardar como .xlsm CONSERVANDO las macros. setIncludeCharts no es necesario, pero el
        // writer de Xlsx mantiene el VBA que venía en la plantilla automáticamente.
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false); // no recalcular (hay fórmulas/macros)

        $destino = tempnam(sys_get_temp_dir(), 'pagosmix_').'.xlsm';
        $writer->save($destino);

        // Liberar memoria (el Excel puede traer estilos pesados).
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $destino;
    }

    /** Importe en formato del banco: 16 chars, ceros a la izquierda, 2 decimales (0000000006090.33). */
    private function importeFijo(float $importe): string
    {
        return str_pad(number_format($importe, 2, '.', ''), 16, '0', STR_PAD_LEFT);
    }

    /** Texto a ancho fijo, en MAYÚSCULAS, recortado y rellenado con espacios a la derecha. */
    private function campoTexto(string $texto, int $largo): string
    {
        $t = $this->limpiar($texto, $largo);

        return str_pad($t, $largo, ' ', STR_PAD_RIGHT);
    }

    /**
     * Deja el texto en MAYÚSCULAS, sin acentos ni caracteres especiales (regla del banco).
     * Opcionalmente recorta a $max caracteres.
     */
    private function limpiar(string $texto, ?int $max = null): string
    {
        $texto = trim($texto);
        // Quitar acentos: convertir a ASCII.
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        // Dejar solo letras, números y espacios.
        $texto = preg_replace('/[^A-Za-z0-9 ]/', '', $texto);
        $texto = strtoupper(preg_replace('/\s+/', ' ', (string) $texto));
        $texto = trim((string) $texto);
        if ($max !== null && strlen($texto) > $max) {
            $texto = substr($texto, 0, $max);
        }

        return $texto;
    }

    /** Deja solo dígitos (para CLABE y referencias). */
    private function soloDigitos(string $texto): string
    {
        return preg_replace('/\D/', '', $texto) ?: '';
    }
}
