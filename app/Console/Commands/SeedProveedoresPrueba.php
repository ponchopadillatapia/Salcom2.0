<?php

namespace App\Console\Commands;

use App\Models\DocumentoProveedor;
use App\Models\ProveedorUser;
use Illuminate\Console\Command;

/**
 * Crea/actualiza proveedores de PRUEBA (ORPACK y FLEXITEK) para demos del flujo de facturas.
 *
 * POR QUÉ como comando artisan (y no script suelto): así se puede correr igual en local y en
 * SiteGround (producción) por SSH, de forma limpia y repetible. Es idempotente: si ya existen,
 * los actualiza y reactiva; no duplica.
 *
 * Uso:   php artisan facturas:seed-proveedores-prueba
 *        php artisan facturas:seed-proveedores-prueba --borrar   (los quita, para limpiar después de la demo)
 *
 * OJO: son proveedores de PRUEBA. En producción conviene borrarlos tras la demo con --borrar.
 */
class SeedProveedoresPrueba extends Command
{
    protected $signature = 'facturas:seed-proveedores-prueba {--borrar : Elimina los proveedores de prueba en vez de crearlos}';

    protected $description = 'Crea/activa ORPACK y FLEXITEK de prueba (con docs aprobados) para demos del flujo de facturas';

    /**
     * Proveedores de prueba. Las contraseñas son de DEMO (cámbialas si lo usas en producción real).
     *
     * @var array<int, array<string, mixed>>
     */
    private array $proveedores = [
        [
            'usuario' => 'wiese_M213015002',
            'password' => 'orpack123',
            'id_proveedor' => 'M213015002',
            'codigo' => 'M213015002',
            'nombre' => 'ORPACK DE MEXICO',
            'tipo_persona' => 'Persona Moral',
            'rfc' => 'OME0207015E8',
            'correo' => 'pruebas-orpack@test.com',
        ],
        [
            'usuario' => 'flexitek.prov',
            'password' => 'flexitek123',
            'id_proveedor' => 'M213006022',
            'codigo' => 'M213006022',
            'nombre' => 'FLEXITEK DE MEXICO',
            'tipo_persona' => 'Persona Moral',
            'rfc' => 'FME010503KZ7',
            'correo' => 'pruebas-flexitek@test.com',
        ],
    ];

    public function handle(): int
    {
        if ($this->option('borrar')) {
            return $this->borrar();
        }

        foreach ($this->proveedores as $p) {
            $prov = ProveedorUser::withTrashed()->where('usuario', $p['usuario'])->first();

            $datos = [
                'password' => bcrypt($p['password']),
                'id_proveedor' => $p['id_proveedor'],
                'codigo' => $p['codigo'],
                'nombre' => $p['nombre'],
                'moneda' => 'MXN',
                'tipo_persona' => $p['tipo_persona'],
                'es_extranjero' => false,
                'rfc' => $p['rfc'],
                'correo' => $p['correo'],
                'activo' => true,
            ];

            if ($prov) {
                if (method_exists($prov, 'trashed') && $prov->trashed()) {
                    $prov->restore();
                }
                $prov->update($datos);
                $this->info("Actualizado: {$p['usuario']} (id {$prov->id})");
            } else {
                $prov = ProveedorUser::create(array_merge(['usuario' => $p['usuario']], $datos));
                $this->info("Creado: {$p['usuario']} (id {$prov->id})");
            }

            // Aprobar todos los documentos fiscales requeridos para que quede "completo" y activo.
            foreach (array_keys($prov->documentosRequeridos()) as $tipo) {
                DocumentoProveedor::updateOrCreate(
                    ['proveedor_id' => $prov->id, 'tipo' => $tipo],
                    [
                        'archivo' => "pruebas/{$prov->id}/{$tipo}.pdf",
                        'estatus' => 'aprobado',
                        'notas_revision' => 'Aprobado automáticamente (proveedor de prueba para demo).',
                        'revisado_at' => now(),
                    ]
                );
            }

            $prov->load('documentos');
            $ok = $prov->documentosFiscalesCompletos() ? 'SI' : 'NO';
            $this->line("   contraseña demo: {$p['password']} | activo: SI | docs completos: {$ok}");
        }

        $this->newLine();
        $this->info('Listo. Proveedores de prueba dados de alta y activos.');
        $this->warn('Recuerda: son de PRUEBA. Tras la demo, límpialos con: php artisan facturas:seed-proveedores-prueba --borrar');

        return self::SUCCESS;
    }

    private function borrar(): int
    {
        foreach ($this->proveedores as $p) {
            $prov = ProveedorUser::withTrashed()->where('usuario', $p['usuario'])->first();
            if (! $prov) {
                $this->line("No existe: {$p['usuario']}");

                continue;
            }
            DocumentoProveedor::where('proveedor_id', $prov->id)->delete();
            $prov->forceDelete();
            $this->info("Borrado: {$p['usuario']}");
        }

        $this->info('Proveedores de prueba eliminados.');

        return self::SUCCESS;
    }
}
