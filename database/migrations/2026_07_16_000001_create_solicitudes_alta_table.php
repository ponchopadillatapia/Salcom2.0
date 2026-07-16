<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_alta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->string('tipo_persona');
            $table->string('nombre_completo')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('nombres')->nullable();
            $table->string('calle')->nullable();
            $table->string('num_exterior')->nullable();
            $table->string('num_interior')->nullable();
            $table->string('colonia')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('pais')->nullable();
            $table->string('cp', 10)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('celular', 30)->nullable();
            $table->string('telefono2', 30)->nullable();
            $table->string('extension', 20)->nullable();
            $table->string('correo')->nullable();
            $table->string('clabe', 18)->nullable();
            $table->string('cuenta', 30)->nullable();
            $table->string('banco')->nullable();
            $table->json('docs_marcados')->nullable();
            $table->string('nombre_firma')->nullable();
            $table->string('estatus')->default('pendiente'); // pendiente, aprobada, rechazada
            $table->text('notas_admin')->nullable();
            $table->timestamps();

            $table->index('proveedor_id');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_alta');
    }
};
