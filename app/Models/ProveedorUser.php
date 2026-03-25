<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ProveedorUser extends Authenticatable
{
    protected $table = 'proveedores_users';

    protected $fillable = [
        'usuario',
        'password',
        'codigo_compras',
        'nombre',
        'tipo_persona',
        'telefono',
        'correo',
        'activo',
    ];

    protected $hidden = [
        'password',
    ];
}