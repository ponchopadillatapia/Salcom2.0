<?php

namespace App\Console\Commands;

use App\Models\Factura;
use App\Models\ProveedorUser;
use App\Services\ProveedorApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Str;

/**
 * PRECARGA MASIVA: recorre TODOS los proveedores de Wiese, consulta sus facturas
 * pendientes (saldo > 0) por RFC y las importa a la base local. Así "Formato para pago"
 * muestra a TODOS los que deben, sin tener que abrir cada proveedor a mano.
 *
 * IMPORTANTE (rendimiento):
 *   - Es LENTO la primera vez (una llamada a Wiese por proveedor con RFC). Puede tardar
 *     varios minutos. Corre en la TERMINAL del servidor, NO afecta la web del usuario.
 *   - Se corre 1 vez para arrancar, y luego periódicamente (ej. diario) para mantener al día.
 *   - Usa firstOrNew: no duplica facturas; si ya existen, las actualiza.
 *
 * Uso:
 *   php artisan wiese:precargar-facturas
 *   php artisan wiese:precargar-facturas --limite=500   (solo los primeros 500, para probar)
 */
class PrecargarFacturasWiese extends Command
{
    protected $signature = 'wiese:precargar-facturas
        {--limite=0 : Máximo de proveedores a procesar (0 = todos)}';

    protected $description = 'Precarga a local las facturas pendientes de TODOS los proveedores de Wiese';

    public function handle(ProveedorApiService $api): int
    {
        $limite = (int) $this->option('limite');

        $this->info('Trayendo directorio de proveedores de Wiese...');
        $lista = $api->listarProveedoresWiese();
        if (! ($lista['success'] ?? false)) {
            $this->error('No se pudo traer el directorio: '.($lista['message'] ?? '???'));

            return self::FAILURE;
        }

        // Proveedores con RFC válido (sin el genérico de extranjeros, que es ambiguo).
        $items = collect($lista['data']['items'] ?? [])
            ->map(function ($p) {
                return [
                    'codigo' => trim((string) ($p['codigo'] ?? $p['Codigo'] ?? '')),
                    'rfc' => strtoupper(trim((string) ($p['rfc'] ?? $p['Rfc'] ?? ''))),
                    'nombre' => trim((string) ($p['nombre'] ?? $p['Nombre'] ?? '')),
                    'moneda' => (string) ($p['moneda'] ?? $p['Moneda'] ?? ''),
                ];
            })
            ->filter(fn ($p) => $p['codigo'] !== '' && $p['rfc'] !== '' && $p['rfc'] !== 'XEXX010101000')
            // Un mismo RFC puede estar en varios códigos; procesamos todos, pero de-dup por código.
            ->unique('codigo')
            ->values();

        if ($limite > 0) {
            $items = $items->take($limite);
        }

        $total = $items->count();
        $this->info("Procesando {$total} proveedores. Esto puede tardar varios minutos...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $conFacturas = 0;
        $facturasCreadas = 0;
        $facturasActualizadas = 0;
        $errores = 0;

        foreach ($items as $p) {
            try {
                // Consultar Wiese con hasta 3 reintentos. POR QUÉ: al hacer muchas llamadas
                // seguidas Wiese a veces rechaza/tarda; un pequeño reintento con pausa recupera
                // la mayoría de esos fallos temporales (evita cientos de "omitidos").
                $fac = null;
                for ($intento = 1; $intento <= 3; $intento++) {
                    $fac = $api->listarFacturasProveedorPorRFC($p['rfc']);
                    if ($fac['success'] ?? false) {
                        break;
                    }
                    usleep(300000); // 0.3s antes de reintentar
                }
                if (! ($fac['success'] ?? false)) {
                    $errores++;
                    $bar->advance();
                    usleep(150000); // pausa igual para no saturar

                    continue;
                }
                $pendientes = collect($fac['data']['items'] ?? [])->where('pendiente', true);
                if ($pendientes->isEmpty()) {
                    $bar->advance();

                    continue; // este proveedor no debe nada; se salta
                }

                $conFacturas++;

                // Asegurar el proveedor en local (para enlazar por código).
                $prov = ProveedorUser::porCualquierCodigo($p['codigo'])->first();
                if (! $prov) {
                    ProveedorUser::create([
                        'usuario' => 'wiese_'.$p['codigo'],
                        'password' => bcrypt(Str::random(32)),
                        'codigo' => $p['codigo'],
                        'id_proveedor' => $p['codigo'],
                        'nombre' => $p['nombre'] !== '' ? $p['nombre'] : $p['codigo'],
                        'rfc' => $p['rfc'],
                        'moneda' => $p['moneda'] === '2' ? 'DOLLAR' : 'MXN',
                        'datos_identificacion' => ['rfc' => $p['rfc']],
                        'activo' => false,
                    ]);
                }

                foreach ($pendientes as $f) {
                    $folio = (string) ($f['folio'] ?? '');
                    $totalF = (float) ($f['total'] ?? 0);
                    $saldo = (float) ($f['saldo'] ?? 0);
                    $pagado = max($totalF - $saldo, 0);

                    $factura = Factura::withTrashed()->firstOrNew([
                        'folio_cfdi' => $folio !== '' ? $folio : ('WIESE-'.($f['id_documento'] ?? uniqid())),
                        'codigo_proveedor' => $p['codigo'],
                    ]);
                    $existia = $factura->exists;
                    $factura->fill([
                        'monto' => $totalF,
                        'monto_iva' => 0,
                        'total' => $totalF,
                        'monto_pagado' => $pagado,
                        'estatus' => 'pendiente',
                        'regimen_fiscal' => $factura->regimen_fiscal ?: '601',
                        'fecha_vencimiento' => ! empty($f['fecha_vence']) ? Carbon::parse($f['fecha_vence'])->toDateString() : null,
                        'notas' => 'Importada de Wiese (precarga) · serie '.($f['serie'] ?? '').' · idDoc '.($f['id_documento'] ?? ''),
                        'validacion_detalle' => array_merge(
                            is_array($factura->validacion_detalle) ? $factura->validacion_detalle : [],
                            [
                                'forma_pago' => '03',
                                'metodo_pago' => 'PUE',
                                'uso_cfdi' => 'G03',
                                'regimen_fiscal' => '601',
                                'producto' => 'Importado de Wiese',
                            ]
                        ),
                    ]);
                    if ($factura->trashed()) {
                        $factura->restore();
                    }
                    $factura->save();
                    $existia ? $facturasActualizadas++ : $facturasCreadas++;
                }
            } catch (\Throwable $e) {
                $errores++;
            }
            $bar->advance();
            // Pausa breve entre proveedores para no saturar Wiese (rate limiting suave).
            usleep(120000); // 0.12s
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Precarga terminada.');
        $this->line("  Proveedores con facturas pendientes: {$conFacturas}");
        $this->line("  Facturas creadas: {$facturasCreadas} · actualizadas: {$facturasActualizadas}");
        if ($errores > 0) {
            $this->warn("  Proveedores con error/omitidos: {$errores} (Wiese no respondió para esos; puedes reintentar).");
        }
        $this->line('Ahora Formato para pago mostrará a todos los que tienen facturas pendientes.');

        return self::SUCCESS;
    }
}
