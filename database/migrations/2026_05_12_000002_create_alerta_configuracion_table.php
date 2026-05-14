<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerta_configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->string('valor', 255);
            $table->string('descripcion', 255)->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // Valores iniciales
        DB::table('alerta_configuracion')->insert([
            ['clave' => 'umbral_critico_proveedor', 'valor' => '60', 'descripcion' => 'Score mínimo aceptable para proveedores (%)', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'ddi_dias', 'valor' => '90', 'descripcion' => 'Días de inventario (DDI) - política Salcom', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'dias_alerta_documento', 'valor' => '7', 'descripcion' => 'Días antes del vencimiento para primera alerta', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'dias_urgente_documento', 'valor' => '3', 'descripcion' => 'Días antes del vencimiento para alerta urgente', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'frecuencia_oc_trimestral', 'valor' => '90', 'descripcion' => 'Cada cuántos días se genera OC trimestral', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'pico_demanda_porcentaje', 'valor' => '20', 'descripcion' => 'Porcentaje de incremento para considerar pico de demanda', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'entregas_tardias_consecutivas', 'valor' => '2', 'descripcion' => 'Entregas tardías consecutivas para alerta de patrón', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('alerta_configuracion');
    }
};
