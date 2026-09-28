<?php

namespace App\Console\Commands;

use App\Services\ProveedorApiService;
use Illuminate\Console\Command;

/**
 * Muestra los campos crudos que devuelve el listado de proveedores de Wiese,
 * para confirmar si ya viene la moneda (CIDMONEDA / moneda) por proveedor.
 * Correr EN EL SERVIDOR. Uso: php artisan wiese:ver-campos-proveedor
 */
class VerCamposProveedor extends Command
{
    protected $signature = 'wiese:ver-campos-proveedor {--n=3 : Cuántos proveedores de muestra}';

    protected $description = 'Muestra los campos crudos del listado de proveedores de Wiese (¿trae moneda?)';

    public function handle(ProveedorApiService $api): int
    {
        $res = $api->listarProveedoresWiese();
        if (! ($res['success'] ?? false)) {
            $this->error('Falló: '.($res['message'] ?? '???'));

            return self::FAILURE;
        }

        $items = collect($res['data']['items'] ?? []);
        $this->info('Total proveedores: '.$items->count());

        $n = (int) $this->option('n');
        foreach ($items->take($n) as $i => $p) {
            $this->line('');
            $this->info("Proveedor #".($i + 1).":");
            $this->line(json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Buscar cualquier clave que suene a moneda en el primer item.
        $primero = $items->first();
        if (is_array($primero)) {
            $this->line('');
            $this->info('Claves disponibles en el primer proveedor:');
            $this->line(implode(', ', array_keys($primero)));
        }

        return self::SUCCESS;
    }
}
