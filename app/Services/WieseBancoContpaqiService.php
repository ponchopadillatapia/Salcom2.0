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

            // 1) Crear el movimiento en WieseBanco con su NUM consecutivo + balance.
            $movimiento = DB::transaction(function () use ($cuenta, $factura, $folioFactura, $monto, $nombreProveedor, $codigoProveedor) {
                $num = $cuenta->siguienteFolio();
                $saldoNuevo = (float) $cuenta->saldo_actual - $monto; // es un pago: resta

                $mov = MovimientoBancario::create([
                    'cuenta_id' => $cuenta->id,
                    'fecha' => now()->toDateString(),
                    'num' => $num,
                    'payee' => $nombreProveedor,
                    'categoria' => 'PROVEEDOR',
                    'memo' => $folioFactura,          // el folio de la factura (como Quicken)
                    'payment' => $monto,
                    'deposit' => 0,
                    'balance' => $saldoNuevo,
                    'codigo_proveedor' => $codigoProveedor,
                    'estatus' => 'pendiente_contpaqi', // aún no confirmado en Contpaqi
                ]);

                $cuenta->saldo_actual = $saldoNuevo;
                $cuenta->save();

                return $mov;
            });
            $creados++;

            // 2) Registrar el pago en Contpaqi vía la API C#. Si falla, el movimiento queda pendiente.
            $idDocumento = $this->registrarEnContpaqi($movimiento, $codigoProveedor);
            if ($idDocumento !== null) {
                $movimiento->iddocumento_contpaqi = $idDocumento;
                $movimiento->estatus = 'enviado';
                $movimiento->save();
            } else {
                $erroresContpaqi++;
            }
        }

        return ['creados' => $creados, 'errores_contpaqi' => $erroresContpaqi];
    }

    /**
     * Llama a la API C# (APIPortalWeb) para crear el pago en Contpaqi.
     * Devuelve el idDocumento, o null si falló (API apagada, error, etc.).
     *
     * POR QUÉ try/catch amplio: la API puede estar apagada o tardar; nunca debe
     * tumbar el flujo de abono. Si falla, se registra en el log y se reintenta luego.
     */
    private function registrarEnContpaqi(MovimientoBancario $movimiento, string $codigoProveedor): ?int
    {
        $base = rtrim((string) config('services.contpaqi_api.url', 'https://localhost:7090'), '/');

        try {
            $respuesta = Http::timeout(30)
                ->withoutVerifying() // la API corre en https local con certificado de desarrollo
                ->asJson()
                ->post($base.'/api/PagoProveedor/CrearPago', [
                    // Por ahora CrearPago usa valores fijos del lado C# (ORPACK/concepto 28).
                    // Cuando la API acepte parámetros, aquí mandaremos folioQuicken=$movimiento->num,
                    // codigo_proveedor, importe, etc.
                    'folioQuicken' => $movimiento->num,
                    'codigoProveedor' => $codigoProveedor,
                    'importe' => (float) $movimiento->payment,
                ]);

            if (! $respuesta->ok()) {
                Log::warning('[WieseBanco→Contpaqi] Respuesta no OK', ['status' => $respuesta->status(), 'body' => $respuesta->body()]);

                return null;
            }

            $json = $respuesta->json();
            // La API devuelve idDocumento cuando crea bien.
            $id = $json['idDocumento'] ?? null;

            return $id !== null ? (int) $id : null;
        } catch (\Throwable $e) {
            Log::warning('[WieseBanco→Contpaqi] No se pudo llamar a la API C#: '.$e->getMessage());

            return null;
        }
    }
}
