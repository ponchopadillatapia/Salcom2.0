<?php

namespace App\Console\Commands;

use App\Models\AbonoProveedor;
use App\Models\Factura;
use App\Models\PagoProveedor;
use App\Models\PagoProveedorFactura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Borra TODOS los formatos para pago (PagoProveedor) y, opcionalmente, los pagos (AbonoProveedor),
 * y regresa las facturas afectadas a 'pendiente' con monto_pagado en 0.
 *
 * POR QUÉ EXISTE: antes de que los departamentos reales empiecen a usar el flujo, hay que limpiar
 * los formatos de PRUEBA que se crearon durante el desarrollo (si no, confunden a Alexa/Karen).
 * Se hace con un comando controlado (y no SQL a mano) para: 1) correr en transacción, 2) regresar
 * las facturas a 'pendiente' y no dejarlas colgadas en 'programada', 3) pedir confirmación.
 *
 * USO:
 *   php artisan pagos:limpiar-formatos            → borra solo formatos (PagoProveedor)
 *   php artisan pagos:limpiar-formatos --con-pagos → además borra pagos (AbonoProveedor)
 */
class LimpiarFormatosPago extends Command
{
    protected $signature = 'pagos:limpiar-formatos {--con-pagos : También borra los AbonoProveedor (pagos registrados)}';

    protected $description = 'Borra los formatos para pago de prueba y regresa sus facturas a pendiente.';

    public function handle(): int
    {
        $totalFormatos = PagoProveedor::count();
        $totalLineas = PagoProveedorFactura::count();
        $conPagos = (bool) $this->option('con-pagos');
        $totalAbonos = $conPagos ? AbonoProveedor::count() : 0;

        $this->warn('Esto BORRARÁ datos de forma permanente:');
        $this->line("  • Formatos para pago (PagoProveedor): {$totalFormatos}");
        $this->line("  • Líneas de formato (pago_proveedor_facturas): {$totalLineas}");
        if ($conPagos) {
            $this->line("  • Pagos registrados (AbonoProveedor): {$totalAbonos}");
        }
        $this->line('  • Las facturas afectadas volverán a estatus "pendiente" (monto_pagado = 0).');

        // Confirmación obligatoria: no se dispara solo.
        if (! $this->confirm('¿Seguro que quieres continuar? Esto NO se puede deshacer.')) {
            $this->info('Cancelado. No se borró nada.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($conPagos) {
            // 1) Reunir las facturas ligadas a los formatos para regresarlas a pendiente.
            $facturaIds = PagoProveedorFactura::query()->pluck('factura_id')->filter()->unique();

            // Si se piden borrar también los pagos, reunir las facturas de esos abonos.
            if ($conPagos) {
                $idsAbono = DB::table('abono_proveedor_documentos')->pluck('factura_id')->filter()->unique();
                $facturaIds = $facturaIds->merge($idsAbono)->unique();
            }

            // 2) Borrar líneas + formatos.
            PagoProveedorFactura::query()->delete();
            PagoProveedor::query()->delete();

            // 3) Borrar pagos si se pidió.
            if ($conPagos) {
                DB::table('abono_proveedor_documentos')->delete();
                AbonoProveedor::query()->delete();
            }

            // 4) Regresar las facturas a 'pendiente' y limpiar lo pagado.
            //    POR QUÉ: si quedan en 'programada'/'pagada' sin su formato, quedan colgadas.
            if ($facturaIds->isNotEmpty()) {
                Factura::query()
                    ->whereIn('id', $facturaIds->all())
                    ->update(['estatus' => 'pendiente', 'monto_pagado' => 0]);
            }

            $this->info('Facturas regresadas a pendiente: '.$facturaIds->count());
        });

        $this->info('✅ Limpieza completada.');

        return self::SUCCESS;
    }
}
