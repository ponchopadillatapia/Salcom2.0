<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mapea las familias descriptivas existentes a las familias de Wiese (Clasif2).
 * El valor original se mueve a subfamilia para no perder información.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Mapeo: patrón (LIKE) → familia Wiese Clasif2
        $mapeo = [
            // Aerosoles y derivados
            'AEROSOL%' => 'AEROSOLES',
            'ELIMINADOR DE OLORES%' => 'AEROSOLES',
            'DESINFECTANTE%' => 'AEROSOLES',
            'ABRILLANTADOR%' => 'AEROSOLES',
            'INSECTICIDA%' => 'AEROSOLES',
            'BREEZE MATIC%' => 'AEROSOLES',

            // Aromatizantes sólidos y geles
            'AROMATIZANTE%' => 'AROMATIZANTE SOLIDO',
            'CONO AROMATIZANTE%' => 'AROMATIZANTE SOLIDO',
            'LATA GEL AROMATIZANTE' => 'AROMATIZANTE SOLIDO',
            'CANASTILLA%' => 'AROMATIZANTE SOLIDO',
            'HANG AIR' => 'AROMATIZANTE SOLIDO',
            'ARO AUTO' => 'AROMATIZANTE SOLIDO',
            'REPUESTO AROMATIZANTE%' => 'AROMATIZANTE SOLIDO',

            // Líquidos
            'LIQUIDO%' => 'LIQUIDO',
            'JABON LIQUIDO%' => 'LIQUIDO',
            'JAB%N LIQUIDO%' => 'LIQUIDO',

            // Dispensadores / difusores
            'DISPENSADOR%' => 'AEROSOLES',
            'DIFUSOR%' => 'AEROSOLES',
            'MICRO CAN' => 'AEROSOLES',
            'MINI SPRAY%' => 'AEROSOLES',
            'CLIP ON' => 'AEROSOLES',

            // Pastillas
            'PASTILLA%' => 'LIQUIDO SANITARIO',
            'MOTH BALL%' => 'LIQUIDO SANITARIO',
            'BARRA %' => 'LIQUIDO SANITARIO',

            // Tapetes
            'TAPETE%' => 'PRODUCTO PARA',
            'RIM HANGER' => 'PRODUCTO PARA',

            // Producto terminado específico
            'PRODUCTO%' => 'PT',

            // Materia prima
            'MATERIA PRIMA%' => 'MATERIA PRIMA',

            // Maquilas
            'MAQUILAS' => 'MAQUILAS',

            // Herramientas
            'HERRAMIENTAS%' => 'HERRAMIENTAS TALLER',

            // Refacciones
            'REFACCIONES%' => 'REFACCIONES TALLER',
            'REF' => 'REF',

            // Insumos
            'INSUMOS' => 'INSUMOS',
            'INS' => 'INS',

            // Limpieza
            'LIMPIEZA' => 'LIQUIDO',

            // Accesorios plásticos
            'ACCESORIOS%' => 'PRODUCTO DE PLASTICO',
            'TAPA-COLADERA%' => 'PRODUCTO DE PLASTICO',
            'PORTER%' => 'PRODUCTO DE PLASTICO',
            'BA%OS PORTATILES' => 'PRODUCTO DE PLASTICO',

            // Servicios
            'SER' => 'SER',
            'MS' => 'SER',
            'SERVICIO%' => 'SERVICIO',

            // RP
            'RP' => 'R',

            // Otros
            'OTROS' => 'OR',
            'VLIJMEN' => 'OR',
            'MPI' => 'MPI',
        ];

        foreach ($mapeo as $patron => $familiaWiese) {
            // Primero mover el valor actual a subfamilia (solo si subfamilia está vacío)
            DB::table('productos')
                ->where('familia', 'like', $patron)
                ->where('familia', '!=', '')
                ->where('familia', '!=', '(NINGUNO)')
                ->where(function ($q) {
                    $q->whereNull('subfamilia')
                      ->orWhere('subfamilia', '');
                })
                ->update(['subfamilia' => DB::raw('familia')]);

            // Luego asignar la familia Wiese
            DB::table('productos')
                ->where('familia', 'like', $patron)
                ->where('familia', '!=', '')
                ->where('familia', '!=', '(NINGUNO)')
                ->update(['familia' => $familiaWiese]);
        }

        // Los que tienen (NINGUNO) los dejamos vacíos
        DB::table('productos')
            ->where('familia', '(NINGUNO)')
            ->update(['familia' => null]);
    }

    public function down(): void
    {
        // Restaurar subfamilia → familia donde se movió
        DB::table('productos')
            ->whereNotNull('subfamilia')
            ->where('subfamilia', '!=', '')
            ->update(['familia' => DB::raw('subfamilia'), 'subfamilia' => null]);
    }
};
