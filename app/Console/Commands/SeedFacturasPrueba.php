<?php

namespace App\Console\Commands;

use App\Models\Factura;
use App\Models\ProveedorUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Genera facturas de PRUEBA para un proveedor (default SAID001) para probar
 * el flujo completo de pagos: pendiente → programada → pagada → liquidada.
 *
 * Uso:  php artisan facturas:seed-prueba            (50 para SAID001)
 *       php artisan facturas:seed-prueba SAID001 20
 *       php artisan facturas:seed-prueba --limpiar   (borra las de prueba antes)
 */
class SeedFacturasPrueba extends Command
{
    protected $signature = 'facturas:seed-prueba {codigo=SAID001} {cantidad=50} {--limpiar}';

    protected $description = 'Genera facturas de prueba para un proveedor (flujo de pagos)';

    public function handle(): int
    {
        $codigo = (string) $this->argument('codigo');
        $cantidad = (int) $this->argument('cantidad');

        $prov = ProveedorUser::where('codigo', $codigo)
            ->orWhere('id_proveedor', $codigo)
            ->first();

        if (! $prov) {
            $this->error("No se encontró el proveedor {$codigo}.");

            return self::FAILURE;
        }

        $rfcEmisor = $prov->rfc ?? ($prov->datos_identificacion['rfc'] ?? 'SPE230801AA1');
        $rfcReceptor = config('facturas.rfc_receptor', 'ISA951017A10');

        // Marca de prueba en notas para poder limpiarlas después.
        $marca = '[PRUEBA-SEED]';

        if ($this->option('limpiar')) {
            $borradas = Factura::where('codigo_proveedor', $codigo)
                ->where('notas', 'like', "%{$marca}%")
                ->forceDelete();
            $this->warn("Borradas {$borradas} facturas de prueba anteriores.");
        }

        $formasPago = ['03']; // Transferencia
        $usosCfdi = ['G03'];  // Gastos en general
        $bar = $this->output->createProgressBar($cantidad);
        $bar->start();

        for ($i = 1; $i <= $cantidad; $i++) {
            // Montos realistas variados
            $subtotal = round(mt_rand(2000, 80000) + mt_rand(0, 99) / 100, 2);
            $iva = round($subtotal * 0.16, 2);
            // ~30% con retenciones (servicios)
            $conRet = ($i % 3 === 0);
            $retIva = $conRet ? round($subtotal * 0.106667, 2) : 0.0;
            $retIsr = $conRet ? round($subtotal * 0.0125, 2) : 0.0;
            $total = round($subtotal + $iva - $retIva - $retIsr, 2);

            $serie = 'A';
            $folio = 1000 + $i;
            $uuid = strtoupper((string) Str::uuid());
            $diasPlazo = [15, 30, 45][$i % 3];
            // Facturas repartidas en los últimos ~40 días
            $creada = now()->subDays(mt_rand(0, 40))->setTime(mt_rand(8, 18), mt_rand(0, 59));
            $vencimiento = $creada->copy()->addDays($diasPlazo);

            Factura::create([
                'folio_cfdi' => $serie.$folio,
                'uuid_cfdi' => $uuid,
                'codigo_cliente' => null,
                'codigo_proveedor' => $codigo,
                'regimen_fiscal' => '601',
                'es_fletera' => false,
                'monto' => $subtotal,
                'monto_iva' => $iva,
                'retencion_iva' => $retIva,
                'retencion_isr' => $retIsr,
                'total' => $total,
                'monto_pagado' => 0,
                'estatus' => 'pendiente',
                'fecha_vencimiento' => $vencimiento,
                'dias_plazo' => $diasPlazo,
                'archivo_pdf' => null,
                'archivo_xml' => null,
                'notas' => $marca.' factura de prueba',
                'validacion_detalle' => [
                    'uuid' => $uuid,
                    'serie' => $serie,
                    'folio' => (string) $folio,
                    'rfc_emisor' => $rfcEmisor,
                    'nombre_emisor' => $prov->nombre,
                    'rfc_receptor' => $rfcReceptor,
                    'regimen_fiscal' => '601',
                    'forma_pago' => $formasPago[0],
                    'metodo_pago' => 'PPD',
                    'uso_cfdi' => $usosCfdi[0],
                    'moneda' => 'MXN',
                    'producto' => 'Servicios de prueba',
                    'descripcion' => 'Servicios de prueba',
                    'subtotal' => $subtotal,
                    'iva' => $iva,
                    'retencion_iva' => $retIva,
                    'retencion_isr' => $retIsr,
                    'total' => $total,
                    'fecha' => $creada->toDateTimeString(),
                ],
            ])->forceFill(['created_at' => $creada, 'updated_at' => $creada])->save();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Listas {$cantidad} facturas de prueba PENDIENTES para {$prov->nombre} ({$codigo}).");
        $this->line('RFC emisor: '.$rfcEmisor.' · receptor: '.$rfcReceptor);
        $this->line('Para borrarlas: php artisan facturas:seed-prueba '.$codigo.' --limpiar');

        return self::SUCCESS;
    }
}
