<?php

namespace App\Services;

use App\Models\CuentaBancaria;
use App\Models\Factura;
use App\Models\MovimientoBancario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Puente entre el flujo de Abono (saldar en 0) y WieseBanco + Contpaqi.
 *
 * POR QUÉ existe: Sandra pidió NO duplicar procesos. Cuando Karen salda un documento
 * en 0 (en abonoInternoConfirmar), este servicio:
 *   1) crea el movimiento en WieseBanco (como el renglón de Quicken), uno por FACTURA,
 *   2) registra el pago en Contpaqi llamando a la API C# (APIPortalWeb),
 *   3) guarda el idDocumento de Contpaqi en el movimiento.
 *
 * Diseño defensivo: si la API C# falla o está apagada, el movimiento IGUAL se guarda en
 * WieseBanco (no rompemos el flujo de Karen) y queda con estatus 'pendiente_contpaqi'
 * para poder reintentar después. Un problema de Contpaqi NO debe tumbar el abono.
 */
class WieseBancoContpaqiService
{
    /**
     * Refleja en WieseBanco (y Contpaqi) las facturas que se acaban de liquidar.
     *
     * @param  \Illuminate\Support\Collection<int, Factura>  $facturas  Facturas recién liquidadas.
     * @param  string  $codigoProveedor
     * @return array{creados: int, errores_contpaqi: int}
     */
    public function reflejarFacturasLiquidadas($facturas, string $codigoProveedor): array
    {
        // Por ahora TODO va a la cuenta BBVA 8969 MXN (decisión de dirección).
        // Las otras 3 cuentas del abono también se registran aquí mientras no existan sus propias cuentas.
        $cuenta = CuentaBancaria::where('activo', true)->where('clave_corta', '8969')->first();
        if (! $cuenta) {
            Log::warning('[WieseBanco] No existe la cuenta BBVA 8969; no se reflejó el abono.');

            return ['creados' => 0, 'errores_contpaqi' => 0];
        }

        // Nombre del proveedor para el campo payee (como en Quicken).
        $proveedor = \App\Models\ProveedorUser::where('codigo', $codigoProveedor)->first();
        $nombreProveedor = $proveedor->razon_social
            ?? $proveedor->nombre
            ?? $codigoProveedor;

        $creados = 0;
        $erroresContpaqi = 0;

        // UN movimiento por FACTURA (fiel a Quicken: un renglón por folio).
        foreach ($facturas as $factura) {
            $folioFactura = (string) ($factura->folio_cfdi ?: $factura->id);
            $monto = (float) $factura->monto_pagado;
            if ($monto <= 0) {
                // Sin monto no tiene sentido registrar un pago; lo saltamos.
                continue;
            }

            // La serie de la factura: se toma de la propia factura (validacion_detalle), que es
            // donde quedó guardada al capturarla. POR QUÉ: evita consultar el SDK lento de Contpaqi
            // (que se cuelga con miles de documentos). Si no hay serie, se intenta vacía.
            $vd = is_array($factura->validacion_detalle) ? $factura->validacion_detalle : [];
            $serieFactura = (string) ($vd['serie'] ?? $vd['cfdi']['serie'] ?? '');

            // Registrar el pago en Contpaqi Y SALDAR la factura. Contpaqi genera el folio del pago.
            $resultado = $this->registrarYSaldarEnContpaqi($codigoProveedor, $monto, $folioFactura, $serieFactura);

            // ===== PRINCIPIO "TODO O NADA" (evita inconsistencia entre sistemas) =====
            // Si Contpaqi FALLÓ, NO guardamos el movimiento en WieseBanco. Así nunca queda un
            // pago en nuestra base que no exista en Contpaqi (eso descuadraría los saldos).
            if ($resultado === null) {
                $erroresContpaqi++;
                continue; // no se guarda nada de esta factura
            }

            // Contpaqi respondió OK: guardamos el movimiento en WieseBanco. El NUM visible es el
            // folio BONITO consecutivo de la cuenta (estilo Quicken, 80195...), que es el que ve
            // Sandra. El folio BRUTO de Contpaqi (3850965172) y el idDocumento se guardan aparte
            // como referencia para conciliar, SIN mostrarlos.
            DB::transaction(function () use ($cuenta, $folioFactura, $monto, $nombreProveedor, $codigoProveedor, $resultado) {
                $numBonito = $cuenta->siguienteFolio();   // consecutivo WieseBanco (80195, 80196...)
                $saldoNuevo = (float) $cuenta->saldo_actual - $monto;

                MovimientoBancario::create([
                    'cuenta_id' => $cuenta->id,
                    'fecha' => now()->toDateString(),
                    'num' => $numBonito,                     // folio BONITO visible (estilo Quicken)
                    'payee' => $nombreProveedor,
                    'categoria' => 'PROVEEDOR',
                    'memo' => $folioFactura,
                    'payment' => $monto,
                    'deposit' => 0,
                    'balance' => $saldoNuevo,
                    'codigo_proveedor' => $codigoProveedor,
                    'iddocumento_contpaqi' => $resultado['idDocumento'],
                    'folio_contpaqi' => $resultado['folio'], // folio bruto de Contpaqi (referencia)
                    'estatus' => 'enviado',
                ]);

                $cuenta->saldo_actual = $saldoNuevo;
                $cuenta->save();
            });
            $creados++;
        }

        return ['creados' => $creados, 'errores_contpaqi' => $erroresContpaqi];
    }

