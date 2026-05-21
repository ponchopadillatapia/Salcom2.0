<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Pedido;
use App\Models\ProveedorUser;
use Illuminate\Support\Facades\Schema;

class PedidoProveedorSyncService
{
    /** @var array<string, array{0: string, 1: string}> */
    private const POR_FOLIO = [
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

    public function columnasDisponibles(): bool
    {
        return Schema::hasTable('pedidos')
            && Schema::hasColumn('pedidos', 'codigo_proveedor')
            && Schema::hasColumn('pedidos', 'nombre_proveedor');
    }

    public function sincronizar(): int
    {
        if (! $this->columnasDisponibles()) {
            return 0;
        }

        $actualizados = 0;

        foreach (self::POR_FOLIO as $folio => [$codigo, $nombre]) {
            $actualizados += Pedido::where('folio', $folio)
                ->where(function ($q) use ($codigo) {
                    $q->whereNull('codigo_proveedor')
                        ->orWhere('codigo_proveedor', '!=', $codigo)
                        ->orWhereNull('nombre_proveedor');
                })
                ->update([
                    'codigo_proveedor' => $codigo,
                    'nombre_proveedor' => $nombre,
                ]);
        }

        Pedido::where(function ($q) {
            $q->whereNull('codigo_proveedor')->orWhereNull('nombre_proveedor');
        })
            ->orderBy('id')
            ->each(function (Pedido $pedido) use (&$actualizados) {
                if ($this->vincularDesdeFactura($pedido)) {
                    $actualizados++;

                    return;
                }
                if ($this->vincularDesdeProveedorRotativo($pedido)) {
                    $actualizados++;
                }
            });

        return $actualizados;
    }

    private function vincularDesdeFactura(Pedido $pedido): bool
    {
        $factura = Factura::where('pedido_id', $pedido->id)
            ->whereNotNull('codigo_proveedor')
            ->first();

        if (! $factura) {
            return false;
        }

        $prov = ProveedorUser::where('codigo_compras', $factura->codigo_proveedor)->first();

        $pedido->update([
            'codigo_proveedor' => $factura->codigo_proveedor,
            'nombre_proveedor' => $prov !== null ? $prov->nombre : $factura->codigo_proveedor,
        ]);

        return true;
    }

    private function vincularDesdeProveedorRotativo(Pedido $pedido): bool
    {
        $proveedores = ProveedorUser::orderBy('id')->get(['codigo_compras', 'nombre']);
        if ($proveedores->isEmpty()) {
            return false;
        }

        $prov = $proveedores[$pedido->id % $proveedores->count()];

        $pedido->update([
            'codigo_proveedor' => $prov->codigo_compras,
            'nombre_proveedor' => $prov->nombre,
        ]);

        return true;
    }
}
