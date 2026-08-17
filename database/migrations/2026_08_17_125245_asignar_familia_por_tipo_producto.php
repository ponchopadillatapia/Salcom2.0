<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Asigna familia Wiese (Clasif2) a los productos que no tenían familia,
 * basándose en su tipo_producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        $mapeo = [
            'MM' => 'MAQUINARIA',
            'ME' => 'MATERIAL DE EMPAQUE',
            'MP' => 'MATERIA PRIMA',
            'MPI' => 'MPI',
            'PT' => 'PT',
            'MEI' => 'MEI',
            'MO' => 'MAQUILAS',
            'MR' => 'MATERIA PRIMA',
            'MS' => 'SER',
            'MT' => 'MAQUINARIA',
            'HER' => 'HER',
            'HET' => 'HERRAMIENTAS TALLER',
            'REF' => 'REF',
            'REP' => 'REFACCIONES TALLER',
            'RET' => 'RE',
            'INS' => 'INS',
            'GAS' => 'GAS',
            'GA' => 'GA',
            'SE' => 'SE',
            'SER' => 'SER',
            'RP' => 'R',
            'AR' => 'ARE',
            'EA' => 'EM',
            'PR' => 'PM',
            'RO' => 'RE',
            'PZA' => 'PZA',
            'PTT' => 'PT',
            'AEROSOL' => 'AEROSOLES',
            'ENSAMBLES' => 'EM',
            'INSUMOS' => 'INSUMOS',
        ];

        foreach ($mapeo as $tipo => $familia) {
            DB::table('productos')
                ->where('tipo_producto', $tipo)
                ->where(function ($q) {
                    $q->whereNull('familia')->orWhere('familia', '');
                })
                ->update(['familia' => $familia]);
        }
    }

    public function down(): void
    {
        // Volver a dejar sin familia los que se asignaron
        DB::table('productos')
            ->whereIn('tipo_producto', [
                'MM', 'ME', 'MP', 'MPI', 'PT', 'MEI', 'MO', 'MR', 'MS', 'MT',
                'HER', 'HET', 'REF', 'REP', 'RET', 'INS', 'GAS', 'GA', 'SE',
                'SER', 'RP', 'AR', 'EA', 'PR', 'RO', 'PZA', 'PTT', 'AEROSOL',
                'ENSAMBLES', 'INSUMOS',
            ])
            ->whereNull('subfamilia')
            ->update(['familia' => null]);
    }
};
