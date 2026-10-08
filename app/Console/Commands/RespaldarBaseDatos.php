<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Respaldo de la base de datos a un archivo .sql con fecha.
 *
 * POR QUÉ existe: MySQL en XAMPP se ha corrompido varias veces (ver DECISIONES.md).
 * Tener un export reciente convierte "reinstalar y perder todo" en "restaurar 1 archivo".
 * Usa mysqldump (la herramienta oficial que ya viene con XAMPP), no depende de phpMyAdmin.
 *
 * Uso:
 *   php artisan db:backup
 * El archivo queda en storage/backups/salcom20_AAAAMMDD_HHMMSS.sql
 */
class RespaldarBaseDatos extends Command
{
    protected $signature = 'db:backup {--keep=15 : Cuántos respaldos conservar (borra los más viejos)}';

    protected $description = 'Crea un respaldo .sql de la base de datos usando mysqldump';

    public function handle(): int
    {
        // Datos de conexión desde la config (vienen del .env). Así no se escriben a mano.
        $host = (string) config('database.connections.mysql.host', '127.0.0.1');
        $port = (string) config('database.connections.mysql.port', '3306');
        $base = (string) config('database.connections.mysql.database');
        $usuario = (string) config('database.connections.mysql.username');
        $password = (string) config('database.connections.mysql.password');

        if ($base === '') {
            $this->error('No hay base de datos configurada (DB_DATABASE vacío).');

            return self::FAILURE;
        }

        // Buscar mysqldump: primero en la ruta típica de XAMPP, luego en el PATH del sistema.
        $mysqldump = $this->ubicarMysqldump();
        if ($mysqldump === null) {
            $this->error('No se encontró mysqldump. Revisa que XAMPP esté instalado en C:\\xampp.');

            return self::FAILURE;
        }

        // Carpeta destino: storage/backups (se crea si no existe).
        $carpeta = storage_path('backups');
        File::ensureDirectoryExists($carpeta);

        $nombre = sprintf('%s_%s.sql', $base, now()->format('Ymd_His'));
        $rutaArchivo = $carpeta.DIRECTORY_SEPARATOR.$nombre;

        $this->info("Respaldando '{$base}' ...");

        // Armamos los argumentos como ARRAY (no como texto), para evitar problemas de
        // comillas/espacios y de inyección. proc_open recibe cada argumento por separado.
        $args = [
            $mysqldump,
            '--host='.$host,
            '--port='.$port,
            '--user='.$usuario,
        ];
        // POR QUÉ así la contraseña: si está vacía (root sin password, típico en XAMPP),
        // NO mandamos --password para que no truene. Si tiene, la mandamos pegada.
        if ($password !== '') {
            $args[] = '--password='.$password;
        }
        $args[] = '--single-transaction'; // respaldo consistente sin bloquear tablas
        $args[] = '--routines';           // incluye procedimientos/funciones si hay
        $args[] = $base;

        $descriptores = [
            1 => ['file', $rutaArchivo, 'w'],  // la salida (el .sql) va directo al archivo
            2 => ['pipe', 'w'],                // los errores los capturamos aparte
        ];

        $proceso = proc_open($args, $descriptores, $tuberias);
        if (! is_resource($proceso)) {
            $this->error('No se pudo iniciar mysqldump.');

            return self::FAILURE;
        }

        $errores = stream_get_contents($tuberias[2]);
        fclose($tuberias[2]);
        $codigo = proc_close($proceso);

        if ($codigo !== 0) {
            $this->error('mysqldump falló: '.trim($errores));
            // Borramos el archivo a medias para no dejar un respaldo corrupto.
            if (File::exists($rutaArchivo)) {
                File::delete($rutaArchivo);
            }

            return self::FAILURE;
        }

        $tam = File::exists($rutaArchivo) ? round(File::size($rutaArchivo) / 1024, 1) : 0;
        $this->info("Respaldo creado: {$rutaArchivo} ({$tam} KB)");

        $this->limpiarViejos($carpeta, $base, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    /**
     * Busca mysqldump.exe en las rutas típicas de XAMPP; si no, confía en el PATH.
     */
    private function ubicarMysqldump(): ?string
    {
        $candidatos = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:/xampp/mysql/bin/mysqldump.exe',
        ];
        foreach ($candidatos as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        // Último recurso: que el sistema lo resuelva por PATH.
        return 'mysqldump';
    }

    /**
     * Conserva solo los N respaldos más recientes de esta base; borra los más viejos.
     * POR QUÉ: para que la carpeta no crezca sin control con el respaldo diario.
     */
    private function limpiarViejos(string $carpeta, string $base, int $conservar): void
    {
        if ($conservar <= 0) {
            return;
        }

        $archivos = collect(File::files($carpeta))
            ->filter(fn ($f) => str_starts_with($f->getFilename(), $base.'_') && $f->getExtension() === 'sql')
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        $sobran = $archivos->slice($conservar);
        foreach ($sobran as $f) {
            File::delete($f->getPathname());
        }

        if ($sobran->isNotEmpty()) {
            $this->line("Se borraron {$sobran->count()} respaldo(s) viejo(s), se conservan {$conservar}.");
        }
    }
}
