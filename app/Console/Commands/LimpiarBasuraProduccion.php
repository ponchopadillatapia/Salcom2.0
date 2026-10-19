<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Borra archivos BASURA de desarrollo/pruebas que quedaron en el proyecto y NO deben estar en
 * producción (scripts sueltos de Python/PHP de auditoría, salidas de prueba, respaldos sueltos).
 *
 * POR QUÉ un comando con lista fija (y no borrar a ciegas): así se sabe EXACTAMENTE qué se borra,
 * pide confirmación, y nunca toca carpetas del sistema (app, config, vendor, storage/app/public, etc.).
 * Solo borra archivos sueltos conocidos de la raíz y scripts .php/.txt de storage/app.
 *
 * USO:
 *   php artisan limpiar:basura            → muestra qué borraría y pide confirmar
 *   php artisan limpiar:basura --force    → borra sin preguntar (para scripts)
 */
class LimpiarBasuraProduccion extends Command
{
    protected $signature = 'limpiar:basura {--force : Borra sin pedir confirmación}';

    protected $description = 'Borra archivos basura de pruebas/desarrollo que no deben estar en producción.';

    /**
     * Archivos sueltos de la RAÍZ que son basura de desarrollo (rutas relativas a base_path()).
     * NO se incluye deploy.sh (puede ser útil) ni ningún archivo del sistema.
     */
    private array $archivosRaiz = [
        'analizar_prefijos.py',
        'analizar_prefijos_v2.py',
        'fix_e4.py',
        'fix_mpi.py',
        'test-output.txt',
        'test_200_alta.php',
        'clasificaciones_opciones.json',
        'salcom20',
        'RESPUESTAS-AWS.md',
        'PREGUNTAS-PARA-PLANTA.md',
    ];

    public function handle(): int
    {
        // 1) Archivos sueltos de la raíz (lista fija).
        $aBorrar = [];
        foreach ($this->archivosRaiz as $rel) {
            $full = base_path($rel);
            if (is_file($full)) {
                $aBorrar[] = $full;
            }
        }

        // 2) Scripts de auditoría/prueba sueltos en storage/app (NO toca public/ ni private/).
        //    Solo archivos directamente en storage/app con extensión .php/.txt/.json/.csv/.tinker
        //    que son claramente de pruebas (prefijos conocidos).
        $dirStorageApp = storage_path('app');
        $prefijosPrueba = ['fix_', 'check_', 'peek_', 'count_', 'audit', 'auditoria', 'diag_',
            'verify_', 'inspect_', 'sample_', 'show_', 'list_', 'missing_', 'mostrar_',
            'reporte_', 'estructura_', 'dryrun_', 'apply_', 'casos_', 'codigos_', 'fallbacks_',
            'prefijos_', 'chapalita_', 'orpack_', 'productos_fallidos'];
        $extsPrueba = ['php', 'txt', 'json', 'csv', 'tinker', 'tsv'];

        foreach ((array) @scandir($dirStorageApp) as $nombre) {
            if ($nombre === '.' || $nombre === '..') {
                continue;
            }
            $full = $dirStorageApp.DIRECTORY_SEPARATOR.$nombre;
            if (! is_file($full)) {
                continue; // NO toca carpetas (public, private, plantillas...)
            }
            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            if (! in_array($ext, $extsPrueba, true)) {
                continue;
            }
            $nombreLower = strtolower($nombre);
            foreach ($prefijosPrueba as $pref) {
                if (str_starts_with($nombreLower, $pref)) {
                    $aBorrar[] = $full;
                    break;
                }
            }
        }

        if ($aBorrar === []) {
            $this->info('No se encontró basura que borrar. Todo limpio.');

            return self::SUCCESS;
        }

        $this->warn('Se van a BORRAR estos archivos de basura (NO son del sistema):');
        foreach ($aBorrar as $f) {
            $this->line('  • '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $f));
        }
        $this->line('Total: '.count($aBorrar).' archivo(s).');

        if (! $this->option('force') && ! $this->confirm('¿Borrar estos archivos? (no se puede deshacer)')) {
            $this->info('Cancelado. No se borró nada.');

            return self::SUCCESS;
        }

        $borrados = 0;
        foreach ($aBorrar as $f) {
            if (@unlink($f)) {
                $borrados++;
            }
        }

        $this->info("✅ Borrados {$borrados} archivo(s) de basura.");

        return self::SUCCESS;
    }
}
