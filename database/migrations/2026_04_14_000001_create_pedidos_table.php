<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->string('codigo_cliente');
            $table->string('nombre_cliente');
            $table->json('productos');
            $table->decimal('total', 12, 2);
            $table->string('tipo_pago')->default('contado');
            $table->string('estatus')->default('validacion');
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo_cliente');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
