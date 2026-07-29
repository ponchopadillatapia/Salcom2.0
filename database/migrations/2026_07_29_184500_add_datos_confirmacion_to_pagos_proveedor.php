<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_proveedor', function (Blueprint $table) {
            if (! Schema::hasColumn('pagos_proveedor', 'datos_confirmacion')) {
                $table->json('datos_confirmacion')->nullable()->after('comprobantes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pagos_proveedor', function (Blueprint $table) {
            if (Schema::hasColumn('pagos_proveedor', 'datos_confirmacion')) {
                $table->dropColumn('datos_confirmacion');
            }
        });
    }
};
