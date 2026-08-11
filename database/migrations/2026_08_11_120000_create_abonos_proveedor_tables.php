<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->string('poliza_key', 40)->index(); // 8969_mxn | 8969_aduanal | 2026_base | 2026_extranjera
            $table->string('serie', 20)->index();
            $table->unsignedInteger('folio')->index();
            $table->string('concepto')->nullable();
            $table->date('fecha');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores_users')->nullOnDelete();
            $table->string('codigo_proveedor')->index();
            $table->string('nombre_proveedor')->nullable();
            $table->string('moneda', 10)->default('MXN');
            $table->decimal('tipo_cambio', 14, 6)->default(1);
            $table->string('cuenta_bancaria')->nullable();
            $table->string('estatus', 20)->default('borrador'); // borrador | guardado | cancelado
            $table->decimal('monto_pago', 14, 2)->default(0);
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();

            $table->unique(['serie', 'folio', 'poliza_key']);
            $table->index(['estatus', 'fecha']);
        });

        Schema::create('abono_proveedor_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abono_id')->constrained('abonos_proveedor')->cascadeOnDelete();
            $table->foreignId('factura_id')->nullable()->constrained('facturas')->nullOnDelete();
            $table->date('fecha_doc')->nullable();
            $table->string('serie_doc', 40)->nullable();
            $table->string('folio_doc', 80)->nullable();
            $table->string('concepto_doc')->nullable()->default('Compra');
            $table->string('referencia')->nullable();
            $table->decimal('importe_pago', 14, 2)->default(0);
            $table->string('sistema_origen', 40)->default('SALCOM');
            $table->json('detalle')->nullable();
            $table->timestamps();

            $table->index(['abono_id', 'factura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abono_proveedor_documentos');
        Schema::dropIfExists('abonos_proveedor');
    }
};
