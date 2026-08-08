<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('proveedores_users', 'correo_verified_at')) {
            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->timestamp('correo_verified_at')->nullable()->after('correo');
            });
        }

        // Proveedores ya registrados quedan verificados; solo registros nuevos arrancan en null.
        DB::table('proveedores_users')
            ->whereNull('correo_verified_at')
            ->update(['correo_verified_at' => now()]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('proveedores_users', 'correo_verified_at')) {
            Schema::table('proveedores_users', function (Blueprint $table) {
                $table->dropColumn('correo_verified_at');
            });
        }
    }
};
