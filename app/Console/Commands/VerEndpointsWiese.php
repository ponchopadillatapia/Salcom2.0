<?php

namespace App\Console\Commands;

use App\Services\ProveedorApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Lista TODOS los endpoints del Swagger de Wiese (ruta + método + resumen).
 * Sirve para descubrir si hay uno que devuelva facturas/documentos de forma masiva
 * (todas las pendientes de golpe), en vez de ir proveedor por proveedor.
 *
 * Debe correrse EN EL SERVIDOR (que sí alcanza la red interna de Wiese).
 *
 * Uso: php artisan wiese:ver-endpoints
 */
class VerEndpointsWiese extends Command
{
    protected $signature = 'wiese:ver-endpoints {--filtro= : Solo mostrar rutas que contengan este texto (ej. Documento)}';

    protected $description = 'Lista los endpoints del Swagger de Wiese (para hallar uno masivo)';

    public function handle(ProveedorApiService $api): int
    {
        $base = rtrim((string) config('services.proveedor_api.docs_url', ''), '/');
        // El host sin /api al final (el swagger vive en la raíz).
        $host = preg_replace('#/api/?$#', '', $base);
        $filtro = mb_strtolower(trim((string) $this->option('filtro')));

        $urls = [
            $host.'/swagger/v1/swagger.json',
            $base.'/swagger/v1/swagger.json',
            $host.'/swagger/v1/swagger.yaml',
        ];

        foreach ($urls as $url) {
            $this->line("Probando: {$url}");
            try {
                $resp = Http::connectTimeout(5)->timeout(25)->get($url);
            } catch (\Throwable $e) {
                $this->warn('  fallo: '.$e->getMessage());

                continue;
            }
            if (! $resp->successful()) {
                $this->warn('  HTTP '.$resp->status());

                continue;
            }
            $json = $resp->json();
            if (! is_array($json) || ! isset($json['paths'])) {
                $this->warn('  sin paths');

                continue;
            }

            $this->info("=== ENDPOINTS DE WIESE ({$url}) ===");
            $filas = [];
            foreach ($json['paths'] as $ruta => $metodos) {
                if ($filtro !== '' && ! str_contains(mb_strtolower($ruta), $filtro)) {
                    continue;
                }
                foreach ($metodos as $verbo => $info) {
                    $sum = is_array($info) ? ($info['summary'] ?? $info['operationId'] ?? '') : '';
                    $filas[] = [strtoupper($verbo), $ruta, $sum];
                }
            }

            // Ordenar para que sea fácil de leer.
            usort($filas, fn ($a, $b) => strcmp($a[1], $b[1]));
            foreach ($filas as $f) {
                $this->line(str_pad($f[0], 6).$f[1].'   '.$f[2]);
            }
            $this->info('Total endpoints listados: '.count($filas));

            return self::SUCCESS;
        }

        $this->error('No se pudo leer el Swagger. Verifica la URL/puerto (¿7183?) y la red.');

        return self::FAILURE;
    }
}
