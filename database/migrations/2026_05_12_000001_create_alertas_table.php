<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50);
            $table->string('modulo', 30);
            $table->string('destinatario_tipo', 20)->nullable();
            $table->unsignedBigInteger('destinatario_id')->nullable();
            $table->string('titulo', 255);
            $table->text('contenido')->nullable();
            $table->json('datos')->nullable();
            $table->string('canal_enviado', 20)->nullable();
            $table->string('estatus', 20)->default('pendiente');
            $table->string('nivel', 10)->default('info');
            $table->timestamp('leida_at')->nullable();
            $table->timestamp('accionada_at')->nullable();
            $table->timestamps();

            $table->index('tipo');
            $table->index(['destinatario_tipo', 'destinatario_id']);
            $table->index('estatus');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
