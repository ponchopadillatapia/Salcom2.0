<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            // Indica si el proveedor está registrado en el REPSE (STPS).
            $table->boolean('es_repse')->default(false)->after('tipo_persona');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            $table->dropColumn('es_repse');
        });
    }
};
