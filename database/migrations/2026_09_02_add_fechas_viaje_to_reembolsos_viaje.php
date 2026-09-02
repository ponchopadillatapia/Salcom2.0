<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reembolsos_viaje')) {
            Schema::table('reembolsos_viaje', function (Blueprint $table) {
                if (! Schema::hasColumn('reembolsos_viaje', 'fecha_salida')) {
                    $table->date('fecha_salida')->nullable()->after('departamento');
                }
                if (! Schema::hasColumn('reembolsos_viaje', 'fecha_regreso')) {
                    $table->date('fecha_regreso')->nullable()->after('fecha_salida');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reembolsos_viaje')) {
            Schema::table('reembolsos_viaje', function (Blueprint $table) {
                $table->dropColumn(['fecha_salida', 'fecha_regreso']);
            });
        }
    }
};
