<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * POR QUÉ: hasta ahora un pago (pagos_proveedor) era de UN solo proveedor, y sus líneas
     * (pago_proveedor_facturas) heredaban ese proveedor. Ahora un "Formato para pago" puede
     * agrupar facturas de VARIOS proveedores (carrito multi-proveedor). Para eso cada LÍNEA
     * guarda a qué proveedor pertenece su factura: su código y su nombre (para el PDF y el
     * agrupado). Los pagos viejos (mono-proveedor) quedan con estas columnas en null y siguen
     * funcionando igual (se usa el codigo_proveedor de la cabecera como respaldo).
     */
    public function up(): void
    {
        Schema::table('pago_proveedor_facturas', function (Blueprint $table) {
            $table->string('codigo_proveedor')->nullable()->after('factura_id')->index();
            $table->string('nombre_proveedor')->nullable()->after('codigo_proveedor');
        });

        // Marca en la cabecera: un pago que agrupa varios proveedores (carrito). Los normales
        // quedan en false. Sirve para que el PDF y el expediente sepan cómo pintar.
        Schema::table('pagos_proveedor', function (Blueprint $table) {
            $table->boolean('es_multi_proveedor')->default(false)->after('codigo_proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('pago_proveedor_facturas', function (Blueprint $table) {
            $table->dropColumn(['codigo_proveedor', 'nombre_proveedor']);
        });
        Schema::table('pagos_proveedor', function (Blueprint $table) {
            $table->dropColumn('es_multi_proveedor');
        });
    }
};
