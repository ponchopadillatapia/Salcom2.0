<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca de "visto por el admin" en facturas (notificación estilo WhatsApp).
 *
 * POR QUÉ: para saber qué facturas son NUEVAS SIN VER por el departamento. Si visto_admin_at
 * es NULL y la factura sigue en revisión pendiente, se muestra el punto azul en el menú y el
 * punto rojo en la fila. Al abrir el listado, se sella visto_admin_at = now y los puntos se apagan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->timestamp('visto_admin_at')->nullable()->after('revisado_admin_at');
            $table->index('visto_admin_at');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropIndex(['visto_admin_at']);
            $table->dropColumn('visto_admin_at');
        });
    }
};
