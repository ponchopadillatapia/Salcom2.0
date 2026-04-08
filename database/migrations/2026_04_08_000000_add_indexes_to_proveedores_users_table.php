<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            $table->index('correo');
            $table->index('codigo_compras');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores_users', function (Blueprint $table) {
            $table->dropIndex(['correo']);
            $table->dropIndex(['codigo_compras']);
        });
    }
};
