<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reembolsos_viaje', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_empleado', 50);
            $table->string('nombre_empleado');
            $table->string('departamento')->nullable();
            $table->string('pais_destino');
            $table->string('moneda_destino', 10); // COP, USD, EUR, CNY, etc.
            $table->decimal('tipo_cambio', 12, 4)->default(1);
            $table->string('moneda_base', 10)->default('MXN');
            $table->json('gastos'); // [{concepto, monto_local, monto_base}]
            $table->decimal('total_moneda_local', 14, 2)->default(0);
            $table->decimal('total_moneda_base', 14, 2)->default(0);
            $table->string('estatus', 30)->default('borrador'); // borrador, enviado, aprobado, rechazado, reembolsado
            $table->string('archivo_comprobantes')->nullable();
            $table->text('notas')->nullable();
            $table->text('notas_revision')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('aprobado_at')->nullable();
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamps();

            $table->index('codigo_empleado');
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reembolsos_viaje');
    }
};
