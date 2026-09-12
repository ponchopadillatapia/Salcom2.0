<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empleados')) {
            Schema::table('empleados', function (Blueprint $table) {
                if (! Schema::hasColumn('empleados', 'numero_cuenta')) {
                    $table->string('numero_cuenta', 30)->nullable()->after('correo');
                }
                if (! Schema::hasColumn('empleados', 'titular_cuenta')) {
                    $table->string('titular_cuenta')->nullable()->after('numero_cuenta');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('empleados')) {
            Schema::table('empleados', function (Blueprint $table) {
                $table->dropColumn(['numero_cuenta', 'titular_cuenta']);
            });
        }
    }
};
