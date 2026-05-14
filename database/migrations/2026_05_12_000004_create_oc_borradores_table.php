<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_borradores', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->unsignedBigInteger('proveedor_id');
            $table->json('productos');
            $table->decimal('monto_estimado', 12, 2)->default(0);
            $table->string('motivo', 255)->nullable();
            $table->string('estatus', 20)->default('pendiente');
            $table->unsignedBigInteger('aprobada_por')->nullable();
            $table->timestamp('aprobada_at')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('proveedor_id');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_borradores');
    }
};
