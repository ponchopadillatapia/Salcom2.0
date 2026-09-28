<?php

namespace App\Console\Commands;

use App\Models\Factura;
use App\Models\ProveedorUser;
use App\Services\ProveedorApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Importa a la base LOCAL las facturas PENDIENTES (saldo > 0) de un proveedor de Wiese,
 * buscándolas por su RFC (endpoint ListarDocumentosRFC de Alan).
 *
 * POR QUÉ EXISTE: el flujo de pago (Formato → Pago → Abono) trabaja con facturas locales
 * (tabla `facturas`). Las facturas de Wiese que se muestran son solo lectura. Para poder
 * correr el flujo completo (y para las pruebas del equipo), este comando "baja" esas
 * facturas reales de Wiese a local, dejándolas listas para pagar.
 *
 * Uso:
 *   php artisan wiese:importar-facturas 103014037
 *   php artisan wiese:importar-facturas 103014037 --rfc=CON161108SZ1
 */
class ImportarFacturasWiese extends Command
{
    protected $signature = 'wiese:importar-facturas
        {codigo : Código Wiese del proveedor}
        {--rfc= : RFC del proveedor (si no se da, se busca en Wiese por el código)}';

    protected $description = 'Importa a local las facturas pendientes de un proveedor de Wiese (por RFC)';

    public function handle(ProveedorApiService $api): int
    {
        $codigo = trim((string) $this->argument('codigo'));
        $rfc = trim((string) ($this->option('rfc') ?? ''));

        // 1) Si no dieron RFC, buscar el proveedor en Wiese por código para obtenerlo.
        if ($rfc === '') {
            $this->info("Buscando proveedor {$codigo} en Wiese para obtener su RFC...");
            $res = $api->buscarProveedorWiese($codigo);
            if (! ($res['success'] ?? false)) {
                $this->error('No se encontró el proveedor en Wiese: '.($res['message'] ?? '???'));

                return self::FAILURE;
            }
            $d = $res['data'] ?? [];
            $rfc = trim((string) ($d['rfc'] ?? $d['Rfc'] ?? $d['crfc'] ?? $d['CRFC'] ?? ''));
        }

        if ($rfc === '') {
            $this->error('El proveedor no tiene RFC en Wiese; no se pueden traer sus facturas.');

            return self::FAILURE;
        }

        // 2) Traer las facturas pendientes de Wiese por RFC.
        $this->info("Consultando facturas de Wiese para RFC {$rfc}...");
        $fac = $api->listarFacturasProveedorPorRFC($rfc);
        if (! ($fac['success'] ?? false)) {
            $this->error('No se pudieron traer las facturas de Wiese: '.($fac['message'] ?? '???'));

            return self::FAILURE;
        }

        $pendientes = collect($fac['data']['items'] ?? [])->where('pendiente', true);
        if ($pendientes->isEmpty()) {
            $this->warn('Este proveedor no tiene facturas pendientes (saldo > 0) en Wiese. Nada que importar.');

            return self::SUCCESS;
        }

        // 3) Asegurar que el proveedor exista en local (para enlazar por código).
        $prov = ProveedorUser::porCualquierCodigo($codigo)->first();
        if (! $prov) {
            $primera = $pendientes->first();
            $prov = ProveedorUser::create([
                // usuario/password son obligatorios en la tabla. Este proveedor "nace" solo
                // para el flujo de pago (no inicia sesión), así que le damos un usuario único
                // por código y una contraseña aleatoria (no se usa para login real).
                'usuario' => 'wiese_'.$codigo,
                'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                'codigo' => $codigo,
                'id_proveedor' => $codigo,
                'nombre' => (string) ($primera['nombre'] ?? $codigo),
                'rfc' => $rfc,
                'moneda' => (string) ($primera['moneda'] ?? 'MXN') === 'USD' ? 'DOLLAR' : 'MXN',
                'datos_identificacion' => ['rfc' => $rfc],
                'activo' => false,
            ]);
            $this->info("Proveedor creado en local: {$prov->nombre} ({$codigo}).");
        }

        // 4) Insertar/actualizar cada factura pendiente en local.
        $creadas = 0;
        $actualizadas = 0;
        foreach ($pendientes as $f) {
            $folio = (string) ($f['folio'] ?? '');
            $total = (float) ($f['total'] ?? 0);
            $saldo = (float) ($f['saldo'] ?? 0);
            // Lo ya pagado = total - saldo (nunca negativo).
            $pagado = max($total - $saldo, 0);

            // Clave para no duplicar: mismo folio + mismo proveedor.
            $factura = Factura::withTrashed()->firstOrNew([
                'folio_cfdi' => $folio !== '' ? $folio : ('WIESE-'.($f['id_documento'] ?? uniqid())),
                'codigo_proveedor' => $codigo,
            ]);
            $existia = $factura->exists;

            $factura->fill([
                'monto' => $total,
                'monto_iva' => 0,
                'total' => $total,
                'monto_pagado' => $pagado,
                'estatus' => 'pendiente',
                'fecha_vencimiento' => ! empty($f['fecha_vence']) ? Carbon::parse($f['fecha_vence'])->toDateString() : null,
                'notas' => 'Importada de Wiese · serie '.($f['serie'] ?? '').' · idDoc '.($f['id_documento'] ?? ''),
            ]);
            if ($factura->trashed()) {
                $factura->restore();
            }
            $factura->save();

            $existia ? $actualizadas++ : $creadas++;
        }

        $this->info("Listo. Facturas creadas: {$creadas} · actualizadas: {$actualizadas}.");
        $this->line("Ahora abre Formato para pago → proveedor {$codigo} y verás sus facturas para pagar.");

        return self::SUCCESS;
    }
}
