<?php

use App\Models\Pedido;
use App\Services\PedidoProveedorSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(PedidoProveedorSyncService::class)->sincronizar();
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pedidos', 'codigo_proveedor')) {
            return;
        }

        Pedido::query()->update([
            'codigo_proveedor' => null,
            'nombre_proveedor' => null,
        ]);
    }
};
