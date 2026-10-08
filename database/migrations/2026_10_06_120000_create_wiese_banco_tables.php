<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * WieseBanco: registro bancario propio que reemplaza a Quicken 2007.
 *
 * POR QUÉ existe: Quicken es de 2007 y no tiene APIs, imposible de integrar.
 * La dirección (Chuy) decidió llevar el registro bancario dentro de la web.
 * De aquí nace el folio consecutivo (columna NUM en Quicken) que se le manda
 * a Contpaqi como folio del pago. Es el ORIGEN del pago, no un espejo.
 *
 * Arrancamos SOLO con la cuenta "BBVA 969 SALCOM PESOS" (la mítica, concepto
 * Contpaqi 28). Las demás cuentas (Banorte, Santander, etc.) se agregan después.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de cuentas bancarias. Cada cuenta lleva su propio consecutivo.
        if (! Schema::hasTable('cuentas_bancarias')) {
            Schema::create('cuentas_bancarias', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');                       // "BBVA 969 SALCOM PESOS"
                $table->string('banco', 60);                    // "BBVA"
                $table->string('clave_corta', 20)->nullable();  // "8969" (identificador corto que usan)
                // POR QUÉ se guarda aquí el concepto: cada cuenta mapea a un concepto de Contpaqi
                // (BBVA MXN = 28, agente aduanal = 283, etc.). Así el pago sabe qué concepto usar.
                $table->string('concepto_contpaqi', 20)->nullable();
                // POR QUÉ consecutivo_actual: es el último NUM usado en Quicken. El siguiente pago
                // toma este valor + 1. Para BBVA 969 arranca en 80194, así el primero será 80195
                // y NO choca con la numeración vieja de Quicken.
                $table->unsignedBigInteger('consecutivo_actual')->default(0);
                $table->decimal('saldo_actual', 18, 2)->default(0); // balance que se va recalculando
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->index('activo');
            });
        }

        // Movimientos del registro (cada fila, como los renglones de Quicken).
        if (! Schema::hasTable('movimientos_bancarios')) {
            Schema::create('movimientos_bancarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cuenta_id')->constrained('cuentas_bancarias')->cascadeOnDelete();
                $table->date('fecha');
                // num = el folio consecutivo de la cuenta (columna NUM de Quicken).
                $table->unsignedBigInteger('num')->nullable();
                $table->string('payee')->nullable();        // a quién se le paga
                $table->string('categoria')->nullable();    // "PROVEEDOR", etc.
                // memo: aquí van los folios de las facturas que cubre el pago (ej. "370-373").
                $table->string('memo')->nullable();
                $table->decimal('payment', 18, 2)->default(0);  // lo que sale (pago)
                $table->decimal('deposit', 18, 2)->default(0);  // lo que entra (depósito)
                $table->decimal('balance', 18, 2)->default(0);  // saldo tras este movimiento
                // Enlace con Contpaqi: se llena cuando el pago se registra allá vía la API C#.
                $table->unsignedBigInteger('iddocumento_contpaqi')->nullable();
                $table->string('codigo_proveedor', 40)->nullable(); // proveedor en Contpaqi/Wiese
                // estatus del movimiento: borrador (capturado), enviado (ya en Contpaqi), error.
                $table->string('estatus', 20)->default('borrador');
                $table->timestamps();

                $table->index('cuenta_id');
                $table->index('fecha');
                $table->index('num');
                $table->index('estatus');
            });
        }

        // Alta de la cuenta BBVA 969 con su consecutivo en 80194 (último NUM de Quicken).
        // POR QUÉ 80194: es el último folio usado en Quicken para esta cuenta; el próximo pago
        // generará 80195 para continuar la secuencia sin repetir.
        $existe = DB::table('cuentas_bancarias')->where('clave_corta', '8969')->exists();
        if (! $existe) {
            DB::table('cuentas_bancarias')->insert([
                'nombre' => 'BBVA 969 SALCOM PESOS',
                'banco' => 'BBVA',
                'clave_corta' => '8969',
                'concepto_contpaqi' => '28',
                'consecutivo_actual' => 80194,
                'saldo_actual' => 0,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_bancarios');
        Schema::dropIfExists('cuentas_bancarias');
    }
};