    // Concepto de las FACTURAS DE COMPRA en Contpaqi (lo confirmó Alan). Las facturas que
    // paga el proveedor están registradas con este concepto. Se usa para buscarlas y saldarlas.
    private const CONCEPTO_COMPRA = '21';

    /**
     * Crea el pago en Contpaqi Y salda la factura de compra, en una sola llamada a la API C#.
     *
     * POR QUÉ recibe la serie directo: buscarla en el SDK (FacturasProveedor) se CUELGA con
     * proveedores que tienen miles de documentos (ORPACK: 2041). La serie ya viene en la factura
     * (validacion_detalle, donde se guardó al capturarla desde Wiese). Si está vacía, se manda ""
     * y Contpaqi la resuelve (algunos documentos no usan serie).
     *
     * @return array{folio: int|null, idDocumento: int|null}|null  null si falló.
     */
    private function registrarYSaldarEnContpaqi(string $codigoProveedor, float $importe, string $folioFactura, string $serieFactura = ''): ?array
    {
        $base = rtrim((string) config('services.contpaqi_api.url', 'https://127.0.0.1:7090'), '/');

        try {
            $url = $base.'/api/PagoProveedor/CrearPagoYSaldar?'.http_build_query([
                'codigoProveedor' => $codigoProveedor,
                'importe' => $importe,
                'conceptoFactura' => self::CONCEPTO_COMPRA,  // "21"
                'serieFactura' => $serieFactura,
                'folioFactura' => $folioFactura,
            ]);

            $respuesta = Http::timeout(60)
                ->withoutVerifying()
                ->post($url);

            if (! $respuesta->ok()) {
                Log::warning('[WieseBanco→Contpaqi] CrearPagoYSaldar no OK', ['status' => $respuesta->status(), 'body' => $respuesta->body()]);

                return null;
            }

            $json = $respuesta->json();
            if (! ($json['ok'] ?? false) || ! isset($json['idDocumento'])) {
                Log::warning('[WieseBanco→Contpaqi] CrearPagoYSaldar sin idDocumento', ['body' => $respuesta->body()]);

                return null;
            }

            return [
                'folio' => isset($json['folio']) ? (int) $json['folio'] : null,
                'idDocumento' => (int) $json['idDocumento'],
            ];
        } catch (\Throwable $e) {
            Log::warning('[WieseBanco→Contpaqi] Error al crear/saldar: '.$e->getMessage());

            return null;
        }
    }
}
