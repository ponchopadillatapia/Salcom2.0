<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            if (! Schema::hasColumn('proveedores_users', 'datos_identificacion')) {
                $table->json('datos_identificacion')->nullable()->after('telefono');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores_users', 'datos_identificacion')) {
                $table->dropColumn('datos_identificacion');
            }
        });
    }
};
