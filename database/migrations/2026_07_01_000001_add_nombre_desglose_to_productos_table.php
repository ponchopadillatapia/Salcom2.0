<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('nombre_tipo')->nullable()->after('nombre');
            $table->string('nombre_marca')->nullable()->after('nombre_tipo');
            $table->string('nombre_modelo')->nullable()->after('nombre_marca');
            $table->string('nombre_medida')->nullable()->after('nombre_modelo');
            $table->string('nombre_especificacion')->nullable()->after('nombre_medida');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['nombre_tipo', 'nombre_marca', 'nombre_modelo', 'nombre_medida', 'nombre_especificacion']);
        });
    }
};
