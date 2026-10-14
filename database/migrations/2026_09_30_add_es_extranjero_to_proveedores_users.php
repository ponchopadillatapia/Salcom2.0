<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('proveedores_users')) {
            Schema::table('proveedores_users', function (Blueprint $table) {
                // POR QUÉ: separa el flujo mexicano (RFC, Opinión SAT, INE, CLABE, Wiese) del flujo
                // de importación (C-TPAT, en inglés). El proveedor elige su tipo al registrarse.
                if (! Schema::hasColumn('proveedores_users', 'es_extranjero')) {
                    $table->boolean('es_extranjero')->default(false)->after('tipo_persona');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('proveedores_users') && Schema::hasColumn('proveedores_users', 'es_extranjero')) {
            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->dropColumn('es_extranjero');
            });
        }
    }
};
