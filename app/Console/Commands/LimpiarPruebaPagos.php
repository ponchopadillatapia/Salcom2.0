<?php

namespace App\Console\Commands;

use App\Models\Factura;
use App\Models\ProveedorUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Borra la basura de pruebas del flujo de pagos: proveedores fake y sus facturas
 * que quedaron de sesiones anteriores y estorban en "Formato para pago".
 *
 * SOLO borra los códigos indicados explícitamente (lista blanca de basura). NO toca
 * proveedores reales (ej. 213004004 ASCENCIO ni ningún otro que no esté en la lista).
 *
 * Por seguridad:
 *   - Sin --force: solo MUESTRA lo que borraría (simulación).
 *   - Con --force: borra de verdad, dentro de una transacción.
 *
 * Uso:
 *   php artisan wiese:limpiar-prueba-pagos            (simula)
 *   php artisan wiese:limpiar-prueba-pagos --force    (borra)
 */
class LimpiarPruebaPagos extends Command
{
    protected $signature = 'wiese:limpiar-prueba-pagos {--force : Borrar de verdad (sin esto solo simula)}';

    protected $description = 'Borra proveedores/facturas de prueba (P55, ADMIN-8, 102003241) del flujo de pagos';

    /**
     * Códigos BASURA a eliminar (confirmados con Said). NO incluir reales aquí.
     * Se comparan contra codigo, id_proveedor y codigo_proveedor.
     */
    private array $codigosBasura = ['P55', 'ADMIN-8', '102003241'];

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        // 1) Facturas cuyo codigo_proveedor esté en la lista basura.
        $facturas = Factura::withTrashed()
            ->whereIn('codigo_proveedor', $this->codigosBasura)
            ->get();

        // 2) Proveedores fake: por codigo o id_proveedor en la lista basura,
        //    o por nombre "Aneso Cominu" (el ADMIN-8). NUNCA por RFC (para no tocar reales).
        $proveedores = ProveedorUser::withTrashed()
            ->where(function ($q) {
                $q->whereIn('codigo', $this->codigosBasura)
                    ->orWhereIn('id_proveedor', $this->codigosBasura)
                    ->orWhere('nombre', 'like', '%Aneso Cominu%');
            })
            ->get();

        $this->info('Se encontró lo siguiente para borrar (SOLO basura de prueba):');
        $this->line('  Facturas: '.$facturas->count());
        foreach ($facturas as $f) {
            $this->line("    - factura #{$f->id} · cod {$f->codigo_proveedor} · folio {$f->folio_cfdi} · \$".number_format((float) $f->total, 2));
        }
        $this->line('  Proveedores: '.$proveedores->count());
        foreach ($proveedores as $p) {
            $this->line("    - proveedor #{$p->id} · {$p->codigo} · {$p->nombre}");
        }

        if ($facturas->isEmpty() && $proveedores->isEmpty()) {
            $this->info('Nada que borrar. Ya está limpio.');

            return self::SUCCESS;
        }

        if (! $force) {
            $this->warn('');
            $this->warn('MODO SIMULACIÓN: no se borró nada. Para borrar de verdad, corre:');
            $this->warn('  php artisan wiese:limpiar-prueba-pagos --force');

            return self::SUCCESS;
        }

        // Borrado real, protegido en transacción. ORDEN IMPORTANTE por foreign keys:
        // 1) líneas de pago que referencian estas facturas (pago_proveedor_facturas)
        // 2) los pagos (lotes) que quedaron vacíos / eran de estos proveedores basura
        // 3) las facturas
        // 4) los proveedores
        // POR QUÉ: no se puede borrar una factura si un pago la referencia (constraint RESTRICT).
        DB::transaction(function () use ($facturas, $proveedores) {
            $facturaIds = $facturas->pluck('id')->all();
            $codigosProv = $this->codigosBasura;

            // 1) Borrar líneas de pago que usan estas facturas.
            //    PagoProveedorFactura NO usa soft-deletes → delete() normal (borra de verdad).
            \App\Models\PagoProveedorFactura::whereIn('factura_id', $facturaIds)->delete();

            // 2) Borrar los pagos (lotes) de estos proveedores basura.
            //    PagoProveedor tampoco usa soft-deletes → delete() normal.
            \App\Models\PagoProveedor::whereIn('codigo_proveedor', $codigosProv)
                ->get()
                ->each(function ($pago) {
                    // Por si tiene más líneas, limpiarlas primero.
                    \App\Models\PagoProveedorFactura::where('pago_id', $pago->id)->delete();
                    $pago->delete();
                });

            // 3) Facturas.
            foreach ($facturas as $f) {
                $f->forceDelete();
            }

            // 4) Proveedores.
            foreach ($proveedores as $p) {
                $p->forceDelete();
            }
        });

        $this->info('');
        $this->info("Listo. Borrados: {$facturas->count()} facturas y {$proveedores->count()} proveedores de prueba.");
        $this->line('Recarga Formato para pago: ya no deben salir P55, ADMIN-8 ni 102003241.');

        return self::SUCCESS;
    }
}
