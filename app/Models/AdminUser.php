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
        'aneso.cominu',
    ];

    /**
     * Usuarios con acceso RESTRINGIDO y qué secciones puede ver cada uno.
     * POR QUÉ: cada persona externa a Dirección solo gestiona su área.
     * Las "secciones" son claves internas que el menú y el bloqueo usan.
     *  - productos    → Catálogo de Productos + todas las altas nacionales/MPI (admin/alta-producto)
     *  - alta_mpi     → SOLO la pantalla de alta de producto (compras importación). No ve el catálogo ni otras altas.
     *  - alta_pt      → SOLO alta de Producto Terminado (comercial PT)
     *  - alta_mto     → SOLO alta de producto Mantenimiento
     *  - anticipos    → solo Anticipos dentro de Pagos
     *  - pagos        → todo el módulo de Pagos
     *  - catalogo     → SOLO ver el catálogo de Productos (admin/productos), sin ninguna alta
     *  - proveedores  → módulo de Proveedores
     *  - reembolsos   → Reembolsos a empleados (+ viaje + gasolina) y Alta de Empleados
     *
     * POR QUÉ están separadas las altas: cada comprador gestiona SOLO su tipo de
     * producto y no debe colarse a las altas de otras áreas. Por eso alta_mpi,
     * alta_pt y alta_mto son secciones distintas que abren rutas distintas.
     */
    public const ACCESOS_RESTRINGIDOS = [
        // Compras Nacional (ME, MP): catálogo + todas las altas + anticipos
        'brenda.pliego'    => ['productos', 'anticipos'],
        // Contabilidad: pagos y proveedores
        'karen.bravo'      => ['pagos', 'proveedores'],
        // Comercial PT: solo alta de Producto Terminado
        'cintia.barrera'   => ['alta_pt'],
        // Mantenimiento: su alta + ver el catálogo de Productos (sin otras altas)
        'blanca.paganoni'  => ['alta_mto', 'catalogo'],
        // Compras Importación (MPI): su alta + ver el catálogo de Productos (sin otras altas)
        'acela.bolanos'    => ['alta_mpi', 'catalogo'],
        // Compras Importación (MPI) + acceso al catálogo de Productos
        'cinthya.martinez' => ['alta_mpi', 'productos'],
        // POR QUÉ: Nayeli gestiona reembolsos y da de alta empleados. Aún no tiene
        // usuario en la BD; en cuanto se cree con usuario 'nayeli' quedará habilitado.
        'nayeli'           => ['reembolsos'],
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
