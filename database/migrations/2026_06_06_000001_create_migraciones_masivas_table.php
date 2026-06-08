<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla para registrar las migraciones masivas de productos del sistema viejo al nuevo.
 * Cada registro representa una carga de Excel con ~3,000 productos que se procesan en lotes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('migraciones_masivas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->comment('Admin que inició la migración');
            $table->string('archivo_path')->comment('Ruta del Excel subido');
            $table->integer('total_productos')->default(0)->comment('Total de productos en el Excel');
            $table->integer('productos_procesados')->default(0)->comment('Productos procesados exitosamente');
            $table->integer('productos_error')->default(0)->comment('Productos con error de procesamiento');
            $table->integer('lotes_total')->default(0)->comment('Total de lotes (batches de 50)');
            $table->integer('lotes_completados')->default(0)->comment('Lotes procesados');
            $table->enum('estatus', ['pendiente', 'procesando', 'completado', 'error'])->default('pendiente');
            $table->string('resultado_path')->nullable()->comment('Ruta del Excel de resultados');
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admin_users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('migraciones_masivas');
    }
};
