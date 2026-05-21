<?php

use App\Models\Pedido;
use App\Models\ProveedorUser;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private array $porFolio = [
        'PED-2025-089' => ['102003240', 'Distribuidora Nacional SA de CV'],
        'PED-2025-102' => ['102003241', 'Materiales Industriales del Bajío'],
        'PED-2026-008' => ['102003240', 'Distribuidora Nacional SA de CV'],
        'PED-2026-021' => ['102003241', 'Materiales Industriales del Bajío'],
        'PED-2026-035' => ['102003240', 'Distribuidora Nacional SA de CV'],
        'PED-2026-048' => ['102003240', 'Distribuidora Nacional SA de CV'],
        'PED-2026-055' => ['102003242', 'Juan Pérez López'],
        'PED-2026-061' => ['102003240', 'Distribuidora Nacional SA de CV'],
        'PED-2026-068' => ['102003241', 'Materiales Industriales del Bajío'],
        'PED-2025-075' => ['102003240', 'Distribuidora Nacional SA de CV'],
    ];

    public function up(): void
    {
        foreach ($this->porFolio as $folio => [$codigo, $nombre]) {
            Pedido::where('folio', $folio)->update([
                'codigo_proveedor' => $codigo,
                'nombre_proveedor' => $nombre,
            ]);
        }

        $proveedores = ProveedorUser::orderBy('id')->get(['codigo_compras', 'nombre']);
        if ($proveedores->isEmpty()) {
            return;
        }

        $i = 0;
        Pedido::whereNull('codigo_proveedor')->orderBy('id')->each(function (Pedido $pedido) use ($proveedores, &$i) {
            $prov = $proveedores[$i % $proveedores->count()];
            $pedido->update([
                'codigo_proveedor' => $prov->codigo_compras,
                'nombre_proveedor' => $prov->nombre,
            ]);
            $i++;
        });
    }

    public function down(): void
    {
        Pedido::query()->update([
            'codigo_proveedor' => null,
            'nombre_proveedor' => null,
        ]);
    }
};
