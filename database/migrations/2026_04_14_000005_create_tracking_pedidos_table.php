<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_pedidos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pedido_id');
            $table->string('estatus');
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha');
            $table->string('usuario_responsable')->nullable();
            $table->timestamps();

            $table->index('pedido_id');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_pedidos');
    }
};
