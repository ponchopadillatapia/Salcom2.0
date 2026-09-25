<?php

namespace App\Console\Commands;

use App\Models\Alerta;
use App\Models\ClienteUser;
use App\Models\ContactoProveedor;
use App\Models\DocumentoProveedor;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\Muestra;
use App\Models\Notificacion;
use App\Models\OcBorrador;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\ProveedorUser;
use App\Models\TrackingPedido;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * POR QUÉ: dirección pidió dejar el dashboard "en cero" borrando SOLO los datos
 * sembrados por los seeders de prueba/demo, sin tocar datos reales ni la estructura.
 *
 * Este comando NO hace TRUNCATE de tablas completas. Borra únicamente los registros
 * que tienen las "huellas" conocidas de los seeders (códigos/folios/usuarios fijos).
 * Así, si en producción ya existen proveedores o facturas reales, quedan intactos.
 *
 * Seguridad:
 *  - Por defecto corre en simulación (--dry-run implícito): solo CUENTA, no borra.
 *  - Para borrar de verdad hay que pasar --force.
 *  - Todo el borrado va dentro de una transacción (si algo truena, no queda a medias).
 *
 * NO toca: admin_users, empleados. (Usuarios del panel y empleados dados de alta.)
 */
class LimpiarDatosPrueba extends Command
{
    protected $signature = 'salcom:limpiar-prueba {--force : Ejecuta el borrado real (sin esta bandera solo simula)}';

    protected $description = 'Borra SOLO los datos de prueba/demo sembrados por los seeders (deja el dashboard en cero).';

    /** Usuarios de proveedores creados por los seeders de prueba. */
    private const PROV_USUARIOS_PRUEBA = [
        'PROV001', 'PROV002', 'PROV003',
        'said', 'demo', 'Rebeca', 'sinonboarding',
        'proveedor.test', 'said.padilla',
        'diegococca@gmail.com',
        // FAKEPAGO1..FAKEPAGO10 se agregan abajo por patrón
    ];

    /** Códigos de cliente de prueba (ClienteUserSeeder). */
    private const CLI_USUARIOS_PRUEBA = ['CLI001', 'CLI002'];

    /** Códigos de producto de prueba (SAL-001..SAL-015). */
    private const PRODUCTO_PREFIJO = 'SAL-';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        if (! $force) {
            $this->warn('MODO SIMULACIÓN (dry-run). No se borrará nada. Usa --force para ejecutar de verdad.');
        } else {
            $this->error('MODO REAL: se borrarán los datos de prueba de forma PERMANENTE.');
            if (! $this->confirm('¿Seguro que quieres continuar en esta base de datos?', false)) {
                $this->info('Cancelado. No se borró nada.');
                return self::SUCCESS;
            }
        }

        // Lista completa de usuarios de proveedores de prueba (fijos + FAKEPAGO1..10)
        $provUsuarios = self::PROV_USUARIOS_PRUEBA;
        for ($i = 1; $i <= 10; $i++) {
            $provUsuarios[] = 'FAKEPAGO'.$i;
        }

        // IDs de proveedores de prueba (incluye soft-deleted por si acaso)
        $provIds = ProveedorUser::withTrashed()
            ->whereIn('usuario', $provUsuarios)
            ->pluck('id')
            ->all();

        // ── Conteos de lo que se va a borrar (para reportar) ──
        $plan = [];

        // Facturas: por folio de prueba O por proveedor/cliente de prueba
        $facturasQuery = Factura::where(function ($q) {
            $q->where('folio_cfdi', 'like', 'CFDI-A-%')
                ->orWhere('folio_cfdi', 'like', 'CFDI-P-%')
                ->orWhere('folio_cfdi', 'like', 'FAKE-PAGO-%')
                ->orWhere('folio_cfdi', 'like', 'SAID-FAC-%');
        })->orWhereIn('codigo_cliente', self::CLI_USUARIOS_PRUEBA)
          ->orWhereIn('codigo_cliente', ['CLI-2026-001', 'CLI-2026-002']);
        $plan['facturas'] = (clone $facturasQuery)->count();

        // Pedidos de prueba por folio
        $pedidosQuery = Pedido::where('folio', 'like', 'PED-2025-%')
            ->orWhere('folio', 'like', 'PED-2026-%');
        $plan['pedidos'] = (clone $pedidosQuery)->count();

