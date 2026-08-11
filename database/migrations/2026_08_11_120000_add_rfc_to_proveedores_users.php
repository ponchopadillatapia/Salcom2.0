<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            if (! Schema::hasColumn('proveedores_users', 'rfc')) {
                $table->string('rfc', 13)->nullable()->after('tipo_persona');
                $table->unique('rfc');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            if (Schema::hasColumn('proveedores_users', 'rfc')) {
                $table->dropUnique(['rfc']);
                $table->dropColumn('rfc');
            }
        });
    }
};
