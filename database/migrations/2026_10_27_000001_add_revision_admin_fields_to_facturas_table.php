<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el "visto bueno visual del admin" a las facturas.
 *
 * POR QUÉ un campo SEPARADO (revision_admin) y NO reusar 'estatus':
 * La columna 'estatus' la usa el FLUJO DE PAGO (pendiente → programada → pagada → liquidada).
 * Si metiéramos aquí el visto bueno del admin, chocaría con ese flujo. Por eso la revisión
 * del expediente (que el proveedor subió bien la factura + OC) vive en su propio campo:
 *   - revision_admin: 'pendiente' (recién subida, falta visto bueno) | 'aprobada' | 'rechazada'
 * Así una factura puede estar 'pendiente' de pago PERO ya 'aprobada' por revisión del admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('revision_admin', 20)->default('pendiente')->after('estatus');
            $table->text('revision_notas')->nullable()->after('revision_admin');
            $table->unsignedBigInteger('revisado_por')->nullable()->after('revision_notas');
            $table->string('revisado_por_nombre')->nullable()->after('revisado_por');
            $table->timestamp('revisado_admin_at')->nullable()->after('revisado_por_nombre');

            $table->index('revision_admin');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex(['revision_admin']);
            $table->dropColumn([
                'revision_admin',
                'revision_notas',
                'revisado_por',
                'revisado_por_nombre',
                'revisado_admin_at',
            ]);
        });
    }
};
