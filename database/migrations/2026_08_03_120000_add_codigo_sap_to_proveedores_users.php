<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            // Código de enlace con adsalcom18 / SAP (búsqueda futura)
            if (! Schema::hasColumn('proveedores_users', 'codigo')) {
                $table->string('codigo', 64)->nullable()->after('aviso_privacidad_fecha');
                $table->index('codigo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores_users', 'codigo')) {
                $table->dropColumn('codigo');
            }
        });
    }
};
