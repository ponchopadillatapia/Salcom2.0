<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_usuario');
            $table->string('codigo_usuario');
            $table->string('titulo');
            $table->text('mensaje');
            $table->boolean('leida')->default(false);
            $table->string('tipo')->default('info');
            $table->timestamps();

            $table->index(['tipo_usuario', 'codigo_usuario']);
            $table->index('leida');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
