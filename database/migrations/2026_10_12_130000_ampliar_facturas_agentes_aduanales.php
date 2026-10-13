<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega los datos de consulta de la factura: serie, moneda, saldo capturado,
 * honorarios, impuestos, retenciones y estados.
 * El anticipo puede ligarse a la operación antes de que exista la factura.
 * No recalcula importes ni borra facturas ya capturadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facturas_agente_aduanal')) {
            $this->crearTablas();

            return;
        }

        $this->ampliarFacturas();
        $this->ampliarAnticipos();
        $this->ampliarPagos();
    }

    public function down(): void
    {
        if (! Schema::hasTable('facturas_agente_aduanal') || ! Schema::hasColumn('facturas_agente_aduanal', 'serie')) {
            return;
        }

        $this->quitarIndice('facturas_agente_aduanal', 'moneda');
        $this->quitarIndice('facturas_agente_aduanal', 'estado_pago');
        $this->quitarIndice('facturas_agente_aduanal', 'estado_cancelacion');

        Schema::table('facturas_agente_aduanal', function (Blueprint $table) {
            $table->dropColumn([
                'serie',
                'moneda',
                'saldo_pendiente',
                'honorarios',
                'impuestos',
                'retenciones',
                'estado_cancelacion',
                'estado_pago',
                'sentido',
            ]);
        });

        if (Schema::hasColumn('anticipos_agente_aduanal', 'operacion_id')) {
            Schema::table('anticipos_agente_aduanal', function (Blueprint $table) {
                $table->dropConstrainedForeignId('operacion_id');
            });
        }

        if (Schema::hasColumn('anticipos_agente_aduanal', 'moneda')) {
            Schema::table('anticipos_agente_aduanal', function (Blueprint $table) {
                $table->dropColumn('moneda');
            });
        }

        if (Schema::hasColumn('pagos_agente_aduanal', 'moneda')) {
            Schema::table('pagos_agente_aduanal', function (Blueprint $table) {
                $table->dropColumn('moneda');
            });
        }
    }

    private function crearTablas(): void
    {
        Schema::create('facturas_agente_aduanal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_id')->unique()->constrained('operaciones_comercio_exterior')->cascadeOnDelete();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->restrictOnDelete();
            $table->string('folio', 40);
            $table->string('serie', 25)->nullable();
            $table->date('fecha');
            $table->string('moneda', 3)->nullable();
            $table->decimal('importe', 14, 2);
            $table->decimal('saldo_pendiente', 14, 2)->nullable();
            $table->decimal('honorarios', 14, 2)->nullable();
            $table->decimal('impuestos', 14, 2)->nullable();
            $table->decimal('retenciones', 14, 2)->nullable();
            $table->string('estado_cancelacion', 20)->default('vigente');
            $table->string('estado_pago', 20)->default('pendiente');
            $table->string('sentido', 30)->nullable();
            $table->string('nombre_archivo')->nullable();
            $table->string('ruta')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('agente_aduanal_id');
            $table->index('fecha');
            $table->index('moneda');
            $table->index('estado_pago');
            $table->index('estado_cancelacion');
        });

        Schema::create('anticipos_agente_aduanal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->restrictOnDelete();
            $table->foreignId('factura_id')->nullable()->constrained('facturas_agente_aduanal')->cascadeOnDelete();
            $table->foreignId('operacion_id')->nullable()->constrained('operaciones_comercio_exterior')->nullOnDelete();
            $table->date('fecha');
            $table->decimal('importe', 14, 2);
            $table->string('moneda', 3)->nullable();
            $table->string('referencia', 80)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('agente_aduanal_id');
        });

        Schema::create('pagos_agente_aduanal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas_agente_aduanal')->cascadeOnDelete();
            $table->foreignId('anticipo_id')->nullable()->constrained('anticipos_agente_aduanal')->nullOnDelete();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('importe', 14, 2);
            $table->string('moneda', 3)->nullable();
            $table->string('direccion', 30);
            $table->string('referencia', 80)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('agente_aduanal_id');
            $table->index('direccion');
        });
    }

    private function ampliarFacturas(): void
    {
        if (Schema::hasColumn('facturas_agente_aduanal', 'serie')) {
            return;
        }

        Schema::table('facturas_agente_aduanal', function (Blueprint $table) {
            $table->string('serie', 25)->nullable();
            $table->string('moneda', 3)->nullable();
            $table->decimal('saldo_pendiente', 14, 2)->nullable();
            $table->decimal('honorarios', 14, 2)->nullable();
            $table->decimal('impuestos', 14, 2)->nullable();
            $table->decimal('retenciones', 14, 2)->nullable();
            $table->string('estado_cancelacion', 20)->default('vigente');
            $table->string('estado_pago', 20)->default('pendiente');
            $table->string('sentido', 30)->nullable();
            $table->index('moneda');
            $table->index('estado_pago');
            $table->index('estado_cancelacion');
        });
    }

    private function ampliarAnticipos(): void
    {
        $columna = collect(Schema::getColumns('anticipos_agente_aduanal'))->firstWhere('name', 'factura_id');
        $hacerNullable = $columna && ! ($columna['nullable'] ?? false);
        $indiceUnico = $this->indiceUnico('anticipos_agente_aduanal', 'factura_id');

        if ($hacerNullable || $indiceUnico) {
            Schema::table('anticipos_agente_aduanal', function (Blueprint $table) use ($hacerNullable, $indiceUnico) {
                $table->dropForeign(['factura_id']);
                if ($indiceUnico) {
                    $table->dropUnique($indiceUnico);
                }
                if ($hacerNullable) {
                    $table->unsignedBigInteger('factura_id')->nullable()->change();
                }
                $table->foreign('factura_id')->references('id')->on('facturas_agente_aduanal')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('anticipos_agente_aduanal', 'operacion_id')) {
            Schema::table('anticipos_agente_aduanal', function (Blueprint $table) {
                $table->foreignId('operacion_id')->nullable()->constrained('operaciones_comercio_exterior')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('anticipos_agente_aduanal', 'moneda')) {
            Schema::table('anticipos_agente_aduanal', function (Blueprint $table) {
                $table->string('moneda', 3)->nullable();
            });
        }
    }

    private function ampliarPagos(): void
    {
        $indiceUnico = $this->indiceUnico('pagos_agente_aduanal', 'factura_id');
        if ($indiceUnico) {
            Schema::table('pagos_agente_aduanal', function (Blueprint $table) use ($indiceUnico) {
                $table->dropForeign(['factura_id']);
                $table->dropUnique($indiceUnico);
                $table->foreign('factura_id')->references('id')->on('facturas_agente_aduanal')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('pagos_agente_aduanal', 'moneda')) {
            Schema::table('pagos_agente_aduanal', function (Blueprint $table) {
                $table->string('moneda', 3)->nullable();
            });
        }
    }

    private function indiceUnico(string $tabla, string $columna): ?string
    {
        foreach (Schema::getIndexes($tabla) as $indice) {
            $columnas = $indice['columns'] ?? [];
            $esPrimario = ($indice['primary'] ?? false) || ($indice['name'] ?? '') === 'primary';
            if (! $esPrimario && ($indice['unique'] ?? false) && $columnas === [$columna]) {
                return $indice['name'];
            }
        }

        return null;
    }

    private function quitarIndice(string $tabla, string $columna): void
    {
        foreach (Schema::getIndexes($tabla) as $indice) {
            if (($indice['columns'] ?? []) === [$columna] && ! ($indice['primary'] ?? false)) {
                Schema::table($tabla, function (Blueprint $table) use ($indice) {
                    $table->dropIndex($indice['name']);
                });
            }
        }
    }
};
