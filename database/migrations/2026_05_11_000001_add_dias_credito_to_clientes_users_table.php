<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes_users', function (Blueprint $table) {
            $table->unsignedSmallInteger('dias_credito')->nullable()->after('limite_credito');
        });
    }

    public function down(): void
    {
        Schema::table('clientes_users', function (Blueprint $table) {
            $table->dropColumn('dias_credito');
        });
    }
};
