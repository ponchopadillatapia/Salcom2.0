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
     * Genera el contenido del archivo .txt para NetCash (una línea por operación,
     * campos separados por el delimitador que use el layout). BBVA NetCash usa el .txt
     * generado por la macro; replicamos el mismo contenido (sin encabezados).
     */
    public function generarTxt(PagoProveedor $pago): string
    {
        $data = $this->construir($pago);
        $lineas = [];
        foreach ($data['filas'] as $fila) {
            // El layout de NetCash separa por coma. Cada campo ya viene limpio (sin comas internas).
            $lineas[] = implode(',', $fila);
        }

        // Fin de línea Windows (CRLF): el banco espera formato de Windows.
        return implode("\r\n", $lineas)."\r\n";
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
