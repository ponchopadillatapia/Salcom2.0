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
                if (! Schema::hasColumn('empleados', 'requiere_gasolina')) {
                    $table->boolean('requiere_gasolina')->default(false)->after('departamento');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('empleados')) {
            Schema::table('empleados', function (Blueprint $table) {
                $table->dropColumn('requiere_gasolina');
            });
        }
    }
};
