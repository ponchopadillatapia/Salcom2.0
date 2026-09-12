<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'numero_empleado', 'nombre', 'departamento', 'correo', 'activo',
        'numero_cuenta', 'titular_cuenta', 'requiere_gasolina',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'requiere_gasolina' => 'boolean',
    ];
}
