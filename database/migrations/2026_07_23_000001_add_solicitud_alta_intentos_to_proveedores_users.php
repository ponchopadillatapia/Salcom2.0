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
            if (! Schema::hasColumn('proveedores_users', 'solicitud_alta_intentos')) {
                $table->unsignedTinyInteger('solicitud_alta_intentos')->default(0)->after('solicitud_alta_estatus');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('proveedores_users')) {
            return;
        }

        Schema::table('proveedores_users', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores_users', 'solicitud_alta_intentos')) {
                $table->dropColumn('solicitud_alta_intentos');
            }
        });
    }
};
