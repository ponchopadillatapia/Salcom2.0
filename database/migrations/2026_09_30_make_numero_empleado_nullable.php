<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('empleados') && Schema::hasColumn('empleados', 'numero_empleado')) {
            Schema::table('empleados', function (Blueprint $table) {
                // POR QUÉ: dirección pidió poder registrar empleados SIN número y asignarlo después.
                // Guardamos NULL (no '') para que el índice UNIQUE permita varios empleados sin número.
                // La columna se creó NOT NULL en 2026_09_03, por eso aquí la volvemos nullable.
                $table->string('numero_empleado', 50)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('empleados') && Schema::hasColumn('empleados', 'numero_empleado')) {
            Schema::table('empleados', function (Blueprint $table) {
                // Revertir a NOT NULL. OJO: si hay filas con NULL esto fallaría; es el comportamiento esperado.
                $table->string('numero_empleado', 50)->nullable(false)->change();
            });
        }
    }
};
