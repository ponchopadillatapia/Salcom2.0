<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('abonos_proveedor')) {
            return;
        }

        Schema::table('abonos_proveedor', function (Blueprint $table) {
            if (! Schema::hasColumn('abonos_proveedor', 'agente')) {
                $table->string('agente')->nullable()->after('concepto');
                $table->index('agente');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('abonos_proveedor')) {
            return;
        }

        Schema::table('abonos_proveedor', function (Blueprint $table) {
            if (Schema::hasColumn('abonos_proveedor', 'agente')) {
                $table->dropIndex(['agente']);
                $table->dropColumn('agente');
            }
        });
    }
};
