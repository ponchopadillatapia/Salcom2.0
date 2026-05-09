<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de auditoría: registra todas las acciones importantes del sistema.
 * Parte del S-SDLC - Logging y Monitoreo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('accion');              // login, logout, crear, editar, eliminar, etc.
            $table->string('modulo');              // proveedores, clientes, pedidos, admin, etc.
            $table->string('usuario_tipo');        // admin, cliente, proveedor, sistema
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nombre')->nullable();
            $table->string('descripcion');         // Descripción legible de lo que pasó
            $table->json('datos_antes')->nullable();  // Estado anterior (para ediciones)
            $table->json('datos_despues')->nullable(); // Estado nuevo (para ediciones)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('nivel')->default('info'); // info, warning, error, critical
            $table->timestamps();

            // Índices para búsquedas rápidas
            $table->index('accion');
            $table->index('modulo');
            $table->index('usuario_tipo');
            $table->index('usuario_id');
            $table->index('nivel');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
