<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * POR QUÉ: el archivero de Expedientes de Pago se agrupa por DEPARTAMENTO (dirección lo
     * pidió). Hasta ahora el pago no sabía a qué departamento pertenece. Esta columna lo guarda;
     * se captura al generar el pago (selector en el carrito). Lista provisional de departamentos
     * hasta que RH pase los oficiales: otros, mantenimiento, compras, imex, produccion,
     * auditoria, acatlan.
     */
    public function up(): void
    {
        Schema::table('pagos_proveedor', function (Blueprint $table) {
            $table->string('departamento', 40)->nullable()->after('es_multi_proveedor')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pagos_proveedor', function (Blueprint $table) {
            $table->dropColumn('departamento');
        });
    }
};
