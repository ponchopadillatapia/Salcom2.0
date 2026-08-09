<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('solicitudes_modificacion_datos')) {
            return;
        }

        Schema::create('solicitudes_modificacion_datos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id')->index();
            $table->string('campo', 40)->default('nombre'); // nombre | razon_social
            $table->string('valor_actual', 255)->nullable();
            $table->string('valor_propuesto', 255);
            $table->string('tipo_persona', 40)->nullable();
            $table->text('motivo')->nullable();
            $table->string('estatus', 30)->default('pendiente'); // pendiente | aprobada | rechazada
            $table->string('archivo_cif')->nullable();
            $table->string('archivo_acta')->nullable();
            $table->json('resultado_ia')->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();

            $table->index(['proveedor_id', 'estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_modificacion_datos');
    }
};
