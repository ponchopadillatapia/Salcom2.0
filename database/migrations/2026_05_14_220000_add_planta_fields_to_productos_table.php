<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            // Clasificación (del sistema de planta)
            $table->string('familia')->nullable()->after('categoria');
            $table->string('subfamilia')->nullable()->after('familia');
            $table->string('segmento_mercado')->nullable()->after('subfamilia');
            $table->string('tipo_producto')->default('General')->after('segmento_mercado');

            // Descripciones adicionales
            $table->string('codigo_alterno')->nullable()->after('codigo');
            $table->string('nombre_alterno')->nullable()->after('nombre');
            $table->string('clave_sat')->nullable()->after('nombre_alterno');
            $table->string('descripcion_corta')->nullable()->after('clave_sat');

            // Logística / empaque
            $table->integer('cajas_por_tarima')->nullable()->after('stock');
            $table->decimal('peso_bruto_caja', 10, 4)->nullable()->after('cajas_por_tarima');
            $table->decimal('peso_bruto', 10, 4)->nullable()->after('peso_bruto_caja');
            $table->decimal('piezas_por_caja', 10, 2)->nullable()->after('peso_bruto');
            $table->decimal('volumen', 12, 7)->nullable()->after('piezas_por_caja');

            // Control
            $table->boolean('maneja_lotes')->default(false)->after('volumen');
            $table->string('unidad_xml')->nullable()->after('maneja_lotes');

            // Impuestos
            $table->decimal('iva', 5, 2)->default(16.00)->after('unidad_xml');
            $table->decimal('ieps', 5, 2)->default(0)->after('iva');

            // Foto
            $table->string('foto')->nullable()->after('ieps');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'familia', 'subfamilia', 'segmento_mercado', 'tipo_producto',
                'codigo_alterno', 'nombre_alterno', 'clave_sat', 'descripcion_corta',
                'cajas_por_tarima', 'peso_bruto_caja', 'peso_bruto', 'piezas_por_caja',
                'volumen', 'maneja_lotes', 'unidad_xml', 'iva', 'ieps', 'foto',
            ]);
        });
    }
};
