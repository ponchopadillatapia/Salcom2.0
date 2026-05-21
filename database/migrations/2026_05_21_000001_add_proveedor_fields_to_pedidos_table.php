<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('codigo_proveedor')->nullable()->after('codigo_cliente');
            $table->string('nombre_proveedor')->nullable()->after('nombre_cliente');

            $table->index('codigo_proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex(['codigo_proveedor']);
            $table->dropColumn(['codigo_proveedor', 'nombre_proveedor']);
        });
    }
};
