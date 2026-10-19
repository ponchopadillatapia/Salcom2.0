<?php

namespace App\Support;

class PortalModulos
{
    /**
     * Prefijos de nombre de ruta por módulo.
     * Onboarding, perfil, alertas y logout no están aquí: siguen abiertos.
     *
     * @var array<string, list<string>>
     */
    private const RUTAS = [
        'inicio' => ['proveedores.portal', 'proveedores.dashboard'],
        'mis_productos' => ['proveedores.mis-productos'],
        'alta_producto' => ['proveedores.alta-producto'],
        'inventario' => ['proveedores.inventario'],
        'alta_facturas' => ['proveedores.fiscal'],
        'facturas' => ['proveedores.facturas'],
        'ordenes_compra' => ['proveedores.oc'],
        'otif' => ['proveedores.otif'],
        'forecast' => ['proveedores.forecast'],
        'business' => ['proveedores.business', 'proveedores.payment-history'],
        'ia' => ['proveedores.ia'],
        'encuesta' => ['proveedores.encuesta'],
    ];

    public static function habilitado(string $modulo): bool
    {
        if ($modulo === 'onboarding' || $modulo === 'perfil') {
            return true;
        }

        return (bool) config('salcom.portal_modulos.'.$modulo, false);
    }

    public static function rutaBloqueada(?string $ruta): bool
    {
        $modulo = self::moduloDeRuta($ruta);

        return $modulo !== null && ! self::habilitado($modulo);
    }

    public static function destino(): string
    {
        if (self::habilitado('facturas')) {
            return 'proveedores.facturas';
        }
        if (self::habilitado('alta_facturas')) {
            return 'proveedores.fiscal';
        }

        return 'proveedores.onboarding';
    }

    private static function moduloDeRuta(?string $ruta): ?string
    {
        if ($ruta === null || $ruta === '') {
            return null;
        }

        foreach (self::RUTAS as $modulo => $prefijos) {
            foreach ($prefijos as $prefijo) {
                if ($ruta === $prefijo || str_starts_with($ruta, $prefijo.'.')) {
                    return $modulo;
                }
            }
        }

        return null;
    }
}
