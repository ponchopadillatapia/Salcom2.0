<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo interno de agentes aduanales de Industrias Salcom.
 *
 * La tabla aduanas se llena con una copia local del catálogo público c_Aduana
 * (CFDI 4.0). No consulta al SAT y no dice nada sobre la vigencia de una patente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aduanas')) {
            $this->alinearTablasExistentes();

            return;
        }

        $this->crearTablasNuevas();
    }

    private function crearTablasNuevas(): void
    {
        Schema::create('aduanas', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 2)->unique();
            $table->string('nombre');
            $table->string('entidad')->nullable();
            $table->string('descripcion');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('agentes_aduanales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('rfc', 13);
            $table->string('numero_patente', 4);
            $table->string('agencia')->nullable();
            $table->string('tipo_operacion', 20);
            $table->string('contacto_nombre')->nullable();
            $table->string('contacto_correo')->nullable();
            $table->string('contacto_telefono', 20)->nullable();
            $table->string('contacto_celular', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->string('estado_verificacion', 30)->default('no_verificado');
            $table->date('fecha_ultima_verificacion')->nullable();
            $table->string('fuente_verificacion', 30)->nullable();
            $table->string('fuente_detalle')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique('rfc');
            $table->unique('numero_patente');
            $table->index('activo');
            $table->index('estado_verificacion');
            $table->index('tipo_operacion');
        });

        Schema::create('agente_aduanal_aduana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->cascadeOnDelete();
            $table->foreignId('aduana_id')->constrained('aduanas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['agente_aduanal_id', 'aduana_id']);
        });

        Schema::create('documentos_agente_aduanal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('nombre_archivo');
            $table->string('ruta');
            $table->date('fecha_carga');
            $table->date('fecha_vencimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();

            $table->index('tipo');
            $table->index('fecha_vencimiento');
        });

        Schema::create('encargos_conferidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->cascadeOnDelete();
            $table->string('numero_patente', 4);
            $table->date('fecha_inicio');
            $table->date('fecha_termino')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->string('numero_acuse', 80)->nullable();
            $table->string('nombre_acuse')->nullable();
            $table->string('ruta_acuse')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('fecha_termino');
        });

        Schema::create('operaciones_comercio_exterior', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_aduanal_id')->nullable()->constrained('agentes_aduanales')->nullOnDelete();
            $table->foreignId('aduana_id')->nullable()->constrained('aduanas')->nullOnDelete();
            $table->string('numero_patente', 4)->nullable();
            $table->string('numero_pedimento', 30)->nullable();
            $table->date('fecha')->nullable();
            $table->string('contraparte')->nullable();
            $table->string('mercancia')->nullable();
            $table->string('tipo_operacion', 20);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('numero_pedimento');
            $table->index('tipo_operacion');
            $table->index('fecha');
        });

        DB::table('aduanas')->insert($this->filasCatalogo());
    }

    /**
     * La base de Salcom ya tenía estas tablas (migración 2026_09_30).
     * Se renombran columnas y se completan aduanas sin borrar al agente capturado.
     */
    private function alinearTablasExistentes(): void
    {
        Schema::table('aduanas', function (Blueprint $table) {
            if (! Schema::hasColumn('aduanas', 'entidad')) {
                $table->string('entidad')->nullable()->after('nombre');
            }
            if (! Schema::hasColumn('aduanas', 'descripcion')) {
                $table->string('descripcion')->nullable();
            }
        });

        $this->renombrarColumna('agentes_aduanales', 'nombre_completo', 'nombre');
        $this->renombrarColumna('agentes_aduanales', 'agencia_sociedad', 'agencia');
        if (! Schema::hasColumn('agentes_aduanales', 'fuente_detalle')) {
            Schema::table('agentes_aduanales', function (Blueprint $table) {
                $table->string('fuente_detalle')->nullable()->after('fuente_verificacion');
            });
        }

        $this->renombrarColumna('encargos_conferidos', 'referencia_acuse', 'numero_acuse');
        $this->renombrarColumna('encargos_conferidos', 'archivo_acuse', 'ruta_acuse');
        $this->renombrarColumna('encargos_conferidos', 'nombre_archivo_acuse', 'nombre_acuse');
        $this->renombrarColumna('operaciones_comercio_exterior', 'contraparte_nombre', 'contraparte');

        if (DB::getDriverName() === 'mysql') {
            $columna = DB::selectOne("SHOW COLUMNS FROM documentos_agente_aduanal WHERE Field = 'fecha_carga'");
            if ($columna && ! str_starts_with(strtolower((string) $columna->Type), 'date')) {
                DB::statement('ALTER TABLE documentos_agente_aduanal MODIFY fecha_carga DATE NOT NULL');
            }
        }

        $this->completarAduanas();
        $this->normalizarFuentes();
    }

    private function renombrarColumna(string $tabla, string $desde, string $hacia): void
    {
        if (Schema::hasColumn($tabla, $desde) && ! Schema::hasColumn($tabla, $hacia)) {
            Schema::table($tabla, function (Blueprint $table) use ($desde, $hacia) {
                $table->renameColumn($desde, $hacia);
            });
        }
    }

    private function completarAduanas(): void
    {
        $filas = $this->filasCatalogo();
        $usadas = [];

        foreach (DB::table('aduanas')->get() as $aduana) {
            $nombre = $this->normalizar((string) $aduana->nombre);
            $coincidencia = null;
            foreach ($filas as $fila) {
                if ($this->normalizar($fila['nombre']) === $nombre) {
                    $coincidencia = $fila;
                    break;
                }
            }

            if ($coincidencia) {
                DB::table('aduanas')->where('id', $aduana->id)->update([
                    'clave' => $coincidencia['clave'],
                    'entidad' => $coincidencia['entidad'],
                    'descripcion' => $coincidencia['descripcion'],
                    'updated_at' => now(),
                ]);
                $usadas[$coincidencia['clave']] = true;
                continue;
            }

            if (empty($aduana->descripcion)) {
                DB::table('aduanas')->where('id', $aduana->id)->update([
                    'descripcion' => $aduana->nombre,
                    'updated_at' => now(),
                ]);
            }
        }

        $nuevas = array_values(array_filter(
            $filas,
            fn (array $fila) => ! isset($usadas[$fila['clave']])
        ));
        if ($nuevas !== []) {
            DB::table('aduanas')->insert($nuevas);
        }
    }

    private function normalizarFuentes(): void
    {
        if (! Schema::hasColumn('agentes_aduanales', 'fuente_verificacion')) {
            return;
        }

        foreach (DB::table('agentes_aduanales')->get(['id', 'fuente_verificacion']) as $agente) {
            $actual = trim((string) $agente->fuente_verificacion);
            if ($actual === '' || in_array($actual, ['sat', 'anam', 'caaarem', 'otra'], true)) {
                continue;
            }

            $plano = $this->normalizar($actual);
            $codigo = 'otra';
            $detalle = $actual;
            if (str_contains($plano, 'sat') || str_contains($plano, 'padron')) {
                $codigo = 'sat';
                $detalle = null;
            } elseif (str_contains($plano, 'anam')) {
                $codigo = 'anam';
                $detalle = null;
            } elseif (str_contains($plano, 'caaarem')) {
                $codigo = 'caaarem';
                $detalle = null;
            }

            DB::table('agentes_aduanales')->where('id', $agente->id)->update([
                'fuente_verificacion' => $codigo,
                'fuente_detalle' => $detalle,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('migrations')
            && DB::table('migrations')->where('migration', '2026_09_30_120000_create_agentes_aduanales_tables')->exists()) {
            return;
        }

        Schema::dropIfExists('operaciones_comercio_exterior');
        Schema::dropIfExists('encargos_conferidos');
        Schema::dropIfExists('documentos_agente_aduanal');
        Schema::dropIfExists('agente_aduanal_aduana');
        Schema::dropIfExists('agentes_aduanales');
        Schema::dropIfExists('aduanas');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filasCatalogo(): array
    {
        $ahora = now();
        $filas = [];
        foreach ($this->catalogoAduanas() as [$clave, $descripcion]) {
            $partes = array_values(array_filter(array_map(
                'trim',
                explode(',', trim($descripcion, " \t.,"))
            ), fn (string $parte) => $parte !== ''));
            $filas[] = [
                'clave' => $clave,
                'nombre' => $this->titulo($partes[0] ?? $descripcion),
                'entidad' => count($partes) > 1 ? $this->titulo($partes[count($partes) - 1]) : null,
                'descripcion' => $descripcion,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        return $filas;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return preg_replace('/\s+/', ' ', $texto) ?? $texto;
    }

    private function titulo(string $texto): string
    {
        return mb_convert_case(mb_strtolower($texto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Copia local del catálogo c_Aduana publicado para CFDI 4.0.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function catalogoAduanas(): array
    {
        return [
            ['01', 'ACAPULCO, ACAPULCO DE JUAREZ, GUERRERO.'],
            ['02', 'AGUA PRIETA, AGUA PRIETA, SONORA.'],
            ['05', 'SUBTENIENTE LOPEZ, SUBTENIENTE LOPEZ, QUINTANA ROO.'],
            ['06', 'CIUDAD DEL CARMEN, CIUDAD DEL CARMEN, CAMPECHE.'],
            ['07', 'CIUDAD JUAREZ, CIUDAD JUAREZ, CHIHUAHUA.'],
            ['08', 'COATZACOALCOS, COATZACOALCOS, VERACRUZ.'],
            ['11', 'ENSENADA, ENSENADA, BAJA CALIFORNIA.'],
            ['12', 'GUAYMAS, GUAYMAS, SONORA.'],
            ['14', 'LA PAZ, LA PAZ, BAJA CALIFORNIA SUR.'],
            ['16', 'MANZANILLO, MANZANILLO, COLIMA.'],
            ['17', 'MATAMOROS, MATAMOROS, TAMAULIPAS.'],
            ['18', 'MAZATLAN, MAZATLAN, SINALOA.'],
            ['19', 'MEXICALI, MEXICALI, BAJA CALIFORNIA.'],
            ['20', 'MEXICO, DISTRITO FEDERAL.'],
            ['22', 'NACO, NACO, SONORA.'],
            ['23', 'NOGALES, NOGALES, SONORA.'],
            ['24', 'NUEVO LAREDO, NUEVO LAREDO, TAMAULIPAS.'],
            ['25', 'OJINAGA, OJINAGA, CHIHUAHUA.'],
            ['26', 'PUERTO PALOMAS, PUERTO PALOMAS, CHIHUAHUA.'],
            ['27', 'PIEDRAS NEGRAS, PIEDRAS NEGRAS, COAHUILA.'],
            ['28', 'PROGRESO, PROGRESO, YUCATAN.'],
            ['30', 'CIUDAD REYNOSA, CIUDAD REYNOSA, TAMAULIPAS.'],
            ['31', 'SALINA CRUZ, SALINA CRUZ, OAXACA.'],
            ['33', 'SAN LUIS RIO COLORADO, SAN LUIS RIO COLORADO, SONORA.'],
            ['34', 'CIUDAD MIGUEL ALEMAN, CIUDAD MIGUEL ALEMAN, TAMAULIPAS.'],
            ['37', 'CIUDAD HIDALGO, CIUDAD HIDALGO, CHIAPAS.'],
            ['38', 'TAMPICO, TAMPICO, TAMAULIPAS.'],
            ['39', 'TECATE, TECATE, BAJA CALIFORNIA.'],
            ['40', 'TIJUANA, TIJUANA, BAJA CALIFORNIA.'],
            ['42', 'TUXPAN, TUXPAN DE RODRIGUEZ CANO, VERACRUZ.'],
            ['43', 'VERACRUZ, VERACRUZ, VERACRUZ.'],
            ['44', 'CIUDAD ACUÑA, CIUDAD ACUÑA, COAHUILA.'],
            ['46', 'TORREON, TORREON, COAHUILA.'],
            ['47', 'AEROPUERTO INTERNACIONAL DE LA CIUDAD DE MEXICO,'],
            ['48', 'GUADALAJARA, TLACOMULCO DE ZUÑIGA, JALISCO.'],
            ['50', 'SONOYTA, SONOYTA, SONORA.'],
            ['51', 'LAZARO CARDENAS, LAZARO CARDENAS, MICHOACAN.'],
            ['52', 'MONTERREY, GENERAL MARIANO ESCOBEDO, NUEVO LEON.'],
            ['53', 'CANCUN, CANCUN, QUINTANA ROO.'],
            ['64', 'QUERÉTARO, EL MARQUÉS Y COLON, QUERÉTARO.'],
            ['65', 'TOLUCA, TOLUCA, ESTADO DE MEXICO.'],
            ['67', 'CHIHUAHUA, CHIHUAHUA, CHIHUAHUA.'],
            ['73', 'AGUASCALIENTES, AGUASCALIENTES, AGUASCALIENTES.'],
            ['75', 'PUEBLA, HEROICA PUEBLA DE ZARAGOZA, PUEBLA.'],
            ['80', 'COLOMBIA, COLOMBIA, NUEVO LEON.'],
            ['81', 'ALTAMIRA, ALTAMIRA, TAMAULIPAS.'],
            ['82', 'CIUDAD CAMARGO, CIUDAD CAMARGO, TAMAULIPAS.'],
            ['83', 'DOS BOCAS, PARAISO, TABASCO.'],
            ['84', 'GUANAJUATO, SILAO, GUANAJUATO.'],
            ['85', 'AEROPUERTO INTERNACIONAL FELIPE ÁNGELES, SANTA LUCÍA, ZUMPANGO, ESTADO DE MÉXICO.'],
        ];
    }
};
