<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo');
            $table->string('usuario')->unique();
            $table->string('password');
            $table->string('telefono')->nullable();
            $table->string('rfc')->nullable();
            $table->string('tipo_persona')->nullable();
            $table->string('codigo_cliente')->nullable();
            $table->string('tipo_cliente')->nullable();
            $table->boolean('credito_autorizado')->default(false);
            $table->decimal('limite_credito', 12, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('correo');
            $table->index('codigo_cliente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_users');
    }
};