        // Tracking ligado a esos pedidos
        $pedidoIds = (clone $pedidosQuery)->pluck('id')->all();
        $trackingQuery = TrackingPedido::whereIn('pedido_id', $pedidoIds);
        $plan['tracking_pedidos'] = $pedidoIds ? (clone $trackingQuery)->count() : 0;

        // Productos SAL-*
        $productosQuery = Producto::where('codigo', 'like', self::PRODUCTO_PREFIJO.'%');
        $plan['productos'] = (clone $productosQuery)->count();

        // Muestras LOTE-2026-*
        $muestrasQuery = Muestra::where('lote', 'like', 'LOTE-2026-%');
        $plan['muestras'] = (clone $muestrasQuery)->count();

        // Encuestas de los clientes de prueba
        $encuestasQuery = Encuesta::whereIn('codigo_cliente', ['CLI-2026-001', 'CLI-2026-002']);
        $plan['encuestas'] = (clone $encuestasQuery)->count();

        // Documentos y contactos de los proveedores de prueba
        $docsQuery = DocumentoProveedor::whereIn('proveedor_id', $provIds);
        $plan['documentos_proveedor'] = $provIds ? (clone $docsQuery)->count() : 0;

        $contactosQuery = ContactoProveedor::whereIn('proveedor_id', $provIds);
        $plan['contactos_proveedor'] = $provIds ? (clone $contactosQuery)->count() : 0;

        // Alertas: todas las del seeder de demo (sugerencias IA, oc_nueva, etc.) ligadas
        // a proveedores de prueba, más las notificaciones de pago fake.
        $alertasQuery = Alerta::where(function ($q) use ($provIds) {
            $q->where(function ($q2) use ($provIds) {
                $q2->where('destinatario_tipo', 'proveedor')
                    ->whereIn('destinatario_id', $provIds);
            })->orWhere('contenido', 'like', '%FAKE-PAGO-%');
        });
        $plan['alertas'] = (clone $alertasQuery)->count();

        // OC borradores ligadas a proveedores de prueba
        $ocQuery = OcBorrador::whereIn('proveedor_id', $provIds);
        $plan['oc_borradores'] = $provIds ? (clone $ocQuery)->count() : 0;

        // Notificaciones de los clientes/proveedores de prueba
        $notifQuery = Notificacion::whereIn('codigo_usuario', ['CLI-2026-001', 'CLI-2026-002', '102003240', '102003241', '102003242']);
        $plan['notificaciones'] = (clone $notifQuery)->count();

        // Proveedores y clientes de prueba (al final, ya sin dependencias)
        $plan['proveedores_users'] = count($provIds);
        $clientesQuery = ClienteUser::whereIn('usuario', self::CLI_USUARIOS_PRUEBA);
        $plan['clientes_users'] = (clone $clientesQuery)->count();

        // ── Reporte ──
        $this->newLine();
        $this->line('Registros de PRUEBA detectados:');
        foreach ($plan as $tabla => $cnt) {
            $this->line(sprintf('  %-22s %d', $tabla, $cnt));
        }
        $this->newLine();

        if (! $force) {
            $this->info('Nada borrado (simulación). Revisa los números y vuelve a correr con --force.');
            return self::SUCCESS;
        }

        // ── Borrado real, en transacción y en orden seguro (hijos antes que padres) ──
        DB::transaction(function () use (
            $trackingQuery, $facturasQuery, $encuestasQuery, $muestrasQuery,
            $notifQuery, $alertasQuery, $ocQuery, $docsQuery, $contactosQuery,
            $pedidosQuery, $productosQuery, $clientesQuery, $provIds
        ) {
            $trackingQuery->delete();
            $facturasQuery->delete();
            $encuestasQuery->delete();
            $muestrasQuery->delete();
            $notifQuery->delete();
            $alertasQuery->delete();
            $ocQuery->delete();
            $docsQuery->delete();
            $contactosQuery->delete();
            $pedidosQuery->delete();
            $productosQuery->delete();
            $clientesQuery->delete();
            // Proveedores de prueba: forceDelete para que no queden como soft-deleted
            if ($provIds) {
                ProveedorUser::withTrashed()->whereIn('id', $provIds)->forceDelete();
            }
        });

        $this->newLine();
        $this->info('Listo. Datos de prueba borrados. El dashboard debería quedar en cero.');
        return self::SUCCESS;
    }
}
