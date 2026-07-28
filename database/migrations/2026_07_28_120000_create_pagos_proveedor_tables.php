<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores_users')->nullOnDelete();
            $table->string('codigo_proveedor')->index();
            $table->string('tipo', 20)->default('facturas'); // facturas | anticipo (v2)
            $table->string('estatus', 20)->default('borrador'); // borrador | confirmado | cancelado
            $table->date('fecha_pago')->nullable();
            $table->text('notas')->nullable();
            $table->unsignedInteger('num_facturas')->default(0);
            $table->decimal('monto_subtotal', 14, 2)->default(0);
            $table->decimal('monto_iva', 14, 2)->default(0);
            $table->decimal('monto_retencion_iva', 14, 2)->default(0);
            $table->decimal('monto_retencion_isr', 14, 2)->default(0);
            $table->decimal('monto_total', 14, 2)->default(0);
            $table->decimal('monto_neto', 14, 2)->default(0);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('confirmado_por')->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->timestamps();

            $table->index(['estatus', 'created_at']);
        });

        Schema::create('pago_proveedor_facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos_proveedor')->cascadeOnDelete();
            $table->foreignId('factura_id')->constrained('facturas')->restrictOnDelete();
            $table->string('folio_cfdi')->nullable();
            $table->string('uuid_cfdi', 36)->nullable();
            $table->boolean('es_fletera')->default(false);
            $table->string('regimen_fiscal', 10)->nullable();
            $table->decimal('monto', 14, 2)->default(0);
            $table->decimal('monto_iva', 14, 2)->default(0);
            $table->decimal('retencion_iva', 14, 2)->default(0);
            $table->decimal('retencion_isr', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->decimal('neto', 14, 2)->default(0);
            $table->json('avisos')->nullable();
            $table->timestamps();

            $table->unique(['pago_id', 'factura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_proveedor_facturas');
        Schema::dropIfExists('pagos_proveedor');
    }
};
