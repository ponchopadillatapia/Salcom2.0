<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_validaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->string('archivo_path', 255);
            $table->integer('total_productos')->default(0);
            $table->integer('productos_validos')->default(0);
            $table->integer('productos_con_error')->default(0);
            $table->json('errores')->nullable();
            $table->string('estatus', 20)->default('procesando');
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamps();

            $table->index('proveedor_id');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excel_validaciones');
    }
};
