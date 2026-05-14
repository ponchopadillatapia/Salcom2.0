<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pronosticos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 30);
            $table->string('referencia_tipo', 20)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('codigo_referencia', 50)->nullable();
            $table->text('resultado')->nullable();
            $table->json('datos')->nullable();
            $table->string('confianza', 10)->default('media');
            $table->timestamp('generado_at')->nullable();
            $table->timestamps();

            $table->index(['tipo', 'referencia_tipo', 'referencia_id']);
            $table->index('generado_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pronosticos');
    }
};
