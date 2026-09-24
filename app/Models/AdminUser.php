<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'admin_users';

    protected $fillable = [
        'nombre',
        'correo',
        'usuario',
        'password',
        'foto',
        'activo',
        'rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Usuarios con acceso TOTAL al panel de Dirección.
     * POR QUÉ: dirección pidió que solo estas personas vean todo el panel.
     */
    public const USUARIOS_DIRECCION = [
        'fredcominu',
        'alex.salazar',
        'jesus.espinoza',
        'sandra.gutierrez',
        'rebeca',
    ];

    /**
     * Usuarios con acceso RESTRINGIDO y qué secciones puede ver cada uno.
     * POR QUÉ: cada persona externa a Dirección solo gestiona su área.
     * Las "secciones" son claves internas que el menú y el bloqueo usan.
     *  - productos  → Alta de producto + Productos
     *  - anticipos  → solo Anticipos dentro de Pagos
     *  - pagos      → todo el módulo de Pagos
     *  - proveedores→ módulo de Proveedores
     *  - reembolsos → Reembolsos a empleados (+ viaje + gasolina) y Alta de Empleados
     */
    public const ACCESOS_RESTRINGIDOS = [
        'brenda.pliego' => ['productos', 'anticipos'],
        'karen.bravo'   => ['pagos', 'proveedores'],
        // POR QUÉ: Nayeli gestiona reembolsos y da de alta empleados. Aún no tiene
        // usuario en la BD; en cuanto se cree con usuario 'nayeli' quedará habilitado.
        'nayeli'        => ['reembolsos'],
    ];

    /** ¿Tiene acceso total al panel de Dirección? */
    public function esDireccion(): bool
    {
        // El rol gerente/admin conserva acceso total (compatibilidad con lo existente)
        if (in_array($this->rol, ['gerente', 'admin'], true)) {
            return true;
        }

        return in_array(strtolower(trim((string) $this->usuario)), self::USUARIOS_DIRECCION, true);
    }

    /** ¿Es un usuario con acceso restringido a ciertas secciones? */
    public function esRestringido(): bool
    {
        if ($this->esDireccion()) {
            return false;
        }

        return array_key_exists(strtolower(trim((string) $this->usuario)), self::ACCESOS_RESTRINGIDOS);
    }

    /** Lista de secciones que este usuario restringido puede ver. */
    public function seccionesPermitidas(): array
    {
        return self::ACCESOS_RESTRINGIDOS[strtolower(trim((string) $this->usuario))] ?? [];
    }

    /** ¿Puede ver una sección específica? (Dirección ve todo). */
    public function puedeVer(string $seccion): bool
    {
        if ($this->esDireccion()) {
            return true;
        }

        return in_array($seccion, $this->seccionesPermitidas(), true);
    }
}
