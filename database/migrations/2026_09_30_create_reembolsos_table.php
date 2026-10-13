<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reembolsos')) {
            Schema::create('reembolsos', function (Blueprint $table) {
                $table->id();

                // ── Datos del empleado solicitante (histórico; se copian al crear) ──
                $table->string('numero_empleado', 50)->nullable()->index();
                $table->string('solicitante', 150);
                $table->string('numero_cuenta', 50)->nullable();
                $table->string('titular_cuenta', 150)->nullable();

                // ── Clasificación del gasto ──
                $table->string('categoria', 40);          // gasto_general, gasolina, computo, viaticos_nacional, viaticos_internacional
                $table->string('razon_social', 120);      // empresa que paga
                $table->string('metodo_pago_empresa', 20); // bbva | inntec

                // ── Datos fiscales (solo los captura el admin; nullable para el alta del empleado) ──
                $table->string('uso_cfdi', 20)->nullable();
                $table->string('forma_pago', 20)->nullable();
                $table->string('metodo_pago', 20)->nullable();

                // ── Importe y concepto ──
                // POR QUÉ: el form manda el monto como texto ("$1,500"); aquí se normaliza a decimal.
                $table->decimal('monto', 12, 2)->default(0);
                $table->string('concepto', 255);
                $table->date('fecha_factura')->nullable();

                // ── Archivos (rutas en disco public; el binario NO va en la BD) ──
                $table->string('archivo_factura')->nullable();
                $table->string('archivo_xml')->nullable();
                $table->string('archivo_materialidad')->nullable();

                $table->string('notas', 500)->nullable();

                // ── Estado del flujo ──
                // POR QUÉ: estatus como columna real para contar pendientes por SQL (lo usa el dashboard).
                $table->string('estatus', 20)->default('pendiente')->index();
                // Autorización de Sandra Gutiérrez. Antes vivía dentro del JSON; ahora son columnas.
                $table->boolean('autorizado')->default(false);
                $table->timestamp('autorizado_fecha')->nullable();

                // De dónde vino: lo capturó el admin o el propio empleado desde su portal.
                $table->string('origen', 20)->default('admin');

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reembolsos');
    }
};
