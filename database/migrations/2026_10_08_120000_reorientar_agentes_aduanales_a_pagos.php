<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * El módulo deja de ser un catálogo de agentes.
 * La operación, su factura, el anticipo y el pago son el flujo.
 * Encargos conferidos y documentos de patente no entran en ese flujo.
 *
 * Esta migración ya corrió en la base de Salcom. Si las tablas de facturas
 * existen, no vuelve a borrar nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('facturas_agente_aduanal')) {
            return;
        }

        $this->borrarArchivos('documentos_agente_aduanal', 'ruta');
        $this->borrarArchivos('encargos_conferidos', 'ruta_acuse');

        Schema::dropIfExists('encargos_conferidos');
        Schema::dropIfExists('documentos_agente_aduanal');

        Schema::create('facturas_agente_aduanal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->unique()->constrained('operaciones_comercio_exterior')->cascadeOnDelete();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->restrictOnDelete();
            $table->string('folio', 40);
            $table->date('fecha');
            $table->decimal('importe', 14, 2);
            $table->string('nombre_archivo')->nullable();
            $table->string('ruta')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('agente_aduanal_id');
            $table->index('fecha');
            $table->index('importe');
        });

        Schema::create('anticipos_agente_aduanal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->restrictOnDelete();
            $table->foreignId('factura_id')->unique()->constrained('facturas_agente_aduanal')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('importe', 14, 2);
            $table->string('referencia', 80)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('agente_aduanal_id');
        });

        Schema::create('pagos_agente_aduanal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->unique()->constrained('facturas_agente_aduanal')->cascadeOnDelete();
            $table->foreignId('anticipo_id')->nullable()->constrained('anticipos_agente_aduanal')->nullOnDelete();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('importe', 14, 2);
            $table->string('direccion', 20);
            $table->string('referencia', 80)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('agente_aduanal_id');
            $table->index('direccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_agente_aduanal');
        Schema::dropIfExists('anticipos_agente_aduanal');
        Schema::dropIfExists('facturas_agente_aduanal');
    }

    private function borrarArchivos(string $tabla, string $columna): void
    {
        if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
            return;
        }

        foreach (DB::table($tabla)->pluck($columna) as $ruta) {
            if (! is_string($ruta) || $ruta === '' || str_contains($ruta, '..') || ! str_starts_with($ruta, 'agentes-aduanales/')) {
                continue;
            }

            Storage::disk('local')->delete($ruta);
        }
    }
};
