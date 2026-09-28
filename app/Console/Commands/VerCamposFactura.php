<?php

namespace App\Console\Commands;

use App\Services\ProveedorApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Muestra TODOS los campos crudos que devuelve ListarDocumentosRFC (facturas de compra)
 * para un proveedor con facturas, para ver si hay algún campo útil (estatus, activo,
 * tipo, etc.) que ayude a filtrar. Correr EN EL SERVIDOR.
 *
 * Uso: php artisan wiese:ver-campos-factura {rfc?}
 *   ej: php artisan wiese:ver-campos-factura GORJ560821SV8
 */
class VerCamposFactura extends Command
{
    protected $signature = 'wiese:ver-campos-factura {rfc=GORJ560821SV8}';

    protected $description = 'Muestra los campos crudos de las facturas de Wiese (ListarDocumentosRFC)';

    public function handle(ProveedorApiService $api): int
    {
        $rfc = (string) $this->argument('rfc');

        $login = $api->loginServicio();
        if (! ($login['success'] ?? false)) {
            $this->error('Login falló.');

            return self::FAILURE;
        }
        $token = (string) ($login['data']['tokenCreado'] ?? '');
        $docsUrl = rtrim((string) config('services.proveedor_api.docs_url', ''), '/');

        $resp = Http::connectTimeout(5)->timeout(60)->withToken($token)->acceptJson()
            ->get($docsUrl.'/DatoDocumentoProveedor/ListarDocumentosRFC', [
                'RFC' => strtoupper($rfc),
                'fechaInicial' => '2020-01-01T00:00:00',
                'fechaFinal' => '2026-12-31T23:59:59',
            ]);

        $body = $resp->json();

        // Si el RFC dado no tiene facturas ahora, buscar automáticamente uno que SÍ tenga
        // (Wiese cambia en tiempo real; un RFC puede quedarse sin pendientes en cualquier momento).
        if (! is_array($body) || $body === []) {
            $this->warn("Sin facturas para {$rfc}. Buscando automáticamente un proveedor con facturas...");
            $lista = $api->listarProveedoresWiese($token);
            $rfcs = collect($lista['data']['items'] ?? [])
                ->map(fn ($p) => strtoupper(trim((string) ($p['rfc'] ?? ''))))
                ->filter(fn ($r) => $r !== '' && $r !== 'XEXX010101000')
                ->unique()
                ->take(80);

            foreach ($rfcs as $r) {
                $rr = Http::connectTimeout(5)->timeout(60)->withToken($token)->acceptJson()
                    ->get($docsUrl.'/DatoDocumentoProveedor/ListarDocumentosRFC', [
                        'RFC' => $r,
                        'fechaInicial' => '2020-01-01T00:00:00',
                        'fechaFinal' => '2026-12-31T23:59:59',
                    ]);
                $b = $rr->json();
                if (is_array($b) && $b !== []) {
                    $body = $b;
                    $rfc = $r;
                    $this->info("Encontrado RFC con facturas: {$r}");
                    break;
                }
                usleep(120000);
            }

            if (! is_array($body) || $body === []) {
                $this->warn('No se encontró ningún proveedor con facturas en los primeros 80. Reintenta luego.');

                return self::SUCCESS;
            }
        }

        $primera = array_is_list($body) ? $body[0] : $body;
        $this->info("Facturas devueltas: ".count($body));
        $this->info('Campos de la PRIMERA factura (crudos de Wiese):');
        $this->line(json_encode($primera, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line('');
        $this->info('Lista de claves disponibles:');
        $this->line(implode(', ', array_keys((array) $primera)));

        return self::SUCCESS;
    }
}
