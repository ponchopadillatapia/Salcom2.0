<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda el folio BRUTO/interno de Contpaqi (ej. 3850965172) aparte del `num`.
 *
 * POR QUÉ: Sandra ve en WieseBanco el folio BONITO consecutivo estilo Quicken (80195, 80196...),
 * que vive en `num`. Pero Contpaqi devuelve su propio folio interno gigante (3850965172) que no
 * es consecutivo. Lo guardamos aquí como referencia (para conciliar con Contpaqi), sin mostrarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('movimientos_bancarios')
            && ! Schema::hasColumn('movimientos_bancarios', 'folio_contpaqi')) {
            Schema::table('movimientos_bancarios', function (Blueprint $table) {
                // El folio interno de Contpaqi es grande; usamos unsignedBigInteger. Nullable
                // porque los movimientos que NO van a Contpaqi (traspasos, nómina) no lo tienen.
                $table->unsignedBigInteger('folio_contpaqi')->nullable()->after('iddocumento_contpaqi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('movimientos_bancarios')
            && Schema::hasColumn('movimientos_bancarios', 'folio_contpaqi')) {
            Schema::table('movimientos_bancarios', function (Blueprint $table) {
                $table->dropColumn('folio_contpaqi');
            });
        }
    }
};
