<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('uuid_cfdi', 36)->nullable()->after('folio_cfdi');
            $table->string('regimen_fiscal', 10)->nullable()->after('codigo_proveedor');
            $table->boolean('es_fletera')->default(false)->after('regimen_fiscal');
            $table->decimal('retencion_iva', 12, 2)->nullable()->after('monto_iva');
            $table->decimal('retencion_isr', 12, 2)->nullable()->after('retencion_iva');
            $table->string('archivo_pdf')->nullable()->after('fecha_vencimiento');
            $table->string('archivo_xml')->nullable()->after('archivo_pdf');
            $table->string('archivo_oc')->nullable()->after('archivo_xml');
            $table->text('notas')->nullable()->after('archivo_oc');
            $table->json('validacion_detalle')->nullable()->after('notas');

            $table->unique('uuid_cfdi');
            $table->index('es_fletera');
            $table->index('regimen_fiscal');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropUnique(['uuid_cfdi']);
            $table->dropIndex(['es_fletera']);
            $table->dropIndex(['regimen_fiscal']);
            $table->dropColumn([
                'uuid_cfdi',
                'regimen_fiscal',
                'es_fletera',
                'retencion_iva',
                'retencion_isr',
                'archivo_pdf',
                'archivo_xml',
                'archivo_oc',
                'notas',
                'validacion_detalle',
            ]);
        });
    }
};
