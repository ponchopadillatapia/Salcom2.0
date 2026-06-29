<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('departamento')->nullable()->after('proveedor_tipo');
            $table->string('linea')->nullable()->after('departamento');
            $table->string('subfamilia_pt')->nullable()->after('linea');
            $table->string('canal')->nullable()->after('subfamilia_pt');
            $table->string('vendedor')->nullable()->after('canal');
            $table->string('modulo')->nullable()->after('vendedor');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['departamento', 'linea', 'subfamilia_pt', 'canal', 'vendedor', 'modulo']);
        });
    }
};
