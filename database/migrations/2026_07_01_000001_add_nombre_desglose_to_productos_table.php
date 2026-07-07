<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'nombre_tipo')) {
                $table->string('nombre_tipo')->nullable()->after('nombre');
            }
            if (! Schema::hasColumn('productos', 'nombre_marca')) {
                $table->string('nombre_marca')->nullable()->after('nombre_tipo');
            }
            if (! Schema::hasColumn('productos', 'nombre_modelo')) {
                $table->string('nombre_modelo')->nullable()->after('nombre_marca');
            }
            if (! Schema::hasColumn('productos', 'nombre_medida')) {
                $table->string('nombre_medida')->nullable()->after('nombre_modelo');
            }
            if (! Schema::hasColumn('productos', 'nombre_especificacion')) {
                $table->string('nombre_especificacion')->nullable()->after('nombre_medida');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('productos', 'nombre_tipo') ? 'nombre_tipo' : null,
                Schema::hasColumn('productos', 'nombre_marca') ? 'nombre_marca' : null,
                Schema::hasColumn('productos', 'nombre_modelo') ? 'nombre_modelo' : null,
                Schema::hasColumn('productos', 'nombre_medida') ? 'nombre_medida' : null,
                Schema::hasColumn('productos', 'nombre_especificacion') ? 'nombre_especificacion' : null,
            ]);
            if (! empty($cols)) {
                $table->dropColumn(array_values($cols));
            }
        });
    }
};
