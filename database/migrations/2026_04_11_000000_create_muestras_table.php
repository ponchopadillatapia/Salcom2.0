<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muestras', function (Blueprint $table) {
            $table->id();
            $table->string('lote', 50);
            $table->string('producto');
            $table->string('proveedor');
            $table->string('proveedor_contacto')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('cantidad')->default(1);
            $table->string('unidad', 30)->default('piezas');

            // Etapas del proceso — cada una guarda fecha de inicio
            $table->enum('etapa', [
                'registro',
                'recepcion',
                'validacion',
                'laboratorio',
                'piso',
                'estabilidad',
                'aprobado',
                'rechazado',
            ])->default('registro');

            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamp('fecha_recepcion')->nullable();
            $table->timestamp('fecha_validacion')->nullable();
            $table->timestamp('fecha_laboratorio')->nullable();
            $table->timestamp('fecha_piso')->nullable();
            $table->timestamp('fecha_estabilidad')->nullable();
            $table->timestamp('fecha_resolucion')->nullable();

            // Días de validación configurables (default 15-20)
            $table->integer('dias_validacion')->default(15);

            $table->text('notas')->nullable();
            $table->string('motivo_rechazo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muestras');
    }
};
