<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('productos')
            ->where('codigo', 'MPI0536')
            ->update([
                'proveedor_nombre' => 'Acela Bolaños',
                'proveedor_tipo' => 'admin',
            ]);
    }

    public function down(): void
    {
        DB::table('productos')
            ->where('codigo', 'MPI0536')
            ->update([
                'proveedor_nombre' => null,
                'proveedor_tipo' => null,
            ]);
    }
};
