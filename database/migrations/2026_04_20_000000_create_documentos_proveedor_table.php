<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proveedor_id');
            $table->string('tipo');           // cif, opinion, acta, rep_legal, contribuyente, caratula_banco
            $table->string('archivo');         // ruta en storage
            $table->string('estatus')->default('pendiente'); // pendiente, aprobado, rechazado
            $table->json('resultado_validacion')->nullable(); // resultado automático
            $table->text('notas_revision')->nullable();       // notas de Kiro/admin
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();

            $table->index('proveedor_id');
            $table->index('estatus');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_proveedor');
    }
};
