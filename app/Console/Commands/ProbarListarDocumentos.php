<?php

namespace App\Console\Commands;

use App\Services\ProveedorApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Prueba el endpoint GET /api/DatoDocumentoProveedor/ListarDocumentos (sin RFC),
 * candidato a devolver las facturas de TODOS los proveedores de golpe (Opción 2).
 *
 * Prueba varias combinaciones de parámetros porque no sabemos cuáles pide.
 * Debe correrse EN EL SERVIDOR (alcanza la red de Wiese).
 *
 * Uso: php artisan wiese:probar-listar-documentos
 */
class ProbarListarDocumentos extends Command
{
    protected $signature = 'wiese:probar-listar-documentos';

    protected $description = 'Prueba ListarDocumentos (sin RFC) para ver si trae todo de golpe';

    public function handle(ProveedorApiService $api): int
    {
        // Login de servicio para el token.
        $login = $api->loginServicio();
        if (! ($login['success'] ?? false)) {
            $this->error('Login falló: '.($login['message'] ?? '???'));

            return self::FAILURE;
        }
        $token = (string) ($login['data']['tokenCreado'] ?? '');
        $this->info('Token OK.');

        $docsUrl = rtrim((string) config('services.proveedor_api.docs_url', ''), '/');
        // Probamos ListarAnticipoConSaldoPendiente: NO pide id/rfc de un proveedor,
        // así que es candidato a traer VARIOS/TODOS los documentos con saldo pendiente.
        $endpoint = '/Documento/ListarAnticipoConSaldoPendiente';

        // Rango de fechas seguro para SQL Server (no usar años extremos).
        $fi = '2020-01-01T00:00:00';
        $ff = '2026-12-31T23:59:59';

        // razonSocial y filtroDeDocumentos son OBLIGATORIOS. Probamos comodines para
        // intentar que traiga TODOS (razonSocial vacía no la acepta; probamos "%", "*", " ").
        $combos = [
            'razon="%" filtro="TODOS"' => ['fechaInicio' => $fi, 'fechaFinal' => $ff, 'filtroDeDocumentos' => 'TODOS', 'razonSocial' => '%'],
            'razon="*" filtro="TODOS"' => ['fechaInicio' => $fi, 'fechaFinal' => $ff, 'filtroDeDocumentos' => 'TODOS', 'razonSocial' => '*'],
            'razon=" " filtro="TODOS"' => ['fechaInicio' => $fi, 'fechaFinal' => $ff, 'filtroDeDocumentos' => 'TODOS', 'razonSocial' => ' '],
            'razon="%" filtro="%"' => ['fechaInicio' => $fi, 'fechaFinal' => $ff, 'filtroDeDocumentos' => '%', 'razonSocial' => '%'],
            'razon="A" filtro="TODOS"' => ['fechaInicio' => $fi, 'fechaFinal' => $ff, 'filtroDeDocumentos' => 'TODOS', 'razonSocial' => 'A'],
        ];

        foreach ($combos as $nombre => $params) {
            $this->line('');
            $this->info("Probando [{$nombre}] con params: ".json_encode($params));
            try {
                $resp = Http::connectTimeout(5)->timeout(120)->withToken($token)->acceptJson()
                    ->get($docsUrl.$endpoint, $params);
            } catch (\Throwable $e) {
                $this->warn('  error: '.$e->getMessage());

                continue;
            }

            $this->line('  HTTP: '.$resp->status());
            if (! $resp->successful()) {
                $this->warn('  cuerpo: '.substr((string) $resp->body(), 0, 300));

                continue;
            }
            $body = $resp->json();
            if (! is_array($body)) {
                $this->warn('  respuesta no es lista/JSON');

                continue;
            }
            $n = count($body);
            $this->info("  ¡OK! items devueltos: {$n}");
            if ($n > 0) {
                $primero = array_is_list($body) ? $body[0] : $body;
                $this->line('  primer item: '.json_encode($primero, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->info('  >>> Esta combinación SIRVE. Trae varios de golpe.');
            }
        }

        return self::SUCCESS;
    }
}
