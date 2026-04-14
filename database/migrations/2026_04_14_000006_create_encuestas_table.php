<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuestas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_cliente');
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->tinyInteger('calificacion');
            $table->tinyInteger('tiempo_entrega');
            $table->tinyInteger('calidad_producto');
            $table->text('comentarios')->nullable();
            $table->timestamps();

            $table->index('codigo_cliente');
            $table->index('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas');
    }
};
