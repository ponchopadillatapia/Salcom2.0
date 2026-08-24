<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anticipos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 30)->nullable(); // FCONA-0040
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores_users')->nullOnDelete();
            $table->string('codigo_proveedor', 30)->nullable()->index();
            $table->string('nombre_proveedor', 200)->nullable();
            $table->string('rfc_proveedor', 20)->nullable();
            $table->string('banco', 80)->nullable();
            $table->string('cuenta_banco', 30)->nullable();
            $table->string('clabe', 20)->nullable();
            $table->decimal('importe', 14, 2)->default(0);
            $table->decimal('iva', 14, 2)->default(0);
            $table->decimal('total_banco', 14, 2)->default(0);
            $table->string('folio_general', 120)->nullable(); // OC o proforma del proveedor
            $table->string('departamento', 60)->nullable(); // Mantenimiento, IMEX, Compras Nacional, etc.
            $table->date('fecha')->nullable();
            $table->text('concepto')->nullable(); // "Se paga anticipado para liberación..."
            $table->string('estatus', 20)->default('pendiente'); // pendiente, pagado, aplicado, cancelado
            $table->decimal('monto_aplicado', 14, 2)->default(0); // cuánto ya se descontó en facturas
            $table->unsignedBigInteger('factura_id')->nullable(); // factura donde se aplicó
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->json('datos')->nullable(); // info extra
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anticipos_proveedor');
    }
};
