<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_proveedor_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores_users')->cascadeOnDelete();
            $table->decimal('precio', 12, 2)->default(0);
            $table->unsignedInteger('moq')->default(1);
            $table->timestamps();

            $table->unique(['producto_id', 'proveedor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_proveedor_precios');
    }
};
