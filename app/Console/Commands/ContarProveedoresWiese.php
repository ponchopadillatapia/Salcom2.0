<?php

namespace App\Console\Commands;

use App\Services\ProveedorApiService;
use Illuminate\Console\Command;

/**
 * Cuenta los proveedores de Wiese por moneda (MXN/USD) y por estado (activo/inactivo),
 * para decidir cómo filtrar la precarga (ej. solo MXN, solo activos) y reducir llamadas.
 * Correr EN EL SERVIDOR. Uso: php artisan wiese:contar-proveedores
 */
class ContarProveedoresWiese extends Command
{
    protected $signature = 'wiese:contar-proveedores';

    protected $description = 'Cuenta proveedores de Wiese por moneda y estado (para filtrar la precarga)';

    public function handle(ProveedorApiService $api): int
    {
        $res = $api->listarProveedoresWiese();
        if (! ($res['success'] ?? false)) {
            $this->error('Falló: '.($res['message'] ?? '???'));

            return self::FAILURE;
        }

        $items = collect($res['data']['items'] ?? []);
        $total = $items->count();

        $mxn = $items->filter(fn ($p) => (string) ($p['moneda'] ?? '') === '1')->count();
        $usd = $items->filter(fn ($p) => (string) ($p['moneda'] ?? '') === '2')->count();
        $otraMoneda = $total - $mxn - $usd;

        $activos = $items->filter(fn ($p) => ($p['activo'] ?? false) === true)->count();
        $inactivos = $items->filter(fn ($p) => ($p['activo'] ?? false) === false)->count();

        // Combinaciones útiles
        $mxnActivos = $items->filter(fn ($p) => (string) ($p['moneda'] ?? '') === '1' && ($p['activo'] ?? false) === true)->count();

        $this->info('=== CONTEO DE PROVEEDORES DE WIESE ===');
        $this->line("TOTAL: {$total}");
        $this->line('');
        $this->line("Por MONEDA:");
        $this->line("  MXN (1): {$mxn}");
        $this->line("  USD (2): {$usd}");
        $this->line("  otra/sin moneda: {$otraMoneda}");
        $this->line('');
        $this->line("Por ESTADO:");
        $this->line("  activos:   {$activos}");
        $this->line("  inactivos: {$inactivos}");
        $this->line('');
        $this->line("MXN + activos (posible filtro de precarga): {$mxnActivos}");
        $this->line('');
        if ($activos === 0) {
            $this->warn('OJO: TODOS vienen como inactivo (activo=false). Ese campo NO sirve para filtrar.');
        }

        return self::SUCCESS;
    }
}
