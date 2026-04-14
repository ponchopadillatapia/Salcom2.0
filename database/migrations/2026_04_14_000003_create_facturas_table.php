<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('folio_cfdi')->unique();
            $table->string('codigo_cliente')->nullable();
            $table->string('codigo_proveedor')->nullable();
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->decimal('monto', 12, 2);
            $table->decimal('monto_iva', 12, 2);
            $table->decimal('total', 12, 2);
            $table->string('estatus')->default('pendiente');
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo_cliente');
            $table->index('codigo_proveedor');
            $table->index('estatus');
            $table->index('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
