<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('proveedores_users')) {
            return;
        }

        Schema::table('proveedores_users', function (Blueprint $table) {
            if (! Schema::hasColumn('proveedores_users', 'solicitud_alta_estatus')) {
                $table->string('solicitud_alta_estatus', 30)->nullable()->after('activo');
                $table->index('solicitud_alta_estatus');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('proveedores_users')) {
            return;
        }

        Schema::table('proveedores_users', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores_users', 'solicitud_alta_estatus')) {
                $table->dropIndex(['solicitud_alta_estatus']);
                $table->dropColumn('solicitud_alta_estatus');
            }
        });
    }
};
