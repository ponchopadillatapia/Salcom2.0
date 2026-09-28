<?php

namespace App\Console\Commands;

use App\Services\ProveedorApiService;
use Illuminate\Console\Command;

/**
 * Escanea los proveedores de Wiese y reporta los PRIMEROS que tengan facturas
 * pendientes (saldo > 0). Sirve para encontrar rápido un proveedor con datos
 * reales para probar el flujo de pago, sin adivinar códigos uno por uno.
 *
 * Uso:
 *   php artisan wiese:buscar-con-facturas
 *   php artisan wiese:buscar-con-facturas --cuantos=5 --limite=300
 */
class BuscarProveedoresConFacturas extends Command
{
    protected $signature = 'wiese:buscar-con-facturas
        {--cuantos=5 : Cuántos proveedores CON facturas quiero encontrar antes de parar}
        {--limite=200 : Máximo de proveedores de Wiese a revisar (para no tardar demasiado)}';

    protected $description = 'Encuentra proveedores de Wiese que tengan facturas pendientes (para probar pagos)';

    public function handle(ProveedorApiService $api): int
    {
        $cuantos = max(1, (int) $this->option('cuantos'));
        $limite = max(1, (int) $this->option('limite'));

        $this->info('Trayendo directorio de proveedores de Wiese...');
        $lista = $api->listarProveedoresWiese();
        if (! ($lista['success'] ?? false)) {
            $this->error('No se pudo traer el directorio: '.($lista['message'] ?? '???'));

            return self::FAILURE;
        }

        $items = collect($lista['data']['items'] ?? [])
            ->filter(function ($p) {
                $rfc = trim((string) ($p['rfc'] ?? $p['Rfc'] ?? ''));

                // Saltar sin RFC y el genérico de extranjeros (ambiguo).
                return $rfc !== '' && strtoupper($rfc) !== 'XEXX010101000';
            })
            ->take($limite);

        $this->info("Revisando hasta {$items->count()} proveedores (busco {$cuantos} con facturas)...");

        $encontrados = 0;
        $revisados = 0;
        foreach ($items as $p) {
            $revisados++;
            $codigo = trim((string) ($p['codigo'] ?? $p['Codigo'] ?? ''));
            $rfc = trim((string) ($p['rfc'] ?? $p['Rfc'] ?? ''));
            $nombre = trim((string) ($p['nombre'] ?? $p['Nombre'] ?? ''));
            if ($codigo === '' || $rfc === '') {
                continue;
            }

            $fac = $api->listarFacturasProveedorPorRFC($rfc);
            if (! ($fac['success'] ?? false)) {
                continue;
            }
            $pend = collect($fac['data']['items'] ?? [])->where('pendiente', true);
            if ($pend->isNotEmpty()) {
                $encontrados++;
                $saldo = $pend->sum('saldo');
                $this->line('');
                $this->info("✓ CON FACTURAS: {$nombre}");
                $this->line("   codigo: {$codigo} | rfc: {$rfc} | facturas: {$pend->count()} | saldo total: \$".number_format($saldo, 2));
                $this->line("   Importar con:  php artisan wiese:importar-facturas {$codigo}");
                if ($encontrados >= $cuantos) {
                    break;
                }
            }
        }

        $this->line('');
        if ($encontrados === 0) {
            $this->warn("Revisé {$revisados} proveedores y ninguno tenía facturas pendientes. Sube --limite y reintenta.");
        } else {
            $this->info("Listo. Encontrados {$encontrados} (revisé {$revisados}). Usa cualquiera de los códigos de arriba.");
        }

        return self::SUCCESS;
    }
}
