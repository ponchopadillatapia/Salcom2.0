<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empleados')) {
            Schema::table('empleados', function (Blueprint $table) {
                // POR QUÉ: dirección maneja tarjetas de dos bancos (INNTEC y BBVA).
                // Guardamos el banco para poder filtrar los empleados por el origen de su tarjeta.
                if (! Schema::hasColumn('empleados', 'banco_tarjeta')) {
                    $table->string('banco_tarjeta', 20)->nullable()->after('titular_cuenta');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('empleados')) {
            Schema::table('empleados', function (Blueprint $table) {
                $table->dropColumn('banco_tarjeta');
            });
        }
    }
};
