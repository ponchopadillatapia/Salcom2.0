<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'numero_empleado', 'nombre', 'departamento', 'correo', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
