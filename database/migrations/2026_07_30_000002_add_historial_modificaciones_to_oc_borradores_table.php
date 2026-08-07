<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_borradores', function (Blueprint $table) {
            $table->json('historial_modificaciones')->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('oc_borradores', function (Blueprint $table) {
            $table->dropColumn('historial_modificaciones');
        });
    }
};
