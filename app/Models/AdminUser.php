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
     * Usuarios con acceso RESTRINGIDO: solo Productos y Anticipos.
     * POR QUÉ: Brenda solo gestiona productos y anticipos; el resto va bloqueado.
     */
    public const USUARIOS_RESTRINGIDOS = [
        'brenda',
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

    /** ¿Es un usuario restringido (solo Productos + Anticipos)? */
    public function esRestringido(): bool
    {
        return in_array(strtolower(trim((string) $this->usuario)), self::USUARIOS_RESTRINGIDOS, true);
    }
}
