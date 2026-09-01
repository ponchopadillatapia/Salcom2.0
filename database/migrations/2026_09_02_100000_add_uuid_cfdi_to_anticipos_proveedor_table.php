<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anticipos_proveedor', function (Blueprint $table) {
            // UUID del CFDI de anticipo timbrado. Sirve para ligar automáticamente
            // la factura del proveedor cuando trae CfdiRelacionados TipoRelacion 07.
            $table->string('uuid_cfdi', 36)->nullable()->after('folio_general')->index();
        });
    }

    public function down(): void
    {
        Schema::table('anticipos_proveedor', function (Blueprint $table) {
            $table->dropColumn('uuid_cfdi');
        });
    }
};
