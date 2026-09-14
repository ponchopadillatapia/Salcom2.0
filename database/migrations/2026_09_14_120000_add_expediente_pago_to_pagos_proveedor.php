<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expediente de Pago: campos de autorización (firma digital de Sandra/Karen que
 * reemplaza la firma a pluma) y documentos adjuntos manuales (póliza de Contpaqi,
 * hojas engrapadas u otros que no viven ya en el sistema).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_proveedor', function (Blueprint $table) {
            // Estatus de autorización del expediente: pendiente | autorizado | rechazado
            if (! Schema::hasColumn('pagos_proveedor', 'estatus_autorizacion')) {
                $table->string('estatus_autorizacion')->default('pendiente')->after('confirmado_at');
            }
            // Quién autorizó (admin: Sandra o Karen) y cuándo
            if (! Schema::hasColumn('pagos_proveedor', 'autorizado_por')) {
                $table->unsignedBigInteger('autorizado_por')->nullable()->after('estatus_autorizacion');
            }
            if (! Schema::hasColumn('pagos_proveedor', 'autorizado_por_nombre')) {
                $table->string('autorizado_por_nombre')->nullable()->after('autorizado_por');
            }
            if (! Schema::hasColumn('pagos_proveedor', 'autorizado_at')) {
                $table->timestamp('autorizado_at')->nullable()->after('autorizado_por_nombre');
            }
            if (! Schema::hasColumn('pagos_proveedor', 'notas_autorizacion')) {
                $table->text('notas_autorizacion')->nullable()->after('autorizado_at');
            }
            // Documentos adjuntos manuales del expediente (póliza, hojas engrapadas, etc.)
            // [{ tipo, nombre, archivo, subido_por, subido_at }]
            if (! Schema::hasColumn('pagos_proveedor', 'documentos_adjuntos')) {
                $table->json('documentos_adjuntos')->nullable()->after('notas_autorizacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos_proveedor', function (Blueprint $table) {
            foreach ([
                'estatus_autorizacion',
                'autorizado_por',
                'autorizado_por_nombre',
                'autorizado_at',
                'notas_autorizacion',
                'documentos_adjuntos',
            ] as $col) {
                if (Schema::hasColumn('pagos_proveedor', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
