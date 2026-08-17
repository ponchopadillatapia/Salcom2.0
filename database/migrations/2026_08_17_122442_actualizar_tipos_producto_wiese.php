<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza los tipo_producto existentes para alinearlos con la clasificación
 * de Wiese (Clasif6). Cambio principal: MN → MM y tipos descriptivos → prefijos cortos.
 */
return new class extends Migration
{
    public function up(): void
    {
        $cambios = [
            'MN' => 'MM',
            'HERRAMIENTAS' => 'HER',
            'REFACCIONES' => 'REF',
            'MAQUINARIA' => 'MM',
            'GASTOS' => 'GA',
            'CONTABLE' => 'MM',
            'SEGURIDAD' => 'SE',
            'SERVICIOS' => 'SER',
            'MUESTRAS' => 'MS',
            'EQUIPO' => 'MM',
            'VEHICULOS' => 'MM',
            'MOLDES' => 'MM',
        ];

        foreach ($cambios as $viejo => $nuevo) {
            DB::table('productos')
                ->where('tipo_producto', $viejo)
                ->update(['tipo_producto' => $nuevo]);

            // También actualizar el campo categoria si coincide
            DB::table('productos')
                ->where('categoria', $viejo)
                ->update(['categoria' => $nuevo]);
        }
    }

    public function down(): void
    {
        $revertir = [
            'MM' => 'MN',
            'HER' => 'HERRAMIENTAS',
            'REF' => 'REFACCIONES',
            'GA' => 'GASTOS',
            'SE' => 'SEGURIDAD',
            'SER' => 'SERVICIOS',
        ];

        foreach ($revertir as $nuevo => $viejo) {
            DB::table('productos')
                ->where('tipo_producto', $nuevo)
                ->update(['tipo_producto' => $viejo]);

            DB::table('productos')
                ->where('categoria', $nuevo)
                ->update(['categoria' => $viejo]);
        }
    }
};
