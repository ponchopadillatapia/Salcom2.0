<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            $table->decimal('score_entrega', 5, 2)->default(0)->after('activo');
            $table->decimal('score_puntualidad', 5, 2)->default(0)->after('score_entrega');
            $table->decimal('score_total', 5, 2)->default(0)->after('score_puntualidad');
            $table->boolean('aviso_privacidad_aceptado')->default(false)->after('score_total');
            $table->timestamp('aviso_privacidad_fecha')->nullable()->after('aviso_privacidad_aceptado');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            $table->dropColumn(['score_entrega', 'score_puntualidad', 'score_total', 'aviso_privacidad_aceptado', 'aviso_privacidad_fecha']);
        });
    }
};
