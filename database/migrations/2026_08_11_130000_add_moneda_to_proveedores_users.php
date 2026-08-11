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
            if (! Schema::hasColumn('proveedores_users', 'moneda')) {
                // MXN | DOLLAR — para proveedores con 2 registros Contpaqi (pesos / dólares)
                $table->string('moneda', 10)->default('MXN')->after('nombre');
                $table->index('moneda');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('proveedores_users')) {
            return;
        }

        Schema::table('proveedores_users', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores_users', 'moneda')) {
                $table->dropIndex(['moneda']);
                $table->dropColumn('moneda');
            }
        });
    }
};
