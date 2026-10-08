<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bitacoras_gasolina')) {
            Schema::create('bitacoras_gasolina', function (Blueprint $table) {
                $table->id();
                // POR QUÉ: columnas reales en vez de un JSON genérico; así los filtros/sumas se hacen
                // con SQL (WHERE/SUM) en lugar de recorrer en PHP, y la bitácora escala mejor.
                $table->date('fecha');
                // Guardamos el número Y el nombre tal como se capturaron (histórico), aunque luego
                // cambie el alta del empleado. numero_empleado es nullable porque hay cargas sin número.
                $table->string('numero_empleado', 50)->nullable()->index();
                $table->string('empleado', 150);
                $table->decimal('cantidad_litros', 10, 2)->nullable();
                $table->decimal('rendimiento', 10, 2)->nullable();
                // El monto se captura como texto con '$' en el form; lo normalizamos a decimal aquí.
                $table->decimal('monto', 12, 2)->default(0);
                $table->string('vehiculo', 100)->nullable();
                $table->decimal('kilometraje', 12, 2)->nullable();
                $table->string('notas', 255)->nullable();
                // Ruta del archivo (PDF/imagen) en el disco public; el archivo NO va en la BD.
                $table->string('factura')->nullable();
                // De dónde vino el registro: lo capturó el admin o el propio empleado desde su portal.
                $table->string('origen', 20)->default('admin');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras_gasolina');
    }
};
