<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contactos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->string('nombre');
            $table->string('rol');          // calidad, ventas, compras, logistica, administracion, etc.
            $table->string('telefono')->nullable();
            $table->string('correo')->nullable();
            $table->timestamps();

            $table->index('proveedor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contactos_proveedor');
    }
};
