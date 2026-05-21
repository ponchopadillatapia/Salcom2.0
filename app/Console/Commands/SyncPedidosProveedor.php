<?php

namespace App\Console\Commands;

use App\Services\PedidoProveedorSyncService;
use Illuminate\Console\Command;

class SyncPedidosProveedor extends Command
{
    protected $signature = 'pedidos:sync-proveedores';

    protected $description = 'Vincula proveedores a pedidos que no tienen codigo_proveedor (útil en servidor tras deploy)';

    public function handle(PedidoProveedorSyncService $sync): int
    {
        if (! $sync->columnasDisponibles()) {
            $this->error('Faltan columnas codigo_proveedor/nombre_proveedor. Ejecuta: php artisan migrate --force');

            return self::FAILURE;
        }

        $n = $sync->sincronizar();
        $this->info("Pedidos actualizados: {$n}");

        return self::SUCCESS;
    }
}
